<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    header("Location: ../iniciosesionPromotor.php");
    exit();
}
require_once '../Database.php';
$pdo = Database::getInstance();
$nombre_usuario = $_SESSION['nombre'] ?? $_SESSION['usuario'];
$precio_litro = "4.50";
$sql_resumen = "SELECT L.TIPO_PUNTO_VENTA, COUNT(*) as TOTAL
                FROM LECHERIA L
                INNER JOIN USUARIOS_INVENTARIOS U ON L.PROMOTOR = U.CLAVE_ROL
                WHERE L.EFD_NUMERO = 20 AND U.USUARIO = :usuario
                GROUP BY L.TIPO_PUNTO_VENTA";

$stmt_resumen = $pdo->prepare($sql_resumen);
$stmt_resumen->execute([':usuario' => $_SESSION['usuario']]);
$conteo = $stmt_resumen->fetchAll(PDO::FETCH_ASSOC);

$total_lecherias = 0;
$lecherias_450 = 0;
$lecherias_650 = 0;
foreach ($conteo as $fila) {
    $total_lecherias += $fila['TOTAL'];
    if ($fila['TIPO_PUNTO_VENTA'] == 0) {
        $lecherias_450 += $fila['TOTAL'];
    } elseif ($fila['TIPO_PUNTO_VENTA'] == 1 || $fila['TIPO_PUNTO_VENTA'] == 2) {
        // Agrupamos las de 6.50 y DM
        $lecherias_650 += $fila['TOTAL'];
    }
}
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
                            <div slot="headline">Generar</div>
                            <md-icon slot="start">add_box</md-icon>
                        </md-menu-item>
                        <md-menu-item href="editarinventarioMensual.php">
                            <div slot="headline">Editar</div>
                            <md-icon slot="start">edit</md-icon>
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
                        <md-menu-item href="generarreporteMensual.php">
                            <div slot="headline">Generar</div>
                            <md-icon slot="start">receipt_long</md-icon>
                        </md-menu-item>
                        <md-menu-item href="#">
                            <div slot="headline">Consultar</div>
                            <md-icon slot="start">find_in_page</md-icon>
                        </md-menu-item>
                    </md-menu>
                </div>

                <div style="position: relative;">
                    <md-text-button id="btn-req" onclick="abrirMenu('menu-req')">
                        Requerimiento
                        <md-icon slot="icon">arrow_drop_down</md-icon>
                    </md-text-button>
                    <md-menu id="menu-req" anchor="btn-req">
                        <md-menu-item href="requerimiento.php">
                            <div slot="headline">Generar</div>
                            <md-icon slot="start">inventory</md-icon>
                        </md-menu-item>
                        <md-menu-item href="#">
                            <div slot="headline">Consultar</div>
                            <md-icon slot="start">manage_search</md-icon>
                        </md-menu-item>
                        <md-menu-item href="#">
                            <div slot="headline">Enviar reportes</div>
                            <md-icon slot="start">send</md-icon>
                        </md-menu-item>
                    </md-menu>
                </div>
            </div>

            <md-filled-tonal-button href="../cerrar_sesion.php" style="margin-left: 16px;">
                <md-icon slot="icon">logout</md-icon>
                Salir
            </md-filled-tonal-button>

        </div>
    </header>
    <main class="panel-content">
        <div class="md3-card md3-hero-card">
            <div style="display:flex; align-items:center; gap:16px;">
                <div
                    style="background:var(--md-sys-color-primary-container); border-radius:16px; padding:10px; display:flex;">
                    <md-icon
                        style="color:var(--md-sys-color-on-primary-container); font-size:32px; width:32px; height:32px;">receipt_long</md-icon>
                </div>
                <div>
                    <h2 style="margin:0; font-size:1.6rem; font-weight:500; color:var(--md-sys-color-on-surface);">
                        Reporte Mensual de la Operación en Lecherías
                    </h2>
                    <p style="margin:4px 0 0; font-size:0.9rem; color:var(--md-sys-color-on-surface-variant);">
                        Con venta de leche en polvo de
                        <strong id="precioDisplay"
                            style="color:var(--md-sys-color-primary); font-size:1.1rem;">$<?= htmlspecialchars($precio_litro) ?>/LITRO</strong>
                    </p>
                </div>
            </div>
        </div>
        <div class="md3-card">
            <form id="formReporte" method="POST" action="guardarReporteMensual.php">
                <h3 style="margin: 0 0 16px; font-size:1rem; font-weight:500; color:var(--md-sys-color-on-surface);">
                    <md-icon
                        style="vertical-align:middle; margin-right:6px; color:var(--md-sys-color-primary);">info</md-icon>
                    Datos generales del reporte
                </h3>

                <div class="form-header-grid">
                    <md-outlined-select label="Mes del Reporte" id="selectMesReporte" name="mes_reporte"
                        style="width:100%;">
                        <md-select-option value="">
                            <div slot="headline">Selecciona...</div>
                        </md-select-option>
                        <md-select-option value="1">
                            <div slot="headline">Enero</div>
                        </md-select-option>
                        <md-select-option value="2">
                            <div slot="headline">Febrero</div>
                        </md-select-option>
                        <md-select-option value="3">
                            <div slot="headline">Marzo</div>
                        </md-select-option>
                        <md-select-option value="4">
                            <div slot="headline">Abril</div>
                        </md-select-option>
                        <md-select-option value="5">
                            <div slot="headline">Mayo</div>
                        </md-select-option>
                        <md-select-option value="6">
                            <div slot="headline">Junio</div>
                        </md-select-option>
                        <md-select-option value="7">
                            <div slot="headline">Julio</div>
                        </md-select-option>
                        <md-select-option value="8">
                            <div slot="headline">Agosto</div>
                        </md-select-option>
                        <md-select-option value="9">
                            <div slot="headline">Septiembre</div>
                        </md-select-option>
                        <md-select-option value="10">
                            <div slot="headline">Octubre</div>
                        </md-select-option>
                        <md-select-option value="11">
                            <div slot="headline">Noviembre</div>
                        </md-select-option>
                        <md-select-option value="12">
                            <div slot="headline">Diciembre</div>
                        </md-select-option>
                    </md-outlined-select>

                    <md-outlined-text-field label="Año del Reporte" id="inputAnioReporte" name="anio_reporte"
                        type="number" value="<?= date('Y') ?>" style="width:100%;"></md-outlined-text-field>
                    <md-outlined-select label="Tipo de Venta (Precio)" id="selectTipoVenta" name="tipo_venta"
                        style="width:100%;">
                        <md-select-option value="0" selected>
                            <div slot="headline">$4.50 / Litro</div>
                        </md-select-option>
                        <md-select-option value="1">
                            <div slot="headline">$6.50 / Litro</div>
                        </md-select-option>
                    </md-outlined-select>

                    <md-outlined-select label="Almacén Alimentación" id="selectAlmacen" name="almacen"
                        style="width:100%;">
                        <md-select-option value="">Cargando almacenes...</md-select-option>
                    </md-outlined-select>

                    <md-outlined-text-field label="Periodo — Fecha inicio" id="periodo_inicio" name="periodo_inicio"
                        type="date" style="width:100%;"></md-outlined-text-field>
                    <md-outlined-text-field label="Periodo — Fecha fin" id="periodo_fin" name="periodo_fin" type="date"
                        style="width:100%;"></md-outlined-text-field>
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
                                <th class="col-cajas">CAJAS</th>
                                <th class="col-sobres">SOB.</th>
                                <th class="col-cajas">CAJAS</th>
                                <th class="col-sobres">SOB.</th>
                                <th class="col-cajas">CAJAS</th>
                                <th class="col-sobres">SOB.</th>
                                <th class="col-cajas">CAJAS</th>
                                <th class="col-sobres">SOB.</th>
                                <th class="col-cajas">CAJAS</th>
                                <th class="col-sobres">SOB.</th>
                                <th class="col-cajas">ROTOS</th>
                                <th class="col-cajas">FALT.</th>
                            </tr>
                        </thead>
                        <tbody id="tablaBody">
                            <?php
                            for ($i = 0; $i < 17; $i++):
                                ?>
                                <tr>
                                    <td><input type="text" class="cell-input" name="punto_venta[]" disabled></td>
                                    <td><input type="text" class="cell-input" name="clave_tienda[]" disabled></td>

                                    <td><input type="number" class="cell-input" name="inv_ini_cajas[]" disabled></td>
                                    <td><input type="number" class="cell-input" name="inv_ini_sobres[]" disabled></td>
                                    <td><input type="number" class="cell-input" name="dot_recibida_cajas[]" disabled></td>
                                    <td class="td-total"><input type="number" class="cell-input" name="total_cajas[]"
                                            readonly disabled></td>
                                    <td class="td-total"><input type="number" class="cell-input" name="total_sobres[]"
                                            readonly disabled></td>
                                    <td><input type="number" class="cell-input" name="dot_vend_cajas[]" disabled></td>
                                    <td><input type="number" class="cell-input" name="dot_vend_sobres[]" disabled></td>
                                    <td><input type="number" class="cell-input" name="inv_fin_cajas[]" disabled></td>
                                    <td><input type="number" class="cell-input" name="inv_fin_sobres[]" disabled></td>
                                    <td><input type="number" class="cell-input" name="retiro_cajas[]" disabled></td>
                                    <td><input type="number" class="cell-input" name="retiro_sobres[]" disabled></td>
                                    <td><input type="number" class="cell-input" name="familias_no_acud[]" disabled></td>
                                    <td><input type="number" class="cell-input" name="sobres_rotos[]" disabled></td>
                                    <td><input type="number" class="cell-input" name="sobres_falt[]" disabled></td>
                                    <td><input type="number" class="cell-input" name="dias_venta[]" disabled></td>

                                    <td>
                                        <select class="cell-select" name="mes_corresp[]" disabled>
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
<td><input type="date" class="cell-input" name="fecha_entrada[]" disabled></td>
                                    <td><input type="text" class="cell-input" name="caducidad[]" placeholder="YYYY-MM-DD"
                                            disabled></td>
                                    <td><input type="text" class="cell-input" name="observaciones[]" disabled></td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>

                <p class="nota-legal">
                    ⚠️ NOTA: LOS DATOS QUE APARECEN EN ESTE FORMATO SON FIDEDIGNOS, DE LOS CUALES SE HACEN RESPONSABLES
                    LOS FIRMANTES.
                </p>

                <div class="footer-section">
                    <div class="firma-block">
                        <span class="firma-label">Nombre y Firma</span>
                        <span class="firma-name"><?= htmlspecialchars(strtoupper($nombre_usuario)) ?></span>
                        <span class="firma-role">PROMOTOR SOCIAL</span>
                    </div>

                    <div class="firma-block">
                        <span class="firma-label">Nombre y Firma</span>
                        <md-outlined-text-field id="supervisor" name="supervisor" type="text"
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
        <div class="md3-card"
            style="margin-top: 8px; background-color: var(--md-sys-color-secondary-container); color: var(--md-sys-color-on-secondary-container); text-align: center; border: none;">
            <md-icon
                style="font-size: 36px; margin-bottom: 8px; color: var(--md-sys-color-primary);">analytics</md-icon>
            <h3 style="margin: 0 0 12px; font-size: 1.25rem; font-weight: 500;">Resumen de tu Padrón</h3>
            <p style="margin: 0; font-size: 1rem; line-height: 1.6;">
                Actualmente tienes asignadas <strong><?= $total_lecherias ?> lecherías</strong> en total.<br>
                De las cuales: <strong style="color: var(--md-sys-color-primary);"><?= $lecherias_450 ?></strong> son de
                $4.50 y
                <strong style="color: var(--md-sys-color-primary);"><?= $lecherias_650 ?></strong> son de $6.50.
            </p>
        </div>

        <div id="cardEstadoInventarios" class="md3-card"
            style="display: none; margin-top: 8px; border: 1px solid var(--md-sys-color-outline-variant);">
            <h3 style="margin: 0 0 16px; font-size: 1.1rem; font-weight: 500; color: var(--md-sys-color-on-surface);">
                <md-icon
                    style="vertical-align: middle; margin-right: 6px; color: var(--md-sys-color-primary);">history</md-icon>
                Estado de Inventarios Anteriores
            </h3>

            <div id="listaEstadoInventarios"
                style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px;">
            </div>
        </div>
    </main>
    <script src="../js/temas_md3.js"></script>
    <script src="../js/reporteMensual.js"></script>
    <script>
        // Controla los menús desplegables superiores (desktop)
        function abrirMenu(id) {
            document.querySelectorAll('md-menu').forEach(m => {
                if (m.id !== id) m.open = false;
            });
            const menu = document.getElementById(id);
            menu.open = !menu.open;
        }

        // Controla el menú lateral (mobile)
        function toggleDrawer() {
            document.getElementById('mobile-drawer').classList.toggle('open');
            // Nota: Asegúrate de tener un elemento con id 'drawer-scrim' en tu HTML si usas esta línea
            document.getElementById('drawer-scrim').classList.toggle('open'); 
        }

        // Cierra los menús desplegables si haces clic afuera de ellos
        document.addEventListener('click', (e) => {
            if (!e.target.closest('md-menu') && !e.target.closest('md-text-button')) {
                document.querySelectorAll('md-menu').forEach(m => m.open = false);
            }
        });
    </script>
</body>

</html>