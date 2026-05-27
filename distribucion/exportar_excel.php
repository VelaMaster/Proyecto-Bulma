<?php
// distribucion/exportar_excel.php
// GET: mes, anio, precio, supervisor_id (0=todos), almacen (vacío=todos)
// Descarga CSV (con BOM UTF-8 para que Excel lo abra bien)
require_once __DIR__ . '/../includes/session_guard.php';
session_write_close();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'distribucion') {
    http_response_code(403); exit('Acceso denegado');
}

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

$mes         = isset($_GET['mes'])           ? (int)$_GET['mes']           : 0;
$anio        = isset($_GET['anio'])          ? (int)$_GET['anio']          : 0;
$precio      = isset($_GET['precio'])        ? trim($_GET['precio'])        : '6.50';
$supIdFiltro = isset($_GET['supervisor_id']) ? (int)$_GET['supervisor_id'] : 0;
$almFiltro   = isset($_GET['almacen'])       ? strtoupper(trim($_GET['almacen'])) : '';

if ($mes < 1 || $mes > 12 || $anio < 2000) {
    http_response_code(400); exit('Parámetros inválidos');
}

$precioNum = (float)str_replace(['$', ','], ['', '.'], $precio);
if (abs($precioNum - 4.50) < 0.001) {
    $precioNum = 4.50; $tipoVentaFiltro = [0];
} elseif (abs($precioNum - 6.50) < 0.001) {
    $precioNum = 6.50; $tipoVentaFiltro = [1, 2];
} else {
    http_response_code(400); exit('Precio inválido');
}

try {
    $pdo = Database::getInstance();
    $sql = "
        SELECT TRIM(L.LECHER)        AS LECHER,
               TRIM(L.NUM_TIENDA)    AS NUM_TIENDA,
               TRIM(L.ALMACEN_RURAL) AS ALMACEN,
               L.TIPO_PUNTO_VENTA    AS TIPO_PUNTO_VENTA,
               M.ID_SUPERVISOR       AS ID_SUPERVISOR,
               TRIM(U.NOMBRE)        AS SUPERVISOR_NOMBRE
        FROM LECHERIA L
        JOIN PROMOTOR P ON P.PMT_NUMERO = L.PROMOTOR
        LEFT JOIN MAPEO_SUPERVISOR_LECHERIA M ON TRIM(M.LECHER) = TRIM(L.LECHER)
        LEFT JOIN USUARIOS_INVENTARIOS U
               ON U.CLAVE_ROL = M.ID_SUPERVISOR AND U.ROL = '1'
        WHERE P.PMT_ACTIVO = 'S'
          AND COALESCE(L.EN_OPERACION, 0) = 0
        ORDER BY COALESCE(TRIM(U.NOMBRE), 'ZZZ'),
                 TRIM(L.ALMACEN_RURAL), TRIM(L.LECHER)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sqlite = DatabaseSQLite::getInstance();
    $stmtSQ = $sqlite->prepare(
        "SELECT clave_lecheria, req_actual FROM requerimiento_dotacion
         WHERE mes_base = :mes AND anio_base = :anio"
    );
    $stmtSQ->execute([':mes' => $mes, ':anio' => $anio]);
    $reqs = [];
    foreach ($stmtSQ->fetchAll() as $rq) {
        $reqs[trim((string)$rq['clave_lecheria'])] = (int)$rq['req_actual'];
    }

    $mesesNombres = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                     'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    $nombreMes = $mesesNombres[$mes] ?? $mes;

    $supSlug = $supIdFiltro ? "sup{$supIdFiltro}_" : 'todos_';
    $almSlug = $almFiltro   ? '_' . preg_replace('/[^A-Za-z0-9]/', '', $almFiltro) : '';
    $filename = "req_{$anio}_{$mes}_{$supSlug}p" . str_replace('.', '', number_format($precioNum, 2)) . "{$almSlug}.csv";

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store');
    header('Pragma: no-cache');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8

    fputcsv($out, ['REQUERIMIENTO DE DOTACIÓN — ' . strtoupper($nombreMes) . ' ' . $anio]);
    fputcsv($out, ['Precio:', '$' . number_format($precioNum, 2) . '/litro']);
    fputcsv($out, ['Generado:', date('d/m/Y H:i')]);
    fputcsv($out, []);
    fputcsv($out, ['Supervisor', 'Almacén', 'Punto de Venta', 'Tienda', 'Requerimiento (cajas)']);

    $subtotalSup = 0;
    $supActual   = null;
    $almActual   = null;
    $subtotalAlm = 0;

    foreach ($rows as $r) {
        $tipo = (int)$r['TIPO_PUNTO_VENTA'];
        if (!in_array($tipo, $tipoVentaFiltro, true)) continue;

        $supId     = $r['ID_SUPERVISOR'] !== null ? (int)$r['ID_SUPERVISOR'] : 0;
        $supNombre = $r['SUPERVISOR_NOMBRE'] !== null
            ? trim((string)$r['SUPERVISOR_NOMBRE']) : '(Sin supervisor)';
        if ($supNombre === '') $supNombre = 'Supervisor #' . $supId;
        $supNombre = mb_convert_encoding($supNombre, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');

        if ($supIdFiltro && $supId !== $supIdFiltro) continue;

        $alm = strtoupper(trim((string)$r['ALMACEN']));
        if ($alm === '') $alm = '(SIN ALMACÉN)';
        $alm = mb_convert_encoding($alm, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');

        if ($almFiltro && $alm !== $almFiltro) continue;

        // Fila de subtotal anterior si cambia supervisor o almacén
        if ($almActual !== null && ($almActual !== $alm || $supActual !== $supNombre)) {
            fputcsv($out, ['', '', '', 'SUBTOTAL:', $subtotalAlm]);
            fputcsv($out, []);
            $subtotalAlm = 0;
        }
        if ($supActual !== null && $supActual !== $supNombre) {
            fputcsv($out, ['', '', '', 'TOTAL SUPERVISOR:', $subtotalSup]);
            fputcsv($out, []);
            $subtotalSup = 0;
        }

        $supActual = $supNombre;
        $almActual = $alm;

        $numTiendaRaw = trim((string)$r['NUM_TIENDA']);
        $tienda = ($tipo === 2 || $numTiendaRaw === '10101') ? 'DM' : $numTiendaRaw;

        $k   = trim((string)$r['LECHER']);
        $req = isset($reqs[$k]) ? $reqs[$k] : 'FALTA';
        $reqNum = is_int($req) ? $req : 0;

        fputcsv($out, [$supNombre, $alm, $k, $tienda, $req]);

        if (is_int($req)) {
            $subtotalAlm += $reqNum;
            $subtotalSup += $reqNum;
        }
    }

    // Último subtotal
    if ($almActual !== null) {
        fputcsv($out, ['', '', '', 'SUBTOTAL:', $subtotalAlm]);
        fputcsv($out, []);
    }
    if ($supActual !== null) {
        fputcsv($out, ['', '', '', 'TOTAL SUPERVISOR:', $subtotalSup]);
        fputcsv($out, []);
    }

    fclose($out);

} catch (Exception $e) {
    if (headers_sent()) exit();
    http_response_code(500);
    header('Content-Type: text/plain');
    exit('Error: ' . $e->getMessage());
}
