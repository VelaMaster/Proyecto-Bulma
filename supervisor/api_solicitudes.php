<?php
/**
 * api_solicitudes.php
 * GET  → Lista solicitudes pendientes/en_proceso del supervisor actual
 * POST { accion:'resolver'|'rechazar', id, nota } → Resuelve/rechaza una solicitud
 *      { accion:'desbloquear', tipo, clave_lecheria, mes, anio, promotor_usr } → Desbloquea para que el supervisor edite
 */
require_once __DIR__ . '/../includes/session_guard.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    http_response_code(401);
    echo json_encode(['error' => true, 'mensaje' => 'Acceso denegado']);
    exit;
}

$id_supervisor = (int)($_SESSION['clave_rol'] ?? 0);
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

$method = $_SERVER['REQUEST_METHOD'];
$db     = DatabaseSQLite::getInstance();

// ── GET: listar solicitudes ───────────────────────────────────────
if ($method === 'GET') {
    $estado = $_GET['estado'] ?? 'pendiente';
    if (!in_array($estado, ['pendiente','en_proceso','resuelto','rechazado','todas'], true)) {
        $estado = 'pendiente';
    }
    if ($estado === 'todas') {
        $stmt = $db->prepare("SELECT * FROM solicitudes_cambio
            WHERE supervisor_clave = ?
            ORDER BY fecha_solicitud DESC LIMIT 200");
        $stmt->execute([$id_supervisor]);
    } else {
        $stmt = $db->prepare("SELECT * FROM solicitudes_cambio
            WHERE supervisor_clave = ? AND estado = ?
            ORDER BY fecha_solicitud DESC");
        $stmt->execute([$id_supervisor, $estado]);
    }
    echo json_encode(['success' => true, 'solicitudes' => $stmt->fetchAll()]);
    exit;
}

// ── POST: resolver / rechazar / desbloquear ───────────────────────
if ($method === 'POST') {
    $d = json_decode(file_get_contents('php://input'), true);
    if (!is_array($d)) { http_response_code(400); echo json_encode(['error' => true]); exit; }

    $accion = trim($d['accion'] ?? '');

    if ($accion === 'resolver' || $accion === 'rechazar') {
        $id    = (int)($d['id']   ?? 0);
        $nota  = mb_substr(trim($d['nota'] ?? ''), 0, 500);
        $estado = $accion === 'resolver' ? 'resuelto' : 'rechazado';

        // Validar que la solicitud pertenece a este supervisor
        $stmtV = $db->prepare("SELECT id, tipo, clave_lecheria, mes, anio, promotor_usr
            FROM solicitudes_cambio WHERE id = ? AND supervisor_clave = ?");
        $stmtV->execute([$id, $id_supervisor]);
        $sol = $stmtV->fetch();
        if (!$sol) {
            http_response_code(404);
            echo json_encode(['error' => true, 'mensaje' => 'Solicitud no encontrada']);
            exit;
        }

        $db->prepare("UPDATE solicitudes_cambio
            SET estado = ?, nota_supervisor = ?, fecha_resolucion = datetime('now','localtime')
            WHERE id = ?")->execute([$estado, $nota, $id]);

        // Si se resuelve → desbloquear el registro para permitir edición del supervisor
        if ($accion === 'resolver') {
            if ($sol['tipo'] === 'reporte') {
                $db->prepare("UPDATE reporte_mensual_lecher SET bloqueado = 0
                    WHERE clave_lecheria = ? AND mes = ? AND anio = ?")
                   ->execute([$sol['clave_lecheria'], $sol['mes'], $sol['anio']]);
            } else {
                $db->prepare("UPDATE requerimiento_dotacion SET bloqueado = 0
                    WHERE clave_lecheria = ? AND mes_base = ? AND anio_base = ?")
                   ->execute([$sol['clave_lecheria'], $sol['mes'], $sol['anio']]);
            }
        }

        echo json_encode(['success' => true]);
        exit;
    }

    // accion = contar (badge)
    if ($accion === 'contar') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM solicitudes_cambio
            WHERE supervisor_clave = ? AND estado IN ('pendiente','en_proceso')");
        $stmt->execute([$id_supervisor]);
        echo json_encode(['count' => (int)$stmt->fetchColumn()]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => true, 'mensaje' => 'Acción no reconocida']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => true]);
