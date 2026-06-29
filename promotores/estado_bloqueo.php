<?php
/**
 * estado_bloqueo.php
 * GET: tipo=reporte|requerimiento  mes=<1-12>  anio=<YYYY>  [clave_lecheria=<n>]
 * Devuelve { bloqueado: true|false, existe: true|false }
 * Si se pasa clave_lecheria, consulta esa fila exacta (comportamiento nuevo,
 * congruente con el bloqueo por lechería). Sin clave_lecheria, devuelve MAX
 * para indicar si AL MENOS UNA fila del usuario+mes está bloqueada (legacy).
 */
require_once __DIR__ . '/../includes/session_guard.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    http_response_code(401);
    echo json_encode(['error' => true]);
    exit;
}

$tipo  = trim($_GET['tipo']  ?? '');
$mes   = (int)($_GET['mes']  ?? 0);
$anio  = (int)($_GET['anio'] ?? 0);
$clave = trim((string)($_GET['clave_lecheria'] ?? ''));

if (!in_array($tipo, ['reporte', 'requerimiento'], true) || $mes < 1 || $mes > 12 || $anio < 2000) {
    http_response_code(400);
    echo json_encode(['error' => true, 'mensaje' => 'Parámetros inválidos']);
    exit;
}

require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

try {
    $db  = DatabaseSQLite::getInstance();
    $usr = $_SESSION['usuario'];

    if ($tipo === 'reporte') {
        if ($clave !== '') {
            $stmt = $db->prepare("SELECT bloqueado AS b, 1 AS n
                FROM reporte_mensual_lecher
                WHERE clave_lecheria = ? AND mes = ? AND anio = ? LIMIT 1");
            $stmt->execute([$clave, $mes, $anio]);
        } else {
            $stmt = $db->prepare("SELECT MAX(bloqueado) AS b, COUNT(*) AS n
                FROM reporte_mensual_lecher WHERE usuario_captura = ? AND mes = ? AND anio = ?");
            $stmt->execute([$usr, $mes, $anio]);
        }
    } else {
        $promotorId = (int)($_SESSION['clave_promotor'] ?? $_SESSION['clave_rol'] ?? 0);
        if ($clave !== '') {
            $stmt = $db->prepare("SELECT bloqueado AS b, 1 AS n
                FROM requerimiento_dotacion
                WHERE clave_lecheria = ? AND mes_base = ? AND anio_base = ? LIMIT 1");
            $stmt->execute([$clave, $mes, $anio]);
        } else {
            $stmt = $db->prepare("SELECT MAX(bloqueado) AS b, COUNT(*) AS n
                FROM requerimiento_dotacion WHERE promotor = ? AND mes_base = ? AND anio_base = ?");
            $stmt->execute([$promotorId, $mes, $anio]);
        }
    }
    $row = $stmt->fetch();

    echo json_encode([
        'bloqueado' => (bool)($row['b'] ?? false),
        'existe'    => (int)($row['n'] ?? 0) > 0,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'mensaje' => $e->getMessage()]);
}
