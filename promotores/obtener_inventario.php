<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    echo json_encode(['status' => 'error', 'mensaje' => 'No autorizado']); exit();
}

require_once('../conexion.php');

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    echo json_encode(['status' => 'error', 'mensaje' => 'ID inválido']); exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM INVENTARIOS_MENSUALES WHERE ID = ?");
    $stmt->execute([(int)$id]);
    $datos = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($datos) {
        echo json_encode(['status' => 'success', 'datos' => $datos]);
    } else {
        echo json_encode(['status' => 'error', 'mensaje' => 'Inventario no encontrado']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
}