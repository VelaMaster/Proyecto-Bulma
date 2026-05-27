<?php
// distribucion/exportar_pdf.php
// GET: mes, anio, precio, supervisor_id (0=todos), almacen (vacío=todos)
// Genera PDF del requerimiento global usando FPDF
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/session_guard.php';
require_once __DIR__ . '/../fpdf/fpdf.php';

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
    ob_end_clean(); exit('Parámetros inválidos');
}

$precioNum = (float)str_replace(['$', ','], ['', '.'], $precio);
if (abs($precioNum - 4.50) < 0.001) {
    $precioNum = 4.50; $tipoVentaFiltro = [0];
} elseif (abs($precioNum - 6.50) < 0.001) {
    $precioNum = 6.50; $tipoVentaFiltro = [1, 2];
} else {
    ob_end_clean(); exit('Precio inválido');
}

function d($s) { return utf8_decode((string)($s ?? '')); }

$nombresMeses = ['','ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO',
                 'JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];
$mesNombre = $nombresMeses[$mes] ?? '';

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

    // Agrupar: supervisor → almacén → filas
    $supervisores = [];
    foreach ($rows as $r) {
        $tipo = (int)$r['TIPO_PUNTO_VENTA'];
        if (!in_array($tipo, $tipoVentaFiltro, true)) continue;

        $supId     = $r['ID_SUPERVISOR'] !== null ? (int)$r['ID_SUPERVISOR'] : 0;
        $supNombre = $r['SUPERVISOR_NOMBRE'] !== null
            ? trim((string)$r['SUPERVISOR_NOMBRE']) : '(Sin supervisor)';
        if ($supNombre === '') $supNombre = 'Supervisor #' . $supId;
        $supNombre = mb_convert_encoding($supNombre, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');

        if ($supIdFiltro && $supId !== $supIdFiltro) continue;

        $alm = strtoupper(trim((string)$r['ALMACEN'])) ?: '(SIN ALMACÉN)';
        $alm = mb_convert_encoding($alm, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');
        if ($almFiltro && $alm !== $almFiltro) continue;

        $numTiendaRaw = trim((string)$r['NUM_TIENDA']);
        $tienda = ($tipo === 2 || $numTiendaRaw === '10101') ? 'DM' : $numTiendaRaw;

        $k   = trim((string)$r['LECHER']);
        $req = isset($reqs[$k]) ? $reqs[$k] : null;

        if (!isset($supervisores[$supId])) {
            $supervisores[$supId] = ['nombre' => $supNombre, 'almacenes' => [], 'total' => 0];
        }
        if (!isset($supervisores[$supId]['almacenes'][$alm])) {
            $supervisores[$supId]['almacenes'][$alm] = ['filas' => [], 'subtotal' => 0];
        }
        $supervisores[$supId]['almacenes'][$alm]['filas'][] = [
            'pv'        => $k,
            'tienda'    => $tienda,
            'req'       => $req,
            'capturado' => $req !== null,
        ];
        if ($req !== null) {
            $supervisores[$supId]['almacenes'][$alm]['subtotal'] += $req;
            $supervisores[$supId]['total'] += $req;
        }
    }

    // Aplanar a lista de secciones para PDF
    $secciones = [];  // ['tipo' => sup_header|alm_header|data|subtotal|sup_total]
    $totalGeneral = 0;
    foreach ($supervisores as $sup) {
        $secciones[] = ['tipo' => 'sup_header', 'nombre' => $sup['nombre']];
        foreach ($sup['almacenes'] as $almNombre => $bloque) {
            $secciones[] = ['tipo' => 'alm_header', 'almacen' => $almNombre];
            foreach ($bloque['filas'] as $f) {
                $secciones[] = ['tipo' => 'data'] + $f;
            }
            $secciones[] = ['tipo' => 'subtotal', 'valor' => $bloque['subtotal']];
        }
        $secciones[] = ['tipo' => 'sup_total', 'nombre' => $sup['nombre'], 'valor' => $sup['total']];
        $secciones[] = ['tipo' => 'spacer'];
        $totalGeneral += $sup['total'];
    }

} catch (Exception $e) {
    ob_end_clean();
    exit('Error BD: ' . $e->getMessage());
}

// ─── PDF ───────────────────────────────────────────────────────────
$pdf = new FPDF('P', 'mm', 'Letter');
$pdf->SetAutoPageBreak(false);
$pdf->SetMargins(10, 10, 10);

$logoIzq = __DIR__ . '/../imagenes/Logos/logo_agricultura.png';
$logoDer = __DIR__ . '/../imagenes/Logos/Logo_lecheparaelbienestar.png';

$PAGE_W   = 215.9;
$MARGIN   = 10;
$AREA_W   = $PAGE_W - 2 * $MARGIN;
$COL_W    = $AREA_W / 2;
$GAP      = 4;
$TABLA_W  = $COL_W - $GAP / 2;
$W_PV     = $TABLA_W * 0.40;
$W_TIENDA = $TABLA_W * 0.28;
$W_REQ    = $TABLA_W - $W_PV - $W_TIENDA;
$ROW_H    = 5.0;
$Y_INICIO = 40;
$Y_TOPE   = 252;

$FILAS_POR_COL = (int)floor(($Y_TOPE - $Y_INICIO - 8) / $ROW_H);

// Calcular páginas
$columnas = [];
$buffer   = [];
foreach ($secciones as $s) {
    if ($s['tipo'] === 'alm_header' && count($buffer) >= $FILAS_POR_COL - 2) {
        while (count($buffer) < $FILAS_POR_COL) $buffer[] = ['tipo' => 'spacer'];
    }
    $buffer[] = $s;
    if (count($buffer) >= $FILAS_POR_COL) {
        $columnas[] = $buffer;
        $buffer = [];
    }
}
if (!empty($buffer)) $columnas[] = $buffer;
$totalPags = max(1, (int)ceil(count($columnas) / 2));

function encabezadoPDF($pdf, $logoIzq, $logoDer, $mesNombre, $anio, $precioNum, $pag, $total) {
    if (file_exists($logoIzq)) $pdf->Image($logoIzq, 10, 8, 30);
    if (file_exists($logoDer)) $pdf->Image($logoDer, 172, 8, 33);
    $pdf->SetY(8);
    $pdf->SetFont('Arial', '', 7.5);
    $pdf->Cell(0, 4, d("Página $pag de $total"), 0, 1, 'C');
    $pdf->SetY(14);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 5, d('LECHE PARA EL BIENESTAR, S.A. DE C.V.'), 0, 1, 'C');
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(0, 4, d('GERENCIA ESTATAL OAXACA — REQUERIMIENTO GLOBAL DE DOTACIÓN'), 0, 1, 'C');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(0, 4, d("Mes: $mesNombre $anio    Precio: \$" . number_format($precioNum, 2) . '/litro    Fecha: ' . date('d/m/Y')), 0, 1, 'C');
    $pdf->Ln(1);
}

function encabezadoColumna($pdf, $x, $y, $wPv, $wTienda, $wReq) {
    $pdf->SetXY($x, $y);
    $pdf->SetFont('Arial', 'B', 6.5);
    $pdf->SetFillColor(235, 235, 235);
    $pdf->MultiCell($wPv,     4, d("PUNTO DE\nVENTA"),       1, 'C', true);
    $pdf->SetXY($x + $wPv, $y);
    $pdf->MultiCell($wTienda, 4, d("TIENDA"),                1, 'C', true);
    $pdf->SetXY($x + $wPv + $wTienda, $y);
    $pdf->MultiCell($wReq,    4, d("REQ.\n(CAJAS)"),         1, 'C', true);
}

function dibujarFilaPDF($pdf, $s, $x, $y, $wPv, $wTienda, $wReq, $rowH) {
    $pdf->SetXY($x, $y);
    switch ($s['tipo']) {
        case 'sup_header':
            $pdf->SetFont('Arial', 'B', 7.5);
            $pdf->SetFillColor(180, 200, 230);
            $pdf->Cell($wPv + $wTienda + $wReq, $rowH,
                ' ' . d(strtoupper($s['nombre'])), 1, 0, 'L', true);
            break;
        case 'alm_header':
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->SetFillColor(220, 230, 245);
            $pdf->Cell($wPv + $wTienda + $wReq, $rowH,
                '  ' . d('ALMACÉN ' . $s['almacen']), 1, 0, 'L', true);
            break;
        case 'data':
            $pdf->SetFont('Arial', '', 7);
            $pdf->Cell($wPv,     $rowH, d($s['pv']),     1, 0, 'C');
            $pdf->Cell($wTienda, $rowH, d($s['tienda']), 1, 0, 'C');
            if (!$s['capturado']) {
                $pdf->SetTextColor(198, 40, 40);
                $pdf->SetFont('Arial', 'BI', 7);
                $pdf->Cell($wReq, $rowH, d('FALTA'), 1, 0, 'C');
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('Arial', '', 7);
            } else {
                $pdf->Cell($wReq, $rowH, d((string)$s['req']), 1, 0, 'C');
            }
            break;
        case 'subtotal':
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->SetFillColor(245, 245, 245);
            $pdf->Cell($wPv + $wTienda, $rowH, d('SUBTOTAL='), 1, 0, 'R', true);
            $pdf->Cell($wReq, $rowH, d((string)$s['valor']), 1, 0, 'C', true);
            break;
        case 'sup_total':
            $pdf->SetFont('Arial', 'B', 7.5);
            $pdf->SetFillColor(200, 215, 240);
            $pdf->Cell($wPv + $wTienda, $rowH,
                d('TOTAL ' . strtoupper($s['nombre']) . ' ='), 1, 0, 'R', true);
            $pdf->Cell($wReq, $rowH, d((string)$s['valor']), 1, 0, 'C', true);
            break;
        case 'spacer':
            // línea vacía
            break;
    }
}

$pagina = 0;
for ($i = 0; $i < count($columnas); $i += 2) {
    $pagina++;
    $pdf->AddPage();
    encabezadoPDF($pdf, $logoIzq, $logoDer, $mesNombre, $anio, $precioNum, $pagina, $totalPags);

    // Columna izquierda
    $xIzq = $MARGIN;
    encabezadoColumna($pdf, $xIzq, $Y_INICIO, $W_PV, $W_TIENDA, $W_REQ);
    $y = $Y_INICIO + 8;
    foreach ($columnas[$i] as $s) {
        dibujarFilaPDF($pdf, $s, $xIzq, $y, $W_PV, $W_TIENDA, $W_REQ, $ROW_H);
        $y += $ROW_H;
    }

    // Columna derecha (si existe)
    if (isset($columnas[$i + 1])) {
        $xDer = $MARGIN + $COL_W + $GAP / 2;
        encabezadoColumna($pdf, $xDer, $Y_INICIO, $W_PV, $W_TIENDA, $W_REQ);
        $y = $Y_INICIO + 8;
        foreach ($columnas[$i + 1] as $s) {
            dibujarFilaPDF($pdf, $s, $xDer, $y, $W_PV, $W_TIENDA, $W_REQ, $ROW_H);
            $y += $ROW_H;
        }
    }

    // Total general en última página
    if ($pagina === $totalPags) {
        $pdf->SetY(258);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(160, 5, d('TOTAL GENERAL:'), 0, 0, 'R');
        $pdf->Cell(25,  5, d((string)$totalGeneral), 'B', 1, 'C');
    }

    // Folio
    $pdf->SetY(-12);
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(0, 4, d('OA-IN-810-02-R03'), 0, 1, 'R');
}

$slug = preg_replace('/[^A-Za-z0-9]/', '_', $_SESSION['usuario'] ?? 'dist');
$nombreArchivo = sprintf('ReqGlobal_%04d_%02d_%s.pdf', $anio, $mes,
                         str_replace('.', '', number_format($precioNum, 2)));

if (ob_get_length()) ob_end_clean();
$pdf->Output('I', $nombreArchivo);
