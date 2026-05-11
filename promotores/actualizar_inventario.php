<?php
session_start();
// Apagamos los errores de PHP en pantalla para que NUNCA rompan el JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);
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

// 1. Usamos nuestra conexión parcheada
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../src/Repositorio/InventarioRepositorio.php';

// 2. Funciones ayudantes (Spoon-feeding para el SQL Crudo)
$q = function ($val, $len = 255) {
    if ($val === null || $val === "") return 'NULL';
    $limpio = substr((string)$val, 0, $len);
    return "'" . str_replace("'", "''", $limpio) . "'";
};

$n = function ($val) {
    if ($val === null || $val === "") return 0;
    return (int)$val;
};

$lecheria = $datos['lecheria'] ?? 'X';
// Tomamos el periodo del payload si viene, si no caemos a la fecha del documento.
$mes  = !empty($datos['mes_periodo'])  ? (int)$datos['mes_periodo']  : (int)date('m', strtotime($datos['fecha'] ?? 'now'));
$anio = !empty($datos['anio_periodo']) ? (int)$datos['anio_periodo'] : (int)date('Y', strtotime($datos['fecha'] ?? 'now'));
$nombreArchivo = "Inventario_{$lecheria}_{$anio}_{$mes}.pdf";

try {
    $db = Database::getInstance();
    
    $id = (int)$datos['inventario_id'];

    // 3. ARMAMOS EL UPDATE GIGANTE EN TEXTO PLANO
    $sql = "UPDATE INVENTARIOS_MENSUALES SET
        FECHA          = " . $q($datos['fecha'], 10) . ",
        SURT_FECHA     = " . $q($datos['surt_fecha'], 10) . ",
        SURT_CAJAS     = " . $n($datos['surt_cajas']) . ",
        SURT_LITROS    = " . $n($datos['surt_litros']) . ",
        SURT_FACTURA   = " . $q($datos['surt_factura'], 60) . ",
        SURT_CADUCIDAD = " . $q($datos['surt_caducidad'], 10) . ",

        INV_INI_CAJA   = " . $n($datos['inv_ini_caja']) . ",
        INV_INI_SOBRES = " . $n($datos['inv_ini_sobres']) . ",
        INV_INI_LITROS = " . $n($datos['inv_ini_litros']) . ",

        ABASTO_CAJA    = " . $n($datos['abasto_caja']) . ",
        ABASTO_SOBRES  = " . $n($datos['abasto_sobres']) . ",
        ABASTO_LITROS  = " . $n($datos['abasto_litros']) . ",

        VENTA_CAJA     = " . $n($datos['venta_caja']) . ",
        VENTA_SOBRES   = " . $n($datos['venta_sobres']) . ",
        VENTA_LITROS   = " . $n($datos['venta_litros']) . ",

        REG_CAJA       = " . $n($datos['reg_caja']) . ",
        REG_SOBRES     = " . $n($datos['reg_sobres']) . ",
        REG_LITROS     = " . $n($datos['reg_litros']) . ",

        DIF_CAJA       = " . $n($datos['dif_caja']) . ",
        DIF_SOBRES     = " . $n($datos['dif_sobres']) . ",
        DIF_LITROS     = " . $n($datos['dif_litros']) . ",

        FIN_CAJA       = " . $n($datos['fin_caja']) . ",
        FIN_SOBRES     = " . $n($datos['fin_sobres']) . ",
        FIN_LITROS     = " . $n($datos['fin_litros']) . ",

        PDF_RUTA       = " . $q($nombreArchivo, 255) . ",
        ESTADO         = 'editado'
        WHERE ID = $id";

    if (!$db->inTransaction()) { $db->beginTransaction(); }

    // 4. EJECUTAMOS DIRECTO (Sin bind_params)
    $db->exec($sql);

    $db->commit();

    // 5. Sincronizamos también INVENTARIO_LEP_SUBSIDIADA para que el flujo
    //    (reporte/requerimiento) pueda leer los nuevos valores sin que
    //    Distribución tenga que cargar nada a mano.
    try {
        $repo = new InventarioRepositorio();
        $repo->syncLepSubsidiada($lecheria, $mes, $anio, $datos);
    } catch (Exception $eSync) {
        // No tumbamos la operación principal por un fallo en el sync.
        error_log('[actualizar_inventario] sync LEP falló: ' . $eSync->getMessage());
    }

    echo json_encode(["status" => "success", "mensaje" => "Inventario actualizado correctamente."]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
    
    // Devolvemos el error en un JSON válido, no en HTML
    http_response_code(500);
    echo json_encode(["status" => "error", "mensaje" => "Error BD: " . $e->getMessage()]);
}