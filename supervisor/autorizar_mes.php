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
        // HALLAZGO #6: Contamos por separado las lecherías con INVENTARIO y con
        // REPORTE formal (que es lo que finalmente lee el OPE). Autorizamos por
        // el total UNIÓN, pero devolvemos AMBOS números para que el supervisor
        // vea si está cerrando con datos por fallback.
        $sqlCount = "
            WITH lech_sup AS (
                SELECT DISTINCT CAST(M.LECHER AS TEXT) AS k
                  FROM mapeo_supervisor_lecheria M
                 WHERE M.ID_SUPERVISOR = :sup
            )
            SELECT
                (SELECT COUNT(*) FROM lech_sup
                  WHERE k IN (SELECT TRIM(CAST(CLAVE_LECHERIA AS TEXT))
                                FROM inventarios_mensuales
                               WHERE MES_PERIODO = :mes AND ANIO_PERIODO = :anio))   AS n_inv,
                (SELECT COUNT(*) FROM lech_sup
                  WHERE k IN (SELECT TRIM(CAST(clave_lecheria AS TEXT))
                                FROM reporte_mensual_lecher
                               WHERE mes = :mes AND anio = :anio))                    AS n_rep,
                (SELECT COUNT(*) FROM lech_sup
                  WHERE k IN (SELECT TRIM(CAST(CLAVE_LECHERIA AS TEXT))
                                FROM inventarios_mensuales
                               WHERE MES_PERIODO = :mes AND ANIO_PERIODO = :anio)
                     OR k IN (SELECT TRIM(CAST(clave_lecheria AS TEXT))
                                FROM reporte_mensual_lecher
                               WHERE mes = :mes AND anio = :anio))                    AS n_union
        ";
        $st = $pdo->prepare($sqlCount);
        $st->execute([':sup' => $supClave, ':mes' => $mes, ':anio' => $anio]);
        $cnt   = $st->fetch(PDO::FETCH_ASSOC) ?: ['n_inv'=>0,'n_rep'=>0,'n_union'=>0];
        $nInv  = (int)$cnt['n_inv'];
        $nRep  = (int)$cnt['n_rep'];
        $n     = (int)$cnt['n_union'];

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
            'status'                => 'ok',
            'autorizado'            => true,
            'total_lecherias'       => $n,
            'con_reporte_formal'    => $nRep,
            'solo_inventario'       => max(0, $n - $nRep),
            'con_inventario'        => $nInv,
            'advertencia_fallback'  => ($nRep < $n)
                ? "Hay $n - $nRep lecherías que sólo tienen inventario, sin reporte mensual formal. " .
                  "El OPE las incluirá usando los datos del inventario como fallback."
                : null,
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
