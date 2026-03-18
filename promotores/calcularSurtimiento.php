<?php
// calcularSurtimiento.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
require_once '../conexion.php'; 

$datos = json_decode(file_get_contents('php://input'), true);

$lecher = $datos['lecher'] ?? '';
$menores = intval($datos['menores'] ?? 0);
$mayores = intval($datos['mayores'] ?? 0);

if ($lecher === '') {
    echo json_encode(['error' => true, 'mensaje' => 'Clave de lechería no proporcionada.']);
    exit();
}

// 🧠 CLASE NEURONA ARTIFICIAL
class NeuronaLiconsa {
    private $pesos;
    private $bias;

    public function __construct($pesos, $bias) {
        $this->pesos = $pesos;
        $this->bias = $bias;
    }

    public function predecir_meta($entradas) {
        $sumaPonderada = $this->bias;
        for ($i = 0; $i < count($entradas); $i++) {
            $sumaPonderada += ($entradas[$i] * $this->pesos[$i]);
        }
        return max(0, ceil($sumaPonderada));
    }
}

try {
    // 1. Obtener historial de ventas e inventarios
    $sql = "SELECT VENTA_REAL, INVENTARIO_FINAL 
            FROM INVENTARIO_LEP_SUBSIDIADA 
            WHERE LECHER = :lecher 
            ORDER BY ANIO_PERIODO DESC, MES_PERIODO DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':lecher' => $lecher]);
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $mesesEncontrados = count($historial);
    $ventasCajas = [];
    $sobrantesCajas = [];
    
    // MAGIA: El inventario inicial de HOY es el inventario final del MES MÁS RECIENTE
    $cajasIniciales = 0;
    $litrosIniciales = 0; // <-- NUEVA VARIABLE PARA LOS LITROS EXACTOS

    if ($mesesEncontrados > 0) {
        // Atrapamos los litros exactos directamente de la base de datos (ej. 848)
        $litrosIniciales = floatval($historial[0]['INVENTARIO_FINAL']);
        // Calculamos las cajas para la IA interna (ej. 11.777)
        $cajasIniciales = $litrosIniciales / 72;

        foreach ($historial as $reg) {
            $ventasCajas[] = floatval($reg['VENTA_REAL']) / 72;
            $sobrantesCajas[] = floatval($reg['INVENTARIO_FINAL']) / 72;
        }
    }

    // 2. PREPARACIÓN DE DATOS PARA LA NEURONA
    $historialReal = ($mesesEncontrados > 0) ? (array_sum($ventasCajas) / $mesesEncontrados) : 0;
    
    $totalBeneficiarios = $menores + $mayores;
    $demandaTeorica = ($totalBeneficiarios > 0) ? (($totalBeneficiarios * 8) / 36) : 0;

    $mediaSobrante = ($mesesEncontrados > 0) ? (array_sum($sobrantesCajas) / $mesesEncontrados) : 0;
    $excesoInventario = 0;
    
    if ($cajasIniciales >= 10 && $cajasIniciales > ($mediaSobrante + 5)) {
        $excesoInventario = $cajasIniciales - $mediaSobrante;
    }

    // 3. INICIALIZAR LA NEURONA
    if ($mesesEncontrados >= 2) {
        $pesos = [0.85, 0.15, -0.50]; 
        $explicacion = "Se priorizó el consumo histórico promedio (" . round($historialReal) . " cajas), con un leve ajuste del padrón.";
    } elseif ($mesesEncontrados == 1) {
        $pesos = [0.50, 0.50, -0.50];
        $explicacion = "Cálculo promediado entre el único mes de historial y la capacidad del padrón.";
    } else {
        $pesos = [0.00, 1.00, 0.00]; 
        $explicacion = "Sin historial previo. El cálculo se basa enteramente en el padrón de beneficiarios.";
    }
    
    $neurona = new NeuronaLiconsa($pesos, 0);
    $entradas_X = [$historialReal, $demandaTeorica, $excesoInventario];
    $metaMensual = $neurona->predecir_meta($entradas_X);

    // 4. ALERTAS
    $alerta = "";
    if ($excesoInventario > 0) {
        $cajasCastigadas = ceil($excesoInventario * 0.50); 
        $alerta = "Nota: Sobraron demasiadas cajas (" . round($cajasIniciales, 1) . "). El sistema redujo la meta en aprox. " . $cajasCastigadas . " cajas para evitar saturar la tienda.";
    }

    // 5. CÁLCULO LOGÍSTICO
    $cajasSurtir = $metaMensual - $cajasIniciales;
    if ($cajasSurtir < 0) $cajasSurtir = 0;
    $litrosSurtir = $cajasSurtir * 72;

    $mensaje = "<div style='color: var(--bulma-text); line-height: 1.5;'>";
    $mensaje .= "<strong>Análisis de Surtimiento:</strong><br>";
    $mensaje .= $explicacion . "<br>";
    if ($alerta !== "") {
        $mensaje .= "<br><strong>" . $alerta . "</strong><br>";
    }
    $mensaje .= "<br>Meta mensual calculada: " . $metaMensual . " cajas.<br>";
    $mensaje .= "Restando " . round($cajasIniciales, 1) . " cajas en tienda, <strong>se sugieren " . round($cajasSurtir, 1) . " cajas a surtir.</strong>";
    $mensaje .= "</div>";

    // Devolvemos el cálculo Y los litros puros para que el JS no tenga que adivinar
    echo json_encode([
        'exito' => true,
        'litros_iniciales' => $litrosIniciales, // <-- AHORA MANDAMOS LOS LITROS EXACTOS (ej. 848)
        'cajas_iniciales' => $cajasIniciales,   
        'cajas_surtir' => $cajasSurtir,
        'litros_surtir' => $litrosSurtir,
        'mensaje' => $mensaje
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'mensaje' => 'Error BD: ' . $e->getMessage()]);
}
?>