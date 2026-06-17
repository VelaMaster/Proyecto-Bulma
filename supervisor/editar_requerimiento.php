<?php
// Permite al supervisor editar el req_actual de una lechería
// SOLO si el promotor ya capturó su requerimiento (no estimado).
require_once __DIR__ . '/../includes/session_guard.php';
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    http_response_code(401);
    echo json_encode(['status'=>'error','message'=>'Acceso denegado.']);
    exit;
}
$idSup = (int)($_SESSION['clave_rol'] ?? 0);
$usr   = (string)$_SESSION['usuario'];

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$mes   = (int)($in['mes']   ?? 0);
$anio  = (int)($in['anio']  ?? 0);
$clave = trim((string)($in['clave'] ?? ''));
$nuevo = (int)($in['cantidad'] ?? 0);

if ($mes < 1 || $mes > 12 || $anio < 2000 || $clave === '' || $nuevo < 0) {
    http_response_code(422);
    echo json_encode(['status'=>'error','message'=>'Parámetros inválidos.']);
    exit;
}

try {
    $db = DatabaseSQLite::getInstance();

    // Verificar que la lechería pertenece al supervisor
    $chk = $db->prepare(
        "SELECT 1 FROM mapeo_supervisor_lecheria
         WHERE ID_SUPERVISOR = :sup AND LECHER = :k LIMIT 1"
    );
    $chk->execute([':sup'=>$idSup, ':k'=>$clave]);
    if (!$chk->fetchColumn()) {
        http_response_code(403);
        echo json_encode(['status'=>'error','message'=>'Lechería no pertenece a tu padrón.']);
        exit;
    }

    // Verificar que existe un registro capturado por el promotor (no estimado)
    $reg = $db->prepare(
        "SELECT req_actual, COALESCE(aprobado,0) AS aprobado
         FROM requerimiento_dotacion
         WHERE clave_lecheria = :k AND mes_base = :mes AND anio_base = :anio
         LIMIT 1"
    );
    $reg->execute([':k'=>$clave, ':mes'=>$mes, ':anio'=>$anio]);
    $row = $reg->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['status'=>'error','message'=>'El promotor aún no ha capturado requerimiento para esta lechería.']);
        exit;
    }

    // Actualizar
    $upd = $db->prepare(
        "UPDATE requerimiento_dotacion
            SET req_actual = :nuevo,
                supervisor_aprobador = :usr,
                fecha_aprobacion = datetime('now','localtime')
          WHERE clave_lecheria = :k AND mes_base = :mes AND anio_base = :anio"
    );
    $upd->execute([':nuevo'=>$nuevo, ':usr'=>$usr, ':k'=>$clave, ':mes'=>$mes, ':anio'=>$anio]);

    echo json_encode([
        'status'   => 'success',
        'clave'    => $clave,
        'anterior' => (int)$row['req_actual'],
        'nuevo'    => $nuevo,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
