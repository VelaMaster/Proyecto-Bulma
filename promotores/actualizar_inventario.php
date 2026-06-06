<?php
// MIGRADO a SQLite (Fase 3.4a). UPDATE con bind params.
require_once __DIR__ . '/../includes/session_guard.php';
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

require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';
require_once __DIR__ . '/../src/Repositorio/InventarioRepositorio.php';

$n = fn($v) => ($v === null || $v === '') ? 0 : (int)$v;

$lecheria = $datos['lecheria'] ?? 'X';
// El periodo manda. La fecha es solo de captura y NO debe redefinir el mes/año.
$mes  = !empty($datos['mes_periodo'])  ? (int)$datos['mes_periodo']  : (int)date('m', strtotime($datos['fecha'] ?? 'now'));
$anio = !empty($datos['anio_periodo']) ? (int)$datos['anio_periodo'] : (int)date('Y', strtotime($datos['fecha'] ?? 'now'));
$datos['mes_periodo']  = $mes;
$datos['anio_periodo'] = $anio;
$nombreArchivo = "Inventario_{$lecheria}_{$anio}_" . sprintf('%02d', $mes) . ".pdf";

try {
    $db = DatabaseSQLite::getInstance();
    $id = (int)$datos['inventario_id'];

    // Guardamos el PDF_RUTA anterior para detectar si cambió de mes y borrar el viejo en disco.
    $rutaPrev = '';
    try {
        $stmtPrev = $db->prepare("SELECT PDF_RUTA FROM inventarios_mensuales WHERE ID = ?");
        $stmtPrev->execute([$id]);
        $rowPrev = $stmtPrev->fetch(PDO::FETCH_ASSOC);
        $rutaPrev = trim($rowPrev['PDF_RUTA'] ?? '');
    } catch (Throwable $e) { /* no crítico */ }

    $sql = "UPDATE inventarios_mensuales SET
        FECHA          = :fecha,
        SURT_FECHA     = :surt_fecha,
        SURT_CAJAS     = :surt_cajas,
        SURT_LITROS    = :surt_litros,
        SURT_FACTURA   = :surt_factura,
        SURT_CADUCIDAD = :surt_caducidad,
        INV_INI_CAJA   = :inv_ini_caja,
        INV_INI_SOBRES = :inv_ini_sobres,
        INV_INI_LITROS = :inv_ini_litros,
        ABASTO_CAJA    = :abasto_caja,
        ABASTO_SOBRES  = :abasto_sobres,
        ABASTO_LITROS  = :abasto_litros,
        VENTA_CAJA     = :venta_caja,
        VENTA_SOBRES   = :venta_sobres,
        VENTA_LITROS   = :venta_litros,
        REG_CAJA       = :reg_caja,
        REG_SOBRES     = :reg_sobres,
        REG_LITROS     = :reg_litros,
        DIF_CAJA       = :dif_caja,
        DIF_SOBRES     = :dif_sobres,
        DIF_LITROS     = :dif_litros,
        FIN_CAJA       = :fin_caja,
        FIN_SOBRES     = :fin_sobres,
        FIN_LITROS     = :fin_litros,
        PDF_RUTA       = :pdf_ruta,
        MES_PERIODO    = :mes,
        ANIO_PERIODO   = :anio,
        ESTADO         = 'editado'
        WHERE ID = :id";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':fecha'          => $datos['fecha']          ?? null,
        ':surt_fecha'     => $datos['surt_fecha']     ?? null,
        ':surt_cajas'     => $n($datos['surt_cajas']),
        ':surt_litros'    => $n($datos['surt_litros']),
        ':surt_factura'   => $datos['surt_factura']   ?? null,
        ':surt_caducidad' => $datos['surt_caducidad'] ?? null,
        ':inv_ini_caja'   => $n($datos['inv_ini_caja']),
        ':inv_ini_sobres' => $n($datos['inv_ini_sobres']),
        ':inv_ini_litros' => $n($datos['inv_ini_litros']),
        ':abasto_caja'    => $n($datos['abasto_caja']),
        ':abasto_sobres'  => $n($datos['abasto_sobres']),
        ':abasto_litros'  => $n($datos['abasto_litros']),
        ':venta_caja'     => $n($datos['venta_caja']),
        ':venta_sobres'   => $n($datos['venta_sobres']),
        ':venta_litros'   => $n($datos['venta_litros']),
        ':reg_caja'       => $n($datos['reg_caja']),
        ':reg_sobres'     => $n($datos['reg_sobres']),
        ':reg_litros'     => $n($datos['reg_litros']),
        ':dif_caja'       => $n($datos['dif_caja']),
        ':dif_sobres'     => $n($datos['dif_sobres']),
        ':dif_litros'     => $n($datos['dif_litros']),
        ':fin_caja'       => $n($datos['fin_caja']),
        ':fin_sobres'     => $n($datos['fin_sobres']),
        ':fin_litros'     => $n($datos['fin_litros']),
        ':pdf_ruta'       => $nombreArchivo,
        ':mes'            => $mes,
        ':anio'           => $anio,
        ':id'             => $id,
    ]);

    // Regenerar PDF en disco con los datos actualizados.
    try {
        require_once __DIR__ . '/_fn_pdf_inventario.php';
        if ($rutaPrev !== '' && $rutaPrev !== $nombreArchivo) {
            $rutaPrevAbs = __DIR__ . '/../datos/promotores/' . $rutaPrev;
            if (is_file($rutaPrevAbs)) @unlink($rutaPrevAbs);
        }
        generarArchivoInventario($datos);
    } catch (Throwable $ePDF) {
        error_log('[actualizar_inventario] PDF no regenerado: ' . $ePDF->getMessage());
    }

    // syncLepSubsidiada ya es no-op (INVENTARIOS_MENSUALES es la única fuente).
    echo json_encode(["status" => "success", "mensaje" => "Inventario actualizado correctamente."]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "mensaje" => "Error BD: " . $e->getMessage()]);
}
