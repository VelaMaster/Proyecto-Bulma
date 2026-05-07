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
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../Database.php';

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
    $pdo = Database::getInstance();

    // 1) Datos del promotor + usuario asociado, validando que pertenezca al supervisor.
    $sqlP = "
        SELECT FIRST 1 P.PMT_NUMERO, P.PMT_NOMBRE, U.USUARIO
        FROM PROMOTOR P
        JOIN LECHERIA L ON L.PROMOTOR = P.PMT_NUMERO
        JOIN MAPEO_SUPERVISOR_LECHERIA M ON M.LECHER = L.LECHER
        LEFT JOIN USUARIOS_INVENTARIOS U
               ON U.CLAVE_ROL = P.PMT_NUMERO AND U.ROL = '0'
        WHERE M.ID_SUPERVISOR = :id_sup
          AND P.PMT_NUMERO    = :id_prom
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

    // 2) Lecherías de este promotor que están bajo supervisión de este supervisor.
    $sqlL = "
        SELECT TRIM(L.LECHER) AS LECHER,
               TRIM(L.NUM_TIENDA) AS NUM_TIENDA,
               TRIM(L.NOMBRELECH) AS NOMBRE,
               TRIM(L.ALMACEN_RURAL) AS ALMACEN
        FROM LECHERIA L
        JOIN MAPEO_SUPERVISOR_LECHERIA M ON M.LECHER = L.LECHER
        WHERE L.PROMOTOR     = :id_prom
          AND M.ID_SUPERVISOR = :id_sup
        ORDER BY TRIM(L.ALMACEN_RURAL), TRIM(L.LECHER)
    ";
    $stmt = $pdo->prepare($sqlL);
    $stmt->execute([':id_prom' => $promotor_id, ':id_sup' => $id_supervisor]);
    $lecherias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3) Cuáles tienen inventario en BDD para mes/año.
    $sqlI = "SELECT FIRST 1 1 FROM INVENTARIO_LEP_SUBSIDIADA
             WHERE LECHER = ? AND MES_PERIODO = ? AND ANIO_PERIODO = ?";
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

        $out[] = [
            'lecher'           => $lecher,
            'num_tienda'       => $l['NUM_TIENDA'],
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

    // El PDF del requerimiento se nombra con el mes destino (mes_base + 2)
    $mesDest  = $mes + 2;
    $anioDest = $anio;
    while ($mesDest > 12) { $mesDest -= 12; $anioDest++; }
    $reqPDF = sprintf('Requerimiento_%04d_%02d_%s.pdf', $anioDest, $mesDest, $slug);

    $reporte = [
        'existe' => file_exists($dirReportes . '/' . $repJson),
        'pdf'    => file_exists($dirReportPDF . '/' . $repPDF) ? $repPDF : null,
    ];

    $requerimiento = [
        'existe'      => file_exists($dirReqs . '/' . $reqJson),
        'pdf'         => file_exists($dirReqPDF . '/' . $reqPDF) ? $reqPDF : null,
        'mes_destino' => $mesDest,
        'anio_destino' => $anioDest,
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
