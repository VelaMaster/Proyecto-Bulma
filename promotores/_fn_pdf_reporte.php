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

    // ── Definición de columnas (anchos en mm) ─────────────────────
    // Estructura agrupada (como el formato original):
    //   - Grupos con encabezado padre + dos sub-columnas (CAJAS/SOBRES, ROTOS/FALT)
    //   - Columnas simples ocupan ambas filas (rowspan)
    // 'kind' => 'single' | 'group'
    // 'sub'  => sólo en grupos (array de [titulo, ancho])
    // Estructura agrupada (encabezados padre en 2 líneas para no desbordar)
    $cols = [
        ['kind'=>'single', 'title'=>"NUMERO DE\nPUNTO DE VENTA",          'w'=>22],
        ['kind'=>'single', 'title'=>"CLAVE\nDE LA\nTIENDA",                'w'=>13],
        ['kind'=>'single', 'title'=>"PRECIO",                              'w'=>13],
        ['kind'=>'group',  'title'=>"INVENTARIO\nINICIAL",                 'sub'=>[['CAJAS',11],['SOB.',10]]],
        ['kind'=>'single', 'title'=>"DOTACION\nRECIBIDA\n(CAJAS)",         'w'=>15],
        ['kind'=>'group',  'title'=>"T O T A L\n(INV INI + DOT REC.)",     'sub'=>[['CAJAS',11],['SOBRES',11]]],
        ['kind'=>'group',  'title'=>"DOT. VENDIDA\nEN EL PERIODO",         'sub'=>[['CAJAS',11],['SOBRES',11]]],
        ['kind'=>'group',  'title'=>"INVENTARIO\nFINAL",                   'sub'=>[['CAJAS',11],['SOBRES',11]]],
        ['kind'=>'group',  'title'=>"SEGUN REG. DE\nRETIRO DE VENTAS",     'sub'=>[['CAJAS',11],['SOBRES',11]]],
        ['kind'=>'single', 'title'=>"No. DE FAM.\nQUE NO ACUD.\nPOR SU DOT.", 'w'=>17],
        ['kind'=>'group',  'title'=>"SOBRES",                              'sub'=>[['ROTOS',10],['FALT.',10]]],
        ['kind'=>'single', 'title'=>"OBSERVACIONES",                       'w'=>31],
    ];

    // Ancho total y centrado horizontal de la tabla
    $totalAncho = 0;
    foreach ($cols as $c) {
        $totalAncho += ($c['kind'] === 'single')
            ? $c['w']
            : array_sum(array_map(fn($s) => $s[1], $c['sub']));
    }

    // Lista lineal de anchos individuales para los datos (orden importa)
    $colWidths = [];
    foreach ($cols as $c) {
        if ($c['kind'] === 'single') {
            $colWidths[] = $c['w'];
        } else {
            foreach ($c['sub'] as $s) $colWidths[] = $s[1];
        }
    }

    foreach ($almacenes as $bloque) {
        $almacenNombre = $bloque['almacen']   ?? '';
        $lecherias     = $bloque['lecherias'] ?? [];

        $pdf->AddPage();

        if (file_exists($logoIzq)) $pdf->Image($logoIzq, 8,   6, 45);
        if (file_exists($logoDer)) $pdf->Image($logoDer, 225, 6, 40);

        // ── Encabezado superior ───────────────────────────────────
        $pdf->SetY(8);
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell(0, 6, $d_fn('LECHE PARA EL BIENESTAR, S.A. DE C.V.'), 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 5, $d_fn('GERENCIA ESTATAL OAXACA'), 0, 1, 'C');

        // Detectar precios presentes para el título (fiel al original)
        $precios = [];
        foreach ($lecherias as $l) {
            if (!empty($l['precio'])) $precios[trim((string)$l['precio'])] = true;
        }
        $preciosTxt = count($precios) === 1
            ? array_key_first($precios) . '/LITRO'
            : '$ 4.50 Y $ 6.50/LITRO';

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 6, $d_fn('REPORTE MENSUAL DE LA OPERACION EN LECHERIAS CON VENTA DE LECHE EN POLVO DE ' . $preciosTxt), 0, 1, 'C');
        $pdf->Ln(1);

        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(0, 4, $d_fn('ALMACEN ALIMENTACION PARA EL BIENESTAR: ') . $d_fn(strtoupper($almacenNombre)), 0, 1, 'L');
        $periodoTxt = sprintf('REPORTE CORRESPONDIENTE AL PERIODO DEL %s AL %s.',
            $periodoInicio ? _rep_fechaLarga($periodoInicio) : '',
            $periodoFin    ? _rep_fechaLarga($periodoFin)    : ''
        );
        $pdf->Cell(0, 4, $d_fn($periodoTxt), 0, 1, 'L');
        $pdf->Ln(1);

        // ── Tabla: encabezados agrupados (2 filas) ────────────────
        $xIni = ($pdf->GetPageWidth() - $totalAncho) / 2;
        $y0   = $pdf->GetY();
        $hTop = 7;   // alto fila superior (grupo)
        $hSub = 5;   // alto fila inferior (sub-cabeceras)
        $hHead = $hTop + $hSub;

        $pdf->SetFillColor(220, 220, 220);
        $pdf->SetDrawColor(0, 0, 0);

        $x = $xIni;
        foreach ($cols as $c) {
            if ($c['kind'] === 'single') {
                // Celda que abarca las dos filas
                $pdf->Rect($x, $y0, $c['w'], $hHead, 'DF');
                $pdf->SetFont('Arial', 'B', 6);
                $lineas  = explode("\n", $c['title']);
                $nLineas = count($lineas);
                $lh      = 2.6; // alto por línea
                $bloque  = $nLineas * $lh;
                $yTxt    = $y0 + ($hHead - $bloque) / 2;
                foreach ($lineas as $ln) {
                    $pdf->SetXY($x, $yTxt);
                    $pdf->Cell($c['w'], $lh, $d_fn($ln), 0, 0, 'C');
                    $yTxt += $lh;
                }
                $x += $c['w'];
            } else {
                // Grupo: encabezado padre arriba + sub-celdas abajo
                $wGrupo = array_sum(array_map(fn($s) => $s[1], $c['sub']));
                $pdf->Rect($x, $y0, $wGrupo, $hTop, 'DF');
                $pdf->SetFont('Arial', 'B', 6);
                $lineasG  = explode("\n", $c['title']);
                $nLineasG = count($lineasG);
                $lhG      = 2.6;
                $bloqueG  = $nLineasG * $lhG;
                $yTxtG    = $y0 + ($hTop - $bloqueG) / 2;
                foreach ($lineasG as $lnG) {
                    $pdf->SetXY($x, $yTxtG);
                    $pdf->Cell($wGrupo, $lhG, $d_fn($lnG), 0, 0, 'C');
                    $yTxtG += $lhG;
                }

                $subX = $x;
                foreach ($c['sub'] as $s) {
                    [$subTitle, $subW] = $s;
                    $pdf->Rect($subX, $y0 + $hTop, $subW, $hSub, 'DF');
                    $pdf->SetXY($subX, $y0 + $hTop + ($hSub - 3) / 2);
                    $pdf->SetFont('Arial', 'B', 6);
                    $pdf->Cell($subW, 3, $d_fn($subTitle), 0, 0, 'C');
                    $subX += $subW;
                }
                $x += $wGrupo;
            }
        }

        // Posicionar cursor debajo del encabezado
        $pdf->SetY($y0 + $hHead);

        // ── Filas de datos ────────────────────────────────────────
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
            $pdf->SetX($xIni);
            foreach ($colWidths as $i => $w) {
                $pdf->Cell($w, 6, $d_fn((string)$vals[$i]), 1, 0, 'C');
            }
            $pdf->Ln();
        }

        // Filas vacías hasta 17
        $faltan = max(0, 17 - count($lecherias));
        for ($i = 0; $i < $faltan; $i++) {
            $pdf->SetX($xIni);
            foreach ($colWidths as $w) $pdf->Cell($w, 6, '', 1, 0);
            $pdf->Ln();
        }

        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(0, 4, $d_fn('NOTA: LOS DATOS QUE APARECEN EN ESTE FORMATO SON FIDEDIGNOS, DE LOS CUALES SE HACEN RESPONSABLES LOS FIRMANTES.'), 0, 1, 'L');

        $pdf->SetY(-10);
        $pdf->SetFont('Arial', '', 6);
        $pdf->Cell(0, 4, $d_fn('OA-IN-810-02-R04'), 0, 1, 'R');

        // Firmas (sin cambios)
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
