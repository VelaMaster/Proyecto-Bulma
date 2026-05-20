/**
 * sw.js — Service Worker PWA "Inventarios Leche Bienestar"
 *
 * Estrategias:
 *  - Assets estáticos (CSS/JS/imágenes): Cache-First
 *  - Páginas PHP (HTML):                 Network-First  → cache fallback
 *  - APIs PHP GET (datos JSON):          Network-First  → api-cache-v1 (90 días)
 *  - POSTs a endpoints de guardado:      Network → si falla, guarda en
 *                                        IndexedDB y registra Background Sync
 *
 * Caches:
 *   bulma-pwa-v2   → assets estáticos + páginas HTML
 *   api-cache-v1   → respuestas JSON de APIs (lecherías, inventarios, etc.)
 *
 * IndexedDB: bulma_sync_db  |  stores: pending_requests, offline_session
 * Sync tag:  sync-inventarios
 */

'use strict';

const CACHE_NAME   = 'bulma-pwa-v2';
const API_CACHE    = 'api-cache-v1';
const SYNC_TAG     = 'sync-inventarios';
const DB_NAME      = 'bulma_sync_db';
const STORE_NAME   = 'pending_requests';

/* ─── Assets pre-cacheados en install ───────────────────────────── */
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
  '/js/offline_preload.js',
  '/imagenes/Logos/icon-192.png',
  '/imagenes/Logos/icon-512.png',
  '/imagenes/Logos/Logo_lecheparaelbienestar.png',
  '/offline.html',
];

/* ─── Páginas PHP que se cachean con Network-First ───────────────── */
const CACHE_PAGES = [
  '/iniciosesionPromotor.php',
  '/iniciosesionSupervisor.php',
  '/promotores/inicio.php',
  '/promotores/generarinventarioMensual.php',
  '/promotores/consultarinventarioMensual.php',
  '/promotores/generarreporteMensual.php',
  '/promotores/requerimiento.php',
  '/promotores/editarinventarioMensual.php',
  '/supervisor/inicio.php',
];

/* ─── APIs de datos (GET) que se cachean en api-cache-v1 ─────────── */
const API_ENDPOINTS = [
  'mis_lecherias',
  'obtenerAlmacenes',
  'obtenerLecheriasPorAlmacen',
  'obtenerInventarioAnterior',
  'obtenerSupervisorAsignado',
  'obtenerLecheriasRequerimiento',
  'buscar_inventario_guardado',
  'obtener_inventarios_por_lecheria',
  'obtener_inventario',
  'listar_inventarios_lecheria',
  'detalleInventarioMensual',
  'api_requerimiento_dotacion',
  'buscarLecheria',
  'calcularSurtimiento',
  'ver_pdf',
  'api_supervisor',
  'api_avance_promotores',
  'api_estado_promotor',
];

/* ─── POSTs que se encolan si no hay red ─────────────────────────── */
const SYNC_ENDPOINTS = [
  '/promotores/guardar_inventario.php',
  '/promotores/guardarReporteMensual.php',
  '/promotores/guardarRequerimiento.php',
  '/promotores/actualizar_inventario.php',
];

/* ════════════════════════════════════════════════════════════════════
   INDEXEDDB helpers
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
    tx.objectStore(STORE_NAME).add({ url, method, body, headers: headers || {}, timestamp: Date.now(), retries: 0 });
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
    get.onsuccess = () => { const i = get.result; if (i) { i.retries = (i.retries||0)+1; store.put(i); } };
    tx.oncomplete = resolve;
    tx.onerror    = () => reject(tx.error);
  });
}

/* ════════════════════════════════════════════════════════════════════
   INSTALL
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
   ACTIVATE — limpiar caches viejos (respetar api-cache-v1)
   ════════════════════════════════════════════════════════════════════ */
self.addEventListener('activate', (event) => {
  const keepCaches = [CACHE_NAME, API_CACHE];
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(
        keys.filter((k) => !keepCaches.includes(k)).map((k) => caches.delete(k))
      ))
      .then(() => self.clients.claim())
  );
});

/* ════════════════════════════════════════════════════════════════════
   FETCH — interceptar peticiones
   ════════════════════════════════════════════════════════════════════ */
self.addEventListener('fetch', (event) => {
  const req = event.request;
  const url = new URL(req.url);

  /* Solo mismo origen */
  if (url.origin !== self.location.origin) return;

  /* POST a sync endpoints → encolar si offline */
  if (req.method === 'POST' && SYNC_ENDPOINTS.some((ep) => url.pathname.includes(ep))) {
    event.respondWith(handleSyncEndpoint(req));
    return;
  }

  /* Assets estáticos → Cache-First */
  if (isStaticAsset(url.pathname)) {
    event.respondWith(cacheFirst(req));
    return;
  }

  /* APIs de datos (GET) → Network-First con api-cache-v1 */
  if (req.method === 'GET' && isApiEndpoint(url.pathname)) {
    event.respondWith(apiNetworkFirst(req));
    return;
  }

  /* Páginas PHP → Network-First con page cache */
  if (url.pathname.endsWith('.php') || url.pathname === '/') {
    event.respondWith(networkFirst(req));
    return;
  }

  /* Default */
  event.respondWith(networkFirst(req));
});

/* ── Helpers de tipo ─────────────────────────────────────────────── */
function isStaticAsset(pathname) {
  return /\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot|webp)$/i.test(pathname);
}

function isApiEndpoint(pathname) {
  return API_ENDPOINTS.some((ep) => pathname.includes(ep));
}

/* ════════════════════════════════════════════════════════════════════
   ESTRATEGIAS DE CACHE
   ════════════════════════════════════════════════════════════════════ */

/* ── Cache-First (assets estáticos) ─────────────────────────────── */
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

/* ── Network-First (páginas HTML) ───────────────────────────────── */
async function networkFirst(request) {
  try {
    const response = await fetch(request);
    if (response.ok) {
      const url = new URL(request.url);
      const shouldCache = CACHE_PAGES.some((p) => url.pathname.includes(p));
      if (shouldCache) {
        const cache = await caches.open(CACHE_NAME);
        cache.put(request, response.clone());
      }
    }
    return response;
  } catch {
    /* Sin red → caché → offline.html */
    const cached = await caches.match(request);
    if (cached) return cached;
    const offline = await caches.match('/offline.html');
    return offline || new Response(
      `<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
       <meta name="viewport" content="width=device-width,initial-scale=1">
       <title>Sin conexión</title>
       <style>body{font-family:Roboto,sans-serif;background:#141218;color:#e6e1e5;
        display:flex;align-items:center;justify-content:center;min-height:100vh;
        margin:0;text-align:center;padding:24px;}
        .card{background:#1e1b2e;border-radius:24px;padding:40px 32px;max-width:400px;}
       </style></head><body><div class="card">
       <p style="font-size:3rem">📶</p>
       <h1 style="font-size:1.5rem;font-weight:500">Sin conexión</h1>
       <p style="opacity:.7">Esta página no está disponible offline.<br>
       Regresa cuando tengas internet.</p>
       </div></body></html>`,
      { headers: { 'Content-Type': 'text/html;charset=utf-8' } }
    );
  }
}

/* ── Network-First para APIs (datos JSON) ───────────────────────── */
async function apiNetworkFirst(request) {
  const cache = await caches.open(API_CACHE);
  try {
    const response = await fetch(request);
    /* Solo cachear respuestas JSON exitosas */
    if (response.ok) {
      const ct = response.headers.get('content-type') || '';
      if (ct.includes('json') || ct.includes('text')) {
        cache.put(request, response.clone());
      }
    }
    return response;
  } catch {
    /* Sin red → buscar en api-cache-v1 */
    const cached = await cache.match(request);
    if (cached) {
      /* Agregar header para que el cliente sepa que es dato offline */
      const headers = new Headers(cached.headers);
      headers.set('X-Served-From', 'offline-cache');
      return new Response(cached.body, { status: cached.status, headers });
    }
    /* No hay cache → respuesta de error JSON amigable */
    const url = new URL(request.url);
    const endpoint = url.pathname.split('/').pop();
    return new Response(
      JSON.stringify({
        error: true,
        offline: true,
        mensaje: `Sin conexión. No hay datos en caché para "${endpoint}". Conecta a internet para descargar datos offline.`,
      }),
      {
        status: 503,
        headers: { 'Content-Type': 'application/json;charset=utf-8' },
      }
    );
  }
}

/* ── POST offline → encolar en IndexedDB ────────────────────────── */
async function handleSyncEndpoint(request) {
  try {
    return await fetch(request.clone());
  } catch {
    let body = '';
    try { body = await request.clone().text(); } catch {}
    const headersObj = {};
    request.headers.forEach((v, k) => { headersObj[k] = v; });

    await savePendingRequest(request.url, request.method, body, headersObj);

    try { await self.registration.sync.register(SYNC_TAG); } catch {}

    notifyClients({ type: 'QUEUED', url: request.url });

    return new Response(
      JSON.stringify({
        status:  'offline_queued',
        offline: true,
        mensaje: 'Sin conexión. Guardado localmente. Se enviará cuando regrese internet.',
      }),
      { status: 202, headers: { 'Content-Type': 'application/json;charset=utf-8' } }
    );
  }
}

/* ════════════════════════════════════════════════════════════════════
   BACKGROUND SYNC
   ════════════════════════════════════════════════════════════════════ */
self.addEventListener('sync', (event) => {
  if (event.tag === SYNC_TAG) event.waitUntil(syncPendingRequests());
});

async function syncPendingRequests() {
  const pending = await getPendingRequests();
  if (!pending.length) return;

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
        await deletePendingRequest(item.id);
        notifyClients({ type: 'SYNC_SESSION_EXPIRED', url: item.url });
      } else {
        await incrementRetry(item.id);
        failed++;
      }
    } catch {
      failed++;
      await incrementRetry(item.id);
      throw new Error('Sync incompleto');
    }
  }

  if (synced > 0) notifyClients({ type: 'SYNC_SUCCESS', count: synced });
  if (failed > 0) throw new Error(`${failed} elemento(s) fallaron`);
}

/* ════════════════════════════════════════════════════════════════════
   MENSAJES desde clientes
   ════════════════════════════════════════════════════════════════════ */
self.addEventListener('message', async (event) => {
  switch (event.data?.type) {
    case 'GET_PENDING_COUNT': {
      const items = await getPendingRequests();
      event.source?.postMessage({ type: 'PENDING_COUNT', count: items.length });
      break;
    }
    case 'TRIGGER_SYNC':
      try { await self.registration.sync.register(SYNC_TAG); }
      catch { await syncPendingRequests().catch(() => {}); }
      break;
    case 'SKIP_WAITING':
      self.skipWaiting();
      break;
    case 'CLEAR_API_CACHE':
      await caches.delete(API_CACHE);
      break;
    case 'CACHE_API_URLS': {
      /* Precachear una lista de URLs en api-cache-v1 */
      const urls = event.data.urls || [];
      const cache = await caches.open(API_CACHE);
      let cached = 0;
      for (const url of urls) {
        try {
          const res = await fetch(url, { credentials: 'include' });
          if (res.ok) { await cache.put(url, res); cached++; }
        } catch { /* skip URL inaccesible */ }
      }
      event.source?.postMessage({ type: 'CACHE_API_DONE', cached, total: urls.length });
      break;
    }
  }
});

async function notifyClients(message) {
  const clients = await self.clients.matchAll({ includeUncontrolled: true });
  clients.forEach((c) => c.postMessage(message));
}
