<?php
/**
 * listar_pdfs.php
 * Devuelve JSON con los PDFs (reporte + requerimiento) del promotor en sesión.
 * Filtrado por slug de usuario en el nombre del archivo.
 */
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    http_response_code(403);
    echo json_encode(['error' => true, 'mensaje' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
// Permitir que el SW lo cachee
header('Cache-Control: private, max-age=300'); // 5 min

$slugUsr = preg_replace('/[^A-Za-z0-9]/', '_', $_SESSION['usuario']);
$dirBase = __DIR__ . '/../datos/promotores/';

$carpetas = [
    'reportes_pdf'      => 'Reporte Mensual',
    'requerimientos_pdf' => 'Requerimiento',
];

$archivos = [];

foreach ($carpetas as $subcarpeta => $tipo) {
    $dir = $dirBase . $subcarpeta . '/';
    if (!is_dir($dir)) continue;

    foreach (scandir($dir) as $archivo) {
        if ($archivo === '.' || $archivo === '..') continue;
        if (strtolower(pathinfo($archivo, PATHINFO_EXTENSION)) !== 'pdf') continue;
        // Solo archivos del usuario actual
        if (!str_contains(strtolower($archivo), strtolower($slugUsr))) continue;

        $ruta = $dir . $archivo;
        $archivos[] = [
            'nombre'   => $archivo,
            'tipo'     => $tipo,
            'tamanio'  => filesize($ruta),
            'fecha'    => date('Y-m-d H:i:s', filemtime($ruta)),
            'url_ver'  => 'ver_pdf.php?archivo=' . urlencode($subcarpeta . '/' . $archivo),
            'url_dl'   => 'ver_pdf.php?archivo=' . urlencode($subcarpeta . '/' . $archivo) . '&dl=1',
        ];
    }
}

// Orden: más reciente primero
usort($archivos, fn($a, $b) => strcmp($b['fecha'], $a['fecha']));

echo json_encode($archivos, JSON_UNESCAPED_UNICODE);
