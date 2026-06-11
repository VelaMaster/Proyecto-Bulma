<?php
// supervisor/obtener_reporte_promotor.php
// GET ?promotor=PMT_NUMERO&mes=...&anio=...
// Devuelve el reporte_mensual_lecher buscando por dos posibles llaves:
//   - usuario_captura = U.USUARIO (si el promotor tiene login)
//   - usuario_captura = "promotor_<id>" (placeholder cuando el supervisor lo capturó)
require_once __DIR__ . '/../includes/session_guard.php';
session_write_close();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'mensaje' => 'Acceso denegado.']);
    exit();
}

$promotor = isset($_GET['promotor']) ? (int)$_GET['promotor'] : 0;
$mes      = isset($_GET['mes'])      ? (int)$_GET['mes']  : 0;
$anio     = isset($_GET['anio'])     ? (int)$_GET['anio'] : 0;

if ($promotor <= 0 || $mes < 1 || $mes > 12 || $anio < 2000) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'mensaje' => 'Parámetros inválidos.']);
    exit();
}

$id_supervisor = $_SESSION['clave_rol'] ?? null;
try {
    $db = DatabaseSQLite::getInstance();

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

    // Resolver posibles usuario_captura
    $st = $db->prepare("SELECT USUARIO FROM usuarios_inventarios
                        WHERE CLAVE_ROL = :id AND ROL = 'promotor' LIMIT 1");
    $st->execute([':id' => $promotor]);
    $usuarioReal = $st->fetchColumn() ?: null;
    $usuarioFallback = 'promotor_' . $promotor;

    $keys = array_values(array_filter(array_unique([$usuarioReal, $usuarioFallback])));
    $ph   = implode(',', array_fill(0, count($keys), '?'));
    $params = array_merge($keys, [$mes, $anio]);

    $stmt = $db->prepare("
        SELECT * FROM reporte_mensual_lecher
        WHERE usuario_captura IN ($ph) AND mes = ? AND anio = ?
        ORDER BY almacen, clave_lecheria
    ");
    $stmt->execute($params);
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
                'aprobado'       => (int)($r['aprobado'] ?? 0),
            ];
        }
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
