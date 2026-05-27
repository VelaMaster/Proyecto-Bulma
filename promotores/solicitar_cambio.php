<?php
/**
 * solicitar_cambio.php
 * POST JSON: { tipo, clave_lecheria, mes, anio, motivo }
 * Crea una solicitud de cambio para que el supervisor edite el reporte/requerimiento.
 */
require_once __DIR__ . '/../includes/session_guard.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    http_response_code(401);
    echo json_encode(['error' => true, 'mensaje' => 'Sesión no válida']);
    exit;
}

$json = file_get_contents('php://input');
$d    = json_decode($json, true);
if (!is_array($d)) {
    http_response_code(400);
    echo json_encode(['error' => true, 'mensaje' => 'Datos inválidos']);
    exit;
}

$tipo          = in_array($d['tipo'] ?? '', ['reporte', 'requerimiento'], true) ? $d['tipo'] : null;
$clave_lecheria = trim($d['clave_lecheria'] ?? '');
$mes           = (int)($d['mes']  ?? 0);
$anio          = (int)($d['anio'] ?? 0);
$motivo        = mb_substr(trim($d['motivo'] ?? ''), 0, 500);

if (!$tipo || $clave_lecheria === '' || $mes < 1 || $mes > 12 || $anio < 2000) {
    http_response_code(422);
    echo json_encode(['error' => true, 'mensaje' => 'Parámetros inválidos']);
    exit;
}

require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';
require_once __DIR__ . '/../Database.php';

try {
    // Obtener supervisor de la lechería desde Firebird
    $supervisor_clave = null;
    try {
        $pdo = Database::getInstance();
        $stmtSup = $pdo->prepare("SELECT FIRST 1 M.ID_SUPERVISOR
            FROM MAPEO_SUPERVISOR_LECHERIA M WHERE TRIM(M.LECHER) = ?");
        $stmtSup->execute([$clave_lecheria]);
        $supRow = $stmtSup->fetch(PDO::FETCH_ASSOC);
        if ($supRow) $supervisor_clave = (int)$supRow['ID_SUPERVISOR'];
    } catch (Throwable $ignored) {}

    $db   = DatabaseSQLite::getInstance();

    // Evitar duplicados pendientes
    $stmtDup = $db->prepare("SELECT id FROM solicitudes_cambio
        WHERE tipo = ? AND clave_lecheria = ? AND mes = ? AND anio = ? AND promotor_usr = ?
          AND estado IN ('pendiente','en_proceso') LIMIT 1");
    $stmtDup->execute([$tipo, $clave_lecheria, $mes, $anio, $_SESSION['usuario']]);
    if ($stmtDup->fetch()) {
        echo json_encode(['success' => false, 'mensaje' => 'Ya tienes una solicitud pendiente para este registro.']);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO solicitudes_cambio
        (tipo, clave_lecheria, mes, anio, promotor_usr, supervisor_clave, motivo)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$tipo, $clave_lecheria, $mes, $anio,
                    $_SESSION['usuario'], $supervisor_clave, $motivo]);

    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'mensaje' => 'Error: ' . $e->getMessage()]);
}
