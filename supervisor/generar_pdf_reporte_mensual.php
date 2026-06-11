<?php
// ───────────────────────────────────────────────────────────────────────────
//  PDF "Reporte Mensual de la Operación" — formato oficial OA-IN-810-02-R08
//  Réplica visual de la hoja física "REQUERIMIENTO DE LECHE DE $X.XX/LITRO".
//
//  POST: campo 'datos' (JSON) generado desde reporte_mensual.php
//  datos: {
//    mes, anio, supervisor,
//    filtro_almacen, filtro_promotor, filtro_tipo,
//    almacenes: [{almacen, lecherias:[{...}], capturadas, total}]
//  }
//
//  Salida: descarga inline + guardado automático en
//          datos/supervisores/reportes_mensuales/AAAA-MM/<archivo>.pdf
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
$fTipo      = trim((string)($datos['filtro_tipo'] ?? 'Todos'));
$mesNombre  = $nombresMeses[$mes] ?? '';
$mesSig     = $nombresMeses[($mes % 12) + 1] ?? '';

// Precio para el título: tomamos el primero no vacío encontrado en las lecherías
$precioTit = '6.50';
foreach ($datos['almacenes'] as $b) {
    foreach (($b['lecherias'] ?? []) as $l) {
        $p = (string)($l['precio'] ?? '');
        if (str_contains($p, '4.50')) { $precioTit = '4.50'; break 2; }
        if (str_contains($p, '6.50')) { $precioTit = '6.50'; break 2; }
    }
}

$logoIzq = __DIR__ . '/../imagenes/Logos/logo_agricultura.png';
$logoDer = __DIR__ . '/../imagenes/Logos/Logo_lecheparaelbienestar.png';

// ─── Clase PDF (vertical, Letter) ──────────────────────────────────────────
class PDFReporteOficial extends FPDF
{
    public $mesNombre  = '';
    public $mesSig     = '';
    public $anio       = 0;
    public $precioTit  = '6.50';
    public $supervisor = '';
    public $logoIzq    = '';
    public $logoDer    = '';
    public $almacenAct = '';
    public $zonaAct    = '';
    public $rutaAct    = '';
    public $promotorAct= '';

    // Ancho de columnas (mm). Total ~ 195 mm útil en Letter vertical
    public $cols = [
        ['hdr' => "NUMERO DE\nPUNTO DE\nVENTA",  'w' => 19, 'k' => 'pv'],
        ['hdr' => "NO. DE\nTIENDA",              'w' => 14, 'k' => 'tienda'],
        ['hdr' => "FAMILIAS",                    'w' => 13, 'k' => 'familias'],
        ['hdr' => "NO. DE\nBENEFICIARIOS",       'w' => 18, 'k' => 'beneficiarios'],
        ['hdr' => "DOTACION\nTEORICA",           'w' => 16, 'k' => 'dot_teorica'],
        ['hdr' => "INVENTARIO\nINICIAL",         'w' => 17, 'k' => 'inv_ini'],
        ['hdr' => "SURT.",                       'w' => 11, 'k' => 'surt'],
        ['hdr' => "VENTAS",                      'w' => 15, 'k' => 'ventas'],
        ['hdr' => "INVENTARIO\nFINAL",           'w' => 17, 'k' => 'inv_fin'],
        ['hdr' => "REQ.",                        'w' => 11, 'k' => 'req_ant'],
        ['hdr' => "V.M.S.",                      'w' => 11, 'k' => 'vms'],
        ['hdr' => "REQ.",                        'w' => 11, 'k' => 'req_sig'],
        ['hdr' => "OBSERVACIONES",               'w' => 26, 'k' => 'obs'],
    ];

    function Header()
    {
        // Logos
        if (file_exists($this->logoIzq)) $this->Image($this->logoIzq, 10, 8, 32);
        if (file_exists($this->logoDer)) $this->Image($this->logoDer, 173, 8, 30);

        $this->SetY(10);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 5, d('LECHE PARA EL BIENESTAR, S.A. DE C.V.'), 0, 1, 'C');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 5, d('GERENCIA ESTATAL OAXACA'), 0, 1, 'C');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 5, d("REQUERIMIENTO DE LECHE DE \$" . $this->precioTit . "/LITRO"), 0, 1, 'C');
        $this->Ln(2);

        // ZONA / RUTA / MES
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(15, 5, d('ZONA:'), 0, 0, 'L');
        $this->SetFont('Arial', '', 9);
        $this->Cell(25, 5, d($this->zonaAct), 'B', 0, 'L');
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(15, 5, d('RUTA:'), 0, 0, 'L');
        $this->SetFont('Arial', '', 9);
        $this->Cell(40, 5, d($this->rutaAct), 'B', 0, 'L');
        $this->Cell(40, 5, '', 0, 0);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(20, 5, d('MES DE:'), 0, 0, 'R');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 5, d($this->mesNombre . ' ' . $this->anio), 'B', 1, 'L');
        $this->Ln(2);

        // Cabecera de tabla (3 líneas de alto)
        $this->SetFont('Arial', 'B', 6.5);
        $this->SetFillColor(235, 235, 235);
        $xIni = $this->GetX();
        $yIni = $this->GetY();
        $hCab = 9;
        foreach ($this->cols as $c) {
            $x = $this->GetX(); $y = $this->GetY();
            $this->MultiCell($c['w'], 3, d($c['hdr']), 1, 'C', true);
            $this->SetXY($x + $c['w'], $y);
        }
        $this->Ln($hCab);

        // Sub-cabecera de meses (REQ mes anterior, V.M.S., REQ mes siguiente)
        // y la fila "ALMACEN ALIMENTACION PARA EL BIENESTAR: <ALMACEN>" como en el formato.
        // Sub-rótulos pequeños bajo REQ. (col 10) y REQ. (col 12)
        $this->SetFont('Arial', 'B', 5.5);
        // Vamos a reescribir los headers con un sub-rótulo dentro: lo solucionamos
        // pintando un rectángulo pequeño con el mes en la celda de cabecera.
        // (Implementado simple: la cabecera ya muestra "REQ."; el mes se anota
        //  en la fila informativa siguiente.)

        // Banda "ALMACEN ALIMENTACION PARA EL BIENESTAR: ..."
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(220, 235, 220);
        $wTot = array_sum(array_column($this->cols, 'w'));
        $this->Cell($wTot, 5,
            d('ALMACEN ALIMENTACION PARA EL BIENESTAR: ' . $this->almacenAct),
            1, 1, 'L', true);
    }

    function Footer()
    {
        // Folio inferior derecho
        $this->SetY(-12);
        $this->SetFont('Arial', '', 7);
        $this->Cell(0, 4, d('OA-IN-810-02-R08'), 0, 0, 'R');
    }

    function pintarFila($l, $mes, $anio, $fill = false)
    {
        $this->SetFont('Arial', '', 7);
        if ($fill) { $this->SetFillColor(250, 250, 250); }
        $h = 5;

        $cap = !empty($l['capturado']);
        $par = function($c, $s) {
            $c = $c === null ? 0 : (int)$c;
            $s = $s === null ? 0 : (int)$s;
            return sprintf('%02d-%02d', $c, $s);
        };

        $valores = [
            'pv'            => (string)($l['punto_venta'] ?? ''),
            'tienda'        => (string)($l['num_tienda']  ?? ''),
            'familias'      => '',  // no en inventarios_mensuales
            'beneficiarios' => '',  // no en inventarios_mensuales
            'dot_teorica'   => '',  // pendiente
            'inv_ini'       => $cap ? $par($l['inv_ini_cajas'], $l['inv_ini_sobres']) : '',
            'surt'          => $cap ? (string)(int)($l['dot_recib_cajas'] ?? 0)       : '',
            'ventas'        => $cap ? $par($l['vend_cajas'],   $l['vend_sobres'])     : '',
            'inv_fin'       => $cap ? $par($l['inv_fin_cajas'],$l['inv_fin_sobres'])  : '',
            'req_ant'       => '',
            'vms'           => '',
            'req_sig'       => '',
            'obs'           => '',
        ];

        if (!$cap) {
            $this->SetTextColor(180, 0, 0);
            $this->SetFont('Arial', 'BI', 7);
        }

        foreach ($this->cols as $c) {
            $v = $valores[$c['k']] ?? '';
            $this->Cell($c['w'], $h, d($v), 1, 0, 'C', $fill);
        }
        $this->Ln();
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 7);
    }

    function pintarFilaVacia()
    {
        $this->SetFont('Arial', '', 7);
        foreach ($this->cols as $c) {
            $this->Cell($c['w'], 5, '', 1, 0, 'C');
        }
        $this->Ln();
    }

    function pintarFirmas($fechaElab, $promotor)
    {
        // Fija al pie: dos columnas de firma + folio
        $yFirmas = 250;
        $this->SetY($yFirmas);

        $this->SetFont('Arial', 'B', 8);
        // Columna izquierda — Promotor
        $this->Cell(95, 4, d('FECHA DE ELABORACION:'), 0, 0, 'L');
        // Columna derecha — Supervisor
        $this->Cell(0, 4, d('REVISO:'), 0, 1, 'L');

        $this->SetFont('Arial', '', 8);
        $this->Cell(95, 4, d($fechaElab), 0, 0, 'L');
        $this->Cell(0, 4, d($fechaElab), 0, 1, 'L');

        $this->SetFont('Arial', '', 7);
        $this->Cell(95, 4, d('DD       MM       AA'), 0, 0, 'L');
        $this->Cell(0, 4, d('DD       MM       AA'), 0, 1, 'L');

        $this->Ln(6);
        $this->SetFont('Arial', '', 9);
        $this->Cell(95, 4, '_________________________', 0, 0, 'L');
        $this->Cell(0, 4, '_________________________', 0, 1, 'L');

        $this->SetFont('Arial', 'B', 8);
        $this->Cell(95, 4, d(strtoupper($promotor ?: 'NOMBRE Y FIRMA DEL PROMOTOR')), 0, 0, 'L');
        $this->Cell(0, 4, d(strtoupper($this->supervisor)), 0, 1, 'L');

        $this->SetFont('Arial', '', 7);
        $this->Cell(95, 3, d('NOMBRE Y FIRMA DEL PROMOTOR'), 0, 0, 'L');
        $this->Cell(0, 3, d('NOMBRE Y FIRMA DEL SUPERVISOR'), 0, 1, 'L');
    }
}

// ─── Construcción del documento ───────────────────────────────────────────
$pdf = new PDFReporteOficial('P', 'mm', 'Letter');
$pdf->mesNombre  = $mesNombre;
$pdf->mesSig     = $mesSig;
$pdf->anio       = $anio;
$pdf->precioTit  = $precioTit;
$pdf->supervisor = $supervisor;
$pdf->logoIzq    = $logoIzq;
$pdf->logoDer    = $logoDer;
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 35);  // deja sitio para firmas+folio

// Una página por almacén. Si un almacén excede capacidad, se parte en varias.
$FILAS_POR_PAGINA = 25;

foreach ($datos['almacenes'] as $bloque) {
    $alm  = strtoupper(trim((string)($bloque['almacen'] ?? '')));
    $lecs = $bloque['lecherias'] ?? [];

    // Promotor "de la página": usamos el primero capturado/disponible
    $promotorNombre = '';
    foreach ($lecs as $l) {
        if (!empty($l['promotor_nombre'])) { $promotorNombre = $l['promotor_nombre']; break; }
    }

    $pdf->almacenAct  = $alm;
    $pdf->zonaAct     = '';   // sin dato en BD — se llena a mano
    $pdf->rutaAct     = '';
    $pdf->promotorAct = $promotorNombre;

    $trozos = array_chunk($lecs, $FILAS_POR_PAGINA);
    if (empty($trozos)) $trozos = [[]];

    foreach ($trozos as $iTrozo => $trozo) {
        $pdf->AddPage();

        $i = 0;
        foreach ($trozo as $l) {
            $pdf->pintarFila($l, $mes, $anio, ($i++ % 2 === 0));
        }
        // Rellena hasta completar el bloque visual del formato
        $faltan = $FILAS_POR_PAGINA - count($trozo);
        for ($k = 0; $k < $faltan; $k++) $pdf->pintarFilaVacia();

        $pdf->pintarFirmas(date('d / m / Y'), $promotorNombre);
    }
}

// ─── Guardado automático + salida inline ──────────────────────────────────
$slugSup = preg_replace('/[^A-Za-z0-9]/', '_', $_SESSION['usuario'] ?? 'supervisor');
$nombreArchivo = sprintf('ReporteMensual_%04d_%02d_%s.pdf', $anio, $mes, $slugSup);

$totalLec = 0; $totalCapt = 0;
foreach ($datos['almacenes'] as $b) {
    foreach (($b['lecherias'] ?? []) as $l) {
        $totalLec++;
        if (!empty($l['capturado'])) $totalCapt++;
    }
}

require_once __DIR__ . '/../includes/pdf_archivado.php';
archivarPdf($pdf, [
    'tipo'    => 'reporte_mensual',
    'modulo'  => 'supervisor',
    'subdir'  => 'reportes_mensuales',
    'mes'     => $mes,
    'anio'    => $anio,
    'usuario' => $_SESSION['usuario'] ?? '',
    'nombre'  => $nombreArchivo,
    'extras'  => [
        'precio'           => $precioTit,
        'total_lecherias'  => $totalLec,
        'total_capturadas' => $totalCapt,
    ],
]);

$pdf->Output('I', $nombreArchivo);
