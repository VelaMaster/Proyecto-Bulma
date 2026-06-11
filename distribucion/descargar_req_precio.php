<?php
// distribucion/descargar_req_precio.php
// GET: mes, anio, precio (4.50 | 6.50)
//
// Genera el "Requerimiento de Leche en Polvo" mensual para el precio indicado.
// - mes/anio = mes destino (al que llega la leche; el reporte se llama "JUNIO 2026")
// - mes_base = mes - 1 → consulta requerimiento_dotacion.req_actual
// - mes_pad  = mes - 2 → leyenda "Padrón al cierre de ABRIL 2026"
//
// Estructura:
//   POR ALMACEN  → headers fijos + bloques (ALMACEN + lecherías + TOTAL + OBSERVACIONES + TOTAL_RECIBIR)
//   TOTAL        → resumen por SUCURSAL (HUAJUAPAN / ISTMO-COSTA / V. CENTRAL) con referencias cruzadas

require_once __DIR__ . '/../includes/session_guard.php';
session_write_close();
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'distribucion') {
    http_response_code(403); exit('Acceso denegado.');
}
if (!class_exists('ZipArchive')) {
    http_response_code(500); exit('Falta la extensión PHP zip.');
}

$mes    = isset($_GET['mes'])    ? (int)$_GET['mes']    : 0;
$anio   = isset($_GET['anio'])   ? (int)$_GET['anio']   : 0;
$precio = isset($_GET['precio']) ? trim($_GET['precio']) : '';

if ($mes < 1 || $mes > 12 || $anio < 2000) {
    http_response_code(400); exit('Parámetros inválidos (mes/anio).');
}

if ($precio === '6.50' || $precio === '6.5') {
    $cfgKey = '650';
    $tipoFiltro = [1, 2];
} elseif ($precio === '4.50' || $precio === '4.5') {
    $cfgKey = '450';
    $tipoFiltro = [0];
} else {
    http_response_code(400); exit('Precio inválido (use 4.50 o 6.50).');
}

$plantilla = __DIR__ . "/plantillas/REQ_{$cfgKey}_PLANTILLA.xlsx";
$estilosJs = __DIR__ . '/plantillas/REQ_ESTILOS.json';
$catSucAlm = require __DIR__ . '/catalogos/sucursal_almacen.php';

if (!is_file($plantilla) || !is_file($estilosJs)) {
    http_response_code(500); exit('Plantilla/estilos no encontrados.');
}

$ESTILOS = json_decode(file_get_contents($estilosJs), true);
$CFG     = $ESTILOS[$cfgKey];

// mes_base = mes - 1 ; mes_pad = mes - 2
$mes_base = $mes - 1; $anio_base = $anio;
if ($mes_base < 1) { $mes_base += 12; $anio_base--; }
$mes_pad = $mes_base - 1; $anio_pad = $anio_base;
if ($mes_pad < 1) { $mes_pad += 12; $anio_pad--; }

$NOMBRE_MES = ['','ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO',
               'JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];

try {
    $pdo = DatabaseSQLite::getInstance();

    // ── 1) Lecherías con padrón (CC_FAM y suma CC_BT1..CC_BT7) ─────────────
    $sql = "
        SELECT  L.LECHER, L.NUM_TIENDA, L.NOMBRELECH,
                TRIM(L.ALMACEN_RURAL)   AS ALMACEN,
                L.TIPO_PUNTO_VENTA      AS TIPO,
                COALESCE(L.CC_FAM,0)    AS FAM,
                COALESCE(L.CC_BT1,0)+COALESCE(L.CC_BT2,0)+COALESCE(L.CC_BT3,0)+
                COALESCE(L.CC_BT4,0)+COALESCE(L.CC_BT5,0)+COALESCE(L.CC_BT6,0)+
                COALESCE(L.CC_BT7,0)    AS BENEF
        FROM lecheria L
        WHERE COALESCE(L.EN_OPERACION,0) = 0
          AND L.TIPO_PUNTO_VENTA IN (".implode(',', array_map('intval',$tipoFiltro)).")
        ORDER BY L.ALMACEN_RURAL, L.LECHER
    ";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) { http_response_code(404); exit('No hay lecherías para ese precio.'); }

    // ── 2) Requerimientos (req_actual) del mes_base ────────────────────────
    $st = $pdo->prepare("SELECT clave_lecheria, req_actual
                         FROM requerimiento_dotacion
                         WHERE mes_base = :m AND anio_base = :a");
    $st->execute([':m' => $mes_base, ':a' => $anio_base]);
    $reqs = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $reqs[trim((string)$r['clave_lecheria'])] = (int)$r['req_actual'];
    }

    // ── 3) Agrupar por almacén canónico (aplica alias) ─────────────────────
    $alias = $catSucAlm['alias'];
    $normAlm = function($s) use ($alias) {
        $s = strtoupper(trim((string)$s));
        $s = strtr($s, ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N']);
        return $alias[$s] ?? $s;
    };
    $porAlm = [];
    foreach ($rows as $r) {
        $alm = $normAlm($r['ALMACEN']);
        $porAlm[$alm][] = $r;
    }

    // ── 4) Orden por SUCURSAL → almacén (del catálogo) ─────────────────────
    $sucursales = $catSucAlm['sucursales'];
    // Detectar almacenes sin sucursal
    $catAlmacenes = [];
    foreach ($sucursales as $alms) foreach ($alms as $a) $catAlmacenes[] = $a;
    $huerf = array_values(array_diff(array_keys($porAlm), $catAlmacenes));
    if ($huerf) $sucursales['(SIN SUCURSAL)'] = $huerf;

    // ── 5) Construir sheetData de POR ALMACEN ──────────────────────────────
    $sheetData    = '';
    $rowNum       = $CFG['first_data_row']; // 11
    $bloques      = []; // [almacen => ['ini'=>r14, 'fin'=>r20, 'total_row'=>22]]

    $hAlm  = $CFG['header_almacen'];        // {B:40,C:41}  o {C:27,D:28}
    $sLech = $CFG['lecheria'];
    $sTot  = $CFG['total_almacen'];
    $sObs  = $CFG['observaciones'];
    $colClave  = $CFG['col_clave'];
    $colTienda = $CFG['col_tienda'];
    $colLocal  = $CFG['col_local'];
    $colFam    = $CFG['col_fam'];
    $colBenef  = $CFG['col_benef'];
    $colDotTeo = $CFG['col_dot_teo'];
    $colPreq   = $CFG['col_p_req'];
    $colGuia   = $CFG['col_guia'];
    $colSumIni = $CFG['col_sum_inicio'];
    $colSumFin = $CFG['col_sum_fin'];

    // helpers XML
    $cN  = fn($ref, $sty, $val) => $val === null || $val === ''
        ? "<c r=\"$ref\" s=\"$sty\"/>"
        : "<c r=\"$ref\" s=\"$sty\"><v>".(is_int($val) ? $val : (string)$val)."</v></c>";
    $cF  = fn($ref, $sty, $form) =>
        "<c r=\"$ref\" s=\"$sty\"><f>".$form."</f></c>";
    $cS  = fn($ref, $sty, $txt) => "<c r=\"$ref\" s=\"$sty\" t=\"inlineStr\"><is><t>"
        .htmlspecialchars((string)$txt, ENT_XML1|ENT_QUOTES, 'UTF-8')."</t></is></c>";

    foreach ($sucursales as $sucNombre => $almacenes) {
        foreach ($almacenes as $almNombre) {
            $lechs = $porAlm[$almNombre] ?? [];
            // (5.1) Fila header del almacén (ALMACEN RURAL : XXX) ─ B (o C) inlineStr
            $row = "<row r=\"$rowNum\">";
            foreach ($hAlm as $col => $sty) {
                if ($col === array_key_first($hAlm)) {
                    $row .= $cS($col.$rowNum, $sty, "ALMACEN RURAL : $almNombre");
                } else {
                    $row .= "<c r=\"$col$rowNum\" s=\"$sty\"/>";
                }
            }
            $row .= "</row>";
            $sheetData .= $row;
            $rowNum++;
            // (5.2) Fila vacía separadora (2 filas en plantilla)
            $rowNum += 2;
            $iniLech = $rowNum;

            // (5.3) Filas de lecherías
            foreach ($lechs as $L) {
                $clave  = (int)$L['LECHER'];
                $tienda = trim((string)$L['NUM_TIENDA']);
                $local  = trim((string)$L['NOMBRELECH']);
                $fam    = (int)$L['FAM'];
                $benef  = (int)$L['BENEF'];
                $req    = $reqs[(string)$clave] ?? null;

                $row = "<row r=\"$rowNum\">";
                foreach ($sLech as $col => $sty) {
                    $ref = $col . $rowNum;
                    if ($col === $colClave) {
                        // Convertir LECHER (10 dígitos) → clave corta (6 dígitos): 2008810400 → 88104
                        $claveCorta = intdiv($clave, 100) % 1000000;
                        $row .= $cN($ref, $sty, $claveCorta);
                    } elseif ($col === $colTienda) {
                        $row .= $cN($ref, $sty, $tienda === '' ? null : (int)$tienda);
                    } elseif ($col === $colLocal) {
                        $row .= $cS($ref, $sty, $local);
                    } elseif ($col === $colFam) {
                        $row .= $cN($ref, $sty, $fam ?: null);
                    } elseif ($col === $colBenef) {
                        $row .= $cN($ref, $sty, $benef ?: null);
                    } elseif ($col === $colDotTeo) {
                        // Fórmula =E{n}*0.666*24/72  (col_benef)
                        $row .= $cF($ref, $sty, $colBenef.$rowNum."*0.666*24/72");
                    } elseif ($col === $colPreq) {
                        $row .= $cN($ref, $sty, $req);
                    } elseif ($col === $colGuia) {
                        $row .= $cF($ref, $sty, "+".$colPreq.$rowNum);
                    } else {
                        $row .= "<c r=\"$ref\" s=\"$sty\"><v>0</v></c>";
                    }
                }
                $row .= "</row>";
                $sheetData .= $row;
                $rowNum++;
            }
            $finLech = $rowNum - 1;
            $rowNum++; // fila vacía antes de TOTAL

            // (5.4) Fila TOTAL del almacén
            $totalRow = $rowNum;
            $row = "<row r=\"$totalRow\">";
            foreach ($sTot as $col => $sty) {
                $ref = $col . $totalRow;
                if ($col === $colClave) {
                    $row .= $cS($ref, $sty, "T O T A L");
                } elseif ($col === $colLocal) {
                    $row .= $cF($ref, $sty, "COUNTA($colClave$iniLech:$colClave$finLech)");
                } elseif (ord($col[0]) >= ord($colSumIni[0]) && ord($col[0]) <= ord($colSumFin[0])
                          && $col !== $colLocal && $col !== $colClave && $col !== $colTienda) {
                    $row .= $cF($ref, $sty, "SUM($col$iniLech:$col$finLech)");
                } else {
                    $row .= "<c r=\"$ref\" s=\"$sty\"/>";
                }
            }
            $row .= "</row>";
            $sheetData .= $row;
            $rowNum++;

            // (5.5) Fila OBSERVACIONES:
            $row = "<row r=\"$rowNum\">";
            foreach ($sObs as $col => $sty) {
                $ref = $col . $rowNum;
                if ($col === $colClave) {
                    $row .= $cS($ref, $sty, "OBSERVACIONES: ");
                } else {
                    $row .= "<c r=\"$ref\" s=\"$sty\"/>";
                }
            }
            $row .= "</row>";
            $sheetData .= $row;
            $rowNum++;

            // (5.6) Fila TOTAL A RECIBIR EN ALMACEN:
            $lblCol = $CFG['total_recibir_label_col'];
            $valCol = $CFG['total_recibir_value_col'];
            $row = "<row r=\"$rowNum\">";
            $row .= $cS($lblCol.$rowNum, $sTot[$lblCol] ?? 1, "TOTAL A RECIBIR EN ALMACEN:");
            $row .= $cF($valCol.$rowNum, $sTot[$valCol] ?? 1, $colGuia.$totalRow);
            $row .= "</row>";
            $sheetData .= $row;
            $rowNum += 3; // 2 filas en blanco antes del siguiente almacén

            $bloques[$almNombre] = [
                'sucursal'  => $sucNombre,
                'total_row' => $totalRow,
            ];
        }
    }

    // ── 6) Generar sheetData de TOTAL (sucursales) ─────────────────────────
    $totData = '';
    $tRow = 11;
    foreach ($sucursales as $sucNombre => $almacenes) {
        $sucRow = $tRow;
        // Encabezado: "SUCURSAL XXX" + J = SUM(Iini:Ifin)
        // Calcular rango J de almacenes
        $iniAlm = $sucRow + 2;
        $finAlm = $iniAlm + count($almacenes) - 1;
        $totData .= "<row r=\"$sucRow\">";
        $totData .= "<c r=\"A$sucRow\" s=\"1\" t=\"inlineStr\"><is><t>SUCURSAL ".htmlspecialchars($sucNombre, ENT_XML1, 'UTF-8')."</t></is></c>";
        $totData .= "<c r=\"J$sucRow\" s=\"1\"><f>SUM(I$iniAlm:I$finAlm)</f></c>";
        $totData .= "</row>";
        $tRow++; // 12 vacía
        $tRow++; // 13 inicia
        foreach ($almacenes as $almNombre) {
            $info = $bloques[$almNombre] ?? null;
            $bRow = $tRow;
            $totData .= "<row r=\"$bRow\">";
            $totData .= "<c r=\"B$bRow\" s=\"1\" t=\"inlineStr\"><is><t>  ".htmlspecialchars($almNombre, ENT_XML1, 'UTF-8')."</t></is></c>";
            if ($info) {
                $tr = $info['total_row'];
                $totData .= "<c r=\"C$bRow\" s=\"1\"><f>+'POR ALMACEN'!{$colLocal}{$tr}</f></c>";
                $totData .= "<c r=\"D$bRow\" s=\"1\"><f>+'POR ALMACEN'!{$colFam}{$tr}</f></c>";
                $totData .= "<c r=\"E$bRow\" s=\"1\"><f>+'POR ALMACEN'!{$colBenef}{$tr}</f></c>";
                $totData .= "<c r=\"F$bRow\" s=\"1\"><f>+'POR ALMACEN'!{$colDotTeo}{$tr}</f></c>";
                $totData .= "<c r=\"G$bRow\" s=\"1\"><v>0</v></c>";
                $totData .= "<c r=\"H$bRow\" s=\"1\"><f>+'POR ALMACEN'!{$colPreq}{$tr}</f></c>";
                $totData .= "<c r=\"I$bRow\" s=\"1\"><f>H$bRow-G$bRow</f></c>";
            }
            $totData .= "</row>";
            $tRow++;
        }
        $tRow++; // separador
    }

    // ── 7) Cargar plantilla y reemplazar sheetData ─────────────────────────
    $tmp = tempnam(sys_get_temp_dir(), 'req_').'.xlsx';
    if (!copy($plantilla, $tmp)) { http_response_code(500); exit('No se pudo copiar plantilla.'); }

    $zip = new ZipArchive();
    if ($zip->open($tmp) !== true) { @unlink($tmp); http_response_code(500); exit('No se pudo abrir plantilla.'); }

    // -- POR ALMACEN
    $sheetPathPA = $CFG['sheet_target'];
    $xmlPA = $zip->getFromName($sheetPathPA);
    // Reemplazar contenido entre <sheetData> y </sheetData>, preservando rows 1..10
    // Estrategia: extraer las rows 1..10 originales, descartar todo desde row 11
    if (preg_match('#<sheetData>(.*?)</sheetData>#s', $xmlPA, $mm)) {
        $sdContent = $mm[1];
        preg_match_all('#<row r="(\d+)"[^>]*>.*?</row>#s', $sdContent, $allRows, PREG_SET_ORDER);
        $keep = '';
        foreach ($allRows as $rr) {
            if ((int)$rr[1] < $CFG['first_data_row']) $keep .= $rr[0];
        }
        $newSheetData = '<sheetData>'.$keep.$sheetData.'</sheetData>';
        $xmlPA = preg_replace('#<sheetData>.*?</sheetData>#s', $newSheetData, $xmlPA, 1);
    }

    // Header: reemplazar la línea "REQUERIMIENTO ... MES DEL ANIO." en sharedStrings o inline
    // Como la plantilla usa sharedStrings, vamos a actualizar sharedStrings.xml
    $mesAnio = $NOMBRE_MES[$mes].' DEL '.$anio;
    $mesPad  = $NOMBRE_MES[$mes_pad].' '.$anio_pad;

    // Actualizar dimension
    $xmlPA = preg_replace('#<dimension[^/]+/>#', '<dimension ref="'.$colClave.'1:'.$colGuia.($rowNum).'"/>', $xmlPA, 1);
    // Borrar calcChain (Excel lo regenera)
    @$zip->deleteName('xl/calcChain.xml');

    $zip->deleteName($sheetPathPA);
    $zip->addFromString($sheetPathPA, $xmlPA);

    // -- TOTAL
    $sheetPathT = $CFG['sheet_total'];
    $xmlT = $zip->getFromName($sheetPathT);
    if (preg_match('#<sheetData>(.*?)</sheetData>#s', $xmlT, $mm)) {
        preg_match_all('#<row r="(\d+)"[^>]*>.*?</row>#s', $mm[1], $allRows, PREG_SET_ORDER);
        $keep = '';
        foreach ($allRows as $rr) {
            if ((int)$rr[1] < 11) $keep .= $rr[0];
        }
        $newSD = '<sheetData>'.$keep.$totData.'</sheetData>';
        $xmlT = preg_replace('#<sheetData>.*?</sheetData>#s', $newSD, $xmlT, 1);
    }
    $xmlT = preg_replace('#<dimension[^/]+/>#', '<dimension ref="A1:J'.($tRow).'"/>', $xmlT, 1);
    $zip->deleteName($sheetPathT);
    $zip->addFromString($sheetPathT, $xmlT);

    // -- sharedStrings: reemplazar fechas "ABRIL 2026" / "JUNIO DEL 2026"
    $ssPath = 'xl/sharedStrings.xml';
    $ssXml  = $zip->getFromName($ssPath);
    if ($ssXml !== false) {
        // Reemplazar mes destino (encabezado A4)
        $ssXml = preg_replace(
            '#(REQUERIMIENTO DE LECHE EN POLVO CORRESPONDIENTE AL MES DE )[A-ZÁÉÍÓÚÑ]+( DEL \d{4}\.?)#u',
            '$1'.$mesAnio.'.',
            $ssXml
        );
        // Reemplazar leyenda del padrón
        $ssXml = preg_replace(
            '#(PADRON DE BENEFICIARIOS\. AL CIERRE (?:DE|DEL MES DE) )[A-ZÁÉÍÓÚÑ]+( DEL? ?\d{4})#u',
            '$1'.$mesPad,
            $ssXml
        );
        $zip->deleteName($ssPath);
        $zip->addFromString($ssPath, $ssXml);
    }

    $zip->close();

    // ── 8) Enviar archivo ──────────────────────────────────────────────────
    $filename = sprintf('REQ_%02d_%d_PRECIO_%s.xlsx', $mes, $anio, $cfgKey === '650' ? '6.50' : '4.50');

    require_once __DIR__ . '/../includes/pdf_archivado.php';
    archivarArchivo($tmp, [
        'tipo'    => 'req_precio',
        'modulo'  => 'distribucion',
        'subdir'  => 'req_precio',
        'mes'     => $mes,
        'anio'    => $anio,
        'usuario' => $_SESSION['usuario'] ?? '',
        'nombre'  => $filename,
        'extras'  => ['precio' => $cfgKey === '650' ? '6.50' : '4.50'],
    ]);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Content-Length: '.filesize($tmp));
    header('Cache-Control: max-age=0');
    readfile($tmp);
    @unlink($tmp);

} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}
