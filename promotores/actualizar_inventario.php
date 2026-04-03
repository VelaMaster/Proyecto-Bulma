<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    echo json_encode(["status" => "error", "mensaje" => "No autorizado"]); exit();
}

$json  = file_get_contents('php://input');
$datos = json_decode($json, true);

if (!$datos || empty($datos['inventario_id'])) {
    echo json_encode(["status" => "error", "mensaje" => "Faltan datos o ID de inventario."]);
    exit();
}

$intVal  = fn($val) => (empty($val) && $val !== '0') ? 0 : (int)$val;
$dateVal = fn($val) => empty($val) ? null : $val;

$nombreArchivo = 'Inventario_'
    . ($datos['lecheria'] ?? 'X') . '_'
    . ($datos['fecha']    ?? date('Ymd')) . '.pdf';

try {
    require_once('../conexion.php');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ¡El bug estaba aquí: faltaba la coma antes de ESTADO = 'editado'!
    $sql = "UPDATE INVENTARIOS_MENSUALES SET
        FECHA          = ?,
        SURT_FECHA     = ?, SURT_CAJAS  = ?, SURT_LITROS   = ?,
        SURT_FACTURA   = ?, SURT_CADUCIDAD = ?,
        INV_INI_CAJA   = ?, INV_INI_SOBRES = ?, INV_INI_LITROS = ?,
        ABASTO_CAJA    = ?, ABASTO_SOBRES  = ?, ABASTO_LITROS  = ?,
        VENTA_CAJA     = ?, VENTA_SOBRES   = ?, VENTA_LITROS   = ?,
        REG_CAJA       = ?, REG_SOBRES     = ?, REG_LITROS     = ?,
        DIF_CAJA       = ?, DIF_SOBRES     = ?, DIF_LITROS     = ?,
        FIN_CAJA       = ?, FIN_SOBRES     = ?, FIN_LITROS     = ?,
        PDF_RUTA       = ?,
        ESTADO         = 'editado'
        WHERE ID = ?";

    if (!$pdo->inTransaction()) { $pdo->beginTransaction(); }

    $stmt = $pdo->prepare($sql);
    $ok   = $stmt->execute([
        $dateVal($datos['fecha']          ?? null),

        $dateVal($datos['surt_fecha']     ?? null),
        $intVal ($datos['surt_cajas']     ?? 0),
        $intVal ($datos['surt_litros']    ?? 0),
        $datos  ['surt_factura']          ?? '',
        $dateVal($datos['surt_caducidad'] ?? null),

        $intVal($datos['inv_ini_caja']    ?? 0),
        $intVal($datos['inv_ini_sobres']  ?? 0),
        $intVal($datos['inv_ini_litros']  ?? 0),

        $intVal($datos['abasto_caja']     ?? 0),
        $intVal($datos['abasto_sobres']   ?? 0),
        $intVal($datos['abasto_litros']   ?? 0),

        $intVal($datos['venta_caja']      ?? 0),
        $intVal($datos['venta_sobres']    ?? 0),
        $intVal($datos['venta_litros']    ?? 0),

        $intVal($datos['reg_caja']        ?? 0),
        $intVal($datos['reg_sobres']      ?? 0),
        $intVal($datos['reg_litros']      ?? 0),

        $intVal($datos['dif_caja']        ?? 0),
        $intVal($datos['dif_sobres']      ?? 0),
        $intVal($datos['dif_litros']      ?? 0),

        $intVal($datos['fin_caja']        ?? 0),
        $intVal($datos['fin_sobres']      ?? 0),
        $intVal($datos['fin_litros']      ?? 0),

        $nombreArchivo,
        (int)$datos['inventario_id']
    ]);

    if ($ok) {
        $pdo->commit();
        echo json_encode(["status" => "success", "mensaje" => "Inventario actualizado correctamente."]);
    } else {
        $pdo->rollBack();
        echo json_encode(["status" => "error", "mensaje" => "No se pudo actualizar el registro."]);
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    echo json_encode(["status" => "error", "mensaje" => "Error BD: " . $e->getMessage()]);
}