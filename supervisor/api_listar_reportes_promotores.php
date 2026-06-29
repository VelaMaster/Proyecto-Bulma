<?php
// supervisor/api_listar_reportes_promotores.php
// Lista los reportes mensuales generados por los promotores asignados al supervisor.
// GET opcional: mes, anio
// Devuelve un agregado por (usuario_captura, mes, anio): promotor, total lecherias, aprobado, fecha.
require_once __DIR__ . '/../includes/session_guard.php';
session_write_close();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'mensaje' => 'Acceso denegado.']);
    exit();
}

$id_supervisor = $_SESSION['clave_rol'] ?? null;
if (!$id_supervisor) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'mensaje' => 'Supervisor sin clave_rol.']);
    exit();
}

// Por defecto: mes y año actuales
$mes  = isset($_GET['mes'])  ? (int)$_GET['mes']  : (int)date('n');
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');

try {
    $pdo = DatabaseSQLite::getInstance();

    // 1) Promotores asignados al supervisor (vía mapeo) — usa la tabla
    //    espejo `promotor` (Firebird), no `usuarios_inventarios`, igual
    //    que api_reporte_mensual_supervisor.php para incluir TODOS los
    //    promotores activos aunque no tengan usuario_inventarios.
    $sqlUsr = "
        SELECT P.PMT_NUMERO              AS PMT_NUMERO,
               TRIM(P.PMT_NOMBRE)        AS PMT_NOMBRE,
               COUNT(DISTINCT L.LECHER)  AS total_lech,
               (SELECT U.USUARIO
                  FROM usuarios_inventarios U
                 WHERE U.CLAVE_ROL = P.PMT_NUMERO
                   AND U.ROL IN ('0','promotor')
                 LIMIT 1)                AS USUARIO
        FROM promotor P
        JOIN lecheria L ON L.PROMOTOR = P.PMT_NUMERO
        JOIN mapeo_supervisor_lecheria M
              ON M.LECHER = L.LECHER AND M.ID_SUPERVISOR = :sup
        WHERE COALESCE(P.PMT_ACTIVO,'S') = 'S'
          AND COALESCE(L.EN_OPERACION, 0) = 0
        GROUP BY P.PMT_NUMERO, P.PMT_NOMBRE
        ORDER BY TRIM(P.PMT_NOMBRE)
    ";
    $st = $pdo->prepare($sqlUsr);
    $st->execute([':sup' => $id_supervisor]);
    $promotores = $st->fetchAll();
    if (empty($promotores)) {
        echo json_encode(['status' => 'success', 'mes' => $mes, 'anio' => $anio, 'reportes' => []]);
        exit();
    }

    // 2) Avance del INVENTARIO MENSUAL — cuántas lecherías por promotor
    //    ya capturaron en INVENTARIOS_MENSUALES para ese mes/año.
    $stmtInv = $pdo->prepare("
        SELECT L.PROMOTOR AS PROMOTOR, COUNT(DISTINCT IM.CLAVE_LECHERIA) AS capt
        FROM inventarios_mensuales IM
        JOIN lecheria L
          ON (L.LECHER = IM.CLAVE_LECHERIA
              OR (CAST(L.LECHER AS TEXT) || '00') = IM.CLAVE_LECHERIA)
        WHERE IM.MES_PERIODO = :mes AND IM.ANIO_PERIODO = :anio
        GROUP BY L.PROMOTOR
    ");
    $stmtInv->execute([':mes' => $mes, ':anio' => $anio]);
    $invMap = [];
    foreach ($stmtInv->fetchAll() as $r) {
        $invMap[(int)$r['PROMOTOR']] = (int)$r['capt'];
    }

    // 3) Reportes guardados (reporte_mensual_lecher) por usuario_captura
    //    — busca tanto el USUARIO real como el placeholder "promotor_<id>".
    $claves = [];
    foreach ($promotores as $p) {
        if (!empty($p['USUARIO'])) $claves[] = $p['USUARIO'];
        $claves[] = 'promotor_' . $p['PMT_NUMERO'];
    }
    $reportesMap = [];
    if (!empty($claves)) {
        $placeholders = implode(',', array_fill(0, count($claves), '?'));
        $params       = $claves;
        $params[]     = $mes;
        $params[]     = $anio;
        $sqlRep = "
            SELECT usuario_captura,
                   COUNT(*)                  AS total_lecherias,
                   MAX(bloqueado)            AS bloqueado,
                   MAX(aprobado)             AS aprobado,
                   MAX(fecha_captura)        AS fecha_captura,
                   MAX(fecha_aprobacion)     AS fecha_aprobacion,
                   MAX(supervisor_aprobador) AS supervisor_aprobador
            FROM reporte_mensual_lecher
            WHERE usuario_captura IN ($placeholders)
              AND mes = ? AND anio = ?
            GROUP BY usuario_captura
        ";
        $st = $pdo->prepare($sqlRep);
        $st->execute($params);
        foreach ($st->fetchAll() as $r) {
            $reportesMap[$r['usuario_captura']] = $r;
        }
    }

    // 4) Unir: cada promotor aparece SIEMPRE, con o sin captura
    $out = [];
    foreach ($promotores as $p) {
        $pid  = (int)$p['PMT_NUMERO'];
        $usr  = $p['USUARIO'] ?? null;
        $rep  = ($usr && isset($reportesMap[$usr]))
                ? $reportesMap[$usr]
                : ($reportesMap['promotor_' . $pid] ?? null);
        $nom  = $p['PMT_NOMBRE'] !== '' ? $p['PMT_NOMBRE'] : ('Promotor #' . $pid);
        $captInv = $invMap[$pid] ?? 0;
        $out[] = [
            'usuario_captura'      => $usr,
            'promotor_id'          => $pid,
            'promotor_nombre'      => mb_convert_encoding($nom, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252'),
            'mes'                  => $mes,
            'anio'                 => $anio,
            'total_padron'         => (int)$p['total_lech'],
            'capturadas_inv'       => $captInv,             // avance del INVENTARIO MENSUAL
            'total_lecherias'      => $rep ? (int)$rep['total_lecherias']  : 0,
            'bloqueado'            => $rep ? (int)$rep['bloqueado']        : 0,
            'aprobado'             => $rep ? (int)$rep['aprobado']         : 0,
            'fecha_captura'        => $rep['fecha_captura']        ?? null,
            'fecha_aprobacion'     => $rep['fecha_aprobacion']     ?? null,
            'supervisor_aprobador' => $rep['supervisor_aprobador'] ?? null,
        ];
    }

    echo json_encode([
        'status'   => 'success',
        'mes'      => $mes,
        'anio'     => $anio,
        'reportes' => $out,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage()]);
}
