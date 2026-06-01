<?php
/**
 * _fn_pdf_inventario.php
 * Helper compartido: genera el PDF del Inventario Mensual y lo guarda a disco.
 * NO envía nada al browser — devuelve el nombre del archivo generado.
 *
 * Uso:
 *   require_once '_fn_pdf_inventario.php';
 *   $nombre = generarArchivoInventario($datos);
 *   // $nombre === false si hubo error
 */

if (!function_exists('generarArchivoInventario')) :

function generarArchivoInventario(array $datos): string|false
{
    require_once __DIR__ . '/../fpdf/fpdf.php';

    $d = function($str) { return mb_convert_encoding((string)($str ?? ''), 'ISO-8859-1', 'UTF-8'); };

    function _inv_smartCell($pdf, $w, $h, $text, $border = 0, $ln = 0,
                             $align = 'L', $style = '', $baseSize = 9, $minSize = 6) {
        $size = $baseSize;
        if ($w > 0) {
            $pdf->SetFont('Arial', $style, $size);
            while ($size > $minSize && $pdf->GetStringWidth($text) > ($w - 1.5)) {
                $size -= 0.5;
                $pdf->SetFont('Arial', $style, $size);
            }
        } else {
            $pdf->SetFont('Arial', $style, $size);
        }
        $pdf->Cell($w, $h, $text, $border, $ln, $align);
        $pdf->SetFont('Arial', $style, $baseSize);
    }

    $pdf = new FPDF('P', 'mm', 'Letter');
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(15, 10, 15);

    // ── LOGOS ────────────────────────────────────────────────
    $logoIzq = __DIR__ . '/../imagenes/Logos/logo_agricultura.png';
    $logoDer  = __DIR__ . '/../imagenes/Logos/Logo_lecheparaelbienestar.png';
    if (file_exists($logoIzq)) $pdf->Image($logoIzq, 15,  10, 52);
    if (file_exists($logoDer))  $pdf->Image($logoDer, 150,  8, 50);
    $pdf->Ln(22);

    // ── TÍTULO ───────────────────────────────────────────────
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 7, $d('INVENTARIO MENSUAL DE LECHE EN POLVO'), 0, 1, 'C');
    $pdf->Ln(2);

    // ── DATOS GENERALES ──────────────────────────────────────
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(16, 6, $d('Fecha:'), 0, 0);
    _inv_smartCell($pdf, 28, 6, $d($datos['fecha'] ?? ''), 'B', 0, 'C', '', 9);
    $pdf->Cell(48, 6, $d('  Clave del punto de venta:'), 0, 0);
    _inv_smartCell($pdf, 33, 6, $d($datos['lecheria'] ?? ''), 'B', 0, 'C', '', 9);
    $pdf->Cell(28, 6, $d('  Clave de tienda:'), 0, 0);
    _inv_smartCell($pdf,  0, 6, $d($datos['tienda'] ?? ''), 'B', 1, 'C', '', 9);
    $pdf->Ln(1);

    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(32, 6, $d('Almacen que surte:'), 0, 0);
    _inv_smartCell($pdf, 35, 6, $d($datos['almacen'] ?? ''), 'B', 0, 'C', '', 9);
    $pdf->Cell(20, 6, $d('  Municipio:'), 0, 0);
    _inv_smartCell($pdf, 40, 6, $d($datos['municipio'] ?? ''), 'B', 0, 'C', '', 9);
    $pdf->Cell(18, 6, $d('  Comunidad:'), 0, 0);
    _inv_smartCell($pdf,  0, 6, $d($datos['comunidad'] ?? ''), 'B', 1, 'C', '', 9);
    $pdf->Ln(4);

    // ── I. EXISTENCIA ────────────────────────────────────────
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(0, 5, $d('I.- EXISTENCIA DE LECHE.'), 0, 1);

    $wT = [30, 25, 25, 25, 25, 25, 30];
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Cell($wT[0], 7, '', 1, 0, 'C');
    $pdf->Cell($wT[1], 7, $d('Inventario Inicial'),   1, 0, 'C');
    $pdf->Cell($wT[2], 7, $d('Abasto total mes'),     1, 0, 'C');
    $pdf->Cell($wT[3], 7, $d('Ventas real mes'),      1, 0, 'C');
    $pdf->Cell($wT[4], 7, $d('Litros Registrados'),   1, 0, 'C');
    $pdf->Cell($wT[5], 7, $d('Diferencias'),          1, 0, 'C');
    $pdf->Cell($wT[6], 7, $d('Inventario final mes'), 1, 1, 'C');

    // Helper: muestra '0' si el valor es vacío o null (campos numéricos de la tabla)
    $n = fn($k) => $d(($datos[$k] !== '' && $datos[$k] !== null) ? $datos[$k] : '0');

    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell($wT[0], 6, $d('Cajas'), 1, 0, 'C');
    foreach (['inv_ini_caja','abasto_caja','venta_caja','reg_caja','dif_caja','fin_caja'] as $i => $k)
        $pdf->Cell($wT[$i+1], 6, $n($k), 1, 0, 'C');
    $pdf->Ln();

    $pdf->Cell($wT[0], 6, $d('Sobres'), 1, 0, 'C');
    foreach (['inv_ini_sobres','abasto_sobres','venta_sobres','reg_sobres','dif_sobres','fin_sobres'] as $i => $k)
        $pdf->Cell($wT[$i+1], 6, $n($k), 1, 0, 'C');
    $pdf->Ln();

    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell($wT[0], 6, $d('Total litros'), 1, 0, 'C');
    foreach (['inv_ini_litros','abasto_litros','venta_litros','reg_litros','dif_litros','fin_litros'] as $i => $k)
        $pdf->Cell($wT[$i+1], 6, $n($k), 1, 0, 'C');
    $pdf->Ln();
    $pdf->Ln(3);

    // ── 1.1 ──────────────────────────────────────────────────
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(0, 5, $d('1.1.- ¿La venta registrada es igual a la venta real?'), 0, 1);
    $pdf->SetFont('Arial', '', 8);
    $y = $pdf->GetY() + 1;
    $pdf->Rect($pdf->GetX(), $y, 4, 4); $pdf->Cell(6, 6, '', 0, 0);
    $pdf->Cell(12, 6, $d('No'), 0, 0);
    $pdf->Rect($pdf->GetX(), $y, 4, 4); $pdf->Cell(6, 6, '', 0, 0);
    $pdf->Cell(0,  6, $d('Si'), 0, 1);
    $pdf->Cell(52, 5, $d('Señale o describa la causa:'), 0, 0);
    $pdf->Cell(0,  5, '', 'B', 1);
    $pdf->Ln(3);
    $y = $pdf->GetY() + 1.5;
    $pdf->Rect($pdf->GetX(), $y, 4, 4); $pdf->Cell(6, 6, '', 0, 0);
    $pdf->Cell(84, 6, $d('a) Falta de capacitacion'), 0, 0);
    $pdf->Rect($pdf->GetX(), $y, 4, 4); $pdf->Cell(6, 6, '', 0, 0);
    $pdf->Cell(0, 6, $d('b) Omision del responsable'), 0, 1);
    $y = $pdf->GetY() + 1.5;
    $pdf->Rect($pdf->GetX(), $y, 4, 4); $pdf->Cell(6, 6, '', 0, 0);
    $pdf->Cell(84, 6, $d('c) Resistencia de titulares'), 0, 0);
    $pdf->Cell(18, 6, $d('d) Otros:'), 0, 0);
    $pdf->Cell(0,  6, '', 'B', 1);
    $pdf->Ln(3);

    // ── 1.2 ──────────────────────────────────────────────────
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(0, 5, $d('1.2.- ¿Se vendio leche a personas no incluidas en el libro de retiro?'), 0, 1);
    $pdf->SetFont('Arial', '', 8);
    $y = $pdf->GetY() + 1;
    $pdf->Rect($pdf->GetX(), $y, 4, 4); $pdf->Cell(6, 6, '', 0, 0);
    $pdf->Cell(12, 6, $d('No'), 0, 0);
    $pdf->Rect($pdf->GetX(), $y, 4, 4); $pdf->Cell(6, 6, '', 0, 0);
    $pdf->Cell(12, 6, $d('Si'), 0, 0);
    $pdf->Cell(20, 6, $d('Motivo:'), 0, 0);
    $pdf->Cell(0,  6, '', 'B', 1);
    $pdf->Ln(5);

    // ── II. SURTIMIENTOS ─────────────────────────────────────
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(0, 5, $d('II.- SURTIMIENTOS.'), 0, 1);
    $wS = [40, 25, 30, 60, 30];
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell($wS[0], 6, $d('Fecha'),     1, 0, 'C');
    $pdf->Cell($wS[1], 6, $d('Cajas'),     1, 0, 'C');
    $pdf->Cell($wS[2], 6, $d('Litros'),    1, 0, 'C');
    $pdf->Cell($wS[3], 6, $d('Facturas'),  1, 0, 'C');
    $pdf->Cell($wS[4], 6, $d('Caducidad'), 1, 1, 'C');
    $pdf->SetFont('Arial', '', 9);
    _inv_smartCell($pdf, $wS[0], 6, $d($datos['surt_fecha']     ?? ''), 1, 0, 'C', '', 9);
    _inv_smartCell($pdf, $wS[1], 6, $d($datos['surt_cajas']     ?? ''), 1, 0, 'C', '', 9);
    _inv_smartCell($pdf, $wS[2], 6, $d($datos['surt_litros']    ?? ''), 1, 0, 'C', '', 9);
    _inv_smartCell($pdf, $wS[3], 6, $d($datos['surt_factura']   ?? ''), 1, 0, 'C', '', 9);
    _inv_smartCell($pdf, $wS[4], 6, $d($datos['surt_caducidad'] ?? ''), 1, 1, 'C', '', 9);
    $pdf->Ln(5);

    // ── III. COBERTURA SOCIAL ────────────────────────────────
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(0, 5, $d('III.- COBERTURA SOCIAL Y DOTACION ASIGNADA SEGUN PADRON.'), 0, 1);
    $wC = 46;
    $pdf->SetX(15.5);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell($wC, 6, $d('HOGARES'),       1, 0, 'C');
    $pdf->Cell($wC, 6, $d('MENORES'),       1, 0, 'C');
    $pdf->Cell($wC, 6, $d('MAYORES'),       1, 0, 'C');
    $pdf->Cell($wC, 6, $d('LITROS AL MES'), 1, 1, 'C');
    $pdf->SetX(15.5);
    $pdf->SetFont('Arial', '', 10);
    _inv_smartCell($pdf, $wC, 7, $d($datos['hogares']  ?? ''), 1, 0, 'C', '', 10);
    _inv_smartCell($pdf, $wC, 7, $d($datos['menores']  ?? ''), 1, 0, 'C', '', 10);
    _inv_smartCell($pdf, $wC, 7, $d($datos['mayores']  ?? ''), 1, 0, 'C', '', 10);
    _inv_smartCell($pdf, $wC, 7, $d($datos['dotacion'] ?? ''), 1, 1, 'C', '', 10);
    $pdf->Ln(5);

    // ── IV. PROBLEMAS DE OPERACION ───────────────────────────
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(0, 5, $d('IV.- PROBLEMAS DE OPERACION EN EL PUNTO DE VENTA'), 0, 1);
    $pdf->SetFont('Arial', '', 8);
    $y = $pdf->GetY() + 1.5;
    $pdf->Rect($pdf->GetX(), $y, 4, 4); $pdf->Cell(6, 6, '', 0, 0);
    $pdf->Cell(84, 6, $d('a) Cierre por reubicacion'), 0, 0);
    $pdf->Rect($pdf->GetX(), $y, 4, 4); $pdf->Cell(6, 6, '', 0, 0);
    $pdf->Cell(0, 6, $d('b) Renuncia o baja responsable'), 0, 1);
    $y = $pdf->GetY() + 1.5;
    $pdf->Rect($pdf->GetX(), $y, 4, 4); $pdf->Cell(6, 6, '', 0, 0);
    $pdf->Cell(84, 6, $d('c) Adeudo del responsable'), 0, 0);
    $pdf->Cell(18, 6, $d('d) Otros:'), 0, 0);
    $pdf->Cell(0,  6, '', 'B', 1);
    $pdf->Ln(3);

    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(0, 5, $d('IV.1.- ¿Se puede continuar con la venta de leche?'), 0, 1);
    $pdf->SetFont('Arial', '', 8);
    $y = $pdf->GetY() + 1;
    $pdf->Rect($pdf->GetX(), $y, 4, 4); $pdf->Cell(6, 6, '', 0, 0);
    $pdf->Cell(12, 6, $d('Si'), 0, 0);
    $pdf->Rect($pdf->GetX(), $y, 4, 4); $pdf->Cell(6, 6, '', 0, 0);
    $pdf->Cell(0,  6, $d('No'), 0, 1);
    $pdf->Cell(50, 5, $d('Alternativas de solucion:'), 0, 0);
    $pdf->Cell(0,  5, '', 'B', 1);
    $pdf->Ln(3);
    $y = $pdf->GetY() + 1.5;
    $pdf->Rect($pdf->GetX(), $y, 4, 4); $pdf->Cell(6, 6, '', 0, 0);
    $pdf->Cell(84, 6, $d('a) Propuesta nuevo local'), 0, 0);
    $pdf->Rect($pdf->GetX(), $y, 4, 4); $pdf->Cell(6, 6, '', 0, 0);
    $pdf->Cell(0, 6, $d('b) Fusion de beneficiarios'), 0, 1);
    $y = $pdf->GetY() + 1.5;
    $pdf->Rect($pdf->GetX(), $y, 4, 4); $pdf->Cell(6, 6, '', 0, 0);
    $pdf->Cell(84, 6, $d('c) Baja del padron'), 0, 0);
    $pdf->Cell(14, 6, $d('d) Otra:'), 0, 0);
    $pdf->Cell(0,  6, '', 'B', 1);

    // ── FIRMAS ───────────────────────────────────────────────
    $pdf->SetY(-28);
    $pdf->Cell(87, 0, '', 'T', 0, 'C');
    $pdf->Cell(11, 0, '',  0, 0, 'C');
    $pdf->Cell(87, 0, '', 'T', 1, 'C');
    $pdf->Cell(87, 5, $d('Nombre y firma del Promotor(a) Social'),     0, 0, 'C');
    $pdf->Cell(11, 5, '',                                              0, 0, 'C');
    $pdf->Cell(87, 5, $d('Nombre y firma del distribuidor mercantil'), 0, 1, 'C');

    // ── GUARDAR EN DISCO ─────────────────────────────────────
    $lecheria     = $datos['lecheria'] ?? 'X';
    $time         = strtotime($datos['fecha'] ?? date('Y-m-d'));
    $anio         = date('Y', $time);
    $mes          = date('m', $time);
    $nombreArchivo = "Inventario_{$lecheria}_{$anio}_{$mes}.pdf";
    $baseDir       = __DIR__ . '/../datos/promotores';
    $rutaCompleta  = $baseDir . '/' . $nombreArchivo;

    if (!is_dir($baseDir)) { @mkdir($baseDir, 0775, true); }

    try {
        $pdf->Output('F', $rutaCompleta);
        return $nombreArchivo;
    } catch (Throwable $e) {
        error_log('[_fn_pdf_inventario] Error al guardar PDF: ' . $e->getMessage());
        return false;
    }
}

endif;
