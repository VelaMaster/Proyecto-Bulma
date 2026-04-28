<?php
// supervisor/api_supervisor.php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/Repositorio/UsuarioRepositorio.php';

// Validación de seguridad para que solo el supervisor pueda consultar esto
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado.']);
    exit();
}
$id_supervisor = $_SESSION['clave_rol'] ?? null;
if (!$id_supervisor) {
    echo json_encode(['status' => 'error', 'message' => 'ID de supervisor no encontrado en la sesión.']);
    exit();
}

try {
    $repo = new UsuarioRepositorio();
    $promotores = $repo->obtenerPromotoresDeSupervisor($id_supervisor);
    
    if ($promotores === false) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Hubo un problema al consultar la base de datos. Revisa los logs.'
        ]);
        exit();
    }

    // --- LA MAGIA CONTRA EL ERROR DE FIREBIRD ---
    // Limpiamos los strings para evitar que json_encode colapse con acentos inválidos
    array_walk_recursive($promotores, function (&$v) {
        if (is_string($v)) {
            // Convierte caracteres problemáticos a UTF-8 seguro
            $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        }
    });

    // Intentamos codificar
    $json_final = json_encode([
        'status' => 'success',
        'promotores' => $promotores
    ], JSON_UNESCAPED_UNICODE);

    // Si sigue fallando, escupimos el error real de PHP
    if ($json_final === false) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error de formato JSON: ' . json_last_error_msg()
        ]);
        exit();
    }

    echo $json_final;

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Error inesperado: ' . $e->getMessage()
    ]);
}