<?php
// GET ?mes=5&anio=2026
require_once __DIR__ . '/../includes/session_guard.php';
session_write_close();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'mensaje' => 'Sesión no válida.']);
    exit();
}

$mes  = isset($_GET['mes'])  ? (int)$_GET['mes']  : 0;
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : 0;
if ($mes < 1 || $mes > 12 || $anio < 2000) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'mensaje' => 'Mes/año inválido.']);
    exit();
}

require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';
try {
    $db   = DatabaseSQLite::getInstance();
    $stmt = $db->prepare("
        SELECT * FROM reporte_mensual_lecher
        WHERE mes = :mes AND anio = :anio AND usuario_captura = :usr
        ORDER BY almacen, clave_lecheria
    ");
    $stmt->execute([':mes' => $mes, ':anio' => $anio, ':usr' => $_SESSION['usuario']]);
    $rows = $stmt->fetchAll();

    $almacenesMap = [];
    $meta = [];
    foreach ($rows as $r) {
        $alm = $r['almacen'] ?? '';
        if (!isset($almacenesMap[$alm])) $almacenesMap[$alm] = [];
        if (empty($meta)) {
            $meta = [
                'periodo_inicio' => $r['periodo_inicio'] ?? '',
                'periodo_fin'    => $r['periodo_fin']    ?? '',
                'promotor'       => $r['promotor']       ?? '',
                'supervisor'     => $r['supervisor']     ?? '',
            ];
        }
        // Renombrar punto_venta para compatibilidad con el JS
        $r['punto_venta'] = $r['clave_lecheria'];
        unset($r['almacen'], $r['periodo_inicio'], $r['periodo_fin'],
              $r['promotor'], $r['supervisor'], $r['fecha_captura'],
              $r['usuario_captura']);
        $almacenesMap[$alm][] = $r;
    }

    $almacenesOut = [];
    foreach ($almacenesMap as $nombre => $lecherias) {
        $almacenesOut[] = ['almacen' => $nombre, 'lecherias' => $lecherias];
    }

    echo json_encode([
        'status'          => 'success',
        'encontrado'      => !empty($almacenesOut),
        'almacenes'       => $almacenesOut,
        'total_lecherias' => count($rows),
        'meta'            => $meta,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
}
