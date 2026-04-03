<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['usuario']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'promotor') {
    header("Location: ../iniciosesionPromotor.php");
    exit();
}
$nombre_usuario = $_SESSION['nombre'] ?? $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Inventario Mensual - Promotor</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../main_md3.css">
    <link rel="stylesheet" href="../estilos/generarInventarioMensual.css">
    <link rel="stylesheet" href="../estilos/editarinventarioMensual.css">
    <script type="importmap">{"imports": {"@material/web/": "https://esm.run/@material/web/"}}</script>
    <script type="module">import '@material/web/all.js';</script>

</head>
<body>

<!-- ══ TOP BAR ══════════════════════════════════════════════════════════ -->
<header class="md3-top-app-bar">
    <div class="app-bar-start">
        <md-icon-button class="mobile-menu-btn" onclick="toggleDrawer()">
            <md-icon>menu</md-icon>
        </md-icon-button>
        <div class="app-brand"><span>Editar Inventario Mensual</span></div>
    </div>
    <div class="app-bar-end">
        <div class="desktop-nav">
            <md-text-button href="inicio.php">
                <md-icon slot="icon">home</md-icon>Inicio
            </md-text-button>
            <div style="position: relative;">
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
            <div style="position: relative;">
                <md-text-button id="btn-rep" onclick="abrirMenu('menu-rep')">
                    Reporte lecherías <md-icon slot="icon">arrow_drop_down</md-icon>
                </md-text-button>
                <md-menu id="menu-rep" anchor="btn-rep">
                    <md-menu-item href="#"><div slot="headline">Generar</div></md-menu-item>
                    <md-menu-item href="#"><div slot="headline">Consultar</div></md-menu-item>
                </md-menu>
            </div>
            <div style="position: relative;">
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

<!-- ══ DRAWER MÓVIL ══════════════════════════════════════════════════════ -->
<div class="md3-drawer-scrim" id="drawer-scrim" onclick="toggleDrawer()"></div>
<aside class="md3-drawer" id="mobile-drawer">
    <div style="padding-top:24px;"></div>
    <md-list style="background:transparent;">
        <div class="drawer-section-title">Inventario mensual</div>
        <md-list-item href="generarinventarioMensual.php"><div slot="headline">Generar</div></md-list-item>
        <md-list-item href="editarinventarioMensual.php"><div slot="headline">Editar</div></md-list-item>
        <md-list-item href="consultarinventarioMensual.php"><div slot="headline">Consultar</div></md-list-item>
        <md-divider style="margin:8px 0;"></md-divider>
        <div class="drawer-section-title">Reporte lecherías</div>
        <md-list-item href="#"><div slot="headline">Generar</div></md-list-item>
        <md-list-item href="#"><div slot="headline">Consultar</div></md-list-item>
        <md-divider style="margin:8px 0;"></md-divider>
        <div class="drawer-section-title">Requerimiento</div>
        <md-list-item href="#"><div slot="headline">Generar</div></md-list-item>
        <md-list-item href="#"><div slot="headline">Consultar</div></md-list-item>
        <md-list-item href="#"><div slot="headline">Enviar a supervisor</div></md-list-item>
    </md-list>
</aside>

<!-- ══ MODAL DE REGISTROS ════════════════════════════════════════════════ -->
<div class="modal-backdrop" id="modalRegistros">
    <div class="modal-card">

        <div class="modal-header">
            <div class="modal-header-info">
                <p class="modal-title" id="modalTitulo">Registros de la lechería</p>
                <p class="modal-subtitle" id="modalSubtitulo">Selecciona el inventario a editar</p>
            </div>
            <button class="modal-close-btn" id="btnCerrarModal" title="Cerrar">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="modal-filter">
            <span class="material-symbols-outlined" style="font-size:18px; color:var(--md3-on-surface-variant,#aaa);">calendar_month</span>
            <label for="filtroFecha">Filtrar por mes:</label>
            <input class="md3-input md3-input-sm" type="month" id="filtroFecha"
                   style="max-width:180px; padding:6px 10px;">
            <button class="modal-filter-clear" id="btnLimpiarFiltro">Limpiar</button>
        </div>

        <div class="modal-body" id="modalBody">
            <div class="modal-loading">
                <span class="material-symbols-outlined" style="font-size:28px;">hourglass_empty</span>
                <p>Cargando registros...</p>
            </div>
        </div>
    </div>
</div>

<!-- ══ MAIN ══════════════════════════════════════════════════════════════ -->
<main class="panel-content">

    <div class="edit-banner" id="bannerEdicion" style="display:none;">
        <span class="material-symbols-outlined">edit_note</span>
        <span>Modo edición activo — al guardar, los datos y el PDF serán actualizados.</span>
    </div>

    <input type="hidden" id="inventario_id" value="">

    <!-- ── BUSCADOR DE LECHERÍA ───────────────────────────────────── -->
    <div class="form-section">
        <div class="section-header">
            <div class="section-badge">
                <span class="material-symbols-outlined" style="font-size:17px;">manage_search</span>
            </div>
            <h2 class="section-title">Buscar lechería</h2>
        </div>
        <div class="md3-card">
            <div class="form-grid fg-1">
                <div class="field-group">
                    <label class="field-label" for="inputLecheriaEditar">
                        Buscar por número o nombre de lechería
                    </label>
                    <div class="autocomplete-wrapper" style="position:relative;">
                        <div class="input-with-icon">
                            <span class="material-symbols-outlined input-icon">search</span>
                            <input class="md3-input" type="text" id="inputLecheriaEditar"
                                   autocomplete="off" placeholder="Ej: 12345 o TIENDA COMUNITARIA...">
                        </div>
                        <div id="dropdown-lecheria-editar">
                            <div id="lista-lecheria-editar"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── FORMULARIO PRINCIPAL ──────────────────────────────────── -->
    <div id="formularioPrincipal" style="display:none; opacity:0;">

        <!-- SECCIÓN: DATOS GENERALES -->
        <div class="form-section">
            <div class="section-header">
                <div class="section-badge">
                    <span class="material-symbols-outlined" style="font-size:17px;">description</span>
                </div>
                <h2 class="section-title">Datos generales del inventario</h2>
            </div>
            <div class="md3-card">
                <div class="form-grid fg-3">
                    <div class="field-group">
                        <label class="field-label">Fecha</label>
                        <input class="md3-input" type="date" id="inputFecha" required>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Clave del punto de venta</label>
                        <input class="md3-input" type="text" id="inputLecheria" readonly>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Clave de la tienda</label>
                        <input class="md3-input" type="text" id="campoTienda" readonly>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Almacén que surte</label>
                        <input class="md3-input" type="text" id="campoAlmacen" readonly>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Municipio</label>
                        <input class="md3-input" type="text" id="campoMunicipio" readonly>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Comunidad</label>
                        <input class="md3-input" type="text" id="campoComunidad" readonly>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN I: EXISTENCIA DE LECHE -->
        <div class="form-section">
            <div class="section-header">
                <div class="section-badge">I</div>
                <h2 class="section-title">Existencia de Leche</h2>
            </div>
            <div class="md3-card">
                <div class="md3-table-wrapper">
                    <table class="md3-table">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Inventario inicial</th>
                                <th>Abasto total en el mes</th>
                                <th>Ventas real del mes</th>
                                <th>Litros registrados</th>
                                <th>Diferencia (reg. vs real)</th>
                                <th>Inventario final del mes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Caja</td>
                                <td><input type="number" id="inv_ini_caja"    class="md3-input md3-input-sm" readonly placeholder="0"></td>
                                <td><input type="number" id="abasto_caja"     class="md3-input md3-input-sm" readonly placeholder="0"></td>
                                <td><input type="number" id="venta_caja"      class="md3-input md3-input-sm" placeholder="0"></td>
                                <td><input type="number" id="litros_reg_caja" class="md3-input md3-input-sm" placeholder="0"></td>
                                <td><input type="number" id="dif_caja"        class="md3-input md3-input-sm" readonly placeholder="0"></td>
                                <td><input type="number" id="inv_fin_caja"    class="md3-input md3-input-sm" readonly placeholder="0"></td>
                            </tr>
                            <tr>
                                <td>Sobres</td>
                                <td><input type="number" id="inv_ini_sobres"    class="md3-input md3-input-sm" readonly placeholder="0"></td>
                                <td><input type="number" id="abasto_sobres"     class="md3-input md3-input-sm" readonly placeholder="0"></td>
                                <td><input type="number" id="venta_sobres"      class="md3-input md3-input-sm" placeholder="0"></td>
                                <td><input type="number" id="litros_reg_sobres" class="md3-input md3-input-sm" placeholder="0"></td>
                                <td><input type="number" id="dif_sobres"        class="md3-input md3-input-sm" readonly placeholder="0"></td>
                                <td><input type="number" id="inv_fin_sobres"    class="md3-input md3-input-sm" readonly placeholder="0"></td>
                            </tr>
                            <tr>
                                <td>Total en litros</td>
                                <td><input type="number" id="inv_ini_litros"    class="md3-input md3-input-sm" readonly placeholder="0"></td>
                                <td><input type="number" id="abasto_litros"     class="md3-input md3-input-sm" readonly placeholder="0"></td>
                                <td><input type="number" id="venta_litros"      class="md3-input md3-input-sm" placeholder="0"></td>
                                <td><input type="number" id="litros_reg_litros" class="md3-input md3-input-sm" placeholder="0"></td>
                                <td><input type="number" id="dif_litros"        class="md3-input md3-input-sm" readonly placeholder="0"></td>
                                <td><input type="number" id="inv_fin_litros"    class="md3-input md3-input-sm" readonly placeholder="0"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- SECCIÓN II: SURTIMIENTOS -->
        <div class="form-section">
            <div class="section-header">
                <div class="section-badge">II</div>
                <h2 class="section-title">Surtimientos sugeridos</h2>
            </div>
            <div class="md3-card">
                <div class="md3-table-wrapper">
                    <table class="md3-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Cajas</th>
                                <th>Litros</th>
                                <th>Facturas</th>
                                <th>Caducidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><input type="date"   id="surt_fecha"     class="md3-input md3-input-sm"></td>
                                <td><input type="number" id="surt_cajas"     class="md3-input md3-input-sm" placeholder="0"></td>
                                <td><input type="number" id="surt_litros"    class="md3-input md3-input-sm" placeholder="0"></td>
                                <td><input type="text"   id="surt_factura"   class="md3-input md3-input-sm" placeholder="N° factura"></td>
                                <td><input type="date"   id="surt_caducidad" class="md3-input md3-input-sm"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- SECCIÓN III: COBERTURA SOCIAL -->
        <div class="form-section">
            <div class="section-header">
                <div class="section-badge">III</div>
                <h2 class="section-title">Cobertura social y dotación</h2>
            </div>
            <div class="md3-card">
                <div class="form-grid fg-4">
                    <div class="field-group">
                        <label class="field-label">Número de Hogares</label>
                        <input class="md3-input" type="text" id="campoHogares" readonly placeholder="0">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Menores de 12 años</label>
                        <input class="md3-input" type="text" id="campoMenores" readonly placeholder="0">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Mayores de 12 años</label>
                        <input class="md3-input" type="text" id="campoMayores" readonly placeholder="0">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Litros al mes</label>
                        <input class="md3-input" type="text" id="campoDotacion" readonly placeholder="0">
                    </div>
                </div>
            </div>
        </div>

        <!-- BOTÓN -->
        <div class="save-actions">
            <button id="btnActualizarPDF" class="md3-filled-btn">
                <span class="material-symbols-outlined btn-label" style="font-size:20px;">update</span>
                <span class="btn-label">Actualizar datos y PDF</span>
            </button>
        </div>

    </div><!-- /formularioPrincipal -->
</main>

<script src="../js/temas_md3.js"></script>
<script src="../js/promotores.js"></script>

<script>
/* ══ MENÚ Y DRAWER ═══════════════════════════════════════════════════ */
function abrirMenu(id) {
    document.querySelectorAll('md-menu').forEach(m => { if (m.id !== id) m.open = false; });
    document.getElementById(id).open = !document.getElementById(id).open;
}
function toggleDrawer() {
    document.getElementById('mobile-drawer').classList.toggle('open');
    document.getElementById('drawer-scrim').classList.toggle('open');
}
document.addEventListener('click', (e) => {
    if (!e.target.closest('md-menu') && !e.target.closest('md-text-button'))
        document.querySelectorAll('md-menu').forEach(m => m.open = false);
});

/* ══ LÓGICA PRINCIPAL ════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {

    /* ── referencias DOM ── */
    const inputEditar  = document.getElementById('inputLecheriaEditar');
    const dropdown     = document.getElementById('dropdown-lecheria-editar');
    const listaDropdown= document.getElementById('lista-lecheria-editar');
    const modal        = document.getElementById('modalRegistros');
    const modalTitulo  = document.getElementById('modalTitulo');
    const modalSub     = document.getElementById('modalSubtitulo');
    const modalBody    = document.getElementById('modalBody');
    const filtroFecha  = document.getElementById('filtroFecha');
    const btnCerrar    = document.getElementById('btnCerrarModal');
    const btnLimpiar   = document.getElementById('btnLimpiarFiltro');
    const formPrincipal= document.getElementById('formularioPrincipal');
    const bannerEdicion= document.getElementById('bannerEdicion');

    let timeout;
    let claveActual = '';   // lechería seleccionada en el dropdown

    /* ════════════════════════════════════════════════════════════════
       1. AUTOCOMPLETE — reutiliza buscarLecheria.php exactamente
          igual que en generarInventarioMensual
    ════════════════════════════════════════════════════════════════ */
    inputEditar.addEventListener('input', function () {
        const texto = this.value.trim();
        clearTimeout(timeout);
        if (texto.length < 1) { dropdown.style.display = 'none'; return; }

        timeout = setTimeout(() => {
            fetch('buscarLecheria.php?q=' + encodeURIComponent(texto))
                .then(r => r.json())
                .then(datos => {
                    listaDropdown.innerHTML = '';
                    if (!Array.isArray(datos) || datos.length === 0) {
                        dropdown.style.display = 'none'; return;
                    }

                    datos.forEach(item => {
                        const opt = document.createElement('a');
                        opt.className = 'dropdown-item';
                        opt.href = '#';
                        opt.innerHTML = `
                            <strong>${item.LECHER}</strong> &ndash; ${item.NOMBRELECH}<br>
                            <small>${item.MUNICIPIO_NOMBRE ?? ''} &ndash; ${item.LOCALIDAD_DESC ?? ''}</small>
                        `;

                        opt.addEventListener('mousedown', (e) => {
                            e.preventDefault();
                            claveActual = item.LECHER;
                            inputEditar.value = `${item.LECHER} — ${item.NOMBRELECH}`;
                            dropdown.style.display = 'none';
                            // Abrir modal con los inventarios de esta lechería
                            abrirModal(item.LECHER, item.NOMBRELECH);
                        });

                        listaDropdown.appendChild(opt);
                    });

                    dropdown.style.display = 'block';
                })
                .catch(err => console.error('Error búsqueda lechería:', err));
        }, 300);
    });

    // Cerrar dropdown
    document.addEventListener('click', (e) => {
        if (!inputEditar.contains(e.target) && !dropdown.contains(e.target))
            dropdown.style.display = 'none';
    });
    inputEditar.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') dropdown.style.display = 'none';
    });

    /* ════════════════════════════════════════════════════════════════
       2. MODAL
    ════════════════════════════════════════════════════════════════ */
    function abrirModal(clave, nombre) {
        modalTitulo.textContent = `Lechería ${clave} — ${nombre}`;
        modalSub.textContent    = 'Selecciona el inventario mensual que deseas editar';
        filtroFecha.value       = '';
        modal.classList.add('open');
        cargarRegistros(clave, '');
    }

    function cerrarModal() {
        modal.classList.remove('open');
    }

    btnCerrar.addEventListener('click', cerrarModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) cerrarModal(); });

    /* ── filtro de fecha ── */
    filtroFecha.addEventListener('change', () => {
        cargarRegistros(claveActual, filtroFecha.value);
    });
    btnLimpiar.addEventListener('click', () => {
        filtroFecha.value = '';
        cargarRegistros(claveActual, '');
    });

    /* ── cargar registros del modal ── */
    function cargarRegistros(clave, mesISO) {
        modalBody.innerHTML = `
            <div class="modal-loading">
                <span class="material-symbols-outlined" style="font-size:32px;opacity:.5;">hourglass_empty</span>
                <p>Buscando inventarios...</p>
            </div>`;

        // mesISO viene como "2026-03" (input[type=month])
        // el endpoint acepta substring de fecha, ej "2026-03"
        const params = new URLSearchParams({ clave });
        if (mesISO) params.append('fecha', mesISO);

        fetch('obtener_inventarios_por_lecheria.php?' + params.toString())
            .then(r => r.json())
            .then(rows => {
                if (!Array.isArray(rows) || rows.length === 0) {
                    modalBody.innerHTML = `
                        <div class="modal-empty">
                            <span class="material-symbols-outlined">inventory_2</span>
                            <p>No se encontraron inventarios${mesISO ? ' para ese mes' : ''}.</p>
                        </div>`;
                    return;
                }

                modalBody.innerHTML = '';
                rows.forEach(inv => {
                    const esEditado = (inv.ESTADO ?? '').toLowerCase() === 'editado';
                    const iconCls   = esEditado ? 'icon-editado'  : 'icon-guardado';
                    const pillCls   = esEditado ? 'pill-editado'  : 'pill-guardado';
                    const iconName  = esEditado ? 'edit_document' : 'description';

                    // Formatear fecha legible
                    const fechaFmt = inv.FECHA
                        ? new Date(inv.FECHA + 'T12:00:00').toLocaleDateString('es-MX', {
                            day: '2-digit', month: 'long', year: 'numeric'
                          })
                        : inv.FECHA;

                    const item = document.createElement('div');
                    item.className  = 'inv-item';
                    item.innerHTML  = `
                        <div class="inv-item-icon ${iconCls}">
                            <span class="material-symbols-outlined">${iconName}</span>
                        </div>
                        <div class="inv-item-info">
                            <div class="inv-item-fecha">${fechaFmt}</div>
                            <div class="inv-item-meta">
                                ${inv.MUNICIPIO ?? ''} · ${inv.COMUNIDAD ?? ''}
                                &nbsp;·&nbsp; Inv. final: ${inv.FIN_CAJA ?? 0} cajas / ${inv.FIN_LITROS ?? 0} L
                            </div>
                        </div>
                        <span class="estado-pill ${pillCls}">${inv.ESTADO ?? 'guardado'}</span>
                        <span class="material-symbols-outlined inv-item-arrow">chevron_right</span>
                    `;

                    item.addEventListener('click', () => {
                        cerrarModal();
                        cargarInventarioEnFormulario(inv.ID);
                    });

                    modalBody.appendChild(item);
                });
            })
            .catch(err => {
                console.error(err);
                modalBody.innerHTML = `
                    <div class="modal-empty">
                        <span class="material-symbols-outlined">wifi_off</span>
                        <p>Error de conexión al buscar registros.</p>
                    </div>`;
            });
    }

    /* ════════════════════════════════════════════════════════════════
       3. CARGAR INVENTARIO EN FORMULARIO
    ════════════════════════════════════════════════════════════════ */
    function cargarInventarioEnFormulario(id) {
        fetch('obtener_inventario.php?id=' + id)
            .then(r => r.json())
            .then(res => {
                if (res.status !== 'success') { alert('Error: ' + res.mensaje); return; }

                const inv = res.datos;   // Firebird → claves MAYÚSCULAS

                document.getElementById('inventario_id').value = inv.ID;

                // Datos generales
                document.getElementById('inputFecha').value     = inv.FECHA            ?? '';
                document.getElementById('inputLecheria').value  = inv.CLAVE_LECHERIA   ?? '';
                document.getElementById('campoTienda').value    = inv.CLAVE_TIENDA     ?? '';
                document.getElementById('campoAlmacen').value   = inv.ALMACEN          ?? '';
                document.getElementById('campoMunicipio').value = inv.MUNICIPIO        ?? '';
                document.getElementById('campoComunidad').value = inv.COMUNIDAD        ?? '';

                // Sección I
                document.getElementById('inv_ini_caja').value      = inv.INV_INI_CAJA   ?? 0;
                document.getElementById('abasto_caja').value       = inv.ABASTO_CAJA    ?? 0;
                document.getElementById('venta_caja').value        = inv.VENTA_CAJA     ?? 0;
                document.getElementById('litros_reg_caja').value   = inv.REG_CAJA       ?? 0;
                document.getElementById('dif_caja').value          = inv.DIF_CAJA       ?? 0;
                document.getElementById('inv_fin_caja').value      = inv.FIN_CAJA       ?? 0;

                document.getElementById('inv_ini_sobres').value    = inv.INV_INI_SOBRES ?? 0;
                document.getElementById('abasto_sobres').value     = inv.ABASTO_SOBRES  ?? 0;
                document.getElementById('venta_sobres').value      = inv.VENTA_SOBRES   ?? 0;
                document.getElementById('litros_reg_sobres').value = inv.REG_SOBRES     ?? 0;
                document.getElementById('dif_sobres').value        = inv.DIF_SOBRES     ?? 0;
                document.getElementById('inv_fin_sobres').value    = inv.FIN_SOBRES     ?? 0;

                document.getElementById('inv_ini_litros').value    = inv.INV_INI_LITROS ?? 0;
                document.getElementById('abasto_litros').value     = inv.ABASTO_LITROS  ?? 0;
                document.getElementById('venta_litros').value      = inv.VENTA_LITROS   ?? 0;
                document.getElementById('litros_reg_litros').value = inv.REG_LITROS     ?? 0;
                document.getElementById('dif_litros').value        = inv.DIF_LITROS     ?? 0;
                document.getElementById('inv_fin_litros').value    = inv.FIN_LITROS     ?? 0;

                // Sección II
                document.getElementById('surt_fecha').value     = inv.SURT_FECHA     ?? '';
                document.getElementById('surt_cajas').value     = inv.SURT_CAJAS     ?? 0;
                document.getElementById('surt_litros').value    = inv.SURT_LITROS    ?? 0;
                document.getElementById('surt_factura').value   = inv.SURT_FACTURA   ?? '';
                document.getElementById('surt_caducidad').value = inv.SURT_CADUCIDAD ?? '';

                // Sección III
                document.getElementById('campoHogares').value  = inv.HOGARES  ?? 0;
                document.getElementById('campoMenores').value  = inv.MENORES  ?? 0;
                document.getElementById('campoMayores').value  = inv.MAYORES  ?? 0;
                document.getElementById('campoDotacion').value = inv.DOTACION ?? 0;

                // Mostrar formulario
                bannerEdicion.style.display  = 'flex';
                formPrincipal.style.display  = 'block';
                setTimeout(() => {
                    formPrincipal.style.opacity = '1';
                    formPrincipal.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 60);
            })
            .catch(err => { console.error(err); alert('No se pudo cargar el inventario.'); });
    }

    /* ════════════════════════════════════════════════════════════════
       4. RECOLECTAR Y ENVIAR
    ════════════════════════════════════════════════════════════════ */
    function recolectarDatos() {
        return {
            inventario_id:   document.getElementById('inventario_id').value,
            fecha:           document.getElementById('inputFecha').value,
            lecheria:        document.getElementById('inputLecheria').value,
            tienda:          document.getElementById('campoTienda').value,
            almacen:         document.getElementById('campoAlmacen').value,
            municipio:       document.getElementById('campoMunicipio').value,
            comunidad:       document.getElementById('campoComunidad').value,

            inv_ini_caja:    document.getElementById('inv_ini_caja').value,
            abasto_caja:     document.getElementById('abasto_caja').value,
            venta_caja:      document.getElementById('venta_caja').value,
            reg_caja:        document.getElementById('litros_reg_caja').value,
            dif_caja:        document.getElementById('dif_caja').value,
            fin_caja:        document.getElementById('inv_fin_caja').value,

            inv_ini_sobres:  document.getElementById('inv_ini_sobres').value,
            abasto_sobres:   document.getElementById('abasto_sobres').value,
            venta_sobres:    document.getElementById('venta_sobres').value,
            reg_sobres:      document.getElementById('litros_reg_sobres').value,
            dif_sobres:      document.getElementById('dif_sobres').value,
            fin_sobres:      document.getElementById('inv_fin_sobres').value,

            inv_ini_litros:  document.getElementById('inv_ini_litros').value,
            abasto_litros:   document.getElementById('abasto_litros').value,
            venta_litros:    document.getElementById('venta_litros').value,
            reg_litros:      document.getElementById('litros_reg_litros').value,
            dif_litros:      document.getElementById('dif_litros').value,
            fin_litros:      document.getElementById('inv_fin_litros').value,

            surt_fecha:      document.getElementById('surt_fecha').value,
            surt_cajas:      document.getElementById('surt_cajas').value,
            surt_litros:     document.getElementById('surt_litros').value,
            surt_factura:    document.getElementById('surt_factura').value,
            surt_caducidad:  document.getElementById('surt_caducidad').value,

            hogares:         document.getElementById('campoHogares').value,
            menores:         document.getElementById('campoMenores').value,
            mayores:         document.getElementById('campoMayores').value,
            dotacion:        document.getElementById('campoDotacion').value,
        };
    }

    document.getElementById('btnActualizarPDF').addEventListener('click', async () => {
        if (!document.getElementById('inventario_id').value) {
            alert('No hay inventario cargado.'); return;
        }
        if (!confirm('¿Confirmas actualizar este inventario? El PDF anterior será reemplazado.')) return;

        const btn = document.getElementById('btnActualizarPDF');
        btn.disabled = true;
        btn.classList.add('is-loading');

        const datos = recolectarDatos();

        try {
            // UPDATE en BD
            const r1 = await fetch('actualizar_inventario.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datos)
            });
            const j1 = await r1.json();
            if (j1.status !== 'success') throw new Error(j1.mensaje);

            // Regenerar PDF
            const r2 = await fetch('generar_pdf.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datos)
            });
            if (!r2.ok) throw new Error('Error al generar el PDF.');

            const blob = await r2.blob();
            window.open(window.URL.createObjectURL(blob), '_blank');
            alert('✔ Inventario y PDF actualizados correctamente.');

        } catch (err) {
            console.error(err);
            alert('Error: ' + err.message);
        } finally {
            btn.disabled = false;
            btn.classList.remove('is-loading');
        }
    });

}); // DOMContentLoaded
</script>
</body>
</html>