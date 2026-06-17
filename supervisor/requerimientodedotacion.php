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
    <title>Requerimiento de Dotación</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../main_md3.css">
    <link rel="stylesheet" href="../estilos/iniciocards.css">
    <style>
        .filtros-card{
            display:flex; flex-wrap:wrap; align-items:center; gap:14px;
            padding:16px 20px; margin-bottom:16px;
        }
        .filtros-card label{font-size:0.85rem; color:var(--md-sys-color-on-surface-variant);}
        .precio-pills{display:inline-flex; gap:8px;}
        .precio-pill-btn{
            cursor:pointer; padding:6px 14px; border-radius:999px;
            border:1px solid var(--md-sys-color-outline-variant);
            background:var(--md-sys-color-surface-container); color:var(--md-sys-color-on-surface);
            font-size:0.85rem; font-weight:500;
        }
        .precio-pill-btn.active{
            background:var(--md-sys-color-primary); color:var(--md-sys-color-on-primary);
            border-color:transparent;
        }
        /* Grid responsivo: 2 columnas en desktop, 1 en móvil */
        .almacenes-grid{
            display:grid;
            grid-template-columns: repeat(auto-fill, minmax(460px, 1fr));
            gap:14px;
        }
        .almacen-card{
            background:var(--md-sys-color-surface-container);
            border:1px solid var(--md-sys-color-outline-variant);
            border-radius:14px;
            overflow:hidden;
        }
        .almacen-card .tabla-scroll{overflow-x:auto;}
        .almacen-card .almacen-titulo{
            display:flex; align-items:center; gap:10px;
            padding:10px 14px;
            background:var(--md-sys-color-secondary-container);
            color:var(--md-sys-color-on-secondary-container);
            font-weight:600; font-size:0.95rem;
        }
        .almacen-card .almacen-titulo .meta{
            margin-left:auto; font-weight:500; opacity:.85; font-size:0.8rem;
        }
        .reporte-table{width:100%; border-collapse:collapse; font-size:0.82rem; min-width:420px;}
        .reporte-table th, .reporte-table td{
            padding:6px 8px; border-bottom:1px solid var(--md-sys-color-outline-variant);
            text-align:left; white-space:nowrap;
        }
        .reporte-table th{
            font-weight:600; color:var(--md-sys-color-on-surface-variant);
            background:var(--md-sys-color-surface-container-high);
            font-size:0.75rem; text-transform:uppercase; letter-spacing:.4px;
        }
        .reporte-table th:last-child, .reporte-table td:last-child{text-align:right;}
        .subtotal-row td{
            background:var(--md-sys-color-surface-container-high);
            font-weight:700; color:var(--md-sys-color-on-surface);
        }
        .falta-pill{
            display:inline-block; padding:2px 10px; border-radius:999px;
            background:color-mix(in srgb, var(--md-sys-color-error) 18%, transparent);
            color:var(--md-sys-color-error); font-weight:600; font-size:0.75rem;
        }
        .estado-pill{
            display:inline-flex; align-items:center; gap:4px;
            padding:2px 10px; border-radius:999px;
            font-weight:600; font-size:0.7rem; text-transform:uppercase; letter-spacing:.4px;
        }
        .estado-pill md-icon{font-size:14px; width:14px; height:14px;}
        .estado-ver{
            background:color-mix(in srgb, #2e7d32 22%, transparent);
            color:#7fd996;
        }
        .estado-cap{
            background:color-mix(in srgb, var(--md-sys-color-primary) 22%, transparent);
            color:var(--md-sys-color-primary);
        }
        .estado-est{
            background:color-mix(in srgb, #ffc107 22%, transparent);
            color:#ffd966;
        }
        .req-val.estimado{opacity:.85; font-style:italic;}
        tr.estimada td{background:color-mix(in srgb, #ffc107 6%, transparent);}
        tr.faltante td{opacity:.85;}
        .legend{
            display:flex; flex-wrap:wrap; gap:14px; align-items:center;
            padding:10px 14px; margin-bottom:12px; border-radius:12px;
            background:var(--md-sys-color-surface-container);
            border:1px solid var(--md-sys-color-outline-variant);
            font-size:0.78rem; color:var(--md-sys-color-on-surface-variant);
        }
        .legend .estado-pill{font-size:0.65rem;}
        .almacen-acciones{display:flex; gap:6px; margin-left:auto;}
        .dm-tag{
            display:inline-block; padding:1px 8px; border-radius:6px;
            background:color-mix(in srgb, var(--md-sys-color-tertiary) 22%, transparent);
            color:var(--md-sys-color-tertiary); font-weight:600; font-size:0.75rem;
            letter-spacing:.5px;
        }
        /* Resumen final */
        .resumen-grid{
            display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr));
            gap:12px; margin-top:14px;
        }
        .resumen-cell{
            padding:14px 16px; border-radius:12px;
            background:var(--md-sys-color-surface-container);
            border:1px solid var(--md-sys-color-outline-variant);
        }
        .resumen-cell .label{
            font-size:0.78rem; color:var(--md-sys-color-on-surface-variant);
            text-transform:uppercase; letter-spacing:.5px;
        }
        .resumen-cell .value{
            font-size:1.5rem; font-weight:600; color:var(--md-sys-color-primary);
            margin-top:4px;
        }
        .grand-total{
            display:flex; align-items:center; justify-content:flex-end; gap:14px;
            padding:14px 18px; margin-top:8px; border-radius:14px;
            background:color-mix(in srgb, var(--md-sys-color-primary) 14%, transparent);
            color:var(--md-sys-color-on-surface);
        }
        .grand-total strong{color:var(--md-sys-color-primary); font-size:1.15rem;}
        .skel-row td{padding:14px 10px;}
        .skel-bar{height:14px; border-radius:6px; background:var(--md-sys-color-surface-container-highest);
                  animation:pulseSkel 1.4s infinite ease-in-out;}
        @keyframes pulseSkel{50%{opacity:.5}}
        .warn-card{
            display:flex; align-items:flex-start; gap:12px; padding:12px 16px; border-radius:12px;
            background:color-mix(in srgb, var(--md-sys-color-tertiary) 16%, transparent);
            color:var(--md-sys-color-on-surface);
            margin-bottom:14px;
        }
        .req-input{
            width:60px; padding:3px 6px; border-radius:8px; font-size:0.82rem;
            border:1px solid var(--md-sys-color-outline-variant);
            background:var(--md-sys-color-surface-container-high);
            color:var(--md-sys-color-on-surface); text-align:right;
        }
        .req-input:focus{outline:2px solid var(--md-sys-color-primary); border-color:transparent;}
        .req-input.guardando{opacity:.5; pointer-events:none;}
        .req-input.ok{border-color:#4caf50;}
        .req-input.err{border-color:var(--md-sys-color-error);}
    </style>
    <script type="importmap">{ "imports": { "@material/web/": "https://esm.run/@material/web/" } }</script>
    <script type="module"> import '@material/web/all.js'; </script>
</head>
<body>
    <header class="md3-top-app-bar">
        <div class="app-bar-start">
            <md-icon-button class="mobile-menu-btn" onclick="toggleDrawer()">
                <md-icon>menu</md-icon>
            </md-icon-button>
            <div class="app-brand"><span>Liconsa - Supervisión</span></div>
        </div>

        <div class="app-bar-end">
            <div class="desktop-nav">
                <md-text-button href="inicio.php">
                    <md-icon slot="icon">home</md-icon>Inicio
                </md-text-button>
                <md-text-button href="listadoReportesPromotores.php">
                    <md-icon slot="icon">receipt_long</md-icon>Reporte Mensual
                </md-text-button>
                <md-text-button href="requerimientodedotacion.php">
                    <md-icon slot="icon">fact_check</md-icon>Requerimiento de Dotación
                </md-text-button>
                <md-text-button href="inventario_almacen.php">
                    <md-icon slot="icon">warehouse</md-icon>Inventario de Almacén
                </md-text-button>
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
            <md-list-item href="lecherias.php" type="button">
                <div slot="headline">Lecherías</div><md-icon slot="start">storefront</md-icon>
            </md-list-item>
            <md-list-item href="listadoReportesPromotores.php" type="button">
                <div slot="headline">Reporte Mensual</div><md-icon slot="start">receipt_long</md-icon>
            </md-list-item>
            <md-list-item href="requerimientodedotacion.php" type="button">
                <div slot="headline">Requerimiento de Dotación</div><md-icon slot="start">fact_check</md-icon>
            </md-list-item>
        </md-list>
    </aside>

    <main class="panel-content">
        <div class="md3-card md3-hero-card">
            <div style="display:flex; align-items:center; gap:16px;">
                <div style="background:var(--md-sys-color-primary-container); border-radius:16px; padding:10px; display:flex;">
                    <md-icon style="color:var(--md-sys-color-on-primary-container); font-size:32px; width:32px; height:32px;">description</md-icon>
                </div>
                <div>
                    <h2 style="margin:0; font-size:1.6rem; font-weight:500; color:var(--md-sys-color-on-surface);">
                        Requerimiento de Dotación
                    </h2>
                    <p style="margin:4px 0 0; font-size:0.9rem; color:var(--md-sys-color-on-surface-variant);">
                        Consolidado en base al desplazamiento mensual reportado por tus promotores,
                        agrupado por almacén y separado por precio.
                    </p>
                </div>
            </div>
        </div>

        <div class="md3-card filtros-card">
            <md-outlined-select label="Mes" id="selMes" style="min-width:130px;">
                <md-select-option value="1"><div slot="headline">Enero</div></md-select-option>
                <md-select-option value="2"><div slot="headline">Febrero</div></md-select-option>
                <md-select-option value="3"><div slot="headline">Marzo</div></md-select-option>
                <md-select-option value="4"><div slot="headline">Abril</div></md-select-option>
                <md-select-option value="5"><div slot="headline">Mayo</div></md-select-option>
                <md-select-option value="6"><div slot="headline">Junio</div></md-select-option>
                <md-select-option value="7"><div slot="headline">Julio</div></md-select-option>
                <md-select-option value="8"><div slot="headline">Agosto</div></md-select-option>
                <md-select-option value="9"><div slot="headline">Septiembre</div></md-select-option>
                <md-select-option value="10"><div slot="headline">Octubre</div></md-select-option>
                <md-select-option value="11"><div slot="headline">Noviembre</div></md-select-option>
                <md-select-option value="12"><div slot="headline">Diciembre</div></md-select-option>
            </md-outlined-select>

            <md-outlined-text-field label="Año" id="inputAnio" type="number"
                value="<?= date('Y') ?>" style="max-width:100px;"></md-outlined-text-field>

            <!-- Precio como pills (Todos / $4.50 / $6.50) -->
            <div class="precio-pills" role="tablist" aria-label="Filtro por precio">
                <button type="button" class="precio-pill-btn active" data-precio="todos">Todos</button>
                <button type="button" class="precio-pill-btn" data-precio="6.50">$6.50</button>
                <button type="button" class="precio-pill-btn" data-precio="4.50">$4.50</button>
            </div>

            <!-- Almacén (se llena dinámicamente) -->
            <md-outlined-select label="Almacén" id="selAlmacen" style="min-width:150px;">
                <md-select-option value=""><div slot="headline">Todos</div></md-select-option>
            </md-outlined-select>

            <!-- Distribuidor / responsable de surtimiento -->
            <md-outlined-select label="Distribuidor" id="selRessurti" style="min-width:190px;">
                <md-select-option value=""><div slot="headline">Todos</div></md-select-option>
            </md-outlined-select>

            <span style="flex-grow:1;"></span>

            <md-filled-button id="btnGenerarPDF">
                <md-icon slot="icon">picture_as_pdf</md-icon> Generar PDF
            </md-filled-button>
        </div>
                <div class="md3-card" id="resumenCard" style="display:none; margin-top:14px;">
            <h3 style="margin:0 0 4px; font-size:1rem; font-weight:500; color:var(--md-sys-color-on-surface);">
                <md-icon style="vertical-align:middle; margin-right:6px; color:var(--md-sys-color-primary);">insights</md-icon>
                Resumen del padrón a tu cargo
            </h3>
            <p style="margin:0 0 8px; font-size:0.85rem; color:var(--md-sys-color-on-surface-variant);">
            </p>
            <div class="resumen-grid">
                <div class="resumen-cell">
                    <div class="label">Promotores</div>
                    <div class="value" id="resPromotores">0</div>
                </div>
                <div class="resumen-cell">
                    <div class="label">Lecherías en total</div>
                    <div class="value" id="resLechTotal">0</div>
                </div>
                <div class="resumen-cell">
                    <div class="label">Lecherías $4.50</div>
                    <div class="value" id="resLech450">0</div>
                </div>
                <div class="resumen-cell">
                    <div class="label">Lecherías $6.50</div>
                    <div class="value" id="resLech650">0</div>
                </div>
            </div>
        </div>
        <div id="warnPromos" style="display:none;"></div>

        <div class="legend" id="legendCard" style="display:none;">
            <span><md-icon style="vertical-align:middle; font-size:18px;">info</md-icon>
                Estado de cada lechería:</span>
            <span class="estado-pill estado-ver"><md-icon>verified</md-icon>Verificado</span>
            <span style="opacity:.8;">VB del promotor y supervisor.</span>
            <span class="estado-pill estado-cap"><md-icon>edit_note</md-icon>Capturado</span>
            <span style="opacity:.8;">Promotor envió, falta tu VB.</span>
            <span class="estado-pill estado-est"><md-icon>auto_graph</md-icon>Estimado</span>
            <span style="opacity:.8;">Calculado del inventario, aún no confiable.</span>
            <span class="falta-pill">FALTA</span>
            <span style="opacity:.8;">Sin datos.</span>
        </div>

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
        const selAlmacen    = document.getElementById('selAlmacen');
        const selRessurti   = document.getElementById('selRessurti');
        const contenedor    = document.getElementById('contenedorTabla');
        const btnPDF        = document.getElementById('btnGenerarPDF');
        const grandTotal    = document.getElementById('grandTotal');
        const totalGeneralEl= document.getElementById('totalGeneralVal');
        const totalLechEl   = document.getElementById('totalLechVal');
        const warnPromos    = document.getElementById('warnPromos');
        const pills         = document.querySelectorAll('.precio-pill-btn');

        let precioActivo = 'todos';
        let ultimoConsolidado = null;
        const supervisorNombre = <?= json_encode($nombre_usuario, JSON_UNESCAPED_UNICODE) ?>;

        // Default: mes actual, pill "Todos" activo
        selMes.value = String(new Date().getMonth() + 1);

        pills.forEach(p => p.addEventListener('click', () => {
            pills.forEach(x => x.classList.remove('active'));
            p.classList.add('active');
            precioActivo = p.dataset.precio;
            cargar();
        }));
        selMes.addEventListener('change', () => { limpiarCatalogos(); cargar(); });
        inputAnio.addEventListener('change', () => { limpiarCatalogos(); cargar(); });
        selAlmacen.addEventListener('change', cargar);
        selRessurti.addEventListener('change', cargar);

        function limpiarCatalogos() {
            while (selAlmacen.children.length  > 1) selAlmacen.removeChild(selAlmacen.lastChild);
            while (selRessurti.children.length > 1) selRessurti.removeChild(selRessurti.lastChild);
        }

        function llenarSelect(sel, items) {
            const val = sel.value;
            while (sel.children.length > 1) sel.removeChild(sel.lastChild);
            items.forEach(it => {
                const o = document.createElement('md-select-option');
                o.value = it.value;
                o.innerHTML = `<div slot="headline">${it.label}</div>`;
                sel.appendChild(o);
            });
            // Restaurar valor si sigue siendo válido
            if (items.some(i => String(i.value) === String(val))) sel.value = val;
        }

        function skeleton() {
            const placeholder = Array(4).fill(0).map(() => `
                <div class="almacen-card">
                    <div class="almacen-titulo">
                        <md-icon>warehouse</md-icon>
                        <div class="skel-bar" style="flex:1; max-width:160px;"></div>
                    </div>
                    <div style="padding:14px;">
                        ${Array(3).fill('<div class="skel-bar" style="margin-bottom:8px;"></div>').join('')}
                    </div>
                </div>`).join('');
            contenedor.innerHTML = `<div class="almacenes-grid">${placeholder}</div>`;
        }

        function fmtNum(n) { return Number(n || 0).toLocaleString('es-MX'); }

        function tiendaCell(l) {
            // "DM" se muestra como pildora; cualquier otro num_tienda como texto plano.
            if (l.num_tienda === 'DM') return `<span class="dm-tag">DM</span>`;
            return l.num_tienda || '';
        }

        function distribCell(l) {
            if (l.num_tienda === 'DM') return `<span class="dm-tag">DM</span>`;
            if (l.ressurti_label) return `<span style="font-size:0.78rem;opacity:.8;">${l.ressurti_label}</span>`;
            return '—';
        }

        function estadoPill(l) {
            if (l.estado === 'verificado') return `<span class="estado-pill estado-ver"><md-icon>verified</md-icon>Verificado</span>`;
            if (l.estado === 'capturado')  return `<span class="estado-pill estado-cap"><md-icon>edit_note</md-icon>Capturado</span>`;
            if (l.estado === 'estimado')   return `<span class="estado-pill estado-est"><md-icon>auto_graph</md-icon>Estimado</span>`;
            return `<span class="falta-pill">FALTA</span>`;
        }
        function reqCell(l) {
            if (!l.capturado) return `<span class="falta-pill">FALTA</span>`;
            // Capturado (promotor envió) → editable por supervisor
            if (l.estado === 'capturado') {
                return `<input type="number" class="req-input" min="0"
                         value="${l.requerimiento || 0}"
                         data-clave="${l.punto_venta}"
                         data-original="${l.requerimiento || 0}"
                         onchange="editarReq(this)">`;
            }
            // Estimado → solo lectura (dato del sistema, no editable)
            if (l.es_estimado) return `<span class="req-val estimado">${fmtNum(l.requerimiento)} *</span>`;
            // Verificado → solo lectura
            return `<span class="req-val">${fmtNum(l.requerimiento)}</span>`;
        }

        function pintarAlmacen(b) {
            const hayMezcla = b.lecherias.some(l => l.precio_label !== b.lecherias[0].precio_label);
            const filas = b.lecherias.map(l => {
                const precioCol = hayMezcla ? `<td>${l.precio_label || ''}</td>` : '';
                const trCls = l.capturado ? (l.es_estimado ? 'estimada' : '') : 'faltante';
                return `<tr class="${trCls}">
                    <td>${l.punto_venta}</td>
                    <td>${tiendaCell(l)}</td>
                    <td>${distribCell(l)}</td>
                    <td>${estadoPill(l)}</td>
                    ${precioCol}
                    <td>${reqCell(l)}</td>
                </tr>`;
            }).join('');

            const precioTh = hayMezcla ? '<th>Precio</th>' : '';
            const span = hayMezcla ? 4 : 3;
            const verCount = b.verificadas || 0;
            const capCount = (b.capturadas || 0) - verCount - (b.estimadas || 0);
            const puedeVerificar = capCount > 0;
            const metaTxt = `${verCount}<md-icon style="font-size:14px;width:14px;height:14px;vertical-align:middle;">verified</md-icon> / ${b.capturadas}/${b.total}`;

            return `
                <div class="almacen-card">
                    <div class="almacen-titulo">
                        <md-icon>warehouse</md-icon>
                        <span>ALMACÉN ${b.almacen}</span>
                        <span class="meta">${metaTxt}</span>
                        <span class="almacen-acciones">
                            ${puedeVerificar ? `
                              <md-text-button class="btn-verificar" data-almacen="${b.almacen}">
                                <md-icon slot="icon">verified</md-icon>VB
                              </md-text-button>` : ''}
                        </span>
                    </div>
                    <div class="tabla-scroll">
                    <table class="reporte-table">
                        <thead>
                            <tr>
                                <th>Punto de Venta</th>
                                <th>Tienda</th>
                                <th>Distribuidor</th>
                                <th>Estado</th>
                                ${precioTh}
                                <th>Req.</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${filas}
                            <tr class="subtotal-row">
                                <td colspan="${span}" style="text-align:right;">SUBTOTAL =</td>
                                <td>${fmtNum(b.subtotal)}</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>`;
        }

        function pintarResumen(r) {
            if (!r) {
                document.getElementById('resumenCard').style.display = 'none';
                return;
            }
            document.getElementById('resPromotores').textContent = fmtNum(r.promotores);
            document.getElementById('resLechTotal').textContent  = fmtNum(r.lecherias_total);
            document.getElementById('resLech450').textContent    = fmtNum(r.lecherias_450);
            document.getElementById('resLech650').textContent    = fmtNum(r.lecherias_650);
            document.getElementById('resumenCard').style.display = 'block';
        }

        function pintar(data) {
            ultimoConsolidado = data;

            // Poblar catálogos con lo que devolvió la API
            if (data.cat_almacenes) {
                llenarSelect(selAlmacen, data.cat_almacenes.map(a => ({value: a, label: a})));
            }
            if (data.cat_ressurti) {
                llenarSelect(selRessurti, data.cat_ressurti.map(r => ({value: r.value, label: r.label})));
            }

            if (!data.almacenes || data.almacenes.length === 0) {
                contenedor.innerHTML = `
                    <div class="md3-card" style="text-align:center; padding:24px; color:var(--md-sys-color-on-surface-variant);">
                        <md-icon style="font-size:36px; color:var(--md-sys-color-tertiary);">inbox</md-icon>
                        <p style="margin-top:8px;">Sin resultados para los filtros seleccionados.</p>
                    </div>`;
                grandTotal.style.display = 'none';
                pintarResumen(data.resumen);
                return;
            }

            contenedor.innerHTML =
                `<div class="almacenes-grid">${data.almacenes.map(pintarAlmacen).join('')}</div>`;

            document.getElementById('legendCard').style.display = 'flex';
            totalGeneralEl.textContent = fmtNum(data.total_general);
            totalLechEl.textContent =
                `(${data.total_capturadas}/${data.total_lecherias} lecherías)`;
            grandTotal.style.display = 'flex';

            // Engancha botones VB recién creados
            contenedor.querySelectorAll('.btn-verificar').forEach(btn => {
                btn.addEventListener('click', () => verificarAlmacen(btn.dataset.almacen));
            });

            pintarResumen(data.resumen);
        }

        async function verificarAlmacen(almacen) {
            if (!confirm(`¿Confirmas que los requerimientos del almacén ${almacen} están correctos? Pasarán a "Verificado" y serán visibles como datos confiables para distribución.`)) return;
            try {
                const r = await fetch('verificar_requerimiento.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        mes:  Number(selMes.value),
                        anio: Number(inputAnio.value),
                        almacen,
                    }),
                });
                const j = await r.json();
                if (j.status === 'success') {
                    cargar();
                } else {
                    alert(j.message || 'No se pudo verificar.');
                }
            } catch (e) {
                alert('Error de conexión: ' + e.message);
            }
        }

        async function cargar() {
            const mes  = selMes.value;
            const anio = inputAnio.value;
            if (!mes || !anio) return;

            skeleton();
            grandTotal.style.display = 'none';
            try {
                const p = new URLSearchParams({
                    mes, anio,
                    precio:   precioActivo,
                    almacen:  selAlmacen.value,
                    ressurti: selRessurti.value,
                });
                const r = await fetch(`api_requerimiento_dotacion.php?${p}`);
                const j = await r.json();
                if (j.status !== 'success') {
                    contenedor.innerHTML = `<p style="color:var(--md-sys-color-error); padding:16px;">${j.message || 'Error al cargar.'}</p>`;
                    return;
                }
                pintar(j);
                warnPromos.style.display = 'none';
            } catch (e) {
                contenedor.innerHTML = `<p style="color:var(--md-sys-color-error); padding:16px;">Error de conexión: ${e.message}</p>`;
            }
        }

        btnPDF.addEventListener('click', async () => {
            if (!ultimoConsolidado || !ultimoConsolidado.almacenes?.length) {
                alert('No hay datos para imprimir aún. Selecciona mes/año y precio primero.');
                return;
            }
            const almLabel  = selAlmacen.value  || 'Todos';
            const respLabel = selRessurti.options[selRessurti.selectedIndex]?.text || 'Todos';
            const payload = {
                mes:        Number(selMes.value),
                anio:       Number(inputAnio.value),
                precio:     precioActivo,
                zona:       '',
                esquema:    '',
                supervisor: ultimoConsolidado.supervisor?.nombre || supervisorNombre,
                filtro_almacen:      almLabel,
                filtro_distribuidor: respLabel,
                almacenes:  ultimoConsolidado.almacenes,
            };
            const resp = await fetch('generar_pdf_requerimiento_supervisor.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            if (!resp.ok) {
                alert('No se pudo generar el PDF.');
                return;
            }
            const blob = await resp.blob();
            const url = URL.createObjectURL(blob);
            window.open(url, '_blank');
        });

        function abrirMenu(id) {
            document.querySelectorAll('md-menu').forEach(m => { if (m.id !== id) m.open = false; });
            const menu = document.getElementById(id);
            if (menu) menu.open = !menu.open;
        }
        function toggleDrawer() {
            const d = document.getElementById('mobile-drawer');
            const s = document.getElementById('drawer-scrim');
            if (d) d.classList.toggle('open');
            if (s) s.classList.toggle('open');
        }

        async function editarReq(input) {
            const clave   = input.dataset.clave;
            const nuevo   = parseInt(input.value, 10) || 0;
            const original= parseInt(input.dataset.original, 10) || 0;
            if (nuevo === original) return;
            if (nuevo < 0) { input.value = original; return; }

            input.classList.add('guardando');
            try {
                const r = await fetch('editar_requerimiento.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({
                        mes:      Number(selMes.value),
                        anio:     Number(inputAnio.value),
                        clave,
                        cantidad: nuevo,
                    }),
                });
                const j = await r.json();
                if (j.status === 'success') {
                    input.dataset.original = nuevo;
                    input.classList.add('ok');
                    setTimeout(() => input.classList.remove('ok'), 1500);
                    // Recalcular subtotales sin recargar todo
                    recalcSubtotales();
                } else {
                    alert(j.message || 'No se pudo guardar.');
                    input.value = original;
                    input.classList.add('err');
                    setTimeout(() => input.classList.remove('err'), 2000);
                }
            } catch (e) {
                alert('Error de conexión: ' + e.message);
                input.value = original;
            }
            input.classList.remove('guardando');
        }

        function recalcSubtotales() {
            let totalGen = 0;
            contenedor.querySelectorAll('.almacen-card').forEach(card => {
                let sub = 0;
                card.querySelectorAll('.reporte-table tbody tr:not(.subtotal-row)').forEach(tr => {
                    const inp = tr.querySelector('.req-input');
                    const reqSpan = tr.querySelector('.req-val');
                    if (inp) sub += parseInt(inp.value, 10) || 0;
                    else if (reqSpan) {
                        const txt = reqSpan.textContent.replace(/[^0-9]/g, '');
                        sub += parseInt(txt, 10) || 0;
                    }
                });
                const subTd = card.querySelector('.subtotal-row td:last-child');
                if (subTd) subTd.textContent = fmtNum(sub);
                totalGen += sub;
            });
            totalGeneralEl.textContent = fmtNum(totalGen);
        }

        cargar();
    </script>
</body>
</html>
