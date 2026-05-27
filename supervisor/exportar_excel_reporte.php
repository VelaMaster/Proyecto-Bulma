<?php
// supervisor/exportar_excel_reporte.php
// GET: mes, anio
// Descarga CSV del reporte mensual de las lecherías del supervisor
require_once __DIR__ . '/../includes/session_guard.php';
session_write_close();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    http_response_code(403); exit('Acceso denegado');
}
$id_supervisor = $_SESSION['clave_rol'] ?? null;
if (!$id_supervisor) { http_response_code(400); exit('Sin ID supervisor'); }

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

$mes  = isset($_GET['mes'])  ? (int)$_GET['mes']  : 0;
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : 0;
if ($mes < 1 || $mes > 12 || $anio < 2000) {
    http_response_code(400); exit('Parámetros inválidos');
}

try {
    $pdo = Database::getInstance();

    $sql = "
        SELECT TRIM(L.LECHER)        AS LECHER,
               TRIM(L.NUM_TIENDA)    AS NUM_TIENDA,
               TRIM(L.ALMACEN_RURAL) AS ALMACEN,
               L.TIPO_PUNTO_VENTA    AS TIPO_PUNTO_VENTA
        FROM LECHERIA L
        JOIN PROMOTOR P ON P.PMT_NUMERO = L.PROMOTOR
        WHERE P.PMT_ACTIVO = 'S'
          AND COALESCE(L.EN_OPERACION, 0) = 0
          AND EXISTS (
                SELECT 1 FROM MAPEO_SUPERVISOR_LECHERIA M
                WHERE M.ID_SUPERVISOR = :id_sup
                  AND TRIM(M.LECHER) = TRIM(L.LECHER)
              )
        ORDER BY TRIM(L.ALMACEN_RURAL), TRIM(L.LECHER)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_sup' => $id_supervisor]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sqlite = DatabaseSQLite::getInstance();
    $stmtSQ = $sqlite->prepare(
        "SELECT * FROM reporte_mensual_lecher WHERE mes = :mes AND anio = :anio"
    );
    $stmtSQ->execute([':mes' => $mes, ':anio' => $anio]);
    $reportes = [];
    foreach ($stmtSQ->fetchAll() as $rq) {
        $reportes[trim((string)$rq['clave_lecheria'])] = $rq;
    }

    $stmtN = $pdo->prepare("SELECT FIRST 1 NOMBRE FROM USUARIOS_INVENTARIOS
                            WHERE CLAVE_ROL = :id AND ROL = '1'");
    $stmtN->execute([':id' => $id_supervisor]);
    $nombreSup = trim((string)$stmtN->fetchColumn());
    if ($nombreSup === '') $nombreSup = 'Supervisor #' . $id_supervisor;

    $mesesNombres = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                     'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    $nombreMes = $mesesNombres[$mes] ?? $mes;

    $slug = preg_replace('/[^A-Za-z0-9]/', '_', $_SESSION['usuario']);
    $filename = "reporte_mensual_{$anio}_{$mes}_{$slug}.csv";

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");

    fputcsv($out, ['REPORTE MENSUAL DE LA OPERACIÓN — ' . strtoupper($nombreMes) . ' ' . $anio]);
    fputcsv($out, ['Supervisor:', mb_convert_encoding($nombreSup, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252')]);
    fputcsv($out, ['Generado:', date('d/m/Y H:i')]);
    fputcsv($out, []);
    fputcsv($out, [
        'Almacén','Punto de Venta','Tienda','Precio',
        'Inv.Ini Cajas','Inv.Ini Sobres','Dot.Recib. Cajas',
        'Total Cajas','Total Sobres',
        'Vend. Cajas','Vend. Sobres',
        'Inv.Fin Cajas','Inv.Fin Sobres',
        'Retiro Cajas','Retiro Sobres',
        'Fam. No Acud.','Sobres Rotos','Sobres Falt.',
        'Observaciones','Promotor','Fecha Captura'
    ]);

    foreach ($rows as $r) {
        $tipo = (int)$r['TIPO_PUNTO_VENTA'];
        $alm  = mb_convert_encoding(
            strtoupper(trim((string)$r['ALMACEN'])) ?: '(SIN ALMACÉN)',
            'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252'
        );

        $numTiendaRaw = trim((string)$r['NUM_TIENDA']);
        $tienda = ($tipo === 2 || $numTiendaRaw === '10101') ? 'DM' : $numTiendaRaw;

        $precio = match($tipo) { 0 => '$4.50', 1, 2 => '$6.50', default => '' };

        $k   = trim((string)$r['LECHER']);
        $rep = $reportes[$k] ?? null;

        if ($rep === null) {
            fputcsv($out, [$alm, $k, $tienda, $precio, 'FALTA']);
        } else {
            fputcsv($out, [
                $alm, $k, $tienda, $precio,
                $rep['inv_ini_cajas'],   $rep['inv_ini_sobres'],
                $rep['dot_recib_cajas'],
                $rep['total_cajas'],     $rep['total_sobres'],
                $rep['vend_cajas'],      $rep['vend_sobres'],
                $rep['inv_fin_cajas'],   $rep['inv_fin_sobres'],
                $rep['retiro_cajas'],    $rep['retiro_sobres'],
                $rep['familias_no_acud'],$rep['sobres_rotos'],  $rep['sobres_falt'],
                $rep['observaciones'],   $rep['promotor'],       $rep['fecha_captura'],
            ]);
        }
    }

    fclose($out);

} catch (Exception $e) {
    if (headers_sent()) exit();
    http_response_code(500);
    header('Content-Type: text/plain');
    exit('Error: ' . $e->getMessage());
}
