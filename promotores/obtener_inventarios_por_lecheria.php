<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    echo json_encode([]); exit();
}

require_once('../conexion.php');

$clave  = trim($_GET['clave'] ?? '');
$fecha  = trim($_GET['fecha'] ?? ''); // filtro opcional

if ($clave === '') { echo json_encode([]); exit; }

try {
    $where = "WHERE UPPER(CLAVE_LECHERIA) = ?";
    $params = [strtoupper($clave)];

    if ($fecha !== '') {
        $where .= " AND CAST(FECHA AS VARCHAR(20)) CONTAINING ?";
        $params[] = $fecha;
    }

    $sql = "SELECT FIRST 50
                ID, CLAVE_LECHERIA, FECHA, MUNICIPIO, COMUNIDAD,
                ESTADO, CREATED_AT, UPDATED_AT,
                FIN_LITROS, FIN_CAJA, FIN_SOBRES
            FROM INVENTARIOS_MENSUALES
            $where
            ORDER BY FECHA DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    array_walk_recursive($rows, function (&$v) {
        if (is_string($v)) $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8');
    });

    echo json_encode($rows, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}