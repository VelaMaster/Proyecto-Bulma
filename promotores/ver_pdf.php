<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    http_response_code(403); exit('No autorizado');
}

$archivo  = basename($_GET['archivo'] ?? '');   // solo nombre, sin rutas
$ruta     = __DIR__ . '/../datos/promotores/' . $archivo;
$descargar = isset($_GET['dl']);  // ?dl para forzar descarga

if ($archivo === '' || !file_exists($ruta) || pathinfo($ruta, PATHINFO_EXTENSION) !== 'pdf') {
    http_response_code(404); exit('Archivo no encontrado');
}

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($ruta));

if ($descargar) {
    header('Content-Disposition: attachment; filename="' . $archivo . '"');
} else {
    header('Content-Disposition: inline; filename="' . $archivo . '"');
}

header('Cache-Control: private, max-age=0, must-revalidate');
readfile($ruta);
exit;