<?php
error_reporting(0);
require_once('../fpdf/fpdf.php');

$json = file_get_contents('php://input');
$datos = json_decode($json, true);

if (!$datos) {
    http_response_code(400);
    exit('No se recibieron datos.');
}
function d($str) {
    return utf8_decode($str ?? '');
}

// Función helper para dibujar un checkbox con una X
function drawCheckbox($pdf, $x, $y, $isChecked) {
    $pdf->Rect($x, $y, 4, 4); // Dibuja el cuadrito
    if ($isChecked) {
        $pdf->SetXY($x, $y);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(4, 4, 'X', 0, 0, 'C'); // Pon la X si está marcado
        $pdf->SetFont('Arial', '', 8);
    }
}

// Inicializar PDF
$pdf = new FPDF('P', 'mm', 'Letter');
$pdf->AddPage();
$pdf->SetAutoPageBreak(false); // Para control manual

// --- LOGOS (Simulados) ---
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(40, 15, '[LOGO SADER]', 1, 0, 'C');
$pdf->Cell(115, 15, '', 0, 0, 'C');
$pdf->Cell(40, 15, '[LOGO LICONSA]', 1, 1, 'C');
$pdf->Ln(5);

// --- TÍTULO ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 6, d('INVENTARIO MENSUAL DE LECHE EN POLVO'), 0, 1, 'C');
$pdf->Ln(3);

// --- DATOS GENERALES ---
$pdf->SetFont('Arial', '', 9);

$pdf->Cell(15, 6, 'Fecha:', 0, 0);
$pdf->Cell(35, 6, d($datos['fecha']), 'B', 0, 'C');
$pdf->Cell(40, 6, 'Clave del punto de venta:', 0, 0);
$pdf->Cell(40, 6, d($datos['lecheria']), 'B', 0, 'C');
$pdf->Cell(30, 6, 'Clave de tienda:', 0, 0);
$pdf->Cell(35, 6, d($datos['tienda']), 'B', 1, 'C');

$pdf->Cell(18, 6, 'Municipio:', 0, 0);
$pdf->Cell(70, 6, d($datos['municipio']), 'B', 0, 'C');
$pdf->Cell(20, 6, 'Comunidad:', 0, 0);
$pdf->Cell(87, 6, d($datos['comunidad']), 'B', 1, 'C');

$pdf->Ln(4);

// --- I. EXISTENCIA ---
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(0, 6, d('I.- EXISTENCIA DE LECHE.'), 0, 1);

$pdf->SetFont('Arial', 'B', 7);
$w = array(25, 25, 28, 28, 28, 30, 31);
$pdf->Cell($w[0], 8, '', 1, 0, 'C');
$pdf->Cell($w[1], 8, 'Inventario Inicial', 1, 0, 'C');
$pdf->Cell($w[2], 8, 'Abasto total mes', 1, 0, 'C');
$pdf->Cell($w[3], 8, 'Ventas real mes', 1, 0, 'C');
$pdf->Cell($w[4], 8, 'Litros Registrados', 1, 0, 'C');
$pdf->Cell($w[5], 8, d('Diferencias'), 1, 0, 'C');
$pdf->Cell($w[6], 8, 'Inventario final mes', 1, 1, 'C');

$pdf->SetFont('Arial', '', 8);
$rowsExistencia = [
    ['Cajas', 'inv_ini_caja', 'abasto_caja', 'venta_caja', 'reg_caja', 'dif_caja', 'fin_caja'],
    ['Sobres', 'inv_ini_sobres', 'abasto_sobres', 'venta_sobres', 'reg_sobres', 'dif_sobres', 'fin_sobres'],
    ['Total en litros', 'inv_ini_litros', 'abasto_litros', 'venta_litros', 'reg_litros', 'dif_litros', 'fin_litros']
];

foreach($rowsExistencia as $r) {
    $pdf->Cell($w[0], 6, d($r[0]), 1, 0, 'C');
    for($i=1; $i<=6; $i++) {
        $pdf->Cell($w[$i], 6, d($datos[$r[$i]]), 1, 0, 'C');
    }
    $pdf->Ln();
}
$pdf->Ln(4);

// --- 1.1 DIFERENCIAS ---
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(40, 5, '1.1 DIFERENCIAS', 0, 0);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(60, 5, d('¿La venta registrada es igual a la venta real?'), 0, 0);

$pdf->Cell(15, 5, 'No', 0, 0, 'R');
drawCheckbox($pdf, $pdf->GetX(), $pdf->GetY()+0.5, ($datos['venta_igual'] == 'No'));
$pdf->Cell(10, 5, '', 0, 0); // Espacio

$pdf->Cell(15, 5, d('Sí'), 0, 0, 'R');
drawCheckbox($pdf, $pdf->GetX(), $pdf->GetY()+0.5, ($datos['venta_igual'] == 'Si'));
$pdf->Ln(6);

$pdf->Cell(40, 5, d('¿Señale o describa la causa?'), 0, 0);
$pdf->Cell(0, 5, d($datos['causa_desc']), 'B', 1);

$pdf->Cell(65, 6, d('a) Falta de capacitación al responsable'), 0, 0);
drawCheckbox($pdf, $pdf->GetX(), $pdf->GetY()+1, $datos['causa_a']);
$pdf->Cell(20, 6, '', 0, 0); // Espacio central
$pdf->Cell(55, 6, d('b) Omisión del responsable de la venta'), 0, 0);
drawCheckbox($pdf, $pdf->GetX(), $pdf->GetY()+1, $datos['causa_b']);
$pdf->Ln(6);

$pdf->Cell(65, 6, d('c) Resistencia de las personas titulares'), 0, 0);
drawCheckbox($pdf, $pdf->GetX(), $pdf->GetY()+1, $datos['causa_c']);
$pdf->Cell(20, 6, '', 0, 0); // Espacio central
$pdf->Cell(15, 6, 'd) Otros:', 0, 0);
$pdf->Cell(0, 6, d($datos['causa_d']), 'B', 1);

$pdf->Ln(2);

// --- 1.2 VENTA NO REGISTRADA ---
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(0, 5, '1.2 VENTA NO REGISTRADA.', 0, 1);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(75, 5, d('a) ¿Se vendió leche a personas no incluidas en el libro de retiro?'), 0, 0);

$pdf->Cell(10, 5, 'No', 0, 0, 'R');
drawCheckbox($pdf, $pdf->GetX(), $pdf->GetY()+0.5, ($datos['venta_no_incluida'] == 'No'));
$pdf->Cell(15, 5, d('Sí'), 0, 0, 'R');
drawCheckbox($pdf, $pdf->GetX(), $pdf->GetY()+0.5, ($datos['venta_no_incluida'] == 'Si'));
$pdf->Cell(15, 5, '', 0, 0);
$pdf->Cell(25, 5, 'Anote el motivo:', 0, 0);
$pdf->Cell(0, 5, d($datos['motivo_no_incluida']), 'B', 1);

$pdf->Ln(5);

// --- II. SURTIMIENTOS ---
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(0, 6, 'II.- SURTIMIENTOS.', 0, 1);
$pdf->SetFont('Arial', 'B', 8);
$w2 = array(35, 30, 35, 45, 50);
$pdf->Cell($w2[0], 6, 'Fecha', 1, 0, 'C');
$pdf->Cell($w2[1], 6, 'Cajas', 1, 0, 'C');
$pdf->Cell($w2[2], 6, 'Litros', 1, 0, 'C');
$pdf->Cell($w2[3], 6, 'Facturas', 1, 0, 'C');
$pdf->Cell($w2[4], 6, 'Caducidad', 1, 1, 'C');

$pdf->SetFont('Arial', '', 9);
$pdf->Cell($w2[0], 6, d($datos['surt_fecha']), 1, 0, 'C');
$pdf->Cell($w2[1], 6, d($datos['surt_cajas']), 1, 0, 'C');
$pdf->Cell($w2[2], 6, d($datos['surt_litros']), 1, 0, 'C');
$pdf->Cell($w2[3], 6, d($datos['surt_factura']), 1, 0, 'C');
$pdf->Cell($w2[4], 6, d($datos['surt_caducidad']), 1, 1, 'C');

$pdf->Ln(4);

// --- 2.1 FALTA DE SURTIMIENTO ---
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(40, 5, 'II.1 DESABASTO', 0, 1);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(35, 5, d('¿Hubo Falta de surtimiento?'), 0, 0);
$pdf->Cell(10, 5, 'No.', 0, 0, 'R');
drawCheckbox($pdf, $pdf->GetX(), $pdf->GetY()+0.5, ($datos['falta_surtimiento'] == 'No'));
$pdf->Cell(20, 5, d('Sí.'), 0, 0, 'R');
drawCheckbox($pdf, $pdf->GetX(), $pdf->GetY()+0.5, ($datos['falta_surtimiento'] == 'Si'));
$pdf->Ln(6);

$pdf->Cell(35, 5, d('Señale o describa la causa:'), 0, 0);
$pdf->Cell(0, 5, d($datos['causa_falta_desc']), 'B', 1);

$pdf->Cell(50, 6, d('a) Adeudo del responsable de la venta'), 0, 0);
drawCheckbox($pdf, $pdf->GetX(), $pdf->GetY()+1, $datos['causa_falta_a']);
$pdf->Cell(35, 6, '', 0, 0); 
$pdf->Cell(45, 6, d('b) Retraso en la distribución'), 0, 0);
drawCheckbox($pdf, $pdf->GetX(), $pdf->GetY()+1, $datos['causa_falta_b']);
$pdf->Ln(6);

$pdf->Cell(15, 6, 'c) Otros:', 0, 0);
$pdf->Cell(0, 6, d($datos['causa_falta_c']), 'B', 1);
$pdf->Ln(4);

// --- III. COBERTURA SOCIAL ---
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(0, 6, d('III.- COBERTURA SOCIAL Y DOTACIÓN ASIGNADA SEGÚN PADRÓN DE BENEFICIARIOS.'), 0, 1);

$w3 = array(40, 40, 40, 40);
// Centramos la tabla (Ajustamos el X inicial)
$pdf->SetX(28); 
$pdf->Cell($w3[0], 6, 'HOGARES', 1, 0, 'C');
$pdf->Cell($w3[1], 6, 'MENORES', 1, 0, 'C');
$pdf->Cell($w3[2], 6, 'PERSONAS ADULTAS', 1, 0, 'C');
$pdf->Cell($w3[3], 6, 'LITROS AL MES', 1, 1, 'C');

$pdf->SetX(28);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell($w3[0], 8, d($datos['hogares']), 1, 0, 'C');
$pdf->Cell($w3[1], 8, d($datos['menores']), 1, 0, 'C');
$pdf->Cell($w3[2], 8, d($datos['mayores']), 1, 0, 'C');
$pdf->Cell($w3[3], 8, d($datos['dotacion']), 1, 1, 'C');
$pdf->Ln(4);

// --- IV. PROBLEMAS DE OPERACIÓN ---
$pdf->SetX(15);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(0, 5, d('IV.- PROBLEMAS DE OPERACIÓN EN EL PUNTO DE VENTA'), 0, 1);
$pdf->SetFont('Arial', '', 8);

$pdf->Cell(55, 6, d('a) Cierre por reubicación de punto de venta'), 0, 0);
drawCheckbox($pdf, $pdf->GetX(), $pdf->GetY()+1, $datos['prob_a']);
$pdf->Cell(30, 6, '', 0, 0); 
$pdf->Cell(55, 6, d('b) Renuncia o baja del responsable'), 0, 0);
drawCheckbox($pdf, $pdf->GetX(), $pdf->GetY()+1, $datos['prob_b']);
$pdf->Ln(6);

$pdf->Cell(55, 6, d('c) Adeudo del responsable'), 0, 0);
drawCheckbox($pdf, $pdf->GetX(), $pdf->GetY()+1, $datos['prob_c']);
$pdf->Cell(30, 6, '', 0, 0);
$pdf->Cell(15, 6, 'd) Otros:', 0, 0);
$pdf->Cell(0, 6, d($datos['prob_d']), 'B', 1);
$pdf->Ln(2);

// 4.1 Continuar
$pdf->Cell(65, 5, d('IV.1.- ¿Se puede continuar con la venta de leche Liconsa?'), 0, 0);
$pdf->Cell(10, 5, d('Sí'), 0, 0, 'R');
drawCheckbox($pdf, $pdf->GetX(), $pdf->GetY()+0.5, ($datos['continuar_venta'] == 'Si'));
$pdf->Cell(15, 5, 'No', 0, 0, 'R');
drawCheckbox($pdf, $pdf->GetX(), $pdf->GetY()+0.5, ($datos['continuar_venta'] == 'No'));

$pdf->Cell(35, 5, d('Alternativas de solución:'), 0, 0, 'R');
$pdf->Cell(0, 5, d($datos['alternativa_general']), 'B', 1);

$pdf->Cell(45, 6, 'a) Propuesta de un nuevo local', 0, 0);
drawCheckbox($pdf, $pdf->GetX(), $pdf->GetY()+1, $datos['alt_a']);
$pdf->Cell(40, 6, '', 0, 0); 
$pdf->Cell(40, 6, d('b) Fusión de beneficiarios'), 0, 0);
drawCheckbox($pdf, $pdf->GetX(), $pdf->GetY()+1, $datos['alt_b']);
$pdf->Ln(6);

$pdf->Cell(45, 6, d('c) Baja del padrón de beneficiarios'), 0, 0);
drawCheckbox($pdf, $pdf->GetX(), $pdf->GetY()+1, $datos['alt_c']);
$pdf->Cell(40, 6, '', 0, 0);
$pdf->Cell(15, 6, 'd) Otra:', 0, 0);
$pdf->Cell(0, 6, d($datos['alt_d']), 'B', 1);
$pdf->Ln(15);

// --- FIRMAS ---
$pdf->Cell(80, 0, '', 'T', 0, 'C'); // Línea 1
$pdf->Cell(35, 0, '', 0, 0, 'C');    // Espacio medio
$pdf->Cell(80, 0, '', 'T', 1, 'C'); // Línea 2

$pdf->Cell(80, 5, 'Nombre y firma del Promotor(a) Social', 0, 0, 'C');
$pdf->Cell(35, 5, '', 0, 0, 'C');
$pdf->Cell(80, 5, d('Nombre y firma del distribuidor(a) mercantil'), 0, 1, 'C');
$pdf->Cell(80, 4, '', 0, 0, 'C');
$pdf->Cell(35, 4, '', 0, 0, 'C');
$pdf->Cell(80, 4, d('o encargado(a) de la tienda Diconsa'), 0, 1, 'C');

// MAGIA 2: Limpieza de buffer
if (ob_get_length()) ob_end_clean();

// Salida PDF
$pdf->Output('I', 'inventario.pdf');
?>