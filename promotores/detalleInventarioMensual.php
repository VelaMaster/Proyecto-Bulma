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
    <link rel="stylesheet" href="../estilos/detalleinventarioMensual.css">
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