<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    echo json_encode([]); exit();
}

require_once('../conexion.php');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 1) { echo json_encode([]); exit; }

try {
    // CONTAINING es más eficiente que LIKE en Firebird
    $sql = "SELECT FIRST 10 ID, CLAVE_LECHERIA, FECHA, MUNICIPIO, ESTADO
            FROM INVENTARIOS_MENSUALES
            WHERE CLAVE_LECHERIA CONTAINING ?
               OR CAST(FECHA AS VARCHAR(20)) CONTAINING ?
            ORDER BY FECHA DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([strtoupper($q), $q]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($resultados);

} catch (PDOException $e) {
    echo json_encode([]);
}