<?php

session_start();

require_once '../Database.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    header("Location: ../iniciosesionPromotor.php");
    exit();
}

$nombre_usuario = $_SESSION['nombre'] ?? $_SESSION['usuario'];
$origen_conexion = Database::getEnvName();

$lecher_get = $_GET['lecher'] ?? '';

?>
<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario Mensual - Promotor</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../main_md3.css">
    <link rel="stylesheet" href="../estilos/generarInventarioMensual.css">
    <link rel="stylesheet" href="../estilos/editarinventarioMensual.css">
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
    <style>
        /* ── Banner modo edición ── */
        .edit-banner {
            display: flex;
            align-items: center;
            gap: 10px;
            background: color-mix(in srgb, var(--md-sys-color-tertiary) 15%, transparent);
            border: 1.5px solid var(--md-sys-color-tertiary);
            border-radius: 12px;
            padding: 12px 20px;
            margin-bottom: 20px;
            color: var(--md-sys-color-on-surface);
            font-size: 0.9rem;
            font-weight: 500;
            animation: slideDown 0.3s ease;
        }
        .edit-banner .material-symbols-outlined {
            color: var(--md-sys-color-tertiary);
            font-size: 22px;
            flex-shrink: 0;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Indicador de búsqueda ── */
        .periodo-status {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 500;
            transition: all 0.25s;
        }
        .periodo-status.buscando {
            background: color-mix(in srgb, var(--md-sys-color-primary) 12%, transparent);
            color: var(--md-sys-color-primary);
        }
        .periodo-status.nuevo {
            background: color-mix(in srgb, var(--md-sys-color-secondary) 12%, transparent);
            color: var(--md-sys-color-secondary);
        }
        .periodo-status.edicion {
            background: color-mix(in srgb, var(--md-sys-color-tertiary) 12%, transparent);
            color: var(--md-sys-color-tertiary);
        }
        .periodo-status.oculto { display: none; }

        /* ── Botón guardar dinámico ── */
        #btnGuardar {
            transition: background 0.25s, opacity 0.25s;
        }
    </style>
</head>

<body>
    <header class="md3-top-app-bar">
        <div class="app-bar-start">
            <md-icon-button class="mobile-menu-btn" onclick="toggleDrawer()">
                <md-icon>menu</md-icon>
            </md-icon-button>
            <div class="app-brand">
                <span>Leche para el bienestar - Promotor</span>
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
                            <div slot="headline">Generar / Editar</div>
                            <md-icon slot="start">edit_document</md-icon>
                        </md-menu-item>
                        <md-menu-item href="consultarinventarioMensual.php">
                            <div slot="headline">Consultar</div>
                            <md-icon slot="start">search</md-icon>
                        </md-menu-item>
                    </md-menu>
                </div>

                <md-text-button href="generarreporteMensual.php">
                    <md-icon slot="icon">receipt_long</md-icon>
                    Reporte mensual
                </md-text-button>

                <md-text-button href="requerimiento.php">
                    <md-icon slot="icon">inventory</md-icon>
                    Requerimiento
                </md-text-button>
            </div>

            <md-filled-tonal-button href="../cerrar_sesion.php" style="margin-left: 16px;">
                <md-icon slot="icon">logout</md-icon>
                Salir
            </md-filled-tonal-button>
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
                    <div slot="headline">Generar / Editar</div>
                    <md-icon slot="start">edit_document</md-icon>
                </md-list-item>
                <md-list-item href="consultarinventarioMensual.php" type="button">
                    <div slot="headline">Consultar</div>
                    <md-icon slot="start">search</md-icon>
                </md-list-item>
                <md-divider style="margin: 8px 0;"></md-divider>
                <md-list-item href="generarreporteMensual.php" type="button">
                    <div slot="headline">Reporte mensual</div>
                    <md-icon slot="start">receipt_long</md-icon>
                </md-list-item>
                <md-list-item href="requerimiento.php" type="button">
                    <div slot="headline">Requerimiento</div>
                    <md-icon slot="start">inventory</md-icon>
                </md-list-item>
            </md-list>
        </div>
    </aside>

    <main class="panel-content">

        <!-- ══ BANNER: Modo edición activo ══ -->
        <div class="edit-banner" id="bannerEdicion" style="display:none;">
            <span class="material-symbols-outlined">edit_note</span>
            <span id="bannerTexto">Modo edición — ya existe un inventario para este periodo. Al guardar se actualizarán los datos y el PDF.</span>
            <md-text-button id="btnLimpiarEdicion" style="margin-left:auto;" onclick="limpiarModoEdicion()">
                <md-icon slot="icon">close</md-icon>Cancelar edición
            </md-text-button>
        </div>

        <!-- ID oculto del inventario en edición (vacío = modo nuevo) -->
        <input type="hidden" id="inventario_id" value="">

        <!-- ══ PERIODO ══ -->
        <div style="display: flex; justify-content: left; margin-bottom: 24px; margin-top: 8px;">
            <div class="md3-card"
                style="width: auto; padding: 12px 24px; display: flex; flex-wrap: wrap; align-items: center; gap: 16px; flex-direction: row; justify-content: center;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-outlined" style="color: var(--md-sys-color-primary);">calendar_month</span>
                    <span style="font-weight: 500; color: var(--md-sys-color-on-surface);">Periodo a Reportar:</span>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <select class="md3-input" name="mes_periodo" id="mes_periodo"
                        style="min-width: 140px; cursor: pointer; margin: 0;">
                        <option value="1"  <?php echo date('n') == 1  ? 'selected' : ''; ?>>Enero</option>
                        <option value="2"  <?php echo date('n') == 2  ? 'selected' : ''; ?>>Febrero</option>
                        <option value="3"  <?php echo date('n') == 3  ? 'selected' : ''; ?>>Marzo</option>
                        <option value="4"  <?php echo date('n') == 4  ? 'selected' : ''; ?>>Abril</option>
                        <option value="5"  <?php echo date('n') == 5  ? 'selected' : ''; ?>>Mayo</option>
                        <option value="6"  <?php echo date('n') == 6  ? 'selected' : ''; ?>>Junio</option>
                        <option value="7"  <?php echo date('n') == 7  ? 'selected' : ''; ?>>Julio</option>
                        <option value="8"  <?php echo date('n') == 8  ? 'selected' : ''; ?>>Agosto</option>
                        <option value="9"  <?php echo date('n') == 9  ? 'selected' : ''; ?>>Septiembre</option>
                        <option value="10" <?php echo date('n') == 10 ? 'selected' : ''; ?>>Octubre</option>
                        <option value="11" <?php echo date('n') == 11 ? 'selected' : ''; ?>>Noviembre</option>
                        <option value="12" <?php echo date('n') == 12 ? 'selected' : ''; ?>>Diciembre</option>
                    </select>
                    <input class="md3-input" type="number" name="anio_periodo" id="anio_periodo"
                        value="<?php echo date('Y'); ?>" readonly
                        style="max-width: 80px; margin: 0; background-color: var(--md-sys-color-surface-variant); text-align: center; pointer-events: none;">
                </div>
                <!-- Indicador de estado -->
                <div class="periodo-status oculto" id="periodoStatus">
                    <span class="material-symbols-outlined" style="font-size:16px;" id="periodoStatusIcon">search</span>
                    <span id="periodoStatusText"></span>
                </div>
            </div>
        </div>

        <!-- ══ SECCIÓN I: DATOS GENERALES ══ -->
        <div class="form-section">
            <div class="section-header">
                <div class="section-badge">
                    <span class="material-symbols-outlined" style="font-size:17px;">description</span>
                </div>
                <h2 class="section-title">Datos generales del inventario</h2>
            </div>

            <div class="md3-card">
                <div class="form-grid fg-3">
                    <input type="hidden" id="inputUsuarioOculto" value="<?php echo htmlspecialchars($nombre_usuario); ?>">
                    <div class="field-group">
                        <label class="field-label" for="inputFecha">Fecha</label>
                        <input class="md3-input" type="date" name="fecha" id="inputFecha"
                            value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="field-group">
                        <label class="field-label" for="inputLecheria">Clave del punto de venta (LECHER)</label>
                        <div class="autocomplete-wrapper" style="position: relative;">
                            <div class="input-with-icon">
                                <span class="material-symbols-outlined input-icon">search</span>
                                <input class="md3-input" type="text" id="inputLecheria"
                                    placeholder="Escribe clave o nombre..."
                                    value="<?php echo htmlspecialchars($lecher_get); ?>" required>
                            </div>
                            <div id="dropdown-menu" style="display:none;">
                                <div id="lista-sugerencias"></div>
                            </div>
                        </div>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Clave de la tienda</label>
                        <input class="md3-input" type="text" id="campoTienda" name="clave_tienda" placeholder="Automático" readonly>
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

        <!-- ══ SECCIÓN I: EXISTENCIA DE LECHE ══ -->
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

        <!-- ══ SECCIÓN 1.1/1.2 (ocultas, calculadas automáticamente) ══ -->
        <div class="md3-card" style="display: none; flex-direction:column; gap:24px;">
            <div>
                <p class="subsection-title">1.1 Diferencias</p>
                <div class="field-group" style="gap:10px;">
                    <span class="field-label" style="font-size:0.875rem;">¿La venta registrada es igual a la venta real?</span>
                    <div class="radio-group">
                        <label class="radio-label"><input type="radio" name="venta_igual" value="No" disabled> No</label>
                        <label class="radio-label"><input type="radio" name="venta_igual" value="Si" disabled> Sí</label>
                        <span class="auto-note">* Se calcula automáticamente</span>
                    </div>
                </div>
                <div id="causas_diferencia" style="display:none; margin-top:16px;">
                    <div class="subsection-box">
                        <div class="field-group" style="margin-bottom:16px;">
                            <label class="field-label">¿Señale o describa la causa?</label>
                            <input class="md3-input" type="text" name="causa_descripcion" placeholder="Escriba aquí la causa general..." style="max-width:560px;">
                        </div>
                        <div class="check-grid">
                            <div class="check-item"><input type="checkbox" name="causa_a" id="chk_causa_a"><label for="chk_causa_a">a) Falta de capacitación al responsable de la venta</label></div>
                            <div class="check-item"><input type="checkbox" name="causa_b" id="chk_causa_b"><label for="chk_causa_b">b) Omisión del responsable de la venta</label></div>
                            <div class="check-item"><input type="checkbox" name="causa_c" id="chk_causa_c"><label for="chk_causa_c">c) Resistencia de las personas titulares</label></div>
                            <div class="check-item">
                                <span style="white-space:nowrap; font-weight:500;">d) Otros:</span>
                                <input class="md3-input" type="text" name="causa_d_texto" placeholder="Especifique..." style="max-width:260px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <md-divider></md-divider>
            <div>
                <p class="subsection-title">1.2 Venta no registrada</p>
                <div class="field-group" style="gap:10px;">
                    <span class="field-label" style="font-size:0.875rem;">a) ¿Se vendió leche a personas no incluidas en el libro de retiro?</span>
                    <div class="radio-group">
                        <label class="radio-label"><input type="radio" name="venta_no_incluida" value="No" checked> No</label>
                        <label class="radio-label"><input type="radio" name="venta_no_incluida" value="Si"> Sí</label>
                    </div>
                </div>
                <div id="motivo_no_incluida" style="display:none; margin-top:12px;">
                    <div class="field-group">
                        <label class="field-label">Anote el motivo:</label>
                        <input class="md3-input" type="text" name="motivo_venta_no_incluida" placeholder="Describa el motivo..." style="max-width:560px;">
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ SECCIÓN II: SURTIMIENTOS ══ -->
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
                                <td><input type="date"   id="surt_fecha"     class="md3-input md3-input-sm" value="<?php echo date('Y-m-d'); ?>"></td>
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

        <!-- ══ SECCIÓN 2.1 (oculta) ══ -->
        <div class="md3-card" style="display: none">
            <p class="subsection-title">2.1 Falta de surtimiento</p>
            <div class="field-group" style="gap:10px;">
                <span class="field-label" style="font-size:0.875rem;">¿Hubo falta de surtimiento?</span>
                <div class="radio-group">
                    <label class="radio-label"><input type="radio" name="falta_surtimiento" value="No" checked> No</label>
                    <label class="radio-label"><input type="radio" name="falta_surtimiento" value="Si"> Sí</label>
                </div>
            </div>
            <div id="causas_falta_surtimiento" style="display:none; margin-top:16px;">
                <div class="subsection-box">
                    <div class="field-group" style="margin-bottom:16px;">
                        <label class="field-label">¿Señale o describa la causa?</label>
                        <input class="md3-input" type="text" name="causa_falta_descripcion" placeholder="Escriba aquí la causa general..." style="max-width:560px;">
                    </div>
                    <div class="check-grid">
                        <div class="check-item"><input type="checkbox" name="causa_falta_a" id="chk_falta_a"><label for="chk_falta_a">a) Adeudo del responsable de la venta</label></div>
                        <div class="check-item"><input type="checkbox" name="causa_falta_b" id="chk_falta_b"><label for="chk_falta_b">b) Retraso en la distribución</label></div>
                        <div class="check-item full-width" style="border-bottom:none;">
                            <span style="white-space:nowrap; font-weight:500;">c) Otros:</span>
                            <input class="md3-input" type="text" name="causa_falta_c_texto" placeholder="Especifique..." style="max-width:360px;">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ SECCIÓN III: COBERTURA SOCIAL ══ -->
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

        <!-- ══ SECCIÓN IV: PROBLEMAS (oculta) ══ -->
        <div class="form-section" style="display: none">
            <div class="section-header">
                <div class="section-badge">IV</div>
                <h2 class="section-title">Problemas de operación</h2>
            </div>
            <div class="md3-card" style="display:flex; flex-direction:column; gap:24px;">
                <div class="check-grid">
                    <div class="check-item"><input type="checkbox" name="prob_a" id="chk_prob_a"><label for="chk_prob_a">a) Cierre por reubicación de punto de venta</label></div>
                    <div class="check-item"><input type="checkbox" name="prob_b" id="chk_prob_b"><label for="chk_prob_b">b) Renuncia o baja del responsable</label></div>
                    <div class="check-item"><input type="checkbox" name="prob_c" id="chk_prob_c"><label for="chk_prob_c">c) Adeudo del responsable</label></div>
                    <div class="check-item" style="border-bottom:none;">
                        <span style="white-space:nowrap; font-weight:500;">d) Otros:</span>
                        <input class="md3-input" type="text" name="prob_d_texto" placeholder="Especifique..." style="max-width:260px;">
                    </div>
                </div>
                <md-divider></md-divider>
                <div>
                    <p class="subsection-title">4.1 ¿Se puede continuar con la venta?</p>
                    <div class="radio-group">
                        <label class="radio-label"><input type="radio" name="continuar_venta" value="Si" checked> Sí</label>
                        <label class="radio-label"><input type="radio" name="continuar_venta" value="No"> No</label>
                    </div>
                    <div id="alternativas_solucion" style="display:none; margin-top:16px;">
                        <div class="subsection-box">
                            <div class="field-group" style="margin-bottom:16px;">
                                <label class="field-label">Alternativas de solución:</label>
                                <input class="md3-input" type="text" name="alternativa_general" placeholder="Describa la alternativa principal..." style="max-width:560px;">
                            </div>
                            <div class="check-grid">
                                <div class="check-item"><input type="checkbox" name="alt_a" id="chk_alt_a"><label for="chk_alt_a">a) Propuesta de un nuevo local</label></div>
                                <div class="check-item"><input type="checkbox" name="alt_b" id="chk_alt_b"><label for="chk_alt_b">b) Fusión de beneficiarios</label></div>
                                <div class="check-item"><input type="checkbox" name="alt_c" id="chk_alt_c"><label for="chk_alt_c">c) Baja del padrón de beneficiarios</label></div>
                                <div class="check-item" style="border-bottom:none;">
                                    <span style="white-space:nowrap; font-weight:500;">d) Otra:</span>
                                    <input class="md3-input" type="text" name="alt_d_texto" placeholder="Especifique..." style="max-width:260px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ BOTÓN GUARDAR ══ -->
        <div class="save-actions">
            <button id="btnGuardar" class="md3-filled-btn">
                <span class="material-symbols-outlined btn-label" style="font-size:20px;" id="btnGuardarIcon">save</span>
                <span class="btn-label" id="btnGuardarTexto">Guardar datos</span>
            </button>
        </div>

    </main>

    <script src="../js/temas_md3.js"></script>
    <script src="../js/notificaciones.js"></script>
    <script src="../js/promotores.js"></script>

    <script>
    // ══════════════════════════════════════════════════════════
    //  NAVEGACIÓN (menú, drawer) — igual que antes
    // ══════════════════════════════════════════════════════════
    function abrirMenu(id) {
        document.querySelectorAll('md-menu').forEach(m => {
            if (m.id !== id) m.open = false;
        });
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

    // ══════════════════════════════════════════════════════════
    //  ESTADO GLOBAL DEL MODO (nuevo | edicion)
    // ══════════════════════════════════════════════════════════
    const Estado = {
        modo: 'nuevo',          // 'nuevo' | 'edicion'
        inventarioId: null,
        lecheriaActual: null,
        buscandoTimer: null,    // debounce

        setNuevo() {
            this.modo = 'nuevo';
            this.inventarioId = null;
            document.getElementById('inventario_id').value = '';
            document.getElementById('bannerEdicion').style.display = 'none';
            document.getElementById('btnGuardarIcon').textContent = 'save';
            document.getElementById('btnGuardarTexto').textContent = 'Guardar datos';
            mostrarStatusPeriodo('nuevo', 'Inventario nuevo');
        },

        setEdicion(id, msg) {
            this.modo = 'edicion';
            this.inventarioId = id;
            document.getElementById('inventario_id').value = id;
            document.getElementById('bannerTexto').textContent = msg ||
                'Modo edición — ya existe un inventario para este periodo. Al guardar se actualizarán los datos y el PDF.';
            document.getElementById('bannerEdicion').style.display = 'flex';
            document.getElementById('btnGuardarIcon').textContent = 'update';
            document.getElementById('btnGuardarTexto').textContent = 'Actualizar datos';
            mostrarStatusPeriodo('edicion', 'Modo edición');
        }
    };

    // ══════════════════════════════════════════════════════════
    //  INDICADOR DE ESTADO DEL PERIODO
    // ══════════════════════════════════════════════════════════
    function mostrarStatusPeriodo(tipo, texto) {
        const el = document.getElementById('periodoStatus');
        const icon = document.getElementById('periodoStatusIcon');
        const txt  = document.getElementById('periodoStatusText');
        el.className = 'periodo-status ' + tipo;
        icon.textContent = tipo === 'buscando' ? 'hourglass_empty'
                         : tipo === 'edicion'  ? 'edit_note'
                         : 'fiber_new';
        txt.textContent = texto;
    }
    function ocultarStatusPeriodo() {
        document.getElementById('periodoStatus').className = 'periodo-status oculto';
    }

    // ══════════════════════════════════════════════════════════
    //  VERIFICAR SI YA EXISTE INVENTARIO para lechería + periodo
    // ══════════════════════════════════════════════════════════
    function verificarInventarioPeriodo() {
        const lecheria = document.getElementById('inputLecheria').value.trim();
        const mes  = document.getElementById('mes_periodo').value;
        const anio = document.getElementById('anio_periodo').value;

        if (!lecheria || !mes || !anio) {
            ocultarStatusPeriodo();
            Estado.setNuevo();
            return;
        }

        mostrarStatusPeriodo('buscando', 'Verificando periodo...');

        fetch(`obtener_inventarios_por_lecheria.php?clave=${encodeURIComponent(lecheria)}&mes=${mes}&anio=${anio}`)
            .then(r => r.json())
            .then(data => {
                if (Array.isArray(data) && data.length > 0) {
                    const inv = data[0]; // el más reciente del periodo
                    Estado.setEdicion(inv.ID,
                        `Modo edición — ya existe un inventario para este periodo (ID ${inv.ID}). Al guardar se actualizarán los datos y el PDF.`
                    );
                    cargarDatosInventario(inv.ID);
                } else {
                    Estado.setNuevo();
                    // Si cambió el periodo/lechería y estábamos en edición → limpiar tabla
                    limpiarTablaLeche();
                    // Relanzar cálculo de inventario inicial desde calcularSurtimiento
                    document.dispatchEvent(new Event('lecheriaSeleccionada'));
                }
            })
            .catch(() => {
                ocultarStatusPeriodo();
                Estado.setNuevo();
            });
    }

    // ══════════════════════════════════════════════════════════
    //  CARGAR DATOS DE UN INVENTARIO EXISTENTE
    // ══════════════════════════════════════════════════════════
    function cargarDatosInventario(id) {
        fetch(`obtener_inventario.php?id=${id}`)
            .then(r => r.json())
            .then(res => {
                if (res.status !== 'success') return;
                const d = res.datos;

                // Campos de texto / fecha
                document.getElementById('inputFecha').value    = d.FECHA?.substring(0, 10) ?? '';
                document.getElementById('campoTienda').value   = d.CLAVE_TIENDA   ?? '';
                document.getElementById('campoAlmacen').value  = d.ALMACEN        ?? '';
                document.getElementById('campoMunicipio').value= d.MUNICIPIO      ?? '';
                document.getElementById('campoComunidad').value= d.COMUNIDAD      ?? '';
                document.getElementById('campoHogares').value  = d.HOGARES        ?? '';
                document.getElementById('campoMenores').value  = d.MENORES        ?? '';
                document.getElementById('campoMayores').value  = d.MAYORES        ?? '';
                document.getElementById('campoDotacion').value = d.DOTACION       ?? '';

                // Tabla I
                document.getElementById('inv_ini_caja').value    = d.INV_INI_CAJA    ?? '';
                document.getElementById('inv_ini_sobres').value  = d.INV_INI_SOBRES  ?? '';
                document.getElementById('inv_ini_litros').value  = d.INV_INI_LITROS  ?? '';
                document.getElementById('abasto_caja').value     = d.ABASTO_CAJA     ?? '';
                document.getElementById('abasto_sobres').value   = d.ABASTO_SOBRES   ?? '';
                document.getElementById('abasto_litros').value   = d.ABASTO_LITROS   ?? '';
                document.getElementById('venta_caja').value      = d.VENTA_CAJA      ?? '';
                document.getElementById('venta_sobres').value    = d.VENTA_SOBRES    ?? '';
                document.getElementById('venta_litros').value    = d.VENTA_LITROS    ?? '';
                document.getElementById('litros_reg_caja').value = d.REG_CAJA        ?? '';
                document.getElementById('litros_reg_sobres').value = d.REG_SOBRES    ?? '';
                document.getElementById('litros_reg_litros').value = d.REG_LITROS    ?? '';
                document.getElementById('dif_caja').value        = d.DIF_CAJA        ?? '';
                document.getElementById('dif_sobres').value      = d.DIF_SOBRES      ?? '';
                document.getElementById('dif_litros').value      = d.DIF_LITROS      ?? '';
                document.getElementById('inv_fin_caja').value    = d.FIN_CAJA        ?? '';
                document.getElementById('inv_fin_sobres').value  = d.FIN_SOBRES      ?? '';
                document.getElementById('inv_fin_litros').value  = d.FIN_LITROS      ?? '';

                // Tabla II
                document.getElementById('surt_fecha').value     = d.SURT_FECHA?.substring(0, 10) ?? '';
                document.getElementById('surt_cajas').value     = d.SURT_CAJAS     ?? '';
                document.getElementById('surt_litros').value    = d.SURT_LITROS    ?? '';
                document.getElementById('surt_factura').value   = d.SURT_FACTURA   ?? '';
                document.getElementById('surt_caducidad').value = d.SURT_CADUCIDAD?.substring(0, 10) ?? '';

                mostrarNotificacion('Inventario existente cargado para edición.', 'info');
            })
            .catch(() => mostrarNotificacion('No se pudo cargar el inventario existente.', 'error'));
    }

    // ══════════════════════════════════════════════════════════
    //  LIMPIAR TABLA AL CAMBIAR A MODO NUEVO
    // ══════════════════════════════════════════════════════════
    function limpiarTablaLeche() {
        const ids = [
            'inv_ini_caja','inv_ini_sobres','inv_ini_litros',
            'abasto_caja','abasto_sobres','abasto_litros',
            'venta_caja','venta_sobres','venta_litros',
            'litros_reg_caja','litros_reg_sobres','litros_reg_litros',
            'dif_caja','dif_sobres','dif_litros',
            'inv_fin_caja','inv_fin_sobres','inv_fin_litros',
        ];
        ids.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
    }

    // ══════════════════════════════════════════════════════════
    //  CANCELAR EDICIÓN (botón X del banner)
    // ══════════════════════════════════════════════════════════
    function limpiarModoEdicion() {
        Estado.setNuevo();
        limpiarTablaLeche();
        ocultarStatusPeriodo();
    }

    // ══════════════════════════════════════════════════════════
    //  COMBOBOX INTELIGENTE DE LECHERÍAS
    // ══════════════════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', () => {
        const inputLecheria    = document.getElementById('inputLecheria');
        const dropdown         = document.getElementById('dropdown-menu');
        const listaSugerencias = document.getElementById('lista-sugerencias');
        const selectMes        = document.getElementById('mes_periodo');

        let lecheriasDelPromotor = [];
        let ignorarBlur  = false;
        let estaCargando = true;

        // Escuchar cambio de mes → re-verificar si hay inventario
        selectMes.addEventListener('change', () => {
            clearTimeout(Estado.buscandoTimer);
            Estado.buscandoTimer = setTimeout(verificarInventarioPeriodo, 300);
        });

        // 1. Cargar todas las lecherías del promotor
        fetch('buscarLecheria.php?q=')
            .then(r => r.json())
            .then(datos => {
                lecheriasDelPromotor = Array.isArray(datos) ? datos : [];
                estaCargando = false;

                const lecherInicial = "<?php echo $lecher_get; ?>";
                if (lecherInicial !== '') {
                    const encontrada = lecheriasDelPromotor.find(l => l.LECHER == lecherInicial);
                    if (encontrada) seleccionarLecheria(encontrada);
                } else if (document.activeElement === inputLecheria) {
                    mostrarOpciones(inputLecheria.value.trim());
                }
            })
            .catch(err => {
                console.error('Error cargando lecherías:', err);
                estaCargando = false;
            });

        // 2. Mostrar opciones (todas o filtradas)
        function mostrarOpciones(filtro = '') {
            listaSugerencias.innerHTML = '';

            if (estaCargando) {
                listaSugerencias.innerHTML = '<div style="padding:16px; color:var(--md-sys-color-primary); text-align:center; font-weight:500;">Cargando tus lecherías...</div>';
                dropdown.style.display = 'block';
                return;
            }

            const txt = filtro.toUpperCase();
            const filtradas = lecheriasDelPromotor.filter(item =>
                item.LECHER.toString().includes(txt) ||
                (item.NOMBRELECH && item.NOMBRELECH.toUpperCase().includes(txt))
            );

            if (filtradas.length === 0) {
                listaSugerencias.innerHTML = '<div style="padding:16px; color:var(--md-sys-color-on-surface-variant); text-align:center;">No se encontraron coincidencias</div>';
            } else {
                filtradas.forEach(item => {
                    const opt = document.createElement('a');
                    opt.className = 'dropdown-item';
                    opt.href = '#';
                    opt.innerHTML = `
                        <strong>${item.LECHER}</strong> &ndash; ${item.NOMBRELECH}<br>
                        <small>${item.MUNICIPIO_NOMBRE ?? ''} &ndash; ${item.LOCALIDAD_DESC ?? ''}</small>
                    `;
                    opt.addEventListener('mousedown', (e) => {
                        e.preventDefault();
                        ignorarBlur = true;
                        seleccionarLecheria(item);
                        dropdown.style.display = 'none';
                        ignorarBlur = false;
                    });
                    listaSugerencias.appendChild(opt);
                });
            }
            dropdown.style.display = 'block';
        }

        // 3. Al seleccionar una lechería
        function seleccionarLecheria(item) {
            inputLecheria.value = item.LECHER;
            Estado.lecheriaActual = item.LECHER;

            document.getElementById('campoTienda').value    = item.TIPO_PUNTO_VENTA == 2 ? 'DM' : (item.NUM_TIENDA ?? '');
            document.getElementById('campoAlmacen').value   = item.ALMACEN_RURAL   ?? '';
            document.getElementById('campoMunicipio').value = item.MUNICIPIO_NOMBRE ?? '';
            document.getElementById('campoComunidad').value = item.LOCALIDAD_DESC   ?? '';

            const hogares  = item.TOTAL_HOGARES      ?? 0;
            const menores  = item.TOTAL_INFANTILES   ?? 0;
            const mayores  = item.TOTAL_RESTO        ?? 0;
            document.getElementById('campoHogares').value = hogares;
            document.getElementById('campoMenores').value = menores;
            document.getElementById('campoMayores').value = mayores;
            document.getElementById('campoDotacion').value = ((parseInt(menores) + parseInt(mayores)) * 8 / 36 * 72).toFixed(0);

            // Primero verificamos si ya existe inventario para este periodo
            verificarInventarioPeriodo();

            // El evento lecheriaSeleccionada lo escucha promotores.js para calcular surtimiento
            // Solo lo disparamos si vamos a modo nuevo (verificarInventarioPeriodo lo maneja)
        }

        // ── Eventos del input ──
        inputLecheria.addEventListener('focus', () => mostrarOpciones(inputLecheria.value.trim()));
        inputLecheria.addEventListener('click', () => mostrarOpciones(inputLecheria.value.trim()));
        inputLecheria.addEventListener('input', function () { mostrarOpciones(this.value.trim()); });

        document.addEventListener('click', (e) => {
            if (ignorarBlur) return;
            if (!inputLecheria.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
        inputLecheria.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') dropdown.style.display = 'none';
        });
    });

    // ══════════════════════════════════════════════════════════
    //  BOTÓN GUARDAR / ACTUALIZAR (unificado)
    // ══════════════════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', () => {
        const btnGuardar = document.getElementById('btnGuardar');
        if (!btnGuardar) return;

        btnGuardar.addEventListener('click', async () => {
            if (!document.getElementById('inputLecheria').value) {
                mostrarNotificacion('Debe seleccionar un punto de venta primero.', 'error');
                return;
            }

            const datosFormulario = construirDatosFormulario();
            btnGuardar.classList.add('is-loading');

            try {
                if (Estado.modo === 'edicion' && Estado.inventarioId) {
                    // ── MODO EDICIÓN: actualizar ──
                    datosFormulario.inventario_id = Estado.inventarioId;

                    const res = await fetch('actualizar_inventario.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(datosFormulario)
                    });
                    const resultado = await res.json();

                    if (resultado.status !== 'success') {
                        throw new Error(resultado.mensaje || 'Error al actualizar en base de datos');
                    }

                    mostrarNotificacion('Inventario actualizado correctamente.', 'info');

                } else {
                    // ── MODO NUEVO: guardar ──
                    datosFormulario.confirmado_periodo = false;

                    const intentarGuardar = async (datos) => {
                        const res = await fetch('guardar_inventario.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(datos)
                        });
                        const resultado = await res.json();

                        if (resultado.status === 'requiere_confirmacion') {
                            const seguro = await mostrarConfirmacionMD3(resultado.mensaje);
                            if (seguro) {
                                datos.confirmado_periodo = true;
                                return await intentarGuardar(datos);
                            } else {
                                throw new Error('Operación cancelada. Registra primero el mes anterior.');
                            }
                        }
                        if (resultado.status !== 'success') {
                            throw new Error(resultado.mensaje || 'Error al guardar en base de datos');
                        }
                        return true;
                    };

                    await intentarGuardar(datosFormulario);
                    mostrarNotificacion('Datos guardados en la base de datos.', 'info');
                }

                // ── Generar PDF (igual en ambos modos) ──
                const resPDF = await fetch('generar_pdf.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(datosFormulario)
                });

                if (!resPDF.ok) throw new Error('Error en el servidor al generar el PDF');

                const blob = await resPDF.blob();
                const url  = window.URL.createObjectURL(blob);
                window.open(url, '_blank');
                mostrarNotificacion('¡PDF generado exitosamente!', 'info');
                setTimeout(() => window.URL.revokeObjectURL(url), 1000);

            } catch (error) {
                console.error(error);
                mostrarNotificacion(error.message || 'Ocurrió un error en el proceso.', 'error');
            } finally {
                btnGuardar.classList.remove('is-loading');
            }
        });
    });

    // ══════════════════════════════════════════════════════════
    //  HELPER: construir objeto de datos del formulario
    // ══════════════════════════════════════════════════════════
    function construirDatosFormulario() {
        return {
            fecha:        document.getElementById('inputFecha').value,
            mes_periodo:  document.getElementById('mes_periodo').value,
            anio_periodo: document.getElementById('anio_periodo').value,
            lecheria:     document.getElementById('inputLecheria').value,
            tienda:       document.getElementById('campoTienda').value,
            almacen:      document.getElementById('campoAlmacen').value,
            municipio:    document.getElementById('campoMunicipio').value,
            comunidad:    document.getElementById('campoComunidad').value,

            inv_ini_caja:   document.getElementById('inv_ini_caja').value,
            inv_ini_sobres: document.getElementById('inv_ini_sobres').value,
            inv_ini_litros: document.getElementById('inv_ini_litros').value,
            abasto_caja:    document.getElementById('abasto_caja').value,
            abasto_sobres:  document.getElementById('abasto_sobres').value,
            abasto_litros:  document.getElementById('abasto_litros').value,
            venta_caja:     document.getElementById('venta_caja').value,
            venta_sobres:   document.getElementById('venta_sobres').value,
            venta_litros:   document.getElementById('venta_litros').value,
            reg_caja:       document.getElementById('litros_reg_caja').value,
            reg_sobres:     document.getElementById('litros_reg_sobres').value,
            reg_litros:     document.getElementById('litros_reg_litros').value,
            dif_caja:       document.getElementById('dif_caja').value,
            dif_sobres:     document.getElementById('dif_sobres').value,
            dif_litros:     document.getElementById('dif_litros').value,
            fin_caja:       document.getElementById('inv_fin_caja').value,
            fin_sobres:     document.getElementById('inv_fin_sobres').value,
            fin_litros:     document.getElementById('inv_fin_litros').value,

            venta_igual:        document.querySelector('input[name="venta_igual"]:checked')?.value || 'Si',
            causa_desc:         document.querySelector('input[name="causa_descripcion"]')?.value || '',
            causa_a:            document.querySelector('input[name="causa_a"]')?.checked || false,
            causa_b:            document.querySelector('input[name="causa_b"]')?.checked || false,
            causa_c:            document.querySelector('input[name="causa_c"]')?.checked || false,
            causa_d:            document.querySelector('input[name="causa_d_texto"]')?.value || '',
            venta_no_incluida:  document.querySelector('input[name="venta_no_incluida"]:checked')?.value || 'No',
            motivo_no_incluida: document.querySelector('input[name="motivo_venta_no_incluida"]')?.value || '',

            surt_fecha:    document.getElementById('surt_fecha').value,
            surt_cajas:    document.getElementById('surt_cajas').value,
            surt_litros:   document.getElementById('surt_litros').value,
            surt_factura:  document.getElementById('surt_factura').value,
            surt_caducidad:document.getElementById('surt_caducidad').value,

            falta_surt:       document.querySelector('input[name="falta_surtimiento"]:checked')?.value || 'No',
            causa_falta_desc: document.querySelector('input[name="causa_falta_descripcion"]')?.value || '',
            causa_falta_a:    document.querySelector('input[name="causa_falta_a"]')?.checked || false,
            causa_falta_b:    document.querySelector('input[name="causa_falta_b"]')?.checked || false,
            causa_falta_c:    document.querySelector('input[name="causa_falta_c_texto"]')?.value || '',

            hogares:  document.getElementById('campoHogares').value,
            menores:  document.getElementById('campoMenores').value,
            mayores:  document.getElementById('campoMayores').value,
            dotacion: document.getElementById('campoDotacion').value,

            prob_a: document.querySelector('input[name="prob_a"]')?.checked || false,
            prob_b: document.querySelector('input[name="prob_b"]')?.checked || false,
            prob_c: document.querySelector('input[name="prob_c"]')?.checked || false,
            prob_d: document.querySelector('input[name="prob_d_texto"]')?.value || '',

            continuar:   document.querySelector('input[name="continuar_venta"]:checked')?.value || 'Si',
            alt_general: document.querySelector('input[name="alternativa_general"]')?.value || '',
            alt_a:       document.querySelector('input[name="alt_a"]')?.checked || false,
            alt_b:       document.querySelector('input[name="alt_b"]')?.checked || false,
            alt_c:       document.querySelector('input[name="alt_c"]')?.checked || false,
            alt_d:       document.querySelector('input[name="alt_d_texto"]')?.value || '',

            usuario: document.getElementById('inputUsuarioOculto').value
        };
    }
    </script>
</body>
</html>