<?php
// promotores/obtener_inventario.php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    echo json_encode(['status' => 'error', 'mensaje' => 'No autorizado']); exit();
}

try {
    require_once __DIR__ . '/../src/Repositorio/InventarioRepositorio.php';

    $id = $_GET['id'] ?? null;
    if (!$id || !is_numeric($id)) {
        echo json_encode(['status' => 'error', 'mensaje' => 'ID inválido']); exit;
    }

    $repositorio = new InventarioRepositorio();
    $datos = $repositorio->obtenerPorId($id);

    if ($datos) {
        // Limpieza UTF-8
        array_walk_recursive($datos, function (&$v) {
            if (is_string($v)) $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8');
        });
        echo json_encode(['status' => 'success', 'datos' => $datos], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['status' => 'error', 'mensaje' => 'Inventario no encontrado']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
}