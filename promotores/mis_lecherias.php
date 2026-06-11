<?php
require_once __DIR__ . '/../includes/session_guard.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    echo json_encode([]); exit();
}

require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';
$pdo = DatabaseSQLite::getInstance();

$clavePromotor = $_SESSION['clave_promotor'] ?? null;
if (!$clavePromotor) {
    echo json_encode(['error' => true, 'mensaje' => 'Sin clave de promotor en sesión.']);
    exit();
}

// Filtros opcionales: mes (1-12) y anio. Si ambos vienen, se calcula estado por periodo.
$mesFiltro  = isset($_GET['mes'])  && $_GET['mes']  !== '' ? (int)$_GET['mes']  : 0;
$anioFiltro = isset($_GET['anio']) && $_GET['anio'] !== '' ? (int)$_GET['anio'] : 0;
$filtrarPeriodo = ($mesFiltro >= 1 && $mesFiltro <= 12 && $anioFiltro > 0);

try {
    if ($filtrarPeriodo) {
        // Conteos y flags acotados al periodo seleccionado
        $sql = "SELECT
                    L.LECHER,
                    TRIM(L.NOMBRELECH)          AS NOMBRELECH,
                    TRIM(M.MUN_DESCRIPCION)     AS MUNICIPIO,
                    TRIM(LOC.LOC_DESCRIPCION)   AS COMUNIDAD,
                    L.CC_FAM                    AS TOTAL_HOGARES,
                    (L.CC_BT1 + L.CC_BT2)       AS TOTAL_INFANTILES,
                    (L.CC_BT3 + L.CC_BT4 + L.CC_BT5 + L.CC_BT6 + L.CC_BT7) AS TOTAL_RESTO,
                    TRIM(L.ALMACEN_RURAL)       AS ALMACEN_RURAL,
                    L.NUM_TIENDA,
                    L.EN_OPERACION,
                    (SELECT COUNT(*) FROM inventarios_mensuales IM
                     WHERE IM.CLAVE_LECHERIA = CAST(L.LECHER AS TEXT)
                       AND IM.MES_PERIODO  = :mes
                       AND IM.ANIO_PERIODO = :anio
                    ) AS TOTAL_INVENTARIOS,
                    (SELECT MAX(IM2.FECHA) FROM inventarios_mensuales IM2
                     WHERE IM2.CLAVE_LECHERIA = CAST(L.LECHER AS TEXT)
                       AND IM2.MES_PERIODO  = :mes
                       AND IM2.ANIO_PERIODO = :anio
                    ) AS ULTIMO_INVENTARIO,
                    (SELECT COUNT(*) FROM reporte_mensual_lecher RM
                     WHERE RM.clave_lecheria = CAST(L.LECHER AS TEXT)
                       AND RM.mes  = :mes
                       AND RM.anio = :anio
                    ) AS TOTAL_REPORTES
                FROM lecheria L
                LEFT JOIN municipio M
                       ON L.EFD_NUMERO = M.EFD_NUMERO AND L.MUN_NUMERO = M.MUN_NUMERO
                LEFT JOIN localidad LOC
                       ON L.EFD_NUMERO = LOC.EFD_NUMERO
                      AND L.MUN_NUMERO = LOC.MUN_NUMERO
                      AND L.LOC_NUMERO = LOC.LOC_NUMERO
                WHERE L.EFD_NUMERO = 20
                  AND L.PROMOTOR = :prom
                  AND COALESCE(L.EN_OPERACION, 0) = 0
                ORDER BY L.NOMBRELECH";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':mes'  => $mesFiltro,
            ':anio' => $anioFiltro,
            ':prom' => $clavePromotor,
        ]);
    } else {
        // Sin filtro: totales históricos (comportamiento original + conteo de reportes)
        $sql = "SELECT
                    L.LECHER,
                    TRIM(L.NOMBRELECH)          AS NOMBRELECH,
                    TRIM(M.MUN_DESCRIPCION)     AS MUNICIPIO,
                    TRIM(LOC.LOC_DESCRIPCION)   AS COMUNIDAD,
                    L.CC_FAM                    AS TOTAL_HOGARES,
                    (L.CC_BT1 + L.CC_BT2)       AS TOTAL_INFANTILES,
                    (L.CC_BT3 + L.CC_BT4 + L.CC_BT5 + L.CC_BT6 + L.CC_BT7) AS TOTAL_RESTO,
                    TRIM(L.ALMACEN_RURAL)       AS ALMACEN_RURAL,
                    L.NUM_TIENDA,
                    L.EN_OPERACION,
                    (SELECT COUNT(*) FROM inventarios_mensuales IM
                     WHERE IM.CLAVE_LECHERIA = CAST(L.LECHER AS TEXT)
                    ) AS TOTAL_INVENTARIOS,
                    (SELECT MAX(IM2.FECHA) FROM inventarios_mensuales IM2
                     WHERE IM2.CLAVE_LECHERIA = CAST(L.LECHER AS TEXT)
                    ) AS ULTIMO_INVENTARIO,
                    (SELECT COUNT(DISTINCT RM.mes || '-' || RM.anio)
                     FROM reporte_mensual_lecher RM
                     WHERE RM.clave_lecheria = CAST(L.LECHER AS TEXT)
                    ) AS TOTAL_REPORTES
                FROM lecheria L
                LEFT JOIN municipio M
                       ON L.EFD_NUMERO = M.EFD_NUMERO AND L.MUN_NUMERO = M.MUN_NUMERO
                LEFT JOIN localidad LOC
                       ON L.EFD_NUMERO = LOC.EFD_NUMERO
                      AND L.MUN_NUMERO = LOC.MUN_NUMERO
                      AND L.LOC_NUMERO = LOC.LOC_NUMERO
                WHERE L.EFD_NUMERO = 20
                  AND L.PROMOTOR = ?
                  AND COALESCE(L.EN_OPERACION, 0) = 0
                ORDER BY L.NOMBRELECH";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$clavePromotor]);
    }
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Inyectar metadata del filtro para que el frontend sepa cómo render
    foreach ($rows as &$r) {
        $r['_FILTRO_MES']  = $filtrarPeriodo ? $mesFiltro  : null;
        $r['_FILTRO_ANIO'] = $filtrarPeriodo ? $anioFiltro : null;
    }
    unset($r);

    array_walk_recursive($rows, function (&$v) {
        if (is_string($v)) $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8');
    });

    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['error' => true, 'mensaje' => $e->getMessage()]);
}
