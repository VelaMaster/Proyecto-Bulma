<?php
// ────────────────────────────────────────────────────────────────────
//  Genera el PDF del Reporte Mensual de la Operación en Lecherías
//  (formato OA-IN-810-02-R04).
//  Recibe payload multi-almacén:
//    { mes_reporte, anio_reporte, periodo_inicio, periodo_fin,
//      promotor, supervisor,
//      almacenes: [ { almacen, lecherias: [...] }, ... ] }
//  Compatible con payload viejo (almacen + lecherias[]).
// ────────────────────────────────────────────────────────────────────
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require_once('../fpdf/fpdf.php');

$json  = file_get_contents('php://input');
$datos = json_decode($json, true);

if (!is_array($datos)) {
    http_response_code(400);
    exit('No se recibieron datos del reporte.');
}

// Normalizar a almacenes[]
$almacenes = $datos['almacenes'] ?? null;
if (!$almacenes && !empty($datos['lecherias'])) {
    $almacenes = [['almacen' => $datos['almacen'] ?? '', 'lecherias' => $datos['lecherias']]];
}
if (empty($almacenes)) {
    http_response_code(400);
    exit('No hay almacenes con lecherías en el payload.');
}

function d($s) { return utf8_decode((string)($s ?? '')); }
function fmtFecha($iso) {
    if (!$iso) return '';
    $t = strtotime($iso);
    return $t ? date('d/m/y', $t) : $iso;
}
function fechaLarga($iso) {
    static $meses = ['','ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];
    $t = strtotime($iso); if (!$t) return $iso;
    return date('d', $t) . ' DE ' . $meses[(int)date('n',$t)] . ' DEL ' . date('Y',$t);
}

$nombresMeses = ["", "ENERO","FEBRERO","MARZO","ABRIL","MAYO","JUNIO",
                     "JULIO","AGOSTO","SEPTIEMBRE","OCTUBRE","NOVIEMBRE","DICIEMBRE"];

$mes  = (int)($datos['mes_reporte']  ?? 0);
$anio = (int)($datos['anio_reporte'] ?? 0);
$mesNombre = $nombresMeses[$mes] ?? '';

$periodoInicio = $datos['periodo_inicio'] ?? '';
$periodoFin    = $datos['periodo_fin']    ?? '';

$promotor   = $datos['promotor']   ?? ($_SESSION['nombre'] ?? $_SESSION['usuario'] ?? '');
$supervisor = $datos['supervisor'] ?? '';

// ─────────────────────────────────────────────────────────────
$pdf = new FPDF('L', 'mm', 'Letter');
$pdf->SetAutoPageBreak(false);
$pdf->SetMargins(8, 8, 8);

// Logos
$logoIzq = __DIR__ . '/../imagenes/Logos/logo_agricultura.png';
$logoDer = __DIR__ . '/../imagenes/Logos/Logo_lecheparaelbienestar.png';

// Configuración de columnas
$cols = [
    ['NUMERO DE PUNTO DE VENTA',      18],
    ['CLAVE TIENDA',                  12],
    ['PRECIO',                        13],
    ['INV. INI. CAJAS',               12],
    ['INV. INI. SOB',                 10],
    ['DOTACION RECIB. CAJAS',         15],
    ['TOTAL CAJAS',                   12],
    ['TOTAL SOB',                     10],
    ['VEND. CAJAS',                   12],
    ['VEND. SOB',                     10],
    ['INV. FIN. CAJAS',               12],
    ['INV. FIN. SOB',                 10],
    ['RETIRO CAJAS',                  12],
    ['RETIRO SOB',                    10],
    ['FAM. NO ACUD.',                 13],
    ['SOB. ROTOS',                    10],
    ['SOB. FALT.',                    10],
    ['DIAS VENTA',                    11],
    ['FECHA ENTRADA',                 17],
    ['CADUCIDAD',                     16],
    ['OBSERVACIONES',                 25],
];

function pintarPaginaAlmacen($pdf, $almacenNombre, $lecherias, $cols, $logoIzq, $logoDer, $periodoInicio, $periodoFin) {
    $pdf->AddPage();

    if (file_exists($logoIzq)) $pdf->Image($logoIzq, 8,  6, 45);
    if (file_exists($logoDer)) $pdf->Image($logoDer, 225, 6, 40);

    $pdf->SetY(8);
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->Cell(0, 6, d('LECHE PARA EL BIENESTAR, S.A. DE C.V.'), 0, 1, 'C');
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 5, d('GERENCIA ESTATAL OAXACA'), 0, 1, 'C');
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 6, d("REPORTE MENSUAL DE LA OPERACION EN LECHERIAS"), 0, 1, 'C');
    $pdf->Ln(1);

    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(0, 4, d("ALMACEN ALIMENTACION PARA EL BIENESTAR: ") . d(strtoupper($almacenNombre)), 0, 1, 'L');
    $periodoTxt = sprintf('REPORTE CORRESPONDIENTE AL PERIODO DEL %s AL %s.',
        $periodoInicio ? fechaLarga($periodoInicio) : '',
        $periodoFin    ? fechaLarga($periodoFin)    : ''
    );
    $pdf->Cell(0, 4, d($periodoTxt), 0, 1, 'L');
    $pdf->Ln(1);

    // Encabezado de la tabla
    $pdf->SetFont('Arial', 'B', 6);
    $pdf->SetFillColor(220, 220, 220);
    foreach ($cols as $c) {
        $pdf->Cell($c[1], 9, d($c[0]), 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Filas
    $pdf->SetFont('Arial', '', 7);
    foreach ($lecherias as $l) {
        $vals = [
            $l['punto_venta']        ?? '',
            $l['clave_tienda']       ?? '',
            $l['precio']             ?? '',
            $l['inv_ini_cajas']      ?? '',
            $l['inv_ini_sobres']     ?? '',
            $l['dot_recibida_cajas'] ?? '',
            $l['total_cajas']        ?? '',
            $l['total_sobres']       ?? '',
            $l['dot_vend_cajas']     ?? '',
            $l['dot_vend_sobres']    ?? '',
            $l['inv_fin_cajas']      ?? '',
            $l['inv_fin_sobres']     ?? '',
            $l['retiro_cajas']       ?? '',
            $l['retiro_sobres']      ?? '',
            $l['familias_no_acud']   ?? '',
            $l['sobres_rotos']       ?? '',
            $l['sobres_falt']        ?? '',
            $l['dias_venta']         ?? '',
            fmtFecha($l['fecha_entrada'] ?? ''),
            fmtFecha($l['caducidad']     ?? ''),
            $l['observaciones']      ?? '',
        ];
        foreach ($cols as $i => $c) {
            $pdf->Cell($c[1], 6, d((string)$vals[$i]), 1, 0, 'C');
        }
        $pdf->Ln();
    }

    // Filas vacías para llegar a 17
    $faltan = max(0, 17 - count($lecherias));
    for ($i = 0; $i < $faltan; $i++) {
        foreach ($cols as $c) $pdf->Cell($c[1], 6, '', 1, 0);
        $pdf->Ln();
    }

    $pdf->Ln(2);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(0, 4, d('NOTA: LOS DATOS QUE APARECEN EN ESTE FORMATO SON FIDEDIGNOS, DE LOS CUALES SE HACEN RESPONSABLES LOS FIRMANTES.'), 0, 1, 'L');

    // Folio
    $pdf->SetY(-10);
    $pdf->SetFont('Arial', '', 6);
    $pdf->Cell(0, 4, d('OA-IN-810-02-R04'), 0, 1, 'R');
}

// Pintamos una hoja por almacén
foreach ($almacenes as $bloque) {
    $almacenNombre = $bloque['almacen']   ?? '';
    $lecherias     = $bloque['lecherias'] ?? [];
    pintarPaginaAlmacen($pdf, $almacenNombre, $lecherias, $cols, $logoIzq, $logoDer, $periodoInicio, $periodoFin);

    // Firmas (igual en cada hoja)
    $pdf->SetY(-30);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(50, 5, d('FECHAS:'), 0, 0);
    $pdf->Cell(40, 5, d('DD'), 0, 0, 'C');
    $pdf->Cell(20, 5, d('MM'), 0, 0, 'C');
    $pdf->Cell(20, 5, d('AA'), 0, 1, 'C');

    $dia = date('d');
    $mesNum = str_pad($mes, 2, '0', STR_PAD_LEFT);
    $anioStr = (string)$anio;

    $pdf->Cell(50, 5, d('DE ELABORACION:'), 0, 0);
    $pdf->Cell(40, 5, $dia, 'B', 0, 'C');
    $pdf->Cell(20, 5, $mesNum, 'B', 0, 'C');
    $pdf->Cell(20, 5, $anioStr, 'B', 1, 'C');
    $pdf->Cell(50, 5, d('DE RECEPCION:'), 0, 0);
    $pdf->Cell(40, 5, $dia, 'B', 0, 'C');
    $pdf->Cell(20, 5, $mesNum, 'B', 0, 'C');
    $pdf->Cell(20, 5, $anioStr, 'B', 1, 'C');

    $pdf->SetY(-22);
    $pdf->SetX(150);
    $pdf->Cell(60, 5, d(strtoupper($promotor)),   'B', 0, 'C');
    $pdf->Cell(20, 5, '', 0, 0);
    $pdf->Cell(60, 5, d(strtoupper($supervisor)), 'B', 1, 'C');

    $pdf->SetX(150);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell(60, 4, d('NOMBRE Y FIRMA'), 0, 0, 'C');
    $pdf->Cell(20, 4, '', 0, 0);
    $pdf->Cell(60, 4, d('NOMBRE Y FIRMA'), 0, 1, 'C');

    $pdf->SetX(150);
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(60, 4, d('PROMOTOR SOCIAL'), 0, 0, 'C');
    $pdf->Cell(20, 4, '', 0, 0);
    $pdf->Cell(60, 4, d('SUPERVISOR SOCIAL'), 0, 1, 'C');
}

// ─── Salida ───
$slugUsr = preg_replace('/[^A-Za-z0-9]/', '_', $_SESSION['usuario'] ?? 'promotor');
$nombreArchivo = sprintf('Reporte_%04d_%02d_%s.pdf', $anio, $mes, $slugUsr);

$baseDir      = __DIR__ . '/../datos/promotores/reportes_pdf';
if (!is_dir($baseDir)) @mkdir($baseDir, 0775, true);
$rutaCompleta = $baseDir . '/' . $nombreArchivo;

if (ob_get_length()) ob_end_clean();
$pdf->Output('F', $rutaCompleta);
$pdf->Output('I', $nombreArchivo);
