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
session_start();
require_once __DIR__ . '/../Database.php';

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
    $pdo = Database::getInstance();

    // 1) Validar que el promotor pertenece a este supervisor.
    $sqlV = "
        SELECT FIRST 1 P.PMT_NUMERO, U.USUARIO
        FROM PROMOTOR P
        JOIN LECHERIA L ON L.PROMOTOR = P.PMT_NUMERO
        JOIN MAPEO_SUPERVISOR_LECHERIA M ON M.LECHER = L.LECHER
        LEFT JOIN USUARIOS_INVENTARIOS U
               ON U.CLAVE_ROL = P.PMT_NUMERO AND U.ROL = '0'
        WHERE M.ID_SUPERVISOR = :id_sup
          AND P.PMT_NUMERO    = :id_prom
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
        $sqlL = "SELECT FIRST 1 1
                 FROM LECHERIA L
                 JOIN MAPEO_SUPERVISOR_LECHERIA M ON M.LECHER = L.LECHER
                 WHERE M.ID_SUPERVISOR = :id_sup
                   AND L.PROMOTOR      = :id_prom
                   AND TRIM(L.LECHER)  = :lecher";
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
        // El PDF del requerimiento se llama con el mes destino (mes+2).
        $mesDest  = $mes + 2;
        $anioDest = $anio;
        while ($mesDest > 12) { $mesDest -= 12; $anioDest++; }
        $nombre = sprintf('Requerimiento_%04d_%02d_%s.pdf', $anioDest, $mesDest, $slug);
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
