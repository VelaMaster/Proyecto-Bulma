<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../src/Repositorio/LecheriaRepositorio.php';

    // Bloqueamos si no hay sesión activa, en lugar de bloquear por búsqueda vacía
    if (!isset($_SESSION['usuario'])) {
        echo json_encode([]);
        exit();
    }

    $promotor_id = $_SESSION['usuario']; // Obtenemos el usuario en sesión
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';

    $repo = new LecheriaRepositorio();
    
    // Pasamos tanto la búsqueda (que puede venir vacía) como el promotor
    $data = $repo->searchByTerm($q, $promotor_id);
    
    array_walk_recursive($data, function (&$item) {
        if (is_string($item)) {
            $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
        }
    });
    
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => true,
        "mensaje" => "Error interno: " . $e->getMessage()
    ]);
}