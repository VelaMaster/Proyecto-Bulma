<?php
/**
 * _fn_pdf_reporte.php
 * Helper compartido: genera el PDF del Reporte Mensual y lo guarda a disco.
 * NO envía nada al browser — devuelve la ruta del archivo generado.
 *
 * Uso:
 *   require_once '_fn_pdf_reporte.php';
 *   $ruta = generarArchivoReporte($datos, $slugUsuario);
 *   // $ruta === false si hubo error
 */

if (!function_exists('generarArchivoReporte')) :

function generarArchivoReporte(array $datos, string $slugUsr): string|false
{
    require_once __DIR__ . '/../fpdf/fpdf.php';

    // ── Helpers locales ────────────────────────────────────────────
    $d_fn = function($s) { return mb_convert_encoding((string)($s ?? ''), 'ISO-8859-1', 'UTF-8'); };

    if (!function_exists('_rep_fmtFecha')) {
        function _rep_fmtFecha($iso) {
            if (!$iso) return '';
            $t = strtotime($iso);
            return $t ? date('d/m/y', $t) : $iso;
        }
    }
    if (!function_exists('_rep_fechaLarga')) {
        function _rep_fechaLarga($iso) {
            static $meses = ['','ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO',
                             'JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];
            $t = strtotime($iso); if (!$t) return $iso;
            return date('d', $t) . ' DE ' . $meses[(int)date('n',$t)] . ' DEL ' . date('Y',$t);
        }
    }

    // ── Datos del reporte ─────────────────────────────────────────
    $nombresMeses = ["","ENERO","FEBRERO","MARZO","ABRIL","MAYO","JUNIO",
                     "JULIO","AGOSTO","SEPTIEMBRE","OCTUBRE","NOVIEMBRE","DICIEMBRE"];

    $mes  = (int)($datos['mes_reporte']  ?? 0);
    $anio = (int)($datos['anio_reporte'] ?? 0);

    $almacenes = $datos['almacenes'] ?? null;
    if (!$almacenes && !empty($datos['lecherias'])) {
        $almacenes = [['almacen' => $datos['almacen'] ?? '', 'lecherias' => $datos['lecherias']]];
    }
    if (empty($almacenes) || $mes < 1 || $anio < 2000) return false;

    $periodoInicio = $datos['periodo_inicio'] ?? '';
    $periodoFin    = $datos['periodo_fin']    ?? '';
    $promotor      = $datos['promotor']       ?? '';
    $supervisor    = $datos['supervisor']     ?? '';

    // ── FPDF ──────────────────────────────────────────────────────
    $pdf = new FPDF('L', 'mm', 'Letter');
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(8, 8, 8);

    $logoIzq = __DIR__ . '/../imagenes/Logos/logo_agricultura.png';
    $logoDer = __DIR__ . '/../imagenes/Logos/Logo_lecheparaelbienestar.png';

    $cols = [
        ['NUMERO DE PUNTO DE VENTA',  18],
        ['CLAVE TIENDA',              12],
        ['PRECIO',                    13],
        ['INV. INI. CAJAS',           12],
        ['INV. INI. SOB',             10],
        ['DOTACION RECIB. CAJAS',     15],
        ['TOTAL CAJAS',               12],
        ['TOTAL SOB',                 10],
        ['VEND. CAJAS',               12],
        ['VEND. SOB',                 10],
        ['INV. FIN. CAJAS',           12],
        ['INV. FIN. SOB',             10],
        ['RETIRO CAJAS',              12],
        ['RETIRO SOB',                10],
        ['FAM. NO ACUD.',             13],
        ['SOB. ROTOS',                10],
        ['SOB. FALT.',                10],
        ['OBSERVACIONES',             36],
    ];

    foreach ($almacenes as $bloque) {
        $almacenNombre = $bloque['almacen']   ?? '';
        $lecherias     = $bloque['lecherias'] ?? [];

        $pdf->AddPage();

        if (file_exists($logoIzq)) $pdf->Image($logoIzq, 8,   6, 45);
        if (file_exists($logoDer)) $pdf->Image($logoDer, 225, 6, 40);

        $pdf->SetY(8);
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell(0, 6, $d_fn('LECHE PARA EL BIENESTAR, S.A. DE C.V.'), 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 5, $d_fn('GERENCIA ESTATAL OAXACA'), 0, 1, 'C');
        $pdf->Cell(0, 6, $d_fn('REPORTE MENSUAL DE LA OPERACION EN LECHERIAS'), 0, 1, 'C');
        $pdf->Ln(1);

        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(0, 4, $d_fn('ALMACEN ALIMENTACION PARA EL BIENESTAR: ') . $d_fn(strtoupper($almacenNombre)), 0, 1, 'L');
        $periodoTxt = sprintf('REPORTE CORRESPONDIENTE AL PERIODO DEL %s AL %s.',
            $periodoInicio ? _rep_fechaLarga($periodoInicio) : '',
            $periodoFin    ? _rep_fechaLarga($periodoFin)    : ''
        );
        $pdf->Cell(0, 4, $d_fn($periodoTxt), 0, 1, 'L');
        $pdf->Ln(1);

        $pdf->SetFont('Arial', 'B', 6);
        $pdf->SetFillColor(220, 220, 220);
        foreach ($cols as $c) $pdf->Cell($c[1], 9, $d_fn($c[0]), 1, 0, 'C', true);
        $pdf->Ln();

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
                ($l['observaciones'] !== '' && $l['observaciones'] !== null) ? $l['observaciones'] : 'x',
            ];
            foreach ($cols as $i => $c) $pdf->Cell($c[1], 6, $d_fn((string)$vals[$i]), 1, 0, 'C');
            $pdf->Ln();
        }

        // Filas vacías hasta 17
        $faltan = max(0, 17 - count($lecherias));
        for ($i = 0; $i < $faltan; $i++) {
            foreach ($cols as $c) $pdf->Cell($c[1], 6, '', 1, 0);
            $pdf->Ln();
        }

        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(0, 4, $d_fn('NOTA: LOS DATOS QUE APARECEN EN ESTE FORMATO SON FIDEDIGNOS, DE LOS CUALES SE HACEN RESPONSABLES LOS FIRMANTES.'), 0, 1, 'L');

        $pdf->SetY(-10);
        $pdf->SetFont('Arial', '', 6);
        $pdf->Cell(0, 4, $d_fn('OA-IN-810-02-R04'), 0, 1, 'R');

        // Firmas
        $mesNum  = str_pad((string)$mes, 2, '0', STR_PAD_LEFT);
        $anioStr = (string)$anio;
        $dia     = date('d');

        $pdf->SetY(-30);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(50, 5, $d_fn('FECHAS:'), 0, 0);
        $pdf->Cell(40, 5, $d_fn('DD'), 0, 0, 'C');
        $pdf->Cell(20, 5, $d_fn('MM'), 0, 0, 'C');
        $pdf->Cell(20, 5, $d_fn('AA'), 0, 1, 'C');
        $pdf->Cell(50, 5, $d_fn('DE ELABORACION:'), 0, 0);
        $pdf->Cell(40, 5, $dia,     'B', 0, 'C');
        $pdf->Cell(20, 5, $mesNum,  'B', 0, 'C');
        $pdf->Cell(20, 5, $anioStr, 'B', 1, 'C');
        $pdf->Cell(50, 5, $d_fn('DE RECEPCION:'), 0, 0);
        $pdf->Cell(40, 5, $dia,     'B', 0, 'C');
        $pdf->Cell(20, 5, $mesNum,  'B', 0, 'C');
        $pdf->Cell(20, 5, $anioStr, 'B', 1, 'C');

        $pdf->SetY(-22);
        $pdf->SetX(150);
        $pdf->Cell(60, 5, $d_fn(strtoupper($promotor)),   'B', 0, 'C');
        $pdf->Cell(20, 5, '', 0, 0);
        $pdf->Cell(60, 5, $d_fn(strtoupper($supervisor)), 'B', 1, 'C');
        $pdf->SetX(150);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(60, 4, $d_fn('NOMBRE Y FIRMA'), 0, 0, 'C');
        $pdf->Cell(20, 4, '', 0, 0);
        $pdf->Cell(60, 4, $d_fn('NOMBRE Y FIRMA'), 0, 1, 'C');
        $pdf->SetX(150);
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(60, 4, $d_fn('PROMOTOR SOCIAL'), 0, 0, 'C');
        $pdf->Cell(20, 4, '', 0, 0);
        $pdf->Cell(60, 4, $d_fn('SUPERVISOR SOCIAL'), 0, 1, 'C');
    }

    // ── Guardar a disco ───────────────────────────────────────────
    $baseDir = __DIR__ . '/../datos/promotores/reportes_pdf';
    if (!is_dir($baseDir)) @mkdir($baseDir, 0775, true);

    $nombreArchivo = sprintf('Reporte_%04d_%02d_%s.pdf', $anio, $mes, $slugUsr);
    $rutaCompleta  = $baseDir . '/' . $nombreArchivo;

    $pdf->Output('F', $rutaCompleta);

    return file_exists($rutaCompleta) ? $rutaCompleta : false;
}

endif;
