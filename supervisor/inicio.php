<?php
require_once __DIR__ . '/../includes/session_guard.php';
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    header("Location: ../iniciosesionSupervisor.php");
    exit();
}
$nombre_usuario = $_SESSION['nombre'] ?? $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - Supervisor</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../main_md3.css">
    <link rel="stylesheet" href="../estilos/iniciocards.css">
    <link rel="stylesheet" href="../estilos/iniciosupervisor.css">

    <!-- PWA -->
    <!-- [OFFLINE DESACTIVADO] <link rel="manifest" href="/manifest.json"> -->
    <meta name="theme-color" content="#6750A4">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Inventarios">
    <link rel="apple-touch-icon" href="/imagenes/Logos/icon-192.png">

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
                <span>Liconsa - Supervisión</span>
            </div>
        </div>

        <div class="app-bar-end">
            <div class="desktop-nav">
                                <md-text-button href="lecherias.php">
                    <md-icon slot="icon">storefront</md-icon>
                    Lecherías
                </md-text-button>
                <div style="position: relative;">
                    <md-text-button id="btn-rev" onclick="abrirMenu('menu-rev')">
                        Requerimiento de dotacion
                        <md-icon slot="icon">arrow_drop_down</md-icon>
                    </md-text-button>
                    <md-menu id="menu-rev" anchor="btn-rev">
                        <md-menu-item href="requerimientodedotacion.php">
                            <div slot="headline">Revisar</div>
                            <md-icon slot="start">fact_check</md-icon>
                        </md-menu-item>
                        <md-menu-item href="historialGlobal.php">
                            <div slot="headline">Historial General</div>
                            <md-icon slot="start">history</md-icon>
                        </md-menu-item>
                    </md-menu>
                </div>

                <div style="position: relative;">
                    <md-text-button id="btn-prom" onclick="abrirMenu('menu-prom')">
                        Inventarios leche en polvo
                        <md-icon slot="icon">arrow_drop_down</md-icon>
                    </md-text-button>
                    <md-menu id="menu-prom" anchor="btn-prom">
                        <md-menu-item href="listaPromotores.php">
                            <div slot="headline">Ver Mis Promotores</div>
                            <md-icon slot="start">group</md-icon>
                        </md-menu-item>
                    </md-menu>
                </div>

                                <md-text-button href="reporte_mensual.php">
                    <md-icon slot="icon">bar_chart</md-icon>
                    Reporte Mensual
                </md-text-button>

                <a href="solicitudes.php" style="position:relative;display:inline-flex;align-items:center;gap:6px;
                    padding:0 12px;height:40px;border-radius:20px;text-decoration:none;
                    color:var(--md-sys-color-on-surface);font-size:.875rem;font-weight:500;">
                    <md-icon>inbox</md-icon>
                    Solicitudes
                    <span id="badgeSolicitudes" style="display:none;position:absolute;top:4px;right:4px;
                        background:var(--md-sys-color-error);color:var(--md-sys-color-on-error);
                        font-size:.7rem;font-weight:700;min-width:18px;height:18px;border-radius:999px;
                        display:none;align-items:center;justify-content:center;padding:0 4px;"></span>
                </a>
            </div>

            <md-filled-tonal-button href="../cerrar_sesionsupervisor.php" style="margin-left: 16px;">
                <md-icon slot="icon">logout</md-icon> Salir
            </md-filled-tonal-button>
        </div>
    </header>

    <div id="drawer-scrim" class="md3-drawer-scrim" onclick="toggleDrawer()"></div>

    <aside class="md3-drawer" id="mobile-drawer">
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 16px 8px 24px;">
            <span style="font-size: 1.25rem; font-weight: 500; color: var(--md-sys-color-on-surface);">Menú Supervisor</span>
            <md-icon-button onclick="toggleDrawer()"><md-icon>close</md-icon></md-icon-button>
        </div>
        <div style="overflow-y: auto; flex-grow: 1;">
            <md-list style="background: transparent;">
                <md-divider style="margin: 8px 0;"></md-divider>
                                <md-list-item href="lecherias.php" type="button">
                    <div slot="headline">Lecherías</div>
                    <md-icon slot="start">storefront</md-icon>
                </md-list-item>
                
                <div class="drawer-section-title">Requerimiento de Dotación</div>
                <md-list-item href="requerimientodedotacion.php" type="button">
                    <div slot="headline">Generar Requerimiento</div>
                    <md-icon slot="start">description</md-icon>
                </md-list-item>

                <md-divider style="margin: 8px 0;"></md-divider>

                <div class="drawer-section-title">Revisión de Inventarios</div>
                <md-list-item href="validarInventarios.php" type="button">
                    <div slot="headline">Validar Pendientes</div>
                    <md-icon slot="start">fact_check</md-icon>
                </md-list-item>
                               <md-divider style="margin: 8px 0;"></md-divider>
                <md-list-item href="historialGlobal.php" type="button">
                    <div slot="headline">Historial General</div>
                    <md-icon slot="start">history</md-icon>
                </md-list-item>


                <md-divider style="margin: 8px 0;"></md-divider>

                <div class="drawer-section-title">Promotores</div>
                <md-list-item href="listaPromotores.php" type="button">
                    <div slot="headline">Ver Mis Promotores</div>
                    <md-icon slot="start">group</md-icon>
                </md-list-item>

                <md-divider style="margin: 8px 0;"></md-divider>

                <div class="drawer-section-title">Reporte Mensual</div>
                <md-list-item href="reporte_mensual.php" type="button">
                    <div slot="headline">Ver Reporte Mensual</div>
                    <md-icon slot="start">bar_chart</md-icon>
                </md-list-item>
            </md-list>
        </div>
    </aside>

    <main class="panel-content">
        <div class="md3-hero-card">
            <canvas id="hero-canvas"></canvas>
            <h2 style="font-size: 2.25rem; font-weight: 500; margin: 0; letter-spacing: -0.5px;">Panel de Supervisión</h2>
            <p style="font-size: 1.1rem; margin: 8px 0 20px; max-width: 600px; line-height: 1.5; opacity: 0.9;">
                Bienvenido, <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong>. 
                Aquí podrás gestionar a tus promotores asignados y validar los cierres de inventario de sus respectivas lecherías.
            </p>
            <div style="margin-top: 12px;">
                <md-filled-button onclick="location.href='validarInventarios.php'" style="--md-filled-button-container-shape: 16px; height: 48px;">
                    <md-icon slot="icon">fact_check</md-icon>
                    Validar Cierres Pendientes
                </md-filled-button>
            </div>
        </div>

        <h3 style="font-size: 1.25rem; font-weight: 500; color: var(--md-sys-color-on-surface); margin-top: 16px; margin-bottom: 0;">
            Acciones Rápidas
        </h3>
        <div class="md3-dashboard-grid">
            <a href="estadisticas.php" class="md3-action-card">
                <div class="action-card-icon" style="background-color: var(--md-sys-color-tertiary-container); color: var(--md-sys-color-on-tertiary-container);">
                    <md-icon>monitoring</md-icon>
                </div>
                <h4 class="action-card-title">Estadísticas de Zona</h4>
                <p class="action-card-desc">Revisa el rendimiento, distribución y consumo de las lecherías a tu cargo.</p>
            </a>

            <a href="validarInventarios.php" class="md3-action-card">
                <div class="action-card-icon">
                    <md-icon>assignment_turned_in</md-icon>
                </div>
                <h4 class="action-card-title">Validar Cierres</h4>
                <p class="action-card-desc">Autoriza los inventarios mensuales enviados por tus promotores.</p>
            </a>
        </div>

        <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-top:24px; margin-bottom:16px;">
            <h3 style="font-size:1.25rem; font-weight:500; color:var(--md-sys-color-on-surface); margin:0;">
                Mis Promotores Asignados
            </h3>

            <div class="md3-card" style="display:flex; flex-wrap:wrap; align-items:center; gap:12px; padding:10px 16px; margin:0;">
                <span class="material-symbols-outlined" style="color:var(--md-sys-color-primary);">calendar_month</span>
                <span style="font-weight:500;">Avance del mes:</span>
                <select class="md3-input" id="avance_mes" style="margin:0; cursor:pointer;">
                    <option value="1"  <?= date('n')==1  ? 'selected' : '' ?>>Enero</option>
                    <option value="2"  <?= date('n')==2  ? 'selected' : '' ?>>Febrero</option>
                    <option value="3"  <?= date('n')==3  ? 'selected' : '' ?>>Marzo</option>
                    <option value="4"  <?= date('n')==4  ? 'selected' : '' ?>>Abril</option>
                    <option value="5"  <?= date('n')==5  ? 'selected' : '' ?>>Mayo</option>
                    <option value="6"  <?= date('n')==6  ? 'selected' : '' ?>>Junio</option>
                    <option value="7"  <?= date('n')==7  ? 'selected' : '' ?>>Julio</option>
                    <option value="8"  <?= date('n')==8  ? 'selected' : '' ?>>Agosto</option>
                    <option value="9"  <?= date('n')==9  ? 'selected' : '' ?>>Septiembre</option>
                    <option value="10" <?= date('n')==10 ? 'selected' : '' ?>>Octubre</option>
                    <option value="11" <?= date('n')==11 ? 'selected' : '' ?>>Noviembre</option>
                    <option value="12" <?= date('n')==12 ? 'selected' : '' ?>>Diciembre</option>
                </select>
                <input class="md3-input" type="number" id="avance_anio" value="<?= date('Y') ?>"
                       style="max-width:96px; margin:0; text-align:center;">
            </div>
        </div>

        <div class="promotores-grid" id="promotoresGrid">
            <div class="promotor-card is-skeleton">
                <div class="promotor-header">
                    <div class="sk-avatar skeleton" style="width: 48px; height: 48px; border-radius: 50%;"></div>
                    <div style="flex-grow: 1;">
                        <div class="sk-line skeleton" style="width: 70%; margin-bottom: 8px;"></div>
                        <div class="sk-line skeleton" style="width: 40%;"></div>
                    </div>
                </div>
            </div>
            <div class="promotor-card is-skeleton">
                <div class="promotor-header">
                    <div class="sk-avatar skeleton" style="width: 48px; height: 48px; border-radius: 50%;"></div>
                    <div style="flex-grow: 1;">
                        <div class="sk-line skeleton" style="width: 60%; margin-bottom: 8px;"></div>
                        <div class="sk-line skeleton" style="width: 50%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

<div class="md3-dialog-backdrop" id="modalOpcionesPromotor">
        <div class="md3-dialog-surface">
            <div class="md3-dialog-header">
                <div>
                    <h3 class="md3-dialog-title" id="modalPromotorTitulo">Promotor: [Nombre]</h3>
                    <p class="md3-dialog-subtitle">Gestión de lecherías asignadas</p>
                </div>
                <md-icon-button id="btnCerrarModalPromotor">
                    <md-icon>close</md-icon>
                </md-icon-button>
            </div>
            
            <div class="md3-dialog-content" style="padding: 0 24px;">
                <p style="color: var(--md-sys-color-on-surface-variant); margin-bottom: 12px;">Lecherías a cargo:</p>
                <md-list id="listaLecheriasModal" style="background: var(--md-sys-color-surface-container-low); border-radius: 12px; max-height: 200px; overflow-y: auto;">
                    </md-list>
            </div>

            <div class="md3-dialog-actions" style="margin-top: 16px;">
                <md-outlined-button id="btnIrValidarPromotor">
                    <md-icon slot="icon">fact_check</md-icon> Validar sus inventarios
                </md-outlined-button>
            </div>
        </div>
    </div>

    <script src="../js/temas_md3.js"></script>
    <script src="../js/hero_physics.js"></script> <script src="../js/inicio_supervisor.js"></script> <script>
        function abrirMenu(id) {
            document.querySelectorAll('md-menu').forEach(menu => {
                if (menu.id !== id) menu.open = false;
            });
            const menu = document.getElementById(id);
            menu.open = !menu.open;
        }

        function toggleDrawer() {
            const drawer = document.getElementById('mobile-drawer');
            const scrim = document.getElementById('drawer-scrim');
            if(drawer) drawer.classList.toggle('open');
            if(scrim) scrim.classList.toggle('open');
        }

        document.addEventListener('click', (event) => {
            if (!event.target.closest('md-menu') && !event.target.closest('md-text-button')) {
                document.querySelectorAll('md-menu').forEach(menu => menu.open = false);
            }
        });
    </script>
    <!-- Guardar sesión en IndexedDB para acceso offline -->
    <script>
    window.__SESION_PHP__ = {
        usuario:   '<?= htmlspecialchars($_SESSION['usuario'],  ENT_QUOTES) ?>',
        nombre:    '<?= htmlspecialchars($_SESSION['nombre'],   ENT_QUOTES) ?>',
        rol:       '<?= htmlspecialchars($_SESSION['rol'],      ENT_QUOTES) ?>',
        clave_rol: '<?= htmlspecialchars($_SESSION['clave_rol'] ?? '', ENT_QUOTES) ?>',
    };
    </script>
    <!-- [OFFLINE DESACTIVADO] <script src="../js/pwa_offline.js"></script> -->
    <script src="../js/offline_login.js"></script>
    <!-- [OFFLINE DESACTIVADO] <script src="../js/offline_preload.js"></script> -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.__SESION_PHP__ && window.OfflineLogin) {
            window.OfflineLogin.guardarSesionOffline(window.__SESION_PHP__);
        }
        if (window.OfflinePreload && navigator.onLine) {
            setTimeout(() => { window.OfflinePreload.runIfStale('/supervisor'); }, 2500);
        }

        // Badge de solicitudes pendientes
        fetch('api_solicitudes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'contar' })
        }).then(r => r.json()).then(d => {
            const badge = document.getElementById('badgeSolicitudes');
            if (badge && d.count > 0) {
                badge.textContent = d.count;
                badge.style.display = 'flex';
            }
        }).catch(() => {});
    });
    </script>
</body>
</html>