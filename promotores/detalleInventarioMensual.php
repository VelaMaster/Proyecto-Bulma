<?php
require_once __DIR__ . '/../includes/session_guard.php';
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['usuario']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'promotor') {
    header("Location: ../index.php");
    exit();
}
$clave  = htmlspecialchars($_GET['clave']  ?? '');
$nombre = htmlspecialchars($_GET['nombre'] ?? 'Lechería');

if ($clave === '') {
    header("Location: consultarinventarioMensual.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventarios — <?= $nombre ?> - Promotor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../main_md3.css">
    <link rel="stylesheet" href="../estilos/generarInventarioMensual.css">
    <link rel="stylesheet" href="../estilos/consultarInventarioMensual.css">
    <link rel="stylesheet" href="../estilos/detalleinventarioMensual.css">
    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#6750A4">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Inventarios">
    <link rel="apple-touch-icon" href="/imagenes/Logos/icon-192.png">
    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://esm.run">
    <script type="importmap">{"imports":{"@material/web/":"https://esm.run/@material/web/"}}</script>
    <script type="module">import '@material/web/all.js';</script>
</head>
<body>
<header class="md3-top-app-bar">
    <div class="app-bar-start">
        <md-icon-button class="mobile-menu-btn" onclick="toggleDrawer()">
            <md-icon>menu</md-icon>
        </md-icon-button>
        <div class="app-brand"><span>Detalle de Inventarios</span></div>
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
                        <div slot="headline">Generar</div><md-icon slot="start">add_box</md-icon>
                    </md-menu-item>
                    <md-menu-item href="consultarinventarioMensual.php">
                        <div slot="headline">Consultar</div><md-icon slot="start">search</md-icon>
                    </md-menu-item>
                </md-menu>
            </div>
            <md-text-button href="generarreporteMensual.php">
                <md-icon slot="icon">receipt_long</md-icon>Reporte mensual
            </md-text-button>
            <md-text-button href="requerimiento.php">
                <md-icon slot="icon">inventory</md-icon>Requerimiento
            </md-text-button>
        </div>
        <md-filled-tonal-button href="../cerrar_sesion.php" style="margin-left:8px;">
            <md-icon slot="icon">logout</md-icon>Salir
        </md-filled-tonal-button>
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
                <div slot="headline">Inicio</div><md-icon slot="start">home</md-icon>
            </md-list-item>
            <md-divider style="margin:8px 0;"></md-divider>
            <div class="drawer-section-title">Inventario mensual</div>
            <md-list-item href="generarinventarioMensual.php" type="button">
                <div slot="headline">Generar</div><md-icon slot="start">add_box</md-icon>
            </md-list-item>
            <md-list-item href="consultarinventarioMensual.php" type="button">
                <div slot="headline">Consultar</div><md-icon slot="start">search</md-icon>
            </md-list-item>
            <md-divider style="margin:8px 0;"></md-divider>
            <div class="drawer-section-title">Reporte mensual</div>
            <md-list-item href="generarreporteMensual.php" type="button">
                <div slot="headline">Generar</div><md-icon slot="start">receipt_long</md-icon>
            </md-list-item>
            <md-divider style="margin:8px 0;"></md-divider>
            <div class="drawer-section-title">Requerimiento</div>
            <md-list-item href="requerimiento.php" type="button">
                <div slot="headline">Generar</div><md-icon slot="start">inventory</md-icon>
            </md-list-item>
        </md-list>
    </div>
</aside>

<main class="panel-content">

    <!-- Volver -->
    <button class="btn-volver" onclick="history.back()">
        <span class="material-symbols-outlined">arrow_back</span>
        Volver a mis lecherías
    </button>

    <!-- Hero de la lechería -->
    <div class="detalle-hero">
        <div class="detalle-hero-icon">
            <span class="material-symbols-outlined">storefront</span>
        </div>
        <div class="detalle-hero-info">
            <div class="detalle-hero-num">Lechería #<?= $clave ?></div>
            <div class="detalle-hero-nombre"><?= $nombre ?></div>
        </div>
    </div>

    <!-- Tabs de tipo de documento -->
    <div class="anio-tabs" id="tipoTabs" style="margin-bottom:4px;">
        <button class="anio-tab active" data-tipo="inventario">
            <span class="material-symbols-outlined" style="font-size:15px;vertical-align:-3px;">description</span>
            Inventarios
        </button>
        <button class="anio-tab" data-tipo="reporte">
            <span class="material-symbols-outlined" style="font-size:15px;vertical-align:-3px;">receipt_long</span>
            Reportes
        </button>
        <button class="anio-tab" data-tipo="requerimiento">
            <span class="material-symbols-outlined" style="font-size:15px;vertical-align:-3px;">inventory</span>
            Requerimientos
        </button>
    </div>

    <!-- Tabs de año (solo visible en inventarios) -->
    <div class="anio-tabs" id="anioTabs">
        <button class="anio-tab active" data-anio="">Todos</button>
        <!-- años se agregan dinámicamente -->
    </div>

    <!-- Lista -->
    <div class="inv-list" id="invList">
        <!-- skeletons -->
        <div class="inv-row" style="pointer-events:none;">
            <div class="inv-row-icon skeleton" style="width:42px;height:42px;border-radius:10px;"></div>
            <div class="inv-row-info">
                <div class="skeleton" style="height:12px;width:50%;border-radius:6px;margin-bottom:8px;"></div>
                <div class="skeleton" style="height:10px;width:70%;border-radius:6px;"></div>
            </div>
        </div>
        <div class="inv-row" style="pointer-events:none;">
            <div class="inv-row-icon skeleton" style="width:42px;height:42px;border-radius:10px;"></div>
            <div class="inv-row-info">
                <div class="skeleton" style="height:12px;width:40%;border-radius:6px;margin-bottom:8px;"></div>
                <div class="skeleton" style="height:10px;width:60%;border-radius:6px;"></div>
            </div>
        </div>
    </div>

</main>

<script src="../js/temas_md3.js"></script>
<script src="../js/pwa_offline.js"></script>
<script src="../js/offline_preload.js"></script>
<script>
const CLAVE_LECHERIA = <?= json_encode($clave) ?>;

function abrirMenu(id) {
    document.querySelectorAll('md-menu').forEach(m => { if (m.id !== id) m.open = false; });
    document.getElementById(id).open = !document.getElementById(id).open;
}
function toggleDrawer() {
    document.getElementById('mobile-drawer').classList.toggle('open');
    document.getElementById('drawer-scrim').classList.toggle('open');
}
document.addEventListener('click', e => {
    if (!e.target.closest('md-menu') && !e.target.closest('md-text-button'))
        document.querySelectorAll('md-menu').forEach(m => m.open = false);
});

document.addEventListener('DOMContentLoaded', () => {
    const invList  = document.getElementById('invList');
    const anioTabs = document.getElementById('anioTabs');
    const tipoTabs = document.getElementById('tipoTabs');
    let tipoActivo = 'inventario';
    let anioActivo = '';

    /* ── Tabs de tipo ── */
    tipoTabs.addEventListener('click', e => {
        const btn = e.target.closest('.anio-tab[data-tipo]');
        if (!btn) return;
        tipoTabs.querySelectorAll('.anio-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        tipoActivo = btn.dataset.tipo;
        // Mostrar/ocultar tabs de año (solo aplican a inventarios)
        anioTabs.style.display = tipoActivo === 'inventario' ? '' : 'none';
        if (tipoActivo === 'inventario') {
            cargarInventarios(anioActivo);
        } else {
            cargarDocs(tipoActivo);
        }
    });

    /* ── Inventarios ── */
    cargarInventarios('');

    function cargarInventarios(anio) {
        anioActivo = anio;
        mostrarSkeleton();
        const params = new URLSearchParams({ clave: CLAVE_LECHERIA });
        if (anio) params.append('anio', anio);

        fetch('listar_inventarios_lecheria.php?' + params.toString())
            .then(r => r.json())
            .then(rows => {
                if (!Array.isArray(rows)) { mostrarError('Error al cargar inventarios.'); return; }
                if (anio === '') construirAnioTabs(rows);
                renderInvRows(rows);
            })
            .catch(() => mostrarError('Error de conexión.'));
    }

    function construirAnioTabs(rows) {
        const anios = [...new Set(
            rows.filter(r => r.FECHA).map(r => r.FECHA.substring(0, 4))
        )].sort((a, b) => b - a);

        anioTabs.querySelectorAll('[data-anio]:not([data-anio=""])').forEach(t => t.remove());
        anios.forEach(a => {
            const btn = document.createElement('button');
            btn.className    = 'anio-tab';
            btn.dataset.anio = a;
            btn.textContent  = a;
            anioTabs.appendChild(btn);
        });
    }

    anioTabs.addEventListener('click', e => {
        const btn = e.target.closest('.anio-tab[data-anio]');
        if (!btn) return;
        anioTabs.querySelectorAll('.anio-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        cargarInventarios(btn.dataset.anio);
    });

    /* ── Reportes / Requerimientos ── */
    function cargarDocs(tipo) {
        mostrarSkeleton();
        fetch(`listar_docs_lecheria.php?clave=${encodeURIComponent(CLAVE_LECHERIA)}&tipo=${tipo}`)
            .then(r => r.json())
            .then(rows => {
                if (!Array.isArray(rows)) { mostrarError('Error al cargar documentos.'); return; }
                renderDocRows(rows, tipo);
            })
            .catch(() => mostrarError('Error de conexión.'));
    }

    function renderDocRows(rows, tipo) {
        if (rows.length === 0) {
            const label = tipo === 'reporte' ? 'reportes' : 'requerimientos';
            invList.innerHTML = `<div class="empty-state">
                <span class="material-symbols-outlined">inventory_2</span>
                <p>No hay ${label} registrados para esta lechería.</p>
            </div>`;
            return;
        }
        invList.innerHTML = '';
        rows.forEach(doc => invList.appendChild(crearFilaDoc(doc, tipo)));
    }

    function crearFilaDoc(doc, tipo) {
        const iconName = tipo === 'reporte' ? 'receipt_long' : 'inventory';
        const meta = tipo === 'reporte'
            ? [doc.periodo_ini && doc.periodo_fin ? `${doc.periodo_ini} — ${doc.periodo_fin}` : null]
            : [
                doc.familias    != null ? `Familias: ${doc.familias}`       : null,
                doc.req_actual  != null ? `Req. actual: ${doc.req_actual}`  : null,
              ];
        const metaTxt = meta.filter(Boolean).join('  ·  ');

        let acciones = '';
        if (doc.pdf_existe) {
            acciones = `
                <button class="btn-pdf btn-ver" data-url="${encodeURIComponent(doc.pdf_url)}" title="Ver PDF">
                    <span class="material-symbols-outlined">visibility</span> Ver
                </button>
                <button class="btn-pdf btn-dl" data-url="${encodeURIComponent(doc.pdf_url)}" title="Descargar PDF">
                    <span class="material-symbols-outlined">download</span>
                </button>`;
        } else {
            acciones = `
                <span class="estado-pill" style="background:var(--md-sys-color-surface-variant);color:var(--md-sys-color-on-surface-variant);display:inline-flex;align-items:center;gap:4px;">
                    <span class="material-symbols-outlined" style="font-size:14px;">block</span>Sin archivo
                </span>`;
        }

        const fila = document.createElement('div');
        fila.className = 'inv-row';
        fila.innerHTML = `
            <div class="inv-row-icon icon-guardado">
                <span class="material-symbols-outlined">${iconName}</span>
            </div>
            <div class="inv-row-info">
                <div class="inv-row-fecha">${doc.label ?? '—'}</div>
                ${metaTxt ? `<div class="inv-row-meta">${metaTxt}</div>` : ''}
            </div>
            <div class="inv-row-actions">${acciones}</div>
        `;

        fila.querySelectorAll('.btn-ver').forEach(btn => {
            btn.addEventListener('click', e => {
                e.stopPropagation();
                window.open(decodeURIComponent(btn.dataset.url), '_blank');
            });
        });
        fila.querySelectorAll('.btn-dl').forEach(btn => {
            btn.addEventListener('click', e => {
                e.stopPropagation();
                window.open(decodeURIComponent(btn.dataset.url) + '&dl=1', '_blank');
            });
        });
        return fila;
    }

    /* ── Inventarios: render ── */
    function renderInvRows(rows) {
        if (rows.length === 0) {
            invList.innerHTML = `<div class="empty-state">
                <span class="material-symbols-outlined">inventory_2</span>
                <p>No hay inventarios${anioActivo ? ' para ' + anioActivo : ''}.</p>
            </div>`;
            return;
        }
        invList.innerHTML = '';
        rows.forEach(inv => invList.appendChild(crearFilaInv(inv)));
    }

    function crearFilaInv(inv) {
        const esEditado = (inv.ESTADO ?? '').toLowerCase() === 'editado';
        const iconCls   = esEditado ? 'icon-editado' : 'icon-guardado';
        const pillCls   = esEditado ? 'pill-editado' : 'pill-guardado';
        const iconName  = esEditado ? 'edit_document' : 'description';

        const fechaFmt = inv.FECHA
            ? new Date(inv.FECHA + 'T12:00:00').toLocaleDateString('es-MX',
                { day: '2-digit', month: 'long', year: 'numeric' })
            : '—';

        const fechaEdicion = inv.UPDATED_AT || inv.CREATED_AT || null;
        const edicionFmt = fechaEdicion
            ? new Date(fechaEdicion).toLocaleDateString('es-MX',
                { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
            : null;

        const metaTxt = [
            inv.FIN_CAJA     != null ? `Inv. final: ${inv.FIN_CAJA} cajas` : null,
            inv.VENTA_LITROS != null ? `Venta: ${inv.VENTA_LITROS} L`      : null,
            edicionFmt       ? `Editado: ${edicionFmt}`                    : null,
        ].filter(Boolean).join('  ·  ');

        const tienePDF   = inv.PDF_RUTA && inv.PDF_RUTA.trim() !== '';
        const pdfEnDisco = tienePDF && inv.pdf_existe == 1;
        const pdfBorrado = tienePDF && inv.pdf_existe == 0;

        let acciones = '';
        if (pdfEnDisco) {
            acciones = `
                <button class="btn-pdf btn-ver" data-pdf="${encodeURIComponent(inv.PDF_RUTA)}" title="Ver PDF">
                    <span class="material-symbols-outlined">visibility</span> Ver
                </button>
                <button class="btn-pdf btn-dl" data-pdf="${encodeURIComponent(inv.PDF_RUTA)}" title="Descargar PDF">
                    <span class="material-symbols-outlined">download</span>
                </button>`;
        } else if (pdfBorrado) {
            acciones = `
                <span class="estado-pill" style="background:var(--md-sys-color-error-container);color:var(--md-sys-color-on-error-container);display:inline-flex;align-items:center;gap:4px;">
                    <span class="material-symbols-outlined" style="font-size:14px;">block</span>Sin archivo
                </span>
                <md-icon-button class="btn-regen" data-id="${encodeURIComponent(inv.ID)}" title="Regenerar PDF">
                    <md-icon>refresh</md-icon>
                </md-icon-button>`;
        } else {
            acciones = `
                <span class="estado-pill" style="background:var(--md-sys-color-surface-variant);color:var(--md-sys-color-on-surface-variant);display:inline-flex;align-items:center;gap:4px;">
                    <span class="material-symbols-outlined" style="font-size:14px;">block</span>Sin PDF
                </span>`;
        }

        const fila = document.createElement('div');
        fila.className = 'inv-row';
        fila.innerHTML = `
            <div class="inv-row-icon ${iconCls}">
                <span class="material-symbols-outlined">${iconName}</span>
            </div>
            <div class="inv-row-info">
                <div class="inv-row-fecha">${fechaFmt}</div>
                ${metaTxt ? `<div class="inv-row-meta">${metaTxt}</div>` : ''}
            </div>
            <span class="estado-pill ${pillCls}">${inv.ESTADO ?? 'guardado'}</span>
            <div class="inv-row-actions">${acciones}</div>
        `;

        fila.querySelectorAll('.btn-ver').forEach(btn => {
            btn.addEventListener('click', e => {
                e.stopPropagation();
                window.open(`ver_pdf.php?archivo=${btn.dataset.pdf}`, '_blank');
            });
        });
        fila.querySelectorAll('.btn-dl').forEach(btn => {
            btn.addEventListener('click', e => {
                e.stopPropagation();
                window.open(`ver_pdf.php?archivo=${btn.dataset.pdf}&dl=1`, '_blank');
            });
        });
        fila.querySelectorAll('.btn-regen').forEach(btn => {
            btn.addEventListener('click', async e => {
                e.stopPropagation();
                btn.disabled = true;
                try {
                    const res  = await fetch(`regenerar_pdf_inventario.php?id=${btn.dataset.id}`);
                    const data = await res.json();
                    if (data.status === 'success') {
                        inv.pdf_existe = 1;
                        inv.PDF_RUTA   = data.pdf_ruta;
                        fila.replaceWith(crearFilaInv(inv));
                        if (window.PWA) window.PWA.mostrarToast('PDF regenerado correctamente.', 'success');
                    } else {
                        throw new Error(data.mensaje || 'Error al regenerar');
                    }
                } catch(err) {
                    if (window.PWA) window.PWA.mostrarToast(err.message, 'error');
                    btn.disabled = false;
                }
            });
        });
        return fila;
    }

    /* ── Helpers ── */
    function mostrarSkeleton() {
        invList.innerHTML = `
            <div class="inv-row" style="pointer-events:none;">
                <div class="inv-row-icon skeleton" style="width:42px;height:42px;border-radius:10px;"></div>
                <div class="inv-row-info">
                    <div class="skeleton" style="height:12px;width:50%;border-radius:6px;margin-bottom:8px;"></div>
                    <div class="skeleton" style="height:10px;width:70%;border-radius:6px;"></div>
                </div>
            </div>`;
    }
    function mostrarError(msg) {
        invList.innerHTML = `<div class="empty-state">
            <span class="material-symbols-outlined">wifi_off</span>
            <p>${msg}</p></div>`;
    }
});
</script>
</body>
</html>