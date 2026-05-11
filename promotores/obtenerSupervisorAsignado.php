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
session_start();
session_write_close();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../Database.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit();
}

$usuario = $_SESSION['usuario'];

try {
    $pdo = Database::getInstance();

    // 1) Conteo total de lecherías ACTIVAS del promotor.
    $sqlTotal = "SELECT COUNT(*) AS TOTAL
                 FROM LECHERIA L
                 INNER JOIN USUARIOS_INVENTARIOS U ON L.PROMOTOR = U.CLAVE_ROL
                 WHERE U.USUARIO = :usuario
                   AND L.EFD_NUMERO = 20
                   AND COALESCE(L.EN_OPERACION, 0) = 0";
    $stmtT = $pdo->prepare($sqlTotal);
    $stmtT->execute([':usuario' => $usuario]);
    $total = (int)$stmtT->fetchColumn();

    // 2) Por cada supervisor que tiene lecherías de este promotor,
    //    contamos cuántas son. Tomamos el de mayor cobertura.
    $sql = "
        SELECT FIRST 1
            M.ID_SUPERVISOR AS ID,
            COUNT(*)        AS COBERTURA
        FROM MAPEO_SUPERVISOR_LECHERIA M
        JOIN LECHERIA L ON L.LECHER = M.LECHER
        JOIN USUARIOS_INVENTARIOS U ON U.CLAVE_ROL = L.PROMOTOR
        WHERE U.USUARIO = :usuario
          AND L.EFD_NUMERO = 20
          AND COALESCE(L.EN_OPERACION, 0) = 0
        GROUP BY M.ID_SUPERVISOR
        ORDER BY COUNT(*) DESC
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
    //    Buscamos primero en USUARIOS_INVENTARIOS (rol supervisor).
    $sqlNom = "SELECT FIRST 1 NOMBRE FROM USUARIOS_INVENTARIOS
               WHERE CLAVE_ROL = :id AND ROL = '1'";
    $stmtN = $pdo->prepare($sqlNom);
    $stmtN->execute([':id' => $idSup]);
    $nombre = $stmtN->fetchColumn();

    // Fallback a tabla SUPERVISOR si existe.
    if (!$nombre) {
        try {
            $sqlSup = "SELECT FIRST 1 SPV_NOMBRE FROM SUPERVISOR WHERE SPV_NUMERO = :id";
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
