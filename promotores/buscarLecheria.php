<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require_once '../conexion.php'; 

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($q === '') {
    echo json_encode([]);
    exit();
}

try {
    $patron = '%' . strtoupper($q) . '%';

    // Nota: Usamos UPPER() y TRIM() para limpiar los datos de Firebird
    $sql = "SELECT FIRST 20
                L.LECHER,
                TRIM(L.NOMBRELECH) as NOMBRELECH,
                TRIM(M.MUN_DESCRIPCION) as MUNICIPIO_NOMBRE,
                TRIM(LOC.LOC_DESCRIPCION) as LOCALIDAD_DESC
            FROM LECHERIA L
            LEFT JOIN LOCALIDAD LOC ON 
                (L.EFD_NUMERO = LOC.EFD_NUMERO AND L.MUN_NUMERO = LOC.MUN_NUMERO AND L.LOC_NUMERO = LOC.LOC_NUMERO)
            LEFT JOIN MUNICIPIO M ON
                (L.EFD_NUMERO = M.EFD_NUMERO AND L.MUN_NUMERO = M.MUN_NUMERO)
            WHERE L.EFD_NUMERO = 20
            AND (CAST(L.LECHER AS VARCHAR(20)) LIKE :query1 OR UPPER(L.NOMBRELECH) LIKE :query2)
            ORDER BY L.NOMBRELECH";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':query1', $patron, PDO::PARAM_STR);
    $stmt->bindValue(':query2', $patron, PDO::PARAM_STR);

    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Si fetchAll devuelve false, aseguramos un array vacío
    $data = $resultados ? $resultados : [];

    // IMPORTANTE: Asegurar que los datos sean UTF-8 antes de codificar
    // Firebird suele devolver caracteres en Windows-1252 o similar si el Charset es NONE
    array_walk_recursive($data, function (&$item) {
        if (is_string($item)) {
            $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
        }
    });

    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => true,
        "mensaje" => "Error en búsqueda: " . $e->getMessage()
    ]);
}