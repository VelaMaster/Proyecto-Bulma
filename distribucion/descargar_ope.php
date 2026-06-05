<?php
// distribucion/descargar_ope.php
// GET: mes, anio, precio (4.50 | 6.50 | all, default=all), force (1=ignora autorización supervisores)
// Genera y descarga OPE{MM}{YYYY}DICONSA.xlsx con datos consolidados desde SQLite,
// rellenando la plantilla distribucion/plantillas/OPE_PLANTILLA.xlsx.

require_once __DIR__ . '/../includes/session_guard.php';
session_write_close();
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'distribucion') {
    http_response_code(403);
    exit('Acceso denegado.');
}

if (!class_exists('ZipArchive')) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'  => 'error',
        'message' => 'Falta la extensión PHP zip. Ejecuta en la raíz del proyecto: docker compose build --no-cache web && docker compose up -d'
    ]);
    exit();
}

$mes    = isset($_GET['mes'])    ? (int)$_GET['mes']    : 0;
$anio   = isset($_GET['anio'])   ? (int)$_GET['anio']   : 0;
$precio = isset($_GET['precio']) ? trim($_GET['precio']) : 'all';
$force  = !empty($_GET['force']);

if ($mes < 1 || $mes > 12 || $anio < 2000) {
    http_response_code(400);
    exit('Parámetros inválidos (mes/anio).');
}

// Filtro de precio: 4.50 → TIPO_PUNTO_VENTA=0, 6.50 → 1|2, all → no filtra
$tipoFiltro = null;
if ($precio === '4.50' || $precio === '4.5') {
    $tipoFiltro = [0];
} elseif ($precio === '6.50' || $precio === '6.5') {
    $tipoFiltro = [1, 2];
}

$plantilla = __DIR__ . '/plantillas/OPE_PLANTILLA.xlsx';
if (!is_file($plantilla)) {
    http_response_code(500);
    exit('Plantilla OPE_PLANTILLA.xlsx no encontrada.');
}

try {
    $pdo = DatabaseSQLite::getInstance();

    // ── 1) Validar autorización de supervisores ─────────────────────────────
    // Supervisores activos en el sistema
    $supsActivos = $pdo->query("
        SELECT DISTINCT CLAVE_ROL AS id, NOMBRE
        FROM usuarios_inventarios
        WHERE ROL IN ('1','supervisor')
          AND COALESCE(ACTIVO, 1) = 1
    ")->fetchAll();

    $autorizados = [];
    if ($supsActivos) {
        $idsActivos = array_column($supsActivos, 'id');
        $in = implode(',', array_map('intval', $idsActivos));
        $rowsAut = $pdo->query("
            SELECT supervisor_clave FROM cierre_mes_supervisor
            WHERE mes = $mes AND anio = $anio
              AND supervisor_clave IN ($in)
        ")->fetchAll();
        $autorizados = array_column($rowsAut, 'supervisor_clave');
    }

    $faltantes = array_values(array_filter($supsActivos, function($s) use ($autorizados) {
        return !in_array((int)$s['id'], array_map('intval', $autorizados), true);
    }));

    if (!$force && !empty($faltantes)) {
        // Devolver lista para que la UI muestre los pendientes
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'pendiente',
            'mes' => $mes, 'anio' => $anio,
            'supervisores_pendientes' => array_map(function($s) {
                return ['id' => (int)$s['id'], 'nombre' => trim($s['NOMBRE'] ?? '')];
            }, $faltantes),
            'message' => 'Supervisores aún no han autorizado el cierre.'
        ]);
        exit();
    }

    // ── 2) Consultar datos consolidados ─────────────────────────────────────
    $sql = "
        SELECT
            L.LECHER                          AS lecher,
            L.TIPO_PUNTO_VENTA                AS tipo,
            R.inv_ini_cajas                   AS inv_ini_cajas,
            R.inv_ini_sobres                  AS inv_ini_sobres,
            R.dot_recib_cajas                 AS dot_recib_cajas,
            R.vend_cajas                      AS vend_cajas,
            R.vend_sobres                     AS vend_sobres,
            R.inv_fin_cajas                   AS inv_fin_cajas,
            R.inv_fin_sobres                  AS inv_fin_sobres,
            R.retiro_cajas                    AS retiro_cajas,
            R.retiro_sobres                   AS retiro_sobres,
            R.sobres_rotos                    AS sobres_rotos,
            R.sobres_falt                     AS sobres_falt,
            R.observaciones                   AS observaciones,
            U.NOMBRE                          AS sup_nombre,
            M.ID_SUPERVISOR                   AS sup_id
        FROM reporte_mensual_lecher R
        JOIN lecheria L ON L.LECHER = CAST(R.clave_lecheria AS INTEGER)
        LEFT JOIN mapeo_supervisor_lecheria M ON M.LECHER = L.LECHER
        LEFT JOIN usuarios_inventarios U
               ON U.CLAVE_ROL = M.ID_SUPERVISOR
              AND U.ROL IN ('1','supervisor')
        WHERE R.mes = :mes AND R.anio = :anio
          AND COALESCE(L.EN_OPERACION, 0) = 0
    ";
    $params = [':mes' => $mes, ':anio' => $anio];
    if ($tipoFiltro !== null) {
        $in = implode(',', array_map('intval', $tipoFiltro));
        $sql .= " AND L.TIPO_PUNTO_VENTA IN ($in)";
    }
    $sql .= " ORDER BY L.LECHER";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    if (empty($rows)) {
        http_response_code(404);
        exit('No hay datos capturados para ese mes/año.');
    }

    // ── 3) Copiar plantilla a archivo temporal ──────────────────────────────
    $tmpPath = tempnam(sys_get_temp_dir(), 'ope_') . '.xlsx';
    if (!copy($plantilla, $tmpPath)) {
        http_response_code(500);
        exit('No se pudo copiar la plantilla.');
    }

    $zip = new ZipArchive();
    if ($zip->open($tmpPath) !== true) {
        @unlink($tmpPath);
        http_response_code(500);
        exit('No se pudo abrir la plantilla XLSX.');
    }

    $sheetPath = 'xl/worksheets/sheet1.xml';
    $xml = $zip->getFromName($sheetPath);
    if ($xml === false) {
        $zip->close(); @unlink($tmpPath);
        http_response_code(500);
        exit('sheet1.xml no encontrado en la plantilla.');
    }

    // ── 4) Reemplazar placeholder mes/año en headers ────────────────────────
    $nombresMes = ['','ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO',
                   'JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];
    $mesAnio = $nombresMes[$mes] . ' ' . $anio;
    $xml = str_replace('###MES_ANIO###', htmlspecialchars($mesAnio, ENT_XML1, 'UTF-8'), $xml);

    // ── 5) Inyectar datos en filas R10..R(10+N-1) ───────────────────────────
    // La plantilla tiene R10..R750 pre-formateadas con celdas <c r="X{row}" s="N" t="n"></c>.
    // Para cada lechería, reemplazo el contenido vacío de las celdas que mapeamos.
    //
    // Mapeo Letra → llave del array $rows + tipo ('n' numérico, 's' string inline).
    // Las columnas dudosas (DOT.REAL, LITROS CONSUMIDOS, EDO. OPER., etc.) se dejan
    // vacías para que el área de Distribución las llene a mano si las necesita.

    foreach ($rows as $i => $r) {
        $rowNum = 10 + $i;
        if ($rowNum > 750) break; // límite de la plantilla

        $precioLech = ((int)$r['tipo'] === 0) ? 4.5 : 6.5;

        $cells = [
            'A'  => ['n', $r['lecher']],
            'B'  => ['n', $precioLech],
            'C'  => ['n', $mes],
            'D'  => ['n', $anio],
            'G'  => ['n', $r['dot_recib_cajas']],
            'J'  => ['n', $r['vend_cajas']],
            'K'  => ['n', $r['vend_sobres']],
            'L'  => ['n', $r['retiro_cajas']],
            'R'  => ['n', $r['sobres_rotos']],
            'S'  => ['n', $r['sobres_falt']],
            'T'  => ['n', $r['inv_ini_cajas']],
            'V'  => ['n', $r['inv_fin_cajas']],
            'X'  => ['n', $r['inv_fin_sobres']],
            'AF' => ['s', trim((string)$r['sup_nombre'])],
            'AH' => ['s', trim((string)$r['observaciones'])],
        ];

        foreach ($cells as $colLetra => $cell) {
            [$tipo, $val] = $cell;
            if ($val === null || $val === '' || $val === 0 && $colLetra === 'AH') continue;

            $ref = $colLetra . $rowNum;
            $refQ = preg_quote($ref, '#');

            if ($tipo === 'n') {
                $numVal = is_numeric($val) ? (float)$val : 0;
                if ($numVal == 0 && $colLetra !== 'A' && $colLetra !== 'C' && $colLetra !== 'D') {
                    // omitimos ceros en columnas que no son LECHER/MES/AÑO (evita ruido en Excel)
                    continue;
                }
                // Formato sin decimales innecesarios
                $strVal = (floor($numVal) == $numVal) ? (string)(int)$numVal : (string)$numVal;
                $pattern = '#<c r="' . $refQ . '"([^>]*) t="n"></c>#';
                $replace = '<c r="' . $ref . '"$1 t="n"><v>' . $strVal . '</v></c>';
                $xml = preg_replace($pattern, $replace, $xml, 1);
            } else {
                $strVal = (string)$val;
                if ($strVal === '') continue;
                $valEsc = htmlspecialchars($strVal, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $pattern = '#<c r="' . $refQ . '"([^>]*) t="n"></c>#';
                $replace = '<c r="' . $ref . '"$1 t="inlineStr"><is><t>' . $valEsc . '</t></is></c>';
                $xml = preg_replace($pattern, $replace, $xml, 1);
            }
        }
    }

    // ── 6) Guardar sheet1.xml modificado de vuelta al ZIP ───────────────────
    if ($zip->deleteName($sheetPath) === false) {
        // En algunos casos deleteName falla pero addFromString sobrescribe
    }
    $zip->addFromString($sheetPath, $xml);
    $zip->close();

    // ── 7) Stream descarga ──────────────────────────────────────────────────
    $filename = sprintf('OPE%02d%dDICONSA.xlsx', $mes, $anio);
    if ($tipoFiltro !== null) {
        $sufijo = ($tipoFiltro === [0]) ? '_4.50' : '_6.50';
        $filename = sprintf('OPE%02d%dDICONSA%s.xlsx', $mes, $anio, $sufijo);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tmpPath));
    header('Cache-Control: max-age=0');
    readfile($tmpPath);
    @unlink($tmpPath);
    exit();

} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
