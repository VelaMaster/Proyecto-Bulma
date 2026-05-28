<?php
require_once __DIR__ . '/../includes/session_guard.php';
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    echo json_encode([]); exit();
}
require_once __DIR__ . '/../Database.php';

$clave = trim($_GET['clave'] ?? '');
$anio  = trim($_GET['anio']  ?? '');
if ($clave === '') { echo json_encode([]); exit; }
try {
    $db = Database::getInstance();
    $clave_limpia = str_replace("'", "''", $clave);
    $where  = "WHERE UPPER(CLAVE_LECHERIA) = UPPER('$clave_limpia')";
    if ($anio !== '') {
        $anio_int = (int)$anio;
        $where .= " AND EXTRACT(YEAR FROM FECHA) = $anio_int";
    }
    $sql = "SELECT FIRST 100
                ID, CLAVE_LECHERIA, FECHA, MUNICIPIO, COMUNIDAD,
                ESTADO, PDF_RUTA, CREATED_AT, UPDATED_AT,
                MES_PERIODO, ANIO_PERIODO,
                FIN_CAJA, FIN_SOBRES, FIN_LITROS,
                VENTA_LITROS, ABASTO_LITROS
            FROM INVENTARIOS_MENSUALES
            $where
            ORDER BY FECHA DESC";
    $stmt = $db->query($sql);
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