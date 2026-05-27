<?php
/**
 * listar_docs_lecheria.php
 * Devuelve registros de Reporte Mensual o Requerimiento para una lechería.
 * GET params:
 *   clave  — clave de lechería
 *   tipo   — "reporte" | "requerimiento"
 */
require_once __DIR__ . '/../includes/session_guard.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    http_response_code(401);
    echo json_encode(['error' => true, 'mensaje' => 'Sesión no válida']);
    exit;
}

$clave = trim($_GET['clave'] ?? '');
$tipo  = trim($_GET['tipo']  ?? '');

if ($clave === '' || !in_array($tipo, ['reporte', 'requerimiento'], true)) {
    http_response_code(400);
    echo json_encode(['error' => true, 'mensaje' => 'Parámetros inválidos']);
    exit;
}

require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

try {
    $db      = DatabaseSQLite::getInstance();
    $slugUsr = preg_replace('/[^A-Za-z0-9]/', '_', $_SESSION['usuario']);

    if ($tipo === 'reporte') {
        $stmt = $db->prepare("
            SELECT mes, anio, fecha_captura, periodo_inicio, periodo_fin,
                   promotor, supervisor
            FROM reporte_mensual_lecher
            WHERE clave_lecheria = ?
            ORDER BY anio DESC, mes DESC
        ");
        $stmt->execute([$clave]);
        $rows = $stmt->fetchAll();

        $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                  'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

        $resultado = [];
        $dirPdf    = __DIR__ . '/../datos/promotores/reportes_pdf/';
        foreach ($rows as $r) {
            $nombre    = sprintf('Reporte_%04d_%02d_%s.pdf', $r['anio'], $r['mes'], $slugUsr);
            $pdfExiste = file_exists($dirPdf . $nombre);
            $resultado[] = [
                'mes'          => $r['mes'],
                'anio'         => $r['anio'],
                'label'        => ($meses[$r['mes']] ?? $r['mes']) . ' ' . $r['anio'],
                'fecha_captura'=> $r['fecha_captura'],
                'periodo_ini'  => $r['periodo_inicio'],
                'periodo_fin'  => $r['periodo_fin'],
                'pdf_existe'   => $pdfExiste ? 1 : 0,
                'pdf_url'      => $pdfExiste
                    ? 'ver_pdf.php?archivo=' . urlencode('reportes_pdf/' . $nombre)
                    : null,
            ];
        }

    } else { // requerimiento
        $stmt = $db->prepare("
            SELECT mes_base, anio_base, fecha_captura,
                   familias, beneficiarios, req_actual
            FROM requerimiento_dotacion
            WHERE clave_lecheria = ?
            ORDER BY anio_base DESC, mes_base DESC
        ");
        $stmt->execute([$clave]);
        $rows = $stmt->fetchAll();

        $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                  'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

        $resultado = [];
        $dirPdf    = __DIR__ . '/../datos/promotores/requerimientos_pdf/';
        foreach ($rows as $r) {
            $nombre    = sprintf('Requerimiento_%04d_%02d_%s.pdf', $r['anio_base'], $r['mes_base'], $slugUsr);
            $pdfExiste = file_exists($dirPdf . $nombre);
            $resultado[] = [
                'mes'          => $r['mes_base'],
                'anio'         => $r['anio_base'],
                'label'        => ($meses[$r['mes_base']] ?? $r['mes_base']) . ' ' . $r['anio_base'],
                'fecha_captura'=> $r['fecha_captura'],
                'familias'     => $r['familias'],
                'beneficiarios'=> $r['beneficiarios'],
                'req_actual'   => $r['req_actual'],
                'pdf_existe'   => $pdfExiste ? 1 : 0,
                'pdf_url'      => $pdfExiste
                    ? 'ver_pdf.php?archivo=' . urlencode('requerimientos_pdf/' . $nombre)
                    : null,
            ];
        }
    }

    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'mensaje' => 'Error BD: ' . $e->getMessage()]);
}
