<?php
require_once __DIR__ . '/../includes/session_guard.php';
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    header('Location: ../iniciosesionSupervisor.php');
    exit();
}
$nombre_usuario = $_SESSION['nombre'] ?? $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Mensual — Supervisor</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../main_md3.css">
    <link rel="stylesheet" href="../estilos/iniciocards.css">
    <style>
        .filtros-card{
            display:flex; flex-wrap:wrap; align-items:center; gap:14px;
            padding:16px 20px; margin-bottom:16px;
        }
        /* Almacén cards con scroll horizontal para la tabla ancha */
        .almacenes-lista{ display:flex; flex-direction:column; gap:16px; }
        .almacen-card{
            background:var(--md-sys-color-surface-container);
            border:1px solid var(--md-sys-color-outline-variant);
            border-radius:14px; overflow:hidden;
        }
        .almacen-titulo{
            display:flex; align-items:center; gap:10px;
            padding:10px 14px;
            background:var(--md-sys-color-secondary-container);
            color:var(--md-sys-color-on-secondary-container);
            font-weight:600; font-size:0.95rem;
        }
        .almacen-titulo .meta{
            margin-left:auto; font-weight:500; opacity:.85; font-size:0.8rem;
        }
        .tabla-scroll{ overflow-x:auto; }
        .reporte-table{
            width:100%; border-collapse:collapse; font-size:0.82rem;
            min-width:900px;
        }
        .reporte-table th, .reporte-table td{
            padding:5px 9px; border-bottom:1px solid var(--md-sys-color-outline-variant);
            text-align:center; white-space:nowrap;
        }
        .reporte-table th{
            font-weight:600; color:var(--md-sys-color-on-surface-variant);
            background:var(--md-sys-color-surface-container-high);
            font-size:0.72rem; text-transform:uppercase; letter-spacing:.4px;
        }
        .reporte-table td:first-child, .reporte-table th:first-child{ text-align:left; }
        .falta-pill{
            display:inline-block; padding:2px 10px; border-radius:999px;
            background:color-mix(in srgb, var(--md-sys-color-error) 18%, transparent);
            color:var(--md-sys-color-error); font-weight:600; font-size:0.72rem;
        }
        .dm-tag{
            display:inline-block; padding:1px 8px; border-radius:6px;
            background:color-mix(in srgb, var(--md-sys-color-tertiary) 22%, transparent);
            color:var(--md-sys-color-tertiary); font-weight:600; font-size:0.72rem;
        }
        .skel-bar{
            height:14px; border-radius:6px;
            background:var(--md-sys-color-surface-container-highest);
            animation:pulseSkel 1.4s infinite ease-in-out;
        }
        @keyframes pulseSkel{50%{opacity:.5}}
        .resumen-grid{
            display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr));
            gap:12px; margin-top:12px;
        }
        .resumen-cell{
            padding:12px 16px; border-radius:12px;
            background:var(--md-sys-color-surface-container);
            border:1px solid var(--md-sys-color-outline-variant);
        }
        .resumen-cell .label{
            font-size:0.75rem; color:var(--md-sys-color-on-surface-variant);
            text-transform:uppercase; letter-spacing:.5px;
        }
        .resumen-cell .value{
            font-size:1.4rem; font-weight:600; color:var(--md-sys-color-primary); margin-top:4px;
        }
        .obs-cell{ max-width:160px; overflow:hidden; text-overflow:ellipsis; }
    </style>
    <script type="importmap">{ "imports": { "@material/web/": "https://esm.run/@material/web/" } }</script>
    <script type="module"> import '@material/web/all.js'; </script>
</head>
<body>
    <header class="md3-top-app-bar">
        <div class="app-bar-start">
            <md-icon-button onclick="toggleDrawer()"><md-icon>menu</md-icon></md-icon-button>
            <div class="app-brand"><span>Liconsa — Supervisión</span></div>
        </div>
        <div class="app-bar-end">
            <div class="desktop-nav">
                <md-text-button href="inicio.php">
                    <md-icon slot="icon">home</md-icon> Inicio
                </md-text-button>
                <md-text-button href="requerimientodedotacion.php">
                    <md-icon slot="icon">description</md-icon> Requerimiento
                </md-text-button>
            </div>
            <md-filled-tonal-button href="../cerrar_sesionsupervisor.php" style="margin-left:16px;">
                <md-icon slot="icon">logout</md-icon> Salir
            </md-filled-tonal-button>
        </div>
    </header>

    <aside class="md3-drawer" id="mobile-drawer">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 16px 8px 24px;">
            <span style="font-size:1.25rem;font-weight:500;">Menú Supervisor</span>
            <md-icon-button onclick="toggleDrawer()"><md-icon>close</md-icon></md-icon-button>
        </div>
        <md-list style="background:transparent;">
            <md-list-item href="inicio.php" type="button">
                <div slot="headline">Inicio</div>
                <md-icon slot="start">home</md-icon>
            </md-list-item>
            <md-list-item href="requerimientodedotacion.php" type="button">
                <div slot="headline">Requerimiento de Dotación</div>
                <md-icon slot="start">description</md-icon>
            </md-list-item>
            <md-list-item href="reporte_mensual.php" type="button">
                <div slot="headline">Reporte Mensual</div>
                <md-icon slot="start">bar_chart</md-icon>
            </md-list-item>
        </md-list>
    </aside>

    <main class="panel-content">
        <div class="md3-card md3-hero-card">
            <div style="display:flex;align-items:center;gap:16px;">
                <div style="background:var(--md-sys-color-primary-container);border-radius:16px;padding:10px;display:flex;">
                    <md-icon style="color:var(--md-sys-color-on-primary-container);font-size:32px;width:32px;height:32px;">bar_chart</md-icon>
                </div>
                <div>
                    <h2 style="margin:0;font-size:1.6rem;font-weight:500;">Reporte Mensual de la Operación</h2>
                    <p style="margin:4px 0 0;font-size:0.9rem;color:var(--md-sys-color-on-surface-variant);">
                        Avance de captura de tus promotores, agrupado por almacén.
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
            <span style="flex-grow:1;"></span>
            <md-outlined-button id="btnExcelRpt">
                <md-icon slot="icon">download</md-icon> Excel
            </md-outlined-button>
        </div>

        <!-- Resumen -->
        <div class="md3-card" id="resumenCard" style="display:none; margin-bottom:16px;">
            <h3 style="margin:0 0 4px;font-size:1rem;font-weight:500;">
                <md-icon style="vertical-align:middle;margin-right:6px;color:var(--md-sys-color-primary);">insights</md-icon>
                Resumen de captura
            </h3>
            <div class="resumen-grid">
                <div class="resumen-cell">
                    <div class="label">Lecherías total</div>
                    <div class="value" id="resTotalLech">0</div>
                </div>
                <div class="resumen-cell">
                    <div class="label">Capturadas</div>
                    <div class="value" id="resCapt">0</div>
                </div>
                <div class="resumen-cell">
                    <div class="label">Pendientes</div>
                    <div class="value" id="resPend">0</div>
                </div>
                <div class="resumen-cell">
                    <div class="label">% Avance</div>
                    <div class="value" id="resPct">0%</div>
                </div>
            </div>
        </div>

        <div id="contenedorTabla">
            <div class="md3-card" style="text-align:center;padding:24px;color:var(--md-sys-color-on-surface-variant);">
                Selecciona mes y año para ver el reporte.
            </div>
        </div>
    </main>

    <script src="../js/temas_md3.js"></script>
    <script>
        const selMes      = document.getElementById('selMes');
        const inputAnio   = document.getElementById('inputAnio');
        const contenedor  = document.getElementById('contenedorTabla');
        const resumenCard = document.getElementById('resumenCard');

        selMes.value = String(new Date().getMonth() + 1);

        selMes.addEventListener('change', cargar);
        inputAnio.addEventListener('change', cargar);

        document.getElementById('btnExcelRpt').addEventListener('click', () => {
            const mes  = selMes.value;
            const anio = inputAnio.value;
            if (!mes || !anio) return;
            window.location.href =
                `exportar_excel_reporte.php?mes=${mes}&anio=${anio}`;
        });

        function fmtNum(n) {
            if (n === null || n === undefined) return '—';
            return Number(n).toLocaleString('es-MX');
        }

        function skeleton() {
            contenedor.innerHTML = `
                <div class="almacen-card" style="padding:14px;">
                    ${Array(4).fill('<div class="skel-bar" style="margin-bottom:10px;"></div>').join('')}
                </div>`;
        }

        function pintarAlmacen(alm) {
            const filas = alm.lecherias.map(l => {
                if (!l.capturado) {
                    return `<tr>
                        <td>${l.punto_venta}</td>
                        <td>${l.num_tienda === 'DM' ? '<span class="dm-tag">DM</span>' : l.num_tienda}</td>
                        <td>${l.precio}</td>
                        <td colspan="10" style="text-align:center;">
                            <span class="falta-pill">FALTA</span>
                        </td>
                    </tr>`;
                }
                return `<tr>
                    <td>${l.punto_venta}</td>
                    <td>${l.num_tienda === 'DM' ? '<span class="dm-tag">DM</span>' : l.num_tienda}</td>
                    <td>${l.precio}</td>
                    <td>${fmtNum(l.inv_ini_cajas)}</td>
                    <td>${fmtNum(l.dot_recib_cajas)}</td>
                    <td>${fmtNum(l.total_cajas)}</td>
                    <td>${fmtNum(l.vend_cajas)} / ${fmtNum(l.vend_sobres)}</td>
                    <td>${fmtNum(l.inv_fin_cajas)} / ${fmtNum(l.inv_fin_sobres)}</td>
                    <td>${fmtNum(l.retiro_cajas)}</td>
                    <td>${fmtNum(l.familias_no_acud)}</td>
                    <td title="${l.observaciones || ''}" class="obs-cell">${l.observaciones || '—'}</td>
                    <td style="font-size:0.7rem;opacity:.7;">${l.promotor || '—'}</td>
                </tr>`;
            }).join('');

            return `
                <div class="almacen-card">
                    <div class="almacen-titulo">
                        <md-icon>warehouse</md-icon>
                        <span>ALMACÉN ${alm.almacen}</span>
                        <span class="meta">${alm.capturadas}/${alm.total} capturadas</span>
                    </div>
                    <div class="tabla-scroll">
                        <table class="reporte-table">
                            <thead>
                                <tr>
                                    <th>Punto Venta</th>
                                    <th>Tienda</th>
                                    <th>Precio</th>
                                    <th>Inv.Ini Cajas</th>
                                    <th>Dot.Recib.</th>
                                    <th>Total Cajas</th>
                                    <th>Vend C/S</th>
                                    <th>Inv.Fin C/S</th>
                                    <th>Retiro Caj.</th>
                                    <th>Fam. No Acud.</th>
                                    <th>Observaciones</th>
                                    <th>Promotor</th>
                                </tr>
                            </thead>
                            <tbody>${filas}</tbody>
                        </table>
                    </div>
                </div>`;
        }

        function pintar(data) {
            if (!data.almacenes || data.almacenes.length === 0) {
                contenedor.innerHTML = `
                    <div class="md3-card" style="text-align:center;padding:24px;color:var(--md-sys-color-on-surface-variant);">
                        <md-icon style="font-size:36px;color:var(--md-sys-color-tertiary);">inbox</md-icon>
                        <p>Sin datos para este período.</p>
                    </div>`;
                resumenCard.style.display = 'none';
                return;
            }

            contenedor.innerHTML = `<div class="almacenes-lista">
                ${data.almacenes.map(pintarAlmacen).join('')}
            </div>`;

            const pct = data.total_lecherias > 0
                ? Math.round((data.total_capturadas / data.total_lecherias) * 100) : 0;
            document.getElementById('resTotalLech').textContent = data.total_lecherias;
            document.getElementById('resCapt').textContent = data.total_capturadas;
            document.getElementById('resPend').textContent = data.total_lecherias - data.total_capturadas;
            document.getElementById('resPct').textContent  = pct + '%';
            resumenCard.style.display = 'block';
        }

        async function cargar() {
            const mes  = selMes.value;
            const anio = inputAnio.value;
            if (!mes || !anio) return;
            skeleton();
            try {
                const r = await fetch(`api_reporte_mensual_supervisor.php?mes=${mes}&anio=${anio}`);
                const j = await r.json();
                if (j.status !== 'success') {
                    contenedor.innerHTML = `<p style="color:var(--md-sys-color-error);padding:16px;">${j.message}</p>`;
                    return;
                }
                pintar(j);
            } catch (e) {
                contenedor.innerHTML = `<p style="color:var(--md-sys-color-error);padding:16px;">Error: ${e.message}</p>`;
            }
        }

        function toggleDrawer() {
            document.getElementById('mobile-drawer').classList.toggle('open');
        }

        cargar();
    </script>
</body>
</html>
