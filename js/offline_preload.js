/**
 * offline_preload.js
 *
 * Descarga proactivamente todos los datos necesarios para trabajar
 * sin internet: lecherías, almacenes, supervisor e inventarios de
 * los últimos 3 meses. Pide al Service Worker que los guarde en
 * api-cache-v1 para uso offline.
 *
 * USO: incluir en promotores/inicio.php y supervisor/inicio.php,
 *      luego llamar OfflinePreload.run() cuando el DOM esté listo.
 */

'use strict';

const OfflinePreload = (() => {

  /* ── UI ──────────────────────────────────────────────────────────── */
  let _card = null;
  let _bar  = null;
  let _msg  = null;

  function crearUI() {
    if (document.getElementById('preload-card')) return;

    _card = document.createElement('div');
    _card.id = 'preload-card';
    _card.setAttribute('role', 'status');
    _card.style.cssText = `
      position: fixed;
      bottom: 24px;
      right: 24px;
      background: var(--md-sys-color-surface-container-high, #2b2930);
      color: var(--md-sys-color-on-surface, #e6e1e5);
      border-radius: 16px;
      padding: 16px 20px;
      min-width: 280px;
      max-width: 340px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.35);
      z-index: 8000;
      font-size: 0.85rem;
      border: 1px solid var(--md-sys-color-outline-variant, #49454f);
      opacity: 0;
      transform: translateY(16px);
      transition: opacity 0.3s, transform 0.3s;
    `;

    _card.innerHTML = `
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
        <span class="material-symbols-outlined" style="font-size:20px;color:var(--md-sys-color-primary,#d0bcff);flex-shrink:0">cloud_download</span>
        <span style="font-weight:500;flex-grow:1">Preparando datos offline</span>
        <span id="preload-close" style="cursor:pointer;opacity:.6;font-size:18px" class="material-symbols-outlined" title="Ocultar">close</span>
      </div>
      <div id="preload-msg" style="opacity:.75;margin-bottom:10px;min-height:18px;line-height:1.4"></div>
      <div style="background:var(--md-sys-color-surface-container-highest,#36343b);border-radius:100px;height:6px;overflow:hidden;">
        <div id="preload-bar" style="height:100%;background:var(--md-sys-color-primary,#d0bcff);width:0%;transition:width 0.4s ease;border-radius:100px;"></div>
      </div>
      <div id="preload-pct" style="text-align:right;margin-top:4px;font-size:0.75rem;opacity:.5">0%</div>
    `;

    document.body.appendChild(_card);
    _bar = _card.querySelector('#preload-bar');
    _msg = _card.querySelector('#preload-msg');

    /* Botón cerrar */
    _card.querySelector('#preload-close').addEventListener('click', () => ocultarUI());

    requestAnimationFrame(() => {
      _card.style.opacity   = '1';
      _card.style.transform = 'translateY(0)';
    });
  }

  function setProgreso(pct, mensaje) {
    if (!_card) return;
    if (_bar)  _bar.style.width = `${Math.min(100, pct)}%`;
    if (_msg)  _msg.textContent = mensaje;
    const pctEl = _card.querySelector('#preload-pct');
    if (pctEl) pctEl.textContent = `${Math.round(pct)}%`;
  }

  function finalizarUI(ok) {
    if (!_card) return;
    const icon = _card.querySelector('.material-symbols-outlined');
    if (icon) icon.textContent = ok ? 'cloud_done' : 'cloud_off';

    const titulo = _card.querySelector('span[style*="font-weight"]');
    if (titulo) titulo.textContent = ok
      ? 'Datos offline listos ✓'
      : 'Descarga parcial';

    if (_bar) _bar.style.background = ok
      ? 'var(--md-sys-color-tertiary,#7d5260)'
      : 'var(--md-sys-color-error,#f2b8b5)';

    setTimeout(ocultarUI, ok ? 3500 : 6000);
  }

  function ocultarUI() {
    if (!_card) return;
    _card.style.opacity   = '0';
    _card.style.transform = 'translateY(16px)';
    setTimeout(() => _card?.remove(), 350);
    _card = null;
  }

  /* ── Helpers ─────────────────────────────────────────────────────── */
  function mesesAtras(n) {
    const ahora = new Date();
    const result = [];
    for (let i = 0; i <= n; i++) {
      const d = new Date(ahora.getFullYear(), ahora.getMonth() - i, 1);
      result.push({ mes: d.getMonth() + 1, anio: d.getFullYear() });
    }
    return result;
  }

  /* Fetch simple que deja al SW cachear la respuesta */
  async function precargar(url) {
    try {
      const r = await fetch(url, { credentials: 'include', cache: 'no-store' });
      if (r.ok) return await r.json().catch(() => null);
      return null;
    } catch {
      return null;
    }
  }

  /* ── Núcleo del preload ──────────────────────────────────────────── */
  async function run(opcionesBase) {
    /* No correr si no hay internet */
    if (!navigator.onLine) return;

    /* No correr si el SW no está listo */
    if (!navigator.serviceWorker?.controller) {
      /* Esperar hasta 3s a que el SW tome control */
      await new Promise((res) => {
        if (navigator.serviceWorker.controller) { res(); return; }
        const t = setTimeout(res, 3000);
        navigator.serviceWorker.addEventListener('controllerchange', () => { clearTimeout(t); res(); }, { once: true });
      });
      if (!navigator.serviceWorker.controller) return;
    }

    /* Base URL para rutas relativas */
    const base = opcionesBase?.base || '/promotores';

    crearUI();

    const pasos = [];
    let paso = 0;

    function avanzar(mensaje) {
      paso++;
      const pct = (paso / pasos.length) * 100;
      setProgreso(pct, mensaje);
    }

    /* ── 1. Lecherías del promotor ─────────────────────────────── */
    setProgreso(2, 'Cargando tus lecherías...');
    const lecherias = await precargar(`${base}/mis_lecherias.php`);
    const listaLecherias = Array.isArray(lecherias) ? lecherias : [];
    const ids = listaLecherias.map(l => l.LECHER).filter(Boolean);

    /* ── 2. Almacenes y supervisor ─────────────────────────────── */
    setProgreso(8, 'Descargando almacenes y supervisor...');
    await Promise.all([
      precargar(`${base}/obtenerAlmacenes.php`),
      precargar(`${base}/obtenerSupervisorAsignado.php`),
    ]);

    /* ── 3. Últimos 3 meses: lecherías por almacén + requerimiento ── */
    const meses = mesesAtras(2); // [mes actual, mes-1, mes-2]
    let mesIdx = 0;
    for (const { mes, anio } of meses) {
      mesIdx++;
      setProgreso(10 + (mesIdx / meses.length) * 20,
        `Cargando datos ${nombreMes(mes)} ${anio}...`);
      await Promise.all([
        precargar(`${base}/obtenerLecheriasPorAlmacen.php?mes_reporte=${mes}&anio_reporte=${anio}`),
        precargar(`${base}/obtenerLecheriasRequerimiento.php?mes_reporte=${mes}&anio_reporte=${anio}`),
      ]);
    }

    /* ── 3b. Búsqueda inicial de lecherías (inventario mensual) ── */
    await precargar(`${base}/buscarLecheria.php?q=`);

    /* ── 4. Por cada lechería × mes: inventarios y datos previos ─ */
    if (ids.length === 0) {
      setProgreso(90, 'Sin lecherías asignadas');
    } else {
      const totalIds = ids.length;
      let idxDone = 0;

      /* Procesar en lotes de 3 para no saturar */
      const lote = 3;
      for (let i = 0; i < ids.length; i += lote) {
        const grupo = ids.slice(i, i + lote);
        await Promise.all(grupo.flatMap(id => {
          const calls = [
            precargar(`${base}/obtenerInventarioAnterior.php?lecher=${id}`),
          ];
          /* Precargar con los mismos params que usa generarinventarioMensual.php:
             ?clave=X&mes=M&anio=Y  (antes se omitían mes y anio → cache miss) */
          for (const { mes, anio } of meses) {
            calls.push(
              precargar(`${base}/obtener_inventarios_por_lecheria.php?clave=${encodeURIComponent(id)}&mes=${mes}&anio=${anio}`),
              precargar(`${base}/buscar_inventario_guardado.php?lecher=${id}&mes=${mes}&anio=${anio}`)
            );
          }
          return calls;
        }));
        idxDone += grupo.length;
        setProgreso(
          30 + (idxDone / totalIds) * 65,
          `Lechería ${idxDone}/${totalIds} descargada...`
        );
      }
    }

    setProgreso(100, `${ids.length} lecherías y 3 meses listos`);
    finalizarUI(true);

    /* Persistir timestamp del último preload */
    try {
      localStorage.setItem('offline_preload_ts', Date.now().toString());
      localStorage.setItem('offline_preload_lecherias', ids.length.toString());
    } catch {}
  }

  /* ── Correr sólo si no se corrió en las últimas 4 horas ─────────── */
  async function runIfStale(base) {
    if (!navigator.onLine) return;
    try {
      const ts = parseInt(localStorage.getItem('offline_preload_ts') || '0');
      const cuatroHoras = 4 * 60 * 60 * 1000;
      if (Date.now() - ts < cuatroHoras) return; // datos frescos, no descargar de nuevo
    } catch {}
    await run({ base });
  }

  function nombreMes(n) {
    return ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
            'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'][n] || n;
  }

  /* API pública */
  return { run, runIfStale };
})();

window.OfflinePreload = OfflinePreload;
