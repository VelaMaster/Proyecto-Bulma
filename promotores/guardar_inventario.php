<?php
session_start();
header('Content-Type: application/json');

$json = file_get_contents('php://input');
$datos = json_decode($json, true);

if (!$datos) {
    echo json_encode(["status" => "error", "mensaje" => "No se recibieron datos."]);
    exit();
}
$usuario = $_SESSION['usuario'] ?? 'desconocido';
$intVal = function($val) {
    return (empty($val) && $val !== '0') ? 0 : (int)$val;
};
$dateVal = function($val) {
    return empty($val) ? null : $val;
};
$nombreArchivo = 'Inventario_' 
    . ($datos['lecheria'] ?? 'X') . '_' 
    . ($datos['fecha'] ?? date('Ymd')) . '.pdf';

try {
    require_once('../conexion.php'); 
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = "INSERT INTO inventarios_mensuales (
        id, /* <--- Agregamos el ID explícitamente aquí */
        fecha, clave_lecheria, clave_tienda, almacen, municipio, comunidad,
        surt_fecha, surt_cajas, surt_litros, surt_factura, surt_caducidad,
        inv_ini_caja, inv_ini_sobres, inv_ini_litros,
        abasto_caja, abasto_sobres, abasto_litros,
        venta_caja, venta_sobres, venta_litros,
        reg_caja, reg_sobres, reg_litros,
        dif_caja, dif_sobres, dif_litros,
        fin_caja, fin_sobres, fin_litros,
        hogares, menores, mayores, dotacion,
        pdf_ruta, usuario, estado
    ) VALUES (
        NEXT VALUE FOR seq_inventarios_mens_id,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, 'guardado'
    )";

    // 2. Iniciamos una transacción (Firebird ama las transacciones)
    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
    }

    $stmt = $pdo->prepare($sql);
    
    // Guardamos el resultado del execute para validar
    $ejecucionExitosa = $stmt->execute([
        $dateVal($datos['fecha'] ?? null),
        $datos['lecheria'] ?? '',
        $datos['tienda'] ?? '',
        $datos['almacen'] ?? '',
        $datos['municipio'] ?? '',
        $datos['comunidad'] ?? '',
        
        $dateVal($datos['surt_fecha'] ?? null),
        $intVal($datos['surt_cajas'] ?? 0),
        $intVal($datos['surt_litros'] ?? 0),
        $datos['surt_factura'] ?? '',
        $dateVal($datos['surt_caducidad'] ?? null),
        
        $intVal($datos['inv_ini_caja'] ?? 0),
        $intVal($datos['inv_ini_sobres'] ?? 0),
        $intVal($datos['inv_ini_litros'] ?? 0),
        
        $intVal($datos['abasto_caja'] ?? 0),
        $intVal($datos['abasto_sobres'] ?? 0),
        $intVal($datos['abasto_litros'] ?? 0),
        
        $intVal($datos['venta_caja'] ?? 0),
        $intVal($datos['venta_sobres'] ?? 0),
        $intVal($datos['venta_litros'] ?? 0),
        
        $intVal($datos['reg_caja'] ?? 0),
        $intVal($datos['reg_sobres'] ?? 0),
        $intVal($datos['reg_litros'] ?? 0),
        
        $intVal($datos['dif_caja'] ?? 0),
        $intVal($datos['dif_sobres'] ?? 0),
        $intVal($datos['dif_litros'] ?? 0),
        
        $intVal($datos['fin_caja'] ?? 0),
        $intVal($datos['fin_sobres'] ?? 0),
        $intVal($datos['fin_litros'] ?? 0),
        
        $intVal($datos['hogares'] ?? 0),
        $intVal($datos['menores'] ?? 0),
        $intVal($datos['mayores'] ?? 0),
        $intVal($datos['dotacion'] ?? 0),
        
        $nombreArchivo,
        $usuario
    ]);

    // 3. Confirmamos si realmente se insertó
    if ($ejecucionExitosa) {
        $pdo->commit(); // Confirmamos los cambios en Firebird
        echo json_encode(["status" => "success", "mensaje" => "Inventario guardado en BDD correctamente."]);
    } else {
        $pdo->rollBack(); // Revertimos por si acaso
        $errorInfo = $stmt->errorInfo();
        echo json_encode(["status" => "error", "mensaje" => "Error de SQL: " . implode(" - ", $errorInfo)]);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Ahora sí capturará el error real de Firebird (Ej. un campo nulo, tipo de dato incorrecto)
    echo json_encode(["status" => "error", "mensaje" => "Fallo en BDD: " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "mensaje" => "Error general: " . $e->getMessage()]);
}
?>