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
<html lang="es" data-theme="dark" data-theme-accent="violeta">
<head>
<meta charset="UTF-8">
<title>Admin · Config Firebird</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
<link rel="stylesheet" href="../main_md3.css">
<link rel="stylesheet" href="../estilos/admin_md3.css">
<style>
  /* Override local: el form completo actúa como card */
  form.config-form { background: var(--md-sys-color-surface-container); padding: 24px; border-radius: 16px; border: 1px solid var(--md-sys-color-outline-variant); }
  form.config-form .btn { margin-top: 18px; margin-right: 8px; padding: 11px 22px; font-size: .9rem; }
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

  <form method="POST" class="config-form">
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
