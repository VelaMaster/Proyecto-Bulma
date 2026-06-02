<?php
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

header('Content-Type: application/json; charset=utf-8');

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$pdo  = DatabaseSQLite::getInstance();

try {
    switch ($body['accion'] ?? '') {
        case 'limpiar':
            $pdo->exec("DELETE FROM errores_log");
            echo json_encode(['ok' => true]);
            break;
        case 'limpiar_tipo':
            $pdo->prepare("DELETE FROM errores_log WHERE tipo=?")->execute([$body['tipo'] ?? '']);
            echo json_encode(['ok' => true]);
            break;
        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'mensaje' => 'Acción desconocida']);
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}
