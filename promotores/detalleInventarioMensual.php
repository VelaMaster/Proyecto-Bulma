<?php
session_start();
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
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../main_md3.css">
    <link rel="stylesheet" href="../estilos/generarInventarioMensual.css">
    <link rel="stylesheet" href="../estilos/consultarInventarioMensual.css">
    <script type="importmap">{"imports":{"@material/web/":"https://esm.run/@material/web/"}}</script>
    <script type="module">import '@material/web/all.js';</script>
    <style>
        /* ── Cabecera de detalle ── */
        .detalle-hero {
            background: var(--md-sys-color-tertiary-container);
            border-radius: 16px;
            padding: 22px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }
        .detalle-hero-icon {
            width: 52px; height: 52px;
            border-radius: 14px;
            background: var(--md-sys-color-tertiary);
            color: var(--md-sys-color-on-tertiary);
            display: flex; align-items: center; justify-content: center;
            font-size: 26px; flex-shrink: 0;
        }
        .detalle-hero-info { flex: 1; min-width: 0; }
        .detalle-hero-num  {
            font-size: .72rem; font-weight: 700; letter-spacing: .8px;
            text-transform: uppercase;
            color: var(--md-sys-color-on-tertiary-container); opacity: .7;
        }
        .detalle-hero-nombre {
            font-size: 1.25rem; font-weight: 600;
            color: var(--md-sys-color-on-tertiary-container);
            margin: 2px 0 0;
        }

        /* ── Filtro de año ── */
        .anio-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .anio-tab {
            background: var(--md-sys-color-surface-container);
            border: 1px solid var(--md-sys-color-outline-variant);
            color: var(--md-sys-color-on-surface-variant);
            border-radius: 20px;
            padding: 6px 16px;
            font-size: .8125rem;
            font-weight: 500;
            cursor: pointer;
            font-family: 'Roboto', sans-serif;
            transition: background .15s, color .15s, border-color .15s;
        }
        .anio-tab:hover {
            background: color-mix(in srgb, var(--md-sys-color-tertiary) 10%, transparent);
            border-color: var(--md-sys-color-tertiary);
            color: var(--md-sys-color-tertiary);
        }
        .anio-tab.active {
            background: var(--md-sys-color-tertiary);
            color: var(--md-sys-color-on-tertiary);
            border-color: transparent;
        }

        /* ── Lista de inventarios ── */
        .inv-list { display: flex; flex-direction: column; gap: 10px; }

        .inv-row {
            background: var(--md-sys-color-surface-container);
            border: 1px solid var(--md-sys-color-outline-variant);
            border-radius: 12px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: border-color .15s, background .15s;
        }
        .inv-row:hover {
            border-color: var(--md-sys-color-tertiary);
            background: color-mix(in srgb, var(--md-sys-color-tertiary) 5%, var(--md-sys-color-surface-container));
        }

        .inv-row-icon {
            width: 42px; height: 42px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
        }
        .icon-guardado {
            background: color-mix(in srgb, #4caf50 14%, transparent);
            color: #81c784;
        }
        .icon-editado {
            background: color-mix(in srgb, var(--md-sys-color-tertiary) 16%, transparent);
            color: var(--md-sys-color-tertiary);
        }

        .inv-row-info { flex: 1; min-width: 0; }
        .inv-row-fecha {
            font-weight: 600; font-size: .925rem;
            color: var(--md-sys-color-on-surface);
            text-transform: capitalize;
        }
        .inv-row-meta {
            font-size: .775rem;
            color: var(--md-sys-color-outline);
            margin-top: 3px;
        }

        .estado-pill {
            font-size: .68rem; font-weight: 600;
            padding: 3px 10px; border-radius: 20px;
            white-space: nowrap; text-transform: uppercase; letter-spacing: .3px;
            flex-shrink: 0;
        }
        .pill-guardado {
            background: color-mix(in srgb, #4caf50 14%, transparent);
            color: #81c784;
        }
        .pill-editado {
            background: color-mix(in srgb, var(--md-sys-color-tertiary) 16%, transparent);
            color: var(--md-sys-color-tertiary);
        }

        /* botones de acción PDF */
        .inv-row-actions { display: flex; gap: 6px; flex-shrink: 0; }
        .btn-pdf {
            display: inline-flex; align-items: center; gap: 5px;
            background: none;
            border: 1px solid var(--md-sys-color-outline-variant);
            color: var(--md-sys-color-on-surface-variant);
            border-radius: 20px;
            padding: 5px 12px;
            font-size: .78rem; font-weight: 500;
            font-family: 'Roboto', sans-serif;
            cursor: pointer;
            transition: background .15s, color .15s, border-color .15s;
            white-space: nowrap;
        }
        .btn-pdf .material-symbols-outlined { font-size: 15px; }
        .btn-pdf:hover {
            background: color-mix(in srgb, var(--md-sys-color-tertiary) 12%, transparent);
            color: var(--md-sys-color-tertiary);
            border-color: var(--md-sys-color-tertiary);
        }
        .btn-pdf.btn-dl:hover {
            background: color-mix(in srgb, #4caf50 12%, transparent);
            color: #81c784;
            border-color: #81c784;
        }

        @media (max-width: 560px) {
            .inv-row { flex-wrap: wrap; }
            .inv-row-actions { width: 100%; justify-content: flex-end; }
            .estado-pill { display: none; }
        }

        /* ── botón volver ── */
        .btn-volver {
            display: inline-flex; align-items: center; gap: 6px;
            background: none; border: none;
            color: var(--md-sys-color-on-surface-variant);
            font-size: .875rem; font-weight: 500;
            cursor: pointer; font-family: 'Roboto', sans-serif;
            padding: 0; margin-bottom: 20px;
            transition: color .15s;
        }
        .btn-volver:hover { color: var(--md-sys-color-tertiary); }
        .btn-volver .material-symbols-outlined { font-size: 18px; }

        /* ── empty state ── */
        .empty-state {
            text-align: center; padding: 52px 24px;
            color: var(--md-sys-color-outline);
        }
        .empty-state .material-symbols-outlined {
            font-size: 56px; opacity: .3;
            display: block; margin: 0 auto 14px;
        }
    </style>
</head>
<body>

<!-- ══ TOP BAR ══════════════════════════════════════════════════ -->
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
        </div>
        <md-filled-tonal-button href="../cerrar_sesion.php" style="margin-left:8px;">Salir</md-filled-tonal-button>
    </div>
</header>

<div class="md3-drawer-scrim" id="drawer-scrim" onclick="toggleDrawer()"></div>
<aside class="md3-drawer" id="mobile-drawer">
    <div style="padding-top:24px;"></div>
    <md-list style="background:transparent;">
        <div class="drawer-section-title">Inventario mensual</div>
        <md-list-item href="generarinventarioMensual.php"><div slot="headline">Generar</div></md-list-item>
        <md-list-item href="editarinventarioMensual.php"><div slot="headline">Editar</div></md-list-item>
        <md-list-item href="consultarinventarioMensual.php"><div slot="headline">Consultar</div></md-list-item>
        <md-divider style="margin:8px 0;"></md-divider>
    </md-list>
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

    <!-- Sección de inventarios -->
    <div class="form-section">
        <div class="section-header">
            <div class="section-badge">
                <span class="material-symbols-outlined" style="font-size:17px;">description</span>
            </div>
            <h2 class="section-title">Inventarios mensuales</h2>
        </div>
    </div>

    <!-- Tabs de año -->
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
    let todosInv   = [];
    let anioActivo = '';

    /* ── cargar inventarios ── */
    cargarInventarios('');

    function cargarInventarios(anio) {
        anioActivo = anio;
        invList.innerHTML = `
            <div class="inv-row" style="pointer-events:none;">
                <div class="inv-row-icon skeleton" style="width:42px;height:42px;border-radius:10px;"></div>
                <div class="inv-row-info">
                    <div class="skeleton" style="height:12px;width:50%;border-radius:6px;margin-bottom:8px;"></div>
                    <div class="skeleton" style="height:10px;width:70%;border-radius:6px;"></div>
                </div>
            </div>`;

        const params = new URLSearchParams({ clave: CLAVE_LECHERIA });
        if (anio) params.append('anio', anio);

        fetch('listar_inventarios_lecheria.php?' + params.toString())
            .then(r => r.json())
            .then(rows => {
                if (!Array.isArray(rows)) {
                    invList.innerHTML = `<div class="empty-state">
                        <span class="material-symbols-outlined">error</span>
                        <p>Error al cargar inventarios.</p></div>`;
                    return;
                }

                todosInv = rows;

                // Construir tabs de año la primera vez (sin filtro activo)
                if (anio === '') construirAnioTabs(rows);

                renderInv(rows);
            })
            .catch(() => {
                invList.innerHTML = `<div class="empty-state">
                    <span class="material-symbols-outlined">wifi_off</span>
                    <p>Error de conexión.</p></div>`;
            });
    }

    /* ── construir tabs de año ── */
    function construirAnioTabs(rows) {
        const anios = [...new Set(
            rows
                .filter(r => r.FECHA)
                .map(r => r.FECHA.substring(0, 4))
        )].sort((a, b) => b - a);

        // quitar tabs anteriores salvo "Todos"
        anioTabs.querySelectorAll('[data-anio]:not([data-anio=""])').forEach(t => t.remove());

        anios.forEach(a => {
            const btn = document.createElement('button');
            btn.className  = 'anio-tab';
            btn.dataset.anio = a;
            btn.textContent  = a;
            btn.addEventListener('click', () => activarTab(btn, a));
            anioTabs.appendChild(btn);
        });
    }

    /* ── activar tab ── */
    anioTabs.addEventListener('click', e => {
        const btn = e.target.closest('.anio-tab');
        if (!btn) return;
        activarTab(btn, btn.dataset.anio);
    });

    function activarTab(btn, anio) {
        anioTabs.querySelectorAll('.anio-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        cargarInventarios(anio);
    }

    /* ── render de filas ── */
    function renderInv(rows) {
        if (rows.length === 0) {
            invList.innerHTML = `<div class="empty-state">
                <span class="material-symbols-outlined">inventory_2</span>
                <p>No hay inventarios${anioActivo ? ' para ' + anioActivo : ''}.</p>
            </div>`;
            return;
        }

        invList.innerHTML = '';
        rows.forEach(inv => {
            invList.appendChild(crearFila(inv));
        });
    }

    function crearFila(inv) {
        const esEditado = (inv.ESTADO ?? '').toLowerCase() === 'editado';
        const iconCls   = esEditado ? 'icon-editado'  : 'icon-guardado';
        const pillCls   = esEditado ? 'pill-editado'  : 'pill-guardado';
        const iconName  = esEditado ? 'edit_document' : 'description';

        const fechaFmt = inv.FECHA
            ? new Date(inv.FECHA + 'T12:00:00').toLocaleDateString('es-MX', {
                day: '2-digit', month: 'long', year: 'numeric'
              })
            : '—';

        const metaTxt = [
            inv.FIN_CAJA    != null ? `Inv. final: ${inv.FIN_CAJA} cajas` : null,
            inv.VENTA_LITROS!= null ? `Venta: ${inv.VENTA_LITROS} L`      : null,
        ].filter(Boolean).join('  ·  ');

        // Botones PDF: solo si hay ruta guardada
        const tienePDF = inv.PDF_RUTA && inv.PDF_RUTA.trim() !== '';
        const acciones = tienePDF
            ? `<button class="btn-pdf btn-ver"  data-pdf="${encodeURIComponent(inv.PDF_RUTA)}" title="Ver PDF">
                   <span class="material-symbols-outlined">visibility</span> Ver
               </button>
               <button class="btn-pdf btn-dl"   data-pdf="${encodeURIComponent(inv.PDF_RUTA)}" title="Descargar PDF">
                   <span class="material-symbols-outlined">download</span>
               </button>`
            : `<span style="font-size:.75rem;color:var(--md-sys-color-outline);">Sin PDF</span>`;

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

        // Eventos botones PDF
        fila.querySelectorAll('.btn-ver').forEach(btn => {
            btn.addEventListener('click', e => {
                e.stopPropagation();
                const archivo = decodeURIComponent(btn.dataset.pdf);
                window.open(`ver_pdf.php?archivo=${encodeURIComponent(archivo)}`, '_blank');
            });
        });
        fila.querySelectorAll('.btn-dl').forEach(btn => {
            btn.addEventListener('click', e => {
                e.stopPropagation();
                const archivo = decodeURIComponent(btn.dataset.pdf);
                window.open(`ver_pdf.php?archivo=${encodeURIComponent(archivo)}&dl=1`, '_blank');
            });
        });

        return fila;
    }
});
</script>
</body>
</html>