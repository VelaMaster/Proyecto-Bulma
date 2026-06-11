<?php
// distribucion/api_requerimiento_global.php
// Consolida el Requerimiento de Dotación de TODOS los supervisores.
// Filtros: mes, anio, precio (4.50 | 6.50)
// Salida JSON:
// {
//   status, mes, anio, precio,
//   supervisores: [
//     { id, nombre, almacenes:[{almacen, lecherias, subtotal, capturadas, total}],
//       subtotal_supervisor, capturadas_sup, total_sup }
//   ],
//   total_general, total_lecherias, total_capturadas,
//   resumen: { supervisores, lecherias_total, lecherias_450, lecherias_650 }
// }
require_once __DIR__ . '/../includes/session_guard.php';
session_write_close();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'distribucion') {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado.']);
    exit();
}

$mes    = isset($_GET['mes'])    ? (int)$_GET['mes']    : 0;
$anio   = isset($_GET['anio'])   ? (int)$_GET['anio']   : 0;
$precio = isset($_GET['precio']) ? trim($_GET['precio']) : '6.50';

if ($mes < 1 || $mes > 12 || $anio < 2000) {
    echo json_encode(['status' => 'error', 'message' => 'Mes/año inválido.']);
    exit();
}

$precioNum = (float)str_replace(['$', ','], ['', '.'], $precio);
if (abs($precioNum - 4.50) < 0.001) {
    $precioNum = 4.50;
    $tipoVentaFiltro = [0];
} elseif (abs($precioNum - 6.50) < 0.001) {
    $precioNum = 6.50;
    $tipoVentaFiltro = [1, 2];
} else {
    echo json_encode(['status' => 'error', 'message' => 'Precio inválido (use 4.50 o 6.50).']);
    exit();
}

try {
    $pdo = DatabaseSQLite::getInstance();

    // 1) Lecherías desde SQLite con supervisor (sin JOIN a requerimiento)
    $sql = "
        SELECT
            TRIM(CAST(L.LECHER AS TEXT))   AS LECHER,
            TRIM(L.NUM_TIENDA)             AS NUM_TIENDA,
            TRIM(L.ALMACEN_RURAL)          AS ALMACEN,
            L.TIPO_PUNTO_VENTA             AS TIPO_PUNTO_VENTA,
            L.PROMOTOR                     AS PROMOTOR_ID,
            M.ID_SUPERVISOR                AS ID_SUPERVISOR,
            TRIM(U.NOMBRE)                 AS SUPERVISOR_NOMBRE
        FROM lecheria L
        JOIN promotor P ON P.PMT_NUMERO = L.PROMOTOR
        LEFT JOIN mapeo_supervisor_lecheria M ON M.LECHER = L.LECHER
        LEFT JOIN usuarios_inventarios U
               ON U.CLAVE_ROL = M.ID_SUPERVISOR
              AND U.ROL = '1'
        WHERE P.PMT_ACTIVO = 'S'
          AND COALESCE(L.EN_OPERACION, 0) = 0
        ORDER BY COALESCE(TRIM(U.NOMBRE), 'ZZZ'),
                 TRIM(L.ALMACEN_RURAL),
                 L.LECHER
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Columnas de aprobación: defensivo
    foreach (['aprobado INTEGER DEFAULT 0',
              'supervisor_aprobador TEXT',
              'fecha_aprobacion TEXT'] as $colDef) {
        try { $pdo->exec("ALTER TABLE requerimiento_dotacion ADD COLUMN $colDef"); }
        catch (Throwable $e) {}
    }

    // 2) Requerimientos capturados + estado de aprobación
    $stmtSQ = $pdo->prepare(
        "SELECT clave_lecheria, req_actual,
                COALESCE(aprobado,0) AS aprobado
         FROM requerimiento_dotacion
         WHERE mes_base = :mes AND anio_base = :anio"
    );
    $stmtSQ->execute([':mes' => $mes, ':anio' => $anio]);
    $reqs = [];
    foreach ($stmtSQ->fetchAll() as $rq) {
        $reqs[trim((string)$rq['clave_lecheria'])] = [
            'req'       => (int)$rq['req_actual'],
            'aprobado'  => (int)$rq['aprobado'],
        ];
    }

    // 2.b) Avance desde INVENTARIOS_MENSUALES: promedio últimos 3 meses para
    //      estimar requerimiento cuando el promotor aún no envía formal.
    $stmtInv = $pdo->prepare(
        "SELECT TRIM(CLAVE_LECHERIA) AS K,
                SURT_LITROS, MES_PERIODO, ANIO_PERIODO
         FROM inventarios_mensuales
         WHERE (ANIO_PERIODO*12 + MES_PERIODO) BETWEEN :ini AND :fin"
    );
    $finKey = $anio*12 + $mes;
    $iniKey = $finKey - 3;
    $stmtInv->execute([':ini' => $iniKey, ':fin' => $finKey]);
    $inv = [];
    foreach ($stmtInv->fetchAll() as $iv) {
        $k = trim((string)$iv['K']);
        if (!isset($inv[$k])) $inv[$k] = ['surt'=>[], 'tiene_mes_base'=>false];
        $l = (int)$iv['SURT_LITROS'];
        if ($l > 0) $inv[$k]['surt'][] = $l;
        if ((int)$iv['MES_PERIODO'] === $mes && (int)$iv['ANIO_PERIODO'] === $anio)
            $inv[$k]['tiene_mes_base'] = true;
    }

    // Enriquecer
    foreach ($rows as &$r) {
        $k = trim((string)$r['LECHER']);
        if (isset($reqs[$k])) {
            $r['REQ_ACTUAL']  = $reqs[$k]['req'];
            $r['ES_ESTIMADO'] = false;
            $r['ESTADO']      = $reqs[$k]['aprobado'] === 1 ? 'verificado' : 'capturado';
        } elseif (isset($inv[$k]) && ($inv[$k]['tiene_mes_base'] || count($inv[$k]['surt']))) {
            $s = $inv[$k]['surt'];
            $prom = count($s) ? (int) round(array_sum($s)/count($s)) : 0;
            $r['REQ_ACTUAL']  = $prom;
            $r['ES_ESTIMADO'] = true;
            $r['ESTADO']      = 'estimado';
        } else {
            $r['REQ_ACTUAL']  = null;
            $r['ES_ESTIMADO'] = false;
            $r['ESTADO']      = 'falta';
        }
    }
    unset($r);

    // Agrupación por supervisor → almacén
    $supervisores   = [];
    $totalGeneral   = 0;
    $totalLech      = 0;
    $totalCapt      = 0;
    $resumenPromotores = [];
    $resumenLech450    = 0;
    $resumenLech650    = 0;

    foreach ($rows as $r) {
        $tipo = (int)$r['TIPO_PUNTO_VENTA'];
        $pmt  = (int)$r['PROMOTOR_ID'];
        $supId = $r['ID_SUPERVISOR'] !== null ? (int)$r['ID_SUPERVISOR'] : 0;
        $supNombre = $r['SUPERVISOR_NOMBRE'] !== null ? trim((string)$r['SUPERVISOR_NOMBRE']) : '(Sin supervisor)';
        if ($supNombre === '') $supNombre = 'Supervisor #' . $supId;

        // Resumen global
        $resumenPromotores[$pmt] = true;
        if ($tipo === 0) $resumenLech450++;
        elseif ($tipo === 1 || $tipo === 2) $resumenLech650++;

        // Filtro de precio
        if (!in_array($tipo, $tipoVentaFiltro, true)) continue;

        if (!isset($supervisores[$supId])) {
            $supervisores[$supId] = [
                'id'                 => $supId,
                'nombre'             => $supNombre,
                'almacenes'          => [],
                'subtotal_supervisor'=> 0,
                'capturadas_sup'     => 0,
                'total_sup'          => 0,
            ];
        }

        $alm = strtoupper(trim((string)$r['ALMACEN']));
        if ($alm === '') $alm = '(SIN ALMACÉN)';

        if (!isset($supervisores[$supId]['almacenes'][$alm])) {
            $supervisores[$supId]['almacenes'][$alm] = [
                'almacen'     => $alm,
                'lecherias'   => [],
                'subtotal'    => 0,
                'subtotal_v'  => 0,
                'capturadas'  => 0,
                'verificadas' => 0,
                'estimadas'   => 0,
                'total'       => 0,
            ];
        }

        $estado    = $r['ESTADO'] ?? 'falta';
        $esEst     = !empty($r['ES_ESTIMADO']);
        $capturado = $r['REQ_ACTUAL'] !== null;
        $req = $capturado ? (int)$r['REQ_ACTUAL'] : null;

        $numTiendaRaw     = trim((string)$r['NUM_TIENDA']);
        $numTiendaMostrar = ($tipo === 2 || $numTiendaRaw === '10101') ? 'DM' : $numTiendaRaw;

        $supervisores[$supId]['almacenes'][$alm]['lecherias'][] = [
            'punto_venta'   => (string)$r['LECHER'],
            'num_tienda'    => $numTiendaMostrar,
            'requerimiento' => $req,
            'capturado'     => $capturado,
            'estado'        => $estado,
            'es_estimado'   => $esEst,
        ];
        $supervisores[$supId]['almacenes'][$alm]['total']++;
        $supervisores[$supId]['total_sup']++;
        $totalLech++;

        if ($capturado) {
            $supervisores[$supId]['almacenes'][$alm]['subtotal']   += $req;
            $supervisores[$supId]['almacenes'][$alm]['capturadas'] ++;
            if ($estado === 'verificado') {
                $supervisores[$supId]['almacenes'][$alm]['verificadas']++;
                $supervisores[$supId]['almacenes'][$alm]['subtotal_v'] += $req;
            } elseif ($esEst) {
                $supervisores[$supId]['almacenes'][$alm]['estimadas']++;
            }
            $supervisores[$supId]['subtotal_supervisor']  += $req;
            $supervisores[$supId]['capturadas_sup']      ++;
            $totalGeneral += $req;
            $totalCapt++;
        }
    }

    // Ordenar almacenes dentro de cada supervisor y convertir a arrays
    $supervisoresOut = [];
    foreach ($supervisores as $sup) {
        ksort($sup['almacenes']);
        $sup['almacenes'] = array_values($sup['almacenes']);
        $supervisoresOut[] = $sup;
    }
    // Ordenar supervisores por nombre
    usort($supervisoresOut, fn($a, $b) => strcmp($a['nombre'], $b['nombre']));

    $resp = [
        'status'           => 'success',
        'mes'              => $mes,
        'anio'             => $anio,
        'precio'           => number_format($precioNum, 2, '.', ''),
        'supervisores'     => $supervisoresOut,
        'total_general'    => $totalGeneral,
        'total_lecherias'  => $totalLech,
        'total_capturadas' => $totalCapt,
        'resumen'          => [
            'supervisores'      => count($supervisores),
            'promotores'        => count($resumenPromotores),
            'lecherias_total'   => $resumenLech450 + $resumenLech650,
            'lecherias_450'     => $resumenLech450,
            'lecherias_650'     => $resumenLech650,
        ],
    ];

    array_walk_recursive($resp, function (&$v) {
        if (is_string($v)) {
            $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        }
    });

    echo json_encode($resp, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
