<?php
/**
 * generar_pdf_reporte.php
 * Genera el PDF del Reporte Mensual, lo guarda a disco Y lo envía al browser.
 */
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();

$json  = file_get_contents('php://input');
$datos = json_decode($json, true);

if (!is_array($datos)) {
    http_response_code(400);
    exit('No se recibieron datos del reporte.');
}

$slugUsr = preg_replace('/[^A-Za-z0-9]/', '_', $_SESSION['usuario'] ?? 'promotor');

require_once __DIR__ . '/_fn_pdf_reporte.php';
$ruta = generarArchivoReporte($datos, $slugUsr);

if ($ruta === false) {
    http_response_code(422);
    exit('No se pudo generar el PDF. Verifica los datos.');
}

// Enviar al browser
if (ob_get_length()) ob_end_clean();
$nombreArchivo = basename($ruta);
header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($ruta));
header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');
header('Cache-Control: private, max-age=604800');
readfile($ruta);
