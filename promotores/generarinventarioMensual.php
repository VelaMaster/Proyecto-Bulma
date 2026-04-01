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
    <title>Generar Inventario Mensual - Promotor</title>

    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../main_md3.css">
    <link rel="stylesheet" href="../estilos/generarInventarioMensual.css">

    <script type="importmap">
    {
        "imports": {
            "@material/web/": "https://esm.run/@material/web/"
        }
    }
    </script>
    <script type="module">
        import '@material/web/all.js';
    </script>
</head>
<body>
<header class="md3-top-app-bar">
    <div class="app-bar-start">
        <md-icon-button class="mobile-menu-btn" onclick="toggleDrawer()">
            <md-icon>menu</md-icon>
        </md-icon-button>
        <div class="app-brand">
            <span>Inventario Mensual de Leche</span>
        </div>
    </div>

    <div class="app-bar-end">
        <div class="desktop-nav">
            <md-text-button href="inicio.php">
                <md-icon slot="icon">home</md-icon>
                Inicio
            </md-text-button>

            <div style="position: relative;">
                <md-text-button id="btn-inv" onclick="abrirMenu('menu-inv')">
                    Inventario mensual
                    <md-icon slot="icon">arrow_drop_down</md-icon>
                </md-text-button>
                <md-menu id="menu-inv" anchor="btn-inv">
                    <md-menu-item href="generarinventarioMensual.php">
                        <div slot="headline">Generar</div>
                        <md-icon slot="start">add_circle</md-icon>
                    </md-menu-item>
                    <md-menu-item href="consultarinventarioMensual.php">
                        <div slot="headline">Consultar</div>
                        <md-icon slot="start">search</md-icon>
                    </md-menu-item>
                </md-menu>
            </div>

            <div style="position: relative;">
                <md-text-button id="btn-rep" onclick="abrirMenu('menu-rep')">
                    Reporte lecherías
                    <md-icon slot="icon">arrow_drop_down</md-icon>
                </md-text-button>
                <md-menu id="menu-rep" anchor="btn-rep">
                    <md-menu-item href="#"><div slot="headline">Generar</div></md-menu-item>
                    <md-menu-item href="#"><div slot="headline">Consultar</div></md-menu-item>
                </md-menu>
            </div>

            <div style="position: relative;">
                <md-text-button id="btn-req" onclick="abrirMenu('menu-req')">
                    Requerimiento
                    <md-icon slot="icon">arrow_drop_down</md-icon>
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

        <md-filled-tonal-button href="../cerrar_sesion.php" style="margin-left: 8px;">Salir</md-filled-tonal-button>
    </div>
</header>

<div class="md3-drawer-scrim" id="drawer-scrim" onclick="toggleDrawer()"></div>
<aside class="md3-drawer" id="mobile-drawer">
    <div style="padding-top: 24px;"></div>
    
    <md-list style="background: transparent;">
        <div class="drawer-section-title">Inventario mensual</div>
        <md-list-item href="generarinventarioMensual.php"><div slot="headline">Generar</div></md-list-item>
        <md-list-item href="consultarinventarioMensual.php"><div slot="headline">Consultar</div></md-list-item>
        <md-divider style="margin: 8px 0;"></md-divider>
        
        <div class="drawer-section-title">Reporte lecherías</div>
        <md-list-item href="#"><div slot="headline">Generar</div></md-list-item>
        <md-list-item href="#"><div slot="headline">Consultar</div></md-list-item>
        <md-divider style="margin: 8px 0;"></md-divider>
        
        <div class="drawer-section-title">Requerimiento</div>
        <md-list-item href="#"><div slot="headline">Generar</div></md-list-item>
        <md-list-item href="#"><div slot="headline">Consultar</div></md-list-item>
        <md-list-item href="#"><div slot="headline">Enviar a supervisor</div></md-list-item>
    </md-list>
</aside>

<main class="panel-content">
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
                    <label class="field-label" for="inputFecha">Fecha</label>
                    <input class="md3-input" type="date" name="fecha" id="inputFecha"
                           value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="field-group">
                    <label class="field-label" for="inputLecheria">Clave del punto de venta (LECHER)</label>
                    <!-- FIX: position:relative es OBLIGATORIO para que el dropdown absolute se posicione aquí -->
                    <div class="autocomplete-wrapper" style="position: relative;">
                        <div class="input-with-icon">
                            <span class="material-symbols-outlined input-icon">search</span>
                            <input class="md3-input" type="text" id="inputLecheria"
                                   name="clave_punto_venta" autocomplete="off"
                                   placeholder="Escribe clave o nombre..." required>
                        </div>
                        <div id="dropdown-menu" style="display:none;">
                            <div id="lista-sugerencias"></div>
                        </div>
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label">Clave de la tienda</label>
                    <input class="md3-input" type="text" id="campoTienda" name="clave_tienda"
                           placeholder="Automático" readonly>
                </div>

                <div class="field-group">
                    <label class="field-label">Almacén que surte</label>
                    <input class="md3-input" type="text" id="campoAlmacen" name="almacen_nombre" readonly>
                </div>

                <div class="field-group">
                    <label class="field-label">Municipio</label>
                    <input class="md3-input" type="text" id="campoMunicipio" name="municipio" readonly>
                </div>

                <div class="field-group">
                    <label class="field-label">Comunidad</label>
                    <input class="md3-input" type="text" id="campoComunidad" name="comunidad" readonly>
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
                            <td><input type="number" id="inv_ini_caja"    class="md3-input md3-input-sm" min="0" readonly placeholder="0"></td>
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
                            <td><input type="number" id="inv_ini_litros"    class="md3-input md3-input-sm" min="0" step="72" readonly placeholder="0"></td>
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
    
    <div class="md3-card" style="display: none; flex-direction:column; gap:24px;">
        <div>
            <p class="subsection-title">1.1 Diferencias</p>
            <div class="field-group" style="gap:10px;">
                <span class="field-label" style="font-size:0.875rem;">
                    ¿La venta registrada es igual a la venta real?
                </span>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="venta_igual" value="No" disabled> No
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="venta_igual" value="Si" disabled> Sí
                    </label>
                    <span class="auto-note">* Se calcula automáticamente</span>
                </div>
            </div>
            <div id="causas_diferencia" style="display:none; margin-top:16px;">
                <div class="subsection-box">
                    <div class="field-group" style="margin-bottom:16px;">
                        <label class="field-label">¿Señale o describa la causa?</label>
                        <input class="md3-input" type="text" name="causa_descripcion"
                               placeholder="Escriba aquí la causa general..."
                               style="max-width:560px;">
                    </div>
                    <div class="check-grid">
                        <div class="check-item">
                            <input type="checkbox" name="causa_a" id="chk_causa_a">
                            <label for="chk_causa_a">a) Falta de capacitación al responsable de la venta</label>
                        </div>
                        <div class="check-item">
                            <input type="checkbox" name="causa_b" id="chk_causa_b">
                            <label for="chk_causa_b">b) Omisión del responsable de la venta</label>
                        </div>
                        <div class="check-item">
                            <input type="checkbox" name="causa_c" id="chk_causa_c">
                            <label for="chk_causa_c">c) Resistencia de las personas titulares</label>
                        </div>
                        <div class="check-item">
                            <span style="white-space:nowrap; font-weight:500;">d) Otros:</span>
                            <input class="md3-input" type="text" name="causa_d_texto"
                                   placeholder="Especifique..." style="max-width:260px;">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <md-divider></md-divider>
        <div>
            <p class="subsection-title">1.2 Venta no registrada</p>

            <div class="field-group" style="gap:10px;">
                <span class="field-label" style="font-size:0.875rem;">
                    a) ¿Se vendió leche a personas no incluidas en el libro de retiro?
                </span>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="venta_no_incluida" value="No" checked> No
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="venta_no_incluida" value="Si"> Sí
                    </label>
                </div>
            </div>

            <div id="motivo_no_incluida" style="display:none; margin-top:12px;">
                <div class="field-group">
                    <label class="field-label">Anote el motivo:</label>
                    <input class="md3-input" type="text" name="motivo_venta_no_incluida"
                           placeholder="Describa el motivo..." style="max-width:560px;">
                </div>
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
                            <td><input type="date" id="surt_fecha"     class="md3-input md3-input-sm" value="<?php echo date('Y-m-d'); ?>"></td>
                            <td><input type="number" id="surt_cajas"   class="md3-input md3-input-sm" placeholder="0"></td>
                            <td><input type="number" id="surt_litros"  class="md3-input md3-input-sm" placeholder="0"></td>
                            <td><input type="text"   id="surt_factura" class="md3-input md3-input-sm" placeholder="N° factura"></td>
                            <td><input type="date"   id="surt_caducidad" class="md3-input md3-input-sm"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="md3-card" style="display: none">
        <p class="subsection-title">2.1 Falta de surtimiento</p>

        <div class="field-group" style="gap:10px;">
            <span class="field-label" style="font-size:0.875rem;">¿Hubo falta de surtimiento?</span>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="falta_surtimiento" value="No" checked> No
                </label>
                <label class="radio-label">
                    <input type="radio" name="falta_surtimiento" value="Si"> Sí
                </label>
            </div>
        </div>

        <div id="causas_falta_surtimiento" style="display:none; margin-top:16px;">
            <div class="subsection-box">
                <div class="field-group" style="margin-bottom:16px;">
                    <label class="field-label">¿Señale o describa la causa?</label>
                    <input class="md3-input" type="text" name="causa_falta_descripcion"
                           placeholder="Escriba aquí la causa general..."
                           style="max-width:560px;">
                </div>
                <div class="check-grid">
                    <div class="check-item">
                        <input type="checkbox" name="causa_falta_a" id="chk_falta_a">
                        <label for="chk_falta_a">a) Adeudo del responsable de la venta</label>
                    </div>
                    <div class="check-item">
                        <input type="checkbox" name="causa_falta_b" id="chk_falta_b">
                        <label for="chk_falta_b">b) Retraso en la distribución</label>
                    </div>
                    <div class="check-item full-width" style="border-bottom:none;">
                        <span style="white-space:nowrap; font-weight:500;">c) Otros:</span>
                        <input class="md3-input" type="text" name="causa_falta_c_texto"
                               placeholder="Especifique..." style="max-width:360px;">
                    </div>
                </div>
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
    
    <div class="form-section" style="display: none">
        <div class="section-header">
            <div class="section-badge">IV</div>
            <h2 class="section-title">Problemas de operación</h2>
        </div>

        <div class="md3-card" style="display:flex; flex-direction:column; gap:24px;">

            <div class="check-grid">
                <div class="check-item">
                    <input type="checkbox" name="prob_a" id="chk_prob_a">
                    <label for="chk_prob_a">a) Cierre por reubicación de punto de venta</label>
                </div>
                <div class="check-item">
                    <input type="checkbox" name="prob_b" id="chk_prob_b">
                    <label for="chk_prob_b">b) Renuncia o baja del responsable</label>
                </div>
                <div class="check-item">
                    <input type="checkbox" name="prob_c" id="chk_prob_c">
                    <label for="chk_prob_c">c) Adeudo del responsable</label>
                </div>
                <div class="check-item" style="border-bottom:none;">
                    <span style="white-space:nowrap; font-weight:500;">d) Otros:</span>
                    <input class="md3-input" type="text" name="prob_d_texto"
                           placeholder="Especifique..." style="max-width:260px;">
                </div>
            </div>

            <md-divider></md-divider>
            <div>
                <p class="subsection-title">4.1 ¿Se puede continuar con la venta?</p>

                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="continuar_venta" value="Si" checked> Sí
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="continuar_venta" value="No"> No
                    </label>
                </div>
                <div id="alternativas_solucion" style="display:none; margin-top:16px;">
                    <div class="subsection-box">
                        <div class="field-group" style="margin-bottom:16px;">
                            <label class="field-label">Alternativas de solución:</label>
                            <input class="md3-input" type="text" name="alternativa_general"
                                   placeholder="Describa la alternativa principal..."
                                   style="max-width:560px;">
                        </div>
                        <div class="check-grid">
                            <div class="check-item">
                                <input type="checkbox" name="alt_a" id="chk_alt_a">
                                <label for="chk_alt_a">a) Propuesta de un nuevo local</label>
                            </div>
                            <div class="check-item">
                                <input type="checkbox" name="alt_b" id="chk_alt_b">
                                <label for="chk_alt_b">b) Fusión de beneficiarios</label>
                            </div>
                            <div class="check-item">
                                <input type="checkbox" name="alt_c" id="chk_alt_c">
                                <label for="chk_alt_c">c) Baja del padrón de beneficiarios</label>
                            </div>
                            <div class="check-item" style="border-bottom:none;">
                                <span style="white-space:nowrap; font-weight:500;">d) Otra:</span>
                                <input class="md3-input" type="text" name="alt_d_texto"
                                       placeholder="Especifique..." style="max-width:260px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <div class="save-actions">
        <button id="btnGenerarPDF" class="md3-filled-btn">
            <span class="material-symbols-outlined btn-label" style="font-size:20px;">save</span>
            <span class="btn-label">Guardar datos</span>
        </button>
    </div>

</main>

<script src="../js/temas_md3.js"></script>
<script src="../js/promotores.js"></script>
<script>
    /* ── Menú y drawer ── */
    function abrirMenu(id) {
        document.querySelectorAll('md-menu').forEach(m => { if (m.id !== id) m.open = false; });
        const menu = document.getElementById(id);
        menu.open = !menu.open;
    }
    function toggleDrawer() {
        document.getElementById('mobile-drawer').classList.toggle('open');
        document.getElementById('drawer-scrim').classList.toggle('open');
    }
    document.addEventListener('click', (e) => {
        if (!e.target.closest('md-menu') && !e.target.closest('md-text-button')) {
            document.querySelectorAll('md-menu').forEach(m => m.open = false);
        }
    });

    /* ── Buscador de lechería ── */
    document.addEventListener('DOMContentLoaded', () => {
        const inputLecheria    = document.getElementById('inputLecheria');
        const dropdown         = document.getElementById('dropdown-menu');
        const listaSugerencias = document.getElementById('lista-sugerencias');
        let timeout;
        let ignorarBlur = false; // FLAG: evita cerrar el dropdown cuando el click viene de una opción

        /* ─ Escuchar escritura ─ */
        inputLecheria.addEventListener('input', function () {
            const texto = this.value.trim();
            clearTimeout(timeout);

            if (texto.length < 1) {
                dropdown.style.display = 'none';
                return;
            }

            timeout = setTimeout(() => {
                fetch('buscarLecheria.php?q=' + encodeURIComponent(texto))
                    .then(r => r.json())
                    .then(datos => {
                        listaSugerencias.innerHTML = '';

                        if (!Array.isArray(datos) || datos.length === 0) {
                            dropdown.style.display = 'none';
                            return;
                        }

                        datos.forEach(item => {
                            const opt = document.createElement('a');
                            opt.className  = 'dropdown-item';
                            opt.href       = '#';   // necesario para que el cursor cambie a pointer
                            opt.innerHTML  = `
                                <strong>${item.LECHER}</strong> &ndash; ${item.NOMBRELECH}<br>
                                <small>${item.MUNICIPIO_NOMBRE ?? ''} &ndash; ${item.LOCALIDAD_DESC ?? ''}</small>
                            `;

                            /*
                             * FIX RACE CONDITION:
                             * Usamos 'mousedown' en lugar de 'click'.
                             * mousedown se dispara ANTES de que el input pierda el foco (blur),
                             * así que podemos marcar ignorarBlur=true y evitar que el documento
                             * cierre el dropdown antes de procesar la selección.
                             * e.preventDefault() evita que el input pierda el foco en absoluto.
                             */
                            opt.addEventListener('mousedown', (e) => {
                                e.preventDefault(); // impide blur en el input
                                ignorarBlur = true;

                                inputLecheria.value = item.LECHER;

                                document.getElementById('campoTienda').value    = item.NUM_TIENDA       ?? '';
                                document.getElementById('campoAlmacen').value   = item.ALMACEN_RURAL    ?? '';
                                document.getElementById('campoMunicipio').value = item.MUNICIPIO_NOMBRE ?? '';
                                document.getElementById('campoComunidad').value = item.LOCALIDAD_DESC   ?? '';

                                const hogares = item.TOTAL_HOGARES    ?? 0;
                                const menores = item.TOTAL_INFANTILES ?? 0;
                                const mayores = item.TOTAL_RESTO      ?? 0;

                                document.getElementById('campoHogares').value = hogares;
                                document.getElementById('campoMenores').value = menores;
                                document.getElementById('campoMayores').value = mayores;

                                const totalBen = parseInt(menores) + parseInt(mayores);
                                document.getElementById('campoDotacion').value =
                                    ((totalBen * 8) / 36 * 72).toFixed(0);

                                dropdown.style.display = 'none';
                                ignorarBlur = false;

                                // Dispara el evento para que promotores.js calcule el surtimiento
                                document.dispatchEvent(new Event('lecheriaSeleccionada'));
                            });

                            listaSugerencias.appendChild(opt);
                        });

                        dropdown.style.display = 'block';
                    })
                    .catch(err => console.error('Error búsqueda lechería:', err));
            }, 300);
        });

        /* ─ Cerrar al hacer click fuera ─ */
        document.addEventListener('click', (e) => {
            if (ignorarBlur) return; // el mousedown del item ya manejó esto
            if (!inputLecheria.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });

        /* ─ Cerrar con Escape ─ */
        inputLecheria.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') dropdown.style.display = 'none';
        });
    });
</script>
</body>
</html>