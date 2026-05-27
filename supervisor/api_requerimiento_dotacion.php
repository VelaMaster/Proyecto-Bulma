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

$mes    = isset($_GET['mes'])    ? (int)$_GET['mes']    : 0;
$anio   = isset($_GET['anio'])   ? (int)$_GET['anio']   : 0;
$precio = isset($_GET['precio']) ? trim($_GET['precio']) : '6.50';

if ($mes < 1 || $mes > 12 || $anio < 2000) {
    echo json_encode(['status' => 'error', 'message' => 'Mes/año inválido.']);
    exit();
}

// Precio aceptado: 4.50 | 6.50
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
    $pdo = Database::getInstance();

    // 1) Lecherías del supervisor desde Firebird (sin JOIN a requerimiento)
    $sql = "
        SELECT TRIM(L.LECHER)        AS LECHER,
               TRIM(L.NUM_TIENDA)    AS NUM_TIENDA,
               TRIM(L.ALMACEN_RURAL) AS ALMACEN,
               L.TIPO_PUNTO_VENTA    AS TIPO_PUNTO_VENTA,
               L.PROMOTOR            AS PROMOTOR_ID
        FROM LECHERIA L
        JOIN PROMOTOR P ON P.PMT_NUMERO = L.PROMOTOR
        WHERE P.PMT_ACTIVO = 'S'
          AND COALESCE(L.EN_OPERACION, 0) = 0
          AND EXISTS (
                SELECT 1
                FROM MAPEO_SUPERVISOR_LECHERIA M
                JOIN LECHERIA L2 ON M.LECHER = L2.LECHER
                WHERE M.ID_SUPERVISOR = :id_sup
                  AND L2.PROMOTOR = L.PROMOTOR
              )
        ORDER BY TRIM(L.ALMACEN_RURAL), TRIM(L.LECHER)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_sup' => $id_supervisor]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2) Requerimientos desde SQLite — mapa clave_lecheria → req_actual
    $sqlite    = DatabaseSQLite::getInstance();
    $stmtSQ    = $sqlite->prepare(
        "SELECT clave_lecheria, req_actual, fecha_captura
         FROM requerimiento_dotacion
         WHERE mes_base = :mes AND anio_base = :anio"
    );
    $stmtSQ->execute([':mes' => $mes, ':anio' => $anio]);
    $reqs = [];
    foreach ($stmtSQ->fetchAll() as $rq) {
        $reqs[trim((string)$rq['clave_lecheria'])] = $rq;
    }

    // Enriquecer cada fila de Firebird con el requerimiento de SQLite
    foreach ($rows as &$r) {
        $k = trim((string)$r['LECHER']);
        $r['REQ_ACTUAL']    = isset($reqs[$k]) ? (int)$reqs[$k]['req_actual']    : null;
        $r['FECHA_CAPTURA'] = isset($reqs[$k]) ?      $reqs[$k]['fecha_captura'] : null;
    }
    unset($r);

    // 3) Nombre del supervisor.
    $stmtN = $pdo->prepare("SELECT FIRST 1 NOMBRE FROM USUARIOS_INVENTARIOS
                            WHERE CLAVE_ROL = :id AND ROL = '1'");
    $stmtN->execute([':id' => $id_supervisor]);
    $nombreSup = trim((string)$stmtN->fetchColumn());
    if ($nombreSup === '') $nombreSup = 'Supervisor #' . $id_supervisor;

    // 3) Recorremos: agrupamos por almacén las que pasan el filtro de
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
        if (!in_array($tipo, $tipoVentaFiltro, true)) continue;

        $alm = trim((string)$r['ALMACEN']);
        if ($alm === '') $alm = '(SIN ALMACÉN)';
        $alm = strtoupper($alm);

        if (!isset($almacenes[$alm])) {
            $almacenes[$alm] = [
                'almacen'    => $alm,
                'lecherias'  => [],
                'subtotal'   => 0,
                'capturadas' => 0,
                'total'      => 0,
            ];
        }

        $capturado = $r['REQ_ACTUAL'] !== null;
        $req = $capturado ? (int)$r['REQ_ACTUAL'] : null;

        // "DM" = Distribución Mercantil: TIPO_PUNTO_VENTA = 2 o num_tienda 10101.
        $numTiendaRaw    = trim((string)$r['NUM_TIENDA']);
        $numTiendaMostrar = ($tipo === 2 || $numTiendaRaw === '10101')
                            ? 'DM' : $numTiendaRaw;

        $almacenes[$alm]['lecherias'][] = [
            'punto_venta'      => (string)$r['LECHER'],
            'num_tienda'       => $numTiendaMostrar,
            'num_tienda_raw'   => $numTiendaRaw,
            'tipo_punto_venta' => $tipo,
            'requerimiento'    => $req,        // null = FALTA
            'capturado'        => $capturado,
        ];
        $almacenes[$alm]['total']++;
        $totalLech++;

        if ($capturado) {
            $almacenes[$alm]['subtotal'] += $req;
            $almacenes[$alm]['capturadas']++;
            $totalGeneral += $req;
            $totalCapt++;
        }
    }

    ksort($almacenes);
    $almacenesOut = array_values($almacenes);

    $resp = [
        'status'     => 'success',
        'mes'        => $mes,
        'anio'       => $anio,
        'precio'     => number_format($precioNum, 2, '.', ''),
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
