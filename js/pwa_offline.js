/**
 * pwa_offline.js — Helper cliente para PWA offline
 *
 * Responsabilidades:
 *  1. Registrar el Service Worker (/sw.js)
 *  2. Mostrar banner cuando no hay internet
 *  3. Mostrar toast de sincronización exitosa
 *  4. Badge de "pendientes" en el header
 *  5. Botón de instalación (Add to Home Screen)
 */

'use strict';

/* ════════════════════════════════════════════════════════════════════
   1. REGISTRO DEL SERVICE WORKER
   ════════════════════════════════════════════════════════════════════ */
if ('serviceWorker' in navigator) {
  window.addEventListener('load', async () => {
    try {
      const reg = await navigator.serviceWorker.register('/sw.js', { scope: '/' });
      console.log('[PWA] Service Worker registrado. Scope:', reg.scope);

      /* Detectar nueva versión disponible */
      reg.addEventListener('updatefound', () => {
        const newWorker = reg.installing;
        if (!newWorker) return;
        newWorker.addEventListener('statechange', () => {
          if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
            mostrarToast('Nueva versión disponible. Recarga la página para actualizar.', 'info', 6000);
          }
        });
      });

      /* Pedir conteo de pendientes al activarse */
      if (navigator.serviceWorker.controller) {
        navigator.serviceWorker.controller.postMessage({ type: 'GET_PENDING_COUNT' });
      }
    } catch (err) {
      console.warn('[PWA] Error al registrar SW:', err);
    }
  });

  /* ── Mensajes desde el Service Worker ─────────────────────────── */
  navigator.serviceWorker.addEventListener('message', (event) => {
    const data = event.data;
    if (!data) return;

    switch (data.type) {
      case 'SYNC_SUCCESS': {
        ocultarBannerOffline();
        actualizarBadgePendientes(0);
        /* Construir mensaje legible con los ítems sincronizados */
        const items = data.items || [];
        if (items.length === 0) {
          mostrarToast('✅ Cambios sincronizados con el servidor.', 'success');
          break;
        }
        /* Abrir modal de confirmación en lugar de solo un toast */
        abrirModalSyncExito(items);
        break;
      }

      case 'QUEUED':
        actualizarContadorPendientesDesdeDB();
        break;

      case 'PENDING_COUNT':
        actualizarBadgePendientes(data.count);
        break;

      case 'PENDING_DETAIL':
        /* Respuesta al GET_PENDING_DETAIL: rellenar lista del modal */
        rellenarListaModal(data.items || []);
        break;

      case 'SYNC_SESSION_EXPIRED':
        mostrarToast(
          '⚠️ Tu sesión expiró. Algunos cambios offline no pudieron enviarse. Inicia sesión de nuevo.',
          'warning',
          8000
        );
        break;

      case 'SYNC_PROGRESS':
        /* Progreso en tiempo real: actualizar badge con items restantes */
        actualizarContadorPendientesDesdeDB();
        break;

      case 'SYNC_ABANDONED':
        mostrarToast(
          '⚠️ Un cambio alcanzó el límite de reintentos y fue descartado.',
          'warning',
          6000
        );
        actualizarContadorPendientesDesdeDB();
        break;

      case 'SW_UPDATED':
        /* El SW se actualizó — limpiar timestamp de preload para forzar
           re-descarga de datos API en la próxima visita */
        try { localStorage.removeItem('offline_preload_ts'); } catch {}
        /* Disparar preload inmediato si hay módulo disponible y hay conexión */
        if (navigator.onLine && window.OfflinePreload?.runIfStale) {
          window.OfflinePreload.runIfStale('/promotores').catch(() => {});
        }
        break;
    }
  });
}

/* ════════════════════════════════════════════════════════════════════
   2. BANNER OFFLINE / ONLINE
   ════════════════════════════════════════════════════════════════════ */
let _bannerOffline = null;

function crearBannerOffline() {
  if (document.getElementById('pwa-offline-banner')) return;

  const banner = document.createElement('div');
  banner.id    = 'pwa-offline-banner';
  banner.setAttribute('role', 'alert');
  banner.setAttribute('aria-live', 'assertive');
  banner.style.cssText = `
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%) translateY(80px);
    background: var(--md-sys-color-error-container, #f9dedc);
    color: var(--md-sys-color-on-error-container, #410e0b);
    padding: 12px 24px;
    border-radius: 28px;
    font-size: 0.875rem;
    font-weight: 500;
    z-index: 9990;
    box-shadow: 0 4px 16px rgba(0,0,0,0.25);
    display: flex;
    align-items: center;
    gap: 10px;
    transition: transform 0.35s cubic-bezier(.4,0,.2,1), opacity 0.35s;
    opacity: 0;
    pointer-events: none;
    white-space: nowrap;
  `;
  banner.innerHTML = `
    <span class="material-symbols-outlined" style="font-size:20px;flex-shrink:0">wifi_off</span>
    Sin conexión
  `;
  document.body.appendChild(banner);
  _bannerOffline = banner;
}

function mostrarBannerOffline() {
  if (!_bannerOffline) crearBannerOffline();
  requestAnimationFrame(() => {
    _bannerOffline.style.opacity = '1';
    _bannerOffline.style.transform = 'translateX(-50%) translateY(0)';
    _bannerOffline.style.pointerEvents = 'auto';
  });
}

function ocultarBannerOffline() {
  if (!_bannerOffline) return;
  _bannerOffline.style.opacity = '0';
  _bannerOffline.style.transform = 'translateX(-50%) translateY(80px)';
  _bannerOffline.style.pointerEvents = 'none';
}

window.addEventListener('online', async () => {
  ocultarBannerOffline();
  mostrarToast('Conexión restaurada. Sincronizando...', 'success');

  /* 1. Registrar Background Sync desde el cliente — el browser lo ejecuta
        incluso si el usuario navega o cierra la app antes de que termine */
  try {
    const reg = await navigator.serviceWorker.ready;
    await reg.sync.register('sync-inventarios');
  } catch {}

  /* 2. TRIGGER_SYNC manual como respaldo inmediato (por si Background Sync
        no está disponible en este browser/plataforma) */
  if (navigator.serviceWorker?.controller) {
    navigator.serviceWorker.controller.postMessage({ type: 'TRIGGER_SYNC' });
  }
});

window.addEventListener('offline', () => {
  mostrarBannerOffline();
});

document.addEventListener('DOMContentLoaded', async () => {
  crearBannerOffline();
  asegurarBadge();
  if (!navigator.onLine) mostrarBannerOffline();

  /* Pedir al navegador que no evicte nuestros caches/IndexedDB */
  try {
    if (navigator.storage?.persist) {
      const granted = await navigator.storage.persist();
      if (!granted) console.warn('[PWA] Almacenamiento persistente no garantizado — el navegador puede limpiar el caché.');
    }
  } catch {}

  /* Actualizar badge de pendientes al cargar */
  actualizarContadorPendientesDesdeDB();

  /* Si hay pendientes Y hay conexión al cargar la página, disparar sync.
     Cubre el caso donde el SW no disparó por cierre de app o navegación. */
  if (navigator.onLine) {
    const count = await contarPendientesDB().catch(() => 0);
    if (count > 0) {
      try {
        const reg = await navigator.serviceWorker.ready;
        await reg.sync.register('sync-inventarios');
      } catch {}
      if (navigator.serviceWorker?.controller) {
        navigator.serviceWorker.controller.postMessage({ type: 'TRIGGER_SYNC' });
      }
    }
  }

  /* Disparar preload si el módulo está cargado y los datos son stale */
  if (navigator.onLine && window.OfflinePreload?.runIfStale) {
    window.OfflinePreload.runIfStale('/promotores').catch(() => {});
  }
});

/* ════════════════════════════════════════════════════════════════════
   3. TOAST NOTIFICATIONS
   ════════════════════════════════════════════════════════════════════ */
const TOAST_COLORS = {
  success: {
    bg:    'var(--md-sys-color-primary-container, #eaddff)',
    color: 'var(--md-sys-color-on-primary-container, #21005d)',
  },
  warning: {
    bg:    'var(--md-sys-color-tertiary-container, #ffd8e4)',
    color: 'var(--md-sys-color-on-tertiary-container, #31111d)',
  },
  info: {
    bg:    'var(--md-sys-color-secondary-container, #e8def8)',
    color: 'var(--md-sys-color-on-secondary-container, #1d192b)',
  },
  error: {
    bg:    'var(--md-sys-color-error-container, #f9dedc)',
    color: 'var(--md-sys-color-on-error-container, #410e0b)',
  },
};

let _toastContainer = null;

function obtenerToastContainer() {
  if (!_toastContainer) {
    _toastContainer = document.createElement('div');
    _toastContainer.id = 'pwa-toast-container';
    _toastContainer.style.cssText = `
      position: fixed;
      bottom: 80px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      flex-direction: column-reverse;
      align-items: center;
      gap: 8px;
      z-index: 9995;
      pointer-events: none;
    `;
    document.body.appendChild(_toastContainer);
  }
  return _toastContainer;
}

function mostrarToast(mensaje, tipo = 'info', duracion = 4000) {
  const colors  = TOAST_COLORS[tipo] || TOAST_COLORS.info;
  const toast   = document.createElement('div');
  toast.style.cssText = `
    background: ${colors.bg};
    color: ${colors.color};
    padding: 10px 20px;
    border-radius: 28px;
    font-size: 0.875rem;
    font-weight: 500;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    opacity: 0;
    transform: translateY(16px);
    transition: opacity 0.3s, transform 0.3s;
    pointer-events: auto;
    max-width: 90vw;
    text-align: center;
    white-space: nowrap;
  `;
  toast.textContent = mensaje;

  const container = obtenerToastContainer();
  container.appendChild(toast);

  requestAnimationFrame(() => {
    toast.style.opacity   = '1';
    toast.style.transform = 'translateY(0)';
  });

  setTimeout(() => {
    toast.style.opacity   = '0';
    toast.style.transform = 'translateY(16px)';
    toast.addEventListener('transitionend', () => toast.remove(), { once: true });
  }, duracion);
}

/* ════════════════════════════════════════════════════════════════════
   4. BADGE DE PENDIENTES + MODAL DE DETALLE
   ════════════════════════════════════════════════════════════════════ */

/* Crear badge en el header si la página no lo tiene */
function asegurarBadge() {
  if (document.getElementById('offline-badge')) return;
  const appBarEnd = document.querySelector('.app-bar-end');
  if (!appBarEnd) return;

  const badge = document.createElement('span');
  badge.id = 'offline-badge';
  badge.setAttribute('role', 'button');
  badge.setAttribute('tabindex', '0');
  badge.setAttribute('aria-label', 'Ver cambios pendientes');
  badge.title = 'Cambios pendientes — toca para ver el detalle';
  badge.style.cssText = `
    display:none;
    background:var(--md-sys-color-error,#B3261E);
    color:#fff;
    border-radius:12px;
    font-size:0.72rem;
    font-weight:700;
    min-width:22px;
    height:22px;
    padding:0 7px;
    align-items:center;
    justify-content:center;
    margin-right:6px;
    cursor:pointer;
    letter-spacing:0.2px;
    box-shadow:0 1px 4px rgba(0,0,0,.3);
    flex-shrink:0;
  `;
  badge.textContent = '0';
  appBarEnd.prepend(badge);
}

/* Actualizar badge en el header (si existe o se puede crear) */
function actualizarBadgePendientes(count) {
  asegurarBadge();
  let badge = document.getElementById('offline-badge');
  if (!badge) return;

  if (count > 0) {
    badge.textContent = count;
    badge.style.display = 'inline-flex';
    /* Registrar click para abrir modal — solo una vez */
    if (!badge._clickRegistrado) {
      badge._clickRegistrado = true;
      badge.addEventListener('click', abrirModalPendientes);
      badge.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') abrirModalPendientes();
      });
    }
  } else {
    badge.style.display = 'none';
  }
}

/* Consultar conteo directo desde IndexedDB */
async function actualizarContadorPendientesDesdeDB() {
  try {
    const count = await contarPendientesDB();
    actualizarBadgePendientes(count);
  } catch {}
}

function contarPendientesDB() {
  return new Promise((resolve) => {
    const req = indexedDB.open('bulma_sync_db', 2);
    req.onerror = () => resolve(0);
    req.onsuccess = () => {
      const db = req.result;
      if (!db.objectStoreNames.contains('pending_requests')) { resolve(0); return; }
      const tx    = db.transaction('pending_requests', 'readonly');
      const count = tx.objectStore('pending_requests').count();
      count.onsuccess = () => resolve(count.result);
      count.onerror   = () => resolve(0);
    };
  });
}

/* Leer todos los pendientes desde IndexedDB */
function leerPendientesDB() {
  return new Promise((resolve) => {
    const req = indexedDB.open('bulma_sync_db', 2);
    req.onerror = () => resolve([]);
    req.onsuccess = () => {
      const db = req.result;
      if (!db.objectStoreNames.contains('pending_requests')) { resolve([]); return; }
      const tx  = db.transaction('pending_requests', 'readonly');
      const all = tx.objectStore('pending_requests').getAll();
      all.onsuccess = () => resolve(all.result || []);
      all.onerror   = () => resolve([]);
    };
  });
}

/* ── Descripción legible de cada cambio pendiente ─────────────────── */
function describir(item) {
  const path = (() => {
    try { return new URL(item.url).pathname; } catch { return item.url; }
  })();

  let tipo = 'Cambio';
  let detalle = '';

  /* Tipo de operación por endpoint */
  if (path.includes('guardar_inventario'))    tipo = 'Inventario mensual guardado';
  else if (path.includes('actualizar_inventario')) tipo = 'Inventario mensual editado';
  else if (path.includes('guardarReporteMensual')) tipo = 'Reporte mensual guardado';
  else if (path.includes('generar_pdf_reporte'))   tipo = 'PDF de reporte pendiente';
  else if (path.includes('guardarRequerimiento'))  tipo = 'Requerimiento guardado';
  else if (path.includes('generar_pdf_requ'))      tipo = 'PDF de requerimiento pendiente';

  /* Extraer datos del body JSON */
  try {
    const b = typeof item.body === 'string' ? JSON.parse(item.body) : item.body;

    const meses = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

    if (b.clave_lecheria || b.CLAVE_LECHERIA) {
      const clave = b.clave_lecheria || b.CLAVE_LECHERIA;
      const nombre = b.nombre_lecheria || b.NOMBRELECH || '';
      detalle = `Lechería #${clave}${nombre ? ' — ' + nombre : ''}`;
    } else if (b.mes_reporte && b.anio_reporte) {
      const mes = parseInt(b.mes_reporte);
      detalle = `${meses[mes] || mes} ${b.anio_reporte}`;
    } else if (b.mes_base && b.anio_base) {
      const mes = parseInt(b.mes_base);
      detalle = `Base: ${meses[mes] || mes} ${b.anio_base}`;
    }
  } catch { /* body no es JSON válido */ }

  const fecha = item.timestamp
    ? new Date(item.timestamp).toLocaleString('es-MX', { dateStyle: 'short', timeStyle: 'short' })
    : '';
  const reintentos = item.retries > 0 ? ` · ${item.retries} reintento(s)` : '';

  return { tipo, detalle, fecha, reintentos };
}

/* ── Modal de cambios pendientes ─────────────────────────────────── */
function abrirModalPendientes() {
  /* Crear modal si no existe */
  let modal = document.getElementById('pwa-pending-modal');
  if (!modal) {
    modal = document.createElement('dialog');
    modal.id = 'pwa-pending-modal';
    modal.style.cssText = `
      border: none;
      border-radius: 28px;
      padding: 0;
      max-width: 480px;
      width: calc(100vw - 32px);
      background: var(--md-sys-color-surface-container-high, #1e1b2e);
      color: var(--md-sys-color-on-surface, #e6e1e5);
      box-shadow: 0 8px 32px rgba(0,0,0,.45);
    `;
    modal.innerHTML = `
      <div style="padding:20px 24px 12px; display:flex; align-items:center; justify-content:space-between; gap:12px;">
        <div style="display:flex; align-items:center; gap:10px;">
          <span class="material-symbols-outlined" style="color:var(--md-sys-color-error,#cf6679); font-size:22px;">sync_problem</span>
          <span style="font-size:1rem; font-weight:600;">Cambios pendientes de enviar</span>
        </div>
        <button id="pwa-modal-close" style="
          background:none; border:none; cursor:pointer; padding:4px;
          color:var(--md-sys-color-on-surface-variant,#cac4d0); border-radius:50%;
          display:flex; align-items:center; justify-content:center;
        " aria-label="Cerrar">
          <span class="material-symbols-outlined" style="font-size:22px;">close</span>
        </button>
      </div>
      <div id="pwa-modal-body" style="padding:0 24px 8px; max-height:55vh; overflow-y:auto;"></div>
      <div style="padding:12px 24px 20px; text-align:right;">
        <button id="pwa-modal-sync" style="
          background:var(--md-sys-color-primary,#d0bcff);
          color:var(--md-sys-color-on-primary,#21005d);
          border:none; border-radius:20px; padding:10px 20px;
          font-size:0.875rem; font-weight:600; cursor:pointer;
          display:inline-flex; align-items:center; gap:6px;
        ">
          <span class="material-symbols-outlined" style="font-size:18px;">sync</span>
          Sincronizar ahora
        </button>
      </div>
    `;
    document.body.appendChild(modal);

    modal.querySelector('#pwa-modal-close').addEventListener('click', () => modal.close());
    modal.querySelector('#pwa-modal-sync').addEventListener('click', () => {
      if (navigator.serviceWorker?.controller) {
        navigator.serviceWorker.controller.postMessage({ type: 'TRIGGER_SYNC' });
      }
      modal.close();
      mostrarToast('Sincronización iniciada...', 'info', 3000);
    });
    modal.addEventListener('click', (e) => { if (e.target === modal) modal.close(); });
  }

  /* Rellenar contenido */
  const body = modal.querySelector('#pwa-modal-body');
  body.innerHTML = `<p style="font-size:.82rem; color:var(--md-sys-color-on-surface-variant,#cac4d0); margin:0 0 12px;">
    Estos cambios están guardados en tu dispositivo. Se enviarán automáticamente cuando haya conexión.
  </p><div id="pwa-modal-lista" style="display:flex; flex-direction:column; gap:8px;">
    <div style="text-align:center; padding:16px; opacity:.6; font-size:.85rem;">Cargando...</div>
  </div>`;

  modal.showModal();

  /* Pedir detalle al SW */
  if (navigator.serviceWorker?.controller) {
    navigator.serviceWorker.controller.postMessage({ type: 'GET_PENDING_DETAIL' });
  } else {
    /* Fallback: leer directo de IndexedDB */
    leerPendientesDB().then(items => rellenarListaModal(items));
  }
}

function rellenarListaModal(items) {
  const lista = document.getElementById('pwa-modal-lista');
  if (!lista) return;

  if (!items || items.length === 0) {
    lista.innerHTML = `<div style="text-align:center; padding:16px; opacity:.6; font-size:.85rem;">
      No hay cambios pendientes.
    </div>`;
    return;
  }

  lista.innerHTML = items.map((item, i) => {
    const { tipo, detalle, fecha, reintentos } = describir(item);
    return `
      <div style="
        background:var(--md-sys-color-surface-container,#2b2930);
        border:1px solid var(--md-sys-color-outline-variant,#49454f);
        border-radius:12px; padding:10px 14px;
        display:flex; align-items:flex-start; gap:10px;
      ">
        <span class="material-symbols-outlined" style="
          font-size:20px; flex-shrink:0; margin-top:1px;
          color:var(--md-sys-color-primary,#d0bcff);
        ">pending_actions</span>
        <div style="min-width:0; flex:1;">
          <div style="font-size:.875rem; font-weight:600; color:var(--md-sys-color-on-surface,#e6e1e5);">${tipo}</div>
          ${detalle ? `<div style="font-size:.78rem; color:var(--md-sys-color-on-surface-variant,#cac4d0); margin-top:2px;">${detalle}</div>` : ''}
          ${fecha ? `<div style="font-size:.72rem; color:var(--md-sys-color-outline,#938f99); margin-top:3px;">${fecha}${reintentos}</div>` : ''}
        </div>
      </div>`;
  }).join('');
}

/* ── Modal de confirmación tras sync exitosa ─────────────────────── */
function abrirModalSyncExito(items) {
  /* Reutilizar el modal de pendientes pero en modo "éxito" */
  let modal = document.getElementById('pwa-sync-ok-modal');
  if (!modal) {
    modal = document.createElement('dialog');
    modal.id = 'pwa-sync-ok-modal';
    modal.style.cssText = `
      border: none;
      border-radius: 28px;
      padding: 0;
      max-width: 460px;
      width: calc(100vw - 32px);
      background: var(--md-sys-color-surface-container-high, #1e1b2e);
      color: var(--md-sys-color-on-surface, #e6e1e5);
      box-shadow: 0 8px 32px rgba(0,0,0,.45);
    `;
    modal.innerHTML = `
      <div style="padding:20px 24px 12px; display:flex; align-items:center; justify-content:space-between; gap:12px;">
        <div style="display:flex; align-items:center; gap:10px;">
          <span class="material-symbols-outlined" style="color:var(--md-sys-color-primary,#d0bcff); font-size:22px;">cloud_done</span>
          <span style="font-size:1rem; font-weight:600;">¡Sincronización exitosa!</span>
        </div>
        <button onclick="document.getElementById('pwa-sync-ok-modal').close()" style="
          background:none; border:none; cursor:pointer; padding:4px;
          color:var(--md-sys-color-on-surface-variant,#cac4d0); border-radius:50%;
          display:flex; align-items:center; justify-content:center;
        " aria-label="Cerrar">
          <span class="material-symbols-outlined" style="font-size:22px;">close</span>
        </button>
      </div>
      <p style="margin:0 24px 10px; font-size:.82rem; color:var(--md-sys-color-on-surface-variant,#cac4d0);">
        Los siguientes cambios se enviaron correctamente al servidor:
      </p>
      <div id="pwa-sync-ok-lista" style="padding:0 24px 8px; max-height:50vh; overflow-y:auto; display:flex; flex-direction:column; gap:8px;"></div>
      <div style="padding:12px 24px 20px; text-align:right;">
        <button onclick="document.getElementById('pwa-sync-ok-modal').close()" style="
          background:var(--md-sys-color-primary-container,#4a4458);
          color:var(--md-sys-color-on-primary-container,#eaddff);
          border:none; border-radius:20px; padding:10px 20px;
          font-size:0.875rem; font-weight:600; cursor:pointer;
        ">Entendido</button>
      </div>
    `;
    document.body.appendChild(modal);
    modal.addEventListener('click', (e) => { if (e.target === modal) modal.close(); });
  }

  /* Rellenar lista de ítems sincronizados */
  const lista = modal.querySelector('#pwa-sync-ok-lista');
  lista.innerHTML = items.map(item => {
    const { tipo, detalle, fecha } = describir(item);
    return `
      <div style="
        background:var(--md-sys-color-surface-container,#2b2930);
        border:1px solid var(--md-sys-color-outline-variant,#49454f);
        border-radius:12px; padding:10px 14px;
        display:flex; align-items:flex-start; gap:10px;
      ">
        <span class="material-symbols-outlined" style="
          font-size:20px; flex-shrink:0; margin-top:1px;
          color:#66bb6a;
        ">check_circle</span>
        <div style="min-width:0;">
          <div style="font-size:.875rem; font-weight:600; color:var(--md-sys-color-on-surface,#e6e1e5);">${tipo}</div>
          ${detalle ? `<div style="font-size:.78rem; color:var(--md-sys-color-on-surface-variant,#cac4d0); margin-top:2px;">${detalle}</div>` : ''}
          ${fecha ? `<div style="font-size:.72rem; color:var(--md-sys-color-outline,#938f99); margin-top:3px;">${fecha}</div>` : ''}
        </div>
      </div>`;
  }).join('');

  modal.showModal();
}

/* ════════════════════════════════════════════════════════════════════
   5. BOTÓN "INSTALAR APP" (Add to Home Screen)
   ════════════════════════════════════════════════════════════════════ */
let _deferredInstallPrompt = null;

window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  _deferredInstallPrompt = e;
  mostrarBotonInstalar();
});

window.addEventListener('appinstalled', () => {
  _deferredInstallPrompt = null;
  ocultarBotonInstalar();
  mostrarToast('¡App instalada! Ya puedes usarla desde tu pantalla de inicio.', 'success', 5000);
});

function mostrarBotonInstalar() {
  /* Si ya existe, no duplicar */
  if (document.getElementById('pwa-install-btn')) return;

  const btn = document.createElement('md-filled-tonal-button');
  btn.id            = 'pwa-install-btn';
  btn.title         = 'Instalar aplicación';
  btn.style.cssText = `
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9980;
    display: flex;
    align-items: center;
    gap: 8px;
  `;
  btn.innerHTML = `
    <md-icon slot="icon">install_mobile</md-icon>
    Instalar app
  `;
  btn.addEventListener('click', async () => {
    if (!_deferredInstallPrompt) return;
    _deferredInstallPrompt.prompt();
    const { outcome } = await _deferredInstallPrompt.userChoice;
    if (outcome === 'accepted') ocultarBotonInstalar();
    _deferredInstallPrompt = null;
  });

  document.body.appendChild(btn);
}

function ocultarBotonInstalar() {
  const btn = document.getElementById('pwa-install-btn');
  if (btn) btn.remove();
}

/* ── Exportar funciones útiles para otras partes del app ─────────── */
window.PWA = {
  mostrarToast,
  mostrarBannerOffline,
  ocultarBannerOffline,
  actualizarContadorPendientesDesdeDB,
  contarPendientesDB,
};
