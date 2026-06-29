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
<html lang="es" data-theme="dark" data-theme-accent="violeta">
<head>
<meta charset="UTF-8">
<title>Admin · Errores</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
<link rel="stylesheet" href="../main_md3.css">
<link rel="stylesheet" href="../estilos/admin_md3.css">
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
          <td class="msg-cell"><?= htmlspecialchars($e['mensaje']) ?></td>
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
