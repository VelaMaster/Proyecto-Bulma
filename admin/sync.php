<?php
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';
require_once __DIR__ . '/../src/Servicio/SincronizadorFirebird.php';

$sync     = new SincronizadorFirebird();
$tablas   = $sync->tablas();
$lastSync = [];
foreach ($tablas as $t) {
    $lastSync[$t] = DatabaseSQLite::getConfig("last_sync_$t");
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">
<head>
<meta charset="UTF-8">
<title>Admin · Sincronizar</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
<link rel="stylesheet" href="../main_md3.css">
<link rel="stylesheet" href="../estilos/admin_md3.css">
</head>
<body>
<div class="wrap narrow">
  <div class="crumb"><a href="index.php">← Panel</a> / Sincronizar BDD</div>
  <h1>Sincronizar Firebird → SQLite</h1>

  <div class="panel">
    <p style="margin:0 0 12px;opacity:.85">
      Lee las tablas catálogo del servidor Firebird configurado y las espeja en SQLite local.
      <a href="config.php" style="color:var(--md-sys-color-primary)">Cambiar configuración</a>.
    </p>
    <button class="btn btn-primary btn-big" id="btnSyncTodo">
      <span class="material-symbols-outlined">sync</span>Sincronizar TODO
    </button>
  </div>

  <div class="panel">
    <h3 style="margin-top:0;font-weight:500;font-size:1rem">Por tabla</h3>
    <table>
      <thead><tr><th>Tabla</th><th>Última sync</th><th style="text-align:right">Acción</th></tr></thead>
      <tbody>
      <?php foreach ($tablas as $t): ?>
        <tr data-tabla="<?= $t ?>">
          <td><code><?= htmlspecialchars($t) ?></code></td>
          <td class="last-sync"><?= htmlspecialchars($lastSync[$t] ?? '— nunca —') ?></td>
          <td style="text-align:right">
            <button class="btn btn-secondary btn-sync-tabla" data-tabla="<?= $t ?>">
              <span class="material-symbols-outlined">sync</span>Sincronizar
            </button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="panel">
    <h3 style="margin-top:0;font-weight:500;font-size:1rem">Log</h3>
    <div id="log">Listo. Presiona un botón para sincronizar.</div>
  </div>
</div>

<script>
const $log = document.getElementById('log');
function log(msg) { $log.textContent += '\n' + msg; $log.scrollTop = $log.scrollHeight; }
function clearLog() { $log.textContent = '── ' + new Date().toLocaleTimeString() + ' ──'; }

async function ejecutar(tabla) {
    const r = await fetch('api_sync.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ tabla })
    });
    return r.json();
}

function pintarFila(tabla, res) {
    const row = document.querySelector(`tr[data-tabla="${tabla}"] .last-sync`);
    if (row) row.innerHTML = res.ok
        ? `${new Date().toLocaleString('es-MX')} <span class="pill pill-ok">${res.filas} filas</span>`
        : `<span class="pill pill-err">ERROR</span>`;
}

document.getElementById('btnSyncTodo').addEventListener('click', async () => {
    clearLog();
    log('Sincronizando TODAS las tablas…');
    const btns = document.querySelectorAll('button');
    btns.forEach(b => b.disabled = true);
    try {
        const res = await ejecutar('__todo__');
        for (const [tabla, r] of Object.entries(res)) {
            log(`  ${tabla}: ${r.ok ? '✔' : '✘'} ${r.filas} filas (${r.duracion_ms} ms) ${r.ok ? '' : '— '+r.mensaje}`);
            pintarFila(tabla, r);
        }
        log('Fin.');
    } catch (e) { log('ERROR: ' + e.message); }
    btns.forEach(b => b.disabled = false);
});

document.querySelectorAll('.btn-sync-tabla').forEach(b => {
    b.addEventListener('click', async () => {
        const tabla = b.dataset.tabla;
        clearLog();
        log(`Sincronizando ${tabla}…`);
        b.disabled = true;
        try {
            const r = await ejecutar(tabla);
            log(`  ${r.ok ? '✔' : '✘'} ${r.filas} filas (${r.duracion_ms} ms) ${r.ok ? '' : '— '+r.mensaje}`);
            pintarFila(tabla, r);
        } catch (e) { log('ERROR: ' + e.message); }
        b.disabled = false;
    });
});
</script>
</body>
</html>
