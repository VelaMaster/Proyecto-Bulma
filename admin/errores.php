<?php
// admin/errores.php
// Visor de errores robusto. Filtros por tipo/nivel/fecha/usuario/texto,
// paginación, panel de detalle (incluye contexto JSON y traza), exportación
// CSV, limpieza por criterio.
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

$pdo = DatabaseSQLite::getInstance();

// ── Parámetros ─────────────────────────────────────────────────────
$tipo    = trim($_GET['tipo']   ?? '');
$nivel   = trim($_GET['nivel']  ?? '');
$desde   = trim($_GET['desde']  ?? '');           // YYYY-MM-DD
$hasta   = trim($_GET['hasta']  ?? '');
$q       = trim($_GET['q']      ?? '');           // búsqueda libre en mensaje/archivo
$usuario = trim($_GET['usuario']?? '');
$page    = max(1, (int)($_GET['page']  ?? 1));
$per     = min(200, max(10, (int)($_GET['per'] ?? 50)));

$where = []; $args = [];
if ($tipo)    { $where[] = 'tipo = ?';    $args[] = $tipo; }
if ($nivel)   { $where[] = 'nivel = ?';   $args[] = $nivel; }
if ($desde)   { $where[] = 'fecha >= ?';  $args[] = $desde . ' 00:00:00'; }
if ($hasta)   { $where[] = 'fecha <= ?';  $args[] = $hasta . ' 23:59:59'; }
if ($usuario) { $where[] = 'usuario = ?'; $args[] = $usuario; }
if ($q) {
    $where[] = '(mensaje LIKE ? OR archivo LIKE ? OR contexto LIKE ?)';
    $like = '%' . $q . '%'; $args[] = $like; $args[] = $like; $args[] = $like;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ── Conteo total + paginación ──────────────────────────────────────
$stmtN = $pdo->prepare("SELECT COUNT(*) FROM errores_log $whereSql");
$stmtN->execute($args);
$total = (int)$stmtN->fetchColumn();
$totalPages = max(1, (int)ceil($total / $per));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $per;

// ── Listado ────────────────────────────────────────────────────────
$sql = "SELECT id, fecha, tipo, nivel, mensaje, archivo, linea, usuario,
               CASE WHEN contexto IS NOT NULL AND contexto <> '' THEN 1 ELSE 0 END AS tiene_contexto
          FROM errores_log $whereSql
         ORDER BY id DESC
         LIMIT $per OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Agregados (cards) ──────────────────────────────────────────────
$agg = $pdo->query("
    SELECT
      SUM(CASE WHEN nivel='error'   THEN 1 ELSE 0 END) AS n_err,
      SUM(CASE WHEN nivel='warning' THEN 1 ELSE 0 END) AS n_warn,
      SUM(CASE WHEN nivel='notice'  THEN 1 ELSE 0 END) AS n_not,
      SUM(CASE WHEN nivel='info'    THEN 1 ELSE 0 END) AS n_info,
      SUM(CASE WHEN date(fecha) = date('now','localtime') THEN 1 ELSE 0 END) AS n_hoy,
      COUNT(*) AS n_tot
    FROM errores_log
")->fetch(PDO::FETCH_ASSOC) ?: [];

$porTipo = $pdo->query("SELECT tipo, COUNT(*) c FROM errores_log GROUP BY tipo ORDER BY c DESC")->fetchAll(PDO::FETCH_ASSOC);

// Querystring base para conservar filtros en links
function qs(array $override = []): string {
    $q = array_merge($_GET, $override);
    return '?' . http_build_query(array_filter($q, fn($v) => $v !== '' && $v !== null));
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">
<head>
<meta charset="UTF-8">
<title>Admin · Visor de errores</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
<link rel="stylesheet" href="../main_md3.css">
<link rel="stylesheet" href="../estilos/admin_md3.css">
<style>
  /* Override local para esta pantalla */
  .err-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(140px,1fr)); gap:12px; margin-bottom:18px; }
  .filter-form { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:10px; align-items:end; }
  .filter-form input, .filter-form select { width:100%; }
  .row-actions { display:flex; gap:6px; flex-wrap:wrap; }
  .pager { display:flex; gap:8px; align-items:center; justify-content:flex-end; margin-top:14px; font-size:.88rem; }
  .pager a, .pager span { padding:6px 10px; border-radius:8px; background:var(--md-sys-color-surface-container-high); color:var(--md-sys-color-on-surface); text-decoration:none; }
  .pager a.active, .pager span.active { background:var(--md-sys-color-primary); color:var(--md-sys-color-on-primary); font-weight:600; }
  .pager .disabled { opacity:.4; pointer-events:none; }
  .detalle-drawer { position:fixed; top:0; right:-720px; width:min(720px,92vw); height:100vh; background:var(--md-sys-color-surface-container-high); border-left:1px solid var(--md-sys-color-outline-variant); transition: right .25s ease; z-index:200; padding:24px; overflow-y:auto; box-shadow:-8px 0 24px rgba(0,0,0,.3); }
  .detalle-drawer.open { right:0; }
  .detalle-drawer pre { background:var(--md-sys-color-surface-container-lowest, #0a0a0e); padding:12px; border-radius:8px; overflow-x:auto; font-size:.78rem; max-height:300px; }
  .scrim { position:fixed; inset:0; background:rgba(0,0,0,.45); opacity:0; pointer-events:none; transition: opacity .25s ease; z-index:199; }
  .scrim.open { opacity:1; pointer-events:auto; }
  .field-label { font-size:.7rem; text-transform:uppercase; letter-spacing:.5px; opacity:.65; margin:14px 0 4px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="crumb"><a href="index.php">&larr; Panel</a> / Visor de errores</div>
  <h1>Visor de errores</h1>
  <p class="sub">Captura automática de errores PHP, excepciones, fallas de PDO y eventos de sincronización.</p>

  <!-- ── Cards de agregados ──────────────────────────────────────── -->
  <div class="err-grid">
    <div class="stat-card"><div class="label">Total</div><div class="value"><?= (int)($agg['n_tot'] ?? 0) ?></div></div>
    <div class="stat-card"><div class="label">Hoy</div><div class="value"><?= (int)($agg['n_hoy'] ?? 0) ?></div></div>
    <div class="stat-card"><div class="label">Errores</div><div class="value err"><?= (int)($agg['n_err'] ?? 0) ?></div></div>
    <div class="stat-card"><div class="label">Warnings</div><div class="value" style="color:#E0B400"><?= (int)($agg['n_warn'] ?? 0) ?></div></div>
    <div class="stat-card"><div class="label">Notices</div><div class="value"><?= (int)($agg['n_not'] ?? 0) ?></div></div>
    <div class="stat-card"><div class="label">Info</div><div class="value ok"><?= (int)($agg['n_info'] ?? 0) ?></div></div>
  </div>

  <!-- ── Filtros ─────────────────────────────────────────────────── -->
  <div class="panel">
    <form method="GET" class="filter-form">
      <div>
        <label>Tipo</label>
        <select name="tipo">
          <option value="">Todos</option>
          <?php foreach (['php','pdo','sync','app'] as $t): ?>
            <option value="<?= $t ?>" <?= $tipo===$t?'selected':'' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Nivel</label>
        <select name="nivel">
          <option value="">Todos</option>
          <?php foreach (['error','warning','notice','info'] as $n): ?>
            <option value="<?= $n ?>" <?= $nivel===$n?'selected':'' ?>><?= $n ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Desde</label>
        <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>">
      </div>
      <div>
        <label>Hasta</label>
        <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>">
      </div>
      <div>
        <label>Usuario</label>
        <input type="text" name="usuario" value="<?= htmlspecialchars($usuario) ?>" placeholder="usuario">
      </div>
      <div style="grid-column:span 2;">
        <label>Buscar texto</label>
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="mensaje, archivo, contexto">
      </div>
      <div>
        <label>Por página</label>
        <select name="per">
          <?php foreach ([25,50,100,200] as $n): ?>
            <option value="<?= $n ?>" <?= $per===$n?'selected':'' ?>><?= $n ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display:flex;gap:8px;">
        <button class="btn btn-primary" type="submit"><span class="material-symbols-outlined">filter_alt</span>Filtrar</button>
        <a class="btn btn-secondary" href="errores.php"><span class="material-symbols-outlined">restart_alt</span>Limpiar</a>
      </div>
    </form>
  </div>

  <!-- ── Acciones globales ───────────────────────────────────────── -->
  <div class="panel" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
    <span style="font-size:.85rem;opacity:.8;">Acciones masivas:</span>
    <a class="btn btn-secondary" href="api_errores.php?<?= http_build_query(array_filter(['accion'=>'export_csv','tipo'=>$tipo,'nivel'=>$nivel,'desde'=>$desde,'q'=>$q])) ?>">
      <span class="material-symbols-outlined">download</span>Exportar CSV
    </a>
    <button class="btn btn-secondary" onclick="purgar(30)">
      <span class="material-symbols-outlined">delete_sweep</span>Borrar &gt; 30 días
    </button>
    <button class="btn btn-secondary" onclick="purgar(7)">
      Borrar &gt; 7 días
    </button>
    <?php if ($nivel): ?>
      <button class="btn btn-danger" onclick="limpiarPor('nivel','<?= htmlspecialchars($nivel) ?>')">
        Limpiar todos los <?= htmlspecialchars($nivel) ?>
      </button>
    <?php endif; ?>
    <?php if ($tipo): ?>
      <button class="btn btn-danger" onclick="limpiarPor('tipo','<?= htmlspecialchars($tipo) ?>')">
        Limpiar todos los <?= htmlspecialchars($tipo) ?>
      </button>
    <?php endif; ?>
    <span style="flex:1;"></span>
    <button class="btn btn-danger" onclick="limpiarTodo()">
      <span class="material-symbols-outlined">delete_forever</span>Borrar TODO
    </button>
  </div>

  <!-- ── Distribución por tipo (pills informativas) ──────────────── -->
  <?php if ($porTipo): ?>
  <div class="panel" style="padding:12px 16px;">
    <span style="font-size:.8rem;opacity:.75;margin-right:8px;">Distribución por tipo:</span>
    <?php foreach ($porTipo as $t): ?>
      <a class="pill pill-rol" style="text-decoration:none;margin-right:6px;" href="<?= qs(['tipo'=>$t['tipo'],'page'=>null]) ?>">
        <?= htmlspecialchars($t['tipo']) ?>: <?= (int)$t['c'] ?>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- ── Tabla de registros ──────────────────────────────────────── -->
  <div class="panel" style="padding:0;overflow:hidden">
    <table>
      <thead>
        <tr>
          <th style="width:140px;">Fecha</th>
          <th style="width:70px;">Tipo</th>
          <th style="width:90px;">Nivel</th>
          <th>Mensaje</th>
          <th style="width:240px;">Archivo</th>
          <th style="width:100px;">Usuario</th>
          <th style="width:80px;"></th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="7" style="text-align:center;padding:36px;opacity:.6">Sin registros que coincidan con los filtros.</td></tr>
      <?php else: foreach ($rows as $e): ?>
        <tr>
          <td style="font-size:.76rem;opacity:.85;white-space:nowrap"><?= htmlspecialchars($e['fecha']) ?></td>
          <td><code><?= htmlspecialchars($e['tipo']) ?></code></td>
          <td><span class="pill pill-<?= htmlspecialchars($e['nivel'] ?? 'error') ?>"><?= htmlspecialchars($e['nivel']) ?></span></td>
          <td class="msg-cell"><?= htmlspecialchars(mb_strimwidth((string)$e['mensaje'], 0, 260, '…')) ?></td>
          <td style="font-size:.76rem;opacity:.85;">
            <?= htmlspecialchars(basename((string)($e['archivo'] ?? ''))) ?><?= $e['linea']?':'.(int)$e['linea']:'' ?>
          </td>
          <td style="font-size:.78rem"><?= htmlspecialchars((string)($e['usuario'] ?? '-')) ?></td>
          <td class="row-actions">
            <button class="btn btn-secondary" title="Ver detalle" onclick="verDetalle(<?= (int)$e['id'] ?>)">
              <span class="material-symbols-outlined" style="font-size:18px;">visibility</span>
            </button>
            <button class="btn btn-danger" title="Borrar este" onclick="borrar(<?= (int)$e['id'] ?>)">
              <span class="material-symbols-outlined" style="font-size:18px;">delete</span>
            </button>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ── Paginación ──────────────────────────────────────────────── -->
  <div class="pager">
    <span style="background:transparent;opacity:.75;">Mostrando <?= count($rows) ?> de <?= $total ?> · página <?= $page ?> de <?= $totalPages ?></span>
    <a class="<?= $page<=1?'disabled':'' ?>" href="<?= qs(['page'=>1]) ?>">&laquo;</a>
    <a class="<?= $page<=1?'disabled':'' ?>" href="<?= qs(['page'=>$page-1]) ?>">&lsaquo;</a>
    <span class="active"><?= $page ?></span>
    <a class="<?= $page>=$totalPages?'disabled':'' ?>" href="<?= qs(['page'=>$page+1]) ?>">&rsaquo;</a>
    <a class="<?= $page>=$totalPages?'disabled':'' ?>" href="<?= qs(['page'=>$totalPages]) ?>">&raquo;</a>
  </div>
</div>

<!-- ── Drawer de detalle ─────────────────────────────────────────── -->
<div class="scrim" id="scrim" onclick="cerrarDetalle()"></div>
<aside class="detalle-drawer" id="drawer">
  <div style="display:flex;align-items:center;gap:10px;">
    <h2 style="font-size:1.15rem;margin:0;flex:1;">Detalle del error</h2>
    <button class="btn btn-secondary" onclick="cerrarDetalle()"><span class="material-symbols-outlined">close</span></button>
  </div>
  <div id="detalleBody" style="margin-top:16px;font-size:.9rem;">Selecciona una fila…</div>
</aside>

<script>
async function verDetalle(id) {
    const r = await fetch('api_errores.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ accion:'detalle', id })
    }).then(r => r.json());
    const cont = document.getElementById('detalleBody');
    if (!r.ok) { cont.textContent = r.mensaje || 'Error'; return; }
    const f = r.fila;
    const ctx = f.contexto_decoded;
    cont.innerHTML = `
      <div class="field-label">Fecha</div><div>${f.fecha}</div>
      <div class="field-label">Tipo / Nivel</div>
      <div><code>${f.tipo}</code> &middot; <span class="pill pill-${f.nivel||'error'}">${f.nivel||''}</span></div>
      <div class="field-label">Usuario</div><div>${f.usuario||'-'}</div>
      <div class="field-label">Mensaje</div>
      <div style="white-space:pre-wrap;word-break:break-word;">${escape(f.mensaje||'')}</div>
      <div class="field-label">Archivo</div>
      <div><code>${f.archivo||'-'}</code>${f.linea?':'+f.linea:''}</div>
      ${ctx ? `<div class="field-label">Contexto</div><pre>${escape(JSON.stringify(ctx, null, 2))}</pre>` : ''}
    `;
    document.getElementById('drawer').classList.add('open');
    document.getElementById('scrim').classList.add('open');
}
function escape(s){return String(s).replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'})[c]);}
function cerrarDetalle(){
    document.getElementById('drawer').classList.remove('open');
    document.getElementById('scrim').classList.remove('open');
}
async function borrar(id){
    if (!confirm('Borrar este registro?')) return;
    const r = await fetch('api_errores.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ accion:'borrar', id })
    }).then(r => r.json());
    if (r.ok) location.reload(); else alert(r.mensaje || 'Error');
}
async function limpiarTodo(){
    if (!confirm('Borrar TODOS los registros del log? Esta accion no se puede deshacer.')) return;
    const r = await fetch('api_errores.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ accion:'limpiar' })
    }).then(r => r.json());
    if (r.ok) location.reload(); else alert(r.mensaje || 'Error');
}
async function limpiarPor(criterio, valor){
    if (!confirm(`Borrar todos los registros con ${criterio}=${valor}?`)) return;
    const accion = criterio === 'tipo' ? 'limpiar_tipo' : 'limpiar_nivel';
    const body = { accion }; body[criterio] = valor;
    const r = await fetch('api_errores.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify(body)
    }).then(r => r.json());
    if (r.ok) location.reload(); else alert(r.mensaje || 'Error');
}
async function purgar(dias){
    if (!confirm(`Borrar registros con mas de ${dias} dias de antiguedad?`)) return;
    const r = await fetch('api_errores.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ accion:'limpiar_antes', dias })
    }).then(r => r.json());
    if (r.ok) { alert(`Borrados: ${r.borrados}`); location.reload(); }
    else alert(r.mensaje || 'Error');
}
</script>
</body>
</html>
