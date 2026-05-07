<?php
// ────────────────────────────────────────────────────────────────────
//  Genera el PDF del Requerimiento de Leche
//  (formato OA-IN-810-02-R08).
//  Recibe payload multi-almacén:
//    { mes_base, anio_base, mes_ms, mes_destino, anio_destino, ...,
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
    exit('No se recibieron datos del requerimiento.');
}

$almacenes = $datos['almacenes'] ?? null;
if (!$almacenes && !empty($datos['lecherias'])) {
    $almacenes = [['almacen' => $datos['almacen'] ?? '', 'lecherias' => $datos['lecherias']]];
}
if (empty($almacenes)) {
    http_response_code(400);
    exit('No hay almacenes con lecherías en el payload.');
}

function d($s) { return utf8_decode((string)($s ?? '')); }
function fechaLargaDDM($iso) {
    static $meses = ['','enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $t = strtotime($iso); if (!$t) return $iso;
    return date('d', $t) . ' de ' . $meses[(int)date('n',$t)] . ' de ' . date('Y',$t);
}

$nombresMeses = ["", "ENERO","FEBRERO","MARZO","ABRIL","MAYO","JUNIO",
                     "JULIO","AGOSTO","SEPTIEMBRE","OCTUBRE","NOVIEMBRE","DICIEMBRE"];

$mesBase     = (int)($datos['mes_base']  ?? 0);
$anioBase    = (int)($datos['anio_base'] ?? 0);
$mesMs       = (int)($datos['mes_ms']    ?? 0);
$mesDestino  = (int)($datos['mes_destino']  ?? 0);
$anioDestino = (int)($datos['anio_destino'] ?? 0);

$mesMsNombre      = $datos['mes_ms_nombre']      ?? ($nombresMeses[$mesMs]      ?? '');
$mesDestinoNombre = $datos['mes_destino_nombre'] ?? ($nombresMeses[$mesDestino] ?? '');

$promotor   = $datos['promotor']   ?? ($_SESSION['nombre'] ?? $_SESSION['usuario'] ?? '');
$supervisor = $datos['supervisor'] ?? '';

$pdf = new FPDF('L', 'mm', 'Letter');
$pdf->SetAutoPageBreak(false);
$pdf->SetMargins(10, 10, 10);

$logoIzq = __DIR__ . '/../imagenes/Logos/logo_agricultura.png';
$logoDer = __DIR__ . '/../imagenes/Logos/Logo_lecheparaelbienestar.png';

$cols = [
    ['NUMERO DE PUNTO DE VENTA', 24],
    ['NO. DE TIENDA',            14],
    ['PRECIO',                   14],
    ['FAMILIAS',                 18],
    ['NO. DE BENEFICIARIOS',     22],
    ['DOTACION TEORICA',         18],
    ['INV. INICIAL',             18],
    ['SURT.',                    14],
    ['VENTAS',                   14],
    ['INV. FINAL',               18],
    ["REQ. M.S.\n" . strtoupper($mesMsNombre),                       20],
    ['V.M.S.',                   14],
    ["REQ. " . strtoupper($mesDestinoNombre),                        22],
    ['OBSERVACIONES',            29],
];

function pintarPaginaAlmacenReq($pdf, $almacenNombre, $lecherias, $cols, $logoIzq, $logoDer, $mesDestinoNombre, $anioDestino) {
    $pdf->AddPage();

    if (file_exists($logoIzq)) $pdf->Image($logoIzq, 10, 8, 50);
    if (file_exists($logoDer)) $pdf->Image($logoDer, 225, 8, 45);

    $pdf->SetY(10);
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->Cell(0, 6, d('LECHE PARA EL BIENESTAR, S.A. DE C.V.'), 0, 1, 'C');
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 5, d('GERENCIA ESTATAL OAXACA'), 0, 1, 'C');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 6, d('REQUERIMIENTO DE LECHE'), 0, 1, 'C');
    $pdf->Ln(2);

    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(120, 5, d('ALMACÉN: ') . d(strtoupper($almacenNombre)), 0, 0);
    $pdf->Cell(0, 5, d('MES DE: ' . strtoupper($mesDestinoNombre) . ' ' . $anioDestino), 0, 1, 'R');
    $pdf->Ln(2);

    // Encabezado de la tabla
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetFillColor(220, 220, 220);
    $y0 = $pdf->GetY();
    $x0 = 10;
    foreach ($cols as $c) {
        $pdf->Cell($c[1], 11, '', 1, 0, 'C', true);
    }
    // Reescribir con MultiCell para títulos multi-línea
    $xCursor = $x0;
    foreach ($cols as $c) {
        $pdf->SetXY($xCursor, $y0);
        $pdf->MultiCell($c[1], 5.5, d($c[0]), 0, 'C');
        $xCursor += $c[1];
    }
    $pdf->SetY($y0 + 11);

    // Filas
    $pdf->SetFont('Arial', '', 8);
    foreach ($lecherias as $l) {
        $vals = [
            $l['punto_venta']      ?? '',
            $l['clave_tienda']     ?? '',
            $l['precio']           ?? '',
            $l['familias']         ?? '',
            $l['beneficiarios']    ?? '',
            $l['dotacion_teorica'] ?? '',
            $l['inv_inicial']      ?? '',
            $l['surtimiento']      ?? '',
            $l['ventas']           ?? '',
            $l['inv_final']        ?? '',
            $l['req_ms_anterior']  ?? '',
            $l['vms']              ?? '',
            $l['req_actual']       ?? '',
            $l['observaciones']    ?? '',
        ];
        foreach ($cols as $i => $c) {
            $pdf->Cell($c[1], 6.5, d((string)$vals[$i]), 1, 0, 'C');
        }
        $pdf->Ln();
    }

    $faltan = max(0, 17 - count($lecherias));
    for ($i = 0; $i < $faltan; $i++) {
        foreach ($cols as $c) $pdf->Cell($c[1], 6.5, '', 1, 0);
        $pdf->Ln();
    }

    // Folio
    $pdf->SetY(-10);
    $pdf->SetFont('Arial', '', 6);
    $pdf->Cell(0, 4, d('OA-IN-810-02-R08'), 0, 1, 'R');
}

foreach ($almacenes as $bloque) {
    $almacenNombre = $bloque['almacen']   ?? '';
    $lecherias     = $bloque['lecherias'] ?? [];
    pintarPaginaAlmacenReq($pdf, $almacenNombre, $lecherias, $cols, $logoIzq, $logoDer, $mesDestinoNombre, $anioDestino);

    // Firmas
    $pdf->Ln(6);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(125, 5, d('FECHA DE ELABORACIÓN:'), 0, 0, 'L');
    $pdf->Cell(15,  5, '', 0, 0);
    $pdf->Cell(0,   5, d('REVISÓ:'), 0, 1, 'L');

    $pdf->SetFont('Arial', '', 9);
    $fechaLarga = fechaLargaDDM(date('Y-m-d'));
    $pdf->Cell(125, 5, d($fechaLarga), 'B', 0, 'L');
    $pdf->Cell(15,  5, '', 0, 0);
    $pdf->Cell(0,   5, d($fechaLarga), 'B', 1, 'L');

    $pdf->Cell(125, 4, d('DD          MM          AA'), 0, 0, 'L');
    $pdf->Cell(15,  4, '', 0, 0);
    $pdf->Cell(0,   4, d('DD          MM          AA'), 0, 1, 'L');

    $pdf->Ln(8);

    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(125, 5, d(strtoupper($promotor)),   'B', 0, 'L');
    $pdf->Cell(15,  5, '', 0, 0);
    $pdf->Cell(0,   5, d(strtoupper($supervisor)), 'B', 1, 'L');

    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(125, 4, d('NOMBRE Y FIRMA DEL PROMOTOR'),   0, 0, 'L');
    $pdf->Cell(15,  4, '', 0, 0);
    $pdf->Cell(0,   4, d('NOMBRE Y FIRMA DEL SUPERVISOR'), 0, 1, 'L');
}

// ─── Salida ───
$slugUsr = preg_replace('/[^A-Za-z0-9]/', '_', $_SESSION['usuario'] ?? 'promotor');
$nombreArchivo = sprintf('Requerimiento_%04d_%02d_%s.pdf', $anioDestino, $mesDestino, $slugUsr);

$baseDir      = __DIR__ . '/../datos/promotores/requerimientos_pdf';
if (!is_dir($baseDir)) @mkdir($baseDir, 0775, true);
$rutaCompleta = $baseDir . '/' . $nombreArchivo;

if (ob_get_length()) ob_end_clean();
$pdf->Output('F', $rutaCompleta);
$pdf->Output('I', $nombreArchivo);
