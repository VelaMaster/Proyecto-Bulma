<?php
// promotores/obtener_inventarios_por_lecheria.php
require_once __DIR__ . '/../includes/session_guard.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    echo json_encode([]); exit();
}

try {
    require_once __DIR__ . '/../src/Repositorio/InventarioRepositorio.php';

    $clave = trim($_GET['clave'] ?? '');
    $mes   = (int)($_GET['mes']  ?? 0);
    $anio  = (int)($_GET['anio'] ?? 0);

    // Validación estricta: sin estos tres datos no buscamos NADA
    // (evita que el SQL caiga a "trae todos" y devuelva el último inventario).
    if ($clave === '' || $mes < 1 || $mes > 12 || $anio < 2000) {
        echo json_encode([]); exit;
    }

    $repositorio = new InventarioRepositorio();

    // Pasamos mes y anio separados — el repositorio filtra exacto por periodo
    $filas = $repositorio->buscarPorLecheria($clave, $mes, $anio);

    array_walk_recursive($filas, function (&$v) {
        if (is_string($v)) $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8');
    });

    echo json_encode($filas, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}