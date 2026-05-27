<?php
/**
 * Sirve PDFs generados por el supervisor (requerimientos_dotacion).
 * GET: archivo=<nombre_archivo>
 */
require_once __DIR__ . '/../includes/session_guard.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    http_response_code(401);
    exit('Acceso denegado.');
}

$archivo = basename(trim($_GET['archivo'] ?? ''));
if ($archivo === '') {
    http_response_code(400);
    exit('Parámetro inválido.');
}

$base     = realpath(__DIR__ . '/../datos/supervisores/requerimientos_dotacion');
$rutaReal = realpath($base . '/' . $archivo);

if (!$rutaReal || strpos($rutaReal, $base) !== 0 || !is_file($rutaReal)) {
    http_response_code(404);
    exit('Archivo no encontrado.');
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $archivo . '"');
header('Content-Length: ' . filesize($rutaReal));
header('Cache-Control: private, max-age=60');
readfile($rutaReal);
