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
  let _themeObs = null;

  function getCSSTema(varName, fallback) {
    return getComputedStyle(document.documentElement).getPropertyValue(varName).trim() || fallback;
  }

  function aplicarTemaAlCard() {
    if (!_card) return;
    _card.style.background           = getCSSTema('--md-sys-color-surface-container-high', '#ECE6F0');
    _card.style.color                = getCSSTema('--md-sys-color-on-surface', '#1D1B20');
    _card.style.borderColor          = getCSSTema('--md-sys-color-outline-variant', '#CAC4D0');
    const icon = _card.querySelector('.preload-icon');
    if (icon) icon.style.color       = getCSSTema('--md-sys-color-primary', '#6750A4');
    const title = _card.querySelector('.preload-title');
    if (title) title.style.color     = getCSSTema('--md-sys-color-on-surface', '#1D1B20');
    const close = _card.querySelector('#preload-close');
    if (close) close.style.color     = getCSSTema('--md-sys-color-on-surface-variant', '#49454F');
    if (_msg) _msg.style.color       = getCSSTema('--md-sys-color-on-surface-variant', '#49454F');
    const track = _card.querySelector('.preload-track');
    if (track) track.style.background = getCSSTema('--md-sys-color-surface-container-highest', '#E6E0E9');
    if (_bar) _bar.style.background  = getCSSTema('--md-sys-color-primary', '#6750A4');
    const pct = _card.querySelector('#preload-pct');
    if (pct) pct.style.color         = getCSSTema('--md-sys-color-on-surface-variant', '#49454F');
  }

  function inyectarEstilos() {
    if (document.getElementById('preload-styles')) return;
    const s = document.createElement('style');
    s.id = 'preload-styles';
    s.textContent = `
      #preload-card {
        position: fixed;
        bottom: 24px;
        right: 24px;
        border-radius: 16px;
        padding: 16px 20px;
        min-width: 280px;
        max-width: 340px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.25);
        z-index: 8000;
        font-size: 0.85rem;
        font-family: Roboto, sans-serif;
        border-style: solid;
        border-width: 1px;
        opacity: 0;
        transform: translateY(16px);
        transition: opacity 0.3s, transform 0.3s, background-color 0.3s, color 0.3s, border-color 0.3s;
      }
      #preload-card .preload-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
      }
      #preload-card .preload-icon {
        font-size: 20px;
        flex-shrink: 0;
      }
      #preload-card .preload-title {
        font-weight: 500;
        flex-grow: 1;
      }
      #preload-card .preload-close {
        cursor: pointer;
        opacity: .55;
        font-size: 18px;
        line-height: 1;
      }
      #preload-card .preload-close:hover { opacity: 1; }
      #preload-msg {
        margin-bottom: 10px;
        min-height: 18px;
        line-height: 1.4;
        font-size: 0.82rem;
      }
      .preload-track {
        border-radius: 100px;
        height: 6px;
        overflow: hidden;
      }
      #preload-bar {
        height: 100%;
        width: 0%;
        transition: width 0.4s ease;
        border-radius: 100px;
      }
      #preload-pct {
        text-align: right;
        margin-top: 4px;
        font-size: 0.75rem;
        opacity: .7;
      }
    `;
    document.head.appendChild(s);
  }

  function crearUI() {
    if (document.getElementById('preload-card')) return;

    inyectarEstilos();

    _card = document.createElement('div');
    _card.id = 'preload-card';
    _card.setAttribute('role', 'status');

    _card.innerHTML = `
      <div class="preload-header">
        <span class="material-symbols-outlined preload-icon">cloud_download</span>
        <span class="preload-title">Preparando datos offline</span>
        <span id="preload-close" class="material-symbols-outlined preload-close" title="Ocultar">close</span>
      </div>
      <div id="preload-msg"></div>
      <div class="preload-track">
        <div id="preload-bar"></div>
      </div>
      <div id="preload-pct">0%</div>
    `;

    document.body.appendChild(_card);
    _bar = _card.querySelector('#preload-bar');
    _msg = _card.querySelector('#preload-msg');

    aplicarTemaAlCard();

    _card.querySelector('#preload-close').addEventListener('click', () => ocultarUI());

    _themeObs = new MutationObserver(() => aplicarTemaAlCard());
    _themeObs.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme', 'data-theme-accent'] });

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
    const icon = _card.querySelector('.preload-icon');
    if (icon) icon.textContent = ok ? 'cloud_done' : 'cloud_off';

    const titulo = _card.querySelector('.preload-title');
    if (titulo) titulo.textContent = ok ? 'Datos offline listos ✓' : 'Descarga parcial';

    if (_bar) _bar.style.background = ok
      ? getCSSTema('--md-sys-color-tertiary', '#00639B')
      : getCSSTema('--md-sys-color-error', '#B3261E');

    setTimeout(ocultarUI, ok ? 3500 : 6000);
  }

  function ocultarUI() {
    if (_themeObs) { _themeObs.disconnect(); _themeObs = null; }
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
