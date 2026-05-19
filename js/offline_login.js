/**
 * offline_login.js
 *
 * Lógica de login cuando no hay internet.
 *
 * Flujo:
 *  1. Al cargar la página de login: si offline Y hay sesión guardada
 *     en IndexedDB → mostrar opción de continuar sin contraseña.
 *  2. Al enviar el form: si offline → intentar acceso offline en lugar
 *     de hacer POST al servidor.
 *  3. Después de login exitoso (llamado desde la página de destino) →
 *     guardar datos de sesión en IndexedDB para uso futuro offline.
 */

'use strict';

const OFFLINE_DB_NAME    = 'bulma_sync_db';
const OFFLINE_DB_VERSION = 2;
const SESSION_STORE      = 'offline_session';
const EXPIRY_DIAS        = 30;

/* ════════════════════════════════════════════════════════════════════
   IndexedDB: abrir/crear stores
   ════════════════════════════════════════════════════════════════════ */
function abrirOfflineDB() {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open(OFFLINE_DB_NAME, OFFLINE_DB_VERSION);

    req.onupgradeneeded = (e) => {
      const db = e.target.result;
      // Store de requests pendientes (ya existía en v1)
      if (!db.objectStoreNames.contains('pending_requests')) {
        const s = db.createObjectStore('pending_requests', { keyPath: 'id', autoIncrement: true });
        s.createIndex('timestamp', 'timestamp', { unique: false });
      }
      // Store de sesión offline (nuevo en v2)
      if (!db.objectStoreNames.contains(SESSION_STORE)) {
        db.createObjectStore(SESSION_STORE, { keyPath: 'clave' });
      }
    };

    req.onsuccess  = () => resolve(req.result);
    req.onerror    = () => reject(req.error);
  });
}

/* ── Guardar sesión en IndexedDB ─────────────────────────────────── */
async function guardarSesionOffline(sesion) {
  const db = await abrirOfflineDB();
  return new Promise((resolve, reject) => {
    const tx    = db.transaction(SESSION_STORE, 'readwrite');
    const store = tx.objectStore(SESSION_STORE);
    store.put({
      clave:      'sesion_actual',
      usuario:    sesion.usuario,
      nombre:     sesion.nombre,
      rol:        sesion.rol,
      clave_rol:  sesion.clave_rol,
      expiry:     Date.now() + (EXPIRY_DIAS * 24 * 60 * 60 * 1000),
      guardado_en: new Date().toISOString(),
    });
    tx.oncomplete = resolve;
    tx.onerror    = () => reject(tx.error);
  });
}

/* ── Obtener sesión guardada ─────────────────────────────────────── */
async function obtenerSesionOffline() {
  try {
    const db = await abrirOfflineDB();
    return new Promise((resolve, reject) => {
      const tx  = db.transaction(SESSION_STORE, 'readonly');
      const req = tx.objectStore(SESSION_STORE).get('sesion_actual');
      req.onsuccess = () => {
        const datos = req.result;
        if (!datos) { resolve(null); return; }
        // Verificar que no expiró
        if (datos.expiry < Date.now()) { resolve(null); return; }
        resolve(datos);
      };
      req.onerror = () => resolve(null);
    });
  } catch {
    return null;
  }
}

/* ── Borrar sesión offline (logout) ─────────────────────────────── */
async function limpiarSesionOffline() {
  try {
    const db = await abrirOfflineDB();
    return new Promise((resolve) => {
      const tx = db.transaction(SESSION_STORE, 'readwrite');
      tx.objectStore(SESSION_STORE).delete('sesion_actual');
      tx.oncomplete = resolve;
      tx.onerror    = resolve; // no fatal
    });
  } catch { /* silencioso */ }
}

/* ── Redirigir a la página cacheada según el rol ─────────────────── */
function redirigirSegunRol(rol) {
  const rutas = {
    promotor:    '/promotores/inicio.php',
    supervisor:  '/supervisor/inicio.php',
    distribucion: '/distribucion/inicio.php',
  };
  window.location.href = rutas[rol] || '/iniciosesionPromotor.php';
}

/* ════════════════════════════════════════════════════════════════════
   UI: Banner de "Continuar offline"
   ════════════════════════════════════════════════════════════════════ */
function mostrarBannerContinuarOffline(sesion) {
  // Si ya existe, no duplicar
  if (document.getElementById('offline-continue-banner')) return;

  const banner = document.createElement('div');
  banner.id = 'offline-continue-banner';
  banner.style.cssText = `
    background: var(--md-sys-color-surface-container, #2b2930);
    border: 1.5px solid var(--md-sys-color-outline-variant, #49454f);
    border-radius: 16px;
    padding: 16px 20px;
    margin-top: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    animation: slideDown 0.3s ease;
  `;
  banner.innerHTML = `
    <div style="display:flex; align-items:center; gap:10px; color:var(--md-sys-color-on-surface);">
      <span class="material-symbols-outlined" style="color:var(--md-sys-color-primary);font-size:22px">offline_bolt</span>
      <div>
        <div style="font-weight:500;font-size:0.9rem">Sin conexión detectada</div>
        <div style="font-size:0.8rem;opacity:0.7">Hay una sesión guardada de <strong>${sesion.nombre}</strong></div>
      </div>
    </div>
    <button id="btn-continuar-offline" style="
      background: var(--md-sys-color-primary-container, #eaddff);
      color: var(--md-sys-color-on-primary-container, #21005d);
      border: none;
      border-radius: 100px;
      padding: 10px 20px;
      font-size: 0.875rem;
      font-weight: 500;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      justify-content: center;
    ">
      <span class="material-symbols-outlined" style="font-size:18px">login</span>
      Continuar como ${sesion.nombre}
    </button>
    <div style="font-size:0.75rem;opacity:0.5;text-align:center">
      Los datos se sincronizarán cuando regrese internet
    </div>
  `;

  // Insertar debajo del formulario de login
  const form = document.querySelector('form.md3-form');
  if (form) {
    form.insertAdjacentElement('afterend', banner);
  } else {
    document.querySelector('.md3-surface-container')?.appendChild(banner);
  }

  document.getElementById('btn-continuar-offline')?.addEventListener('click', () => {
    redirigirSegunRol(sesion.rol);
  });
}

/* ════════════════════════════════════════════════════════════════════
   Inicialización en la página de login
   ════════════════════════════════════════════════════════════════════ */
async function inicializarLoginOffline() {
  const sesion = await obtenerSesionOffline();

  /* Si hay sesión y no hay internet → mostrar banner y auto-redirigir en 3s */
  if (sesion && !navigator.onLine) {
    mostrarBannerContinuarOffline(sesion);

    // Auto-redirigir después de 3 segundos (con countdown)
    let segundos = 3;
    const btn = document.getElementById('btn-continuar-offline');
    if (btn) {
      btn.textContent = `Continuando como ${sesion.nombre} en ${segundos}s...`;
      const interval = setInterval(() => {
        segundos--;
        if (segundos <= 0) {
          clearInterval(interval);
          redirigirSegunRol(sesion.rol);
        } else {
          btn.innerHTML = `<span class="material-symbols-outlined" style="font-size:18px">login</span> Continuando como ${sesion.nombre} en ${segundos}s...`;
        }
      }, 1000);

      // Si el usuario hace click, cancelar countdown
      btn.addEventListener('click', () => clearInterval(interval), { once: true });
    }
  }

  /* Interceptar envío del form cuando offline */
  const form = document.querySelector('form.md3-form');
  if (form) {
    form.addEventListener('submit', async (e) => {
      if (!navigator.onLine) {
        e.preventDefault();

        const sesionGuardada = await obtenerSesionOffline();
        if (sesionGuardada) {
          redirigirSegunRol(sesionGuardada.rol);
        } else {
          // Mostrar mensaje de error
          mostrarErrorOffline();
        }
      }
    });
  }
}

function mostrarErrorOffline() {
  const existente = document.getElementById('offline-error-msg');
  if (existente) return;

  const msg = document.createElement('div');
  msg.id = 'offline-error-msg';
  msg.style.cssText = `
    background: var(--md-sys-color-error-container, #f9dedc);
    color: var(--md-sys-color-on-error-container, #410e0b);
    border-radius: 12px;
    padding: 12px 16px;
    font-size: 0.85rem;
    margin-top: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
  `;
  msg.innerHTML = `
    <span class="material-symbols-outlined" style="font-size:20px">wifi_off</span>
    Sin conexión y sin sesión guardada. Conecta a internet para iniciar sesión por primera vez.
  `;

  const form = document.querySelector('form.md3-form');
  form?.insertAdjacentElement('afterend', msg);
}

/* ── Arrancar al cargar el DOM ───────────────────────────────────── */
document.addEventListener('DOMContentLoaded', inicializarLoginOffline);

/* ── Exportar para uso global ────────────────────────────────────── */
window.OfflineLogin = {
  guardarSesionOffline,
  obtenerSesionOffline,
  limpiarSesionOffline,
};
