<?php
// supervisor/generar_pdf_inventario_almacen.php
// PDF: "Inventario de Leche en Polvo en Almacenes de Alimentación para el Bienestar"
// 3 hojas: R05 + R07 ($4.50) + R07 ($6.50)
// Recibe POST 'datos' (JSON) generado desde inventario_almacen.php
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
if (!is_array($datos)) { http_response_code(400); exit('Sin datos.'); }

function d($s){ return iconv('UTF-8','ISO-8859-1//TRANSLIT//IGNORE',(string)($s ?? '')); }

$meses = ['','ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO',
              'JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];

$almacen   = trim((string)($datos['almacen'] ?? ''));
$encargado = trim((string)($datos['encargado'] ?? ''));
$noAlm     = trim((string)($datos['no_almacen'] ?? ''));
$fecha     = trim((string)($datos['fecha'] ?? date('Y-m-d')));
$mes       = (int)($datos['mes'] ?? 0);
$anio      = (int)($datos['anio'] ?? 0);
$mesNombre = $meses[$mes] ?? '';
$supervisor= trim((string)($datos['supervisor']['nombre'] ?? ''));
$obs       = trim((string)($datos['observaciones'] ?? ''));

$r05 = $datos['r05'] ?? null;
$r4  = $datos['r07_450'] ?? null;
$r6  = $datos['r07_650'] ?? null;

class PDFInvAlmacen extends FPDF
{
    public $titulo = '';
    public $subtitulo = '';
    function Header(){
        // Franja título superior
        $this->SetFont('Arial','B',11);
        $this->SetTextColor(0);
        $this->Cell(0,6, d('LECHE PARA EL BIENESTAR, S.A. DE C.V.'), 0, 1, 'C');
        $this->SetFont('Arial','',9);
        $this->Cell(0,5, d('GERENCIA ESTATAL OAXACA'), 0, 1, 'C');
        $this->SetFont('Arial','B',9);
        $this->Cell(0,5, d($this->titulo), 0, 1, 'C');
        if ($this->subtitulo !== '') {
            $this->SetFont('Arial','',8);
            $this->Cell(0,4, d($this->subtitulo), 0, 1, 'C');
        }
        $this->Ln(2);
    }
    function Footer(){
        $this->SetY(-10);
        $this->SetFont('Arial','I',6);
        $this->SetTextColor(120);
        $this->Cell(0,5, 'Pag. '.$this->PageNo().'/{nb}',0,0,'R');
        $this->SetTextColor(0);
    }
}

$pdf = new PDFInvAlmacen('P', 'mm', 'Letter');
$pdf->AliasNbPages();
$pdf->SetMargins(10, 14, 10);
$pdf->SetAutoPageBreak(true, 14);

// ─── HOJA 1: R05 ───────────────────────────────────────────────────────────
$pdf->titulo    = 'INVENTARIO DE LECHE EN POLVO EN ALMACENES DE ALIMENTACIÓN PARA EL BIENESTAR';
$pdf->subtitulo = '';
$pdf->AddPage();

$pdf->SetFont('Arial','',8);
$pdf->Cell(195,5, d("NOMBRE DEL ALMACÉN: $almacen"), 0, 1);
$pdf->Cell(120,5, d("NOMBRE DEL ENCARGADO DEL ALMACÉN: $encargado"), 0, 0);
$pdf->Cell(75,5,  d("No. DEL ALMACÉN: $noAlm"), 0, 1);
$pdf->Cell(0,5,   d("FECHA DEL INVENTARIO: $fecha"), 0, 1, 'R');
$pdf->Ln(1);

// Tabla doble: PROBREZA EXTREMA | I.N.I.
$pdf->SetFont('Arial','B',7);
$pdf->SetFillColor(220,220,220);
$pdf->Cell(97,5, d('PROGRAMA DE POBREZA EXTREMA'), 1, 0, 'C', true);
$pdf->Cell(98,5, d('PROGRAMA I.N.I.'), 1, 1, 'C', true);

$pdf->SetFont('Arial','B',6.5);
$cellW = 16.16; // 97/6 aprox para 6 columnas (cajas/sobres x 3 grupos)
$grp = ['EXIST. BUEN ESTADO','EXIST. MAL ESTADO','TOTAL'];
foreach ([0,1] as $prog) {
    foreach ($grp as $g) {
        $pdf->Cell($cellW*2,5, d($g), 1, 0, 'C', true);
    }
}
$pdf->Ln();
foreach ([0,1] as $prog) {
    foreach ($grp as $g) {
        $pdf->Cell($cellW,4, 'CAJAS', 1, 0, 'C');
        $pdf->Cell($cellW,4, 'SOBRES', 1, 0, 'C');
    }
}
$pdf->Ln();

$pdf->SetFont('Arial','',7);
$pe = $r05['pe']; $ini = $r05['ini'];
$totPE_c = $pe['buen_cajas'] + $pe['mal_cajas'];
$totPE_s = $pe['buen_sobres'] + $pe['mal_sobres'];
$totIN_c = $ini['buen_cajas'] + $ini['mal_cajas'];
$totIN_s = $ini['buen_sobres'] + $ini['mal_sobres'];

$vals = [
    $pe['buen_cajas'], $pe['buen_sobres'],
    $pe['mal_cajas'],  $pe['mal_sobres'],
    $totPE_c, $totPE_s,
    $ini['buen_cajas'], $ini['buen_sobres'],
    $ini['mal_cajas'],  $ini['mal_sobres'],
    $totIN_c, $totIN_s,
];
foreach ($vals as $v) {
    $pdf->Cell($cellW,5, (string)$v, 1, 0, 'C');
}
$pdf->Ln(7);

// LECHERIAS A LAS QUE CORRESPONDE LA LECHE EN EXISTENCIA (P.P.E)
$pdf->SetFont('Arial','B',7.5);
$pdf->SetFillColor(220,220,220);
$pdf->Cell(0,5, d('LECHERÍAS A LAS QUE CORRESPONDE LA LECHE EN EXISTENCIA (P.P.E)'), 1, 1, 'C', true);

$pdf->SetFont('Arial','B',6.5);
$pdf->Cell(30,5, d('No. PUNTO DE VENTA'), 1, 0, 'C');
$pdf->Cell(20,5, 'No. TIENDA', 1, 0, 'C');
$pdf->Cell(18,5, 'CAJAS', 1, 0, 'C');
$pdf->Cell(18,5, 'SOBRES', 1, 0, 'C');
$pdf->Cell(30,5, d('MES AL QUE CORRESPONDE'), 1, 0, 'C');
$pdf->Cell(79,5, d('BIMESTRE I.N.I. / CAJAS'), 1, 1, 'C');

$pdf->SetFont('Arial','',7);
$lechs = $r05['lecherias_pe'] ?? [];
if (!$lechs) {
    for ($i=0; $i<3; $i++) {
        $pdf->Cell(30,5,'',1,0); $pdf->Cell(20,5,'',1,0);
        $pdf->Cell(18,5,'',1,0); $pdf->Cell(18,5,'',1,0);
        $pdf->Cell(30,5,'',1,0); $pdf->Cell(79,5,'',1,1);
    }
} else {
    foreach ($lechs as $l) {
        $pdf->Cell(30,5, d($l['punto_venta']), 1, 0, 'C');
        $pdf->Cell(20,5, d($l['num_tienda']),  1, 0, 'C');
        $pdf->Cell(18,5, (string)$l['cajas'],  1, 0, 'C');
        $pdf->Cell(18,5, (string)$l['sobres'], 1, 0, 'C');
        $pdf->Cell(30,5, d($l['mes_corresponde']), 1, 0, 'C');
        $pdf->Cell(79,5, '—', 1, 1, 'C');
    }
}

$pdf->Ln(3);
$pdf->SetFont('Arial','B',7.5);
$pdf->Cell(0,5, d('OBSERVACIONES GENERALES'), 1, 1, 'L', true);
$pdf->SetFont('Arial','',8);
$pdf->MultiCell(0, 4.5, d($obs !== '' ? $obs : '—'), 1, 'L');

$pdf->Ln(10);
// Firmas
$pdf->SetFont('Arial','',7);
$pdf->Cell(95,5, '________________________________________', 0, 0, 'C');
$pdf->Cell(10,5, '', 0, 0);
$pdf->Cell(90,5, '________________________________________', 0, 1, 'C');
$pdf->SetFont('Arial','B',7);
$pdf->Cell(95,4, d($supervisor !== '' ? $supervisor : 'NOMBRE Y FIRMA'), 0, 0, 'C');
$pdf->Cell(10,4, '', 0, 0);
$pdf->Cell(90,4, d($encargado !== '' ? $encargado : 'NOMBRE Y FIRMA'), 0, 1, 'C');
$pdf->SetFont('Arial','',6.5);
$pdf->Cell(95,3.5, d('SUPERVISOR O PROMOTOR'), 0, 0, 'C');
$pdf->Cell(10,3.5, '', 0, 0);
$pdf->Cell(90,3.5, d('JEFE O ENCARGADO DEL ALMACÉN'), 0, 1, 'C');
$pdf->Cell(95,3.5, d('LECHE PARA EL BIENESTAR, S.A. DE C.V.'), 0, 0, 'C');
$pdf->Cell(10,3.5, '', 0, 0);
$pdf->Cell(90,3.5, d('ALIMENTACIÓN PARA EL BIENESTAR S.A. DE C.V.'), 0, 1, 'C');

// ─── HOJAS R07 ($4.50 y $6.50) ─────────────────────────────────────────────
function hojaR07($pdf, $datos, $r07, $label) {
    $pdf->titulo = 'CONCILIACIÓN MENSUAL EN ALMACÉN RURAL';
    $pdf->subtitulo = "CONVENIO DE COLABORACIÓN LECHE PARA EL BIENESTAR - ALIMENTACIÓN PARA EL BIENESTAR";
    $pdf->AddPage();

    $almacen = $datos['almacen'];
    $mes     = $datos['mes'];
    $anio    = $datos['anio'];
    $meses = ['','ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO',
                  'JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];
    $mesNom = $meses[$mes] ?? '';
    $supervisor = $datos['supervisor']['nombre'] ?? '';
    $encargado  = $datos['encargado'] ?? '';

    $totalCajas = (int)($r07['total_recibidas'] ?? 0);

    $pdf->SetFont('Arial','',8);
    $pdf->Cell(95,5, d("ALMACÉN: $almacen"), 0, 0);
    $pdf->Cell(100,5, d("MES: $mesNom $anio"), 0, 1);
    $pdf->Cell(95,5, d("DOTACIÓN RECIBIDA: $totalCajas cajas"), 0, 0);
    $pdf->Cell(100,5, d("Leche de $label / litro"), 0, 1);
    $pdf->Ln(2);

    // Tabla
    $pdf->SetFont('Arial','B',6.5);
    $pdf->SetFillColor(220,220,220);
    // Encabezado superior agrupado
    $pdf->Cell(28,5, d('No. PUNTO VENTA'), 1, 0, 'C', true);
    $pdf->Cell(20,5, 'TIENDA', 1, 0, 'C', true);
    $pdf->Cell(34,5, 'CAJAS', 1, 0, 'C', true);
    $pdf->Cell(18,5, 'GUIAS', 1, 0, 'C', true);
    $pdf->Cell(60,5, d('DOTACIÓN ENVIADA'), 1, 0, 'C', true);
    $pdf->Cell(35,5, 'OBSERVACIONES', 1, 1, 'C', true);
    // Sub-encabezados
    $pdf->Cell(28,5, '', 1, 0);
    $pdf->Cell(20,5, 'DICONSA', 1, 0, 'C');
    $pdf->Cell(17,5, 'RECIBIDAS', 1, 0, 'C');
    $pdf->Cell(17,5, 'FECHA REC.', 1, 0, 'C');
    $pdf->Cell(18,5, d('DISTRIBUCIÓN'), 1, 0, 'C');
    $pdf->Cell(18,5, 'No. FACTURA', 1, 0, 'C');
    $pdf->Cell(15,5, 'No. CAJAS', 1, 0, 'C');
    $pdf->Cell(27,5, 'FECHA dd/mm/aa', 1, 0, 'C');
    $pdf->Cell(35,5, '', 1, 1);

    $pdf->SetFont('Arial','',7);
    $rows = $r07['rows'] ?? [];
    if (!$rows) {
        $pdf->Cell(0,6, d('Sin surtimientos registrados a este precio en el mes.'), 1, 1, 'C');
    } else {
        foreach ($rows as $r) {
            $pdf->Cell(28,5, d($r['punto_venta']),         1, 0, 'C');
            $pdf->Cell(20,5, d($r['num_tienda']),          1, 0, 'C');
            $pdf->Cell(17,5, (string)$r['cajas_recibidas'],1, 0, 'C');
            $pdf->Cell(17,5, d($r['fecha_recepcion']),     1, 0, 'C');
            $pdf->Cell(18,5, (string)$r['guias_distribucion'],1, 0, 'C');
            $pdf->Cell(18,5, d($r['no_factura']),          1, 0, 'C');
            $pdf->Cell(15,5, (string)$r['no_cajas_enviadas'],1, 0, 'C');
            $pdf->Cell(27,5, d($r['fecha_enviada']),       1, 0, 'C');
            $pdf->Cell(35,5, d(substr($r['observaciones'] ?? '', 0, 60)), 1, 1, 'L');
        }
        // Totales
        $pdf->SetFont('Arial','B',7);
        $sumRec = array_sum(array_column($rows,'cajas_recibidas'));
        $sumGui = array_sum(array_column($rows,'guias_distribucion'));
        $sumEnv = array_sum(array_column($rows,'no_cajas_enviadas'));
        $pdf->Cell(48,5, 'TOTALES', 1, 0, 'R');
        $pdf->Cell(17,5, (string)$sumRec, 1, 0, 'C');
        $pdf->Cell(17,5, '', 1, 0);
        $pdf->Cell(18,5, (string)$sumGui, 1, 0, 'C');
        $pdf->Cell(18,5, '', 1, 0);
        $pdf->Cell(15,5, (string)$sumEnv, 1, 0, 'C');
        $pdf->Cell(27,5, '', 1, 0);
        $pdf->Cell(35,5, '', 1, 1);
    }

    $pdf->Ln(12);
    // Firmas R07
    $pdf->SetFont('Arial','',7);
    $pdf->Cell(95,5, '________________________________________', 0, 0, 'C');
    $pdf->Cell(10,5, '', 0, 0);
    $pdf->Cell(90,5, '________________________________________', 0, 1, 'C');
    $pdf->SetFont('Arial','B',7);
    $pdf->Cell(95,4, d($supervisor !== '' ? $supervisor : 'NOMBRE Y FIRMA'), 0, 0, 'C');
    $pdf->Cell(10,4, '', 0, 0);
    $pdf->Cell(90,4, d($encargado !== '' ? $encargado : 'NOMBRE Y FIRMA'), 0, 1, 'C');
    $pdf->SetFont('Arial','',6.5);
    $pdf->Cell(95,3.5, d('PROMOTOR SOCIAL'), 0, 0, 'C');
    $pdf->Cell(10,3.5, '', 0, 0);
    $pdf->Cell(90,3.5, d('JEFE DE ALMACÉN'), 0, 1, 'C');
    $pdf->Cell(95,3.5, d('LECHE PARA EL BIENESTAR, S.A. DE C.V.'), 0, 0, 'C');
    $pdf->Cell(10,3.5, '', 0, 0);
    $pdf->Cell(90,3.5, d('ALIMENTACIÓN PARA EL BIENESTAR S.A. DE C.V.'), 0, 1, 'C');
}

if ($r4) hojaR07($pdf, $datos, $r4, '$4.50');
if ($r6) hojaR07($pdf, $datos, $r6, '$6.50');

if (ob_get_length()) ob_clean();
$nombre = sprintf('Inventario_Almacen_%s_%02d_%d.pdf',
    preg_replace('/[^A-Za-z0-9]/','_', $almacen),
    $mes, $anio);

require_once __DIR__ . '/../includes/pdf_archivado.php';
archivarPdf($pdf, [
    'tipo'    => 'inventario_almacen',
    'modulo'  => 'supervisor',
    'subdir'  => 'inventarios_almacen',
    'mes'     => $mes,
    'anio'    => $anio,
    'usuario' => $_SESSION['usuario'] ?? '',
    'nombre'  => $nombre,
    'extras'  => ['almacen' => $almacen],
]);

$pdf->Output('I', $nombre);
exit;
