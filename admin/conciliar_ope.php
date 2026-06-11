<?php
// admin/conciliar_ope.php
// Conciliación mensual con el OPE de Distribución.
// Entrada: textarea con CLAVE_LECHERIA (una por línea) — pegado del OPE.
// Acción: marca EN_OPERACION=0 a las del OPE y EN_OPERACION=1 al resto.
// Muestra preview antes de aplicar (dryrun=1) y resumen al confirmar.

require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

$db = DatabaseSQLite::getInstance();

$pegado   = $_POST['claves'] ?? '';
$accion   = $_POST['accion'] ?? '';   // 'preview' | 'aplicar'
$mensaje  = null;
$resumen  = null;
$preview  = null;

function _parse_claves(string $raw): array {
    // Acepta una clave por línea, también CSV (toma 1ra col), tabuladas o coma-separadas.
    $set = [];
    foreach (preg_split('/[\r\n]+/', $raw) as $linea) {
        $linea = trim($linea);
        if ($linea === '') continue;
        // Primera columna si vienen separadas por coma, tab o ;
        $parts = preg_split('/[\t,;]/', $linea);
        $v = trim($parts[0] ?? '');
        if (preg_match('/^\d{8,12}$/', $v)) $set[(int)$v] = true;
    }
    return array_keys($set);
}

if ($accion === 'preview' || $accion === 'aplicar') {
    $claves = _parse_claves($pegado);
    if (empty($claves)) {
        $mensaje = ['err', 'No se detectaron claves de lechería válidas (8–12 dígitos por línea).'];
    } else {
        // Lecherías que SÍ existen en SQLite
        $ph = implode(',', array_fill(0, count($claves), '?'));
        $stmt = $db->prepare("SELECT LECHER FROM lecheria WHERE LECHER IN ($ph)");
        $stmt->execute($claves);
        $existen = array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_NUM), 0));
        $faltantes = array_diff($claves, $existen);

        // Estado actual
        $totalSqlite = (int)$db->query("SELECT COUNT(*) FROM lecheria")->fetchColumn();
        $opAntes     = (int)$db->query("SELECT COUNT(*) FROM lecheria WHERE EN_OPERACION=0")->fetchColumn();
        $offAntes    = (int)$db->query("SELECT COUNT(*) FROM lecheria WHERE EN_OPERACION=1")->fetchColumn();

        if ($accion === 'preview') {
            // Cuántas pasan a 0 (no estaban en 0) y cuántas pasan a 1 (no estaban en 1)
            $stmt = $db->prepare("SELECT COUNT(*) FROM lecheria WHERE LECHER IN ($ph) AND COALESCE(EN_OPERACION,0)<>0");
            $stmt->execute($claves);
            $activaran = (int)$stmt->fetchColumn();

            $stmt = $db->prepare("SELECT COUNT(*) FROM lecheria WHERE LECHER NOT IN ($ph) AND COALESCE(EN_OPERACION,0)<>1");
            $stmt->execute($claves);
            $desactivaran = (int)$stmt->fetchColumn();

            $preview = [
                'claves_recibidas' => count($claves),
                'en_sqlite'        => count($existen),
                'faltantes'        => array_values($faltantes),
                'pasaran_a_op'     => $activaran,
                'pasaran_a_off'    => $desactivaran,
                'op_antes'         => $opAntes,
                'off_antes'        => $offAntes,
            ];
        } else { // aplicar
            $db->beginTransaction();
            try {
                $stmt1 = $db->prepare("UPDATE lecheria SET EN_OPERACION=1 WHERE LECHER NOT IN ($ph)");
                $stmt1->execute($claves);
                $nOff = $stmt1->rowCount();

                $stmt2 = $db->prepare("UPDATE lecheria SET EN_OPERACION=0 WHERE LECHER IN ($ph)");
                $stmt2->execute($claves);
                $nOp = $stmt2->rowCount();

                $db->commit();
                DatabaseSQLite::setConfig('last_conciliacion_ope', date('Y-m-d H:i:s'));
                DatabaseSQLite::setConfig('last_conciliacion_ope_n', (string)count($claves));

                $resumen = [
                    'aplicado'         => true,
                    'claves_aplicadas' => count($claves),
                    'en_sqlite'        => count($existen),
                    'faltantes'        => array_values($faltantes),
                    'activadas'        => $nOp,
                    'desactivadas'     => $nOff,
                    'op_despues'       => (int)$db->query("SELECT COUNT(*) FROM lecheria WHERE EN_OPERACION=0")->fetchColumn(),
                    'off_despues'      => (int)$db->query("SELECT COUNT(*) FROM lecheria WHERE EN_OPERACION=1")->fetchColumn(),
                ];
                $mensaje = ['ok', 'Conciliación aplicada correctamente.'];
            } catch (\Throwable $e) {
                $db->rollBack();
                $mensaje = ['err', 'Error al aplicar: ' . $e->getMessage()];
            }
        }
    }
}

$ultima = DatabaseSQLite::getConfig('last_conciliacion_ope') ?: 'nunca';
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Conciliar OPE — Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="/main_md3.css">
    <script type="module" src="https://esm.run/@material/web@1.0.0/all.js"></script>
    <style>
        body { background:#141218; color:#E6E1E5; font-family:'Roboto',sans-serif; padding: 24px; margin:0; }
        .wrap { max-width: 920px; margin: 0 auto; }
        h1 { font-size:1.6rem; font-weight:500; margin: 0 0 4px; }
        .sub { opacity:.7; margin: 0 0 22px; font-size:.92rem; }
        .card { background:#211f26; border-radius:18px; padding: 22px 22px 18px; margin-bottom: 18px; }
        textarea { width:100%; min-height: 280px; background:#1c1b1f; color:#E6E1E5; border:1px solid #49454F; border-radius:12px; padding:12px; font-family:'Roboto Mono',monospace; font-size:.85rem; resize:vertical; }
        .actions { display:flex; gap:10px; justify-content:flex-end; margin-top:14px; }
        .msg { padding:12px 14px; border-radius:10px; margin-bottom:14px; }
        .msg.ok  { background: rgba(76,175,80,.16); color:#a5d6a7; }
        .msg.err { background: rgba(244,67,54,.16); color:#ef9a9a; }
        table { width:100%; border-collapse:collapse; margin-top:8px; }
        td, th { padding:8px 10px; border-bottom: 1px solid #322e38; text-align:left; }
        th { opacity:.75; font-weight:500; font-size:.85rem; }
        .num { font-variant-numeric: tabular-nums; text-align:right; font-weight:500; }
        code { background:#0f0e12; padding: 2px 6px; border-radius:4px; }
        .hint { opacity:.65; font-size:.85rem; margin: 6px 0 0; }
        a.back { color:#cfbcff; text-decoration:none; font-size:.9rem; }
    </style>
</head>
<body>
<div class="wrap">
    <p><a class="back" href="/admin/index.php">‹ Volver al panel</a></p>
    <h1>Conciliar OPE de Distribución</h1>
    <p class="sub">Última conciliación: <code><?= htmlspecialchars($ultima) ?></code></p>

    <?php if ($mensaje): ?>
        <div class="msg <?= $mensaje[0] ?>"><?= htmlspecialchars($mensaje[1]) ?></div>
    <?php endif; ?>

    <div class="card">
        <h3 style="margin:0 0 6px">Pegar claves del OPE</h3>
        <p class="hint">Una clave de lechería por línea (8–12 dígitos). Acepta CSV: toma la primera columna. Mezcla DICONSA + DIST si aplica.</p>
        <form method="post">
            <textarea name="claves" placeholder="2008810400&#10;2012042300&#10;2012051000&#10;..."><?= htmlspecialchars($pegado) ?></textarea>
            <div class="actions">
                <md-text-button type="submit" name="accion" value="preview">
                    <md-icon slot="icon">visibility</md-icon> Vista previa
                </md-text-button>
                <md-filled-button type="submit" name="accion" value="aplicar"
                    onclick="return confirm('Esto modificará EN_OPERACION en lecheria. ¿Aplicar?')">
                    <md-icon slot="icon">play_arrow</md-icon> Aplicar conciliación
                </md-filled-button>
            </div>
        </form>
    </div>

    <?php if ($preview): ?>
        <div class="card">
            <h3 style="margin:0 0 10px">Vista previa</h3>
            <table>
                <tr><th>Claves recibidas</th><td class="num"><?= $preview['claves_recibidas'] ?></td></tr>
                <tr><th>De ésas, presentes en SQLite</th><td class="num"><?= $preview['en_sqlite'] ?></td></tr>
                <tr><th>Faltan en SQLite</th><td class="num"><?= count($preview['faltantes']) ?></td></tr>
                <tr><th>Pasarán a operando (EN_OPERACION=0)</th><td class="num">+<?= $preview['pasaran_a_op'] ?></td></tr>
                <tr><th>Pasarán a no-operando (EN_OPERACION=1)</th><td class="num">+<?= $preview['pasaran_a_off'] ?></td></tr>
                <tr><th>Estado actual: operando / no-operando</th><td class="num"><?= $preview['op_antes'] ?> / <?= $preview['off_antes'] ?></td></tr>
            </table>
            <?php if (!empty($preview['faltantes'])): ?>
                <details style="margin-top:14px">
                    <summary>Claves del OPE que NO existen en SQLite (<?= count($preview['faltantes']) ?>)</summary>
                    <p style="font-family:monospace; font-size:.85rem"><?= implode(', ', array_map('htmlspecialchars', $preview['faltantes'])) ?></p>
                </details>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($resumen): ?>
        <div class="card">
            <h3 style="margin:0 0 10px">Resultado aplicado</h3>
            <table>
                <tr><th>Claves aplicadas</th><td class="num"><?= $resumen['claves_aplicadas'] ?></td></tr>
                <tr><th>Activadas (UPDATE EN_OPERACION=0)</th><td class="num"><?= $resumen['activadas'] ?></td></tr>
                <tr><th>Desactivadas (UPDATE EN_OPERACION=1)</th><td class="num"><?= $resumen['desactivadas'] ?></td></tr>
                <tr><th>Estado final: operando / no-operando</th><td class="num"><?= $resumen['op_despues'] ?> / <?= $resumen['off_despues'] ?></td></tr>
                <tr><th>Faltantes (no en SQLite)</th><td class="num"><?= count($resumen['faltantes']) ?></td></tr>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
