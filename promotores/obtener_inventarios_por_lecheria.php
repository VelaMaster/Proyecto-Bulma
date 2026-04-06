<?php
// promotores/obtener_inventarios_por_lecheria.php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Seguridad: Solo promotores
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    echo json_encode([]); exit();
}

try {
    require_once __DIR__ . '/../src/Repositorio/InventarioRepositorio.php';

    $clave = trim($_GET['clave'] ?? '');
    $fecha = trim($_GET['fecha'] ?? '');

    if ($clave === '') { echo json_encode([]); exit; }

    $repositorio = new InventarioRepositorio();
    $filas = $repositorio->buscarPorLecheria($clave, $fecha);

    // Limpieza de caracteres (UTF-8) para que el JSON no truene
    array_walk_recursive($filas, function (&$v) {
        if (is_string($v)) $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8');
    });

    echo json_encode($filas, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}