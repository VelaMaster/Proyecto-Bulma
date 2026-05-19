/**
 * sw.js — Service Worker PWA "Inventarios Leche Bienestar"
 *
 * Estrategias:
 *  - Assets estáticos (CSS/JS/imágenes): Cache-First
 *  - Páginas PHP:                        Network-First  → cache fallback
 *  - POSTs a endpoints de guardado:      Network → si falla, guarda en
 *                                        IndexedDB y registra Background Sync
 *
 * IndexedDB: bulma_sync_db  |  store: pending_requests
 * Sync tag:  sync-inventarios
 */

'use strict';

const CACHE_NAME   = 'bulma-pwa-v1';
const SYNC_TAG     = 'sync-inventarios';
const DB_NAME      = 'bulma_sync_db';
const STORE_NAME   = 'pending_requests';

/* ─── Assets que se pre-cachean en el install ────────────────────── */
const PRECACHE_ASSETS = [
  '/main_md3.css',
  '/loader_md3.css',
  '/mainprincipal.css',
  '/js/temas_md3.js',
  '/js/loader_md3.js',
  '/js/promotores.js',
  '/js/inicio_lecherias.js',
  '/js/reporteMensual.js',
  '/js/requerimiento.js',
  '/js/editar_inventario.js',
  '/js/pwa_offline.js',
  '/js/offline_login.js',
  '/imagenes/Logos/icon-192.png',
  '/imagenes/Logos/icon-512.png',
  '/imagenes/Logos/Logo_lecheparaelbienestar.png',
  '/offline.html',
];

/* ─── Páginas que se cachean en runtime con Network-First ─────────── */
const CACHE_PAGES = [
  '/iniciosesionPromotor.php',
  '/iniciosesionSupervisor.php',
  '/promotores/inicio.php',
  '/promotores/generarinventarioMensual.php',
  '/promotores/consultarinventarioMensual.php',
  '/promotores/generarreporteMensual.php',
  '/promotores/requerimiento.php',
  '/supervisor/inicio.php',
];

/* ─── Endpoints de guardado que se encoloan si no hay red ─────────── */
const SYNC_ENDPOINTS = [
  '/promotores/guardar_inventario.php',
  '/promotores/guardarReporteMensual.php',
  '/promotores/guardarRequerimiento.php',
  '/promotores/actualizar_inventario.php',
];

/* ════════════════════════════════════════════════════════════════════
   INDEXEDDB  helpers
   ════════════════════════════════════════════════════════════════════ */
function openDB() {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open(DB_NAME, 1);
    req.onupgradeneeded = (e) => {
      const db = e.target.result;
      if (!db.objectStoreNames.contains(STORE_NAME)) {
        const store = db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
        store.createIndex('timestamp', 'timestamp', { unique: false });
      }
    };
    req.onsuccess  = () => resolve(req.result);
    req.onerror    = () => reject(req.error);
  });
}

async function savePendingRequest(url, method, body, headers) {
  const db = await openDB();
  return new Promise((resolve, reject) => {
    const tx = db.transaction(STORE_NAME, 'readwrite');
    tx.objectStore(STORE_NAME).add({
      url,
      method,
      body,
      headers: headers || {},
      timestamp: Date.now(),
      retries: 0,
    });
    tx.oncomplete = resolve;
    tx.onerror    = () => reject(tx.error);
  });
}

async function getPendingRequests() {
  const db = await openDB();
  return new Promise((resolve, reject) => {
    const tx  = db.transaction(STORE_NAME, 'readonly');
    const req = tx.objectStore(STORE_NAME).getAll();
    req.onsuccess = () => resolve(req.result);
    req.onerror   = () => reject(req.error);
  });
}

async function deletePendingRequest(id) {
  const db = await openDB();
  return new Promise((resolve, reject) => {
    const tx = db.transaction(STORE_NAME, 'readwrite');
    tx.objectStore(STORE_NAME).delete(id);
    tx.oncomplete = resolve;
    tx.onerror    = () => reject(tx.error);
  });
}

async function incrementRetry(id) {
  const db = await openDB();
  return new Promise((resolve, reject) => {
    const tx    = db.transaction(STORE_NAME, 'readwrite');
    const store = tx.objectStore(STORE_NAME);
    const get   = store.get(id);
    get.onsuccess = () => {
      const item = get.result;
      if (item) { item.retries = (item.retries || 0) + 1; store.put(item); }
    };
    tx.oncomplete = resolve;
    tx.onerror    = () => reject(tx.error);
  });
}

/* ════════════════════════════════════════════════════════════════════
   INSTALL — pre-cache assets estáticos
   ════════════════════════════════════════════════════════════════════ */
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(PRECACHE_ASSETS))
      .then(() => self.skipWaiting())
      .catch((err) => console.warn('[SW] Pre-cache parcial:', err))
  );
});

/* ════════════════════════════════════════════════════════════════════
   ACTIVATE — limpiar caches viejos
   ════════════════════════════════════════════════════════════════════ */
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) =>
        Promise.all(
          keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))
        )
      )
      .then(() => self.clients.claim())
  );
});

/* ════════════════════════════════════════════════════════════════════
   FETCH — interceptar peticiones
   ════════════════════════════════════════════════════════════════════ */
self.addEventListener('fetch', (event) => {
  const req = event.request;
  const url = new URL(req.url);

  /* Solo interceptar peticiones al mismo origen */
  if (url.origin !== self.location.origin) return;

  /* POSTs a endpoints de guardado → encolar si offline */
  if (
    req.method === 'POST' &&
    SYNC_ENDPOINTS.some((ep) => url.pathname.includes(ep))
  ) {
    event.respondWith(handleSyncEndpoint(req));
    return;
  }

  /* Assets estáticos → Cache-First */
  if (isStaticAsset(url.pathname)) {
    event.respondWith(cacheFirst(req));
    return;
  }

  /* Páginas PHP → Network-First con fallback a cache */
  if (url.pathname.endsWith('.php') || url.pathname === '/') {
    event.respondWith(networkFirst(req));
    return;
  }

  /* Default → Network-First */
  event.respondWith(networkFirst(req));
});

function isStaticAsset(pathname) {
  return /\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot|webp)$/i.test(pathname);
}

/* ── Cache-First ─────────────────────────────────────────────────── */
async function cacheFirst(request) {
  const cached = await caches.match(request);
  if (cached) return cached;

  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    return new Response('Recurso no disponible sin conexión.', { status: 503 });
  }
}

/* ── Network-First ───────────────────────────────────────────────── */
async function networkFirst(request) {
  try {
    const response = await fetch(request);
    if (response.ok) {
      /* Cachear solo páginas conocidas */
      const url = new URL(request.url);
      const shouldCache =
        CACHE_PAGES.some((p) => url.pathname.includes(p)) ||
        isStaticAsset(url.pathname);
      if (shouldCache) {
        const cache = await caches.open(CACHE_NAME);
        cache.put(request, response.clone());
      }
    }
    return response;
  } catch {
    /* Sin red → servir caché o fallback */
    const cached = await caches.match(request);
    if (cached) return cached;

    const offline = await caches.match('/offline.html');
    return (
      offline ||
      new Response(
        `<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
         <meta name="viewport" content="width=device-width,initial-scale=1">
         <title>Sin conexión</title>
         <style>
           body{font-family:Roboto,sans-serif;background:#141218;color:#e6e1e5;
                display:flex;align-items:center;justify-content:center;
                min-height:100vh;margin:0;text-align:center;padding:24px;}
           .card{background:#1e1b2e;border-radius:24px;padding:40px 32px;max-width:400px;}
           .icon{font-size:64px;margin-bottom:16px;}
           h1{font-size:1.5rem;font-weight:500;margin:0 0 12px}
           p{opacity:.7;line-height:1.6;margin:0}
         </style></head>
         <body><div class="card">
           <div class="icon">📶</div>
           <h1>Sin conexión a internet</h1>
           <p>Esta página no está disponible sin conexión.<br>
              Los datos que hayas guardado se sincronizarán automáticamente cuando se restaure la red.</p>
         </div></body></html>`,
        { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
      )
    );
  }
}

/* ── Sync endpoint handler (POST offline) ────────────────────────── */
async function handleSyncEndpoint(request) {
  /* Intentar enviar por red primero */
  try {
    const response = await fetch(request.clone());
    return response;
  } catch {
    /* Sin red: guardar en IndexedDB y registrar Background Sync */
    let body = '';
    try { body = await request.clone().text(); } catch {}

    const headersObj = {};
    request.headers.forEach((v, k) => { headersObj[k] = v; });

    await savePendingRequest(request.url, request.method, body, headersObj);

    /* Registrar Background Sync si está disponible */
    try {
      await self.registration.sync.register(SYNC_TAG);
    } catch {
      /* Background Sync API no disponible en este navegador */
    }

    /* Notificar a los clientes que hay datos pendientes */
    notifyClients({ type: 'QUEUED', url: request.url });

    /* Respuesta "falsa" de éxito para que la UI no falle */
    return new Response(
      JSON.stringify({
        status:  'offline_queued',
        mensaje: 'Sin conexión. El dato se guardó localmente y se sincronizará cuando haya internet.',
        offline: true,
      }),
      {
        status:  202,
        headers: { 'Content-Type': 'application/json; charset=utf-8' },
      }
    );
  }
}

/* ════════════════════════════════════════════════════════════════════
   BACKGROUND SYNC — enviar requests pendientes
   ════════════════════════════════════════════════════════════════════ */
self.addEventListener('sync', (event) => {
  if (event.tag === SYNC_TAG) {
    event.waitUntil(syncPendingRequests());
  }
});

async function syncPendingRequests() {
  const pending = await getPendingRequests();
  if (pending.length === 0) return;

  let synced = 0;
  let failed = 0;

  for (const item of pending) {
    try {
      const response = await fetch(item.url, {
        method:  item.method || 'POST',
        headers: Object.assign({ 'Content-Type': 'application/json' }, item.headers || {}),
        body:    item.body,
        credentials: 'include',
      });

      if (response.ok) {
        await deletePendingRequest(item.id);
        synced++;
      } else if (response.status === 401) {
        /* Sesión expirada — no tiene sentido reintentar */
        await deletePendingRequest(item.id);
        notifyClients({ type: 'SYNC_SESSION_EXPIRED', url: item.url });
      } else {
        await incrementRetry(item.id);
        failed++;
      }
    } catch {
      failed++;
      await incrementRetry(item.id);
      throw new Error('Sync incompleto, se reintentará'); // Fuerza retry del Background Sync
    }
  }

  if (synced > 0) {
    notifyClients({ type: 'SYNC_SUCCESS', count: synced });
  }

  /* Si hubo fallas parciales, re-lanzar el sync */
  if (failed > 0) {
    throw new Error(`${failed} elemento(s) no pudieron sincronizarse`);
  }
}

/* ════════════════════════════════════════════════════════════════════
   MENSAJES desde los clientes
   ════════════════════════════════════════════════════════════════════ */
self.addEventListener('message', async (event) => {
  switch (event.data?.type) {
    case 'GET_PENDING_COUNT': {
      const items = await getPendingRequests();
      event.source?.postMessage({ type: 'PENDING_COUNT', count: items.length });
      break;
    }
    case 'TRIGGER_SYNC': {
      try {
        await self.registration.sync.register(SYNC_TAG);
      } catch {
        await syncPendingRequests().catch(() => {});
      }
      break;
    }
    case 'SKIP_WAITING':
      self.skipWaiting();
      break;
  }
});

/* ── Utilidad: notificar a todos los clientes abiertos ───────────── */
async function notifyClients(message) {
  const clients = await self.clients.matchAll({ includeUncontrolled: true });
  clients.forEach((client) => client.postMessage(message));
}
