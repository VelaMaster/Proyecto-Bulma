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
$almacen = $_GET['almacen'] ?? '';
$tipo_venta = $_GET['tipo_venta'] ?? '0'; 

// Recibimos los nuevos parámetros
$mes_reporte = isset($_GET['mes_reporte']) ? (int)$_GET['mes_reporte'] : 0;
$anio_reporte = isset($_GET['anio_reporte']) ? (int)$_GET['anio_reporte'] : 0;

if (!$usuario || empty($almacen) || $mes_reporte === 0 || $anio_reporte === 0) {
    echo json_encode(['error' => true, 'mensaje' => 'Selecciona el Mes y el Año del reporte.']); 
    exit();
}

try {
    if ($tipo_venta == '1') {
        $condicion_tipo = "AND L.TIPO_PUNTO_VENTA IN (1, 2)";
    } else {
        $condicion_tipo = "AND L.TIPO_PUNTO_VENTA = 0";
    }

    // 1. Buscamos las lecherías
    $sql = "SELECT TRIM(L.LECHER) AS LECHER, TRIM(L.NUM_TIENDA) AS NUM_TIENDA
            FROM LECHERIA L
            INNER JOIN USUARIOS_INVENTARIOS U ON L.PROMOTOR = U.CLAVE_ROL
            WHERE L.EFD_NUMERO = 20
              AND U.USUARIO = :usuario
              AND TRIM(L.ALMACEN_RURAL) = :almacen
              $condicion_tipo
            ORDER BY TRIM(L.LECHER) ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':usuario', $usuario, PDO::PARAM_STR);
    $stmt->bindValue(':almacen', trim($almacen), PDO::PARAM_STR);
    $stmt->execute();
    $lecherias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ========================================================
    // 2. MATEMÁTICA DEL MES ANTERIOR
    // ========================================================
    $mes_anterior = $mes_reporte - 1;
    $anio_anterior = $anio_reporte;
    
    if ($mes_anterior == 0) { // Si están haciendo el de Enero, buscamos Diciembre del año pasado
        $mes_anterior = 12;
        $anio_anterior--;
    }

    // 3. Buscamos el inventario EXACTO de ese mes y ese año
    $sql_inv = "SELECT INVENTARIO_FINAL 
                FROM INVENTARIO_LEP_SUBSIDIADA 
                WHERE LECHER = ? AND MES_PERIODO = ? AND ANIO_PERIODO = ?";
    $stmt_inv = $pdo->prepare($sql_inv);

    foreach ($lecherias as &$lech) {
        $stmt_inv->execute([$lech['LECHER'], $mes_anterior, $anio_anterior]);
        $inv = $stmt_inv->fetch(PDO::FETCH_ASSOC);
        
        if ($inv) {
            $lech['encontrado'] = true;
            $lech['inventario_inicial'] = $inv['INVENTARIO_FINAL'];
            $lech['mes_anterior'] = $mes_anterior; // Devolvemos el mes exacto que encontramos
            $lech['anio_anterior'] = $anio_anterior;
        } else {
            $lech['encontrado'] = false;
            $lech['inventario_inicial'] = 0;
            $lech['mes_anterior'] = $mes_anterior;
            $lech['anio_anterior'] = $anio_anterior;
        }
    }

    echo json_encode($lecherias, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['error' => true, 'mensaje' => $e->getMessage()]);
}
?>