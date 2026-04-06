<?php
header('Content-Type: application/json; charset=utf-8');
try {
    require_once '../src/Repositorio/LecheriaRepositorio.php';

    $q = isset($_GET['q']) ? trim($_GET['q']) : '';

    if ($q === '') {
        echo json_encode([]);
        exit();
    }
    $repo = new LecheriaRepository();
    $data = $repo->searchByTerm($q);
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