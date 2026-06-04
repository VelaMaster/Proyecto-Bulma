<?php
// supervisor/api_inventario_almacen.php
// Devuelve el "Inventario de Leche en Polvo en Almacenes de Alimentación
// para el Bienestar" (R05) + "Conciliación Mensual en Almacén Rural" (R07)
// agrupado por almacén + mes + año.
// GET: almacen, mes, anio
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

$almacenFiltro = isset($_GET['almacen']) ? trim($_GET['almacen']) : '';
$mes  = isset($_GET['mes'])  ? (int)$_GET['mes']  : 0;
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : 0;

if ($mes < 1 || $mes > 12 || $anio < 2000) {
    echo json_encode(['status' => 'error', 'message' => 'Mes/año inválido.']);
    exit();
}

try {
    $pdo = DatabaseSQLite::getInstance();

    // 1) Catálogo de almacenes del supervisor (vía mapeo)
    $sqlAlm = "
        SELECT DISTINCT TRIM(L.ALMACEN_RURAL) AS ALMACEN
        FROM lecheria L
        WHERE L.ALMACEN_RURAL IS NOT NULL
          AND TRIM(L.ALMACEN_RURAL) <> ''
          AND EXISTS (
                SELECT 1 FROM mapeo_supervisor_lecheria M
                WHERE M.ID_SUPERVISOR = :id_sup
                  AND M.LECHER = L.LECHER
              )
        ORDER BY 1
    ";
    $stmtAlm = $pdo->prepare($sqlAlm);
    $stmtAlm->execute([':id_sup' => $id_supervisor]);
    $catAlmacenes = array_map(fn($r) => $r['ALMACEN'], $stmtAlm->fetchAll(PDO::FETCH_ASSOC));

    if ($almacenFiltro === '') {
        echo json_encode([
            'status'        => 'success',
            'cat_almacenes' => $catAlmacenes,
            'almacen'       => '',
            'mes'           => $mes,
            'anio'          => $anio,
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // 2) Lecherías del almacén elegido (a cargo del supervisor)
    $sqlLech = "
        SELECT TRIM(CAST(L.LECHER AS TEXT)) AS LECHER,
               TRIM(L.NUM_TIENDA)           AS NUM_TIENDA,
               TRIM(L.NOMBRELECH)           AS NOMBRELECH,
               L.TIPO_PUNTO_VENTA           AS TIPO_PUNTO_VENTA
        FROM lecheria L
        WHERE TRIM(L.ALMACEN_RURAL) = :alm
          AND EXISTS (
                SELECT 1 FROM mapeo_supervisor_lecheria M
                WHERE M.ID_SUPERVISOR = :id_sup
                  AND M.LECHER = L.LECHER
              )
        ORDER BY L.LECHER
    ";
    $stmtL = $pdo->prepare($sqlLech);
    $stmtL->execute([':alm' => $almacenFiltro, ':id_sup' => $id_supervisor]);
    $lecherias = $stmtL->fetchAll(PDO::FETCH_ASSOC);

    if (!$lecherias) {
        echo json_encode([
            'status'        => 'success',
            'cat_almacenes' => $catAlmacenes,
            'almacen'       => $almacenFiltro,
            'mes'           => $mes,
            'anio'          => $anio,
            'lecherias'     => [],
            'r05'           => null,
            'r07_450'       => null,
            'r07_650'       => null,
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $claves = array_map(fn($l) => $l['LECHER'], $lecherias);
    $tipoMap = [];   // LECHER → TIPO_PUNTO_VENTA
    $tiendaMap = []; // LECHER → NUM_TIENDA
    foreach ($lecherias as $l) {
        $tipoMap[$l['LECHER']]   = (int)$l['TIPO_PUNTO_VENTA'];
        $tiendaMap[$l['LECHER']] = trim((string)$l['NUM_TIENDA']);
    }

    // helper IN(...) con clave directa y con sufijo "00" (fix arrastre)
    $bind = [];
    $marks = [];
    foreach ($claves as $i => $c) {
        $marks[] = ":k$i"; $bind[":k$i"] = $c;
        $marks[] = ":k{$i}b"; $bind[":k{$i}b"] = $c . '00';
    }
    $inMarks = implode(',', $marks);

    // 3) R07 — surtimiento del mes elegido por lechería
    $sqlSurt = "
        SELECT TRIM(CLAVE_LECHERIA) AS CLAVE_LECHERIA,
               SURT_FECHA, SURT_FACTURA, SURT_CAJAS
        FROM inventarios_mensuales
        WHERE MES_PERIODO = :mes
          AND ANIO_PERIODO = :anio
          AND TRIM(CLAVE_LECHERIA) IN ($inMarks)
    ";
    $stmtS = $pdo->prepare($sqlSurt);
    $stmtS->execute(array_merge([':mes' => $mes, ':anio' => $anio], $bind));
    $surtByLech = [];
    foreach ($stmtS->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $k = preg_replace('/00$/', '', trim((string)$row['CLAVE_LECHERIA']));
        // si la fila vino con sufijo, intenta también la versión sin sufijo
        if (!isset($tipoMap[$k])) {
            $k = trim((string)$row['CLAVE_LECHERIA']);
        }
        $surtByLech[$k] = $row;
    }

    $r07_450 = ['precio' => '4.50', 'rows' => [], 'total_recibidas' => 0];
    $r07_650 = ['precio' => '6.50', 'rows' => [], 'total_recibidas' => 0];

    foreach ($lecherias as $l) {
        $k = $l['LECHER'];
        $s = $surtByLech[$k] ?? null;
        if (!$s) continue;

        $cajas   = (int)($s['SURT_CAJAS'] ?? 0);
        $factura = trim((string)($s['SURT_FACTURA'] ?? ''));
        $fecha   = trim((string)($s['SURT_FECHA'] ?? ''));
        if ($cajas === 0 && $factura === '' && $fecha === '') continue;

        $row = [
            'punto_venta'       => $k,
            'num_tienda'        => $tiendaMap[$k],
            'cajas_recibidas'   => $cajas,
            'fecha_recepcion'   => $fecha,
            'guias_distribucion'=> $cajas,   // por defecto = cajas; editable en UI
            'no_factura'        => $factura,
            'no_cajas_enviadas' => $cajas,
            'fecha_enviada'     => $fecha,
            'observaciones'     => '',
        ];

        if ($tipoMap[$k] === 0) {
            $r07_450['rows'][] = $row;
            $r07_450['total_recibidas'] += $cajas;
        } elseif ($tipoMap[$k] === 1) {
            $r07_650['rows'][] = $row;
            $r07_650['total_recibidas'] += $cajas;
        }
        // tipo 2 (Distribución Mercantil) no aparece en R07
    }

    // 4) R05 — existencia en almacén = suma de inventario final por lechería
    //     - Pobreza Extrema (P.P.E.) usa tipo 0 ($4.50)
    //     - I.N.I. queda en 0 (no se usa)
    //     Se traen TODOS los inventarios con FIN_CAJA>0 ó FIN_SOBRES>0 del mes
    //     y meses anteriores (para reflejar lo que sigue en almacén).
    $sqlFin = "
        SELECT TRIM(CLAVE_LECHERIA) AS CLAVE_LECHERIA,
               MES_PERIODO, ANIO_PERIODO,
               FIN_CAJA, FIN_SOBRES
        FROM inventarios_mensuales
        WHERE TRIM(CLAVE_LECHERIA) IN ($inMarks)
          AND ( (ANIO_PERIODO < :anio)
                OR (ANIO_PERIODO = :anio AND MES_PERIODO <= :mes) )
          AND (FIN_CAJA > 0 OR FIN_SOBRES > 0)
        ORDER BY CLAVE_LECHERIA, ANIO_PERIODO DESC, MES_PERIODO DESC
    ";
    $stmtF = $pdo->prepare($sqlFin);
    $stmtF->execute(array_merge([':mes' => $mes, ':anio' => $anio], $bind));

    // Tomar la fila más reciente por lechería con saldo > 0
    $pendientesPE = [];
    $vistos = [];
    foreach ($stmtF->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $k = preg_replace('/00$/', '', trim((string)$row['CLAVE_LECHERIA']));
        if (!isset($tipoMap[$k])) $k = trim((string)$row['CLAVE_LECHERIA']);
        if (isset($vistos[$k])) continue;
        $vistos[$k] = true;
        if (($tipoMap[$k] ?? -1) !== 0) continue; // solo P.P.E.
        $pendientesPE[] = [
            'punto_venta'      => $k,
            'num_tienda'       => $tiendaMap[$k] ?? '',
            'cajas'            => (int)$row['FIN_CAJA'],
            'sobres'           => (int)$row['FIN_SOBRES'],
            'mes_corresponde'  => sprintf('%02d/%04d',
                                          (int)$row['MES_PERIODO'],
                                          (int)$row['ANIO_PERIODO']),
        ];
    }

    $totalCajasPE  = array_sum(array_column($pendientesPE, 'cajas'));
    $totalSobresPE = array_sum(array_column($pendientesPE, 'sobres'));

    $r05 = [
        'pe' => [
            'buen_cajas'   => $totalCajasPE,
            'buen_sobres'  => $totalSobresPE,
            'mal_cajas'    => 0,
            'mal_sobres'   => 0,
        ],
        'ini' => [
            'buen_cajas'   => 0,
            'buen_sobres'  => 0,
            'mal_cajas'    => 0,
            'mal_sobres'   => 0,
        ],
        'lecherias_pe'  => $pendientesPE,
        'lecherias_ini' => [],
    ];

    // 5) Nombre supervisor
    $stmtN = $pdo->prepare("SELECT NOMBRE FROM usuarios_inventarios
                            WHERE CLAVE_ROL = :id AND ROL = '1' LIMIT 1");
    $stmtN->execute([':id' => $id_supervisor]);
    $nombreSup = trim((string)$stmtN->fetchColumn());
    if ($nombreSup === '') $nombreSup = 'Supervisor #' . $id_supervisor;

    $resp = [
        'status'        => 'success',
        'cat_almacenes' => $catAlmacenes,
        'almacen'       => $almacenFiltro,
        'mes'           => $mes,
        'anio'          => $anio,
        'supervisor'    => ['id' => (int)$id_supervisor, 'nombre' => $nombreSup],
        'lecherias'     => $lecherias,
        'r05'           => $r05,
        'r07_450'       => $r07_450,
        'r07_650'       => $r07_650,
    ];

    array_walk_recursive($resp, function (&$v) {
        if (is_string($v))
            $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
    });

    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
