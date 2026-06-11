<?php
// distribucion/descargar_minuta.php
// GET: mes, anio   (mes que se está conciliando, ej. mayo=5 / año 2026)
//
// Genera la minuta mensual a partir de plantillas/MINUTA_PLANTILLA.xlsm:
//  - Reemplaza el mes en sharedStrings (ABRIL→{mes}, MAYO→{mes+1})
//  - Repone los embarques (hoja Inventarios filas 18-27) desde inventarios_mensuales
//    agrupados por SURT_FACTURA del mes solicitado
//  - Actualiza J10 de la hoja Tiendas con COUNT(lecheria EN_OPERACION=0)
//  - Conserva macros (xl/vbaProject.bin), fórmulas y formato de la plantilla
//
// Los datos manuales (fechas programadas, leche solicitada por embarque, acuerdos)
// quedan en blanco para que el responsable los llene en Excel.

require_once __DIR__ . '/../includes/session_guard.php';
session_write_close();
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'distribucion') {
    http_response_code(403); exit('Acceso denegado.');
}
if (!class_exists('ZipArchive')) {
    http_response_code(500); exit('Falta extensión PHP zip.');
}

$mes  = isset($_GET['mes'])  ? (int)$_GET['mes']  : 0;
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : 0;
if ($mes < 1 || $mes > 12 || $anio < 2000) {
    http_response_code(400); exit('Parámetros inválidos.');
}

$plantilla = __DIR__ . '/plantillas/MINUTA_PLANTILLA.xlsm';
if (!is_file($plantilla)) {
    http_response_code(500); exit('Plantilla MINUTA_PLANTILLA.xlsm no encontrada.');
}

$NOMBRE_MES = ['','ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO',
               'JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];

$mesActual   = $NOMBRE_MES[$mes];
$mesSig      = $NOMBRE_MES[$mes < 12 ? $mes+1 : 1];
$anioSig     = $mes < 12 ? $anio : $anio+1;

// Catálogo de sucursal para mapear ALMACEN_RURAL → "OAXACA"/"HUAJUAPAN"/"IXTEPEC"
$catSucAlm = require __DIR__ . '/catalogos/sucursal_almacen.php';
$almToSucReceptor = [];
$mapaReceptor = [
    'HUAJUAPAN'   => 'HUAJUAPAN',
    'ISTMO-COSTA' => 'IXTEPEC',
    'V. CENTRAL'  => 'OAXACA',
];
foreach ($catSucAlm['sucursales'] as $sucName => $alms) {
    $rec = $mapaReceptor[$sucName] ?? $sucName;
    foreach ($alms as $a) $almToSucReceptor[$a] = $rec;
}
$alias = $catSucAlm['alias'];
$normAlm = function($s) use ($alias) {
    $s = strtoupper(trim((string)$s));
    $s = strtr($s, ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N']);
    return $alias[$s] ?? $s;
};

// Fecha → serial Excel (epoch 1899-12-30)
$dateToSerial = function($ymd) {
    if (!$ymd) return null;
    $ts = strtotime($ymd);
    if ($ts === false) return null;
    return (int) round(($ts - strtotime('1899-12-30')) / 86400);
};

try {
    $pdo = DatabaseSQLite::getInstance();

    // ── 1) Embarques del mes (agrupados por factura) ───────────────────────
    $st = $pdo->prepare("
        SELECT TRIM(ALMACEN) AS ALMACEN, SURT_FECHA, SURT_FACTURA,
               SUM(SURT_LITROS) AS LITROS
        FROM inventarios_mensuales
        WHERE MES_PERIODO = :m AND ANIO_PERIODO = :a
          AND SURT_FACTURA IS NOT NULL AND SURT_FACTURA <> ''
        GROUP BY ALMACEN, SURT_FECHA, SURT_FACTURA
        ORDER BY ALMACEN, SURT_FECHA
    ");
    $st->execute([':m' => $mes, ':a' => $anio]);
    $embarques = $st->fetchAll(PDO::FETCH_ASSOC);

    // Mapear al receptor (OAXACA/HUAJUAPAN/IXTEPEC)
    foreach ($embarques as &$e) {
        $alm = $normAlm($e['ALMACEN']);
        $e['RECEPTOR'] = $almToSucReceptor[$alm] ?? 'OAXACA';
    }
    unset($e);
    // Orden final: OAXACA, HUAJUAPAN, IXTEPEC, después por fecha
    $ordenReceptor = ['OAXACA' => 0, 'HUAJUAPAN' => 1, 'IXTEPEC' => 2];
    usort($embarques, function($a,$b) use ($ordenReceptor) {
        $ra = $ordenReceptor[$a['RECEPTOR']] ?? 9;
        $rb = $ordenReceptor[$b['RECEPTOR']] ?? 9;
        if ($ra !== $rb) return $ra <=> $rb;
        return strcmp($a['SURT_FECHA'] ?? '', $b['SURT_FECHA'] ?? '');
    });

    // ── 2) Conteo de puntos de venta operativos ────────────────────────────
    $totalPV = (int)$pdo->query("SELECT COUNT(*) c FROM lecheria WHERE COALESCE(EN_OPERACION,0)=0")
                        ->fetch()['c'];

    // ── 3) Copia plantilla a temporal ──────────────────────────────────────
    $tmp = tempnam(sys_get_temp_dir(), 'minuta_').'.xlsm';
    if (!copy($plantilla, $tmp)) {
        http_response_code(500); exit('No se pudo copiar la plantilla.');
    }
    $zip = new ZipArchive();
    if ($zip->open($tmp) !== true) {
        @unlink($tmp); http_response_code(500); exit('No se pudo abrir la plantilla.');
    }

    // ── 4) sharedStrings: reemplazar mes ABRIL/MAYO de la plantilla ────────
    $ssXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml !== false) {
        // plantilla referencia: ABRIL (mes conciliado) → $mesActual
        //                       MAYO  (mes siguiente)  → $mesSig
        // Solo reemplazar como palabra completa.
        $ssXml = preg_replace('/\bABRIL\b/u',  $mesActual, $ssXml);
        $ssXml = preg_replace('/\bMAYO\b/u',   $mesSig,    $ssXml);
        // Año: en la plantilla 2026 está en varias partes, lo dejamos si coincide
        if ($anio !== 2026) {
            $ssXml = preg_replace('/\b2026\b/', (string)$anio, $ssXml);
        }
        $zip->deleteName('xl/sharedStrings.xml');
        $zip->addFromString('xl/sharedStrings.xml', $ssXml);
    }

    // ── 5) Hoja "Inventarios" (sheet1): reemplazar filas 18-27 ─────────────
    $sheetPath = 'xl/worksheets/sheet1.xml';
    $sxml = $zip->getFromName($sheetPath);

    // Construir nuevas filas (máx 10, llenas)
    $newRows = '';
    $rowN = 18;
    $maxRows = 10; // filas 18..27 en plantilla
    $emb = array_slice($embarques, 0, $maxRows);
    foreach ($emb as $e) {
        $serie = $dateToSerial($e['SURT_FECHA']);
        $lts   = (int)$e['LITROS'];
        // SURT_FACTURA es string
        $fact  = htmlspecialchars((string)$e['SURT_FACTURA'], ENT_XML1|ENT_QUOTES, 'UTF-8');
        $receptor = htmlspecialchars($e['RECEPTOR'], ENT_XML1|ENT_QUOTES, 'UTF-8');

        // Mantener estilos de plantilla: A=s18, B=s165, C=s165, D=s202, E=s19, F=s186,
        // G=s20, H=s20, I=s21, J=s22
        $row  = '<row r="'.$rowN.'" spans="1:12" ht="15">';
        $row .= '<c r="A'.$rowN.'" s="18" t="inlineStr"><is><t>'.$receptor.'</t></is></c>';
        $row .= '<c r="B'.$rowN.'" s="165"/>';                              // fecha programada (manual)
        $row .= $serie !== null
              ? '<c r="C'.$rowN.'" s="165"><v>'.$serie.'</v></c>'
              : '<c r="C'.$rowN.'" s="165"/>';
        $row .= '<c r="D'.$rowN.'" s="202" t="inlineStr"><is><t>'.$fact.'</t></is></c>';
        $row .= '<c r="E'.$rowN.'" s="19"><f>IF((+C'.$rowN.'-B'.$rowN.')&lt;0,0,(+C'.$rowN.'-B'.$rowN.'))</f></c>';
        $row .= '<c r="F'.$rowN.'" s="186"/>';                              // litros solicitados (manual)
        $row .= '<c r="G'.$rowN.'" s="20"><v>'.$lts.'</v></c>';              // recibidos (en tiempo)
        $row .= '<c r="H'.$rowN.'" s="20"><f>IF(E'.$rowN.'&gt;0,F'.$rowN.',0)</f></c>';
        $row .= '<c r="I'.$rowN.'" s="21"><f>+G'.$rowN.'+H'.$rowN.'</f></c>';
        $row .= '<c r="J'.$rowN.'" s="22"><f>+F'.$rowN.'-I'.$rowN.'</f></c>';
        $row .= '</row>';
        $newRows .= $row;
        $rowN++;
    }
    // Filas vacías (estilos mínimos) si quedan menos de 10 embarques
    while ($rowN <= 27) {
        $row = '<row r="'.$rowN.'" spans="1:12" ht="15">';
        foreach (['A'=>18,'B'=>165,'C'=>165,'D'=>202,'E'=>19,'F'=>186,'G'=>20,'H'=>20,'I'=>21,'J'=>22] as $col=>$s) {
            $row .= '<c r="'.$col.$rowN.'" s="'.$s.'"/>';
        }
        $row .= '</row>';
        $newRows .= $row;
        $rowN++;
    }

    // Reemplazar filas 18..27 dentro de <sheetData>
    // Quitamos cada <row r="X" ...>...</row> de 18-27 y luego insertamos $newRows justo antes
    // de la fila 28 (TOTAL).
    $sxml = preg_replace('#<row r="(1[89]|2[0-7])"[^>]*>.*?</row>#s', '', $sxml, -1, $count);
    // Insertar las nuevas filas justo antes de la fila 28
    $sxml = preg_replace('#(<row r="28")#', $newRows.'$1', $sxml, 1);

    $zip->deleteName($sheetPath);
    $zip->addFromString($sheetPath, $sxml);

    // ── 6) Hoja "Tiendas" (sheet3): actualizar J10 (cierre actual) ─────────
    $sheetTiendas = 'xl/worksheets/sheet3.xml';
    $txml = $zip->getFromName($sheetTiendas);
    if ($txml !== false) {
        // J10 tiene <c r="J10" s="..."><v>632</v></c> — reemplazar valor
        $txml = preg_replace(
            '#(<c r="J10"[^>]*>)\s*(<f>[^<]*</f>)?\s*(<v>\d+</v>)?\s*(</c>)#',
            '$1<v>'.$totalPV.'</v>$4',
            $txml,
            1
        );
        $zip->deleteName($sheetTiendas);
        $zip->addFromString($sheetTiendas, $txml);
    }

    // ── 7) Borrar calcChain para que Excel recalcule ──────────────────────
    @$zip->deleteName('xl/calcChain.xml');

    $zip->close();

    // ── 8) Enviar archivo ──────────────────────────────────────────────────
    $filename = sprintf('%02d-MINUTA %s %d.xlsm', $mes, $mesActual, $anio);

    require_once __DIR__ . '/../includes/pdf_archivado.php';
    archivarArchivo($tmp, [
        'tipo'    => 'minuta',
        'modulo'  => 'distribucion',
        'subdir'  => 'minutas',
        'mes'     => $mes,
        'anio'    => $anio,
        'usuario' => $_SESSION['usuario'] ?? '',
        'nombre'  => $filename,
    ]);

    header('Content-Type: application/vnd.ms-excel.sheet.macroEnabled.12');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Content-Length: '.filesize($tmp));
    header('Cache-Control: max-age=0');
    readfile($tmp);
    @unlink($tmp);

} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
        'trace'   => $e->getTraceAsString(),
    ]);
}
