<?php
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

$pdo = DatabaseSQLite::getInstance();

$contar = fn($sql) => (int)$pdo->query($sql)->fetchColumn();
$counts = [
    'lecheria'             => $contar("SELECT COUNT(*) FROM lecheria"),
    'usuarios_inventarios' => $contar("SELECT COUNT(*) FROM usuarios_inventarios"),
    'municipio'            => $contar("SELECT COUNT(*) FROM municipio"),
    'localidad'            => $contar("SELECT COUNT(*) FROM localidad"),
    'errores_hoy'          => $contar("SELECT COUNT(*) FROM errores_log WHERE date(fecha)=date('now','localtime')"),
];

$ultimoSync = $pdo->query("SELECT tabla, filas, ok, fecha FROM sync_log ORDER BY id DESC LIMIT 5")->fetchAll();

// Regenerar token si se pidió por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'regen_token') {
    DatabaseSQLite::setConfig('admin_token', bin2hex(random_bytes(24)));
    setcookie('admin_token', '', ['expires'=>1, 'path'=>'/admin']);
    header('Location: index.php?key=' . admin_token_actual());
    exit;
}
$_token = admin_token_actual();
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">
<head>
<meta charset="UTF-8">
<title>Admin · Inventarios Liconsa</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
<link rel="stylesheet" href="../main_md3.css">
<style>
  body { font-family: Roboto, sans-serif; background: var(--md-sys-color-surface, #141218); color: var(--md-sys-color-on-surface, #E6E1E5); margin: 0; padding: 24px; }
  .admin-wrap { max-width: 1100px; margin: 0 auto; }
  .admin-bar { display:flex; align-items:center; gap:12px; margin-bottom:24px; padding-bottom:16px; border-bottom:1px solid var(--md-sys-color-outline-variant,#49454F); }
  .admin-bar h1 { margin:0; font-size:1.5rem; font-weight:500; }
  .badge-admin { background: var(--md-sys-color-error, #B3261E); color:#fff; padding:4px 10px; border-radius:8px; font-size:.72rem; letter-spacing:.05em; font-weight:600; }
  .admin-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap:16px; margin-bottom:32px; }
  .stat-card { background: var(--md-sys-color-surface-container,#1e1b22); padding:18px; border-radius:16px; border:1px solid var(--md-sys-color-outline-variant,#49454F); }
  .stat-card .label { font-size:.78rem; opacity:.7; text-transform:uppercase; letter-spacing:.06em; }
  .stat-card .value { font-size:1.8rem; font-weight:500; margin-top:6px; }
  .admin-nav { display:flex; flex-wrap:wrap; gap:12px; margin-bottom:32px; }
  .admin-nav a { display:inline-flex; align-items:center; gap:8px; padding:12px 18px; background: var(--md-sys-color-primary-container,#4F378A); color: var(--md-sys-color-on-primary-container,#EADDFF); border-radius:24px; text-decoration:none; font-weight:500; font-size:.9rem; }
  .admin-nav a:hover { filter:brightness(1.15); }
  table.adm { width:100%; border-collapse:collapse; background: var(--md-sys-color-surface-container,#1e1b22); border-radius:12px; overflow:hidden; }
  table.adm th, table.adm td { padding:10px 14px; text-align:left; border-bottom:1px solid var(--md-sys-color-outline-variant,#49454F); font-size:.88rem; }
  table.adm th { font-weight:500; opacity:.7; font-size:.75rem; text-transform:uppercase; letter-spacing:.05em; }
  .ok  { color:#86EFAC; } .err { color:#FCA5A5; }
</style>
</head>
<body>
<div class="admin-wrap">
  <div class="admin-bar">
    <span class="material-symbols-outlined" style="font-size:32px;color:var(--md-sys-color-primary,#D0BCFF)">shield_person</span>
    <h1>Panel Administrador</h1>
    <span class="badge-admin">SIN LOGIN · IP ALLOWLIST</span>
  </div>

  <nav class="admin-nav">
    <a href="sync.php"><span class="material-symbols-outlined">sync</span>Sincronizar BDD</a>
    <a href="config.php"><span class="material-symbols-outlined">settings</span>Configurar Firebird</a>
    <a href="usuarios.php"><span class="material-symbols-outlined">group</span>Usuarios</a>
    <a href="conciliar_ope.php"><span class="material-symbols-outlined">fact_check</span>Conciliar OPE</a>
    <a href="errores.php"><span class="material-symbols-outlined">bug_report</span>Visor de errores</a>
  </nav>

  <div class="stat-card" style="margin-bottom:24px;background:#2b1e3a;border-color:#7C4DFF;">
    <div class="label" style="color:#D0BCFF">Token de acceso remoto</div>
    <div style="display:flex;align-items:center;gap:12px;margin-top:8px;flex-wrap:wrap">
      <code id="tokenBox" style="font-size:.78rem;background:#0a0a0e;padding:8px 12px;border-radius:8px;word-break:break-all;flex:1;min-width:220px"><?= htmlspecialchars($_token) ?></code>
      <button onclick="navigator.clipboard.writeText('<?= $_token ?>').then(()=>this.textContent='✔ Copiado')" style="background:#D0BCFF;color:#21005D;border:none;padding:8px 14px;border-radius:18px;cursor:pointer;font-weight:500;font-size:.82rem">Copiar</button>
      <button onclick="navigator.clipboard.writeText(location.origin+'/admin/?key=<?= $_token ?>').then(()=>this.textContent='✔ URL copiada')" style="background:transparent;color:#D0BCFF;border:1px solid #D0BCFF;padding:8px 14px;border-radius:18px;cursor:pointer;font-weight:500;font-size:.82rem">Copiar URL completa</button>
      <form method="POST" style="display:inline" onsubmit="return confirm('¿Regenerar token? Las sesiones remotas activas dejarán de funcionar.')">
        <input type="hidden" name="accion" value="regen_token">
        <button type="submit" style="background:#5C1A1A;color:#FCA5A5;border:none;padding:8px 14px;border-radius:18px;cursor:pointer;font-weight:500;font-size:.82rem">Regenerar</button>
      </form>
    </div>
    <p style="font-size:.78rem;opacity:.7;margin:10px 0 0">
      Desde la LAN entras sin token. Desde fuera usa: <code>https://inventariosliconsaoaxaca.duckdns.org/admin/?key=…</code>
    </p>
  </div>

  <h2 style="font-size:1.1rem;font-weight:500;margin:0 0 12px;opacity:.85">Estado de SQLite</h2>
  <div class="admin-grid">
    <div class="stat-card"><div class="label">Lecherías</div><div class="value"><?= $counts['lecheria'] ?></div></div>
    <div class="stat-card"><div class="label">Usuarios</div><div class="value"><?= $counts['usuarios_inventarios'] ?></div></div>
    <div class="stat-card"><div class="label">Municipios</div><div class="value"><?= $counts['municipio'] ?></div></div>
    <div class="stat-card"><div class="label">Localidades</div><div class="value"><?= $counts['localidad'] ?></div></div>
    <div class="stat-card"><div class="label">Errores hoy</div><div class="value" style="color:<?= $counts['errores_hoy']>0?'#FCA5A5':'#86EFAC' ?>"><?= $counts['errores_hoy'] ?></div></div>
  </div>

  <h2 style="font-size:1.1rem;font-weight:500;margin:0 0 12px;opacity:.85">Últimas sincronizaciones</h2>
  <table class="adm">
    <thead><tr><th>Tabla</th><th>Filas</th><th>Estado</th><th>Fecha</th></tr></thead>
    <tbody>
    <?php if (!$ultimoSync): ?>
      <tr><td colspan="4" style="text-align:center;opacity:.6;padding:24px">Aún no hay sincronizaciones. <a href="sync.php" style="color:var(--md-sys-color-primary,#D0BCFF)">Ejecutar la primera</a></td></tr>
    <?php else: foreach ($ultimoSync as $s): ?>
      <tr>
        <td><?= htmlspecialchars($s['tabla']) ?></td>
        <td><?= (int)$s['filas'] ?></td>
        <td class="<?= $s['ok']?'ok':'err' ?>"><?= $s['ok']?'OK':'ERROR' ?></td>
        <td><?= htmlspecialchars($s['fecha']) ?></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
</body>
</html>
