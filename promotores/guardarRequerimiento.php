<?php
ob_start();
require_once __DIR__ . '/../includes/session_guard.php';

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

ob_end_clean();
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
    echo json_encode(['status' => 'error', 'mensaje' => 'Datos del requerimiento incompletos.']);
    exit();
}

$mesBase  = (int)($datos['mes_base']     ?? 0);
$anioBase = (int)($datos['anio_base']    ?? 0);
$mesDest  = (int)($datos['mes_destino']  ?? 0);
$anioDest = (int)($datos['anio_destino'] ?? 0);

$almacenes = $datos['almacenes'] ?? null;
if (!$almacenes && !empty($datos['lecherias'])) {
    $almacenes = [['almacen' => $datos['almacen'] ?? '', 'lecherias' => $datos['lecherias']]];
}
if ($mesBase < 1 || $mesBase > 12 || $anioBase < 2000 || empty($almacenes)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'mensaje' => 'Parámetros del requerimiento inválidos.']);
    exit();
}

$promotorId = (int)($_SESSION['clave_promotor'] ?? $_SESSION['clave_rol'] ?? 0);
$usuario    = (string)$_SESSION['usuario'];

// ── 1. SQLite ─────────────────────────────────────────────────────
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';
try {
    $db = DatabaseSQLite::getInstance();

    $sql = "INSERT OR REPLACE INTO requerimiento_dotacion
            (clave_lecheria, mes_base, anio_base,
             promotor, mes_destino, anio_destino,
             familias, beneficiarios, dotacion_teorica,
             inv_inicial, surtimiento, ventas, inv_final,
             req_ms_anterior, vms, req_actual,
             observaciones, usuario_captura, fecha_captura)
            VALUES
            (:clave, :mes_b, :anio_b,
             :prom, :mes_d, :anio_d,
             :fam, :ben, :dot,
             :ii, :surt, :ven, :ifn,
             :rma, :vms, :ra,
             :obs, :usr, datetime('now','localtime'))";

    $stmt = $db->prepare($sql);
    $db->beginTransaction();

    $totalLech = 0;
    $errores   = [];
    foreach ($almacenes as $bloque) {
        foreach ($bloque['lecherias'] ?? [] as $l) {
            $clave = trim((string)($l['punto_venta'] ?? ''));
            if ($clave === '') continue;
            try {
                $stmt->execute([
                    ':clave'  => $clave,
                    ':mes_b'  => $mesBase,   ':anio_b' => $anioBase,
                    ':prom'   => $promotorId ?: null,
                    ':mes_d'  => $mesDest ?: null, ':anio_d' => $anioDest ?: null,
                    ':fam'    => (int)($l['familias']         ?? 0),
                    ':ben'    => (int)($l['beneficiarios']    ?? 0),
                    ':dot'    => (int)($l['dotacion_teorica'] ?? 0),
                    ':ii'     => mb_substr((string)($l['inv_inicial'] ?? ''), 0, 30),
                    ':surt'   => (int)($l['surtimiento']      ?? 0),
                    ':ven'    => mb_substr((string)($l['ventas']      ?? ''), 0, 30),
                    ':ifn'    => mb_substr((string)($l['inv_final']   ?? ''), 0, 30),
                    ':rma'    => (int)($l['req_ms_anterior']  ?? 0),
                    ':vms'    => (int)($l['vms']              ?? 0),
                    ':ra'     => (int)($l['req_actual']       ?? 0),
                    ':obs'    => mb_substr((string)($l['observaciones'] ?? ''), 0, 500),
                    ':usr'    => mb_substr($usuario, 0, 50),
                ]);
                $totalLech++;
            } catch (Exception $eFila) {
                $errores[] = ['lecheria' => $clave, 'error' => $eFila->getMessage()];
            }
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
$baseDir = __DIR__ . '/../datos/promotores/requerimientos';
if (!is_dir($baseDir)) @mkdir($baseDir, 0775, true);
$slug    = preg_replace('/[^A-Za-z0-9]/', '_', $usuario);
$archivo = sprintf('req_%04d_%02d_%s.json', $anioBase, $mesBase, $slug);
@file_put_contents(
    $baseDir . '/' . $archivo,
    json_encode(['usuario' => $usuario, 'guardado_en' => date('c'), 'datos' => $datos],
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
);

// ── 3. PDF ────────────────────────────────────────────────────────
$pdfGenerado = false; $pdfNombre = null;
ob_start();
try {
    require_once __DIR__ . '/_fn_pdf_requerimiento.php';
    $ruta = generarArchivoRequerimiento($datos, $slug);
    if ($ruta) { $pdfGenerado = true; $pdfNombre = basename($ruta); }
} catch (Throwable $e) {}
ob_end_clean();

echo json_encode([
    'status'       => 'success',
    'mensaje'      => 'Requerimiento guardado' . ($pdfGenerado ? ' y PDF generado.' : '.'),
    'lecherias'    => $totalLech,
    'errores'      => $errores,
    'pdf_generado' => $pdfGenerado,
    'pdf_nombre'   => $pdfNombre,
], JSON_UNESCAPED_UNICODE);
