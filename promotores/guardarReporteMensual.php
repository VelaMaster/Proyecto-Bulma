<?php
ob_start(); // captura cualquier output accidental (warnings, notices) antes del JSON
require_once __DIR__ . '/../includes/session_guard.php';

// Registrar errores fatales como JSON (en lugar de romper la respuesta)
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'  => 'error',
            'mensaje' => 'Error fatal PHP: ' . $err['message'] . ' en ' . $err['file'] . ':' . $err['line'],
        ]);
    }
});

ob_end_clean(); // limpia cualquier output de session_guard antes del header
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'mensaje' => 'Sesión no válida.']);
    exit();
}

$json  = file_get_contents('php://input');
$datos = json_decode($json, true);
if (!is_array($datos)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'mensaje' => 'Datos del reporte incompletos.']);
    exit();
}

$mes  = (int)($datos['mes_reporte']  ?? 0);
$anio = (int)($datos['anio_reporte'] ?? 0);

$almacenes = $datos['almacenes'] ?? null;
if (!$almacenes && !empty($datos['lecherias'])) {
    $almacenes = [['almacen' => $datos['almacen'] ?? '', 'lecherias' => $datos['lecherias']]];
}
if ($mes < 1 || $mes > 12 || $anio < 2000 || empty($almacenes)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'mensaje' => 'Parámetros del reporte inválidos.']);
    exit();
}

$usuario    = $_SESSION['usuario'];
$periodoIni = $datos['periodo_inicio'] ?? '';
$periodoFin = $datos['periodo_fin']    ?? '';
$promotor   = $datos['promotor']       ?? '';
$supervisor = $datos['supervisor']     ?? '';

// ── 1. SQLite ─────────────────────────────────────────────────────
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

try {
    $db = DatabaseSQLite::getInstance();

    $sql = "INSERT OR REPLACE INTO reporte_mensual_lecher
            (clave_lecheria, mes, anio,
             almacen, precio,
             inv_ini_cajas, inv_ini_sobres, dot_recib_cajas,
             total_cajas, total_sobres,
             vend_cajas, vend_sobres,
             inv_fin_cajas, inv_fin_sobres,
             retiro_cajas, retiro_sobres,
             familias_no_acud, sobres_rotos, sobres_falt,
             observaciones, periodo_inicio, periodo_fin,
             promotor, supervisor, usuario_captura, fecha_captura)
            VALUES
            (:clave, :mes, :anio,
             :almacen, :precio,
             :ini_c, :ini_s, :dot_c,
             :tot_c, :tot_s,
             :vnd_c, :vnd_s,
             :fin_c, :fin_s,
             :ret_c, :ret_s,
             :fam, :rotos, :falt,
             :obs, :pini, :pfin,
             :prom, :sup, :usr, datetime('now','localtime'))";

    $stmt = $db->prepare($sql);
    $db->beginTransaction();

    $total = 0;
    foreach ($almacenes as $bloque) {
        $alm       = trim((string)($bloque['almacen'] ?? ''));
        $lecherias = $bloque['lecherias'] ?? [];
        foreach ($lecherias as $l) {
            $clave = trim((string)($l['punto_venta'] ?? ''));
            if ($clave === '') continue;
            $obs = isset($l['observaciones']) && trim((string)$l['observaciones']) !== ''
                   ? trim((string)$l['observaciones']) : 'x';
            $stmt->execute([
                ':clave'  => $clave,  ':mes'   => $mes,   ':anio'  => $anio,
                ':almacen'=> $alm,    ':precio' => $l['precio'] ?? '',
                ':ini_c'  => (int)($l['inv_ini_cajas']      ?? 0),
                ':ini_s'  => (int)($l['inv_ini_sobres']     ?? 0),
                ':dot_c'  => (int)($l['dot_recibida_cajas'] ?? 0),
                ':tot_c'  => (int)($l['total_cajas']        ?? 0),
                ':tot_s'  => (int)($l['total_sobres']       ?? 0),
                ':vnd_c'  => (int)($l['dot_vend_cajas']     ?? 0),
                ':vnd_s'  => (int)($l['dot_vend_sobres']    ?? 0),
                ':fin_c'  => (int)($l['inv_fin_cajas']      ?? 0),
                ':fin_s'  => (int)($l['inv_fin_sobres']     ?? 0),
                ':ret_c'  => (int)($l['retiro_cajas']       ?? 0),
                ':ret_s'  => (int)($l['retiro_sobres']      ?? 0),
                ':fam'    => (int)($l['familias_no_acud']   ?? 0),
                ':rotos'  => (int)($l['sobres_rotos']       ?? 0),
                ':falt'   => (int)($l['sobres_falt']        ?? 0),
                ':obs'    => $obs,
                ':pini'   => $periodoIni, ':pfin' => $periodoFin,
                ':prom'   => $promotor,   ':sup'  => $supervisor,
                ':usr'    => $usuario,
            ]);
            $total++;
        }
    }
    $db->commit();

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'mensaje' => 'Error BD: ' . $e->getMessage()]);
    exit();
}

// ── 2. Backup JSON ────────────────────────────────────────────────
$baseDir = __DIR__ . '/../datos/promotores/reportes';
if (!is_dir($baseDir)) @mkdir($baseDir, 0775, true);
$slug    = preg_replace('/[^A-Za-z0-9]/', '_', $usuario);
$archivo = sprintf('reporte_%04d_%02d_%s.json', $anio, $mes, $slug);
@file_put_contents(
    $baseDir . '/' . $archivo,
    json_encode(['usuario' => $usuario, 'guardado_en' => date('c'), 'datos' => $datos],
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
);

// ── 3. PDF ────────────────────────────────────────────────────────
$pdfGenerado = false; $pdfNombre = null;
ob_start();
try {
    require_once __DIR__ . '/_fn_pdf_reporte.php';
    $ruta = generarArchivoReporte($datos, $slug);
    if ($ruta) { $pdfGenerado = true; $pdfNombre = basename($ruta); }
} catch (Throwable $e) {}
ob_end_clean();

echo json_encode([
    'status'       => 'success',
    'mensaje'      => 'Reporte guardado' . ($pdfGenerado ? ' y PDF generado.' : '.'),
    'lecherias_bd' => $total,
    'pdf_generado' => $pdfGenerado,
    'pdf_nombre'   => $pdfNombre,
], JSON_UNESCAPED_UNICODE);
