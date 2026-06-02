<?php
require_once __DIR__ . '/../includes/session_guard.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    echo json_encode([]); 
    exit();
}

require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';
$pdo = DatabaseSQLite::getInstance();

$clavePromotor = $_SESSION['clave_promotor'] ?? null;
if (!$clavePromotor) {
    echo json_encode(['error' => true, 'mensaje' => 'Sin clave de promotor en sesión.']);
    exit();
}

try {
    $sql = "SELECT DISTINCT TRIM(ALMACEN_RURAL) AS ALMACEN_RURAL
            FROM lecheria
            WHERE EFD_NUMERO = 20
              AND PROMOTOR = ?
              AND ALMACEN_RURAL IS NOT NULL
              AND TRIM(ALMACEN_RURAL) <> ''
            ORDER BY 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$clavePromotor]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    array_walk_recursive($rows, function (&$v) {
        if (is_string($v)) {
            $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8');
        }
    });
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['error' => true, 'mensaje' => $e->getMessage()]);
}
?>