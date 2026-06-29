<?php
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';
require_once __DIR__ . '/../src/Servicio/SincronizadorFirebird.php';
require_once __DIR__ . '/../src/Servicio/ClonadorFirebird.php';

$sync         = new SincronizadorFirebird();
$tablas       = $sync->tablas();
$clon         = new ClonadorFirebird();
$ultimoClon   = $clon->ultimoClonado();
$lastSync = [];
foreach ($tablas as $t) {
    $lastSync[$t] = DatabaseSQLite::getConfig("last_sync_$t");
}

// Probar conexión + resolver entorno activo (Liconsa real vs Docker)
$estadoConn = $sync->probarConexion();
$entorno    = $sync->entornoActual();
$esLiconsa  = $entorno['codigo'] === 'liconsa';

DatabaseSQLite::getInstance();
$sqlite_path = DatabaseSQLite::getPath();
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

  <!-- ── Indicador de fuente de datos ─────────────────────────────── -->
  <div class="panel">
    <h3 style="margin-top:0;font-weight:500;font-size:1rem">
      Fuente de datos
      <span class="pill <?= $esLiconsa ? 'pill-ok' : 'pill-warning' ?>" style="margin-left:8px;">
        Entorno activo: <?= htmlspecialchars($entorno['nombre']) ?>
      </span>
    </h3>
    <p class="hint" style="margin-top:0;">
      El sincronizador prueba primero el <strong>servidor real de Liconsa</strong> (172.24.10.251 / DB_SIDIST.FDB).
      Si no responde en 0.3 s cae al <strong>Firebird del contenedor Docker</strong> de pruebas. Resultado cacheado 5 min.
    </p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:14px;">
      <div>
        <div style="display:flex;align-items:center;gap:8px;">
          <span class="material-symbols-outlined" style="color:var(--md-sys-color-tertiary)">cloud</span>
          <strong>Origen Firebird (en uso)</strong>
          <span class="pill <?= $estadoConn['ok'] ? 'pill-ok' : 'pill-err' ?>" style="margin-left:auto">
            <?= $estadoConn['ok'] ? 'En línea' : 'Sin conexión' ?>
          </span>
        </div>
        <p style="margin:8px 0 0;font-size:.82rem;opacity:.8;">
          Entorno: <strong><?= htmlspecialchars($entorno['nombre']) ?></strong><br>
          Host: <code><?= htmlspecialchars($entorno['host']) ?>:<?= htmlspecialchars(DatabaseSQLite::getConfig('fb_port','3050')) ?></code><br>
          BDD: <code><?= htmlspecialchars($entorno['bdd']) ?></code><br>
          <?php if (!$estadoConn['ok']): ?>
            <span class="err"><?= htmlspecialchars($estadoConn['mensaje']) ?></span>
          <?php else: ?>
            Latencia: <?= (int)$estadoConn['duracion_ms'] ?> ms
          <?php endif; ?>
        </p>
      </div>
      <div>
        <div style="display:flex;align-items:center;gap:8px;">
          <span class="material-symbols-outlined" style="color:var(--md-sys-color-primary)">storage</span>
          <strong>Destino local — SQLite</strong>
          <span class="pill pill-ok" style="margin-left:auto">Activo</span>
        </div>
        <p style="margin:8px 0 0;font-size:.82rem;opacity:.8;">
          Archivo: <code><?= htmlspecialchars($sqlite_path) ?></code><br>
          Toda la operación diaria (login, captura, reportes) usa <strong>siempre</strong> el archivo local.
          Firebird sólo se consulta al ejecutar esta sincronización.
        </p>
      </div>
    </div>
  </div>

  <div class="panel">
    <p style="margin:0 0 12px;opacity:.85">
      Lee las tablas catálogo del servidor Firebird configurado y las espeja en SQLite local.
      <a href="config.php" style="color:var(--md-sys-color-primary)">Cambiar configuración</a>.
    </p>
    <button class="btn btn-primary btn-big" id="btnSyncTodo" <?= $estadoConn['ok'] ? '' : 'disabled title="Sin conexión a Firebird"' ?>>
      <span class="material-symbols-outlined">sync</span>Sincronizar TODO
    </button>
  </div>

  <!-- ── Clonar FDB completo Liconsa -> Docker ─────────────────────── -->
  <div class="panel">
    <h3 style="margin-top:0;font-weight:500;font-size:1rem">
      Clonar BDD Firebird completa (Liconsa <span class="material-symbols-outlined" style="vertical-align:middle">arrow_right_alt</span> Docker)
    </h3>
    <p style="margin:0 0 12px;opacity:.85;font-size:.9rem;">
      Usa <code>gbak</code> para hacer backup transaccional del servidor real y restaurarlo
      en el Firebird del contenedor. Útil cuando la BDD local del Docker está desactualizada
      y necesitas que el fallback (sin conexión a Liconsa) tenga datos al día.
      Tiempo aproximado: 30 s a varios minutos según tamaño.
    </p>
    <p style="margin:0 0 12px;opacity:.75;font-size:.82rem;">
      Último clonado: <strong><?= htmlspecialchars($ultimoClon ?? 'nunca') ?></strong>
    </p>
    <button class="btn btn-secondary btn-big" id="btnClonarFb" <?= $esLiconsa ? '' : 'disabled title="Solo aplica cuando hay conexión a Liconsa real"' ?>>
      <span class="material-symbols-outlined">database</span>Clonar Firebird ahora
    </button>
    <span id="estadoClonar" style="margin-left:14px;font-size:.85rem;opacity:.85;"></span>
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

let ultimoOrigenCorto = 'Firebird';

function pintarFila(tabla, res) {
    const row = document.querySelector(`tr[data-tabla="${tabla}"] .last-sync`);
    if (row) row.innerHTML = res.ok
        ? `${new Date().toLocaleString('es-MX')} <span class="pill pill-ok">${res.filas} filas ← ${ultimoOrigenCorto}</span>`
        : `<span class="pill pill-err">ERROR</span>`;
}

function logFuente(f) {
    if (!f) return;
    ultimoOrigenCorto = f.codigo === 'liconsa' ? 'Liconsa' : 'Docker (pruebas)';
    log(`  ↳ Origen: ${f.origen}`);
    log(`  ↳ Host:   ${f.host}:${f.puerto}`);
    log(`  ↳ BDD:    ${f.bdd_remota}`);
    log(`  ↳ Destino: ${f.destino}`);
}

document.getElementById('btnSyncTodo').addEventListener('click', async () => {
    clearLog();
    log('Sincronizando TODAS las tablas desde Firebird (Liconsa)…');
    const btns = document.querySelectorAll('button');
    btns.forEach(b => b.disabled = true);
    try {
        const data = await ejecutar('__todo__');
        // Nueva forma de respuesta: { fuente, resultados } — fallback al formato antiguo.
        const res = data.resultados ?? data;
        logFuente(data.fuente);
        for (const [tabla, r] of Object.entries(res)) {
            log(`  ${tabla}: ${r.ok ? '[OK]' : '[ERR]'} ${r.filas} filas (${r.duracion_ms} ms) ${r.ok ? '— de Firebird → SQLite' : '— '+r.mensaje}`);
            pintarFila(tabla, r);
        }
        log('Fin. Los datos quedaron espejados en el SQLite local.');
    } catch (e) { log('ERROR: ' + e.message); }
    btns.forEach(b => b.disabled = false);
});

// ── Clonar Firebird (Liconsa -> Docker) ──────────────────────────
const btnClon = document.getElementById('btnClonarFb');
if (btnClon) {
    btnClon.addEventListener('click', async () => {
        const estado = document.getElementById('estadoClonar');
        if (!confirm('Se hará BACKUP del Firebird de Liconsa y se SOBREESCRIBIRÁ el del Docker. ¿Continuar?')) return;
        clearLog();
        log('Clonando Firebird real -> Docker (gbak)…');
        log('  Esto puede tardar varios minutos. NO cierres la pestaña.');
        btnClon.disabled = true;
        estado.textContent = 'Clonando, espera...';
        try {
            const r = await fetch('api_clonar_fb.php', { method:'POST' }).then(r => r.json());
            log(`  Etapa final: ${r.etapa}`);
            log(`  Duracion:    ${r.duracion_ms} ms`);
            if (r.fbk) log(`  Backup:      ${r.fbk} (${r.bytes_fbk.toLocaleString()} bytes)`);
            (r.salida || []).forEach(line => log('    ' + line));
            log(r.ok ? 'Clonado completado correctamente.' : 'Clonado FALLO: ' + r.mensaje);
            estado.textContent = r.ok
                ? `Clonado OK (${(r.duracion_ms/1000).toFixed(1)} s)`
                : 'Clonado falló — revisa /admin/errores.php';
        } catch (e) {
            log('ERROR de red: ' + e.message);
            estado.textContent = 'Error de red. Revisa /admin/errores.php';
        }
        btnClon.disabled = false;
    });
}

document.querySelectorAll('.btn-sync-tabla').forEach(b => {
    b.addEventListener('click', async () => {
        const tabla = b.dataset.tabla;
        clearLog();
        log(`Sincronizando ${tabla} desde Firebird…`);
        b.disabled = true;
        try {
            const r = await ejecutar(tabla);
            logFuente(r.fuente);
            log(`  ${r.ok ? '[OK]' : '[ERR]'} ${r.filas} filas (${r.duracion_ms} ms) ${r.ok ? '— de Firebird → SQLite' : '— '+r.mensaje}`);
            pintarFila(tabla, r);
        } catch (e) { log('ERROR: ' + e.message); }
        b.disabled = false;
    });
});
</script>
</body>
</html>
