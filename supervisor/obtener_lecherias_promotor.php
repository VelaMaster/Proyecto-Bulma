<?php
// supervisor/obtener_lecherias_promotor.php
// Devuelve las lecherías del PROMOTOR (PMT_NUMERO) indicado, fusionadas
// con su inventario mensual capturado en INVENTARIOS_MENSUALES.
require_once __DIR__ . '/../includes/session_guard.php';
session_write_close();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    echo json_encode(['error' => true, 'mensaje' => 'Acceso denegado.']);
    exit();
}

$id_supervisor = $_SESSION['clave_rol'] ?? null;
$promotor      = isset($_GET['promotor']) ? (int)$_GET['promotor'] : 0;
$mes_reporte   = isset($_GET['mes_reporte'])  ? (int)$_GET['mes_reporte']  : 0;
$anio_reporte  = isset($_GET['anio_reporte']) ? (int)$_GET['anio_reporte'] : 0;

if ($promotor <= 0 || $mes_reporte === 0 || $anio_reporte === 0) {
    echo json_encode(['error' => true, 'mensaje' => 'Parámetros inválidos.']);
    exit();
}

try {
    $pdo = DatabaseSQLite::getInstance();

    // Verificar mapeo supervisor ↔ lechería ↔ promotor
    $st = $pdo->prepare("
        SELECT COUNT(*) FROM lecheria L
        JOIN mapeo_supervisor_lecheria M
              ON M.LECHER = L.LECHER AND M.ID_SUPERVISOR = :sup
        WHERE L.PROMOTOR = :prom
    ");
    $st->execute([':sup' => $id_supervisor, ':prom' => $promotor]);
    if ((int)$st->fetchColumn() === 0) {
        echo json_encode(['error' => true, 'mensaje' => 'Promotor fuera de tu zona.']);
        exit();
    }

    $sql = "SELECT TRIM(CAST(L.LECHER AS TEXT)) AS LECHER,
                   TRIM(L.NUM_TIENDA)            AS NUM_TIENDA,
                   TRIM(L.ALMACEN_RURAL)         AS ALMACEN_RURAL,
                   L.TIPO_PUNTO_VENTA            AS TIPO_PUNTO_VENTA
            FROM lecheria L
            WHERE L.PROMOTOR = :prom
              AND L.EFD_NUMERO = 20
              AND COALESCE(L.EN_OPERACION, 0) = 0
            ORDER BY TRIM(L.ALMACEN_RURAL) ASC, L.TIPO_PUNTO_VENTA ASC, L.LECHER ASC";
    $st = $pdo->prepare($sql);
    $st->execute([':prom' => $promotor]);
    $lecherias = $st->fetchAll(PDO::FETCH_ASSOC);

    $sql_inv = "SELECT INV_INI_CAJA, INV_INI_SOBRES, SURT_CAJAS, ABASTO_CAJA, ABASTO_SOBRES,
                       VENTA_CAJA, VENTA_SOBRES, FIN_CAJA, FIN_SOBRES, REG_CAJA, REG_SOBRES,
                       SURT_FECHA, SURT_CADUCIDAD
                FROM inventarios_mensuales
                WHERE CLAVE_LECHERIA IN (?, ?)
                  AND MES_PERIODO  = " . (int)$mes_reporte . "
                  AND ANIO_PERIODO = " . (int)$anio_reporte;
    $stmt_inv = $pdo->prepare($sql_inv);

    foreach ($lecherias as &$lech) {
        $clave = trim((string)$lech['LECHER']);
        $stmt_inv->execute([$clave, $clave . '00']);
        $inv = $stmt_inv->fetch(PDO::FETCH_ASSOC);
        if ($inv) {
            $lech['encontrado']         = true;
            $lech['inv_ini_cajas']      = (int)$inv['INV_INI_CAJA'];
            $lech['inv_ini_sobres']     = (int)$inv['INV_INI_SOBRES'];
            $lech['dot_recibida_cajas'] = (int)$inv['SURT_CAJAS'];
            $lech['abasto_cajas']       = (int)$inv['ABASTO_CAJA'];
            $lech['abasto_sobres']      = (int)$inv['ABASTO_SOBRES'];
            $lech['venta_cajas']        = (int)$inv['VENTA_CAJA'];
            $lech['venta_sobres']       = (int)$inv['VENTA_SOBRES'];
            $lech['inv_fin_cajas']      = (int)$inv['FIN_CAJA'];
            $lech['inv_fin_sobres']     = (int)$inv['FIN_SOBRES'];
            $lech['retiro_cajas']       = (int)$inv['REG_CAJA'];
            $lech['retiro_sobres']      = (int)$inv['REG_SOBRES'];
        } else {
            $lech['encontrado'] = false;
        }
    }

    echo json_encode($lecherias, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['error' => true, 'mensaje' => $e->getMessage()]);
}
