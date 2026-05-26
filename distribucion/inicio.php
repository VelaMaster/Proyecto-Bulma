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
        .reporte-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
        .reporte-table th, .reporte-table td {
            padding: 5px 10px;
            border-bottom: 1px solid var(--md-sys-color-outline-variant);
            text-align: left;
        }
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
            <md-filled-tonal-button href="../cerrar_sesion.php" style="margin-left:16px;">
                <md-icon slot="icon">logout</md-icon> Salir
            </md-filled-tonal-button>
        </div>
    </header>

    <aside class="md3-drawer" id="mobile-drawer">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:16px 16px 8px 24px;">
            <span style="font-size:1.25rem; font-weight:500;">Menú Distribución</span>
            <md-icon-button onclick="toggleDrawer()"><md-icon>close</md-icon></md-icon-button>
        </div>
        <md-list style="background:transparent;">
            <div class="drawer-section-title">Requerimiento</div>
            <md-list-item href="inicio.php" type="button">
                <div slot="headline">Consolidado Global</div>
                <md-icon slot="start">summarize</md-icon>
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

        function pintarAlmacen(alm) {
            const filas = alm.lecherias.map(l => {
                const reqCell = l.capturado
                    ? fmtNum(l.requerimiento)
                    : `<span class="falta-pill">FALTA</span>`;
                const tiendaCell = l.num_tienda === 'DM'
                    ? `<span class="dm-tag">DM</span>`
                    : (l.num_tienda || '');
                return `<tr>
                    <td>${l.punto_venta}</td>
                    <td>${tiendaCell}</td>
                    <td>${reqCell}</td>
                </tr>`;
            }).join('');

            return `
                <div class="almacen-card">
                    <div class="almacen-titulo">
                        <md-icon style="font-size:18px;">warehouse</md-icon>
                        <span>ALMACÉN ${alm.almacen}</span>
                        <span class="alm-meta">${alm.capturadas}/${alm.total}</span>
                    </div>
                    <table class="reporte-table">
                        <thead>
                            <tr><th>Punto de Venta</th><th>Tienda</th><th>Req.</th></tr>
                        </thead>
                        <tbody>
                            ${filas}
                            <tr class="subtotal-row">
                                <td colspan="2" style="text-align:right;">SUBTOTAL =</td>
                                <td>${fmtNum(alm.subtotal)}</td>
                            </tr>
                        </tbody>
                    </table>
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

        function pintar(data) {
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

            contenedor.innerHTML = `<div class="supervisores-lista">${data.supervisores.map(pintarSupervisor).join('')}</div>`;

            totalGeneralEl.textContent = fmtNum(data.total_general);
            totalLechEl.textContent    = `(${data.total_capturadas}/${data.total_lecherias} capturadas)`;
            grandTotal.style.display   = 'flex';
            pintarResumen({ ...data.resumen, capturadas: data.total_capturadas });
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
            document.getElementById('mobile-drawer').classList.toggle('open');
        }

        cargar();
    </script>
</body>
</html>
