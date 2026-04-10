<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    echo json_encode([]); 
    exit();
}
require_once __DIR__ . '/../Database.php';
$pdo = Database::getInstance();

$clavePromotor = $_SESSION['clave_promotor'] ?? null;
$almacen = $_GET['almacen'] ?? '';

if (!$clavePromotor || empty($almacen)) {
    echo json_encode(['error' => true, 'mensaje' => 'Datos insuficientes.']); 
    exit();
}
try {
    $sql = "SELECT TRIM(LECHER) AS LECHER, TRIM(NUM_TIENDA) AS NUM_TIENDA
            FROM LECHERIA
            WHERE EFD_NUMERO = 20
              AND PROMOTOR = ?
              AND TRIM(ALMACEN_RURAL) = ?
            ORDER BY TRIM(LECHER) ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$clavePromotor, trim($almacen)]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['error' => true, 'mensaje' => $e->getMessage()]);
}
?>