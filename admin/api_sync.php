<?php
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../src/Servicio/SincronizadorFirebird.php';

header('Content-Type: application/json; charset=utf-8');

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$tabla = $body['tabla'] ?? '';

$sync = new SincronizadorFirebird();

if ($tabla === '__todo__') {
    echo json_encode($sync->sincronizarTodo());
} elseif ($tabla !== '') {
    echo json_encode($sync->sincronizar($tabla));
} else {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'mensaje'=>'Falta parámetro "tabla"']);
}
