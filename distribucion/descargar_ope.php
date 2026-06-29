<?php
// distribucion/descargar_ope.php
// GET: mes, anio, precio (4.50 | 6.50 | all), force (1=ignora autorización supervisores)
//
// Genera OPE{MM}{YYYY}DICONSA.xlsx agrupando por almacén con subtotales por
// almacén y por zona (MIXTECA/ISTMO/OAXACA) + TOTAL general. Lee de SQLite
// y rellena dinámicamente sheet1.xml de la plantilla.

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
        'message' => 'Falta la extensión PHP zip. Ejecuta: docker compose build --no-cache web && docker compose up -d'
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

$tipoFiltro = null;
if ($precio === '4.50' || $precio === '4.5') {
    $tipoFiltro = [0];
} elseif ($precio === '6.50' || $precio === '6.5') {
    $tipoFiltro = [1, 2];
}

$plantilla = __DIR__ . '/plantillas/OPE_PLANTILLA.xlsx';
$catAlmZon = require __DIR__ . '/catalogos/almacen_zona.php';

if (!is_file($plantilla)) {
    http_response_code(500);
    exit('Plantilla OPE_PLANTILLA.xlsx no encontrada.');
}

try {
    $pdo = DatabaseSQLite::getInstance();

    // ── 1) Validar autorización de supervisores ─────────────────────────────
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
        $autorizados = array_column($pdo->query("
            SELECT supervisor_clave FROM cierre_mes_supervisor
            WHERE mes = $mes AND anio = $anio
              AND supervisor_clave IN ($in)
        ")->fetchAll(), 'supervisor_clave');
    }

    $faltantes = array_values(array_filter($supsActivos, fn($s) =>
        !in_array((int)$s['id'], array_map('intval', $autorizados), true)
    ));

    if (!$force && !empty($faltantes)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'pendiente',
            'mes' => $mes, 'anio' => $anio,
            'supervisores_pendientes' => array_map(fn($s) => [
                'id' => (int)$s['id'], 'nombre' => trim($s['NOMBRE'] ?? '')
            ], $faltantes),
            'message' => 'Supervisores aún no han autorizado el cierre.'
        ]);
        exit();
    }

    // ── 2) Consultar datos consolidados ─────────────────────────────────────
    // Fuente primaria: reporte_mensual_lecher (R).
    // Fallback (HALLAZGO #6): si el promotor capturó inventario pero NO envió el
    // reporte formal, se usan los datos crudos de inventarios_mensuales (IM) para
    // que el OPE no salga vacío. Se marca como 'fallback_inv' en la fila.
    $sql = "
        SELECT
            L.LECHER                                                                 AS lecher,
            TRIM(COALESCE(R.almacen, L.ALMACEN_RURAL))                               AS almacen,
            L.TIPO_PUNTO_VENTA                                                       AS tipo,
            COALESCE(R.inv_ini_cajas,   IM.INV_INI_CAJA,   0)                        AS inv_ini_cajas,
            COALESCE(R.inv_ini_sobres,  IM.INV_INI_SOBRES, 0)                        AS inv_ini_sobres,
            COALESCE(R.dot_recib_cajas, IM.ABASTO_CAJA,    0)                        AS dot_recib_cajas,
            COALESCE(R.vend_cajas,      IM.VENTA_CAJA,     0)                        AS vend_cajas,
            COALESCE(R.vend_sobres,     IM.VENTA_SOBRES,   0)                        AS vend_sobres,
            COALESCE(R.inv_fin_cajas,   IM.FIN_CAJA,       0)                        AS inv_fin_cajas,
            COALESCE(R.inv_fin_sobres,  IM.FIN_SOBRES,     0)                        AS inv_fin_sobres,
            COALESCE(R.retiro_cajas,    IM.REG_CAJA,       0)                        AS retiro_cajas,
            COALESCE(R.sobres_rotos,    0)                                           AS sobres_rotos,
            COALESCE(R.sobres_falt,     0)                                           AS sobres_falt,
            R.observaciones                                                          AS observaciones,
            U.NOMBRE                                                                 AS sup_nombre,
            CASE
                WHEN R.clave_lecheria IS NOT NULL THEN 'reporte'
                WHEN IM.ID            IS NOT NULL THEN 'inventario'
                ELSE 'sin_datos'
            END                                                                      AS fuente_datos
        FROM lecheria L
        LEFT JOIN reporte_mensual_lecher R
               ON CAST(R.clave_lecheria AS INTEGER) = L.LECHER
              AND R.mes = :mes AND R.anio = :anio
        LEFT JOIN inventarios_mensuales IM
               ON CAST(IM.CLAVE_LECHERIA AS INTEGER) = L.LECHER
              AND IM.MES_PERIODO  = :mes
              AND IM.ANIO_PERIODO = :anio
        LEFT JOIN mapeo_supervisor_lecheria M ON M.LECHER = L.LECHER
        LEFT JOIN usuarios_inventarios U
               ON U.CLAVE_ROL = M.ID_SUPERVISOR
              AND U.ROL IN ('1','supervisor')
        WHERE COALESCE(L.EN_OPERACION, 0) = 0
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
        exit('No hay lecherías para ese mes/año/precio.');
    }

    // ── 3) Normalizar y agrupar por almacén ─────────────────────────────────
    $aliasAlm = $catAlmZon['alias'];
    $ordenAlm = $catAlmZon['orden_almacenes'];
    $zonaDe   = $catAlmZon['zona_de'];

    $normAlm = function($s) use ($aliasAlm) {
        $s = strtoupper(trim((string)$s));
        $s = strtr($s, ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N']);
        return $aliasAlm[$s] ?? $s;
    };

    $porAlmacen = [];
    foreach ($rows as $r) {
        $alm = $normAlm($r['almacen']);
        $porAlmacen[$alm][] = $r;
    }

    // Orden: primero los del catálogo, después los desconocidos
    $almacenesOrden = array_values(array_unique(array_merge(
        array_values(array_filter($ordenAlm, fn($a) => isset($porAlmacen[$a]))),
        array_diff(array_keys($porAlmacen), $ordenAlm)
    )));

    // ── 4) Calcular cada lechería y subtotales ──────────────────────────────
    // I = G*72 + H*2 (VENTA REAL MES total)
    // L = I (duplicado VTA.SEG.LIS.RET.)
    // U = V*72 + X*2 (INVENTARIO FINAL total)
    // T = inv_ini_cajas*72 + inv_ini_sobres*2 (INV INICIAL total)
    // W = 72 (constante)
    // Y = U (LITROS CONSUMIDOS LTA.ESPERA)

    $colsSum = ['F','G','H','I','L','N','O','P','T','U','V','X','Y','AA'];

    $calcLech = function($r) {
        $G = (int)$r['vend_cajas'];
        $H = (int)$r['vend_sobres'];
        $I = $G * 72 + $H * 2;
        $V = (int)$r['inv_fin_cajas'];
        $X = (int)$r['inv_fin_sobres'];
        $U = $V * 72 + $X * 2;
        $T = (int)$r['inv_ini_cajas'] * 72 + (int)$r['inv_ini_sobres'] * 2;
        $N = (int)$r['dot_recib_cajas'];
        return [
            'A'  => (int)$r['lecher'],
            'B'  => (int)$r['tipo'] === 0 ? 4.5 : 6.5,
            'C'  => $GLOBALS['mes'] ?? null,
            'D'  => $GLOBALS['anio'] ?? null,
            'F'  => 0,            // DIAS DE OPER (no capturado)
            'G'  => $G,
            'H'  => $H,
            'I'  => $I,
            'J'  => $G,           // duplicado
            'K'  => $H,
            'L'  => $I,           // duplicado
            'N'  => $N,
            'O'  => $N * 72,      // DOT.REAL POR DIA (asume sobres equivalentes)
            'P'  => $N * 72,      // DOT.REAL DEL MES
            'R'  => (int)$r['sobres_rotos'],
            'S'  => (int)$r['sobres_falt'],
            'T'  => $T,
            'U'  => $U,
            'V'  => $V,
            'W'  => 72,
            'X'  => $X,
            'Y'  => $U,
            'AA' => 0,
            'AF' => trim((string)($r['sup_nombre'] ?? '')),
            'AH' => trim((string)($r['observaciones'] ?? '')),
        ];
    };

    // ── 5) Construir filas XML ──────────────────────────────────────────────
    // Estilos canónicos verificados contra OPE042026DICONSA.xls (cellXfs 0-50)
    $stLech = [
        'A'=>25,'B'=>26,'C'=>19,'D'=>19,'E'=>19,'F'=>20,'G'=>21,'H'=>21,'I'=>19,
        'J'=>21,'K'=>21,'L'=>19,'M'=>19,'N'=>22,'O'=>19,'P'=>19,'Q'=>19,'R'=>19,
        'S'=>19,'T'=>19,'U'=>19,'V'=>23,'W'=>23,'X'=>23,'Y'=>24,
        'AA'=>1,'AD'=>1,'AF'=>1,'AG'=>27,'AH'=>27,
    ];
    $stSub = [
        'A'=>28,'B'=>29,'C'=>30,'D'=>29,'E'=>29,'F'=>31,'G'=>32,'H'=>32,'I'=>28,
        'J'=>32,'K'=>32,'L'=>28,'M'=>29,'N'=>22,'O'=>28,'P'=>28,'Q'=>29,'R'=>29,
        'S'=>29,'T'=>28,'U'=>28,'V'=>33,'W'=>23,'X'=>33,'Y'=>29,'AA'=>1,
    ];
    $stZona = [
        'A'=>30,'B'=>30,'C'=>19,'D'=>30,'E'=>28,'F'=>44,'G'=>28,'H'=>28,'I'=>28,
        'J'=>28,'K'=>28,'L'=>28,'M'=>28,'N'=>48,'O'=>28,'P'=>28,'Q'=>28,'R'=>28,
        'S'=>28,'T'=>28,'U'=>28,'V'=>29,'W'=>29,'X'=>29,'AA'=>28,
    ];
    $stHeaderAlm = 19; // estilo único para nombre del almacén en col A

    $colsLech = array_keys($stLech);
    $colsSubt = array_keys($stSub);

    // helpers
    $cellNum = function($ref, $sty, $val) {
        if ($val === null || $val === '') return '<c r="'.$ref.'" s="'.$sty.'" t="n"></c>';
        $v = (is_int($val) || (is_float($val) && floor($val) == $val)) ? (string)(int)$val : (string)$val;
        return '<c r="'.$ref.'" s="'.$sty.'" t="n"><v>'.$v.'</v></c>';
    };
    $cellStr = function($ref, $sty, $val) {
        if ($val === null || $val === '') return '<c r="'.$ref.'" s="'.$sty.'" t="n"></c>';
        $esc = htmlspecialchars((string)$val, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        return '<c r="'.$ref.'" s="'.$sty.'" t="inlineStr"><is><t>'.$esc.'</t></is></c>';
    };

    $xmlRows = '';
    $rowNum  = 9;

    // Acumuladores por zona y total general
    $zonaSum = [];   // zona => array col=>sum
    $totalSum = array_fill_keys($colsSum, 0);
    $countTotal = 0;
    $zonaCount = [];

    foreach ($almacenesOrden as $alm) {
        $lechs = $porAlmacen[$alm];
        $countAlm = count($lechs);
        $sumAlm   = array_fill_keys($colsSum, 0);

        // FILA: header del almacén (texto en A)
        $xmlRows .= '<row r="'.$rowNum.'" ht="18" customHeight="1">';
        $xmlRows .= $cellStr('A'.$rowNum, $stHeaderAlm, $alm);
        $xmlRows .= '</row>';
        $rowNum++;

        // FILAS: cada lechería
        foreach ($lechs as $r) {
            $L = $calcLech($r);
            $L['C'] = $mes;
            $L['D'] = $anio;

            $cells = '';
            foreach ($colsLech as $col) {
                $sty = $stLech[$col];
                $ref = $col . $rowNum;
                $val = $L[$col] ?? null;

                if ($val === null || $val === '' || $val === 0) {
                    // Mantener A,B,C,D aunque sean cero
                    if (in_array($col, ['A','B','C','D'], true)) {
                        $cells .= $cellNum($ref, $sty, $val);
                    } else {
                        $cells .= $cellNum($ref, $sty, null);
                    }
                    continue;
                }

                if (in_array($col, ['AF','AH'], true)) {
                    $cells .= $cellStr($ref, $sty, $val);
                } else {
                    $cells .= $cellNum($ref, $sty, $val);
                }

                if (in_array($col, $colsSum, true)) {
                    $sumAlm[$col] += (float)$val;
                }
            }

            $xmlRows .= '<row r="'.$rowNum.'" ht="18" customHeight="1" s="56">'.$cells.'</row>';
            $rowNum++;
        }

        // FILA: subtotal del almacén (A=count, M='T', sumas en cols)
        $cellsSub = '';
        foreach ($colsSubt as $col) {
            $sty = $stSub[$col];
            $ref = $col . $rowNum;

            if ($col === 'A') {
                $cellsSub .= $cellNum($ref, $sty, $countAlm);
            } elseif ($col === 'M') {
                $cellsSub .= $cellStr($ref, $sty, 'T');
            } elseif (in_array($col, $colsSum, true)) {
                $cellsSub .= $cellNum($ref, $sty, $sumAlm[$col] ?: null);
            } else {
                $cellsSub .= $cellNum($ref, $sty, null);
            }
        }
        $xmlRows .= '<row r="'.$rowNum.'" ht="18" customHeight="1" s="56">'.$cellsSub.'</row>';
        $rowNum++;

        // Acumular por zona y total
        $zona = $zonaDe[$alm] ?? '(SIN ZONA)';
        if (!isset($zonaSum[$zona])) {
            $zonaSum[$zona]   = array_fill_keys($colsSum, 0);
            $zonaCount[$zona] = 0;
        }
        foreach ($colsSum as $c) {
            $zonaSum[$zona][$c] += $sumAlm[$c];
            $totalSum[$c]       += $sumAlm[$c];
        }
        $zonaCount[$zona] += $countAlm;
        $countTotal       += $countAlm;
    }

    // ── 6) Subtotales por zona + TOTAL ──────────────────────────────────────
    $rowNum++; // fila vacía de separación

    $colsZona = array_keys($stZona);
    foreach (['MIXTECA','ISTMO','OAXACA'] as $zona) {
        if (!isset($zonaSum[$zona])) continue;
        $cellsZ = '';
        foreach ($colsZona as $col) {
            $sty = $stZona[$col];
            $ref = $col . $rowNum;
            if ($col === 'A' && $zona === 'ISTMO') {
                $cellsZ .= $cellNum($ref, $sty, $countTotal); // como en R732 del original
            } elseif ($col === 'E') {
                $cellsZ .= $cellStr($ref, $sty, $zona);
            } elseif (in_array($col, $colsSum, true)) {
                $cellsZ .= $cellNum($ref, $sty, $zonaSum[$zona][$col] ?: null);
            } else {
                $cellsZ .= $cellNum($ref, $sty, null);
            }
        }
        $xmlRows .= '<row r="'.$rowNum.'" ht="18" customHeight="1">'.$cellsZ.'</row>';
        $rowNum++;
    }

    // TOTAL general
    $cellsT = '';
    foreach ($colsZona as $col) {
        $sty = $stZona[$col];
        $ref = $col . $rowNum;
        if ($col === 'E') {
            $cellsT .= $cellStr($ref, $sty, 'TOTAL');
        } elseif (in_array($col, $colsSum, true)) {
            $cellsT .= $cellNum($ref, $sty, $totalSum[$col] ?: null);
        } else {
            $cellsT .= $cellNum($ref, $sty, null);
        }
    }
    $xmlRows .= '<row r="'.$rowNum.'" ht="18" customHeight="1">'.$cellsT.'</row>';

    // ── 7) Leer plantilla y reemplazar sheetData ────────────────────────────
    $tmpPath = tempnam(sys_get_temp_dir(), 'ope_') . '.xlsx';
    if (!copy($plantilla, $tmpPath)) {
        http_response_code(500); exit('No se pudo copiar la plantilla.');
    }

    $zip = new ZipArchive();
    if ($zip->open($tmpPath) !== true) {
        @unlink($tmpPath);
        http_response_code(500); exit('No se pudo abrir la plantilla XLSX.');
    }

    $sheetPath = 'xl/worksheets/sheet1.xml';
    $xml = $zip->getFromName($sheetPath);
    if ($xml === false) {
        $zip->close(); @unlink($tmpPath);
        http_response_code(500); exit('sheet1.xml no encontrado.');
    }

    // Reemplazar mes/año en header
    $nombresMes = ['','ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO',
                   'JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];
    $mesAnio = $nombresMes[$mes] . ' ' . $anio;
    $xml = str_replace('###MES_ANIO###', htmlspecialchars($mesAnio, ENT_XML1, 'UTF-8'), $xml);

    // Inyectar nuestras filas dentro de <sheetData>...</sheetData>
    // La plantilla viene con R1-R8 (headers) en sheetData. Pegamos nuestras filas justo antes de </sheetData>.
    $xml = preg_replace(
        '#</sheetData>#',
        $xmlRows . '</sheetData>',
        $xml,
        1
    );

    // Actualizar <dimension ref="A1:AI{rowNum}"/>
    $xml = preg_replace(
        '#<dimension ref="[^"]*"/>#',
        '<dimension ref="A1:AI'.$rowNum.'"/>',
        $xml,
        1
    );

    // ── 8) Guardar y enviar ────────────────────────────────────────────────
    $zip->deleteName($sheetPath);
    $zip->addFromString($sheetPath, $xml);
    $zip->close();

    $filename = sprintf('OPE%02d%dDICONSA.xlsx', $mes, $anio);
    if ($tipoFiltro !== null) {
        $sufijo = ($tipoFiltro === [0]) ? '_4.50' : '_6.50';
        $filename = sprintf('OPE%02d%dDICONSA%s.xlsx', $mes, $anio, $sufijo);
    }

    require_once __DIR__ . '/../includes/pdf_archivado.php';
    archivarArchivo($tmpPath, [
        'tipo'    => 'ope',
        'modulo'  => 'distribucion',
        'subdir'  => 'ope',
        'mes'     => $mes,
        'anio'    => $anio,
        'usuario' => $_SESSION['usuario'] ?? '',
        'nombre'  => $filename,
        'extras'  => ['precio' => $precio],
    ]);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tmpPath));
    header('Cache-Control: max-age=0');
    readfile($tmpPath);
    @unlink($tmpPath);

} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}
