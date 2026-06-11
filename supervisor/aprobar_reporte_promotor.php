<?php
// supervisor/aprobar_reporte_promotor.php
// Marca el reporte como aprobado para Distribución (clave: PMT_NUMERO).
require_once __DIR__ . '/../includes/session_guard.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'mensaje' => 'Acceso denegado.']);
    exit();
}

$id_supervisor = $_SESSION['clave_rol'] ?? null;
$nombreSup     = $_SESSION['nombre']    ?? $_SESSION['usuario'];

$datos    = json_decode(file_get_contents('php://input'), true) ?: [];
$promotor = (int)($datos['promotor_id'] ?? 0);
$mes      = (int)($datos['mes']  ?? 0);
$anio     = (int)($datos['anio'] ?? 0);

if ($promotor <= 0 || $mes < 1 || $mes > 12 || $anio < 2000) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'mensaje' => 'Parámetros inválidos.']);
    exit();
}

require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';
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

    // Resolver posibles llaves
    $st = $db->prepare("SELECT USUARIO FROM usuarios_inventarios
                        WHERE CLAVE_ROL = :id AND ROL = 'promotor' LIMIT 1");
    $st->execute([':id' => $promotor]);
    $usuarioReal = $st->fetchColumn() ?: null;
    $keys = array_values(array_filter(array_unique([$usuarioReal, 'promotor_' . $promotor])));
    $ph = implode(',', array_fill(0, count($keys), '?'));

    $st = $db->prepare("SELECT COUNT(*) FROM reporte_mensual_lecher
                        WHERE usuario_captura IN ($ph) AND mes = ? AND anio = ?");
    $st->execute(array_merge($keys, [$mes, $anio]));
    if ((int)$st->fetchColumn() === 0) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'mensaje' => 'No existe reporte para ese periodo.']);
        exit();
    }

    $up = $db->prepare("
        UPDATE reporte_mensual_lecher
        SET aprobado = 1,
            supervisor_aprobador = ?,
            fecha_aprobacion     = datetime('now','localtime'),
            bloqueado            = 1
        WHERE usuario_captura IN ($ph) AND mes = ? AND anio = ?
    ");
    $up->execute(array_merge([$nombreSup], $keys, [$mes, $anio]));

    echo json_encode([
        'status'    => 'success',
        'mensaje'   => 'Reporte aprobado para Distribución.',
        'afectadas' => $up->rowCount(),
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
}
