<?php
// MIGRADO a SQLite. CONTAINING/FIRST → LIKE/LIMIT. CAST AS VARCHAR → CAST AS TEXT.
require_once __DIR__ . '/../includes/session_guard.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    echo json_encode([]); exit();
}

require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';
$pdo = DatabaseSQLite::getInstance();

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 1) { echo json_encode([]); exit; }

try {
    $sql = "SELECT ID, CLAVE_LECHERIA, FECHA, MUNICIPIO, ESTADO
            FROM inventarios_mensuales
            WHERE UPPER(CLAVE_LECHERIA) LIKE ?
               OR CAST(FECHA AS TEXT)   LIKE ?
            ORDER BY FECHA DESC
            LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $patron = '%' . strtoupper($q) . '%';
    $stmt->execute([$patron, '%' . $q . '%']);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    echo json_encode([]);
}
