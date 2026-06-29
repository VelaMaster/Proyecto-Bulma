<?php
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

$pdo = DatabaseSQLite::getInstance();
$filtroTipo = $_GET['tipo'] ?? '';
$where = '';
$args = [];
if ($filtroTipo) { $where = "WHERE tipo=?"; $args[] = $filtroTipo; }
$rows = $pdo->prepare("SELECT id, tipo, nivel, mensaje, archivo, linea, usuario, fecha
                       FROM errores_log $where ORDER BY id DESC LIMIT 200");
$rows->execute($args);
$errores = $rows->fetchAll();

$totales = $pdo->query("SELECT tipo, COUNT(*) c FROM errores_log GROUP BY tipo")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
<meta charset="UTF-8">
<title>Admin · Errores</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
<link rel="stylesheet" href="../main_md3.css">
<style>
  body { font-family:Roboto,sans-serif; background:#141218; color:#E6E1E5; margin:0; padding:24px; }
  .wrap { max-width:1200px; margin:0 auto; }
  .crumb { font-size:.85rem; opacity:.7; margin-bottom:16px; } .crumb a { color:#D0BCFF; text-decoration:none; }
  h1 { font-size:1.4rem; font-weight:500; margin:0 0 12px; }
  .panel { background:#1e1b22; padding:18px; border-radius:16px; border:1px solid #49454F; margin-bottom:18px; }
  .filter-bar { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
  .filter-bar a { padding:6px 14px; border-radius:18px; background:#2b2730; color:#E6E1E5; text-decoration:none; font-size:.82rem; }
  .filter-bar a.active { background:#D0BCFF; color:#21005D; font-weight:600; }
  table { width:100%; border-collapse:collapse; font-size:.85rem; }
  th, td { padding:8px 10px; text-align:left; border-bottom:1px solid #49454F; vertical-align:top; }
  th { font-weight:500; opacity:.7; font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; }
  .pill { display:inline-block; padding:2px 8px; border-radius:8px; font-size:.7rem; font-weight:600; }
  .pill-error   { background:#5C1A1A; color:#FCA5A5; }
  .pill-warning { background:#3D2B0F; color:#FDE68A; }
  .pill-notice  { background:#1E2A44; color:#93C5FD; }
  .pill-info    { background:#1B4332; color:#86EFAC; }
  .msg { font-family:'Courier New',monospace; font-size:.78rem; white-space:pre-wrap; word-break:break-word; max-width:520px; }
  .btn { display:inline-flex; align-items:center; gap:6px; padding:8px 14px; border-radius:18px; border:none; cursor:pointer; font-weight:500; font-size:.82rem; font-family:inherit; }
  .btn-danger { background:#5C1A1A; color:#FCA5A5; }
</style>
</head>
<body>
<div class="wrap">
  <div class="crumb"><a href="index.php">← Panel</a> / Errores</div>
  <h1>Visor de errores</h1>

  <div class="panel">
    <div class="filter-bar">
      <strong style="opacity:.7;font-size:.82rem">Filtrar:</strong>
      <a href="errores.php" class="<?= $filtroTipo==='' ? 'active':'' ?>">Todos</a>
      <?php foreach ($totales as $t): ?>
        <a href="errores.php?tipo=<?= urlencode($t['tipo']) ?>" class="<?= $filtroTipo===$t['tipo']?'active':'' ?>">
          <?= htmlspecialchars($t['tipo']) ?> (<?= (int)$t['c'] ?>)
        </a>
      <?php endforeach; ?>
      <span style="flex:1"></span>
      <button class="btn btn-danger" onclick="limpiar()"><span class="material-symbols-outlined">delete_sweep</span>Limpiar todos</button>
    </div>
  </div>

  <div class="panel" style="padding:0;overflow:hidden">
    <table>
      <thead><tr>
        <th>Fecha</th><th>Tipo</th><th>Nivel</th><th>Mensaje</th><th>Archivo</th><th>Usuario</th>
      </tr></thead>
      <tbody>
      <?php if (!$errores): ?>
        <tr><td colspan="6" style="text-align:center;padding:36px;opacity:.6">Sin errores registrados.</td></tr>
      <?php else: foreach ($errores as $e): ?>
        <tr>
          <td style="font-size:.76rem;opacity:.7;white-space:nowrap"><?= htmlspecialchars($e['fecha']) ?></td>
          <td><code><?= htmlspecialchars($e['tipo']) ?></code></td>
          <td><span class="pill pill-<?= htmlspecialchars($e['nivel'] ?? 'error') ?>"><?= htmlspecialchars($e['nivel']) ?></span></td>
          <td class="msg"><?= htmlspecialchars($e['mensaje']) ?></td>
          <td style="font-size:.76rem;opacity:.75"><?= htmlspecialchars(basename($e['archivo'] ?? '')) ?><?= $e['linea']?':'.(int)$e['linea']:'' ?></td>
          <td style="font-size:.78rem"><?= htmlspecialchars($e['usuario'] ?? '—') ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
async function limpiar() {
    if (!confirm('¿Borrar TODOS los errores registrados?')) return;
    const r = await fetch('api_errores.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ accion:'limpiar' })
    }).then(r => r.json());
    if (r.ok) location.reload();
}
</script>
</body>
</html>
