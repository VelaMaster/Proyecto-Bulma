<?php
require_once __DIR__ . '/../includes/session_guard.php';
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    header("Location: ../iniciosesionSupervisor.php");
    exit();
}
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

$promotorId = isset($_GET['promotor']) ? (int)$_GET['promotor'] : 0;
$mesParam   = isset($_GET['mes'])      ? (int)$_GET['mes']      : 0;
$anioParam  = isset($_GET['anio'])     ? (int)$_GET['anio']     : 0;

if ($promotorId <= 0 || $mesParam < 1 || $mesParam > 12 || $anioParam < 2000) {
    header('Location: listadoReportesPromotores.php');
    exit();
}

$pdo = DatabaseSQLite::getInstance();
$id_supervisor = $_SESSION['clave_rol'] ?? null;

// Validar que el promotor tenga al menos una lechería mapeada a este supervisor
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM lecheria L
    JOIN mapeo_supervisor_lecheria M
          ON M.LECHER = L.LECHER AND M.ID_SUPERVISOR = :sup
    WHERE L.PROMOTOR = :prom
");
$stmt->execute([':sup' => $id_supervisor, ':prom' => $promotorId]);
if ((int)$stmt->fetchColumn() === 0) {
    header('Location: listadoReportesPromotores.php?err=sin_acceso');
    exit();
}

// Nombre del promotor desde la tabla espejo de Firebird
$stmt = $pdo->prepare("SELECT TRIM(PMT_NOMBRE) FROM promotor WHERE PMT_NUMERO = :id LIMIT 1");
$stmt->execute([':id' => $promotorId]);
$nombrePromotor = trim((string)$stmt->fetchColumn());
$nombrePromotor = $nombrePromotor !== '' ? $nombrePromotor : ('Promotor #' . $promotorId);

$nombreSupervisor = $_SESSION['nombre'] ?? $_SESSION['usuario'];

// Estado aprobado actual (busca ambas variantes de usuario_captura)
$stmt = $pdo->prepare("SELECT USUARIO FROM usuarios_inventarios
                       WHERE CLAVE_ROL = :id AND ROL = 'promotor' LIMIT 1");
$stmt->execute([':id' => $promotorId]);
$usuarioReal = $stmt->fetchColumn() ?: null;
$keys = array_values(array_filter(array_unique([$usuarioReal, 'promotor_' . $promotorId])));
$ph   = implode(',', array_fill(0, count($keys), '?'));
$stmt = $pdo->prepare("SELECT MAX(aprobado) AS apr, MAX(fecha_aprobacion) AS fa,
                              COUNT(*) AS n
                       FROM reporte_mensual_lecher
                       WHERE usuario_captura IN ($ph) AND mes = ? AND anio = ?");
$stmt->execute(array_merge($keys, [$mesParam, $anioParam]));
$est = $stmt->fetch() ?: ['apr' => 0, 'fa' => null, 'n' => 0];
$yaAprobado = (int)($est['apr'] ?? 0) === 1;

$nombresMes = ['', 'Enero','Febrero','Marzo','Abril','Mayo','Junio',
               'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$mesNombre  = $nombresMes[$mesParam] ?? (string)$mesParam;
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Mensual — <?= htmlspecialchars($nombrePromotor) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://esm.run">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../main_md3.css">
    <link rel="stylesheet" href="../estilos/generarreporteMensual.css">

    <script type="importmap">
    { "imports": { "@material/web/": "https://esm.run/@material/web/" } }
    </script>
    <script type="module"> import '@material/web/all.js'; </script>
</head>

<body>
    <header class="md3-top-app-bar">
        <div class="app-bar-start">
            <md-icon-button class="mobile-menu-btn" onclick="toggleDrawer()">
                <md-icon>menu</md-icon>
            </md-icon-button>
            <div class="app-brand">
                <span>Liconsa — Supervisión</span>
            </div>
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
            <div style="display:flex; align-items:center; gap:16px;">
                <div style="background:var(--md-sys-color-primary-container); border-radius:16px; padding:10px; display:flex;">
                    <md-icon style="color:var(--md-sys-color-on-primary-container); font-size:32px; width:32px; height:32px;">receipt_long</md-icon>
                </div>
                <div>
                    <h2 style="margin:0; font-size:1.6rem; font-weight:500; color:var(--md-sys-color-on-surface);">
                        Reporte Mensual — Edición / Aprobación
                    </h2>
                    <p style="margin:4px 0 0; font-size:0.9rem; color:var(--md-sys-color-on-surface-variant);">
                        Promotor: <strong><?= htmlspecialchars($nombrePromotor) ?></strong>
                        (#<?= $promotorId ?>)
                    </p>
                </div>
            </div>
        </div>

        <?php if ($yaAprobado): ?>
        <div class="md3-card" style="background:var(--md-sys-color-primary-container); color:var(--md-sys-color-on-primary-container); border:none;">
            <div style="display:flex; align-items:center; gap:10px;">
                <md-icon>verified</md-icon>
                <span style="font-weight:500;">
                    Este reporte ya fue APROBADO para Distribución
                    <?= !empty($est['fa']) ? '— ' . htmlspecialchars($est['fa']) : '' ?>.
                </span>
            </div>
        </div>
        <?php endif; ?>

        <div class="md3-card">
            <form id="formReporte" method="POST" onsubmit="return false;">
                <h3 style="margin: 0 0 16px; font-size:1rem; font-weight:500; color:var(--md-sys-color-on-surface);">
                    <md-icon style="vertical-align:middle; margin-right:6px; color:var(--md-sys-color-primary);">info</md-icon>
                    Datos generales del reporte
                </h3>

                <div class="form-header-grid">
                    <md-outlined-text-field label="Mes" id="selectMesReporte" name="mes_reporte"
                        value="<?= htmlspecialchars($mesNombre) ?>" readonly style="width:100%;"></md-outlined-text-field>
                    <md-outlined-text-field label="Año del Reporte" id="inputAnioReporte" name="anio_reporte"
                        type="number" value="<?= $anioParam ?>" readonly style="width:100%;"></md-outlined-text-field>
                    <md-outlined-text-field label="Periodo — Fecha inicio" id="periodo_inicio" name="periodo_inicio"
                        type="date" style="width:100%;"></md-outlined-text-field>
                    <md-outlined-text-field label="Periodo — Fecha fin" id="periodo_fin" name="periodo_fin"
                        type="date" style="width:100%;"></md-outlined-text-field>
                </div>

                <div id="contenedorTablas" style="margin-top:8px;">
                    <div style="text-align:center; padding:32px; color:var(--md-sys-color-on-surface-variant);">
                        Cargando lecherías del promotor...
                    </div>
                </div>

                <p class="nota-legal">
                    ⚠️ NOTA: LOS DATOS QUE APARECEN EN ESTE FORMATO SON FIDEDIGNOS, DE LOS CUALES SE HACEN RESPONSABLES
                    LOS FIRMANTES.
                </p>

                <div class="footer-section">
                    <div class="firma-block">
                        <span class="firma-label">Nombre y Firma</span>
                        <span class="firma-name"><?= htmlspecialchars(strtoupper($nombrePromotor)) ?></span>
                        <span class="firma-role">PROMOTOR SOCIAL</span>
                    </div>
                    <div class="firma-block">
                        <span class="firma-label">Nombre y Firma</span>
                        <span class="firma-name" id="nombreSupervisor"><?= htmlspecialchars(strtoupper($nombreSupervisor)) ?></span>
                        <input type="hidden" id="supervisor" name="supervisor" value="<?= htmlspecialchars($nombreSupervisor) ?>">
                        <span class="firma-role">SUPERVISOR SOCIAL</span>
                    </div>
                </div>

                <div class="action-bar" style="margin-top:24px; display:flex; gap:16px; align-items:center; flex-wrap:wrap;">
                    <md-filled-tonal-button type="button" id="btnGuardar">
                        <md-icon slot="icon">save</md-icon> Guardar cambios
                    </md-filled-tonal-button>

                    <md-filled-button type="button" id="btnAprobar" <?= $yaAprobado ? 'disabled' : '' ?>>
                        <md-icon slot="icon">verified</md-icon>
                        <?= $yaAprobado ? 'Ya aprobado' : 'Aprobar para Distribución' ?>
                    </md-filled-button>
                </div>
            </form>
        </div>
    </main>

    <script>
        window.REPORTE_CTX = {
            modo:        'supervisor',
            promotor_id: <?= $promotorId ?>,
            mes:         <?= $mesParam ?>,
            anio:        <?= $anioParam ?>,
            yaAprobado:  <?= $yaAprobado ? 'true' : 'false' ?>,
            endpoints: {
                lecherias: 'obtener_lecherias_promotor.php',
                reporte:   'obtener_reporte_promotor.php',
                guardar:   'guardar_reporte_promotor.php',
                aprobar:   'aprobar_reporte_promotor.php'
            }
        };
        function toggleDrawer(){
            document.getElementById('mobile-drawer')?.classList.toggle('open');
            document.getElementById('drawer-scrim')?.classList.toggle('open');
        }

        // Borrar memoria de mes/año si el supervisor sale del flujo
        // Reporte Mensual (Inicio, Requerimiento, Salir).
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[href]').forEach(el => {
                const href = el.getAttribute('href') || '';
                const enFlujo = /listadoReportesPromotores\.php|editar_reporte_promotor\.php/.test(href);
                if (!enFlujo && href) {
                    el.addEventListener('click', () => {
                        sessionStorage.removeItem('rpt_supervisor_mes');
                        sessionStorage.removeItem('rpt_supervisor_anio');
                    });
                }
            });
        });
    </script>
    <script src="../js/temas_md3.js"></script>
    <script src="../js/reporteMensualSupervisor.js"></script>
</body>
</html>
