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
      case 'SYNC_SUCCESS':
        ocultarBannerOffline();
        mostrarToast(
          `✅ ${data.count} cambio(s) sincronizado(s) con el servidor.`,
          'success'
        );
        actualizarBadgePendientes(0);
        break;

      case 'QUEUED':
        actualizarContadorPendientesDesdeDB();
        break;

      case 'PENDING_COUNT':
        actualizarBadgePendientes(data.count);
        break;

      case 'SYNC_SESSION_EXPIRED':
        mostrarToast(
          '⚠️ Tu sesión expiró. Algunos cambios offline no pudieron enviarse. Por favor inicia sesión de nuevo.',
          'warning',
          8000
        );
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

window.addEventListener('online', () => {
  ocultarBannerOffline();
  mostrarToast('Conexión restaurada. Sincronizando...', 'success');

  /* Disparar sync manual por si Background Sync no está disponible */
  if (navigator.serviceWorker?.controller) {
    navigator.serviceWorker.controller.postMessage({ type: 'TRIGGER_SYNC' });
  }
});

window.addEventListener('offline', () => {
  mostrarBannerOffline();
});

document.addEventListener('DOMContentLoaded', () => {
  crearBannerOffline();
  if (!navigator.onLine) mostrarBannerOffline();

  /* Actualizar badge de pendientes al cargar */
  actualizarContadorPendientesDesdeDB();
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
   4. BADGE DE PENDIENTES
   ════════════════════════════════════════════════════════════════════ */

/* Actualizar badge en el header (si existe el elemento #offline-badge) */
function actualizarBadgePendientes(count) {
  let badge = document.getElementById('offline-badge');
  if (!badge) return;

  if (count > 0) {
    badge.textContent = count;
    badge.style.display = 'inline-flex';
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
  return new Promise((resolve, reject) => {
    const req = indexedDB.open('bulma_sync_db', 1);
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
