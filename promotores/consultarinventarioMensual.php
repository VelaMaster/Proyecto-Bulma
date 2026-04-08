<?php
session_start();
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
            <div style="position:relative;">
                <md-text-button id="btn-rep" onclick="abrirMenu('menu-rep')">
                    Reporte lecherías <md-icon slot="icon">arrow_drop_down</md-icon>
                </md-text-button>
                <md-menu id="menu-rep" anchor="btn-rep">
                    <md-menu-item href="#"><div slot="headline">Generar</div></md-menu-item>
                    <md-menu-item href="#"><div slot="headline">Consultar</div></md-menu-item>
                </md-menu>
            </div>
            <div style="position:relative;">
                <md-text-button id="btn-req" onclick="abrirMenu('menu-req')">
                    Requerimiento <md-icon slot="icon">arrow_drop_down</md-icon>
                </md-text-button>
                <md-menu id="menu-req" anchor="btn-req">
                    <md-menu-item href="#"><div slot="headline">Generar</div></md-menu-item>
                    <md-menu-item href="#"><div slot="headline">Consultar</div></md-menu-item>
                    <md-menu-item href="#">
                        <div slot="headline">Enviar reportes a supervisor</div>
                        <md-icon slot="start">send</md-icon>
                    </md-menu-item>
                </md-menu>
            </div>
        </div>
        <md-filled-tonal-button href="../cerrar_sesion.php" style="margin-left:8px;">Salir</md-filled-tonal-button>
    </div>
</header>

<aside class="md3-drawer" id="mobile-drawer">

        <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 16px 8px 24px;">
            <span style="font-size: 1.25rem; font-weight: 500; color: var(--md-sys-color-on-surface);">Menú</span>
            <md-icon-button onclick="toggleDrawer()">
                <md-icon>close</md-icon>
            </md-icon-button>
        </div>

        <div style="overflow-y: auto; flex-grow: 1;">
            <md-list style="background: transparent;">
                
                <md-list-item href="inicio.php" type="button">
                    <div slot="headline">Inicio</div>
                    <md-icon slot="start">home</md-icon>
                </md-list-item>

                <md-divider style="margin: 8px 0;"></md-divider>
                <div class="drawer-section-title">Inventario mensual</div>
                <md-list-item href="generarinventarioMensual.php" type="button">
                    <div slot="headline">Generar</div>
                    <md-icon slot="start">add_box</md-icon>
                </md-list-item>
                
                <md-list-item href="editarinventarioMensual.php" type="button">
                    <div slot="headline">Editar</div>
                    <md-icon slot="start">edit</md-icon>
                </md-list-item>
                
                <md-list-item href="consultarinventarioMensual.php" type="button">
                    <div slot="headline">Consultar</div>
                    <md-icon slot="start">search</md-icon>
                </md-list-item>

                <md-divider style="margin: 8px 0;"></md-divider>

                <div class="drawer-section-title">Reporte lecherías</div>
                <md-list-item href="#" type="button">
                    <div slot="headline">Generar</div>
                    <md-icon slot="start">receipt_long</md-icon>
                </md-list-item>
                <md-list-item href="#" type="button">
                    <div slot="headline">Consultar</div>
                    <md-icon slot="start">find_in_page</md-icon>
                </md-list-item>

                <md-divider style="margin: 8px 0;"></md-divider>

                <div class="drawer-section-title">Requerimiento</div>
                <md-list-item href="#" type="button">
                    <div slot="headline">Generar</div>
                    <md-icon slot="start">inventory</md-icon>
                </md-list-item>
                <md-list-item href="#" type="button">
                    <div slot="headline">Consultar</div>
                    <md-icon slot="start">manage_search</md-icon>
                </md-list-item>
                <md-list-item href="#" type="button">
                    <div slot="headline">Enviar a supervisor</div>
                    <md-icon slot="start">send</md-icon>
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
/* ── Menú y drawer ── */
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
</body>
</html>