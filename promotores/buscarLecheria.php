<?php
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

require_once '../conexion.php';

// Si la conexión falló, $pdo es null
if (!$pdo) {
    http_response_code(500);
    echo json_encode(["error" => true, "mensaje" => "Sin conexión a base de datos"]);
    exit;
}

$q = $_GET['q'] ?? '';

if (strlen(trim($q)) < 1) {
    echo json_encode([]);
    exit;
}

$busquedaTexto = strtoupper(trim($q));
$esNumero = ctype_digit($busquedaTexto);

try {

    if ($esNumero) {
        $patron = '%' . $busquedaTexto . '%';
        $sql = "SELECT FIRST 10
                    L.LECHER,
                    TRIM(L.NOMBRELECH) as NOMBRELECH,
                    TRIM(M.MUN_DESCRIPCION) as MUNICIPIO_NOMBRE,
                    TRIM(LOC.LOC_DESCRIPCION) as LOCALIDAD_DESC
                FROM LECHERIA L
                LEFT JOIN LOCALIDAD LOC ON 
                    (L.EFD_NUMERO = LOC.EFD_NUMERO 
                     AND L.MUN_NUMERO = LOC.MUN_NUMERO 
                     AND L.LOC_NUMERO = LOC.LOC_NUMERO)
                LEFT JOIN MUNICIPIO M ON
                    (L.EFD_NUMERO = M.EFD_NUMERO 
                     AND L.MUN_NUMERO = M.MUN_NUMERO)
                WHERE L.EFD_NUMERO = 20
                AND CAST(L.LECHER AS VARCHAR(20)) LIKE ?
                ORDER BY L.NOMBRELECH";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$patron]);

    } else {
        $sql = "SELECT FIRST 10
                    L.LECHER,
                    TRIM(L.NOMBRELECH) as NOMBRELECH,
                    TRIM(M.MUN_DESCRIPCION) as MUNICIPIO_NOMBRE,
                    TRIM(LOC.LOC_DESCRIPCION) as LOCALIDAD_DESC
                FROM LECHERIA L
                LEFT JOIN LOCALIDAD LOC ON 
                    (L.EFD_NUMERO = LOC.EFD_NUMERO 
                     AND L.MUN_NUMERO = LOC.MUN_NUMERO 
                     AND L.LOC_NUMERO = LOC.LOC_NUMERO)
                LEFT JOIN MUNICIPIO M ON
                    (L.EFD_NUMERO = M.EFD_NUMERO 
                     AND L.MUN_NUMERO = M.MUN_NUMERO)
                WHERE L.EFD_NUMERO = 20
                AND (
                    UPPER(TRIM(L.NOMBRELECH)) CONTAINING ?
                    OR UPPER(TRIM(M.MUN_DESCRIPCION)) CONTAINING ?
                )
                ORDER BY L.NOMBRELECH";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$busquedaTexto, $busquedaTexto]);
    }

    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($resultados ?: [], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "error" => true,
        "mensaje" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
exit;