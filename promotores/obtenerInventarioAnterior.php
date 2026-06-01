<?php
require_once __DIR__ . '/../includes/session_guard.php';
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
    // SOLO INVENTARIOS_MENSUALES (captura del promotor). El inventario inicial
    // de un mes = inventario final (FIN_LITROS) del último mes capturado.
    $lecher_q   = "'" . str_replace("'", "''", $lecher) . "'";
    $lecher_q00 = "'" . str_replace("'", "''", $lecher . '00') . "'";

    $sql = "SELECT FIRST 1 FIN_LITROS, MES_PERIODO, ANIO_PERIODO
            FROM INVENTARIOS_MENSUALES
            WHERE CLAVE_LECHERIA IN ($lecher_q, $lecher_q00)
            ORDER BY ANIO_PERIODO DESC, MES_PERIODO DESC";

    $resultado = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);

    if ($resultado) {
        echo json_encode([
            'error' => false,
            'encontrado' => true,
            'inventario_inicial' => $resultado['FIN_LITROS'],
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