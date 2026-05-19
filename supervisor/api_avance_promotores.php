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
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../Database.php';

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
    $pdo = Database::getInstance();

    // "Capturado" significa que el promotor llenó su inventario mensual:
    // se valida contra INVENTARIOS_MENSUALES (la tabla del flujo del
    // promotor), NO contra INVENTARIO_LEP_SUBSIDIADA, que puede traer
    // datos precargados por Distribución y daría falsos positivos.
    $sql = "
        SELECT
            P.PMT_NUMERO  AS ID,
            P.PMT_NOMBRE  AS NOMBRE,
            COUNT(DISTINCT L.LECHER) AS TOTAL,
            COUNT(DISTINCT CASE WHEN IM.CLAVE_LECHERIA IS NOT NULL THEN L.LECHER END) AS CAPTURADAS
        FROM PROMOTOR P
        JOIN LECHERIA L ON L.PROMOTOR = P.PMT_NUMERO
        LEFT JOIN INVENTARIOS_MENSUALES IM
               ON IM.CLAVE_LECHERIA = L.LECHER
              AND IM.MES_PERIODO    = :mes
              AND IM.ANIO_PERIODO   = :anio
        WHERE EXISTS (
                SELECT 1
                FROM MAPEO_SUPERVISOR_LECHERIA M
                JOIN LECHERIA L2 ON M.LECHER = L2.LECHER
                WHERE M.ID_SUPERVISOR = :id_sup
                  AND L2.PROMOTOR = P.PMT_NUMERO
              )
          AND P.PMT_ACTIVO = 'S'
          AND COALESCE(L.EN_OPERACION, 0) = 0   -- 0 = activa, 1 = baja
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
