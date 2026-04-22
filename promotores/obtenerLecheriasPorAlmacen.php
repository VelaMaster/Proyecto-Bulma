<?php
session_start();
session_write_close(); // Liberamos sesión para máxima velocidad
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

if (!$usuario || empty($almacen)) {
    echo json_encode(['error' => true, 'mensaje' => 'Datos insuficientes.']); 
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

    // 2. MAGIA: Buscamos el inventario de cada una desde PHP (Mucho más rápido que JS)
    $sql_inv = "SELECT FIRST 1 INVENTARIO_FINAL, MES_PERIODO, ANIO_PERIODO 
                FROM INVENTARIO_LEP_SUBSIDIADA 
                WHERE LECHER = ? 
                ORDER BY ANIO_PERIODO DESC, MES_PERIODO DESC";
    $stmt_inv = $pdo->prepare($sql_inv);

    foreach ($lecherias as &$lech) {
        $stmt_inv->execute([$lech['LECHER']]);
        $inv = $stmt_inv->fetch(PDO::FETCH_ASSOC);
        
        if ($inv) {
            $lech['encontrado'] = true;
            $lech['inventario_inicial'] = $inv['INVENTARIO_FINAL'];
            $lech['mes_anterior'] = $inv['MES_PERIODO'];
            $lech['anio_anterior'] = $inv['ANIO_PERIODO'];
        } else {
            $lech['encontrado'] = false;
            $lech['inventario_inicial'] = 0;
            $lech['mes_anterior'] = null;
            $lech['anio_anterior'] = null;
        }
    }

    // Mandamos todo el paquete en un solo milisegundo
    echo json_encode($lecherias, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['error' => true, 'mensaje' => $e->getMessage()]);
}
?>