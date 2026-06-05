<?php
// supervisor/autorizar_mes.php
// POST: mes, anio  → Autoriza el cierre del mes para Distribución
// GET : mes, anio  → Devuelve estado de autorización (autorizado | pendiente) + conteo lecherías
require_once __DIR__ . '/../includes/session_guard.php';
session_write_close();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado.']);
    exit();
}

$supClave = (int)($_SESSION['clave_rol'] ?? 0);
$supUsr   = (string)($_SESSION['usuario'] ?? '');
if ($supClave <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión sin clave_rol.']);
    exit();
}

$mes  = isset($_REQUEST['mes'])  ? (int)$_REQUEST['mes']  : 0;
$anio = isset($_REQUEST['anio']) ? (int)$_REQUEST['anio'] : 0;
if ($mes < 1 || $mes > 12 || $anio < 2000) {
    echo json_encode(['status' => 'error', 'message' => 'Mes/año inválidos.']);
    exit();
}

try {
    $pdo = DatabaseSQLite::getInstance();
    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($metodo === 'POST') {
        // Contar lecherías del supervisor que tienen reporte capturado ese mes
        $sqlCount = "
            SELECT COUNT(DISTINCT R.clave_lecheria) AS n
            FROM reporte_mensual_lecher R
            JOIN mapeo_supervisor_lecheria M
              ON M.LECHER = CAST(R.clave_lecheria AS INTEGER)
            WHERE M.ID_SUPERVISOR = :sup
              AND R.mes = :mes AND R.anio = :anio
        ";
        $st = $pdo->prepare($sqlCount);
        $st->execute([':sup' => $supClave, ':mes' => $mes, ':anio' => $anio]);
        $n = (int)($st->fetchColumn() ?: 0);

        $sqlIns = "
            INSERT INTO cierre_mes_supervisor
                (supervisor_clave, supervisor_usr, mes, anio, total_lecherias)
            VALUES (:sup, :usr, :mes, :anio, :n)
            ON CONFLICT(supervisor_clave, mes, anio) DO UPDATE SET
                total_lecherias    = excluded.total_lecherias,
                fecha_autorizacion = datetime('now','localtime'),
                supervisor_usr     = excluded.supervisor_usr
        ";
        $pdo->prepare($sqlIns)->execute([
            ':sup' => $supClave, ':usr' => $supUsr,
            ':mes' => $mes, ':anio' => $anio, ':n' => $n
        ]);

        echo json_encode([
            'status' => 'ok',
            'autorizado' => true,
            'total_lecherias' => $n,
            'fecha' => date('Y-m-d H:i:s'),
            'mes' => $mes, 'anio' => $anio
        ]);
        exit();
    }

    // GET → estado
    $st = $pdo->prepare("
        SELECT total_lecherias, fecha_autorizacion
        FROM cierre_mes_supervisor
        WHERE supervisor_clave = :sup AND mes = :mes AND anio = :anio
    ");
    $st->execute([':sup' => $supClave, ':mes' => $mes, ':anio' => $anio]);
    $row = $st->fetch();
    echo json_encode([
        'status' => 'ok',
        'autorizado' => (bool)$row,
        'total_lecherias' => $row['total_lecherias'] ?? 0,
        'fecha' => $row['fecha_autorizacion'] ?? null,
        'mes' => $mes, 'anio' => $anio
    ]);

} catch (\Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
