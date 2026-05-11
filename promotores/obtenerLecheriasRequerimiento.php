<?php
session_start();
session_write_close(); 
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    echo json_encode([]); 
    exit();
}
require_once __DIR__ . '/../Database.php';
$pdo = Database::getInstance();
$usuario = $_SESSION['usuario'] ?? null;
$almacen = trim($_GET['almacen'] ?? '');

$mes_reporte = isset($_GET['mes_reporte']) ? (int)$_GET['mes_reporte'] : 0;
$anio_reporte = isset($_GET['anio_reporte']) ? (int)$_GET['anio_reporte'] : 0;

if (!$usuario || $mes_reporte === 0 || $anio_reporte === 0) {
    echo json_encode(['error' => true, 'mensaje' => 'Selecciona el Mes y el Año.']);
    exit();
}

try {
    $filtroAlmacen = $almacen !== '' ? "AND TRIM(L.ALMACEN_RURAL) = :almacen" : '';

    // Extraemos demográficos base de la lechería por si no hay inventario previo
    $sql = "SELECT TRIM(L.LECHER) AS LECHER,
                   TRIM(L.NUM_TIENDA) AS NUM_TIENDA,
                   TRIM(L.ALMACEN_RURAL) AS ALMACEN_RURAL,
                   L.TIPO_PUNTO_VENTA AS TIPO_PUNTO_VENTA,
                   COALESCE(L.CC_FAM, 0) AS TOTAL_HOGARES,
                   COALESCE(L.CL_BEN, 0) AS TOTAL_INFANTILES
            FROM LECHERIA L
            INNER JOIN USUARIOS_INVENTARIOS U ON L.PROMOTOR = U.CLAVE_ROL
            WHERE L.EFD_NUMERO = 20
              AND U.USUARIO = :usuario
              AND COALESCE(L.EN_OPERACION, 0) = 0
              $filtroAlmacen
            ORDER BY TRIM(L.ALMACEN_RURAL) ASC, L.TIPO_PUNTO_VENTA ASC, TRIM(L.LECHER) ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario', $usuario, PDO::PARAM_STR);
    if ($almacen !== '') {
        $stmt->bindValue(':almacen', $almacen, PDO::PARAM_STR);
    }
    $stmt->execute();
    $lecherias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Consulta Blindada a la tabla de inventarios (10 dígitos)
    $sql_inv = "SELECT INV_INI_CAJA, INV_INI_SOBRES, SURT_CAJAS, 
                       VENTA_CAJA, VENTA_SOBRES, FIN_CAJA, FIN_SOBRES,
                       HOGARES, MENORES, MAYORES
                FROM INVENTARIOS_MENSUALES
                WHERE CLAVE_LECHERIA IN (?, ?) 
                  AND MES_PERIODO = " . (int)$mes_reporte . " 
                  AND ANIO_PERIODO = " . (int)$anio_reporte;
    $stmt_inv = $pdo->prepare($sql_inv);

    foreach ($lecherias as &$lech) {
        $clave_normal = trim($lech['LECHER']);
        $clave_00     = $clave_normal . '00';

        $stmt_inv->execute([$clave_normal, $clave_00]);
        $inv = $stmt_inv->fetch(PDO::FETCH_ASSOC);

        if ($inv) {
            $lech['encontrado']     = true;
            $lech['inv_ini_cajas']  = (int)$inv['INV_INI_CAJA'];
            $lech['inv_ini_sobres'] = (int)$inv['INV_INI_SOBRES'];
            $lech['surt_cajas']     = (int)$inv['SURT_CAJAS'];
            $lech['venta_cajas']    = (int)$inv['VENTA_CAJA'];
            $lech['venta_sobres']   = (int)$inv['VENTA_SOBRES'];
            $lech['fin_cajas']      = (int)$inv['FIN_CAJA'];
            $lech['fin_sobres']     = (int)$inv['FIN_SOBRES'];
            
            // Actualizamos demográficos si el inventario tiene datos más frescos
            if ((int)$inv['HOGARES'] > 0) {
                $lech['TOTAL_HOGARES'] = (int)$inv['HOGARES'];
            }
            $total_ben = (int)$inv['MENORES'] + (int)$inv['MAYORES'];
            if ($total_ben > 0) {
                $lech['TOTAL_INFANTILES'] = $total_ben;
            }
            $lech['TOTAL_RESTO'] = 0;
        } else {
            $lech['encontrado']  = false;
            $lech['TOTAL_RESTO'] = 0;
        }
    }

    echo json_encode($lecherias, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['error' => true, 'mensaje' => $e->getMessage()]);
}
?>