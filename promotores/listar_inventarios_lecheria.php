<?php
require_once __DIR__ . '/../includes/session_guard.php';
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    echo json_encode([]); exit();
}
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

$clave = trim($_GET['clave'] ?? '');
$anio  = trim($_GET['anio']  ?? '');
if ($clave === '') { echo json_encode([]); exit; }
try {
    $db = DatabaseSQLite::getInstance();
    // SQLite: bind params, ANIO_PERIODO directo (más confiable que parsear FECHA).
    $where = "WHERE UPPER(CLAVE_LECHERIA) = UPPER(:clave)";
    $params = [':clave' => $clave];
    if ($anio !== '') {
        $where .= " AND ANIO_PERIODO = :anio";
        $params[':anio'] = (int)$anio;
    }
    $sql = "SELECT ID, CLAVE_LECHERIA, FECHA, MUNICIPIO, COMUNIDAD,
                   ESTADO, PDF_RUTA,
                   FECHA_CAPTURA AS CREATED_AT,
                   FECHA_CAPTURA AS UPDATED_AT,
                   MES_PERIODO, ANIO_PERIODO,
                   FIN_CAJA, FIN_SOBRES, FIN_LITROS,
                   VENTA_LITROS, ABASTO_LITROS
            FROM inventarios_mensuales
            $where
            ORDER BY FECHA DESC
            LIMIT 100";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $baseDir = __DIR__ . '/../datos/promotores/';
    foreach ($rows as &$row) {
        $ruta = trim($row['PDF_RUTA'] ?? '');

        // Fallback: si PDF_RUTA quedó vacío en BD pero el archivo del mes existe,
        // reconstruimos el nombre canónico para que la fila muestre "Ver" igual.
        if ($ruta === '' && !empty($row['MES_PERIODO']) && !empty($row['ANIO_PERIODO'])) {
            $mes  = sprintf('%02d', (int)$row['MES_PERIODO']);
            $anio = (int)$row['ANIO_PERIODO'];
            $rutaCalc = "Inventario_{$row['CLAVE_LECHERIA']}_{$anio}_{$mes}.pdf";
            if (file_exists($baseDir . $rutaCalc)) {
                $ruta = $rutaCalc;
                $row['PDF_RUTA'] = $rutaCalc;
            }
        }

        $row['pdf_existe'] = ($ruta !== '' && file_exists($baseDir . $ruta)) ? 1 : 0;
    }
    unset($row);

    array_walk_recursive($rows, function (&$v) {
        if (is_string($v)) $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8');
    });
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}