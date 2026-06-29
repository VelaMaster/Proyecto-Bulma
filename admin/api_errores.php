<?php
// admin/api_errores.php
// Endpoint JSON para el visor de errores. Acciones soportadas:
//   - limpiar         : borra TODOS los registros
//   - limpiar_tipo    : borra los de un tipo (php|pdo|sync|app)
//   - limpiar_nivel   : borra los de un nivel (error|warning|notice|info)
//   - limpiar_antes   : borra registros más viejos que N días
//   - borrar          : borra un id específico
//   - detalle         : devuelve la fila completa (incluido contexto JSON)
//   - export_csv      : devuelve CSV de los filtros actuales
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

$pdo = DatabaseSQLite::getInstance();

// Export CSV no es JSON
if (($_GET['accion'] ?? '') === 'export_csv') {
    $where = []; $args = [];
    if (!empty($_GET['tipo']))   { $where[] = 'tipo = ?';    $args[] = $_GET['tipo']; }
    if (!empty($_GET['nivel']))  { $where[] = 'nivel = ?';   $args[] = $_GET['nivel']; }
    if (!empty($_GET['desde']))  { $where[] = 'fecha >= ?';  $args[] = $_GET['desde']; }
    if (!empty($_GET['q']))      { $where[] = '(mensaje LIKE ? OR archivo LIKE ?)';
                                    $args[] = '%'.$_GET['q'].'%'; $args[] = '%'.$_GET['q'].'%'; }
    $sql = "SELECT id, fecha, tipo, nivel, mensaje, archivo, linea, usuario
              FROM errores_log " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
             ORDER BY id DESC LIMIT 5000";
    $stmt = $pdo->prepare($sql); $stmt->execute($args);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="errores_' . date('Ymd_Hi') . '.csv"');
    $fh = fopen('php://output', 'w');
    fputcsv($fh, ['id','fecha','tipo','nivel','mensaje','archivo','linea','usuario']);
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) fputcsv($fh, $r);
    fclose($fh);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
$body = json_decode(file_get_contents('php://input'), true) ?: [];

try {
    switch ($body['accion'] ?? ($_GET['accion'] ?? '')) {
        case 'limpiar':
            $pdo->exec("DELETE FROM errores_log");
            echo json_encode(['ok' => true]);
            break;

        case 'limpiar_tipo':
            $pdo->prepare("DELETE FROM errores_log WHERE tipo=?")->execute([$body['tipo'] ?? '']);
            echo json_encode(['ok' => true]);
            break;

        case 'limpiar_nivel':
            $pdo->prepare("DELETE FROM errores_log WHERE nivel=?")->execute([$body['nivel'] ?? '']);
            echo json_encode(['ok' => true]);
            break;

        case 'limpiar_antes':
            $dias = max(1, (int)($body['dias'] ?? 30));
            $stmt = $pdo->prepare("DELETE FROM errores_log WHERE fecha < datetime('now','localtime', ?)");
            $stmt->execute(["-{$dias} days"]);
            echo json_encode(['ok' => true, 'borrados' => $stmt->rowCount()]);
            break;

        case 'borrar':
            $pdo->prepare("DELETE FROM errores_log WHERE id=?")->execute([(int)($body['id'] ?? 0)]);
            echo json_encode(['ok' => true]);
            break;

        case 'detalle':
            $stmt = $pdo->prepare("SELECT * FROM errores_log WHERE id=?");
            $stmt->execute([(int)($body['id'] ?? 0)]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) { http_response_code(404); echo json_encode(['ok'=>false,'mensaje'=>'No existe']); break; }
            $row['contexto_decoded'] = $row['contexto'] ? json_decode($row['contexto'], true) : null;
            echo json_encode(['ok' => true, 'fila' => $row]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'mensaje' => 'Acción desconocida']);
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}
