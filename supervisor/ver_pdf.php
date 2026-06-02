<?php
// ────────────────────────────────────────────────────────────────────
//  Sirve PDFs (inventario / reporte / requerimiento) al supervisor,
//  validando que el promotor pertenezca a su zona.
//  Parámetros:
//    tipo=inv|rep|req
//    promotor=<PMT_NUMERO>
//    mes=<1-12>
//    anio=<YYYY>
//    lecher=<LECHER>          (sólo para tipo=inv)
//
//  Devuelve el PDF inline. Si no existe, 404.
// ────────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../includes/session_guard.php';
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    http_response_code(401);
    exit('Acceso denegado.');
}
$id_supervisor = $_SESSION['clave_rol'] ?? null;
if (!$id_supervisor) {
    http_response_code(401);
    exit('Sesión inválida.');
}

$tipo     = $_GET['tipo']     ?? '';
$promotor = isset($_GET['promotor']) ? (int)$_GET['promotor'] : 0;
$mes      = isset($_GET['mes'])  ? (int)$_GET['mes']  : 0;
$anio     = isset($_GET['anio']) ? (int)$_GET['anio'] : 0;
$lecher   = trim($_GET['lecher'] ?? '');

if (!in_array($tipo, ['inv','rep','req'], true) ||
    $promotor <= 0 || $mes < 1 || $mes > 12 || $anio < 2000) {
    http_response_code(400);
    exit('Parámetros inválidos.');
}

try {
    $pdo = DatabaseSQLite::getInstance();

    // 1) Validar que el promotor pertenece a este supervisor.
    $sqlV = "
        SELECT P.PMT_NUMERO, U.USUARIO
        FROM promotor P
        JOIN lecheria L ON L.PROMOTOR = P.PMT_NUMERO
        JOIN mapeo_supervisor_lecheria M ON M.LECHER = L.LECHER
        LEFT JOIN usuarios_inventarios U
               ON U.CLAVE_ROL = P.PMT_NUMERO AND U.ROL = '0'
        WHERE M.ID_SUPERVISOR = :id_sup
          AND P.PMT_NUMERO    = :id_prom
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sqlV);
    $stmt->execute([':id_sup' => $id_supervisor, ':id_prom' => $promotor]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(403);
        exit('Promotor no pertenece a este supervisor.');
    }
    $usuario = $row['USUARIO'] ?? '';
    $slug = preg_replace('/[^A-Za-z0-9]/', '_', $usuario);

    // 2) Resolver ruta del PDF según tipo.
    $base = __DIR__ . '/../datos/promotores';
    $ruta = '';
    $nombreDescarga = '';

    if ($tipo === 'inv') {
        if ($lecher === '') { http_response_code(400); exit('Falta lecher.'); }
        // Validar que la lechería esté bajo este supervisor
        $sqlL = "SELECT 1
                 FROM lecheria L
                 JOIN mapeo_supervisor_lecheria M ON M.LECHER = L.LECHER
                 WHERE M.ID_SUPERVISOR = :id_sup
                   AND L.PROMOTOR      = :id_prom
                   AND TRIM(CAST(L.LECHER AS TEXT)) = :lecher
                 LIMIT 1";
        $stmt = $pdo->prepare($sqlL);
        $stmt->execute([
            ':id_sup'  => $id_supervisor,
            ':id_prom' => $promotor,
            ':lecher'  => $lecher,
        ]);
        if (!$stmt->fetchColumn()) {
            http_response_code(403);
            exit('Lechería fuera del alcance del supervisor.');
        }
        $nombre = sprintf('Inventario_%s_%04d_%02d.pdf', $lecher, $anio, $mes);
        $ruta   = $base . '/' . $nombre;
        $nombreDescarga = $nombre;

    } elseif ($tipo === 'rep') {
        $nombre = sprintf('Reporte_%04d_%02d_%s.pdf', $anio, $mes, $slug);
        $ruta   = $base . '/reportes_pdf/' . $nombre;
        $nombreDescarga = $nombre;

    } elseif ($tipo === 'req') {
        // Buscar pdf_nombre en SQLite; fallback a mes_destino/anio_destino; último recurso mes+2
        $nombre = '';
        try {
            $dbSql = DatabaseSQLite::getInstance();
            $stmtSql = $dbSql->prepare("
                SELECT pdf_nombre, mes_destino, anio_destino
                FROM requerimiento_dotacion
                WHERE promotor = ? AND mes_base = ? AND anio_base = ?
                ORDER BY fecha_captura DESC LIMIT 1
            ");
            $stmtSql->execute([$promotor, $mes, $anio]);
            $rowSql = $stmtSql->fetch();
            if ($rowSql && $rowSql['pdf_nombre']) {
                $nombre = $rowSql['pdf_nombre'];
            } elseif ($rowSql && $rowSql['mes_destino'] && $rowSql['anio_destino']) {
                $nombre = sprintf('Requerimiento_%04d_%02d_%s.pdf',
                    (int)$rowSql['anio_destino'], (int)$rowSql['mes_destino'], $slug);
            }
        } catch (Throwable $ignored) {}
        if (!$nombre) {
            $mesDest = $mes + 2; $anioDest = $anio;
            while ($mesDest > 12) { $mesDest -= 12; $anioDest++; }
            $nombre = sprintf('Requerimiento_%04d_%02d_%s.pdf', $anioDest, $mesDest, $slug);
        }
        $ruta   = $base . '/requerimientos_pdf/' . $nombre;
        $nombreDescarga = $nombre;
    }

    // 3) Anti path-traversal: la ruta resuelta debe estar bajo $base.
    $rutaReal = realpath($ruta);
    $baseReal = realpath($base);
    if (!$rutaReal || !$baseReal || strpos($rutaReal, $baseReal) !== 0) {
        http_response_code(404);
        exit('Archivo no encontrado.');
    }
    if (!is_file($rutaReal)) {
        http_response_code(404);
        exit('Archivo no encontrado.');
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $nombreDescarga . '"');
    header('Content-Length: ' . filesize($rutaReal));
    header('Cache-Control: private, max-age=60');
    readfile($rutaReal);
    exit();

} catch (Exception $e) {
    http_response_code(500);
    exit('Error: ' . $e->getMessage());
}
