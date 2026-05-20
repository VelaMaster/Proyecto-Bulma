<?php
/**
 * regenerar_reporte_pdf.php
 * Llamado por el Service Worker tras sincronizar un inventario editado.
 * Lee el JSON snapshot del reporte (si existe) y regenera el PDF a disco.
 * No envía el PDF al browser — solo responde JSON de estado.
 */
require_once __DIR__ . '/../includes/session_guard.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'mensaje' => 'Sesión no válida.']);
    exit;
}

$json  = file_get_contents('php://input');
$input = json_decode($json, true);

$mes  = (int)($input['mes']  ?? 0);
$anio = (int)($input['anio'] ?? 0);

if ($mes < 1 || $mes > 12 || $anio < 2000) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'mensaje' => 'Mes o año inválido.']);
    exit;
}

$slug = preg_replace('/[^A-Za-z0-9]/', '_', $_SESSION['usuario']);

// Buscar el JSON snapshot del reporte para este mes/año/usuario
$rutaJSON = sprintf(
    __DIR__ . '/../datos/promotores/reportes/reporte_%04d_%02d_%s.json',
    $anio, $mes, $slug
);

if (!file_exists($rutaJSON)) {
    // No hay snapshot → nada que regenerar (el promotor nunca generó el reporte)
    echo json_encode(['status' => 'skip', 'mensaje' => 'Sin snapshot JSON; no se requiere regenerar PDF.']);
    exit;
}

$contenido = @file_get_contents($rutaJSON);
$snapshot  = json_decode($contenido, true);

if (!is_array($snapshot) || empty($snapshot['datos'])) {
    echo json_encode(['status' => 'error', 'mensaje' => 'Snapshot JSON inválido.']);
    exit;
}

$datos = $snapshot['datos'];

// Asegurarnos de que los campos de mes/año sean correctos
$datos['mes_reporte']  = $datos['mes_reporte']  ?? $mes;
$datos['anio_reporte'] = $datos['anio_reporte'] ?? $anio;

require_once __DIR__ . '/_fn_pdf_reporte.php';
$ruta = generarArchivoReporte($datos, $slug);

if ($ruta === false) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'mensaje' => 'No se pudo regenerar el PDF.']);
    exit;
}

// Actualizar timestamp en el snapshot para indicar regeneración
$snapshot['pdf_regenerado_en'] = date('c');
@file_put_contents($rutaJSON, json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo json_encode([
    'status'  => 'success',
    'mensaje' => 'PDF regenerado correctamente.',
    'pdf'     => basename($ruta),
]);
