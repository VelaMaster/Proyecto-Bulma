<?php
require_once __DIR__ . '/../includes/session_guard.php';
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    http_response_code(403); exit();
}

$json  = file_get_contents('php://input');
$datos = json_decode($json, true);

if (!$datos) {
    http_response_code(400);
    exit('No se recibieron datos.');
}

require_once __DIR__ . '/_fn_pdf_inventario.php';

$nombreArchivo = generarArchivoInventario($datos);

if (!$nombreArchivo) {
    http_response_code(500);
    exit('Error al generar el PDF.');
}

$rutaCompleta = __DIR__ . '/../datos/promotores/' . $nombreArchivo;

if (!file_exists($rutaCompleta)) {
    http_response_code(500);
    exit('PDF generado pero no encontrado en disco.');
}

// Enviar al navegador
if (ob_get_length()) ob_end_clean();
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');
header('Content-Length: ' . filesize($rutaCompleta));
readfile($rutaCompleta);
