<?php
// ────────────────────────────────────────────────────────────────────
//  Consolida el "Requerimiento de Dotación" para el supervisor.
//
//  Fuente de datos:
//    Tabla REQUERIMIENTO_DOTACION (la que llena el promotor al guardar
//    su requerimiento). Hacemos LEFT JOIN con LECHERIA para listar
//    TODAS las lecherías del supervisor; las que aún no tienen captura
//    salen como "FALTA" (requerimiento = null).
//
//  Filtros: precio
//      $4.50 → TIPO_PUNTO_VENTA = 0
//      $6.50 → TIPO_PUNTO_VENTA IN (1, 2)
//
//  Salida JSON:
//  {
//    status, mes, anio, precio,
//    supervisor:   { id, nombre },
//    almacenes:    [ { almacen,
//                      lecherias:[{punto_venta,num_tienda,requerimiento,capturado}],
//                      subtotal, capturadas, total } ],
//    total_general, total_lecherias, total_capturadas
//  }
// ────────────────────────────────────────────────────────────────────
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

$mes         = isset($_GET['mes'])        ? (int)$_GET['mes']        : 0;
$anio        = isset($_GET['anio'])       ? (int)$_GET['anio']       : 0;
$precio      = isset($_GET['precio'])     ? trim($_GET['precio'])     : 'todos';
$filtroAlm   = isset($_GET['almacen'])    ? trim($_GET['almacen'])    : '';
$filtroResp  = isset($_GET['ressurti'])   ? trim($_GET['ressurti'])   : ''; // ''=todos, '1'..'5' o 'dm'

if ($mes < 1 || $mes > 12 || $anio < 2000) {
    echo json_encode(['status' => 'error', 'message' => 'Mes/año inválido.']);
    exit();
}

// Precio: todos | 4.50 | 6.50
$precioNum = null;
$tipoVentaFiltro = null;  // null = sin filtro de precio
$precioStr = 'todos';

$precioNum = (float)str_replace(['$', ','], ['', '.'], $precio);
if (abs($precioNum - 4.50) < 0.001) {
    $precioNum = 4.50; $tipoVentaFiltro = [0]; $precioStr = '4.50';
} elseif (abs($precioNum - 6.50) < 0.001) {
    $precioNum = 6.50; $tipoVentaFiltro = [1, 2]; $precioStr = '6.50';
} else {
    $precioNum = null; $tipoVentaFiltro = null; $precioStr = 'todos';
}

try {
    $pdo = DatabaseSQLite::getInstance();

    // 1) Lecherías del supervisor (espejo SQLite)
    $sql = "
        SELECT TRIM(CAST(L.LECHER AS TEXT)) AS LECHER,
               TRIM(L.NUM_TIENDA)           AS NUM_TIENDA,
               TRIM(L.ALMACEN_RURAL)        AS ALMACEN,
               L.TIPO_PUNTO_VENTA           AS TIPO_PUNTO_VENTA,
               L.RESSURTI                   AS RESSURTI,
               L.PROMOTOR                   AS PROMOTOR_ID
        FROM lecheria L
        JOIN promotor P ON P.PMT_NUMERO = L.PROMOTOR
        WHERE P.PMT_ACTIVO = 'S'
          AND COALESCE(L.EN_OPERACION, 0) = 0
          AND EXISTS (
                SELECT 1
                FROM mapeo_supervisor_lecheria M
                WHERE M.ID_SUPERVISOR = :id_sup
                  AND M.LECHER = L.LECHER
              )
        ORDER BY TRIM(L.ALMACEN_RURAL), L.LECHER
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_sup' => $id_supervisor]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Columnas de aprobación: defensivo, por si la base no tiene aún el ALTER
    foreach (['aprobado INTEGER DEFAULT 0',
              'supervisor_aprobador TEXT',
              'fecha_aprobacion TEXT'] as $colDef) {
        try { $pdo->exec("ALTER TABLE requerimiento_dotacion ADD COLUMN $colDef"); }
        catch (Throwable $e) { /* ya existe */ }
    }

    // 2) Requerimientos capturados → req_actual + estado de aprobación
    $stmtSQ = $pdo->prepare(
        "SELECT clave_lecheria, req_actual, fecha_captura,
                COALESCE(bloqueado,0) AS bloqueado,
                COALESCE(aprobado,0)  AS aprobado
         FROM requerimiento_dotacion
         WHERE mes_base = :mes AND anio_base = :anio"
    );
    $stmtSQ->execute([':mes' => $mes, ':anio' => $anio]);
    $reqs = [];
    foreach ($stmtSQ->fetchAll() as $rq) {
        $reqs[trim((string)$rq['clave_lecheria'])] = $rq;
    }

    // 2.b) Avance desde INVENTARIOS_MENSUALES: surtimiento del mes base y
    //      promedio de los últimos 3 meses para fallback cuando el promotor
    //      aún no envía el requerimiento formal.
    $stmtInv = $pdo->prepare(
        "SELECT TRIM(CLAVE_LECHERIA) AS K,
                SURT_LITROS, FIN_LITROS, VENTA_LITROS, MES_PERIODO, ANIO_PERIODO,
                FECHA_CAPTURA
         FROM inventarios_mensuales
         WHERE (ANIO_PERIODO*12 + MES_PERIODO) BETWEEN :ini AND :fin"
    );
    $finKey = $anio*12 + $mes;
    $iniKey = $finKey - 3;
    $stmtInv->execute([':ini' => $iniKey, ':fin' => $finKey]);
    $inv = [];           // K => ['surt'=>[..], 'fin_base'=>?, 'tiene_mes_base'=>bool, 'fecha'=>?]
    foreach ($stmtInv->fetchAll() as $iv) {
        $k = trim((string)$iv['K']);
        if (!isset($inv[$k])) $inv[$k] = ['surt'=>[], 'fin_base'=>null, 'tiene_mes_base'=>false, 'fecha'=>null];
        $litros = (int)$iv['SURT_LITROS'];
        if ($litros > 0) $inv[$k]['surt'][] = $litros;
        if ((int)$iv['MES_PERIODO'] === $mes && (int)$iv['ANIO_PERIODO'] === $anio) {
            $inv[$k]['tiene_mes_base'] = true;
            $inv[$k]['fin_base']       = (int)$iv['FIN_LITROS'];
            $inv[$k]['fecha']          = $iv['FECHA_CAPTURA'];
        }
    }

    // Enriquecer cada fila
    foreach ($rows as &$r) {
        $k = trim((string)$r['LECHER']);
        $tieneReq = isset($reqs[$k]);
        $tieneInv = isset($inv[$k]) && ($inv[$k]['tiene_mes_base'] || count($inv[$k]['surt']) > 0);

        if ($tieneReq) {
            $r['REQ_ACTUAL']    = (int)$reqs[$k]['req_actual'];
            $r['FECHA_CAPTURA'] = $reqs[$k]['fecha_captura'];
            $r['ES_ESTIMADO']   = false;
            $r['ESTADO']        = ((int)$reqs[$k]['aprobado'] === 1) ? 'verificado' : 'capturado';
        } elseif ($tieneInv) {
            $surts = $inv[$k]['surt'];
            $prom  = count($surts) ? (int) round(array_sum($surts) / count($surts)) : 0;
            $r['REQ_ACTUAL']    = $prom;          // litros estimados ≈ surtimiento promedio
            $r['FECHA_CAPTURA'] = $inv[$k]['fecha'];
            $r['ES_ESTIMADO']   = true;
            $r['ESTADO']        = 'estimado';
        } else {
            $r['REQ_ACTUAL']    = null;
            $r['FECHA_CAPTURA'] = null;
            $r['ES_ESTIMADO']   = false;
            $r['ESTADO']        = 'falta';
        }
    }
    unset($r);

    // 3) Nombre del supervisor.
    $stmtN = $pdo->prepare("SELECT NOMBRE FROM usuarios_inventarios
                            WHERE CLAVE_ROL = :id AND ROL = '1' LIMIT 1");
    $stmtN->execute([':id' => $id_supervisor]);
    $nombreSup = trim((string)$stmtN->fetchColumn());
    if ($nombreSup === '') $nombreSup = 'Supervisor #' . $id_supervisor;

    // 3) Catálogos para los filtros (calculados ANTES de aplicar filtros)
    $RESSURTI_LABEL = [
        1 => 'Liconsa',
        2 => 'Diconsa',
        3 => 'Inst. responsable',
        4 => 'Particular (comisionado)',
        5 => 'Otro',
    ];
    $catAlmacenes  = [];
    $catRessurti   = [];   // [value => label]
    foreach ($rows as $r) {
        $alm = strtoupper(trim((string)$r['ALMACEN']));
        if ($alm !== '' && !in_array($alm, $catAlmacenes)) $catAlmacenes[] = $alm;
        $tipo = (int)$r['TIPO_PUNTO_VENTA'];
        // DM se trata como responsable especial
        if ($tipo === 2) {
            $catRessurti['dm'] = 'Distribución Mercantil (DM)';
        } else {
            $rs = (int)$r['RESSURTI'];
            if ($rs > 0 && !isset($catRessurti[$rs])) {
                $catRessurti[$rs] = $RESSURTI_LABEL[$rs] ?? "Responsable $rs";
            }
        }
    }
    sort($catAlmacenes);
    ksort($catRessurti);

    // Aplicar filtro de almacén y responsable al dataset
    if ($filtroAlm !== '') {
        $rows = array_filter($rows, fn($r) => strtoupper(trim((string)$r['ALMACEN'])) === strtoupper($filtroAlm));
    }
    if ($filtroResp !== '') {
        if ($filtroResp === 'dm') {
            $rows = array_filter($rows, fn($r) => (int)$r['TIPO_PUNTO_VENTA'] === 2);
        } else {
            $frs = (int)$filtroResp;
            // DM (tipo=2) lo excluimos si el filtro no es DM
            $rows = array_filter($rows, fn($r) => (int)$r['TIPO_PUNTO_VENTA'] !== 2 && (int)$r['RESSURTI'] === $frs);
        }
    }

    // 4) Recorremos: agrupamos por almacén las que pasan el filtro de
    //    precio, y en paralelo armamos un resumen GLOBAL (ambos precios)
    //    para mostrar al final de la página: total promotores, total
    //    lecherías, cuántas $4.50 y cuántas $6.50.
    $almacenes    = [];
    $totalGeneral = 0;
    $totalLech    = 0;
    $totalCapt    = 0;

    $resumenPromotores  = [];   // set de PMT_NUMERO únicos
    $resumenLech450     = 0;
    $resumenLech650     = 0;

    foreach ($rows as $r) {
        $tipo = (int)$r['TIPO_PUNTO_VENTA'];
        $pmt  = (int)$r['PROMOTOR_ID'];

        // Resumen global (independiente del filtro de precio).
        $resumenPromotores[$pmt] = true;
        if ($tipo === 0)                     $resumenLech450++;
        elseif ($tipo === 1 || $tipo === 2)  $resumenLech650++;

        // Filtro de precio para el detalle por almacén.
        if ($tipoVentaFiltro !== null && !in_array($tipo, $tipoVentaFiltro, true)) continue;

        $alm = trim((string)$r['ALMACEN']);
        if ($alm === '') $alm = '(SIN ALMACÉN)';
        $alm = strtoupper($alm);

        if (!isset($almacenes[$alm])) {
            $almacenes[$alm] = [
                'almacen'      => $alm,
                'lecherias'    => [],
                'subtotal'     => 0,
                'subtotal_v'   => 0,   // solo verificados
                'capturadas'   => 0,
                'verificadas'  => 0,
                'estimadas'    => 0,
                'total'        => 0,
            ];
        }

        $estado    = $r['ESTADO'] ?? 'falta';
        $esEst     = !empty($r['ES_ESTIMADO']);
        $capturado = $r['REQ_ACTUAL'] !== null;
        $req = $capturado ? (int)$r['REQ_ACTUAL'] : null;

        // "DM" = Distribución Mercantil: TIPO_PUNTO_VENTA = 2 o num_tienda 10101.
        $numTiendaRaw    = trim((string)$r['NUM_TIENDA']);
        $numTiendaMostrar = ($tipo === 2 || $numTiendaRaw === '10101')
                            ? 'DM' : $numTiendaRaw;

        $rs = (int)$r['RESSURTI'];
        $almacenes[$alm]['lecherias'][] = [
            'punto_venta'      => (string)$r['LECHER'],
            'num_tienda'       => $numTiendaMostrar,
            'num_tienda_raw'   => $numTiendaRaw,
            'tipo_punto_venta' => $tipo,
            'ressurti'         => $rs,
            'ressurti_label'   => ($tipo === 2) ? 'DM' : ($RESSURTI_LABEL[$rs] ?? ''),
            'precio_label'     => ($tipo === 0) ? '$4.50' : '$6.50',
            'requerimiento'    => $req,        // null = FALTA
            'capturado'        => $capturado,
            'estado'           => $estado,     // verificado | capturado | estimado | falta
            'es_estimado'      => $esEst,
            'fecha'            => $r['FECHA_CAPTURA'] ?? null,
        ];
        $almacenes[$alm]['total']++;
        $totalLech++;

        if ($capturado) {
            $almacenes[$alm]['subtotal'] += $req;
            $almacenes[$alm]['capturadas']++;
            if ($estado === 'verificado') {
                $almacenes[$alm]['verificadas']++;
                $almacenes[$alm]['subtotal_v'] += $req;
            } elseif ($esEst) {
                $almacenes[$alm]['estimadas']++;
            }
            $totalGeneral += $req;
            $totalCapt++;
        }
    }

    ksort($almacenes);
    $almacenesOut = array_values($almacenes);

    $catRessurtiOut = [];
    foreach ($catRessurti as $k => $v) $catRessurtiOut[] = ['value' => (string)$k, 'label' => $v];

    $resp = [
        'status'     => 'success',
        'mes'        => $mes,
        'anio'       => $anio,
        'precio'     => $precioStr,
        'supervisor' => [
            'id'     => (int)$id_supervisor,
            'nombre' => $nombreSup,
        ],
        'almacenes'        => $almacenesOut,
        'total_general'    => $totalGeneral,
        'total_lecherias'  => $totalLech,
        'total_capturadas' => $totalCapt,
        'resumen'          => [
            'promotores'        => count($resumenPromotores),
            'lecherias_total'   => $resumenLech450 + $resumenLech650,
            'lecherias_450'     => $resumenLech450,
            'lecherias_650'     => $resumenLech650,
        ],
        'cat_almacenes'  => $catAlmacenes,
        'cat_ressurti'   => $catRessurtiOut,
        'filtros_activos'=> [
            'almacen'  => $filtroAlm,
            'ressurti' => $filtroResp,
            'precio'   => $precioStr,
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
