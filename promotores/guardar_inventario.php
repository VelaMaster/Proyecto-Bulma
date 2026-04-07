
<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../src/Repositorio/InventarioRepositorio.php';

try {
    $datos = json_decode(file_get_contents('php://input'), true);
    $usuario = $datos['usuario'] ?? 'Sistema';

    $repo = new InventarioRepositorio();
    $respuesta = $repo->guardar($datos, $usuario);

    echo json_encode($respuesta);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'mensaje' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}