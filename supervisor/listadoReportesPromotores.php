<?php
require_once __DIR__ . '/../includes/session_guard.php';
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    header('Location: ../iniciosesionSupervisor.php');
    exit();
}
$nombre_usuario = $_SESSION['nombre'] ?? $_SESSION['usuario'];
$meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
          'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
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
        .filtros-card{display:flex;flex-wrap:wrap;align-items:center;gap:14px;padding:16px 20px;margin-bottom:16px;}
        .reportes-table{width:100%;border-collapse:collapse;font-size:.88rem;}
        .reportes-table th,.reportes-table td{
            padding:10px 12px;text-align:left;
            border-bottom:1px solid var(--md-sys-color-outline-variant);
        }
        .reportes-table th{
            font-weight:600;color:var(--md-sys-color-on-surface-variant);
            background:var(--md-sys-color-surface-container-high);
            font-size:.74rem;text-transform:uppercase;letter-spacing:.4px;
        }
        .badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:.72rem;font-weight:600;}
        .badge-aprob{background:color-mix(in srgb,var(--md-sys-color-primary) 22%,transparent);color:var(--md-sys-color-primary);}
        .badge-pend {background:color-mix(in srgb,var(--md-sys-color-tertiary) 22%,transparent);color:var(--md-sys-color-tertiary);}
        .badge-prog {background:color-mix(in srgb,var(--md-sys-color-secondary) 22%,transparent);color:var(--md-sys-color-secondary);}
        .badge-sin  {background:color-mix(in srgb,var(--md-sys-color-error) 18%,transparent);color:var(--md-sys-color-error);}
        /* Tres tarjetas oficiales de estado */
        .resumen-grid{
            display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
            gap:12px; margin-bottom:16px;
        }
        .resumen-cell{
            padding:14px 18px; border-radius:14px;
            background:var(--md-sys-color-surface-container);
            border:1px solid var(--md-sys-color-outline-variant);
        }
        .resumen-cell .label{
            font-size:.75rem; color:var(--md-sys-color-on-surface-variant);
            text-transform:uppercase; letter-spacing:.5px;
        }
        .resumen-cell .value{
            font-size:1.6rem; font-weight:600; margin-top:4px;
        }
        .resumen-cell.aprob .value{ color:var(--md-sys-color-primary); }
        .resumen-cell.pend  .value{ color:var(--md-sys-color-tertiary); }
        .resumen-cell.sin   .value{ color:var(--md-sys-color-error); }
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
                <md-text-button href="inicio.php"><md-icon slot="icon">home</md-icon> Inicio</md-text-button>
                <md-text-button href="listadoReportesPromotores.php"><md-icon slot="icon">receipt_long</md-icon> Reporte Mensual</md-text-button>
                <md-text-button href="requerimientodedotacion.php"><md-icon slot="icon">fact_check</md-icon> Requerimiento de Dotación</md-text-button>
                <md-text-button href="inventario_almacen.php"><md-icon slot="icon">warehouse</md-icon> Inventario de Almacén</md-text-button>
            </div>
            <md-filled-tonal-button href="../cerrar_sesionsupervisor.php" style="margin-left:16px;">
                <md-icon slot="icon">logout</md-icon> Salir
            </md-filled-tonal-button>
        </div>
    </header>

    <div id="drawer-scrim" class="md3-drawer-scrim" onclick="toggleDrawer()"></div>
    <aside class="md3-drawer" id="mobile-drawer">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 16px 8px 24px;">
            <span style="font-size:1.25rem;font-weight:500;">Menú Supervisor</span>
            <md-icon-button onclick="toggleDrawer()"><md-icon>close</md-icon></md-icon-button>
        </div>
        <md-list style="background:transparent;">
            <md-list-item href="inicio.php" type="button">
                <div slot="headline">Inicio</div><md-icon slot="start">home</md-icon>
            </md-list-item>
            <md-divider style="margin:8px 0;"></md-divider>
            <md-list-item href="listadoReportesPromotores.php" type="button">
                <div slot="headline">Reporte Mensual</div><md-icon slot="start">receipt_long</md-icon>
            </md-list-item>
            <md-list-item href="requerimientodedotacion.php" type="button">
                <div slot="headline">Requerimiento de Dotación</div><md-icon slot="start">fact_check</md-icon>
            </md-list-item>
            <md-list-item href="inventario_almacen.php" type="button">
                <div slot="headline">Inventario de Almacén</div><md-icon slot="start">warehouse</md-icon>
            </md-list-item>
        </md-list>
    </aside>

    <main class="panel-content">
        <div class="md3-card md3-hero-card">
            <div style="display:flex;align-items:center;gap:16px;">
                <div style="background:var(--md-sys-color-primary-container);border-radius:16px;padding:10px;display:flex;">
                    <md-icon style="color:var(--md-sys-color-on-primary-container);font-size:32px;width:32px;height:32px;">receipt_long</md-icon>
                </div>
                <div>
                    <h2 style="margin:0;font-size:1.6rem;font-weight:500;">Reporte Mensual</h2>
                    <p style="margin:4px 0 0;font-size:.9rem;color:var(--md-sys-color-on-surface-variant);">
                        Reportes mensuales de tus promotores. Ábrelos para revisarlos, editarlos o aprobarlos para Distribución.
                    </p>
                </div>
            </div>
        </div>

        <div class="md3-card filtros-card">
            <md-outlined-select label="Mes" id="selMes" style="min-width:130px;">
                <?php $mesActual = (int)date('n'); foreach ($meses as $i => $m): $n = $i + 1; ?>
                    <md-select-option value="<?= $n ?>" <?= $n === $mesActual ? 'selected' : '' ?>>
                        <div slot="headline"><?= $m ?></div>
                    </md-select-option>
                <?php endforeach; ?>
            </md-outlined-select>
            <md-outlined-text-field label="Año" id="inputAnio" type="number"
                value="<?= date('Y') ?>" style="max-width:110px;"></md-outlined-text-field>
        </div>

        <!-- 3 tarjetas oficiales de estado -->
        <div class="resumen-grid" id="resumenGrid" style="display:none;">
            <div class="resumen-cell aprob">
                <div class="label">Aprobados</div>
                <div class="value" id="cntAprob">0</div>
            </div>
            <div class="resumen-cell pend">
                <div class="label">Pendientes de aprobar</div>
                <div class="value" id="cntPend">0</div>
            </div>
            <div class="resumen-cell sin">
                <div class="label">Sin captura</div>
                <div class="value" id="cntSin">0</div>
            </div>
        </div>

        <div class="md3-card" id="contenedor">
            <div style="text-align:center;padding:24px;color:var(--md-sys-color-on-surface-variant);">Cargando...</div>
        </div>
    </main>

    <script src="../js/temas_md3.js"></script>
    <script>
        const MESES = <?= json_encode($meses, JSON_UNESCAPED_UNICODE) ?>;
        const selMes    = document.getElementById('selMes');
        const inputAnio = document.getElementById('inputAnio');
        const cont      = document.getElementById('contenedor');
        const resumen   = document.getElementById('resumenGrid');

        // Memoria de mes/año mientras el supervisor está dentro del flujo
        // "Reporte Mensual". Se limpia al salir a Inicio/Requerimiento (más abajo).
        const SK_MES = 'rpt_supervisor_mes', SK_ANIO = 'rpt_supervisor_anio';
        const memMes  = sessionStorage.getItem(SK_MES);
        const memAnio = sessionStorage.getItem(SK_ANIO);
        if (memMes)  selMes.value    = memMes;
        if (memAnio) inputAnio.value = memAnio;
        if (!selMes.value)    selMes.value    = String(new Date().getMonth() + 1);
        if (!inputAnio.value) inputAnio.value = String(new Date().getFullYear());

        const guardarSel = () => {
            sessionStorage.setItem(SK_MES,  selMes.value);
            sessionStorage.setItem(SK_ANIO, inputAnio.value);
        };
        guardarSel();

        selMes.addEventListener('change',    () => { guardarSel(); cargar(); });
        inputAnio.addEventListener('change', () => { guardarSel(); cargar(); });

        // Al pulsar cualquier enlace que saque del flujo de Reporte Mensual,
        // borramos la memoria para que la próxima vez vuelva al mes actual.
        document.querySelectorAll('a[href], md-text-button[href], md-list-item[href], md-filled-tonal-button[href]')
            .forEach(el => {
                const href = el.getAttribute('href') || '';
                const enFlujo = /listadoReportesPromotores\.php|editar_reporte_promotor\.php/.test(href);
                if (!enFlujo && href) {
                    el.addEventListener('click', () => {
                        sessionStorage.removeItem(SK_MES);
                        sessionStorage.removeItem(SK_ANIO);
                    });
                }
            });
        // Auto-refresh cuando la pestaña vuelve a foco (cubre editar/aprobar en otra pestaña)
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') cargar();
        });
        // pageshow se dispara también al regresar con back/forward (bfcache)
        window.addEventListener('pageshow', (e) => { if (e.persisted) cargar(); });

        function estado(r){
            if (r.aprobado)  return { txt:'APROBADO',             cls:'badge-aprob', tipo:'aprob' };
            if (r.bloqueado) return { txt:'PENDIENTE APROBACIÓN', cls:'badge-pend',  tipo:'pend'  };
            if (r.capturadas_inv > 0 && r.capturadas_inv < r.total_padron)
                return { txt:`EN PROGRESO ${r.capturadas_inv}/${r.total_padron}`,
                         cls:'badge-prog', tipo:'pend' };
            if (r.capturadas_inv >= r.total_padron && r.total_padron > 0)
                return { txt:'COMPLETO — SIN REPORTE',
                         cls:'badge-prog', tipo:'pend' };
            return { txt:'FALTA', cls:'badge-sin', tipo:'sin' };
        }

        function toggleDrawer(){
            document.getElementById('mobile-drawer')?.classList.toggle('open');
            document.getElementById('drawer-scrim')?.classList.toggle('open');
        }

        async function cargar(){
            cont.innerHTML = '<div style="text-align:center;padding:24px;color:var(--md-sys-color-on-surface-variant);">Cargando...</div>';
            resumen.style.display = 'none';
            const p = new URLSearchParams();
            if (selMes.value)    p.set('mes',  selMes.value);
            if (inputAnio.value) p.set('anio', inputAnio.value);
            try {
                const r = await fetch('api_listar_reportes_promotores.php?' + p);
                const j = await r.json();
                if (j.status !== 'success') {
                    cont.innerHTML = `<p style="color:var(--md-sys-color-error);padding:16px;">${j.mensaje}</p>`;
                    return;
                }
                if (!j.reportes.length) {
                    cont.innerHTML = '<div style="text-align:center;padding:24px;color:var(--md-sys-color-on-surface-variant);">No tienes promotores asignados.</div>';
                    return;
                }

                let nAprob = 0, nPend = 0, nSin = 0;
                const filas = j.reportes.map(r => {
                    const e = estado(r);
                    if (e.tipo === 'aprob') nAprob++;
                    else if (e.tipo === 'pend') nPend++;
                    else nSin++;

                    const fecha = (r.fecha_captura || '').replace('T',' ').slice(0,16) || '—';
                    // "Lecherías" = avance del INVENTARIO MENSUAL del promotor
                    const lechCol = `${r.capturadas_inv}<span style="opacity:.55;">/${r.total_padron}</span>`;

                    const labelBoton = r.aprobado
                        ? 'Ver'
                        : (r.total_lecherias > 0 ? 'Editar / Aprobar' : 'Ver avance');

                    const subtitulo = r.usuario_captura
                        ? r.usuario_captura
                        : `#${r.promotor_id}`;

                    return `
                        <tr>
                            <td>${r.promotor_nombre}<br>
                                <span style="font-size:.72rem;opacity:.65;">${subtitulo}</span></td>
                            <td>${MESES[r.mes-1]} ${r.anio}</td>
                            <td style="text-align:center;">${lechCol}</td>
                            <td><span class="badge ${e.cls}">${e.txt}</span></td>
                            <td style="font-size:.78rem;opacity:.85;">${fecha}</td>
                            <td style="text-align:right;">
                                <md-filled-tonal-button onclick="abrir(${r.promotor_id},${r.mes},${r.anio})">
                                    <md-icon slot="icon">visibility</md-icon>
                                    ${labelBoton}
                                </md-filled-tonal-button>
                            </td>
                        </tr>`;
                }).join('');

                document.getElementById('cntAprob').textContent = nAprob;
                document.getElementById('cntPend').textContent  = nPend;
                document.getElementById('cntSin').textContent   = nSin;
                resumen.style.display = 'grid';

                cont.innerHTML = `
                    <table class="reportes-table">
                        <thead><tr>
                            <th>Promotor</th><th>Periodo</th><th>Lecherías</th>
                            <th>Estado</th><th>Capturado</th><th></th>
                        </tr></thead>
                        <tbody>${filas}</tbody>
                    </table>`;
            } catch (e) {
                cont.innerHTML = `<p style="color:var(--md-sys-color-error);padding:16px;">Error: ${e.message}</p>`;
            }
        }

        function abrir(promotor, mes, anio){
            location.href = `editar_reporte_promotor.php?promotor=${promotor}&mes=${mes}&anio=${anio}`;
        }

        cargar();
    </script>
</body>
</html>
