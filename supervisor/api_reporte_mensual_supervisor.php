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

$mes          = isset($_GET['mes'])        ? (int)$_GET['mes']        : 0;
$anio         = isset($_GET['anio'])       ? (int)$_GET['anio']       : 0;
$filtroAlm    = isset($_GET['almacen'])    ? trim($_GET['almacen'])    : '';
$filtroPromot = isset($_GET['promotor'])   ? trim($_GET['promotor'])   : '';
$filtroTipo   = isset($_GET['tipo_precio'])? $_GET['tipo_precio']      : '';   // '','0','1','2'

if ($mes < 1 || $mes > 12 || $anio < 2000) {
    echo json_encode(['status' => 'error', 'message' => 'Mes/año inválido.']);
    exit();
}

try {
    $pdo = DatabaseSQLite::getInstance();

    // 1) Lecherías del supervisor — todas las asignadas vía MAPEO (SQLite espejo)
    $sql = "
        SELECT TRIM(CAST(L.LECHER AS TEXT)) AS LECHER,
               TRIM(L.NUM_TIENDA)           AS NUM_TIENDA,
               TRIM(L.ALMACEN_RURAL)        AS ALMACEN,
               L.TIPO_PUNTO_VENTA           AS TIPO_PUNTO_VENTA,
               TRIM(P.PMT_NOMBRE)           AS PROMOTOR_NOMBRE,
               L.PROMOTOR                   AS PROMOTOR_ID
        FROM lecheria L
        JOIN promotor P ON P.PMT_NUMERO = L.PROMOTOR
        WHERE P.PMT_ACTIVO = 'S'
          AND COALESCE(L.EN_OPERACION, 0) = 0
          AND EXISTS (
                SELECT 1 FROM mapeo_supervisor_lecheria M
                WHERE M.ID_SUPERVISOR = :id_sup
                  AND M.LECHER = L.LECHER
              )
        ORDER BY TRIM(L.ALMACEN_RURAL), L.LECHER
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_sup' => $id_supervisor]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // -- Catálogos para los filtros (todos los valores únicos)
    $catAlmacenes = [];
    $catPromotores = [];
    foreach ($rows as $r) {
        $alm = strtoupper(trim((string)$r['ALMACEN']));
        if ($alm && !in_array($alm, $catAlmacenes)) $catAlmacenes[] = $alm;
        $pKey = (int)$r['PROMOTOR_ID'];
        $pNom = mb_convert_encoding(trim((string)$r['PROMOTOR_NOMBRE']), 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');
        if ($pKey && !isset($catPromotores[$pKey])) $catPromotores[$pKey] = $pNom;
    }
    sort($catAlmacenes);
    ksort($catPromotores);

    // -- Aplicar filtros al dataset
    if ($filtroAlm !== '') {
        $rows = array_filter($rows, fn($r) => strtoupper(trim((string)$r['ALMACEN'])) === strtoupper($filtroAlm));
    }
    if ($filtroPromot !== '') {
        $rows = array_filter($rows, fn($r) => (int)$r['PROMOTOR_ID'] === (int)$filtroPromot);
    }
    if ($filtroTipo !== '') {
        $rows = array_filter($rows, fn($r) => (int)$r['TIPO_PUNTO_VENTA'] === (int)$filtroTipo);
    }

    // 2) Capturas reales desde INVENTARIOS_MENSUALES — mapa clave_lecheria → row
    $stmtSQ = $pdo->prepare(
        "SELECT * FROM inventarios_mensuales
         WHERE MES_PERIODO = :mes AND ANIO_PERIODO = :anio"
    );
    $stmtSQ->execute([':mes' => $mes, ':anio' => $anio]);
    $reportes = [];
    foreach ($stmtSQ->fetchAll(PDO::FETCH_ASSOC) as $rq) {
        $reportes[trim((string)$rq['CLAVE_LECHERIA'])] = $rq;
    }

    // 3) Nombre del supervisor
    $stmtN = $pdo->prepare("SELECT NOMBRE FROM usuarios_inventarios
                            WHERE CLAVE_ROL = :id AND ROL = '1' LIMIT 1");
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
            'tipo_punto_venta' => $tipo,
            'promotor_id'      => (int)$r['PROMOTOR_ID'],
            'promotor_nombre'  => mb_convert_encoding(trim((string)$r['PROMOTOR_NOMBRE']), 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252'),
            'capturado'        => $capturado,
            'inv_ini_cajas'    => $capturado ? (int)$rep['INV_INI_CAJA']     : null,
            'inv_ini_sobres'   => $capturado ? (int)$rep['INV_INI_SOBRES']   : null,
            'dot_recib_cajas'  => $capturado ? (int)$rep['SURT_CAJAS']       : null,
            'dot_recib_sobres' => $capturado ? 0                              : null,
            'total_cajas'      => $capturado ? (int)$rep['ABASTO_CAJA']      : null,
            'total_sobres'     => $capturado ? (int)$rep['ABASTO_SOBRES']    : null,
            'vend_cajas'       => $capturado ? (int)$rep['VENTA_CAJA']       : null,
            'vend_sobres'      => $capturado ? (int)$rep['VENTA_SOBRES']     : null,
            'inv_fin_cajas'    => $capturado ? (int)$rep['FIN_CAJA']         : null,
            'inv_fin_sobres'   => $capturado ? (int)$rep['FIN_SOBRES']       : null,
            'retiro_cajas'     => $capturado ? (int)$rep['REG_CAJA']         : null,
            'retiro_sobres'    => $capturado ? (int)$rep['REG_SOBRES']       : null,
            'familias_no_acud' => null,
            'sobres_rotos'     => null,
            'sobres_falt'      => null,
            'observaciones'    => null,
            'promotor'         => $capturado ? (string)$rep['USUARIO_CAPTURA']: null,
            'fecha_captura'    => $capturado ? (string)$rep['FECHA_CAPTURA'] : null,
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

    $promotoresOut = [];
    foreach ($catPromotores as $id => $nom) {
        $promotoresOut[] = ['id' => $id, 'nombre' => $nom];
    }

    $resp = [
        'status'           => 'success',
        'mes'              => $mes,
        'anio'             => $anio,
        'supervisor'       => ['id' => (int)$id_supervisor, 'nombre' => $nombreSup],
        'almacenes'        => $almacenesOut,
        'total_lecherias'  => $totalLech,
        'total_capturadas' => $totalCapt,
        'cat_almacenes'    => $catAlmacenes,
        'cat_promotores'   => $promotoresOut,
        'filtros_activos'  => [
            'almacen'    => $filtroAlm,
            'promotor'   => $filtroPromot,
            'tipo_precio'=> $filtroTipo,
        ],
    ];

    array_walk_recursive($resp, function (&$v) {
        if (is_string($v))
            $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
    });

    echo json_encode($resp, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
