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

    
    $sql = "SELECT FIRST 20
                L.LECHER,
                TRIM(L.NOMBRELECH) as NOMBRELECH,
                TRIM(M.MUN_DESCRIPCION) as MUNICIPIO_NOMBRE,
                TRIM(LOC.LOC_DESCRIPCION) as LOCALIDAD_DESC,
                L.NUM_TIENDA,
                TRIM(L.ALMACEN_RURAL) as ALMACEN_RURAL,
                L.CC_FAM as TOTAL_HOGARES,
                (L.CC_BT1 + L.CC_BT2) as TOTAL_INFANTILES,
                (L.CC_BT3 + L.CC_BT4 + L.CC_BT5 + L.CC_BT6 + L.CC_BT7) as TOTAL_RESTO
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

    $data = $resultados ? $resultados : [];

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