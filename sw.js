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

const CACHE_NAME   = 'bulma-pwa-v10';
const API_CACHE    = 'api-cache-v1';
const SYNC_TAG     = 'sync-inventarios';
const DB_NAME      = 'bulma_sync_db';
const STORE_NAME   = 'pending_requests';

/* ─── Dominios externos que se cachean con Cache-First ───────────── */
const CACHE_EXTERNAL_DOMAINS = [
  'fonts.googleapis.com',
  'fonts.gstatic.com',
  'esm.run',
  'cdnjs.cloudflare.com',
];

/* ─── URLs externas a precachear en install (fuentes + iconos) ────── */
const PRECACHE_EXTERNAL_URLS = [
  'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap',
  'https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
  'https://esm.run/@material/web/all.js',
  /* jsPDF — necesario para generación de PDF offline */
  'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js',
];

/* ─── Assets pre-cacheados en install ───────────────────────────── */
const PRECACHE_ASSETS = [
  '/main_md3.css',
  '/loader_md3.css',
  '/mainprincipal.css',
  '/estilos/generarreporteMensual.css',
  '/estilos/consultarInventarioMensual.css',
  '/estilos/generarInventarioMensual.css',
  '/estilos/detalleinventarioMensual.css',
  '/estilos/editarinventarioMensual.css',
  '/estilos/iniciocards.css',
  '/estilos/iniciosupervisor.css',
  '/js/temas_md3.js',
  '/js/loader_md3.js',
  '/js/promotores.js',
  '/js/inicio_lecherias.js',
  '/js/reporteMensual.js',
  '/js/requerimiento.js',
  '/js/editar_inventario.js',
  '/js/pdf_offline.js',
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
  '/promotores/detalleInventarioMensual.php',
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
  'api_requerimiento_dotacion',
  'obtenerReporteMensual',
  'buscarLecheria',
  'calcularSurtimiento',
  'ver_pdf',
  'api_supervisor',
  'api_avance_promotores',
  'api_estado_promotor',
  'listar_pdfs',
];

/* ─── POSTs que se encolan si no hay red ─────────────────────────── */
const SYNC_ENDPOINTS = [
  '/promotores/guardar_inventario.php',
  '/promotores/guardarReporteMensual.php',
  '/promotores/guardarRequerimiento.php',
  '/promotores/actualizar_inventario.php',
  /* PDF: se encolan para que el archivo en servidor también se regenere */
  '/promotores/generar_pdf_reporte.php',
  '/promotores/generar_pdf_requerimiento.php',
];

/* ════════════════════════════════════════════════════════════════════
   INDEXEDDB helpers
   ════════════════════════════════════════════════════════════════════ */
function openDB() {
  return new Promise((resolve, reject) => {
    /* Versión 2 — mantiene paridad con offline_login.js que crea offline_session */
    const req = indexedDB.open(DB_NAME, 2);
    req.onupgradeneeded = (e) => {
      const db = e.target.result;
      if (!db.objectStoreNames.contains(STORE_NAME)) {
        const store = db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
        store.createIndex('timestamp', 'timestamp', { unique: false });
      }
      /* Store de sesión offline (v2) — creado por offline_login.js pero lo
         declaramos aquí también para que el upgrade sea idempotente */
      if (!db.objectStoreNames.contains('offline_session')) {
        db.createObjectStore('offline_session', { keyPath: 'clave' });
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
    (async () => {
      const cache = await caches.open(CACHE_NAME);
      /* Cachear cada asset individualmente: si uno falla no rompe el install */
      const results = await Promise.allSettled(
        PRECACHE_ASSETS.map((url) =>
          fetch(url, { cache: 'reload' })
            .then((r) => { if (r.ok) return cache.put(url, r); })
            .catch(() => { /* asset no disponible, se omite */ })
        )
      );
      const fallidos = results.filter((r) => r.status === 'rejected').length;
      if (fallidos) console.warn(`[SW] ${fallidos} assets no cacheados en install.`);

      /* Precachear externos en segundo plano — no bloquea el install */
      precachearExternos();

      /* SIEMPRE skip waiting para que el nuevo SW active inmediatamente */
      await self.skipWaiting();
    })()
  );
});

/* Precachea fuentes/iconos/Material Web sin bloquear el install */
async function precachearExternos() {
  let cache;
  try { cache = await caches.open(CACHE_NAME); } catch { return; }
  for (const url of PRECACHE_EXTERNAL_URLS) {
    try {
      const already = await caches.match(url);
      if (already) continue; // ya está en caché, no re-descargar
      const res = await fetch(url, { credentials: 'omit', mode: 'cors' });
      if (res.ok) await cache.put(url, res);
    } catch {
      /* Si falla (sin red o CORS), se ignorará y se cacheará en primera visita */
    }
  }
}

/* ════════════════════════════════════════════════════════════════════
   ACTIVATE — migrar páginas cacheadas, limpiar caches viejos
   ════════════════════════════════════════════════════════════════════ */
self.addEventListener('activate', (event) => {
  event.waitUntil(
    (async () => {
      /* 1. Pedir al navegador que NO evicte nuestros caches (persistencia) */
      try {
        if (self.registration?.navigationPreload) {
          // opcional: habilitar preload de navegación si disponible
        }
        // navigator no existe en SW, usar self — la API es en el cliente.
        // En cambio, en activate podemos al menos marcar la intención
        // desde el cliente via postMessage tras SW_UPDATED.
      } catch {}

      /* 2. Migrar páginas PHP cacheadas de versiones anteriores → nueva versión
            Esto evita que el usuario pierda el caché de páginas al actualizar el SW */
      const newCache = await caches.open(CACHE_NAME);
      const allKeys  = await caches.keys();
      const oldPageCaches = allKeys.filter(
        (k) => k.startsWith('bulma-pwa-') && k !== CACHE_NAME
      );

      for (const oldCacheName of oldPageCaches) {
        try {
          const oldCache = await caches.open(oldCacheName);
          const oldKeys  = await oldCache.keys();
          for (const req of oldKeys) {
            /* Solo migrar páginas PHP (no assets: CSS/JS/imágenes ya se re-precachean) */
            if (req.url.endsWith('.php') || req.url.endsWith('/')) {
              const alreadyCached = await newCache.match(req);
              if (!alreadyCached) {
                try {
                  const res = await oldCache.match(req);
                  if (res) await newCache.put(req, res);
                } catch {}
              }
            }
          }
        } catch {}
      }

      /* 3. Eliminar caches viejos */
      const keepCaches = [CACHE_NAME, API_CACHE];
      await Promise.all(
        allKeys
          .filter((k) => !keepCaches.includes(k))
          .map((k) => caches.delete(k))
      );

      /* 4. Tomar control de todos los clientes abiertos */
      await self.clients.claim();

      /* 5. Notificar a todos los clientes que el SW se actualizó
            → el cliente limpiará el timestamp de offline_preload
               para forzar una re-descarga de datos API */
      notifyClients({ type: 'SW_UPDATED', version: CACHE_NAME });
    })()
  );
});

/* ════════════════════════════════════════════════════════════════════
   FETCH — interceptar peticiones
   ════════════════════════════════════════════════════════════════════ */
/* ─── Rutas de autenticación que el SW jamás debe interceptar ────── */
const AUTH_BYPASS = [
  '/login_proceso.php',
  '/cerrar_sesion.php',
  '/cerrar_sesionsupervisor.php',
];

self.addEventListener('fetch', (event) => {
  const req = event.request;
  const url = new URL(req.url);

  /* Recursos externos: Google Fonts, esm.run, cdnjs → Cache-First */
  if (url.origin !== self.location.origin) {
    if (req.method === 'GET' && CACHE_EXTERNAL_DOMAINS.some(d => url.hostname.includes(d))) {
      event.respondWith(cacheFirstExternal(req));
    }
    return; // otros externos: no interceptar
  }

  /* Auth (login / logout): dejar que el navegador maneje el redirect
     y la cookie de sesión de forma nativa, sin interferencia del SW */
  if (AUTH_BYPASS.some((ep) => url.pathname.includes(ep))) {
    return; // no llamar event.respondWith → el browser lo maneja solo
  }

  /* POST a sync endpoints → encolar si offline */
  if (req.method === 'POST' && SYNC_ENDPOINTS.some((ep) => url.pathname.includes(ep))) {
    event.respondWith(handleSyncEndpoint(req));
    return;
  }

  /* Cualquier otro POST (no es sync ni auth) → pasar directo a la red */
  if (req.method === 'POST') {
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

/* ── Cache-First para recursos externos (Fonts, Material Web) ────── */
async function cacheFirstExternal(request) {
  const cached = await caches.match(request);
  if (cached) return cached;
  try {
    const response = await fetch(request);
    if (response.ok || response.type === 'opaque') {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    const isJS  = request.destination === 'script';
    const isCSS = request.destination === 'style';
    return new Response(
      isJS || isCSS ? '/* offline */' : '',
      {
        status: 503,
        headers: { 'Content-Type': isJS ? 'application/javascript' : isCSS ? 'text/css' : 'text/plain' },
      }
    );
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
    /* Sin red → caché (primero exacta, luego ignorando query params) → offline.html */
    const cached = await caches.match(request)
               || await caches.match(request, { ignoreSearch: true });
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
    /* Cachear respuestas JSON exitosas y PDFs */
    if (response.ok) {
      const ct = response.headers.get('content-type') || '';
      if (ct.includes('json') || ct.includes('text') || ct.includes('pdf')) {
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

/* Guard: evita ejecuciones concurrentes desde múltiples pestañas/eventos */
let _syncRunning = false;
let _syncTimeout = null;         // safety valve
const MAX_RETRIES = 5;
const SYNC_MAX_MS  = 4 * 60 * 1000; // 4 min — fuerza reset si el SW se cuelga

self.addEventListener('sync', (event) => {
  if (event.tag === SYNC_TAG) event.waitUntil(syncPendingRequests());
});

async function syncPendingRequests() {
  if (_syncRunning) return;
  _syncRunning = true;

  /* Safety: si syncPendingRequests no termina en 4 min, liberar el lock */
  if (_syncTimeout) clearTimeout(_syncTimeout);
  _syncTimeout = setTimeout(() => {
    _syncRunning = false;
    _syncTimeout = null;
  }, SYNC_MAX_MS);

  try {
    const pending = await getPendingRequests();
    if (!pending.length) return;

    let synced = 0;
    let failed = 0;
    const syncedItems = [];

    for (const item of pending) {
      /* Abandonar items que superaron el límite de reintentos */
      if ((item.retries || 0) >= MAX_RETRIES) {
        await deletePendingRequest(item.id);
        notifyClients({ type: 'SYNC_ABANDONED', url: item.url });
        continue;
      }

      /* Progreso en tiempo real hacia el cliente */
      notifyClients({
        type:    'SYNC_PROGRESS',
        current: synced + failed + 1,
        total:   pending.length,
        url:     item.url,
      });

      try {
        const response = await fetch(item.url, {
          method:      item.method || 'POST',
          headers:     Object.assign({ 'Content-Type': 'application/json' }, item.headers || {}),
          body:        item.body,
          credentials: 'include',
        });

        if (response.ok) {
          await deletePendingRequest(item.id);
          synced++;
          syncedItems.push({ url: item.url, body: item.body, timestamp: item.timestamp });

          /* Invalidar caché de listar_pdfs cuando se guarda reporte/requerimiento */
          if (item.url.includes('guardarReporteMensual') || item.url.includes('guardarRequerimiento')) {
            try {
              const apiCache = await caches.open(API_CACHE);
              const keys = await apiCache.keys();
              for (const k of keys) {
                if (k.url.includes('listar_pdfs')) await apiCache.delete(k);
              }
            } catch {}
          }

          /* Tras sincronizar un inventario, auto-regenerar el PDF del reporte */
          if (item.url.includes('actualizar_inventario') || item.url.includes('guardar_inventario')) {
            try {
              const parsed = JSON.parse(item.body);
              const mes  = parsed.mes_periodo  || parsed.mes_reporte  || parsed.mes  || null;
              const anio = parsed.anio_periodo || parsed.anio_reporte || parsed.anio || null;
              if (mes && anio) {
                fetch(self.location.origin + '/promotores/regenerar_reporte_pdf.php', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json' },
                  body: JSON.stringify({ mes, anio }),
                  credentials: 'include',
                }).catch(() => {});
              }
            } catch {}
          }

        } else if (response.status === 401) {
          /* Sesión expirada — eliminar, no tiene caso reintentar */
          await deletePendingRequest(item.id);
          notifyClients({ type: 'SYNC_SESSION_EXPIRED', url: item.url });

        } else if (response.status >= 400 && response.status < 500) {
          /* Error de cliente (4xx) — el servidor rechazará siempre, eliminar */
          await deletePendingRequest(item.id);
          failed++;

        } else {
          /* Error de servidor (5xx) — reintentar en siguiente sync */
          await incrementRetry(item.id);
          failed++;
        }

      } catch {
        /* Error de red — reintentar, pero continuar con el siguiente item */
        await incrementRetry(item.id);
        failed++;
      }
    }

    if (synced > 0) notifyClients({ type: 'SYNC_SUCCESS', count: synced, items: syncedItems });
    /* NO lanzar Error si failed > 0 — evita que Background Sync reintente
       el evento completo y cause el bucle infinito / congelamiento */

  } finally {
    _syncRunning = false;
    if (_syncTimeout) { clearTimeout(_syncTimeout); _syncTimeout = null; }
  }
}

/* ════════════════════════════════════════════════════════════════════
   MENSAJES desde clientes
   ════════════════════════════════════════════════════════════════════ */
self.addEventListener('message', (event) => {
  if (!event.data) return;

  /* ── TRIGGER_SYNC: requiere event.waitUntil para que el SW no muera
        antes de terminar aunque el usuario cambie de pestaña o cierre. ── */
  if (event.data.type === 'TRIGGER_SYNC') {
    event.waitUntil(
      (async () => {
        /* 1. Registrar Background Sync (funciona aunque la app cierre en Chrome) */
        try { await self.registration.sync.register(SYNC_TAG); } catch {}
        /* 2. Ejecutar inmediatamente también — no esperar al evento 'sync' */
        await syncPendingRequests().catch(() => {});
      })()
    );
    return;
  }

  /* ── Resto de mensajes (no necesitan waitUntil) ──────────────────── */
  (async () => {
    switch (event.data.type) {
      case 'GET_PENDING_COUNT': {
        const items = await getPendingRequests();
        event.source?.postMessage({ type: 'PENDING_COUNT', count: items.length });
        break;
      }
      case 'GET_PENDING_DETAIL': {
        const items = await getPendingRequests();
        event.source?.postMessage({
          type:  'PENDING_DETAIL',
          count: items.length,
          items: items.map(i => ({ url: i.url, body: i.body, timestamp: i.timestamp, retries: i.retries })),
        });
        break;
      }
      case 'SKIP_WAITING':
        self.skipWaiting();
        break;
      case 'CLEAR_API_CACHE':
        await caches.delete(API_CACHE);
        break;
      case 'CACHE_API_URLS': {
        const urls = event.data.urls || [];
        const cache = await caches.open(API_CACHE);
        let cached = 0;
        for (const url of urls) {
          try {
            const res = await fetch(url, { credentials: 'include' });
            if (res.ok) { await cache.put(url, res); cached++; }
          } catch {}
        }
        event.source?.postMessage({ type: 'CACHE_API_DONE', cached, total: urls.length });
        break;
      }
    }
  })();
});

async function notifyClients(message) {
  const clients = await self.clients.matchAll({ includeUncontrolled: true });
  clients.forEach((c) => c.postMessage(message));
}
