<?php
require_once __DIR__ . '/../includes/session_guard.php';
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'distribucion') {
    header("Location: ../iniciosesionDistribucion.php");
    exit();
}
$nombre_usuario = $_SESSION['nombre'] ?? $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distribución - Requerimiento Global</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../main_md3.css">
    <link rel="stylesheet" href="../estilos/iniciocards.css">
    <style>
        .filtros-card {
            display: flex; flex-wrap: wrap; align-items: center; gap: 14px;
            padding: 16px 20px; margin-bottom: 16px;
        }
        .precio-pills { display: inline-flex; gap: 8px; }
        .precio-pill-btn {
            cursor: pointer; padding: 6px 14px; border-radius: 999px;
            border: 1px solid var(--md-sys-color-outline-variant);
            background: var(--md-sys-color-surface-container);
            color: var(--md-sys-color-on-surface);
            font-size: 0.85rem; font-weight: 500;
        }
        .precio-pill-btn.active {
            background: var(--md-sys-color-primary);
            color: var(--md-sys-color-on-primary);
            border-color: transparent;
        }
        /* Resumen */
        .resumen-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px; margin-top: 14px;
        }
        .resumen-cell {
            padding: 14px 16px; border-radius: 12px;
            background: var(--md-sys-color-surface-container);
            border: 1px solid var(--md-sys-color-outline-variant);
        }
        .resumen-cell .label {
            font-size: 0.78rem; color: var(--md-sys-color-on-surface-variant);
            text-transform: uppercase; letter-spacing: .5px;
        }
        .resumen-cell .value {
            font-size: 1.5rem; font-weight: 600;
            color: var(--md-sys-color-primary); margin-top: 4px;
        }
        /* Supervisor sections */
        .supervisores-lista { display: flex; flex-direction: column; gap: 18px; }
        .supervisor-block {
            background: var(--md-sys-color-surface-container);
            border: 1px solid var(--md-sys-color-outline-variant);
            border-radius: 16px; overflow: hidden;
        }
        .supervisor-header {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px;
            background: var(--md-sys-color-secondary-container);
            color: var(--md-sys-color-on-secondary-container);
        }
        .supervisor-header .sup-nombre {
            font-weight: 600; font-size: 1rem; flex: 1;
        }
        .supervisor-header .sup-meta {
            font-size: 0.8rem; opacity: .85; font-weight: 500;
        }
        .sup-progress {
            height: 4px;
            background: var(--md-sys-color-surface-container-highest);
        }
        .sup-progress-bar {
            height: 4px;
            background: var(--md-sys-color-primary);
            transition: width .4s;
        }
        /* Almacén grid dentro del supervisor */
        .almacenes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 12px; padding: 14px;
        }
        .almacen-card {
            background: var(--md-sys-color-surface-container-low);
            border: 1px solid var(--md-sys-color-outline-variant);
            border-radius: 12px; overflow: hidden;
        }
        .almacen-titulo {
            display: flex; align-items: center; gap: 8px;
            padding: 8px 12px;
            background: color-mix(in srgb, var(--md-sys-color-tertiary) 18%, transparent);
            color: var(--md-sys-color-on-surface);
            font-weight: 600; font-size: 0.85rem;
        }
        .almacen-titulo .alm-meta {
            margin-left: auto; font-weight: 500; opacity: .75; font-size: 0.78rem;
        }
        .reporte-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; min-width:340px; }
        .reporte-table th, .reporte-table td {
            padding: 5px 8px;
            border-bottom: 1px solid var(--md-sys-color-outline-variant);
            text-align: left; white-space: nowrap;
        }
        .almacen-card .tabla-scroll{overflow-x:auto;}
        .reporte-table th {
            font-weight: 600; color: var(--md-sys-color-on-surface-variant);
            background: var(--md-sys-color-surface-container);
            font-size: 0.72rem; text-transform: uppercase; letter-spacing: .4px;
        }
        .reporte-table th:last-child, .reporte-table td:last-child { text-align: right; }
        .subtotal-row td {
            background: var(--md-sys-color-surface-container-high);
            font-weight: 700;
        }
        .falta-pill {
            display: inline-block; padding: 2px 10px; border-radius: 999px;
            background: color-mix(in srgb, var(--md-sys-color-error) 18%, transparent);
            color: var(--md-sys-color-error); font-weight: 600; font-size: 0.72rem;
        }
        .estado-pill{
            display:inline-flex; align-items:center; gap:3px;
            padding:1px 8px; border-radius:999px;
            font-weight:600; font-size:0.65rem; text-transform:uppercase; letter-spacing:.3px;
        }
        .estado-pill md-icon{font-size:12px;width:12px;height:12px;}
        .estado-ver{background:color-mix(in srgb,#2e7d32 22%,transparent); color:#7fd996;}
        .estado-cap{background:color-mix(in srgb,var(--md-sys-color-primary) 22%,transparent); color:var(--md-sys-color-primary);}
        .estado-est{background:color-mix(in srgb,#ffc107 22%,transparent); color:#ffd966;}
        .req-val.estimado{opacity:.85; font-style:italic;}
        tr.estimada td{background:color-mix(in srgb,#ffc107 6%,transparent);}
        .legend-dist{
            display:flex; flex-wrap:wrap; gap:12px; align-items:center;
            padding:8px 12px; margin:10px 0; border-radius:10px;
            background:var(--md-sys-color-surface-container);
            border:1px solid var(--md-sys-color-outline-variant);
            font-size:0.72rem; color:var(--md-sys-color-on-surface-variant);
        }
        .dm-tag {
            display: inline-block; padding: 1px 8px; border-radius: 6px;
            background: color-mix(in srgb, var(--md-sys-color-tertiary) 22%, transparent);
            color: var(--md-sys-color-tertiary); font-weight: 600; font-size: 0.72rem;
        }
        .grand-total {
            display: flex; align-items: center; justify-content: flex-end; gap: 14px;
            padding: 14px 18px; margin-top: 8px; border-radius: 14px;
            background: color-mix(in srgb, var(--md-sys-color-primary) 14%, transparent);
        }
        .grand-total strong { color: var(--md-sys-color-primary); font-size: 1.15rem; }
        /* Skeleton */
        .skel-bar {
            height: 14px; border-radius: 6px;
            background: var(--md-sys-color-surface-container-highest);
            animation: pulseSkel 1.4s infinite ease-in-out;
        }
        @keyframes pulseSkel { 50% { opacity: .5; } }
    </style>
    <script type="importmap">{ "imports": { "@material/web/": "https://esm.run/@material/web/" } }</script>
    <script type="module"> import '@material/web/all.js'; </script>
</head>

<body>
    <header class="md3-top-app-bar">
        <div class="app-bar-start">
            <md-icon-button onclick="toggleDrawer()"><md-icon>menu</md-icon></md-icon-button>
            <div class="app-brand"><span>Leche para el Bienestar — Distribución</span></div>
        </div>
        <div class="app-bar-end">
            <div class="desktop-nav">
                <md-text-button onclick="document.getElementById('seccion-resultado').scrollIntoView({behavior:'smooth'})">
                    <md-icon slot="icon">description</md-icon>
                    Resultado OPE
                </md-text-button>

                <md-text-button onclick="document.getElementById('seccion-guia').scrollIntoView({behavior:'smooth'})">
                    <md-icon slot="icon">local_shipping</md-icon>
                    Guía de Distribución
                </md-text-button>

                <md-text-button onclick="document.getElementById('seccion-minuta').scrollIntoView({behavior:'smooth'})">
                    <md-icon slot="icon">edit_note</md-icon>
                    Minuta
                </md-text-button>
            </div>

            <md-filled-tonal-button href="../cerrar_sesion.php" style="margin-left:16px;">
                <md-icon slot="icon">logout</md-icon> Salir
            </md-filled-tonal-button>
        </div>
    </header>

    <div id="drawer-scrim" class="md3-drawer-scrim" onclick="toggleDrawer()"></div>
    <aside class="md3-drawer" id="mobile-drawer">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:16px 16px 8px 24px;">
            <span style="font-size:1.25rem; font-weight:500;">Menú Distribución</span>
            <md-icon-button onclick="toggleDrawer()"><md-icon>close</md-icon></md-icon-button>
        </div>
        <md-list style="background:transparent;">
            <md-list-item type="button" onclick="document.getElementById('seccion-resultado').scrollIntoView({behavior:'smooth'}); toggleDrawer();">
                <div slot="headline">Resultado OPE</div>
                <md-icon slot="start">description</md-icon>
            </md-list-item>
            <md-list-item type="button" onclick="document.getElementById('seccion-guia').scrollIntoView({behavior:'smooth'}); toggleDrawer();">
                <div slot="headline">Guía de Distribución</div>
                <md-icon slot="start">local_shipping</md-icon>
            </md-list-item>
            <md-list-item type="button" onclick="document.getElementById('seccion-minuta').scrollIntoView({behavior:'smooth'}); toggleDrawer();">
                <div slot="headline">Minuta</div>
                <md-icon slot="start">edit_note</md-icon>
            </md-list-item>
        </md-list>
    </aside>

    <main class="panel-content">
        <!-- Hero -->
        <div class="md3-card md3-hero-card" style="background:var(--md-sys-color-tertiary-container); color:var(--md-sys-color-on-tertiary-container);">
            <div style="display:flex; align-items:center; gap:14px;">
                <div style="background:var(--md-sys-color-tertiary); border-radius:16px; padding:10px; display:flex;">
                    <md-icon style="color:var(--md-sys-color-on-tertiary); font-size:32px; width:32px; height:32px;">local_shipping</md-icon>
                </div>
                <div>
                    <h2 style="margin:0; font-size:1.6rem; font-weight:500;">Centro de Distribución</h2>
                    <p style="margin:4px 0 0; font-size:0.9rem; opacity:.85;">
                        Bienvenido, <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong>.
                        Consolidado de requerimientos de todos los supervisores.
                    </p>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="md3-card filtros-card">
            <md-outlined-select label="Mes" id="selMes" style="min-width:140px;">
                <?php
                $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                          'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                foreach ($meses as $i => $m) {
                    $n = $i + 1;
                    echo "<md-select-option value=\"$n\"><div slot=\"headline\">$m</div></md-select-option>\n";
                }
                ?>
            </md-outlined-select>

            <md-outlined-text-field label="Año" id="inputAnio" type="number"
                value="<?= date('Y') ?>" style="max-width:110px;"></md-outlined-text-field>

            <div class="precio-pills">
                <button type="button" class="precio-pill-btn active" data-precio="6.50">$6.50 / litro</button>
                <button type="button" class="precio-pill-btn"        data-precio="4.50">$4.50 / litro</button>
            </div>
        </div>

        <!-- ── OPE Diconsa ───────────────────────────────────────────── -->
        <div id="seccion-resultado" class="md3-card" style="margin-bottom:16px; padding:16px 20px; background:color-mix(in srgb, var(--md-sys-color-primary-container) 55%, transparent); scroll-margin-top:80px;">
            <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                <md-icon style="color:var(--md-sys-color-primary); font-size:28px; width:28px; height:28px;">description</md-icon>
                <div style="flex:1; min-width:200px;">
                    <h3 style="margin:0; font-size:1.05rem; font-weight:500;">OPE Diconsa (mensual)</h3>
                    <p id="opeSubtitulo" style="margin:4px 0 0; font-size:0.82rem; color:var(--md-sys-color-on-surface-variant);">
                        Descarga el control de la operación con los datos autorizados por supervisores.
                    </p>
                </div>
                <md-outlined-button id="btnOpeAll">
                    <md-icon slot="icon">download</md-icon> OPE completo
                </md-outlined-button>
                <md-outlined-button id="btnOpe450">
                    <md-icon slot="icon">download</md-icon> Solo $4.50
                </md-outlined-button>
                <md-outlined-button id="btnOpe650">
                    <md-icon slot="icon">download</md-icon> Solo $6.50
                </md-outlined-button>
                <md-text-button href="comparar_lecherias.php" target="_blank">
                    <md-icon slot="icon">fact_check</md-icon> Validar lecherías BDD↔OPE
                </md-text-button>
            </div>
            <div id="opePendientes" style="display:none; margin-top:12px; padding:10px 14px; border-radius:10px;
                                           background:color-mix(in srgb,var(--md-sys-color-error-container) 70%,transparent);
                                           color:var(--md-sys-color-on-error-container); font-size:0.85rem;">
                <strong>Supervisores pendientes de autorizar:</strong>
                <span id="opePendientesLista"></span>
                <div style="margin-top:8px;">
                    <md-text-button id="btnOpeForce" style="--md-text-button-label-text-color:var(--md-sys-color-on-error-container);">
                        Descargar de todas formas (sin esperar)
                    </md-text-button>
                </div>
            </div>
        </div>

        <!-- ── Requerimiento de Leche (xlsx por precio) ─────────────────── -->
        <div class="md3-card" style="margin-bottom:16px; padding:16px 20px; background:color-mix(in srgb, var(--md-sys-color-tertiary-container) 55%, transparent);">
            <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                <md-icon style="color:var(--md-sys-color-tertiary); font-size:28px; width:28px; height:28px;">request_page</md-icon>
                <div style="flex:1; min-width:200px;">
                    <h3 style="margin:0; font-size:1.05rem; font-weight:500;">Requerimiento de leche (formato Excel)</h3>
                    <p style="margin:4px 0 0; font-size:0.82rem; color:var(--md-sys-color-on-surface-variant);">
                        Hoja "POR ALMACEN" + "TOTAL" por sucursal (HUAJUAPAN / ISTMO-COSTA / V. CENTRAL).
                    </p>
                </div>
                <md-outlined-button id="btnReq650">
                    <md-icon slot="icon">download</md-icon> REQ $6.50
                </md-outlined-button>
                <md-outlined-button id="btnReq450">
                    <md-icon slot="icon">download</md-icon> REQ $4.50
                </md-outlined-button>
            </div>
        </div>

        <!-- ── Minuta mensual (xlsm con macros) ─────────────────────────── -->
        <div id="seccion-minuta" class="md3-card" style="margin-bottom:16px; padding:16px 20px; background:color-mix(in srgb, var(--md-sys-color-secondary-container) 55%, transparent); scroll-margin-top:80px;">
            <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                <md-icon style="color:var(--md-sys-color-secondary); font-size:28px; width:28px; height:28px;">edit_note</md-icon>
                <div style="flex:1; min-width:200px;">
                    <h3 style="margin:0; font-size:1.05rem; font-weight:500;">Minuta de conciliación mensual</h3>
                    <p style="margin:4px 0 0; font-size:0.82rem; color:var(--md-sys-color-on-surface-variant);">
                        Plantilla precargada con embarques, total de puntos de venta y mes del periodo.
                    </p>
                </div>
                <md-outlined-button id="btnMinuta">
                    <md-icon slot="icon">download</md-icon> Descargar Minuta
                </md-outlined-button>
            </div>
        </div>

        <!-- Barra de exportación -->
        <div id="seccion-guia" style="scroll-margin-top:80px;"></div>
        <div class="md3-card filtros-card" id="exportBar" style="display:none; background:color-mix(in srgb,var(--md-sys-color-secondary-container) 40%,transparent);">
            <md-icon style="color:var(--md-sys-color-secondary);">filter_list</md-icon>
            <span style="font-size:0.85rem;font-weight:500;color:var(--md-sys-color-on-surface-variant);">Filtrar exportación:</span>

            <select id="exportSupSelect" class="md3-input" style="margin:0;cursor:pointer;min-width:180px;">
                <option value="0">Todos los supervisores</option>
            </select>

            <select id="exportAlmSelect" class="md3-input" style="margin:0;cursor:pointer;min-width:160px;">
                <option value="">Todos los almacenes</option>
            </select>

            <span style="flex-grow:1;"></span>

            <md-outlined-button id="btnExportExcel">
                <md-icon slot="icon">table_view</md-icon> Excel (.csv)
            </md-outlined-button>
            <md-filled-button id="btnExportPDF">
                <md-icon slot="icon">picture_as_pdf</md-icon> PDF
            </md-filled-button>
        </div>

        <!-- Resumen -->
        <div class="md3-card" id="resumenCard" style="display:none; margin-bottom:16px;">
            <h3 style="margin:0 0 12px; font-size:1rem; font-weight:500;">
                <md-icon style="vertical-align:middle; margin-right:6px; color:var(--md-sys-color-primary);">insights</md-icon>
                Resumen global
            </h3>
            <div class="resumen-grid">
                <div class="resumen-cell">
                    <div class="label">Supervisores</div>
                    <div class="value" id="resSupervisores">0</div>
                </div>
                <div class="resumen-cell">
                    <div class="label">Promotores</div>
                    <div class="value" id="resPromotores">0</div>
                </div>
                <div class="resumen-cell">
                    <div class="label">Lecherías $4.50</div>
                    <div class="value" id="resLech450">0</div>
                </div>
                <div class="resumen-cell">
                    <div class="label">Lecherías $6.50</div>
                    <div class="value" id="resLech650">0</div>
                </div>
                <div class="resumen-cell">
                    <div class="label">Capturadas</div>
                    <div class="value" id="resCapt">0</div>
                </div>
            </div>
        </div>

        <!-- Contenedor principal -->
        <div id="contenedorTabla">
            <div class="md3-card" style="text-align:center; padding:24px; color:var(--md-sys-color-on-surface-variant);">
                Selecciona mes y año para ver el consolidado.
            </div>
        </div>

        <div class="grand-total" id="grandTotal" style="display:none;">
            <md-icon style="color:var(--md-sys-color-primary);">summarize</md-icon>
            <span>Total general:</span>
            <strong id="totalGeneralVal">0</strong>
            <span style="opacity:.75;" id="totalLechVal">(0 lecherías)</span>
        </div>
    </main>

    <script src="../js/temas_md3.js"></script>
    <script>
        const selMes        = document.getElementById('selMes');
        const inputAnio     = document.getElementById('inputAnio');
        const contenedor    = document.getElementById('contenedorTabla');
        const grandTotal    = document.getElementById('grandTotal');
        const totalGeneralEl= document.getElementById('totalGeneralVal');
        const totalLechEl   = document.getElementById('totalLechVal');
        const pills         = document.querySelectorAll('.precio-pill-btn');

        let precioActivo = '6.50';

        selMes.value = String(new Date().getMonth() + 1);

        pills.forEach(p => p.addEventListener('click', () => {
            pills.forEach(x => x.classList.remove('active'));
            p.classList.add('active');
            precioActivo = p.dataset.precio;
            cargar();
        }));
        selMes.addEventListener('change', cargar);
        inputAnio.addEventListener('change', cargar);

        function fmtNum(n) { return Number(n || 0).toLocaleString('es-MX'); }

        function skeleton() {
            const block = Array(3).fill(0).map(() => `
                <div class="supervisor-block">
                    <div class="supervisor-header">
                        <div class="skel-bar" style="flex:1; max-width:200px; height:16px;"></div>
                    </div>
                    <div style="padding:14px;">
                        ${Array(2).fill('<div class="skel-bar" style="margin-bottom:10px;"></div>').join('')}
                    </div>
                </div>`).join('');
            contenedor.innerHTML = `<div class="supervisores-lista">${block}</div>`;
        }

        function estadoPillDist(l) {
            if (l.estado === 'verificado') return `<span class="estado-pill estado-ver"><md-icon>verified</md-icon>Verif</span>`;
            if (l.estado === 'capturado')  return `<span class="estado-pill estado-cap"><md-icon>edit_note</md-icon>Capt</span>`;
            if (l.estado === 'estimado')   return `<span class="estado-pill estado-est"><md-icon>auto_graph</md-icon>Est</span>`;
            return '';
        }
        function reqCellDist(l) {
            if (!l.capturado) return `<span class="falta-pill">FALTA</span>`;
            const cls = l.es_estimado ? 'req-val estimado' : 'req-val';
            const suf = l.es_estimado ? ' *' : '';
            return `<span class="${cls}">${fmtNum(l.requerimiento)}${suf}</span>`;
        }

        function pintarAlmacen(alm) {
            const filas = alm.lecherias.map(l => {
                const tiendaCell = l.num_tienda === 'DM'
                    ? `<span class="dm-tag">DM</span>`
                    : (l.num_tienda || '');
                const trCls = l.capturado ? (l.es_estimado ? 'estimada' : '') : '';
                return `<tr class="${trCls}">
                    <td>${l.punto_venta}</td>
                    <td>${tiendaCell}</td>
                    <td>${estadoPillDist(l)}</td>
                    <td>${reqCellDist(l)}</td>
                </tr>`;
            }).join('');

            const verCount = alm.verificadas || 0;
            const metaTxt = `${verCount}<md-icon style="font-size:12px;width:12px;height:12px;vertical-align:middle;">verified</md-icon> / ${alm.capturadas}/${alm.total}`;

            return `
                <div class="almacen-card">
                    <div class="almacen-titulo">
                        <md-icon style="font-size:18px;">warehouse</md-icon>
                        <span>ALMACÉN ${alm.almacen}</span>
                        <span class="alm-meta">${metaTxt}</span>
                    </div>
                    <div class="tabla-scroll">
                    <table class="reporte-table">
                        <thead>
                            <tr><th>Punto de Venta</th><th>Tienda</th><th>Estado</th><th>Req.</th></tr>
                        </thead>
                        <tbody>
                            ${filas}
                            <tr class="subtotal-row">
                                <td colspan="3" style="text-align:right;">SUBTOTAL =</td>
                                <td>${fmtNum(alm.subtotal)}</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>`;
        }

        function pintarSupervisor(sup) {
            const pct = sup.total_sup > 0
                ? Math.round((sup.capturadas_sup / sup.total_sup) * 100)
                : 0;
            const almHTML = sup.almacenes.map(pintarAlmacen).join('');

            return `
                <div class="supervisor-block">
                    <div class="supervisor-header">
                        <md-icon>manage_accounts</md-icon>
                        <span class="sup-nombre">${sup.nombre}</span>
                        <span class="sup-meta">${fmtNum(sup.subtotal_supervisor)} cajas &nbsp;·&nbsp; ${pct}% capturado</span>
                    </div>
                    <div class="sup-progress">
                        <div class="sup-progress-bar" style="width:${pct}%;"></div>
                    </div>
                    <div class="almacenes-grid">${almHTML}</div>
                </div>`;
        }

        function pintarResumen(r) {
            if (!r) { document.getElementById('resumenCard').style.display = 'none'; return; }
            document.getElementById('resSupervisores').textContent = fmtNum(r.supervisores);
            document.getElementById('resPromotores').textContent   = fmtNum(r.promotores);
            document.getElementById('resLech450').textContent      = fmtNum(r.lecherias_450);
            document.getElementById('resLech650').textContent      = fmtNum(r.lecherias_650);
            document.getElementById('resCapt').textContent         = fmtNum(r.capturadas);
            document.getElementById('resumenCard').style.display   = 'block';
        }

        let ultimaData = null;

        function actualizarFiltrosExport(data) {
            const supSelect = document.getElementById('exportSupSelect');
            const almSelect = document.getElementById('exportAlmSelect');

            // Reconstruir supervisores
            const supActual = supSelect.value;
            supSelect.innerHTML = '<option value="0">Todos los supervisores</option>';
            data.supervisores.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.nombre;
                supSelect.appendChild(opt);
            });
            supSelect.value = supActual;

            // Reconstruir almacenes según supervisor seleccionado
            function poblarAlmacenes() {
                const supId = parseInt(supSelect.value) || 0;
                const almActual = almSelect.value;
                almSelect.innerHTML = '<option value="">Todos los almacenes</option>';
                const sups = supId ? data.supervisores.filter(s => s.id === supId) : data.supervisores;
                const almSet = new Set();
                sups.forEach(s => s.almacenes.forEach(a => almSet.add(a.almacen)));
                almSet.forEach(a => {
                    const opt = document.createElement('option');
                    opt.value = a;
                    opt.textContent = a;
                    almSelect.appendChild(opt);
                });
                if (almSet.has(almActual)) almSelect.value = almActual;
            }
            poblarAlmacenes();
            supSelect.onchange = poblarAlmacenes;

            document.getElementById('exportBar').style.display = 'flex';
        }

        function pintar(data) {
            ultimaData = data;
            if (!data.supervisores || data.supervisores.length === 0) {
                contenedor.innerHTML = `
                    <div class="md3-card" style="text-align:center; padding:24px; color:var(--md-sys-color-on-surface-variant);">
                        <md-icon style="font-size:36px; color:var(--md-sys-color-tertiary);">inbox</md-icon>
                        <p style="margin-top:8px;">No hay datos para ese periodo y precio.</p>
                    </div>`;
                grandTotal.style.display = 'none';
                pintarResumen({ ...data.resumen, capturadas: data.total_capturadas });
                return;
            }

            const leyenda = `
                <div class="legend-dist">
                    <md-icon style="font-size:16px;">info</md-icon>
                    <span class="estado-pill estado-ver"><md-icon>verified</md-icon>Verif</span><span>VB supervisor (confiable)</span>
                    <span class="estado-pill estado-cap"><md-icon>edit_note</md-icon>Capt</span><span>Promotor envió, pendiente VB</span>
                    <span class="estado-pill estado-est"><md-icon>auto_graph</md-icon>Est</span><span>Avance estimado (no confiable)</span>
                </div>`;
            contenedor.innerHTML = leyenda +
                `<div class="supervisores-lista">${data.supervisores.map(pintarSupervisor).join('')}</div>`;

            totalGeneralEl.textContent = fmtNum(data.total_general);
            totalLechEl.textContent    = `(${data.total_capturadas}/${data.total_lecherias} capturadas)`;
            grandTotal.style.display   = 'flex';
            pintarResumen({ ...data.resumen, capturadas: data.total_capturadas });
            actualizarFiltrosExport(data);
        }

        async function cargar() {
            const mes  = selMes.value;
            const anio = inputAnio.value;
            if (!mes || !anio) return;

            skeleton();
            grandTotal.style.display = 'none';

            try {
                const r = await fetch(`api_requerimiento_global.php?mes=${mes}&anio=${anio}&precio=${precioActivo}`);
                const j = await r.json();
                if (j.status !== 'success') {
                    contenedor.innerHTML = `<p style="color:var(--md-sys-color-error); padding:16px;">${j.message || 'Error al cargar.'}</p>`;
                    return;
                }
                pintar(j);
            } catch (e) {
                contenedor.innerHTML = `<p style="color:var(--md-sys-color-error); padding:16px;">Error de conexión: ${e.message}</p>`;
            }
        }

        function toggleDrawer() {
            document.getElementById('mobile-drawer')?.classList.toggle('open');
            document.getElementById('drawer-scrim')?.classList.toggle('open');
        }

        function exportParams() {
            const mes  = selMes.value;
            const anio = inputAnio.value;
            const sup  = document.getElementById('exportSupSelect')?.value || '0';
            const alm  = encodeURIComponent(document.getElementById('exportAlmSelect')?.value || '');
            return `mes=${mes}&anio=${anio}&precio=${precioActivo}&supervisor_id=${sup}&almacen=${alm}`;
        }

        document.getElementById('btnExportExcel').addEventListener('click', () => {
            if (!ultimaData) return;
            window.location.href = `exportar_excel.php?${exportParams()}`;
        });

        document.getElementById('btnExportPDF').addEventListener('click', () => {
            if (!ultimaData) return;
            window.open(`exportar_pdf.php?${exportParams()}`, '_blank');
        });

        cargar();

        // ── OPE Diconsa: descarga con check de autorización ──────────────
        const opePend     = document.getElementById('opePendientes');
        const opePendList = document.getElementById('opePendientesLista');
        const opeSub      = document.getElementById('opeSubtitulo');
        let opeUltimoPrecio = 'all';

        async function descargarOpe(precioFiltro, force = false) {
            const mes  = selMes.value;
            const anio = inputAnio.value;
            if (!mes || !anio) { alert('Selecciona mes y año.'); return; }
            opeUltimoPrecio = precioFiltro;

            const params = new URLSearchParams({ mes, anio, precio: precioFiltro });
            if (force) params.set('force', '1');

            const url = `descargar_ope.php?${params.toString()}`;

            // Primera llamada como JSON-check (sin force) para detectar pendientes
            if (!force) {
                try {
                    const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const ct = r.headers.get('content-type') || '';
                    if (ct.includes('application/json')) {
                        const d = await r.json();
                        if (d.status === 'pendiente') {
                            opePendList.textContent = ' ' + (d.supervisores_pendientes || [])
                                .map(s => s.nombre || `#${s.id}`).join(', ');
                            opePend.style.display = 'block';
                            return;
                        }
                        if (d.status === 'error') {
                            alert('Error: ' + (d.message || 'no se pudo generar el OPE'));
                            return;
                        }
                        // status ok pero JSON inesperado → no debería ocurrir
                    } else {
                        // El servidor devolvió binario (xlsx) → descargar
                        const blob = await r.blob();
                        triggerBlobDownload(blob, filenameFromHeaders(r) || `OPE_${mes}_${anio}.xlsx`);
                        opePend.style.display = 'none';
                        return;
                    }
                } catch (e) {
                    alert('Error de red: ' + e.message);
                    return;
                }
            }

            // Con force: descarga directa (o segunda llamada)
            window.location.href = url;
            opePend.style.display = 'none';
        }

        function filenameFromHeaders(resp) {
            const cd = resp.headers.get('content-disposition') || '';
            const m = cd.match(/filename="?([^"]+)"?/);
            return m ? m[1] : null;
        }

        function triggerBlobDownload(blob, filename) {
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url; a.download = filename;
            document.body.appendChild(a); a.click();
            setTimeout(() => { URL.revokeObjectURL(url); a.remove(); }, 1000);
        }

        document.getElementById('btnOpeAll').addEventListener('click', () => descargarOpe('all'));
        document.getElementById('btnOpe450').addEventListener('click', () => descargarOpe('4.50'));
        document.getElementById('btnOpe650').addEventListener('click', () => descargarOpe('6.50'));
        document.getElementById('btnOpeForce').addEventListener('click', () => descargarOpe(opeUltimoPrecio, true));

        // Requerimiento por precio (xlsx — POR ALMACEN + TOTAL)
        function descargarReq(precio) {
            const m = parseInt(selMes.value || '0', 10);
            const a = parseInt(inputAnio.value || '0', 10);
            if (!m || !a) { alert('Selecciona mes y año.'); return; }
            const url = `descargar_req_precio.php?mes=${m}&anio=${a}&precio=${precio}`;
            window.location.href = url;
        }
        document.getElementById('btnReq650').addEventListener('click', () => descargarReq('6.50'));
        document.getElementById('btnReq450').addEventListener('click', () => descargarReq('4.50'));

        // Minuta mensual
        document.getElementById('btnMinuta').addEventListener('click', () => {
            const m = parseInt(selMes.value || '0', 10);
            const a = parseInt(inputAnio.value || '0', 10);
            if (!m || !a) { alert('Selecciona mes y año.'); return; }
            window.location.href = `descargar_minuta.php?mes=${m}&anio=${a}`;
        });

        // Subtítulo dinámico mes/año
        function actualizarSubOpe() {
            const meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                           'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
            const m = parseInt(selMes.value || '0', 10);
            opeSub.textContent = m > 0
                ? `Generará OPE${String(m).padStart(2,'0')}${inputAnio.value}DICONSA.xlsx con los datos autorizados de ${meses[m-1]} ${inputAnio.value}.`
                : 'Descarga el control de la operación con los datos autorizados por supervisores.';
            opePend.style.display = 'none';
        }
        selMes.addEventListener('change', actualizarSubOpe);
        inputAnio.addEventListener('change', actualizarSubOpe);
        actualizarSubOpe();
    </script>
</body>
</html>
