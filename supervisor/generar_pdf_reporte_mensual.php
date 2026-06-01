<?php
// ───────────────────────────────────────────────────────────────────────────
//  PDF "Reporte Mensual de Inventario — Supervisor"
//  Recibe POST con campo 'datos' (JSON) generado desde reporte_mensual.php
//
//  datos: {
//    mes, anio, supervisor,
//    filtro_almacen, filtro_promotor, filtro_tipo,
//    almacenes: [{almacen, lecherias:[{...}], capturadas, total}]
//  }
// ───────────────────────────────────────────────────────────────────────────
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/session_guard.php';
require_once __DIR__ . '/../fpdf/fpdf.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    http_response_code(401);
    exit('No autorizado.');
}

$json  = $_POST['datos'] ?? '';
$datos = json_decode($json, true);

if (!is_array($datos) || empty($datos['almacenes'])) {
    http_response_code(400);
    exit('Sin datos para generar el PDF.');
}

function d($s) {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', (string)($s ?? ''));
}

$nombresMeses = ['','ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO',
                    'JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];

$mes        = (int)($datos['mes']  ?? 0);
$anio       = (int)($datos['anio'] ?? 0);
$supervisor = trim((string)($datos['supervisor'] ?? ($_SESSION['nombre'] ?? '')));
$fAlm       = trim((string)($datos['filtro_almacen']  ?? 'Todos'));
$fProm      = trim((string)($datos['filtro_promotor'] ?? 'Todos'));
$fTipo      = trim((string)($datos['filtro_tipo']     ?? 'Todos'));
$mesNombre  = $nombresMeses[$mes] ?? '';

// ─── Clase PDF ─────────────────────────────────────────────────────────────
class PDFReporteMensual extends FPDF
{
    public $supervisor = '';
    public $mesAnio    = '';
    public $filtros    = '';

    function Header()
    {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(30, 100, 60);   // verde Liconsa oscuro
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 8, d('LICONSA — REPORTE MENSUAL DE INVENTARIO'), 0, 1, 'C', true);

        $this->SetFont('Arial', '', 7);
        $this->SetFillColor(220, 237, 222);
        $this->SetTextColor(0);
        $this->Cell(0, 5,
            d('Supervisor: ' . $this->supervisor . '   |   Período: ' . $this->mesAnio .
              '   |   Filtros: ' . $this->filtros),
            0, 1, 'C', true);
        $this->Ln(2);
    }

    function Footer()
    {
        $this->SetY(-10);
        $this->SetFont('Arial', 'I', 6);
        $this->SetTextColor(130);
        $this->Cell(0, 5, 'Pag. ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
    }
}

// ─── Instancia ──────────────────────────────────────────────────────────────
$pdf = new PDFReporteMensual('L', 'mm', 'Letter');
$pdf->AliasNbPages();
$pdf->supervisor = $supervisor;
$pdf->mesAnio    = $mesNombre . ' ' . $anio;
$pdf->filtros    = "Almacén: $fAlm | Promotor: $fProm | Tipo: $fTipo";
$pdf->SetMargins(6, 14, 6);
$pdf->SetAutoPageBreak(true, 12);
$pdf->AddPage();

// ─── Columnas de la tabla ───────────────────────────────────────────────────
$cols = [
    ['hdr' => 'Punto Venta',   'w' => 20, 'align' => 'C'],
    ['hdr' => 'Tienda',        'w' => 16, 'align' => 'C'],
    ['hdr' => 'Precio',        'w' => 14, 'align' => 'C'],
    ['hdr' => 'Promotor',      'w' => 38, 'align' => 'L'],
    ['hdr' => 'Inv.Ini Cajas', 'w' => 18, 'align' => 'R'],
    ['hdr' => 'Dot.Recib.',    'w' => 18, 'align' => 'R'],
    ['hdr' => 'Total Cajas',   'w' => 18, 'align' => 'R'],
    ['hdr' => 'Vend.Cajas',    'w' => 16, 'align' => 'R'],
    ['hdr' => 'Vend.Sobres',   'w' => 16, 'align' => 'R'],
    ['hdr' => 'Inv.Fin Cajas', 'w' => 18, 'align' => 'R'],
    ['hdr' => 'Inv.Fin Sobres','w' => 18, 'align' => 'R'],
    ['hdr' => 'Retiro Caj.',   'w' => 14, 'align' => 'R'],
    ['hdr' => 'Fam.No Acud.',  'w' => 14, 'align' => 'R'],
    ['hdr' => 'Observaciones', 'w' => 36, 'align' => 'L'],
];

function drawTableHeader($pdf, $cols)
{
    $pdf->SetFont('Arial', 'B', 5.5);
    $pdf->SetFillColor(50, 120, 80);
    $pdf->SetTextColor(255);
    foreach ($cols as $c) {
        $pdf->Cell($c['w'], 6, d($c['hdr']), 1, 0, 'C', true);
    }
    $pdf->Ln();
    $pdf->SetTextColor(0);
}

// ─── Totales globales ─────────────────────────────────────────────────────
$totGlobal = array_fill_keys(
    ['inv_ini_cajas','dot_recib_cajas','total_cajas','vend_cajas','vend_sobres',
     'inv_fin_cajas','inv_fin_sobres','retiro_cajas','familias_no_acud'], 0);
$totLech     = 0;
$totCapt     = 0;

// ─── Renderizado por almacén ──────────────────────────────────────────────
foreach ($datos['almacenes'] as $bloque) {
    $alm       = strtoupper(trim((string)($bloque['almacen']    ?? '')));
    $capt      = (int)($bloque['capturadas'] ?? 0);
    $total     = (int)($bloque['total']      ?? 0);
    $lecherias = $bloque['lecherias'] ?? [];

    // Cabecera de almacén
    if ($pdf->GetY() > 170) $pdf->AddPage();

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetFillColor(180, 220, 195);
    $pdf->SetTextColor(0, 70, 30);
    $pdf->Cell(0, 6,
        d("  ALMACÉN: $alm   —   $capt / $total capturadas"),
        0, 1, 'L', true);
    $pdf->SetTextColor(0);

    drawTableHeader($pdf, $cols);

    // Totales por almacén
    $totAlm = array_fill_keys(array_keys($totGlobal), 0);

    $row = 0;
    foreach ($lecherias as $l) {
        if ($pdf->GetY() > 186) {
            $pdf->AddPage();
            drawTableHeader($pdf, $cols);
        }

        $fill = ($row % 2 === 0);
        $pdf->SetFillColor(245, 250, 247);
        $pdf->SetFont('Arial', '', 5.5);

        $capturado = !empty($l['capturado']);

        $tipo    = (int)($l['tipo_punto_venta'] ?? -1);
        $tienda  = (string)($l['num_tienda'] ?? '');
        $precio  = (string)($l['precio']     ?? '');

        if ($capturado) {
            $n = function($v) { return $v !== null ? number_format((int)$v, 0, '.', ',') : '—'; };

            $vals = [
                $l['punto_venta']      ?? '',
                $tienda,
                $precio,
                $l['promotor_nombre']  ?? ($l['promotor'] ?? ''),
                $n($l['inv_ini_cajas']),
                $n($l['dot_recib_cajas']),
                $n($l['total_cajas']),
                $n($l['vend_cajas']),
                $n($l['vend_sobres']),
                $n($l['inv_fin_cajas']),
                $n($l['inv_fin_sobres']),
                $n($l['retiro_cajas']),
                $n($l['familias_no_acud']),
                substr((string)($l['observaciones'] ?? ''), 0, 50),
            ];

            // Acumular totales
            foreach (['inv_ini_cajas','dot_recib_cajas','total_cajas','vend_cajas',
                      'vend_sobres','inv_fin_cajas','inv_fin_sobres','retiro_cajas',
                      'familias_no_acud'] as $campo) {
                $v = (int)($l[$campo] ?? 0);
                $totAlm[$campo]    += $v;
                $totGlobal[$campo] += $v;
            }
        } else {
            $vals = [
                $l['punto_venta'] ?? '',
                $tienda,
                $precio,
                $l['promotor_nombre'] ?? ($l['promotor'] ?? ''),
                'FALTA','','','','','','','','','',
            ];
            $pdf->SetTextColor(180, 0, 0);
        }

        foreach ($cols as $i => $c) {
            $pdf->Cell($c['w'], 5, d($vals[$i]), 'B', 0, $c['align'], $fill);
        }
        $pdf->Ln();
        $pdf->SetTextColor(0);
        $row++;
        $totLech++;
        if ($capturado) $totCapt++;
    }

    // Fila subtotal almacén
    $pdf->SetFont('Arial', 'B', 5.5);
    $pdf->SetFillColor(200, 230, 210);
    $n = function($v) { return number_format($v, 0, '.', ','); };
    $subtotalVals = [
        'SUBTOTAL', '', '', '',
        $n($totAlm['inv_ini_cajas']),
        $n($totAlm['dot_recib_cajas']),
        $n($totAlm['total_cajas']),
        $n($totAlm['vend_cajas']),
        $n($totAlm['vend_sobres']),
        $n($totAlm['inv_fin_cajas']),
        $n($totAlm['inv_fin_sobres']),
        $n($totAlm['retiro_cajas']),
        $n($totAlm['familias_no_acud']),
        '',
    ];
    foreach ($cols as $i => $c) {
        $pdf->Cell($c['w'], 5, d($subtotalVals[$i]), 1, 0,
            in_array($i, [4,5,6,7,8,9,10,11,12]) ? 'R' : 'L', true);
    }
    $pdf->Ln();
    $pdf->Ln(3);
}

// ─── Fila TOTAL GENERAL ───────────────────────────────────────────────────
if ($pdf->GetY() > 182) $pdf->AddPage();

$n = function($v) { return number_format($v, 0, '.', ','); };
$pdf->SetFont('Arial', 'B', 6);
$pdf->SetFillColor(30, 100, 60);
$pdf->SetTextColor(255);
$totalVals = [
    'TOTAL GENERAL', '', '', '',
    $n($totGlobal['inv_ini_cajas']),
    $n($totGlobal['dot_recib_cajas']),
    $n($totGlobal['total_cajas']),
    $n($totGlobal['vend_cajas']),
    $n($totGlobal['vend_sobres']),
    $n($totGlobal['inv_fin_cajas']),
    $n($totGlobal['inv_fin_sobres']),
    $n($totGlobal['retiro_cajas']),
    $n($totGlobal['familias_no_acud']),
    "Lech: $totCapt/$totLech capt.",
];
foreach ($cols as $i => $c) {
    $pdf->Cell($c['w'], 6, d($totalVals[$i]), 1, 0,
        in_array($i, [4,5,6,7,8,9,10,11,12]) ? 'R' : 'L', true);
}
$pdf->Ln();

// ─── Salida ───────────────────────────────────────────────────────────────
ob_end_clean();
$pdf->Output('I', 'reporte_mensual_' . $mesNombre . '_' . $anio . '.pdf');
