<?php
session_start();
session_write_close(); 
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => true, 'mensaje' => 'No autorizado']); 
    exit();
}
require_once __DIR__ . '/../Database.php';
$pdo = Database::getInstance();

$lecher = $_GET['lecher'] ?? '';

if (empty($lecher)) {
    echo json_encode(['error' => true, 'mensaje' => 'Falta la clave de la lechería']); 
    exit();
}
try {
    $sql = "SELECT FIRST 1 INVENTARIO_FINAL, MES_PERIODO, ANIO_PERIODO 
            FROM INVENTARIO_LEP_SUBSIDIADA 
            WHERE LECHER = :lecher 
            ORDER BY ANIO_PERIODO DESC, MES_PERIODO DESC";
              
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':lecher' => $lecher]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultado) {
        echo json_encode([
            'error' => false,
            'encontrado' => true, 
            'inventario_inicial' => $resultado['INVENTARIO_FINAL'],
            'mes_consultado' => $resultado['MES_PERIODO'],
            'anio_consultado' => $resultado['ANIO_PERIODO']
        ]);
    } else {
        echo json_encode([
            'error' => false,
            'encontrado' => false, 
            'inventario_inicial' => 0,
            'mensaje' => 'No se encontró inventario anterior.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['error' => true, 'mensaje' => $e->getMessage()]);
}
?>