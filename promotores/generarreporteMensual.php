<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    header("Location: ../iniciosesionPromotor.php");
    exit();
}
$nombre_usuario = $_SESSION['nombre'] ?? $_SESSION['usuario'];

// =========================================================
// SIMULACIÓN DE EXTRACCIÓN DE FIREBIRD
// =========================================================
$precio_litro = "4.50"; // Variable para el precio (4.50 o 6.50)
$nombre_almacen = "CHALCATONGO DE HIDALGO"; // Variable del almacén

// Los 12 registros exactos del PDF
$lecherias_db = [
    ['punto' => '2012051000', 'clave' => '11'],
    ['punto' => '2037710200', 'clave' => '18'],
    ['punto' => '2037710500', 'clave' => '33'],
    ['punto' => '2037710700', 'clave' => '26'],
    ['punto' => '2037711200', 'clave' => '31'],
    ['punto' => '2038210300', 'clave' => '42'],
    ['punto' => '2039210100', 'clave' => '14'],
    ['punto' => '2039210200', 'clave' => '24'],
    ['punto' => '2039211000', 'clave' => '47'],
    ['punto' => '2050010200', 'clave' => '27'],
    ['punto' => '2050010300', 'clave' => '25'],
    ['punto' => '2050010400', 'clave' => '41']
];
// =========================================================
?>

<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Reporte Mensual - Promotor</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../main_md3.css">
    <link rel="stylesheet" href="../estilos/generarreporteMensual.css">
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
                <span>Leche para el bienestar</span>
            </div>
        </div>

        <div class="app-bar-end">
            <md-filled-tonal-button href="../cerrar_sesion.php" style="margin-left: 16px;">
                <md-icon slot="icon">logout</md-icon>
                Salir
            </md-filled-tonal-button>
        </div>
    </header>

    <main class="panel-content">

        <div class="md3-card md3-hero-card">
            <div style="display:flex; align-items:center; gap:16px;">
                <div style="background:var(--md-sys-color-primary-container); border-radius:16px; padding:10px; display:flex;">
                    <md-icon style="color:var(--md-sys-color-on-primary-container); font-size:32px; width:32px; height:32px;">receipt_long</md-icon>
                </div>
                <div>
                    <h2 style="margin:0; font-size:1.6rem; font-weight:500; color:var(--md-sys-color-on-surface);">
                        Reporte Mensual de la Operación en Lecherías
                    </h2>
                    <p style="margin:4px 0 0; font-size:0.9rem; color:var(--md-sys-color-on-surface-variant);">
                        Con venta de leche en polvo de <strong style="color:var(--md-sys-color-primary); font-size:1.1rem;">$<?= htmlspecialchars($precio_litro) ?>/LITRO</strong>
                    </p>
                </div>
            </div>
        </div>

        <div class="md3-card">
            <form id="formReporte" method="POST" action="guardarReporteMensual.php">
                <h3 style="margin: 0 0 16px; font-size:1rem; font-weight:500; color:var(--md-sys-color-on-surface);">
                    <md-icon style="vertical-align:middle; margin-right:6px; color:var(--md-sys-color-primary);">info</md-icon>
                    Datos generales del reporte
                </h3>

                <div class="form-header-grid">
                    <md-outlined-text-field label="Almacén Alimentación" id="almacen" name="almacen" type="text" value="ALMACÉN: <?= htmlspecialchars($nombre_almacen) ?>" readonly style="width:100%;"></md-outlined-text-field>
                    <md-outlined-text-field label="Periodo — Fecha inicio" id="periodo_inicio" name="periodo_inicio" type="date" style="width:100%;"></md-outlined-text-field>
                    <md-outlined-text-field label="Periodo — Fecha fin" id="periodo_fin" name="periodo_fin" type="date" style="width:100%;"></md-outlined-text-field>
                    <md-outlined-text-field label="Fecha de elaboración" id="fecha_elaboracion" name="fecha_elaboracion" type="date" style="width:100%;"></md-outlined-text-field>
                    <md-outlined-text-field label="Fecha de recepción" id="fecha_recepcion" name="fecha_recepcion" type="date" style="width:100%;"></md-outlined-text-field>
                </div>

                <div class="reporte-wrapper">
                    <table class="reporte-table" id="tablaReporte">
                        <thead>
                            <tr class="header-main">
                                <th rowspan="2" class="col-numero">N° PUNTO<br>DE VENTA</th>
                                <th rowspan="2" class="col-clave">CLAVE<br>DE LA<br>TIENDA</th>
                                <th colspan="2">INVENTARIO<br>INICIAL</th>
                                <th rowspan="2">DOTACIÓN<br>RECIBIDA<br>(CAJAS)</th>
                                <th colspan="2">TOTAL (INV INI<br>+ DOT REC.)</th>
                                <th colspan="2">DOT. VENDIDA<br>EN EL PERIODO</th>
                                <th colspan="2">INVENTARIO<br>FINAL</th>
                                <th colspan="2">SEGÚN REG. DE<br>RETIRO DE VENTAS</th>
                                <th rowspan="2">No. DE FAM.<br>QUE NO ACUD.</th>
                                <th colspan="2">SOBRES</th>
                                <th rowspan="2">No. DE DÍAS<br>DE VENTA</th>
                                <th rowspan="2">DE LA DOT.<br>MES QUE CORRESP.</th>
                                <th rowspan="2" class="col-fecha">RECIBIDA<br>FECHA ENTRADA</th>
                                <th rowspan="2" class="col-fecha">CADUCIDAD<br>LECHE</th>
                                <th rowspan="2" class="col-obs">OBSERVACIONES</th>
                            </tr>
                            <tr class="header-sub">
                                <th class="col-cajas">CAJAS</th><th class="col-sobres">SOB.</th>
                                <th class="col-cajas">CAJAS</th><th class="col-sobres">SOB.</th>
                                <th class="col-cajas">CAJAS</th><th class="col-sobres">SOB.</th>
                                <th class="col-cajas">CAJAS</th><th class="col-sobres">SOB.</th>
                                <th class="col-cajas">CAJAS</th><th class="col-sobres">SOB.</th>
                                <th class="col-cajas">ROTOS</th><th class="col-cajas">FALT.</th>
                            </tr>
                        </thead>
                        <tbody id="tablaBody">
                            <?php 
                            // Generamos exactamente 17 filas
                            for ($i = 0; $i < 17; $i++): 
                                $punto_venta = isset($lecherias_db[$i]) ? $lecherias_db[$i]['punto'] : '';
                                $clave_tienda = isset($lecherias_db[$i]) ? $lecherias_db[$i]['clave'] : '';
                                
                                // Si hay datos, los campos identificadores son de solo lectura. Si no, deshabilitamos la fila.
                                $readonly_attr = ($punto_venta !== '') ? 'readonly' : 'disabled';
                                $input_status = ($punto_venta !== '') ? '' : 'disabled';
                            ?>
                            <tr>
                                <td><input type="text" class="cell-input" name="punto_venta[]" value="<?= htmlspecialchars($punto_venta) ?>" <?= $readonly_attr ?>></td>
                                <td><input type="text" class="cell-input" name="clave_tienda[]" value="<?= htmlspecialchars($clave_tienda) ?>" <?= $readonly_attr ?>></td>
                                
                                <td><input type="number" class="cell-input" name="inv_ini_cajas[]" <?= $input_status ?>></td>
                                <td><input type="number" class="cell-input" name="inv_ini_sobres[]" <?= $input_status ?>></td>
                                <td><input type="number" class="cell-input" name="dot_recibida_cajas[]" <?= $input_status ?>></td>
                                <td class="td-total"><input type="number" class="cell-input" name="total_cajas[]" readonly <?= $input_status ?>></td>
                                <td class="td-total"><input type="number" class="cell-input" name="total_sobres[]" readonly <?= $input_status ?>></td>
                                <td><input type="number" class="cell-input" name="dot_vend_cajas[]" <?= $input_status ?>></td>
                                <td><input type="number" class="cell-input" name="dot_vend_sobres[]" <?= $input_status ?>></td>
                                <td><input type="number" class="cell-input" name="inv_fin_cajas[]" <?= $input_status ?>></td>
                                <td><input type="number" class="cell-input" name="inv_fin_sobres[]" <?= $input_status ?>></td>
                                <td><input type="number" class="cell-input" name="retiro_cajas[]" <?= $input_status ?>></td>
                                <td><input type="number" class="cell-input" name="retiro_sobres[]" <?= $input_status ?>></td>
                                <td><input type="number" class="cell-input" name="familias_no_acud[]" <?= $input_status ?>></td>
                                <td><input type="number" class="cell-input" name="sobres_rotos[]" <?= $input_status ?>></td>
                                <td><input type="number" class="cell-input" name="sobres_falt[]" <?= $input_status ?>></td>
                                <td><input type="number" class="cell-input" name="dias_venta[]" <?= $input_status ?>></td>
                                
                                <td>
                                    <select class="cell-select" name="mes_corresp[]" <?= $input_status ?>>
                                        <option value=""></option>
                                        <option value="ENERO">Ene</option>
                                        <option value="FEBRERO">Feb</option>
                                        <option value="MARZO">Mar</option>
                                        <option value="ABRIL">Abr</option>
                                        <option value="MAYO">May</option>
                                        <option value="JUNIO">Jun</option>
                                        <option value="JULIO">Jul</option>
                                        <option value="AGOSTO">Ago</option>
                                        <option value="SEPTIEMBRE">Sep</option>
                                        <option value="OCTUBRE">Oct</option>
                                        <option value="NOVIEMBRE">Nov</option>
                                        <option value="DICIEMBRE">Dic</option>
                                    </select>
                                </td>
                                <td><input type="date" class="cell-input" name="fecha_entrada[]" <?= $input_status ?>></td>
                                <td><input type="date" class="cell-input" name="caducidad[]" <?= $input_status ?>></td>
                                <td><input type="text" class="cell-input" name="observaciones[]" <?= $input_status ?>></td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>

                <p class="nota-legal">
                    ⚠️ NOTA: LOS DATOS QUE APARECEN EN ESTE FORMATO SON FIDEDIGNOS, DE LOS CUALES SE HACEN RESPONSABLES LOS FIRMANTES.
                </p>

                <div class="footer-section">
                    <div class="firma-block">
                        <span class="firma-label">Nombre y Firma</span>
                        <span class="firma-name"><?= htmlspecialchars(strtoupper($nombre_usuario)) ?></span>
                        <span class="firma-role">PROMOTOR SOCIAL</span>
                    </div>

                    <div class="firma-block">
                        <span class="firma-label">Nombre y Firma</span>
                        <md-outlined-text-field
                            id="supervisor" name="supervisor" type="text"
                            placeholder="Nombre del supervisor" style="width:100%; margin-top:4px;">
                        </md-outlined-text-field>
                        <span class="firma-role">SUPERVISOR SOCIAL</span>
                    </div>
                </div>

                <div class="action-bar" style="margin-top: 24px;">
                    <md-filled-button type="submit" id="btnGuardar">
                        <md-icon slot="icon">save</md-icon>
                        Guardar reporte
                    </md-filled-button>
                </div>

            </form>
        </div>
    </main>
<script src="../js/temas_md3.js"></script>
</body>
</html>