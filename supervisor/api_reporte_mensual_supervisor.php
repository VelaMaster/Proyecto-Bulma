<?php
// supervisor/api_reporte_mensual_supervisor.php
// Devuelve el reporte mensual de lecherías a cargo del supervisor (desde SQLite).
// GET: mes, anio
// Respuesta: { status, mes, anio, supervisor:{id,nombre},
//              almacenes:[{almacen, lecherias:[{...}], capturadas, total}],
//              total_lecherias, total_capturadas }
require_once __DIR__ . '/../includes/session_guard.php';
session_write_close();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado.']);
    exit();
}
$id_supervisor = $_SESSION['clave_rol'] ?? null;
if (!$id_supervisor) {
    echo json_encode(['status' => 'error', 'message' => 'ID de supervisor no encontrado.']);
    exit();
}

$mes  = isset($_GET['mes'])  ? (int)$_GET['mes']  : 0;
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : 0;
if ($mes < 1 || $mes > 12 || $anio < 2000) {
    echo json_encode(['status' => 'error', 'message' => 'Mes/año inválido.']);
    exit();
}

try {
    $pdo = Database::getInstance();

    // 1) Lecherías del supervisor (Firebird) — todas las asignadas vía MAPEO
    $sql = "
        SELECT TRIM(L.LECHER)        AS LECHER,
               TRIM(L.NUM_TIENDA)    AS NUM_TIENDA,
               TRIM(L.ALMACEN_RURAL) AS ALMACEN,
               L.TIPO_PUNTO_VENTA    AS TIPO_PUNTO_VENTA
        FROM LECHERIA L
        JOIN PROMOTOR P ON P.PMT_NUMERO = L.PROMOTOR
        WHERE P.PMT_ACTIVO = 'S'
          AND COALESCE(L.EN_OPERACION, 0) = 0
          AND EXISTS (
                SELECT 1 FROM MAPEO_SUPERVISOR_LECHERIA M
                WHERE M.ID_SUPERVISOR = :id_sup
                  AND TRIM(M.LECHER) = TRIM(L.LECHER)
              )
        ORDER BY TRIM(L.ALMACEN_RURAL), TRIM(L.LECHER)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_sup' => $id_supervisor]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2) Reporte mensual desde SQLite — mapa clave_lecheria → row
    $sqlite = DatabaseSQLite::getInstance();
    $stmtSQ = $sqlite->prepare(
        "SELECT * FROM reporte_mensual_lecher
         WHERE mes = :mes AND anio = :anio"
    );
    $stmtSQ->execute([':mes' => $mes, ':anio' => $anio]);
    $reportes = [];
    foreach ($stmtSQ->fetchAll() as $rq) {
        $reportes[trim((string)$rq['clave_lecheria'])] = $rq;
    }

    // 3) Nombre del supervisor
    $stmtN = $pdo->prepare("SELECT FIRST 1 NOMBRE FROM USUARIOS_INVENTARIOS
                            WHERE CLAVE_ROL = :id AND ROL = '1'");
    $stmtN->execute([':id' => $id_supervisor]);
    $nombreSup = trim((string)$stmtN->fetchColumn());
    if ($nombreSup === '') $nombreSup = 'Supervisor #' . $id_supervisor;

    // 4) Agrupar por almacén
    $almacenes   = [];
    $totalLech   = 0;
    $totalCapt   = 0;

    foreach ($rows as $r) {
        $tipo = (int)$r['TIPO_PUNTO_VENTA'];
        $alm  = strtoupper(trim((string)$r['ALMACEN']));
        if ($alm === '') $alm = '(SIN ALMACÉN)';

        if (!isset($almacenes[$alm])) {
            $almacenes[$alm] = [
                'almacen'    => $alm,
                'lecherias'  => [],
                'capturadas' => 0,
                'total'      => 0,
            ];
        }

        $k = trim((string)$r['LECHER']);
        $capturado = isset($reportes[$k]);
        $rep = $capturado ? $reportes[$k] : null;

        $numTiendaRaw     = trim((string)$r['NUM_TIENDA']);
        $numTiendaMostrar = ($tipo === 2 || $numTiendaRaw === '10101') ? 'DM' : $numTiendaRaw;

        $precio = '';
        if ($tipo === 0) $precio = '$4.50';
        elseif ($tipo === 1 || $tipo === 2) $precio = '$6.50';

        $almacenes[$alm]['lecherias'][] = [
            'punto_venta'      => $k,
            'num_tienda'       => $numTiendaMostrar,
            'precio'           => $precio,
            'capturado'        => $capturado,
            'inv_ini_cajas'    => $capturado ? (int)$rep['inv_ini_cajas']    : null,
            'inv_ini_sobres'   => $capturado ? (int)$rep['inv_ini_sobres']   : null,
            'dot_recib_cajas'  => $capturado ? (int)$rep['dot_recib_cajas']  : null,
            'total_cajas'      => $capturado ? (int)$rep['total_cajas']      : null,
            'total_sobres'     => $capturado ? (int)$rep['total_sobres']     : null,
            'vend_cajas'       => $capturado ? (int)$rep['vend_cajas']       : null,
            'vend_sobres'      => $capturado ? (int)$rep['vend_sobres']      : null,
            'inv_fin_cajas'    => $capturado ? (int)$rep['inv_fin_cajas']    : null,
            'inv_fin_sobres'   => $capturado ? (int)$rep['inv_fin_sobres']   : null,
            'retiro_cajas'     => $capturado ? (int)$rep['retiro_cajas']     : null,
            'retiro_sobres'    => $capturado ? (int)$rep['retiro_sobres']    : null,
            'familias_no_acud' => $capturado ? (int)$rep['familias_no_acud'] : null,
            'sobres_rotos'     => $capturado ? (int)$rep['sobres_rotos']     : null,
            'sobres_falt'      => $capturado ? (int)$rep['sobres_falt']      : null,
            'observaciones'    => $capturado ? (string)$rep['observaciones'] : null,
            'promotor'         => $capturado ? (string)$rep['promotor']      : null,
            'fecha_captura'    => $capturado ? (string)$rep['fecha_captura'] : null,
        ];
        $almacenes[$alm]['total']++;
        $totalLech++;

        if ($capturado) {
            $almacenes[$alm]['capturadas']++;
            $totalCapt++;
        }
    }

    ksort($almacenes);
    $almacenesOut = array_values($almacenes);

    $resp = [
        'status'           => 'success',
        'mes'              => $mes,
        'anio'             => $anio,
        'supervisor'       => ['id' => (int)$id_supervisor, 'nombre' => $nombreSup],
        'almacenes'        => $almacenesOut,
        'total_lecherias'  => $totalLech,
        'total_capturadas' => $totalCapt,
    ];

    array_walk_recursive($resp, function (&$v) {
        if (is_string($v))
            $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
    });

    echo json_encode($resp, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
