<?php
// promotores/calcularSurtimiento.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/Repositorio/InventarioRepositorio.php';
require_once __DIR__ . '/../src/Servicio/NeuronaLiconsa.php';

$datos = json_decode(file_get_contents('php://input'), true);
$lecher      = $datos['lecher']       ?? '';
$menores     = intval($datos['menores']    ?? 0);
$mayores     = intval($datos['mayores']    ?? 0);
$mes_periodo = intval($datos['mes_periodo']  ?? date('n'));
$anio_periodo= intval($datos['anio_periodo'] ?? date('Y'));

try {
    $repo     = new InventarioRepositorio();
    $historial = $repo->obtenerHistorialLecheria($lecher);
    $meses     = count($historial);

    // 1. Inventario inicial = FIN_LITROS del mes ANTERIOR en INVENTARIOS_MENSUALES.
    //    Si no existe → avisamos al promotor; los campos iniciales quedan vacíos.
    $litrosInicialesRaw = $repo->obtenerInventarioFinalMesAnterior($lecher, $mes_periodo, $anio_periodo);

    if ($litrosInicialesRaw === null) {
        $mesAnt = $repo->calcularMesAnterior($mes_periodo, $anio_periodo);
        $nombresMeses = ["","Enero","Febrero","Marzo","Abril","Mayo","Junio",
                         "Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];
        $nombreMesAnt = $nombresMeses[$mesAnt['mes']] ?? '';

        echo json_encode([
            'exito'              => false,
            'falta_mes_anterior' => true,
            'mes_ant'            => $mesAnt['mes'],
            'anio_ant'           => $mesAnt['anio'],
            'mensaje'            => "⚠️ No hay inventario registrado para <strong>{$nombreMesAnt} {$mesAnt['anio']}</strong>.<br>El inventario inicial se dejará en blanco. Captura primero el mes faltante o ingrésalo manualmente.",
        ]);
        exit;
    }

    $litrosIniciales = $litrosInicialesRaw;
    $cajasIniciales  = $litrosIniciales / 72;

    $ventasCajas = [];
    $sobrantesCajas = [];
    foreach ($historial as $reg) {
        $ventasCajas[] = floatval($reg['VENTA_REAL']) / 72;
        $sobrantesCajas[] = floatval($reg['INVENTARIO_FINAL']) / 72;
    }

    // 2. Cálculo de promedios para la Neurona
    $historialReal = $meses > 0 ? (array_sum($ventasCajas) / $meses) : 0;
    $totalBeneficiarios = $menores + $mayores;
    $demandaTeorica = ($totalBeneficiarios > 0) ? (($totalBeneficiarios * 8) / 36) : 0;
    $mediaSobrante = ($meses > 0) ? (array_sum($sobrantesCajas) / $meses) : 0;

    // Lógica de exceso de inventario
    $excesoInventario = 0;
    if ($cajasIniciales >= 10 && $cajasIniciales > ($mediaSobrante + 5)) {
        $excesoInventario = $cajasIniciales - $mediaSobrante;
    }

    // 3. Configuración de la Neurona y Explicación
    $alerta = "";
    if ($meses >= 2) {
        $pesos = [0.85, 0.15, -0.50]; 
        $explicacion = "Se priorizó el consumo histórico promedio (" . round($historialReal) . " cajas), con un ajuste por padrón.";
    } elseif ($meses == 1) {
        $pesos = [0.50, 0.50, -0.50];
        $explicacion = "Cálculo promediado entre el único mes de historial y la capacidad del padrón.";
    } else {
        $pesos = [0.00, 1.00, 0.00]; 
        $explicacion = "Sin historial previo. El cálculo se basa enteramente en el padrón de beneficiarios.";
    }
    
    if ($excesoInventario > 0) {
        $cajasCastigadas = ceil($excesoInventario * 0.50); 
        $alerta = "⚠️ **Nota:** Sobraron demasiadas cajas (" . round($cajasIniciales, 1) . "). El sistema redujo la meta en aprox. " . $cajasCastigadas . " cajas para evitar saturación.";
    }

    $neurona = new NeuronaLiconsa($pesos);
    $metaMensual = $neurona->predecir([$historialReal, $demandaTeorica, $excesoInventario]);

    // 4. Cálculo Logístico Final
    $cajasSurtir = max(0, $metaMensual - $cajasIniciales);

    // 5. Construcción del mensaje detallado (HTML para el Toast de Bulma)
    $mensaje = "<div style='line-height: 1.4;'>";
    $mensaje .= "<strong>Análisis inteligente:</strong><br>";
    $mensaje .= $explicacion . "<br>";
    if ($alerta !== "") {
        $mensaje .= "<br><span style='color: #ffd000;'>" . $alerta . "</span><br>";
    }
    $mensaje .= "<br>Meta mensual sugerida: <strong>" . $metaMensual . " cajas</strong>.<br>";
    $mensaje .= "Restando las " . round($cajasIniciales, 1) . " en tienda, se deben surtir <strong>" . round($cajasSurtir, 1) . " cajas.</strong>";
    $mensaje .= "</div>";

    echo json_encode([
        'exito' => true,
        'litros_iniciales' => $litrosIniciales,
        'cajas_surtir' => $cajasSurtir,
        'litros_surtir' => $cajasSurtir * 72,
        'mensaje' => $mensaje
    ]);

} catch (Exception $e) {
    echo json_encode(['exito' => false, 'mensaje' => "Error en Neurona: " . $e->getMessage()]);
}