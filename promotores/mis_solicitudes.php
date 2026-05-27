<?php
/**
 * mis_solicitudes.php
 * GET              → Lista solicitudes del promotor actual
 * GET ?accion=contar_nuevas → Cuenta resueltas/rechazadas no vistas
 * POST { accion:'marcar_vistas' } → Marca como vistos todos los resueltos/rechazados
 */
require_once __DIR__ . '/../includes/session_guard.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    http_response_code(401);
    echo json_encode(['error' => true]);
    exit;
}

$usr = $_SESSION['usuario'];
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

// Asegurar columna visto_promotor (migración)
$db = DatabaseSQLite::getInstance();
try { $db->exec("ALTER TABLE solicitudes_cambio ADD COLUMN visto_promotor INTEGER DEFAULT 0"); }
catch (Throwable $ignored) {}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $accion = trim($_GET['accion'] ?? '');

    if ($accion === 'contar_nuevas') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM solicitudes_cambio
            WHERE promotor_usr = ? AND estado IN ('resuelto','rechazado') AND visto_promotor = 0");
        $stmt->execute([$usr]);
        echo json_encode(['count' => (int)$stmt->fetchColumn()]);
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM solicitudes_cambio WHERE promotor_usr = ?
        ORDER BY fecha_solicitud DESC LIMIT 100");
    $stmt->execute([$usr]);
    echo json_encode(['success' => true, 'solicitudes' => $stmt->fetchAll()]);
    exit;
}

if ($method === 'POST') {
    $d = json_decode(file_get_contents('php://input'), true);
    if (($d['accion'] ?? '') === 'marcar_vistas') {
        $db->prepare("UPDATE solicitudes_cambio SET visto_promotor = 1
            WHERE promotor_usr = ? AND estado IN ('resuelto','rechazado') AND visto_promotor = 0")
           ->execute([$usr]);
        echo json_encode(['success' => true]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => true]);
