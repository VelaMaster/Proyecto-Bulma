<?php
// ────────────────────────────────────────────────────────────────────
//  Devuelve el avance de cada promotor del supervisor para un mes/año.
//  Salida: { status, mes, anio, promotores: [
//    { id, nombre, total_lecherias, capturadas, faltantes, porcentaje }
//  ] }
//  Sólo cuenta lecherías que estén asignadas a ESTE supervisor en
//  MAPEO_SUPERVISOR_LECHERIA, evitando contar lecherías del promotor
//  que ya no son de este supervisor.
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

    $sql = "
        SELECT
            P.PMT_NUMERO  AS ID,
            P.PMT_NOMBRE  AS NOMBRE,
            COUNT(DISTINCT L.LECHER) AS TOTAL,
            COUNT(DISTINCT CASE WHEN I.LECHER IS NOT NULL THEN L.LECHER END) AS CAPTURADAS
        FROM MAPEO_SUPERVISOR_LECHERIA M
        JOIN LECHERIA L  ON M.LECHER = L.LECHER
        JOIN PROMOTOR P  ON L.PROMOTOR = P.PMT_NUMERO
        LEFT JOIN INVENTARIO_LEP_SUBSIDIADA I
               ON I.LECHER = L.LECHER
              AND I.MES_PERIODO  = :mes
              AND I.ANIO_PERIODO = :anio
        WHERE M.ID_SUPERVISOR = :id_sup
          AND P.PMT_ACTIVO = 'S'
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
