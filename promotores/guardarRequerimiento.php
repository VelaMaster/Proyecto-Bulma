<?php
// ────────────────────────────────────────────────────────────────────
//  Endpoint mínimo para "Guardar Requerimiento".
//  Persiste un snapshot JSON en datos/promotores/requerimientos como
//  bitácora; si más adelante se añade tabla dedicada, este es el lugar.
// ────────────────────────────────────────────────────────────────────
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'mensaje' => 'Sesión no válida.']);
    exit();
}

$json  = file_get_contents('php://input');
$datos = json_decode($json, true);

if (!is_array($datos)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'mensaje' => 'Datos del requerimiento incompletos.']);
    exit();
}

$mesBase  = (int)($datos['mes_base']  ?? 0);
$anioBase = (int)($datos['anio_base'] ?? 0);

$almacenes = $datos['almacenes'] ?? null;
if (!$almacenes && !empty($datos['lecherias'])) {
    $almacenes = [['almacen' => $datos['almacen'] ?? '', 'lecherias' => $datos['lecherias']]];
}

if ($mesBase < 1 || $mesBase > 12 || $anioBase < 2000 || empty($almacenes)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'mensaje' => 'Parámetros del requerimiento inválidos o sin almacenes.']);
    exit();
}

$baseDir = __DIR__ . '/../datos/promotores/requerimientos';
if (!is_dir($baseDir)) @mkdir($baseDir, 0775, true);

$slug = preg_replace('/[^A-Za-z0-9]/', '_', $_SESSION['usuario']);
$nombreArchivo = sprintf('req_%04d_%02d_%s.json', $anioBase, $mesBase, $slug);
$ruta = $baseDir . '/' . $nombreArchivo;

@file_put_contents($ruta, json_encode([
    'usuario'     => $_SESSION['usuario'],
    'guardado_en' => date('c'),
    'datos'       => $datos,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

$totalLech = 0;
foreach ($almacenes as $a) $totalLech += count($a['lecherias'] ?? []);

echo json_encode([
    'status'    => 'success',
    'mensaje'   => 'Requerimiento guardado.',
    'archivo'   => $nombreArchivo,
    'almacenes' => count($almacenes),
    'lecherias' => $totalLech,
]);
