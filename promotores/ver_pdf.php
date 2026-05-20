<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    http_response_code(403); exit('No autorizado');
}

// Permitir subcarpetas seguras (ej: reportes_pdf/Reporte_2026_01_viany.pdf)
$solicitado = $_GET['archivo'] ?? '';
// Sanitizar: solo letras, números, _, -, . y /
$solicitado = preg_replace('/[^A-Za-z0-9_\-\.\/]/', '', $solicitado);
// Evitar path traversal
$solicitado = str_replace(['..', '//'], '', $solicitado);
$solicitado = ltrim($solicitado, '/');

$ruta      = realpath(__DIR__ . '/../datos/promotores/' . $solicitado);
$baseDir   = realpath(__DIR__ . '/../datos/promotores');
$descargar = isset($_GET['dl']);

// Validar: archivo existe, es PDF, y está dentro del directorio permitido
if (
    $ruta === false ||
    !file_exists($ruta) ||
    strpos($ruta, $baseDir) !== 0 ||
    strtolower(pathinfo($ruta, PATHINFO_EXTENSION)) !== 'pdf'
) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => true, 'mensaje' => 'Archivo no encontrado o removido']);
    exit;
}

$nombreArchivo = basename($ruta);

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($ruta));
// Cacheable por el SW (7 días), privado para el usuario
header('Cache-Control: private, max-age=604800');

if ($descargar) {
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
} else {
    header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');
}

readfile($ruta);
exit;