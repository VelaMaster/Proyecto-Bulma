<?php
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

$pdo = DatabaseSQLite::getInstance();
$usuarios = $pdo->query("SELECT USUARIO, NOMBRE, ROL, CLAVE_ROL, ACTIVO FROM usuarios_inventarios ORDER BY ROL, USUARIO")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">
<head>
<meta charset="UTF-8">
<title>Admin · Usuarios</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
<link rel="stylesheet" href="../main_md3.css">
<link rel="stylesheet" href="../estilos/admin_md3.css">
</head>
<body>
<div class="wrap">
  <div class="crumb"><a href="index.php">← Panel</a> / Usuarios</div>
  <h1>Usuarios del sistema</h1>
  <p style="opacity:.7;font-size:.88rem">Se editan directamente en SQLite (<code>usuarios_inventarios</code>). El sincronizador NO borra usuarios creados localmente si los identificas con un USUARIO que no exista en Firebird.</p>

  <div class="panel">
    <h3 style="margin-top:0;font-weight:500;font-size:1rem">Nuevo usuario</h3>
    <form id="frmNuevo" class="row-form">
      <div><label>Usuario</label><input name="usuario" required></div>
      <div><label>Nombre</label><input name="nombre"></div>
      <div><label>Contraseña</label><input name="contrasena" type="text" required></div>
      <div>
        <label>Rol</label>
        <select name="rol">
          <option value="0">0 — promotor</option>
          <option value="1">1 — supervisor</option>
          <option value="2">2 — distribucion</option>
        </select>
      </div>
      <div><label>Clave rol</label><input name="clave_rol" type="number"></div>
      <button class="btn btn-primary" type="submit"><span class="material-symbols-outlined">person_add</span>Crear</button>
    </form>
  </div>

  <div class="panel">
    <h3 style="margin-top:0;font-weight:500;font-size:1rem">Existentes (<?= count($usuarios) ?>)</h3>
    <table>
      <thead><tr><th>Usuario</th><th>Nombre</th><th>Rol</th><th>Clave</th><th>Activo</th><th></th></tr></thead>
      <tbody id="tbody">
      <?php foreach ($usuarios as $u): ?>
        <tr data-usuario="<?= htmlspecialchars($u['USUARIO']) ?>">
          <td><code><?= htmlspecialchars($u['USUARIO']) ?></code></td>
          <td><?= htmlspecialchars($u['NOMBRE'] ?? '') ?></td>
          <?php $rolLabel = ['0'=>'promotor','1'=>'supervisor','2'=>'distribucion'][$u['ROL']] ?? ($u['ROL'] ?? '?'); ?>
          <td><span class="pill-rol"><?= htmlspecialchars($rolLabel) ?></span></td>
          <td><?= htmlspecialchars((string)($u['CLAVE_ROL'] ?? '')) ?></td>
          <td><?= $u['ACTIVO'] ? '✔' : '—' ?></td>
          <td style="text-align:right">
            <button class="btn btn-danger btn-eliminar" data-usuario="<?= htmlspecialchars($u['USUARIO']) ?>">
              <span class="material-symbols-outlined">delete</span>Eliminar
            </button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function toast(msg, ok=true) {
    const t = document.createElement('div');
    t.className = 'toast ' + (ok?'toast-ok':'toast-err');
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}

document.getElementById('frmNuevo').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const payload = Object.fromEntries(fd.entries());
    payload.accion = 'crear';
    const r = await fetch('api_usuarios.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify(payload)
    }).then(r => r.json());
    if (r.ok) { toast('Usuario creado'); location.reload(); }
    else toast(r.mensaje || 'Error', false);
});

document.querySelectorAll('.btn-eliminar').forEach(b => {
    b.addEventListener('click', async () => {
        if (!confirm(`¿Eliminar a ${b.dataset.usuario}?`)) return;
        const r = await fetch('api_usuarios.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ accion:'eliminar', usuario: b.dataset.usuario })
        }).then(r => r.json());
        if (r.ok) {
            document.querySelector(`tr[data-usuario="${b.dataset.usuario}"]`)?.remove();
            toast('Eliminado');
        } else toast(r.mensaje || 'Error', false);
    });
});
</script>
</body>
</html>
