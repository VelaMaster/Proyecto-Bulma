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
    <title>Editar Inventario Mensual - Promotor</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../main_md3.css">
    <link rel="stylesheet" href="../estilos/generarInventarioMensual.css">
    <link rel="stylesheet" href="../estilos/consultarInventarioMensual.css">
    <link rel="stylesheet" href="../estilos/editarinventarioMensual.css">
    <script type="importmap">{"imports": {"@material/web/": "https://esm.run/@material/web/"}}</script>
    <script type="module">import '@material/web/all.js';</script>
</head>
<body>
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
            <span class="material-symbols-outlined" style="font-size:18px; color:var(--md-sys-color-on-surface-variant);">calendar_month</span>
            <label for="filtroFecha">Filtrar por mes:</label>
            <input class="md3-input md3-input-sm" type="month" id="filtroFecha" style="max-width:180px; padding:6px 10px;">
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
        <md-text-button id="btnCambiarLecheria" style="margin-left:auto;">
            <md-icon slot="icon">swap_horiz</md-icon>Cambiar lechería
        </md-text-button>
    </div>

    <input type="hidden" id="inventario_id" value="">

    <!-- GRID DE LECHERÍAS -->
    <div id="seccionGrid">
        <div class="form-section" style="margin-bottom:24px;">
            <div class="section-header">
                <div class="section-badge">
                    <span class="material-symbols-outlined" style="font-size:17px;">edit_note</span>
                </div>
                <h2 class="section-title">Selecciona la lechería a editar</h2>
            </div>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon"><span class="material-symbols-outlined">store</span></div>
                <div class="stat-info">
                    <div class="stat-value" id="statTotal">—</div>
                    <div class="stat-label">Lecherías asignadas</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><span class="material-symbols-outlined">edit_document</span></div>
                <div class="stat-info">
                    <div class="stat-value" id="statConInv">—</div>
                    <div class="stat-label">Con inventarios editables</div>
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

        <div class="filter-bar">
            <div class="input-with-icon" style="position:relative; max-width:420px; flex:1;">
                <span class="material-symbols-outlined input-icon">search</span>
                <input class="md3-input" type="text" id="inputFiltro"
                       placeholder="Filtrar por nombre o número...">
            </div>
            <span class="filter-count" id="filterCount"></span>
        </div>

        <div class="lecherias-grid" id="lecherasGrid">
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
    </div><!-- /seccionGrid -->

    <!-- FORMULARIO PRINCIPAL -->
    <div id="formularioPrincipal" style="display:none; opacity:0;">

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
                                <td><input type="number" id="inv_ini_caja"    class="md3-input md3-input-sm" readonly></td>
                                <td><input type="number" id="abasto_caja"     class="md3-input md3-input-sm" readonly></td>
                                <td><input type="number" id="venta_caja"      class="md3-input md3-input-sm"></td>
                                <td><input type="number" id="litros_reg_caja" class="md3-input md3-input-sm"></td>
                                <td><input type="number" id="dif_caja"        class="md3-input md3-input-sm" readonly></td>
                                <td><input type="number" id="inv_fin_caja"    class="md3-input md3-input-sm" readonly></td>
                            </tr>
                            <tr>
                                <td>Sobres</td>
                                <td><input type="number" id="inv_ini_sobres"    class="md3-input md3-input-sm" readonly></td>
                                <td><input type="number" id="abasto_sobres"     class="md3-input md3-input-sm" readonly></td>
                                <td><input type="number" id="venta_sobres"      class="md3-input md3-input-sm"></td>
                                <td><input type="number" id="litros_reg_sobres" class="md3-input md3-input-sm"></td>
                                <td><input type="number" id="dif_sobres"        class="md3-input md3-input-sm" readonly></td>
                                <td><input type="number" id="inv_fin_sobres"    class="md3-input md3-input-sm" readonly></td>
                            </tr>
                            <tr>
                                <td>Total en litros</td>
                                <td><input type="number" id="inv_ini_litros"    class="md3-input md3-input-sm" readonly></td>
                                <td><input type="number" id="abasto_litros"     class="md3-input md3-input-sm" readonly></td>
                                <td><input type="number" id="venta_litros"      class="md3-input md3-input-sm"></td>
                                <td><input type="number" id="litros_reg_litros" class="md3-input md3-input-sm"></td>
                                <td><input type="number" id="dif_litros"        class="md3-input md3-input-sm" readonly></td>
                                <td><input type="number" id="inv_fin_litros"    class="md3-input md3-input-sm" readonly></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

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
                                <td><input type="number" id="surt_cajas"     class="md3-input md3-input-sm"></td>
                                <td><input type="number" id="surt_litros"    class="md3-input md3-input-sm"></td>
                                <td><input type="text"   id="surt_factura"   class="md3-input md3-input-sm"></td>
                                <td><input type="date"   id="surt_caducidad" class="md3-input md3-input-sm"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="section-header">
                <div class="section-badge">III</div>
                <h2 class="section-title">Cobertura social y dotación</h2>
            </div>
            <div class="md3-card">
                <div class="form-grid fg-4">
                    <div class="field-group">
                        <label class="field-label">Número de Hogares</label>
                        <input class="md3-input" type="text" id="campoHogares" readonly>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Menores de 12 años</label>
                        <input class="md3-input" type="text" id="campoMenores" readonly>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Mayores de 12 años</label>
                        <input class="md3-input" type="text" id="campoMayores" readonly>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Litros al mes</label>
                        <input class="md3-input" type="text" id="campoDotacion" readonly>
                    </div>
                </div>
            </div>
        </div>

        <div class="save-actions">
            <button id="btnActualizarPDF" class="md3-filled-btn">
                <span class="material-symbols-outlined" style="font-size:20px;">update</span>
                <span class="btn-label">Actualizar datos y PDF</span>
            </button>
        </div>

    </div><!-- /formularioPrincipal -->

</main>
<script src="../js/temas_md3.js"></script>
<script src="../js/promotores.js"></script>
<script src="../js/editar_inventario.js"></script>

<script>
/* Menú y drawer — se quedan inline porque son globales de la barra de nav */
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
</script>

</body>
</html>