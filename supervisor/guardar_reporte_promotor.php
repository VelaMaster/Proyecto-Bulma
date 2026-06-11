<?php
// supervisor/guardar_reporte_promotor.php
// El supervisor guarda o crea el reporte mensual de un promotor (por PMT_NUMERO).
// usuario_captura = USUARIO del promotor (si tiene login) o "promotor_<id>".
ob_start();
require_once __DIR__ . '/../includes/session_guard.php';
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error',
            'mensaje' => 'Error fatal: ' . $err['message']]);
    }
});
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'mensaje' => 'Sesión no válida.']);
    exit();
}
$id_supervisor = $_SESSION['clave_rol'] ?? null;
$nombreSup     = $_SESSION['nombre']    ?? $_SESSION['usuario'];

$datos = json_decode(file_get_contents('php://input'), true);
if (!is_array($datos)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'mensaje' => 'Datos inválidos.']);
    exit();
}

$promotor = (int)($datos['promotor_id']   ?? 0);
$mes      = (int)($datos['mes_reporte']   ?? 0);
$anio     = (int)($datos['anio_reporte']  ?? 0);
$almacenes  = $datos['almacenes']      ?? [];
$periodoIni = $datos['periodo_inicio'] ?? '';
$periodoFin = $datos['periodo_fin']    ?? '';
$promNombre = $datos['promotor']       ?? '';
$supervisor = $datos['supervisor']     ?? $nombreSup;

if ($promotor <= 0 || $mes < 1 || $mes > 12 || $anio < 2000 || empty($almacenes)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'mensaje' => 'Parámetros incompletos.']);
    exit();
}

require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

try {
    $db = DatabaseSQLite::getInstance();

    // Permiso: alguna lechería del promotor debe estar mapeada al supervisor
    $st = $db->prepare("
        SELECT COUNT(*) FROM lecheria L
        JOIN mapeo_supervisor_lecheria M
              ON M.LECHER = L.LECHER AND M.ID_SUPERVISOR = :sup
        WHERE L.PROMOTOR = :prom
    ");
    $st->execute([':sup' => $id_supervisor, ':prom' => $promotor]);
    if ((int)$st->fetchColumn() === 0) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'mensaje' => 'Promotor fuera de tu zona.']);
        exit();
    }

    // Resolver usuario_captura
    $st = $db->prepare("SELECT USUARIO FROM usuarios_inventarios
                        WHERE CLAVE_ROL = :id AND ROL = 'promotor' LIMIT 1");
    $st->execute([':id' => $promotor]);
    $usuarioReal = $st->fetchColumn() ?: null;
    $usuarioKey  = $usuarioReal ?: ('promotor_' . $promotor);

    // ¿Ya aprobado?
    $keysCheck = array_values(array_filter(array_unique([$usuarioReal, 'promotor_' . $promotor])));
    $ph = implode(',', array_fill(0, count($keysCheck), '?'));
    $st = $db->prepare("SELECT MAX(aprobado) FROM reporte_mensual_lecher
                        WHERE usuario_captura IN ($ph) AND mes = ? AND anio = ?");
    $st->execute(array_merge($keysCheck, [$mes, $anio]));
    if ((int)$st->fetchColumn() === 1) {
        http_response_code(403);
        echo json_encode(['status' => 'aprobado',
            'mensaje' => 'Reporte ya aprobado. No se puede editar.']);
        exit();
    }

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
             promotor, supervisor, usuario_captura, fecha_captura,
             bloqueado)
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
             :prom, :sup, :usr, datetime('now','localtime'),
             1)";
    $stmt = $db->prepare($sql);
    $db->beginTransaction();
    $total = 0;
    foreach ($almacenes as $bloque) {
        $alm = trim((string)($bloque['almacen'] ?? ''));
        foreach (($bloque['lecherias'] ?? []) as $l) {
            $clave = trim((string)($l['punto_venta'] ?? ''));
            if ($clave === '') continue;
            $obs = isset($l['observaciones']) && trim((string)$l['observaciones']) !== ''
                   ? trim((string)$l['observaciones']) : 'x';
            $stmt->execute([
                ':clave'  => $clave,  ':mes' => $mes, ':anio' => $anio,
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
                ':prom'   => $promNombre, ':sup'  => $supervisor,
                ':usr'    => $usuarioKey,
            ]);
            $total++;
        }
    }
    $db->commit();

    echo json_encode([
        'status'          => 'success',
        'mensaje'         => 'Reporte guardado.',
        'lecherias_bd'    => $total,
        'usuario_captura' => $usuarioKey,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
}
