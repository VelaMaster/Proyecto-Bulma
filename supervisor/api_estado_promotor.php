<?php
// ────────────────────────────────────────────────────────────────────
//  Estado detallado de un promotor para un mes/año:
//    - Lista de lecherías (asignadas al supervisor) con tiene_inventario
//      y si existe el PDF correspondiente.
//    - Reporte mensual: existe (json) y pdf existente.
//    - Requerimiento: existe (json) y pdf existente.
//
//  Salida JSON:
//  {
//    status, promotor: { id, nombre, usuario },
//    mes, anio,
//    lecherias: [
//      { lecher, num_tienda, nombre, almacen, tiene_inventario, pdf }
//    ],
//    reporte:      { existe, pdf },
//    requerimiento:{ existe, pdf, mes_destino, anio_destino }
//  }
// ────────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../includes/session_guard.php';
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

$promotor_id = isset($_GET['promotor']) ? (int)$_GET['promotor'] : 0;
$mes  = isset($_GET['mes'])  ? (int)$_GET['mes']  : 0;
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : 0;

if ($promotor_id <= 0 || $mes < 1 || $mes > 12 || $anio < 2000) {
    echo json_encode(['status' => 'error', 'message' => 'Parámetros inválidos.']);
    exit();
}

try {
    $pdo = DatabaseSQLite::getInstance();

    // 1) Datos del promotor + usuario asociado, validando que pertenezca al supervisor.
    $sqlP = "
        SELECT P.PMT_NUMERO, P.PMT_NOMBRE, U.USUARIO
        FROM promotor P
        JOIN lecheria L ON L.PROMOTOR = P.PMT_NUMERO
        JOIN mapeo_supervisor_lecheria M ON M.LECHER = L.LECHER
        LEFT JOIN usuarios_inventarios U
               ON U.CLAVE_ROL = P.PMT_NUMERO AND U.ROL = '0'
        WHERE M.ID_SUPERVISOR = :id_sup
          AND P.PMT_NUMERO    = :id_prom
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sqlP);
    $stmt->execute([':id_sup' => $id_supervisor, ':id_prom' => $promotor_id]);
    $datosProm = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$datosProm) {
        echo json_encode(['status' => 'error', 'message' => 'Promotor no asignado a este supervisor.']);
        exit();
    }

    $promotor_usuario = $datosProm['USUARIO'] ?? '';
    $promotor_nombre  = trim($datosProm['PMT_NOMBRE'] ?? '');

    // 2) TODAS las lecherías activas del promotor.
    //    Antes filtrábamos por MAPEO_SUPERVISOR_LECHERIA, lo que dejaba
    //    fuera lecherías que ya están asignadas al promotor pero aún
    //    no se reflejan en el mapeo, haciendo que al supervisor le
    //    aparezcan menos lecherías de las que realmente tiene el promotor.
    $sqlL = "
        SELECT TRIM(CAST(L.LECHER AS TEXT)) AS LECHER,
               TRIM(L.NUM_TIENDA) AS NUM_TIENDA,
               L.TIPO_PUNTO_VENTA AS TIPO_PUNTO_VENTA,
               TRIM(L.NOMBRELECH) AS NOMBRE,
               TRIM(L.ALMACEN_RURAL) AS ALMACEN
        FROM lecheria L
        WHERE L.PROMOTOR = :id_prom
          AND COALESCE(L.EN_OPERACION, 0) = 0
        ORDER BY TRIM(L.ALMACEN_RURAL), L.LECHER
    ";
    $stmt = $pdo->prepare($sqlL);
    $stmt->execute([':id_prom' => $promotor_id]);
    $lecherias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3) Cuáles tienen inventario CAPTURADO POR EL PROMOTOR (mes/año).
    //    Validamos contra INVENTARIOS_MENSUALES (flujo del promotor),
    //    no contra INVENTARIO_LEP_SUBSIDIADA, que puede traer datos
    //    precargados por Distribución y daría falsos positivos.
    $sqlI = "SELECT 1 FROM inventarios_mensuales
             WHERE CLAVE_LECHERIA = ? AND MES_PERIODO = ? AND ANIO_PERIODO = ? LIMIT 1";
    $stmtI = $pdo->prepare($sqlI);

    // Carpeta donde se guardan los PDFs individuales de inventario.
    $dirInventarios = __DIR__ . '/../datos/promotores';

    $out = [];
    foreach ($lecherias as $l) {
        $lecher = $l['LECHER'];
        $stmtI->execute([$lecher, $mes, $anio]);
        $tieneInv = (bool)$stmtI->fetchColumn();

        // Nombre de PDF: Inventario_{lecher}_{anio}_{mes}.pdf  (mes a 2 dígitos)
        $pdfName = sprintf('Inventario_%s_%04d_%02d.pdf', $lecher, $anio, $mes);
        $pdfPath = $dirInventarios . '/' . $pdfName;
        $pdfExiste = file_exists($pdfPath);

        // TIPO_PUNTO_VENTA = 2 → Distribución Mercantil → mostramos "DM"
        $tipoPV = (int)($l['TIPO_PUNTO_VENTA'] ?? 0);
        $numTiendaMostrar = ($tipoPV === 2) ? 'DM' : ($l['NUM_TIENDA'] ?? '');

        $out[] = [
            'lecher'           => $lecher,
            'num_tienda'       => $numTiendaMostrar,
            'tipo_punto_venta' => $tipoPV,
            'nombre'           => $l['NOMBRE'],
            'almacen'          => $l['ALMACEN'],
            'tiene_inventario' => $tieneInv,
            'pdf'              => $pdfExiste ? $pdfName : null,
        ];
    }

    // 4) Reporte y Requerimiento (se guardan con slug del usuario).
    $slug = preg_replace('/[^A-Za-z0-9]/', '_', $promotor_usuario);

    $dirReportes  = __DIR__ . '/../datos/promotores/reportes';
    $dirReportPDF = __DIR__ . '/../datos/promotores/reportes_pdf';
    $dirReqs      = __DIR__ . '/../datos/promotores/requerimientos';
    $dirReqPDF    = __DIR__ . '/../datos/promotores/requerimientos_pdf';

    $repJson = sprintf('reporte_%04d_%02d_%s.json', $anio, $mes, $slug);
    $repPDF  = sprintf('Reporte_%04d_%02d_%s.pdf',  $anio, $mes, $slug);

    $reqJson = sprintf('req_%04d_%02d_%s.json',     $anio, $mes, $slug);

    // Lookup pdf_nombre para requerimiento desde SQLite
    $reqPDF = null; $mesDest = null; $anioDest = null;
    try {
        $dbSql = DatabaseSQLite::getInstance();
        $stmtReq = $dbSql->prepare("
            SELECT pdf_nombre, mes_destino, anio_destino
            FROM requerimiento_dotacion
            WHERE promotor = ? AND mes_base = ? AND anio_base = ?
            ORDER BY fecha_captura DESC LIMIT 1
        ");
        $stmtReq->execute([$promotor_id, $mes, $anio]);
        $rowReq = $stmtReq->fetch();
        if ($rowReq && $rowReq['pdf_nombre']) {
            $reqPDF   = $rowReq['pdf_nombre'];
            $mesDest  = (int)($rowReq['mes_destino']  ?? 0);
            $anioDest = (int)($rowReq['anio_destino'] ?? 0);
        } elseif ($rowReq && $rowReq['mes_destino']) {
            $mesDest  = (int)$rowReq['mes_destino'];
            $anioDest = (int)$rowReq['anio_destino'];
            $reqPDF   = sprintf('Requerimiento_%04d_%02d_%s.pdf', $anioDest, $mesDest, $slug);
        }
    } catch (Throwable $ignored) {}
    if (!$mesDest) {
        $mesDest = $mes + 2; $anioDest = $anio;
        while ($mesDest > 12) { $mesDest -= 12; $anioDest++; }
        if (!$reqPDF) $reqPDF = sprintf('Requerimiento_%04d_%02d_%s.pdf', $anioDest, $mesDest, $slug);
    }

    // Reporte mensual: también verificar bloqueado en SQLite
    $repBloqueado = false; $reqBloqueado = false;
    try {
        $dbSql2 = DatabaseSQLite::getInstance();
        $stmtBlk = $dbSql2->prepare("SELECT MAX(bloqueado) AS b FROM reporte_mensual_lecher WHERE usuario_captura = ? AND mes = ? AND anio = ?");
        $stmtBlk->execute([$promotor_usuario, $mes, $anio]);
        $repBloqueado = (bool)($stmtBlk->fetchColumn());
        $stmtBlk2 = $dbSql2->prepare("SELECT MAX(bloqueado) AS b FROM requerimiento_dotacion WHERE promotor = ? AND mes_base = ? AND anio_base = ?");
        $stmtBlk2->execute([$promotor_id, $mes, $anio]);
        $reqBloqueado = (bool)($stmtBlk2->fetchColumn());
    } catch (Throwable $ignored) {}

    $reporte = [
        'existe'    => file_exists($dirReportes . '/' . $repJson),
        'pdf'       => file_exists($dirReportPDF . '/' . $repPDF) ? $repPDF : null,
        'bloqueado' => $repBloqueado,
    ];

    $requerimiento = [
        'existe'      => file_exists($dirReqs . '/' . $reqJson),
        'pdf'         => file_exists($dirReqPDF . '/' . $reqPDF) ? $reqPDF : null,
        'mes_destino' => $mesDest,
        'anio_destino' => $anioDest,
        'bloqueado'   => $reqBloqueado,
    ];

    $resp = [
        'status'   => 'success',
        'promotor' => [
            'id'      => $promotor_id,
            'nombre'  => $promotor_nombre,
            'usuario' => $promotor_usuario,
        ],
        'mes'           => $mes,
        'anio'          => $anio,
        'lecherias'     => $out,
        'reporte'       => $reporte,
        'requerimiento' => $requerimiento,
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
