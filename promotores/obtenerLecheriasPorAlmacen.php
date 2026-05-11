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
    echo json_encode(['error' => true, 'mensaje' => 'Selecciona el Mes y el Año del reporte.']);
    exit();
}

try {
    // Si NO se especifica almacén → devolvemos TODAS las lecherías del
    // promotor (con su ALMACEN_RURAL) para que el front pueda agrupar
    // en N tablas. Si sí se especifica → solo ese almacén.
    $filtroAlmacen = $almacen !== '' ? "AND TRIM(L.ALMACEN_RURAL) = :almacen" : '';

    $sql = "SELECT TRIM(L.LECHER) AS LECHER,
                   TRIM(L.NUM_TIENDA) AS NUM_TIENDA,
                   TRIM(L.ALMACEN_RURAL) AS ALMACEN_RURAL,
                   L.TIPO_PUNTO_VENTA AS TIPO_PUNTO_VENTA
            FROM LECHERIA L
            INNER JOIN USUARIOS_INVENTARIOS U ON L.PROMOTOR = U.CLAVE_ROL
            WHERE L.EFD_NUMERO = 20
              AND U.USUARIO = :usuario
              AND COALESCE(L.EN_OPERACION, 0) = 0   -- 0 = activa, 1 = baja
              $filtroAlmacen
            ORDER BY TRIM(L.ALMACEN_RURAL) ASC, L.TIPO_PUNTO_VENTA ASC, TRIM(L.LECHER) ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario', $usuario, PDO::PARAM_STR);
    if ($almacen !== '') {
        $stmt->bindValue(':almacen', $almacen, PDO::PARAM_STR);
    }
    $stmt->execute();
    $lecherias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // El reporte mensual usa el inventario del MISMO mes/año capturado en
    // campo (cierre del 25 del mes correspondiente).
    $mes_inv  = $mes_reporte;
    $anio_inv = $anio_reporte;

    $sql_inv = "SELECT INVENTARIO_FINAL, SURTIMIENTO, VENTA_REAL, VENTA_LIBRO_RETIRO
                FROM INVENTARIO_LEP_SUBSIDIADA
                WHERE LECHER = ? AND MES_PERIODO = ? AND ANIO_PERIODO = ?";
    $stmt_inv = $pdo->prepare($sql_inv);

    foreach ($lecherias as &$lech) {
        $stmt_inv->execute([$lech['LECHER'], $mes_inv, $anio_inv]);
        $inv = $stmt_inv->fetch(PDO::FETCH_ASSOC);

        if ($inv) {
            $lech['encontrado']         = true;
            $lech['inventario_inicial'] = $inv['INVENTARIO_FINAL'];
            $lech['surtimiento']        = $inv['SURTIMIENTO'];
            $lech['venta_real']         = $inv['VENTA_REAL'];
            $lech['venta_libro_retiro'] = $inv['VENTA_LIBRO_RETIRO'];
        } else {
            $lech['encontrado']         = false;
            $lech['inventario_inicial'] = 0;
            $lech['surtimiento']        = 0;
            $lech['venta_real']         = 0;
            $lech['venta_libro_retiro'] = 0;
        }
        // Mantenemos el nombre 'mes_anterior'/'anio_anterior' para no romper
        // el JS pero apuntan al mes del inventario consultado.
        $lech['mes_anterior']  = $mes_inv;
        $lech['anio_anterior'] = $anio_inv;
    }

    echo json_encode($lecherias, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['error' => true, 'mensaje' => $e->getMessage()]);
}
?>