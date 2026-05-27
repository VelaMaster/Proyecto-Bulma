<?php
/**
 * _fn_pdf_requerimiento.php
 * Helper compartido: genera el PDF del Requerimiento y lo guarda a disco.
 * NO envía nada al browser — devuelve la ruta del archivo generado.
 */

if (!function_exists('generarArchivoRequerimiento')) :

function generarArchivoRequerimiento(array $datos, string $slugUsr): string|false
{
    require_once __DIR__ . '/../fpdf/fpdf.php';

    $d_fn = function($s) { return mb_convert_encoding((string)($s ?? ''), 'ISO-8859-1', 'UTF-8'); };

    if (!function_exists('_req_fechaLargaDDM')) {
        function _req_fechaLargaDDM($iso) {
            static $meses = ['','enero','febrero','marzo','abril','mayo','junio',
                             'julio','agosto','septiembre','octubre','noviembre','diciembre'];
            $t = strtotime($iso); if (!$t) return $iso;
            return date('d', $t) . ' de ' . $meses[(int)date('n',$t)] . ' de ' . date('Y',$t);
        }
    }

    $nombresMeses = ["","ENERO","FEBRERO","MARZO","ABRIL","MAYO","JUNIO",
                     "JULIO","AGOSTO","SEPTIEMBRE","OCTUBRE","NOVIEMBRE","DICIEMBRE"];

    $mesBase     = (int)($datos['mes_base']      ?? 0);
    $anioBase    = (int)($datos['anio_base']     ?? 0);
    $mesMs       = (int)($datos['mes_ms']        ?? 0);
    $mesDestino  = (int)($datos['mes_destino']   ?? 0);
    $anioDestino = (int)($datos['anio_destino']  ?? 0);

    $almacenes = $datos['almacenes'] ?? null;
    if (!$almacenes && !empty($datos['lecherias'])) {
        $almacenes = [['almacen' => $datos['almacen'] ?? '', 'lecherias' => $datos['lecherias']]];
    }
    if (empty($almacenes) || $mesBase < 1 || $anioBase < 2000) return false;

    $mesMsNombre      = $datos['mes_ms_nombre']      ?? ($nombresMeses[$mesMs]      ?? '');
    $mesDestinoNombre = $datos['mes_destino_nombre'] ?? ($nombresMeses[$mesDestino] ?? '');
    $promotor         = $datos['promotor']   ?? '';
    $supervisor       = $datos['supervisor'] ?? '';

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
        ["REQ. M.S.\n" . strtoupper($mesMsNombre), 20],
        ['V.M.S.',                   14],
        ["REQ. " . strtoupper($mesDestinoNombre),  22],
        ['OBSERVACIONES',            29],
    ];

    foreach ($almacenes as $bloque) {
        $almacenNombre = $bloque['almacen']   ?? '';
        $lecherias     = $bloque['lecherias'] ?? [];

        $pdf->AddPage();

        if (file_exists($logoIzq)) $pdf->Image($logoIzq, 10, 8, 50);
        if (file_exists($logoDer)) $pdf->Image($logoDer, 225, 8, 45);

        $pdf->SetY(10);
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell(0, 6, $d_fn('LECHE PARA EL BIENESTAR, S.A. DE C.V.'), 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 5, $d_fn('GERENCIA ESTATAL OAXACA'), 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 6, $d_fn('REQUERIMIENTO DE LECHE'), 0, 1, 'C');
        $pdf->Ln(2);

        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(120, 5, $d_fn('ALMACÉN: ') . $d_fn(strtoupper($almacenNombre)), 0, 0);
        $pdf->Cell(0, 5, $d_fn('MES DE: ' . strtoupper($mesDestinoNombre) . ' ' . $anioDestino), 0, 1, 'R');
        $pdf->Ln(2);

        $pdf->SetFont('Arial', 'B', 7);
        $pdf->SetFillColor(220, 220, 220);
        $y0 = $pdf->GetY();
        $x0 = 10;
        foreach ($cols as $c) $pdf->Cell($c[1], 11, '', 1, 0, 'C', true);
        $xCursor = $x0;
        foreach ($cols as $c) {
            $pdf->SetXY($xCursor, $y0);
            $pdf->MultiCell($c[1], 5.5, $d_fn($c[0]), 0, 'C');
            $xCursor += $c[1];
        }
        $pdf->SetY($y0 + 11);

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
            foreach ($cols as $i => $c) $pdf->Cell($c[1], 6.5, $d_fn((string)$vals[$i]), 1, 0, 'C');
            $pdf->Ln();
        }

        $faltan = max(0, 17 - count($lecherias));
        for ($i = 0; $i < $faltan; $i++) {
            foreach ($cols as $c) $pdf->Cell($c[1], 6.5, '', 1, 0);
            $pdf->Ln();
        }

        $pdf->SetY(-10);
        $pdf->SetFont('Arial', '', 6);
        $pdf->Cell(0, 4, $d_fn('OA-IN-810-02-R08'), 0, 1, 'R');

        // Firmas
        $pdf->Ln(6);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(125, 5, $d_fn('FECHA DE ELABORACIÓN:'), 0, 0, 'L');
        $pdf->Cell(15,  5, '', 0, 0);
        $pdf->Cell(0,   5, $d_fn('REVISÓ:'), 0, 1, 'L');

        $pdf->SetFont('Arial', '', 9);
        $fechaLarga = _req_fechaLargaDDM(date('Y-m-d'));
        $pdf->Cell(125, 5, $d_fn($fechaLarga), 'B', 0, 'L');
        $pdf->Cell(15,  5, '', 0, 0);
        $pdf->Cell(0,   5, $d_fn($fechaLarga), 'B', 1, 'L');
        $pdf->Cell(125, 4, $d_fn('DD          MM          AA'), 0, 0, 'L');
        $pdf->Cell(15,  4, '', 0, 0);
        $pdf->Cell(0,   4, $d_fn('DD          MM          AA'), 0, 1, 'L');
        $pdf->Ln(8);

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(125, 5, $d_fn(strtoupper($promotor)),   'B', 0, 'L');
        $pdf->Cell(15,  5, '', 0, 0);
        $pdf->Cell(0,   5, $d_fn(strtoupper($supervisor)), 'B', 1, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(125, 4, $d_fn('NOMBRE Y FIRMA DEL PROMOTOR'),   0, 0, 'L');
        $pdf->Cell(15,  4, '', 0, 0);
        $pdf->Cell(0,   4, $d_fn('NOMBRE Y FIRMA DEL SUPERVISOR'), 0, 1, 'L');
    }

    // ── Guardar a disco ───────────────────────────────────────────
    $baseDir = __DIR__ . '/../datos/promotores/requerimientos_pdf';
    if (!is_dir($baseDir)) @mkdir($baseDir, 0775, true);

    $nombreArchivo = sprintf('Requerimiento_%04d_%02d_%s.pdf', $anioDestino, $mesDestino, $slugUsr);
    $rutaCompleta  = $baseDir . '/' . $nombreArchivo;

    $pdf->Output('F', $rutaCompleta);

    return file_exists($rutaCompleta) ? $rutaCompleta : false;
}

endif;
