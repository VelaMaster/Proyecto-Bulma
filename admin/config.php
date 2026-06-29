<?php
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';
require_once __DIR__ . '/../src/Servicio/SincronizadorFirebird.php';

$mensaje = null;
$probar  = null;

$CAMPOS = [
    'fb_host_remoto','fb_db_path_remoto','fb_user_remoto','fb_pass_remoto',
    'fb_host_local','fb_db_path_local','fb_user_local','fb_pass_local',
    'fb_port','fb_charset',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($CAMPOS as $k) {
        if (isset($_POST[$k])) DatabaseSQLite::setConfig($k, trim($_POST[$k]));
    }
    // Invalida la cache de auto-detección para que el próximo test use lo nuevo
    @unlink(sys_get_temp_dir() . '/liconsa_dbhost.cache');

    if (isset($_POST['probar'])) {
        $probar = (new SincronizadorFirebird())->probarConexion();
    } else {
        $mensaje = 'Configuración guardada.';
    }
}

$cfg = [];
foreach ($CAMPOS as $k) {
    $cfg[$k] = DatabaseSQLite::getConfig($k, '');
}
$entorno = (new SincronizadorFirebird())->entornoActual();
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
  <p style="opacity:.7;font-size:.88rem;margin-top:4px">
    El sincronizador intenta primero el <strong>servidor real de Liconsa</strong>. Si no responde, cae al
    <strong>Firebird del Docker</strong> de pruebas. Contraseñas en texto plano protegidas por IP allowlist.
  </p>

  <div class="alert" style="background:color-mix(in srgb,var(--md-sys-color-tertiary) 18%,transparent);color:var(--md-sys-color-on-surface);">
    <strong>Entorno activo ahora mismo:</strong> <?= htmlspecialchars($entorno['nombre']) ?>
    &nbsp;·&nbsp; <code><?= htmlspecialchars($entorno['host']) ?></code>
    &nbsp;·&nbsp; <code><?= htmlspecialchars($entorno['bdd']) ?></code>
  </div>

  <?php if ($mensaje): ?><div class="alert alert-ok"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
  <?php if ($probar): ?>
    <div class="alert <?= $probar['ok']?'alert-ok':'alert-err' ?>">
      <?= $probar['ok'] ? '✔ ' : '✘ ' ?><?= htmlspecialchars($probar['mensaje']) ?> <span style="opacity:.7">(<?= $probar['duracion_ms'] ?> ms)</span>
    </div>
  <?php endif; ?>

  <form method="POST" class="config-form">
    <h3 style="margin:0 0 8px;font-weight:500;font-size:1rem;">
      <span class="material-symbols-outlined" style="vertical-align:middle;color:var(--md-sys-color-tertiary)">cloud</span>
      Servidor REAL de Liconsa (prioridad 1)
    </h3>
    <div class="row">
      <div><label>Host / IP</label><input name="fb_host_remoto" value="<?= htmlspecialchars($cfg['fb_host_remoto']) ?>"></div>
      <div><label>Puerto</label><input name="fb_port" value="<?= htmlspecialchars($cfg['fb_port']) ?>"></div>
    </div>
    <div class="row">
      <div><label>Usuario</label><input name="fb_user_remoto" value="<?= htmlspecialchars($cfg['fb_user_remoto']) ?>"></div>
      <div><label>Contraseña</label><input name="fb_pass_remoto" type="text" value="<?= htmlspecialchars($cfg['fb_pass_remoto']) ?>"></div>
    </div>
    <label>Ruta de la BDD en el servidor</label>
    <input name="fb_db_path_remoto" value="<?= htmlspecialchars($cfg['fb_db_path_remoto']) ?>">

    <h3 style="margin:24px 0 8px;font-weight:500;font-size:1rem;">
      <span class="material-symbols-outlined" style="vertical-align:middle;color:var(--md-sys-color-primary)">developer_board</span>
      Firebird Docker local (fallback de pruebas)
    </h3>
    <div class="row">
      <div><label>Host / IP</label><input name="fb_host_local" value="<?= htmlspecialchars($cfg['fb_host_local']) ?>"></div>
      <div><label>Charset</label><input name="fb_charset" value="<?= htmlspecialchars($cfg['fb_charset']) ?>"></div>
    </div>
    <div class="row">
      <div><label>Usuario</label><input name="fb_user_local" value="<?= htmlspecialchars($cfg['fb_user_local']) ?>"></div>
      <div><label>Contraseña</label><input name="fb_pass_local" type="text" value="<?= htmlspecialchars($cfg['fb_pass_local']) ?>"></div>
    </div>
    <label>Ruta de la BDD en el contenedor</label>
    <input name="fb_db_path_local" value="<?= htmlspecialchars($cfg['fb_db_path_local']) ?>">

    <div style="margin-top:18px">
      <button class="btn btn-primary" type="submit"><span class="material-symbols-outlined">save</span>Guardar</button>
      <button class="btn btn-secondary" type="submit" name="probar" value="1"><span class="material-symbols-outlined">network_check</span>Guardar y probar conexión</button>
    </div>
  </form>
</div>
</body>
</html>
