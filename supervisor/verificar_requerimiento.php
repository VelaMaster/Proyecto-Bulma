<?php
// Marca como "verificado" (aprobado=1) los requerimientos de un mes/anio
// para las lecherías a cargo del supervisor. Opcionalmente filtra por
// almacén o por clave_lecheria.
require_once __DIR__ . '/../includes/session_guard.php';
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    http_response_code(401);
    echo json_encode(['status'=>'error','message'=>'Acceso denegado.']);
    exit;
}
$idSup = (int)($_SESSION['clave_rol'] ?? 0);
$usr   = (string)$_SESSION['usuario'];

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$mes  = (int)($in['mes'] ?? 0);
$anio = (int)($in['anio'] ?? 0);
$alm  = trim((string)($in['almacen'] ?? ''));
$claves = $in['claves'] ?? null;                  // opcional: lista de lecherías
$desmarcar = !empty($in['desmarcar']);

if ($mes < 1 || $mes > 12 || $anio < 2000) {
    http_response_code(422);
    echo json_encode(['status'=>'error','message'=>'Mes/año inválido.']);
    exit;
}

try {
    $db = DatabaseSQLite::getInstance();
    foreach (['aprobado INTEGER DEFAULT 0',
              'supervisor_aprobador TEXT',
              'fecha_aprobacion TEXT'] as $c) {
        try { $db->exec("ALTER TABLE requerimiento_dotacion ADD COLUMN $c"); } catch (Throwable $e) {}
    }

    // Lecherías del supervisor (con filtro opcional de almacén o claves)
    $sql = "SELECT TRIM(CAST(L.LECHER AS TEXT)) AS K
              FROM lecheria L
              JOIN mapeo_supervisor_lecheria M
                ON M.LECHER = L.LECHER AND M.ID_SUPERVISOR = :sup
             WHERE 1=1";
    $par = [':sup'=>$idSup];
    if ($alm !== '') {
        $sql .= " AND UPPER(TRIM(L.ALMACEN_RURAL)) = :alm";
        $par[':alm'] = strtoupper($alm);
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($par);
    $misClaves = array_map(fn($r)=>trim($r['K']), $stmt->fetchAll(PDO::FETCH_ASSOC));

    if (is_array($claves) && count($claves)) {
        $set = array_flip(array_map('strval', $claves));
        $misClaves = array_values(array_filter($misClaves, fn($k)=>isset($set[(string)$k])));
    }
    if (!count($misClaves)) {
        echo json_encode(['status'=>'success','aprobadas'=>0,'mensaje'=>'Sin lecherías para procesar.']);
        exit;
    }

    $valor = $desmarcar ? 0 : 1;
    $upd = $db->prepare(
        "UPDATE requerimiento_dotacion
            SET aprobado = :v,
                supervisor_aprobador = :u,
                fecha_aprobacion = datetime('now','localtime')
          WHERE clave_lecheria = :k
            AND mes_base = :mes AND anio_base = :anio"
    );
    $db->beginTransaction();
    $n = 0;
    foreach ($misClaves as $k) {
        $upd->execute([':v'=>$valor, ':u'=>$usr, ':k'=>$k, ':mes'=>$mes, ':anio'=>$anio]);
        $n += $upd->rowCount();
    }
    $db->commit();

    echo json_encode([
        'status'    => 'success',
        'aprobadas' => $n,
        'desmarcar' => $desmarcar,
        'mensaje'   => $desmarcar
            ? "Se quitó la verificación de $n lecherías."
            : "Se marcaron como verificadas $n lecherías.",
    ]);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
