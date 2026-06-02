<?php
require_once __DIR__ . '/../includes/session_guard.php';
session_write_close(); 
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => true, 'mensaje' => 'No autorizado']); 
    exit();
}
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';
$pdo = DatabaseSQLite::getInstance();

$lecher = $_GET['lecher'] ?? '';

if (empty($lecher)) {
    echo json_encode(['error' => true, 'mensaje' => 'Falta la clave de la lechería']);
    exit();
}
try {
    // SQLite: FIRST 1 → LIMIT 1, parametrizamos en vez de concatenar.
    $sql = "SELECT FIN_LITROS, MES_PERIODO, ANIO_PERIODO
            FROM inventarios_mensuales
            WHERE CLAVE_LECHERIA IN (?, ?)
            ORDER BY ANIO_PERIODO DESC, MES_PERIODO DESC
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$lecher, $lecher . '00']);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

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