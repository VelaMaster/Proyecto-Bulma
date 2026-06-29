<?php
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';
require_once __DIR__ . '/../src/Servicio/SincronizadorFirebird.php';

header('Content-Type: application/json; charset=utf-8');

$body  = json_decode(file_get_contents('php://input'), true) ?: [];
$tabla = $body['tabla'] ?? '';

$sync  = new SincronizadorFirebird();

// Resolver entorno activo (Liconsa real vs Docker) para que el cliente vea
// claramente desde dónde se está jalando la información.
$entorno = $sync->entornoActual();
$fuente = [
    'origen'      => 'Firebird — ' . $entorno['nombre'],
    'codigo'      => $entorno['codigo'],       // 'liconsa' | 'docker'
    'host'        => $entorno['host'],
    'puerto'      => DatabaseSQLite::getConfig('fb_port', '3050'),
    'bdd_remota'  => $entorno['bdd'],
    'destino'     => 'SQLite local',
];

if ($tabla === '__todo__') {
    $resultados = $sync->sincronizarTodo();
    echo json_encode(['fuente' => $fuente, 'resultados' => $resultados]);
} elseif ($tabla !== '') {
    $r = $sync->sincronizar($tabla);
    echo json_encode(array_merge($r, ['fuente' => $fuente]));
} else {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'mensaje'=>'Falta parámetro "tabla"']);
}
