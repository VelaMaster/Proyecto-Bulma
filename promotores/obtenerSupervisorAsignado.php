<?php
// ────────────────────────────────────────────────────────────────────
//  Devuelve el supervisor asignado al promotor logueado.
//  Lo resuelve a partir de las lecherías del promotor:
//    LECHERIA → MAPEO_SUPERVISOR_LECHERIA → SUPERVISOR (vía PROMOTOR
//    que puede ser supervisor también, o vía la tabla de supervisores).
//
//  Como un promotor podría tener lecherías mapeadas a más de un
//  supervisor (caso raro), elegimos el supervisor que cubra el mayor
//  número de lecherías de ese promotor.
//
//  Salida:
//    { status:"success", supervisor: { id, nombre, lecherias_cubiertas, total_lecherias } }
//    o { status:"error", message:"..." }
// ────────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../includes/session_guard.php';
session_write_close();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit();
}

$usuario = $_SESSION['usuario'];

try {
    $pdo = DatabaseSQLite::getInstance();

    // 1) Conteo total de lecherías ACTIVAS del promotor.
    $sqlTotal = "SELECT COUNT(*) AS TOTAL
                 FROM lecheria L
                 INNER JOIN usuarios_inventarios U ON L.PROMOTOR = U.CLAVE_ROL
                 WHERE U.USUARIO = :usuario
                   AND L.EFD_NUMERO = 20
                   AND COALESCE(L.EN_OPERACION, 0) = 0";
    $stmtT = $pdo->prepare($sqlTotal);
    $stmtT->execute([':usuario' => $usuario]);
    $total = (int)$stmtT->fetchColumn();

    // 2) Por cada supervisor que tiene lecherías de este promotor,
    //    contamos cuántas son. Tomamos el de mayor cobertura.
    $sql = "
        SELECT M.ID_SUPERVISOR AS ID,
               COUNT(*)        AS COBERTURA
        FROM mapeo_supervisor_lecheria M
        JOIN lecheria L                ON L.LECHER = M.LECHER
        JOIN usuarios_inventarios U    ON U.CLAVE_ROL = L.PROMOTOR
        WHERE U.USUARIO = :usuario
          AND L.EFD_NUMERO = 20
          AND COALESCE(L.EN_OPERACION, 0) = 0
        GROUP BY M.ID_SUPERVISOR
        ORDER BY COUNT(*) DESC
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':usuario' => $usuario]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode([
            'status'  => 'success',
            'supervisor' => null,
            'message' => 'No hay supervisor asignado a las lecherías del promotor.',
        ]);
        exit();
    }

    $idSup = (int)$row['ID'];
    $cobertura = (int)$row['COBERTURA'];

    // 3) Nombre del supervisor.
    //    Buscamos primero en usuarios_inventarios (ROL='1' = supervisor).
    $sqlNom = "SELECT NOMBRE FROM usuarios_inventarios
               WHERE CLAVE_ROL = :id AND ROL = '1' LIMIT 1";
    $stmtN = $pdo->prepare($sqlNom);
    $stmtN->execute([':id' => $idSup]);
    $nombre = $stmtN->fetchColumn();

    // Fallback a la tabla supervisor (espejo).
    if (!$nombre) {
        try {
            $sqlSup = "SELECT NOMBRE_SUPERVISOR FROM supervisor WHERE ID_SUPERVISOR = :id LIMIT 1";
            $stmtS = $pdo->prepare($sqlSup);
            $stmtS->execute([':id' => $idSup]);
            $nombre = $stmtS->fetchColumn();
        } catch (Exception $eS) { /* tabla puede no existir, ignoramos */ }
    }

    if (!$nombre) $nombre = 'Supervisor #' . $idSup;
    $nombre = trim((string)$nombre);
    $nombre = mb_convert_encoding($nombre, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');

    echo json_encode([
        'status'     => 'success',
        'supervisor' => [
            'id'                  => $idSup,
            'nombre'              => $nombre,
            'lecherias_cubiertas' => $cobertura,
            'total_lecherias'     => $total,
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
