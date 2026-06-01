<?php
// ────────────────────────────────────────────────────────────────────
//  PDF "Requerimiento de dotación en base al desplazamiento mensual"
//  (formato OA-IN-810-02-R03). Lo genera el SUPERVISOR a partir del
//  consolidado de requerimientos guardados por sus promotores.
//
//  POST JSON:
//  {
//     mes, anio, precio,                       (filtros)
//     zona, esquema, supervisor,
//     almacenes:[ { almacen, lecherias:[{punto_venta,num_tienda,requerimiento}], subtotal } ]
//  }
// ────────────────────────────────────────────────────────────────────
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/session_guard.php';
require_once __DIR__ . '/../fpdf/fpdf.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    http_response_code(401);
    exit('No autorizado.');
}

$json  = file_get_contents('php://input');
$datos = json_decode($json, true);

if (!is_array($datos) || empty($datos['almacenes'])) {
    http_response_code(400);
    exit('Sin datos para generar el PDF.');
}

function d($s) { return utf8_decode((string)($s ?? '')); }

$nombresMeses = ['', 'ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO',
                     'JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];

$mes        = (int)($datos['mes']   ?? 0);
$anio       = (int)($datos['anio']  ?? 0);
$precio     = $datos['precio']      ?? 'todos';
$zona       = trim((string)($datos['zona']   ?? ''));
$esquema    = trim((string)($datos['esquema']?? ''));
$sup        = trim((string)($datos['supervisor'] ?? ($_SESSION['nombre'] ?? '')));
$fAlm       = trim((string)($datos['filtro_almacen']      ?? 'Todos'));
$fDistrib   = trim((string)($datos['filtro_distribuidor'] ?? 'Todos'));
$mesNombre  = $nombresMeses[$mes] ?? '';

$precioLabel = match(true) {
    str_contains($precio,'4.50') => '$4.50 / litro',
    str_contains($precio,'6.50') => '$6.50 / litro',
    default                      => 'Todos los precios',
};

// ---- Aplanamos el contenido en una sola lista de "filas a imprimir":
//      cada almacén abre con un row "header" y cierra con uno "subtotal".
//      Así podemos repartirlas en 2 columnas y saltar de página parejo.
$filas = [];   // cada fila: ['tipo'=>'header|data|subtotal', ...]
$totalGeneral = 0;
foreach ($datos['almacenes'] as $bloque) {
    $alm = strtoupper(trim((string)($bloque['almacen'] ?? '')));
    $filas[] = ['tipo' => 'header', 'almacen' => $alm];
    foreach (($bloque['lecherias'] ?? []) as $l) {
        $capturado = !empty($l['capturado']) || $l['requerimiento'] !== null;

        // "DM" si TIPO_PUNTO_VENTA = 2 o si el num_tienda crudo es 10101.
        // Si el frontend ya envió "DM" en num_tienda, lo respetamos también.
        $tipo   = (int)($l['tipo_punto_venta'] ?? -1);
        $raw    = (string)($l['num_tienda_raw'] ?? $l['num_tienda'] ?? '');
        $tienda = (string)($l['num_tienda'] ?? '');
        if ($tienda !== 'DM' && ($tipo === 2 || $raw === '10101')) {
            $tienda = 'DM';
        }

        $filas[] = [
            'tipo'      => 'data',
            'pv'        => (string)($l['punto_venta'] ?? ''),
            'tienda'    => $tienda,
            'req'       => $capturado ? (string)($l['requerimiento'] ?? '') : 'FALTA',
            'capturado' => $capturado,
        ];
    }
    $subtotal = (int)($bloque['subtotal'] ?? 0);
    $totalGeneral += $subtotal;
    $filas[] = ['tipo' => 'subtotal', 'valor' => $subtotal];
    $filas[] = ['tipo' => 'spacer'];   // separación visual entre almacenes
}

// ---- PDF ----
$pdf = new FPDF('P', 'mm', 'Letter');
$pdf->SetAutoPageBreak(false);
$pdf->SetMargins(10, 10, 10);

$logoIzq = __DIR__ . '/../imagenes/Logos/logo_agricultura.png';
$logoDer = __DIR__ . '/../imagenes/Logos/Logo_lecheparaelbienestar.png';

// Geometría: dos columnas de tabla, 3 sub-columnas c/u.
$PAGE_W      = 215.9;
$MARGIN      = 10;
$AREA_TABLA_W = $PAGE_W - 2*$MARGIN;            // 195.9
$COL_W       = $AREA_TABLA_W / 2;               // 97.95
$GAP         = 4;                                // separación entre columnas
$TABLA_W     = $COL_W - $GAP/2;                  // ancho real de una tabla
// 3 sub-columnas dentro de cada tabla
$W_PV     = $TABLA_W * 0.42;
$W_TIENDA = $TABLA_W * 0.30;
$W_REQ    = $TABLA_W - $W_PV - $W_TIENDA;
$ROW_H    = 5.5;

function dibujarEncabezado($pdf, $logoIzq, $logoDer, $zona, $sup, $esquema, $mesNombre, $anio, $precio,
                           $paginaActual, $totalPaginas) {
    if (file_exists($logoIzq)) $pdf->Image($logoIzq, 10, 8, 32);
    if (file_exists($logoDer)) $pdf->Image($logoDer, 170, 8, 35);

    $pdf->SetY(8);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(0, 4, d("Página $paginaActual de $totalPaginas"), 0, 1, 'C');

    $pdf->SetY(14);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 5, d('LECHE PARA EL BIENESTAR, S.A. DE C.V.'), 0, 1, 'C');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 4, d('GERENCIA ESTATAL OAXACA'), 0, 1, 'C');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 5, d('REQUERIMIENTO DE DOTACION EN BASE AL DESPLAZAMIENTO MENSUAL'), 0, 1, 'C');
    $pdf->Ln(2);

    // Fila 1: ZONA | SUPERVISOR | FECHA
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(20, 5, d('No. ZONA:'), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(20, 5, d($zona), 'B', 0, 'L');
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(40, 5, d('NOMBRE DEL SUPERVISOR:'), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(70, 5, d(strtoupper($sup)), 'B', 0, 'L');
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(15, 5, d('FECHA:'), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(0, 5, d(strtoupper(date('d-M-Y'))), 'B', 1, 'L');

    // Fila 2: ESQUEMA | MES Y AÑO | PRECIO
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(20, 5, d('ESQUEMA:'), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(60, 5, d(strtoupper($esquema)), 'B', 0, 'L');
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(25, 5, d('MES Y AÑO:'), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(50, 5, d("$mesNombre $anio"), 'B', 0, 'L');
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(15, 5, d('PRECIO:'), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(0, 5, d($precioLabel), 'B', 1, 'L');

    // Fila 3: ALMACÉN | DISTRIBUIDOR
    global $fAlm, $fDistrib;
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(20, 5, d('ALMACÉN:'), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(60, 5, d(strtoupper($fAlm)), 'B', 0, 'L');
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(25, 5, d('DISTRIBUIDOR:'), 0, 0, 'L');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(0, 5, d(strtoupper($fDistrib)), 'B', 1, 'L');

    $pdf->Ln(2);
}

function dibujarEncabezadoTabla($pdf, $xCol, $y, $w_pv, $w_tienda, $w_req) {
    $pdf->SetXY($xCol, $y);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetFillColor(235, 235, 235);
    $pdf->MultiCell($w_pv, 4, d("NUMERO DE PUNTO\nDE VENTA"), 1, 'C', true);
    $pdf->SetXY($xCol + $w_pv, $y);
    $pdf->MultiCell($w_tienda, 4, d("NUMERO DE\nTIENDA"), 1, 'C', true);
    $pdf->SetXY($xCol + $w_pv + $w_tienda, $y);
    $pdf->MultiCell($w_req, 4, d("REQUERIMIENTO\n "), 1, 'C', true);
}

function dibujarFila($pdf, $tipo, $row, $xCol, $y, $w_pv, $w_tienda, $w_req, $rowH) {
    $pdf->SetXY($xCol, $y);
    if ($tipo === 'header') {
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(220, 230, 245);
        $pdf->Cell($w_pv + $w_tienda + $w_req, $rowH, ' ' . d('ALMACEN ' . $row['almacen']),
                   1, 0, 'L', true);
    } elseif ($tipo === 'data') {
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell($w_pv,     $rowH, d($row['pv']),     1, 0, 'C');
        $pdf->Cell($w_tienda, $rowH, d($row['tienda']), 1, 0, 'C');
        if (empty($row['capturado'])) {
            // "FALTA" en rojo / cursiva para que destaque sin romper el look del formato.
            $pdf->SetTextColor(198, 40, 40);
            $pdf->SetFont('Arial', 'BI', 8);
            $pdf->Cell($w_req, $rowH, d('FALTA'), 1, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('Arial', '', 8);
        } else {
            $pdf->Cell($w_req, $rowH, d($row['req']), 1, 0, 'C');
        }
    } elseif ($tipo === 'subtotal') {
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(245, 245, 245);
        $pdf->Cell($w_pv + $w_tienda, $rowH, d('SUBTOTAL='), 1, 0, 'R', true);
        $pdf->Cell($w_req, $rowH, d((string)$row['valor']), 1, 0, 'C', true);
    } elseif ($tipo === 'spacer') {
        // sin borde, sólo deja espacio
    }
}

// ───── Cálculo de paginación: cuántas filas caben por columna ─────
$Y_TABLA_INICIO   = 38;     // donde empieza la tabla (después del header)
$Y_TABLA_TOPE     = 252;    // hasta dónde imprimir (deja espacio para firma y folio)
$FILAS_POR_COL    = (int)floor(($Y_TABLA_TOPE - $Y_TABLA_INICIO - 8) / $ROW_H); // -8 por encabezado tabla
// Repartir filas (excluyendo "spacer" extra al pegar columnas) en columnas.
$columnasFlat = [];   // cada columna = arreglo de filas
$buffer = [];
foreach ($filas as $f) {
    // Asegura que un 'header' de almacén no quede al final de la columna solo:
    // si añadirla deja menos de 2 huecos antes del tope, mete spacers para
    // empujarla a la siguiente columna.
    if ($f['tipo'] === 'header' && count($buffer) >= $FILAS_POR_COL - 2) {
        while (count($buffer) < $FILAS_POR_COL) $buffer[] = ['tipo' => 'spacer'];
    }
    $buffer[] = $f;
    if (count($buffer) >= $FILAS_POR_COL) {
        $columnasFlat[] = $buffer;
        $buffer = [];
    }
}
if (!empty($buffer)) $columnasFlat[] = $buffer;

// Cada página = 2 columnas
$totalPaginas = max(1, (int)ceil(count($columnasFlat) / 2));
$pagina = 0;

for ($i = 0; $i < count($columnasFlat); $i += 2) {
    $pagina++;
    $pdf->AddPage();
    dibujarEncabezado($pdf, $logoIzq, $logoDer, $zona, $sup, $esquema,
                      $mesNombre, $anio, $precio, $pagina, $totalPaginas);

    // Columna izquierda
    $xCol = $MARGIN;
    $y = $Y_TABLA_INICIO;
    dibujarEncabezadoTabla($pdf, $xCol, $y, $W_PV, $W_TIENDA, $W_REQ);
    $y += 8;
    foreach ($columnasFlat[$i] as $f) {
        dibujarFila($pdf, $f['tipo'], $f, $xCol, $y, $W_PV, $W_TIENDA, $W_REQ, $ROW_H);
        $y += $ROW_H;
    }

    // Columna derecha (si existe)
    if (isset($columnasFlat[$i + 1])) {
        $xCol = $MARGIN + $COL_W + $GAP/2;
        $y = $Y_TABLA_INICIO;
        dibujarEncabezadoTabla($pdf, $xCol, $y, $W_PV, $W_TIENDA, $W_REQ);
        $y += 8;
        foreach ($columnasFlat[$i + 1] as $f) {
            dibujarFila($pdf, $f['tipo'], $f, $xCol, $y, $W_PV, $W_TIENDA, $W_REQ, $ROW_H);
            $y += $ROW_H;
        }
    }

    // En la última página: firma del supervisor + total + folio.
    if ($pagina === $totalPaginas) {
        $pdf->SetY(258);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(95, 5, d('TOTAL GENERAL:'), 0, 0, 'R');
        $pdf->Cell(20, 5, d((string)$totalGeneral), 'B', 1, 'L');

        $pdf->Ln(8);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(80, 5, '', 'B', 0, 'C');
        $pdf->Cell(0,  5, '', 0, 1);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(80, 4, d('FIRMA DEL SUPERVISOR'), 0, 0, 'L');
    }

    // Folio (en todas las páginas)
    $pdf->SetY(-12);
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(0, 4, d('OA-IN-810-02-R03'), 0, 1, 'R');
}

// ─── Salida ───
$slugSup = preg_replace('/[^A-Za-z0-9]/', '_', $_SESSION['usuario'] ?? 'supervisor');
$precioTag = number_format((float)$precio, 2);   // 4.50 / 6.50
$nombreArchivo = sprintf('RequerimientoDotacion_%04d_%02d_%s_%s.pdf',
                         $anio, $mes, str_replace('.', '', $precioTag), $slugSup);

$baseDir = __DIR__ . '/../datos/supervisores/requerimientos_dotacion';
if (!is_dir($baseDir)) @mkdir($baseDir, 0775, true);
$rutaCompleta = $baseDir . '/' . $nombreArchivo;

if (ob_get_length()) ob_end_clean();
$pdf->Output('F', $rutaCompleta);

// ── Guardar metadatos en SQLite ───────────────────────────────────────
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';
try {
    $totalLecherias = 0;
    foreach ($datos['almacenes'] as $bloque) {
        $totalLecherias += count($bloque['lecherias'] ?? []);
    }
    $dbSql = DatabaseSQLite::getInstance();
    $stmtSql = $dbSql->prepare("INSERT OR REPLACE INTO requerimiento_supervisor
        (mes, anio, precio, supervisor_usr, pdf_nombre, total_general, total_lecherias)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmtSql->execute([$mes, $anio, $precio, $_SESSION['usuario'],
                       $nombreArchivo, $totalGeneral, $totalLecherias]);
} catch (Throwable $eSql) { /* no crítico */ }

$pdf->Output('I', $nombreArchivo);
