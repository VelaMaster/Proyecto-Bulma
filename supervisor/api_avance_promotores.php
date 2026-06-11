<?php
// ────────────────────────────────────────────────────────────────────
//  Devuelve el avance de cada promotor del supervisor para un mes/año.
//  Salida: { status, mes, anio, promotores: [
//    { id, nombre, total_lecherias, capturadas, faltantes, porcentaje }
//  ] }
//  Identificamos a los promotores del supervisor vía
//  MAPEO_SUPERVISOR_LECHERIA (les basta con tener UNA lechería mapeada)
//  y contamos TODAS las lecherías activas del promotor, para que el
//  total que ve el supervisor coincida con el que ve el propio promotor.
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
    echo json_encode(['status' => 'error', 'message' => 'ID de supervisor no encontrado en la sesión.']);
    exit();
}

$mes  = isset($_GET['mes'])  ? (int)$_GET['mes']  : 0;
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : 0;
if ($mes < 1 || $mes > 12 || $anio < 2000) {
    echo json_encode(['status' => 'error', 'message' => 'Mes/año inválido.']);
    exit();
}

try {
    $pdo = DatabaseSQLite::getInstance();

    // "Capturado" = el promotor llenó su inventario mensual (inventarios_mensuales).
    // El JOIN compara TRIM(CLAVE_LECHERIA) contra la clave del padrón con y
    // sin sufijo "00" (mismo criterio que InventarioRepositorio::clavesCandidatas).
    $sql = "
        SELECT
            P.PMT_NUMERO  AS ID,
            P.PMT_NOMBRE  AS NOMBRE,
            COUNT(DISTINCT L.LECHER) AS TOTAL,
            COUNT(DISTINCT CASE WHEN IM.CLAVE_LECHERIA IS NOT NULL THEN L.LECHER END) AS CAPTURADAS
        FROM promotor P
        JOIN lecheria L ON L.PROMOTOR = P.PMT_NUMERO
        LEFT JOIN inventarios_mensuales IM
               ON TRIM(IM.CLAVE_LECHERIA) IN (
                       TRIM(CAST(L.LECHER AS TEXT)),
                       TRIM(CAST(L.LECHER AS TEXT)) || '00'
                  )
              AND IM.MES_PERIODO    = :mes
              AND IM.ANIO_PERIODO   = :anio
        WHERE EXISTS (
                SELECT 1
                FROM mapeo_supervisor_lecheria M
                JOIN lecheria L2 ON M.LECHER = L2.LECHER
                WHERE M.ID_SUPERVISOR = :id_sup
                  AND L2.PROMOTOR = P.PMT_NUMERO
              )
          AND P.PMT_ACTIVO = 'S'
          AND COALESCE(L.EN_OPERACION, 0) = 0
        GROUP BY P.PMT_NUMERO, P.PMT_NOMBRE
        ORDER BY P.PMT_NOMBRE
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':mes'    => $mes,
        ':anio'   => $anio,
        ':id_sup' => $id_supervisor,
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $promotores = [];
    foreach ($rows as $r) {
        $total = (int)$r['TOTAL'];
        $cap   = (int)$r['CAPTURADAS'];
        $promotores[] = [
            'id'              => (int)$r['ID'],
            'nombre'          => trim($r['NOMBRE']),
            'total_lecherias' => $total,
            'capturadas'      => $cap,
            'faltantes'       => max(0, $total - $cap),
            'porcentaje'      => $total > 0 ? (int)round($cap * 100 / $total) : 0,
        ];
    }

    array_walk_recursive($promotores, function (&$v) {
        if (is_string($v)) {
            $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        }
    });

    echo json_encode([
        'status'     => 'success',
        'mes'        => $mes,
        'anio'       => $anio,
        'promotores' => $promotores,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
