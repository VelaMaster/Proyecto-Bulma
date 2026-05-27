<?php
require_once __DIR__ . '/../includes/session_guard.php';
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['usuario']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'promotor') {
    header("Location: ../index.php");
    exit();
}
$nombre_usuario = $_SESSION['nombre'] ?? $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Inventarios - Promotor</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../main_md3.css">
    <link rel="stylesheet" href="../estilos/generarInventarioMensual.css">
    <link rel="stylesheet" href="../estilos/consultarInventarioMensual.css">
    <script type="importmap">{"imports":{"@material/web/":"https://esm.run/@material/web/"}}</script>
    <script type="module">import '@material/web/all.js';</script>

    <!-- PWA -->
    <!-- [OFFLINE DESACTIVADO] <link rel="manifest" href="/manifest.json"> -->
    <meta name="theme-color" content="#6750A4">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Inventarios">
    <link rel="apple-touch-icon" href="/imagenes/Logos/icon-192.png">
    <!-- Preconnect para reducir latencia de fuentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://esm.run">
</head>
<body>

<!-- ══ TOP BAR ══════════════════════════════════════════════════ -->
<header class="md3-top-app-bar">
    <div class="app-bar-start">
        <md-icon-button class="mobile-menu-btn" onclick="toggleDrawer()">
            <md-icon>menu</md-icon>
        </md-icon-button>
        <div class="app-brand"><span>Consultar Inventarios</span></div>
    </div>
    <div class="app-bar-end">
        <div class="desktop-nav">
            <md-text-button href="inicio.php"><md-icon slot="icon">home</md-icon>Inicio</md-text-button>
            <div style="position:relative;">
                <md-text-button id="btn-inv" onclick="abrirMenu('menu-inv')">
                    Inventario mensual <md-icon slot="icon">arrow_drop_down</md-icon>
                </md-text-button>
                <md-menu id="menu-inv" anchor="btn-inv">
                    <md-menu-item href="generarinventarioMensual.php">
                        <div slot="headline">Generar</div><md-icon slot="start">add_circle</md-icon>
                    </md-menu-item>
                    <md-menu-item href="editarinventarioMensual.php">
                        <div slot="headline">Editar</div><md-icon slot="start">edit</md-icon>
                    </md-menu-item>
                    <md-menu-item href="consultarinventarioMensual.php">
                        <div slot="headline">Consultar</div><md-icon slot="start">search</md-icon>
                    </md-menu-item>
                </md-menu>
            </div>
            <md-text-button href="generarreporteMensual.php">
                <md-icon slot="icon">receipt_long</md-icon>
                Reporte mensual
            </md-text-button>
            <md-text-button href="requerimiento.php">
                <md-icon slot="icon">inventory</md-icon>
                Requerimiento
            </md-text-button>
        </div>
        <md-filled-tonal-button href="../cerrar_sesion.php" style="margin-left:8px;">Salir</md-filled-tonal-button>
    </div>
</header>

<div class="md3-drawer-scrim" id="drawer-scrim" onclick="toggleDrawer()"></div>
<aside class="md3-drawer" id="mobile-drawer">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 16px 8px 24px;">
        <span style="font-size:1.25rem;font-weight:500;color:var(--md-sys-color-on-surface);">Menú</span>
        <md-icon-button onclick="toggleDrawer()"><md-icon>close</md-icon></md-icon-button>
    </div>
    <div style="overflow-y:auto;flex-grow:1;">
        <md-list style="background:transparent;">
            <md-list-item href="inicio.php" type="button">
                <div slot="headline">Inicio</div>
                <md-icon slot="start">home</md-icon>
            </md-list-item>
            <md-divider style="margin:8px 0;"></md-divider>
            <div class="drawer-section-title">Inventario mensual</div>
            <md-list-item href="generarinventarioMensual.php" type="button">
                <div slot="headline">Generar</div>
                <md-icon slot="start">add_box</md-icon>
            </md-list-item>
            <md-list-item href="consultarinventarioMensual.php" type="button">
                <div slot="headline">Consultar</div>
                <md-icon slot="start">search</md-icon>
            </md-list-item>
            <md-divider style="margin:8px 0;"></md-divider>
            <div class="drawer-section-title">Reporte mensual</div>
            <md-list-item href="generarreporteMensual.php" type="button">
                <div slot="headline">Generar</div>
                <md-icon slot="start">receipt_long</md-icon>
            </md-list-item>
            <md-divider style="margin:8px 0;"></md-divider>
            <div class="drawer-section-title">Requerimiento</div>
            <md-list-item href="requerimiento.php" type="button">
                <div slot="headline">Generar</div>
                <md-icon slot="start">inventory</md-icon>
            </md-list-item>
        </md-list>
    </div>
</aside>
<main class="panel-content">

    <!-- CABECERA -->
    <div class="form-section" style="margin-bottom:24px;">
        <div class="section-header">
            <div class="section-badge">
                <span class="material-symbols-outlined" style="font-size:17px;">inventory_2</span>
            </div>
            <h2 class="section-title">Mis lecherías asignadas</h2>
        </div>
    </div>

    <!-- ══ SECCIÓN PDFs ══════════════════════════════════════════════ -->
    <div class="form-section" id="seccionPDFs" style="margin-bottom:20px;">
        <div class="section-header" style="cursor:pointer; user-select:none;" onclick="togglePDFs()">
            <div class="section-badge">
                <span class="material-symbols-outlined" style="font-size:17px;">picture_as_pdf</span>
            </div>
            <h2 class="section-title" style="flex:1;">Mis PDFs generados</h2>
            <span class="material-symbols-outlined" id="iconoPDFs" style="color:var(--md-sys-color-on-surface-variant); transition:transform .25s;">expand_more</span>
        </div>

        <div id="panelPDFs" style="display:none; margin-top:12px;">
            <div id="listaPDFs">
                <!-- Skeleton mientras carga -->
                <div style="display:flex; flex-direction:column; gap:8px;" id="skeletonPDFs">
                    <div class="skeleton" style="height:56px; border-radius:12px;"></div>
                    <div class="skeleton" style="height:56px; border-radius:12px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- STATS -->
    <div class="stats-row" id="statsRow">
        <!-- se llena con JS -->
        <div class="stat-card">
            <div class="stat-icon"><span class="material-symbols-outlined">store</span></div>
            <div class="stat-info">
                <div class="stat-value" id="statTotal">—</div>
                <div class="stat-label">Lecherías asignadas</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><span class="material-symbols-outlined">description</span></div>
            <div class="stat-info">
                <div class="stat-value" id="statConInv">—</div>
                <div class="stat-label">Con inventarios</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><span class="material-symbols-outlined">pending_actions</span></div>
            <div class="stat-info">
                <div class="stat-value" id="statSinInv">—</div>
                <div class="stat-label">Sin inventarios</div>
            </div>
        </div>
    </div>

    <!-- FILTRO -->
    <div class="filter-bar">
        <div class="input-with-icon" style="position:relative; max-width:420px; flex:1;">
            <span class="material-symbols-outlined input-icon">search</span>
            <input class="md3-input" type="text" id="inputFiltro"
                   placeholder="Filtrar por nombre o número...">
        </div>
        <span class="filter-count" id="filterCount"></span>
    </div>

    <!-- GRID -->
    <div class="lecherias-grid" id="lecherasGrid">
        <!-- skeletons mientras carga -->
        <div class="lech-card is-skeleton">
            <div class="lech-card-top"></div>
            <div class="lech-card-body">
                <div class="sk-line skeleton" style="width:60%"></div>
                <div class="sk-line skeleton" style="width:80%"></div>
                <div class="sk-line skeleton" style="width:40%"></div>
            </div>
        </div>
        <div class="lech-card is-skeleton">
            <div class="lech-card-top"></div>
            <div class="lech-card-body">
                <div class="sk-line skeleton" style="width:70%"></div>
                <div class="sk-line skeleton" style="width:50%"></div>
                <div class="sk-line skeleton" style="width:60%"></div>
            </div>
        </div>
        <div class="lech-card is-skeleton">
            <div class="lech-card-top"></div>
            <div class="lech-card-body">
                <div class="sk-line skeleton" style="width:55%"></div>
                <div class="sk-line skeleton" style="width:75%"></div>
                <div class="sk-line skeleton" style="width:45%"></div>
            </div>
        </div>
    </div>

</main>

<script src="../js/temas_md3.js"></script>
<script>
/* ══ Sección PDFs ══════════════════════════════════════════════════ */
let _pdfsAbiertos = false;
let _pdfsYaCargados = false;

function togglePDFs() {
    const panel  = document.getElementById('panelPDFs');
    const icono  = document.getElementById('iconoPDFs');
    _pdfsAbiertos = !_pdfsAbiertos;
    panel.style.display = _pdfsAbiertos ? 'block' : 'none';
    icono.style.transform = _pdfsAbiertos ? 'rotate(180deg)' : '';
    if (_pdfsAbiertos && !_pdfsYaCargados) cargarPDFs();
}

function cargarPDFs() {
    _pdfsYaCargados = true;
    const lista = document.getElementById('listaPDFs');

    fetch('listar_pdfs.php')
        .then(r => {
            const offline = r.headers.get('X-Served-From') === 'offline-cache';
            return r.json().then(d => ({ data: d, offline }));
        })
        .then(({ data, offline }) => {
            renderPDFs(data, offline);
        })
        .catch(() => {
            // Sin conexión y sin caché: intentar desde la caché del SW
            renderPDFs([], true);
        });
}

function renderPDFs(archivos, offline) {
    const lista = document.getElementById('listaPDFs');

    if (!Array.isArray(archivos) || archivos.length === 0) {
        lista.innerHTML = `
            <div style="text-align:center; padding:24px; color:var(--md-sys-color-on-surface-variant);">
                <span class="material-symbols-outlined" style="font-size:40px; display:block; margin-bottom:8px; opacity:.5;">picture_as_pdf</span>
                ${offline ? 'Sin conexión y sin PDFs en caché.' : 'Aún no has generado ningún PDF.'}
            </div>`;
        return;
    }

    const meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                   'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

    lista.innerHTML = (offline ? `
        <div style="padding:8px 12px 4px; background:var(--md-sys-color-tertiary-container);
             color:var(--md-sys-color-on-tertiary-container); border-radius:12px; font-size:.82rem;
             display:flex; align-items:center; gap:8px; margin-bottom:10px;">
            <span class="material-symbols-outlined" style="font-size:18px;">wifi_off</span>
            Mostrando PDFs en caché (sin conexión)
        </div>` : '') +
        archivos.map(pdf => {
            const fecha = new Date(pdf.fecha + 'Z');
            const fechaTxt = isNaN(fecha) ? pdf.fecha :
                fecha.toLocaleDateString('es-MX', { day:'2-digit', month:'short', year:'numeric' });
            const kb = pdf.tamanio ? Math.round(pdf.tamanio / 1024) + ' KB' : '';
            return `
            <div style="display:flex; align-items:center; gap:12px; padding:10px 14px;
                 background:var(--md-sys-color-surface-container); border-radius:12px;
                 border:1px solid var(--md-sys-color-outline-variant); margin-bottom:8px;">
                <span class="material-symbols-outlined" style="color:var(--md-sys-color-error); font-size:28px; flex-shrink:0;">picture_as_pdf</span>
                <div style="flex:1; min-width:0; overflow:hidden;">
                    <div style="font-size:.9rem; font-weight:500; color:var(--md-sys-color-on-surface);
                         white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="${pdf.nombre}">
                        ${pdf.tipo}
                    </div>
                    <div style="font-size:.78rem; color:var(--md-sys-color-on-surface-variant);">
                        ${fechaTxt}${kb ? ' · ' + kb : ''}
                    </div>
                </div>
                <div style="display:flex; gap:6px; flex-shrink:0;">
                    <md-icon-button title="Ver PDF" onclick="abrirPDF('${pdf.url_ver}')">
                        <md-icon>visibility</md-icon>
                    </md-icon-button>
                    <md-icon-button title="Descargar" onclick="descargarPDF('${pdf.url_dl}', '${pdf.nombre}')">
                        <md-icon>download</md-icon>
                    </md-icon-button>
                </div>
            </div>`;
        }).join('');
}

function abrirPDF(url) {
    // Intentar abrir; si falla (offline sin cache) el service worker devolverá JSON de error
    const win = window.open(url, '_blank');
    // Fallback: si el navegador bloquea popups, usar fetch para detectar 404
    if (!win) {
        fetch(url)
            .then(r => {
                if (!r.ok || r.headers.get('content-type')?.includes('json')) {
                    mostrarErrorPDF();
                } else {
                    window.location.href = url;
                }
            })
            .catch(() => mostrarErrorPDF());
    }
}

function descargarPDF(url, nombre) {
    fetch(url)
        .then(r => {
            const ct = r.headers.get('content-type') || '';
            if (!r.ok || ct.includes('json')) {
                return r.json().then(d => Promise.reject(d.mensaje || 'Archivo no disponible'));
            }
            return r.blob();
        })
        .then(blob => {
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = nombre;
            a.click();
            setTimeout(() => URL.revokeObjectURL(a.href), 5000);
        })
        .catch(msg => {
            mostrarErrorPDF(typeof msg === 'string' ? msg : null);
        });
}

function mostrarErrorPDF(msg) {
    const m = msg || 'Archivo removido o no disponible. Genera un nuevo PDF cuando tengas conexión.';
    if (window.PWA?.mostrarToast) {
        window.PWA.mostrarToast('⚠️ ' + m, 'warning', 5000);
    } else {
        alert(m);
    }
}

/* ── Menú y drawer ── */
function abrirMenu(id) {
    document.querySelectorAll('md-menu').forEach(m => { if (m.id !== id) m.open = false; });
    document.getElementById(id).open = !document.getElementById(id).open;
}
function toggleDrawer() {
    document.getElementById('mobile-drawer')?.classList.toggle('open');
    document.getElementById('drawer-scrim')?.classList.toggle('open');
}
document.addEventListener('click', e => {
    if (!e.target.closest('md-menu') && !e.target.closest('md-text-button'))
        document.querySelectorAll('md-menu').forEach(m => m.open = false);
});

/* ── Lógica principal ── */
document.addEventListener('DOMContentLoaded', () => {
    const grid        = document.getElementById('lecherasGrid');
    const inputFiltro = document.getElementById('inputFiltro');
    const filterCount = document.getElementById('filterCount');

    let todasLecherias = [];

    /* ── Cargar lecherías del promotor ── */
    fetch('mis_lecherias.php')
        .then(r => r.json())
        .then(datos => {
            if (datos.error) {
                grid.innerHTML = `<div class="empty-state">
                    <span class="material-symbols-outlined">error</span>
                    <p>${datos.mensaje ?? 'Error al cargar lecherías.'}</p>
                </div>`;
                return;
            }

            todasLecherias = Array.isArray(datos) ? datos : [];

            // Stats
            const conInv = todasLecherias.filter(l => (l.TOTAL_INVENTARIOS ?? 0) > 0).length;
            document.getElementById('statTotal').textContent  = todasLecherias.length;
            document.getElementById('statConInv').textContent = conInv;
            document.getElementById('statSinInv').textContent = todasLecherias.length - conInv;

            renderGrid(todasLecherias);
        })
        .catch(() => {
            grid.innerHTML = `<div class="empty-state">
                <span class="material-symbols-outlined">wifi_off</span>
                <p>No se pudo conectar con el servidor.</p>
            </div>`;
        });

    /* ── Filtro en tiempo real ── */
    inputFiltro.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        const filtradas = q
            ? todasLecherias.filter(l =>
                String(l.LECHER).toLowerCase().includes(q) ||
                (l.NOMBRELECH ?? '').toLowerCase().includes(q) ||
                (l.MUNICIPIO  ?? '').toLowerCase().includes(q)
              )
            : todasLecherias;
        renderGrid(filtradas);
    });

    /* ── Render de cards ── */
    function renderGrid(lista) {
        filterCount.textContent = lista.length < todasLecherias.length
            ? `${lista.length} de ${todasLecherias.length} lecherías`
            : `${lista.length} lecherías`;

        if (lista.length === 0) {
            grid.innerHTML = `<div class="empty-state">
                <span class="material-symbols-outlined">search_off</span>
                <p>No se encontraron lecherías con ese criterio.</p>
            </div>`;
            return;
        }

        grid.innerHTML = '';
        lista.forEach(l => grid.appendChild(crearCard(l)));
    }

    /* ── Crear una card ── */
    function crearCard(l) {
        const totalInv  = parseInt(l.TOTAL_INVENTARIOS ?? 0);
        const hogares   = parseInt(l.TOTAL_HOGARES    ?? 0);
        const benef     = parseInt(l.TOTAL_INFANTILES ?? 0) + parseInt(l.TOTAL_RESTO ?? 0);

        // Fecha último inventario
        let ultimoTxt = 'Sin inventarios';
        if (l.ULTIMO_INVENTARIO) {
            const d = new Date(l.ULTIMO_INVENTARIO + 'T12:00:00');
            ultimoTxt = 'Último: ' + d.toLocaleDateString('es-MX', {
                day:'2-digit', month:'short', year:'numeric'
            });
        }

        const chipInv = totalInv > 0
            ? `<span class="lech-chip chip-inv">
                   <span class="material-symbols-outlined">description</span>
                   ${totalInv} inventario${totalInv > 1 ? 's' : ''}
               </span>`
            : `<span class="lech-chip chip-sin">
                   <span class="material-symbols-outlined">do_not_disturb_on</span>
                   Sin inventarios
               </span>`;

        const card = document.createElement('div');
        card.className = 'lech-card';
        card.innerHTML = `
            <div class="lech-card-top">
                <div class="lech-card-avatar">
                    <span class="material-symbols-outlined">storefront</span>
                </div>
                <div class="lech-card-header">
                    <div class="lech-card-num">Lechería #${l.LECHER}</div>
                    <div class="lech-card-nombre">${l.NOMBRELECH ?? '—'}</div>
                </div>
            </div>
            <div class="lech-card-body">
                <div class="lech-card-meta">
                    <span class="material-symbols-outlined">location_on</span>
                    ${l.MUNICIPIO ?? ''} · ${l.COMUNIDAD ?? ''}
                </div>
                <div class="lech-card-meta">
                    <span class="material-symbols-outlined">group</span>
                    ${hogares} hogares · ${benef} beneficiarios
                </div>
                <div class="lech-card-chips">
                    ${chipInv}
                </div>
            </div>
            <div class="lech-card-footer">
                <span class="lech-card-footer-txt">${ultimoTxt}</span>
                <span class="material-symbols-outlined">chevron_right</span>
            </div>
        `;

        card.addEventListener('click', () => {
            window.location.href = `detalleInventarioMensual.php?clave=${encodeURIComponent(l.LECHER)}&nombre=${encodeURIComponent(l.NOMBRELECH ?? '')}`;
        });

        return card;
    }
});
</script>
<!-- [OFFLINE DESACTIVADO] <script src="../js/pwa_offline.js"></script> -->
<!-- [OFFLINE DESACTIVADO] <script src="../js/offline_preload.js"></script> -->
</body>
</html>