<?php
// ────────────────────────────────────────────────────────────────────
//  Endpoint mínimo para "Guardar Reporte Mensual".
//  Por ahora solo valida la sesión y deja constancia en datos/promotores
//  como JSON. Si más adelante se añade una tabla dedicada al reporte
//  mensual consolidado, este endpoint es el lugar correcto para
//  persistirlo en BDD.
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
    echo json_encode(['status' => 'error', 'mensaje' => 'Datos del reporte incompletos.']);
    exit();
}

$mes  = (int)($datos['mes_reporte']  ?? 0);
$anio = (int)($datos['anio_reporte'] ?? 0);

// El payload nuevo trae almacenes[]; si llega solo lecherias[] (legacy)
// lo envolvemos para compatibilidad.
$almacenes = $datos['almacenes'] ?? null;
if (!$almacenes && !empty($datos['lecherias'])) {
    $almacenes = [['almacen' => $datos['almacen'] ?? '', 'lecherias' => $datos['lecherias']]];
}

if ($mes < 1 || $mes > 12 || $anio < 2000 || empty($almacenes)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'mensaje' => 'Parámetros del reporte inválidos o sin almacenes.']);
    exit();
}

// Guardamos un snapshot JSON como bitácora (un archivo por mes, con
// todos los almacenes adentro).
$baseDir = __DIR__ . '/../datos/promotores/reportes';
if (!is_dir($baseDir)) @mkdir($baseDir, 0775, true);

$slug = preg_replace('/[^A-Za-z0-9]/', '_', $_SESSION['usuario']);
$nombreArchivo = sprintf('reporte_%04d_%02d_%s.json', $anio, $mes, $slug);
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
    'mensaje'   => 'Reporte guardado.',
    'archivo'   => $nombreArchivo,
    'almacenes' => count($almacenes),
    'lecherias' => $totalLech,
]);
