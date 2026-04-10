<?php
session_start();
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
$mesActual = (int) date('n');
$anioActual = (int) date('Y');
if ($mesActual === 1) {
    $mesAnterior = 12;
    $anioAnterior = $anioActual - 1;
} else {
    $mesAnterior = $mesActual - 1;
    $anioAnterior = $anioActual;
}
try {
    $sql = "SELECT INVENTARIO_FINAL 
            FROM INVENTARIO_LEP_SUBSIDIADA 
            WHERE LECHER = ? 
              AND MES_PERIODO = ? 
              AND ANIO_PERIODO = ?";
              
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$lecher, $mesAnterior, $anioAnterior]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultado) {
        // Si hay registro, devolvemos el inventario final
        echo json_encode([
            'encontrado' => true, 
            'inventario_inicial' => $resultado['INVENTARIO_FINAL'],
            'mes_consultado' => $mesAnterior,
            'anio_consultado' => $anioAnterior
        ]);
    } else {
        // Si es una lechería nueva o no hubo inventario el mes pasado, devolvemos 0
        echo json_encode([
            'encontrado' => false, 
            'inventario_inicial' => 0,
            'mensaje' => 'No se encontró inventario del mes anterior.'
        ]);
    }

} catch (Exception $e) {
    echo json_encode(['error' => true, 'mensaje' => $e->getMessage()]);
}
?>