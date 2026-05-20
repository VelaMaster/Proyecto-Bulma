<?php
/**
 * regenerar_pdf_inventario.php
 * Regenera el PDF de un inventario ya guardado en BD, usando sus datos actuales.
 * Recibe: GET id=<ID de INVENTARIOS_MENSUALES>
 */
require_once __DIR__ . '/../includes/session_guard.php';
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    echo json_encode(['status' => 'error', 'mensaje' => 'No autorizado']); exit();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['status' => 'error', 'mensaje' => 'ID inválido']); exit();
}

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/_fn_pdf_inventario.php';

try {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT * FROM INVENTARIOS_MENSUALES WHERE ID = $id");
    $inv = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$inv) {
        echo json_encode(['status' => 'error', 'mensaje' => 'Inventario no encontrado']); exit();
    }

    // Mapear columnas de BD al formato que espera generarArchivoInventario()
    $datos = [
        'fecha'          => $inv['FECHA']          ?? '',
        'lecheria'       => $inv['CLAVE_LECHERIA']  ?? '',
        'tienda'         => $inv['NUM_TIENDA']       ?? '',
        'almacen'        => $inv['ALMACEN']          ?? '',
        'municipio'      => $inv['MUNICIPIO']        ?? '',
        'comunidad'      => $inv['COMUNIDAD']        ?? '',
        'inv_ini_caja'   => $inv['INV_INI_CAJA']     ?? 0,
        'inv_ini_sobres' => $inv['INV_INI_SOBRES']   ?? 0,
        'inv_ini_litros' => $inv['INV_INI_LITROS']   ?? 0,
        'abasto_caja'    => $inv['ABASTO_CAJA']      ?? 0,
        'abasto_sobres'  => $inv['ABASTO_SOBRES']    ?? 0,
        'abasto_litros'  => $inv['ABASTO_LITROS']    ?? 0,
        'venta_caja'     => $inv['VENTA_CAJA']       ?? 0,
        'venta_sobres'   => $inv['VENTA_SOBRES']     ?? 0,
        'venta_litros'   => $inv['VENTA_LITROS']     ?? 0,
        'reg_caja'       => $inv['REG_CAJA']         ?? 0,
        'reg_sobres'     => $inv['REG_SOBRES']       ?? 0,
        'reg_litros'     => $inv['REG_LITROS']       ?? 0,
        'dif_caja'       => $inv['DIF_CAJA']         ?? 0,
        'dif_sobres'     => $inv['DIF_SOBRES']       ?? 0,
        'dif_litros'     => $inv['DIF_LITROS']       ?? 0,
        'fin_caja'       => $inv['FIN_CAJA']         ?? 0,
        'fin_sobres'     => $inv['FIN_SOBRES']       ?? 0,
        'fin_litros'     => $inv['FIN_LITROS']       ?? 0,
        'surt_fecha'     => $inv['SURT_FECHA']       ?? '',
        'surt_cajas'     => $inv['SURT_CAJAS']       ?? 0,
        'surt_litros'    => $inv['SURT_LITROS']      ?? 0,
        'surt_factura'   => $inv['SURT_FACTURA']     ?? '',
        'surt_caducidad' => $inv['SURT_CADUCIDAD']   ?? '',
        'hogares'        => $inv['TOTAL_HOGARES']     ?? 0,
        'menores'        => $inv['TOTAL_INFANTILES']  ?? 0,
        'mayores'        => $inv['TOTAL_RESTO']       ?? 0,
        'dotacion'       => $inv['DOTACION']          ?? 0,
    ];

    $nombreArchivo = generarArchivoInventario($datos);

    if (!$nombreArchivo) {
        echo json_encode(['status' => 'error', 'mensaje' => 'No se pudo generar el PDF']); exit();
    }

    // Actualizar PDF_RUTA en BD por si había cambiado
    $q = function($v) use ($db) { return "'" . str_replace("'", "''", (string)$v) . "'"; };
    $db->exec("UPDATE INVENTARIOS_MENSUALES SET PDF_RUTA = " . $q($nombreArchivo) . " WHERE ID = $id");

    echo json_encode([
        'status'   => 'success',
        'pdf_ruta' => $nombreArchivo,
        'mensaje'  => 'PDF regenerado correctamente',
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
}
