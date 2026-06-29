<?php
// promotores/enviar_inventario.php
// RF-14: marca un inventario mensual como ENVIADO y lo bloquea para edición
// posterior. El supervisor podrá aprobar/validar y, en caso de error, el
// promotor solicitará un cambio vía /promotores/solicitar_cambio.php.
//
// POST JSON: { inventario_id: <int>, clave_lecheria?: <txt>, mes?: <int>, anio?: <int> }
//   - Si viene inventario_id, se usa el ID directamente.
//   - Si no, se busca por (clave_lecheria, mes, anio).
//
// Devuelve { status: 'ok'|'error'|'no_existe', mensaje, bloqueado, fecha_envio }

require_once __DIR__ . '/../includes/session_guard.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'promotor') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'mensaje' => 'No autorizado']);
    exit;
}

$d = json_decode(file_get_contents('php://input'), true) ?: [];
$id    = (int)($d['inventario_id'] ?? 0);
$clave = trim((string)($d['clave_lecheria'] ?? ''));
$mes   = (int)($d['mes']  ?? 0);
$anio  = (int)($d['anio'] ?? 0);

if ($id <= 0 && ($clave === '' || $mes < 1 || $mes > 12 || $anio < 2000)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'mensaje' => 'Faltan parámetros (inventario_id o clave+mes+anio).']);
    exit;
}

require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

try {
    $db = DatabaseSQLite::getInstance();
    $usr = $_SESSION['usuario'];

    if ($id > 0) {
        $sel = $db->prepare("SELECT ID, COALESCE(bloqueado,0) AS b, USUARIO_CAPTURA
                               FROM inventarios_mensuales WHERE ID = ?");
        $sel->execute([$id]);
    } else {
        $sel = $db->prepare("SELECT ID, COALESCE(bloqueado,0) AS b, USUARIO_CAPTURA
                               FROM inventarios_mensuales
                              WHERE CLAVE_LECHERIA = ? AND MES_PERIODO = ? AND ANIO_PERIODO = ?
                              LIMIT 1");
        $sel->execute([$clave, $mes, $anio]);
    }
    $row = $sel->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['status' => 'no_existe', 'mensaje' => 'No se encontró el inventario indicado.']);
        exit;
    }

    // Verificar propiedad: solo el promotor que capturó puede enviarlo
    if (trim((string)$row['USUARIO_CAPTURA']) !== $usr) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'mensaje' => 'Este inventario pertenece a otro promotor.']);
        exit;
    }

    if ((int)$row['b'] === 1) {
        echo json_encode([
            'status'      => 'ok',
            'mensaje'     => 'Ya estaba enviado.',
            'bloqueado'   => true,
            'fecha_envio' => null,
        ]);
        exit;
    }

    $fechaEnvio = date('Y-m-d H:i:s');
    $upd = $db->prepare("UPDATE inventarios_mensuales
                            SET bloqueado = 1, fecha_envio = ?
                          WHERE ID = ?");
    $upd->execute([$fechaEnvio, (int)$row['ID']]);

    echo json_encode([
        'status'      => 'ok',
        'mensaje'     => 'Inventario enviado correctamente. Queda bloqueado para edición.',
        'bloqueado'   => true,
        'fecha_envio' => $fechaEnvio,
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
}
