<?php
// ────────────────────────────────────────────────────────────────────
//  Guarda el "Requerimiento de Dotación" capturado por el promotor.
//  Escribe en la tabla REQUERIMIENTO_DOTACION (fuente de verdad para
//  el supervisor) y deja un snapshot JSON como respaldo / bitácora.
// ────────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../includes/session_guard.php';
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../src/Repositorio/RequerimientoDotacionSchema.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'mensaje' => 'Sesión no válida.']);
    exit();
}

$json  = file_get_contents('php://input');
$datos = json_decode($json, true);

if (!is_array($datos)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'mensaje' => 'Datos del requerimiento incompletos.']);
    exit();
}

$mesBase   = (int)($datos['mes_base']    ?? 0);
$anioBase  = (int)($datos['anio_base']   ?? 0);
$mesDest   = (int)($datos['mes_destino'] ?? 0);
$anioDest  = (int)($datos['anio_destino']?? 0);

$almacenes = $datos['almacenes'] ?? null;
if (!$almacenes && !empty($datos['lecherias'])) {
    $almacenes = [['almacen' => $datos['almacen'] ?? '', 'lecherias' => $datos['lecherias']]];
}

if ($mesBase < 1 || $mesBase > 12 || $anioBase < 2000 || empty($almacenes)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'mensaje' => 'Parámetros del requerimiento inválidos o sin almacenes.']);
    exit();
}

// --- Snapshot JSON (respaldo) ------------------------------------------------
$baseDir = __DIR__ . '/../datos/promotores/requerimientos';
if (!is_dir($baseDir)) @mkdir($baseDir, 0775, true);
$slug = preg_replace('/[^A-Za-z0-9]/', '_', $_SESSION['usuario']);
$nombreArchivo = sprintf('req_%04d_%02d_%s.json', $anioBase, $mesBase, $slug);
$ruta = $baseDir . '/' . $nombreArchivo;
@file_put_contents($ruta, json_encode([
    'usuario'     => $_SESSION['usuario'],
    'guardado_en' => date('c'),
    'datos'       => $datos,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

// --- Persistencia en BD ------------------------------------------------------
$promotorId = (int)($_SESSION['clave_promotor'] ?? $_SESSION['clave_rol'] ?? 0);
$usuario    = (string)$_SESSION['usuario'];

try {
    $pdo = Database::getInstance();
    RequerimientoDotacionSchema::asegurarTabla($pdo);

    // Firebird soporta UPDATE OR INSERT con MATCHING.
    $sql = "UPDATE OR INSERT INTO REQUERIMIENTO_DOTACION (
                CLAVE_LECHERIA, MES_BASE, ANIO_BASE,
                PROMOTOR, MES_DESTINO, ANIO_DESTINO,
                FAMILIAS, BENEFICIARIOS, DOTACION_TEORICA,
                INV_INICIAL, SURTIMIENTO, VENTAS, INV_FINAL,
                REQ_MS_ANTERIOR, VMS, REQ_ACTUAL,
                OBSERVACIONES, FECHA_CAPTURA, USUARIO_CAPTURA
            ) VALUES (
                :clave, :mes_b, :anio_b,
                :prom, :mes_d, :anio_d,
                :fam, :ben, :dot,
                :ii, :surt, :ven, :ifn,
                :rma, :vms, :ra,
                :obs, CURRENT_TIMESTAMP, :usr
            )
            MATCHING (CLAVE_LECHERIA, MES_BASE, ANIO_BASE)";
    $stmt = $pdo->prepare($sql);

    $totalLech = 0;
    $errores   = [];

    // Defensivo: Firebird+PDO puede dejar transacciones implícitas vivas
    // (sobre todo después de DDL). Sólo iniciamos una explícita si no hay
    // una ya activa; al final hacemos commit en cualquier caso.
    $txPropia = false;
    if (!$pdo->inTransaction()) {
        try {
            $pdo->beginTransaction();
            $txPropia = true;
        } catch (PDOException $e) {
            // Ya había una activa pese a inTransaction()==false: seguimos.
            $txPropia = false;
        }
    }

    foreach ($almacenes as $bloque) {
        $lecherias = $bloque['lecherias'] ?? [];
        foreach ($lecherias as $l) {
            $clave = trim((string)($l['punto_venta'] ?? ''));
            if ($clave === '') continue;

            try {
                $stmt->execute([
                    ':clave'  => $clave,
                    ':mes_b'  => $mesBase,
                    ':anio_b' => $anioBase,
                    ':prom'   => $promotorId ?: null,
                    ':mes_d'  => $mesDest ?: null,
                    ':anio_d' => $anioDest ?: null,
                    ':fam'    => (int)($l['familias']         ?? 0),
                    ':ben'    => (int)($l['beneficiarios']    ?? 0),
                    ':dot'    => (int)($l['dotacion_teorica'] ?? 0),
                    ':ii'     => mb_substr((string)($l['inv_inicial'] ?? ''), 0, 30),
                    ':surt'   => (int)($l['surtimiento']      ?? 0),
                    ':ven'    => mb_substr((string)($l['ventas']      ?? ''), 0, 30),
                    ':ifn'    => mb_substr((string)($l['inv_final']   ?? ''), 0, 30),
                    ':rma'    => (int)($l['req_ms_anterior']  ?? 0),
                    ':vms'    => (int)($l['vms']              ?? 0),
                    ':ra'     => (int)($l['req_actual']       ?? 0),
                    ':obs'    => mb_substr((string)($l['observaciones'] ?? ''), 0, 500),
                    ':usr'    => mb_substr($usuario, 0, 50),
                ]);
                $totalLech++;
            } catch (Exception $eFila) {
                $errores[] = [
                    'lecheria' => $clave,
                    'error'    => $eFila->getMessage(),
                ];
            }
        }
    }
    if ($pdo->inTransaction()) {
        $pdo->commit();
    }

    echo json_encode([
        'status'    => 'success',
        'mensaje'   => 'Requerimiento guardado.',
        'archivo'   => $nombreArchivo,
        'almacenes' => count($almacenes),
        'lecherias' => $totalLech,
        'errores'   => $errores,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'mensaje' => 'No se pudo guardar en BD: ' . $e->getMessage(),
    ]);
}
