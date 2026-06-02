<?php
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';
require_once __DIR__ . '/../src/Servicio/SincronizadorFirebird.php';

$mensaje = null;
$probar  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['fb_host','fb_port','fb_user','fb_pass','fb_db_path','fb_charset'] as $k) {
        if (isset($_POST[$k])) DatabaseSQLite::setConfig($k, trim($_POST[$k]));
    }
    if (isset($_POST['probar'])) {
        $probar = (new SincronizadorFirebird())->probarConexion();
    } else {
        $mensaje = 'Configuración guardada.';
    }
}

$cfg = [];
foreach (['fb_host','fb_port','fb_user','fb_pass','fb_db_path','fb_charset'] as $k) {
    $cfg[$k] = DatabaseSQLite::getConfig($k, '');
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
<meta charset="UTF-8">
<title>Admin · Config Firebird</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
<link rel="stylesheet" href="../main_md3.css">
<style>
  body { font-family: Roboto, sans-serif; background:#141218; color:#E6E1E5; margin:0; padding:24px; }
  .wrap { max-width:720px; margin:0 auto; }
  h1 { font-size:1.4rem; font-weight:500; margin:0 0 4px; }
  .crumb { font-size:.85rem; opacity:.7; margin-bottom:24px; }
  .crumb a { color:#D0BCFF; text-decoration:none; }
  form { background:#1e1b22; padding:24px; border-radius:16px; border:1px solid #49454F; }
  label { display:block; font-size:.78rem; opacity:.7; margin-bottom:6px; margin-top:14px; text-transform:uppercase; letter-spacing:.05em; }
  input { width:100%; box-sizing:border-box; padding:10px 12px; border-radius:8px; border:1px solid #49454F; background:#141218; color:#E6E1E5; font-size:.95rem; font-family:inherit; }
  input:focus { outline:none; border-color:#D0BCFF; }
  .row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
  .btn { display:inline-flex; align-items:center; gap:8px; padding:11px 22px; border-radius:24px; border:none; cursor:pointer; font-weight:500; font-size:.9rem; margin-top:18px; margin-right:8px; }
  .btn-primary { background:#D0BCFF; color:#21005D; }
  .btn-secondary { background:transparent; color:#D0BCFF; border:1px solid #D0BCFF; }
  .alert { padding:12px 16px; border-radius:12px; margin-bottom:16px; font-size:.9rem; }
  .alert-ok  { background:#1B4332; color:#86EFAC; }
  .alert-err { background:#5C1A1A; color:#FCA5A5; }
</style>
</head>
<body>
<div class="wrap">
  <div class="crumb"><a href="index.php">← Panel</a> / Configurar Firebird</div>
  <h1>Configuración de Firebird</h1>
  <p style="opacity:.7;font-size:.88rem;margin-top:4px">El sincronizador usa estos datos para leer la BDD origen. La contraseña se guarda en texto plano en SQLite local (acceso restringido por IP).</p>

  <?php if ($mensaje): ?><div class="alert alert-ok"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
  <?php if ($probar): ?>
    <div class="alert <?= $probar['ok']?'alert-ok':'alert-err' ?>">
      <?= $probar['ok'] ? '✔ ' : '✘ ' ?><?= htmlspecialchars($probar['mensaje']) ?> <span style="opacity:.7">(<?= $probar['duracion_ms'] ?> ms)</span>
    </div>
  <?php endif; ?>

  <form method="POST">
    <div class="row">
      <div><label>Host / IP</label><input name="fb_host" value="<?= htmlspecialchars($cfg['fb_host']) ?>"></div>
      <div><label>Puerto</label><input name="fb_port" value="<?= htmlspecialchars($cfg['fb_port']) ?>"></div>
    </div>
    <div class="row">
      <div><label>Usuario</label><input name="fb_user" value="<?= htmlspecialchars($cfg['fb_user']) ?>"></div>
      <div><label>Contraseña</label><input name="fb_pass" type="text" value="<?= htmlspecialchars($cfg['fb_pass']) ?>"></div>
    </div>
    <label>Ruta de la BDD (en el servidor Firebird)</label>
    <input name="fb_db_path" value="<?= htmlspecialchars($cfg['fb_db_path']) ?>">
    <label>Charset</label>
    <input name="fb_charset" value="<?= htmlspecialchars($cfg['fb_charset']) ?>" style="max-width:160px">

    <div style="margin-top:8px">
      <button class="btn btn-primary" type="submit"><span class="material-symbols-outlined">save</span>Guardar</button>
      <button class="btn btn-secondary" type="submit" name="probar" value="1"><span class="material-symbols-outlined">network_check</span>Guardar y probar conexión</button>
    </div>
  </form>
</div>
</body>
</html>
