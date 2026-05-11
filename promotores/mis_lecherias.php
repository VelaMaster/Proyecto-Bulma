<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    echo json_encode([]); exit();
}

// 1. AQUÍ ESTÁ EL CAMBIO MÁGICO 👇
require_once __DIR__ . '/../Database.php';
$pdo = Database::getInstance();

$clavePromotor = $_SESSION['clave_promotor'] ?? null;
if (!$clavePromotor) {
    echo json_encode(['error' => true, 'mensaje' => 'Sin clave de promotor en sesión.']);
    exit();
}

try {
    $sql = "SELECT
                L.LECHER,
                TRIM(L.NOMBRELECH)          AS NOMBRELECH,
                TRIM(M.MUN_DESCRIPCION)     AS MUNICIPIO,
                TRIM(LOC.LOC_DESCRIPCION)   AS COMUNIDAD,
                L.CC_FAM                    AS TOTAL_HOGARES,
                (L.CC_BT1 + L.CC_BT2)      AS TOTAL_INFANTILES,
                (L.CC_BT3 + L.CC_BT4 + L.CC_BT5 + L.CC_BT6 + L.CC_BT7) AS TOTAL_RESTO,
                TRIM(L.ALMACEN_RURAL)       AS ALMACEN_RURAL,
                L.NUM_TIENDA,
                L.EN_OPERACION,
                -- Conteo de inventarios guardados para esta lechería
                (SELECT COUNT(*)
                 FROM INVENTARIOS_MENSUALES IM
                 WHERE IM.CLAVE_LECHERIA = CAST(L.LECHER AS VARCHAR(20))
                ) AS TOTAL_INVENTARIOS,
                -- Fecha del último inventario
                (SELECT MAX(IM2.FECHA)
                 FROM INVENTARIOS_MENSUALES IM2
                 WHERE IM2.CLAVE_LECHERIA = CAST(L.LECHER AS VARCHAR(20))
                ) AS ULTIMO_INVENTARIO
            FROM LECHERIA L
            LEFT JOIN MUNICIPIO M ON
                (L.EFD_NUMERO = M.EFD_NUMERO AND L.MUN_NUMERO = M.MUN_NUMERO)
            LEFT JOIN LOCALIDAD LOC ON
                (L.EFD_NUMERO = LOC.EFD_NUMERO AND L.MUN_NUMERO = LOC.MUN_NUMERO
                 AND L.LOC_NUMERO = LOC.LOC_NUMERO)
            WHERE L.EFD_NUMERO = 20
              AND L.PROMOTOR = ?
              AND COALESCE(L.EN_OPERACION, 0) = 0   -- 0 = activa, 1 = baja
            ORDER BY L.NOMBRELECH";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$clavePromotor]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    array_walk_recursive($rows, function (&$v) {
        if (is_string($v)) $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8');
    });

    echo json_encode($rows, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) { // Cambiado a Exception general por si acaso
    echo json_encode(['error' => true, 'mensaje' => $e->getMessage()]);
}