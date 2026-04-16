<?php
session_start();
// Validación de seguridad para Supervisor
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
    <title>Panel de Control - Supervisor</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../main_md3.css">
    <link rel="stylesheet" href="../estilos/iniciocards.css">
    <style>
        /* Estilos específicos para el Hero de Supervisor */
        .md3-hero-card {
            background-color: var(--md-sys-color-primary-container, #2b2b2b);
            color: var(--md-sys-color-on-primary-container);
            border-radius: 28px;
            padding: 32px;
            margin-bottom: 24px;
        }
    </style>
    <script type="importmap">
        { "imports": { "@material/web/": "https://esm.run/@material/web/" } }
    </script>
    <script type="module"> import '@material/web/all.js'; </script>
</head>

<body>
    <header class="md3-top-app-bar">
        <div class="app-bar-start">
            <md-icon-button onclick="toggleDrawer()"><md-icon>menu</md-icon></md-icon-button>
            <div class="app-brand"><span>Liconsa - Supervisión</span></div>
        </div>

        <div class="app-bar-end">
            <div class="desktop-nav">
                <div style="position: relative;">
                    <md-text-button id="btn-rev" onclick="abrirMenu('menu-rev')">
                        Revisión de Inventarios
                        <md-icon slot="icon">arrow_drop_down</md-icon>
                    </md-text-button>
                    <md-menu id="menu-rev" anchor="btn-rev">
                        <md-menu-item href="validarInventarios.php">
                            <div slot="headline">Validar Pendientes</div>
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
                        Gestión Promotores
                        <md-icon slot="icon">arrow_drop_down</md-icon>
                    </md-text-button>
                    <md-menu id="menu-prom" anchor="btn-prom">
                        <md-menu-item href="listaPromotores.php">
                            <div slot="headline">Ver Mis Promotores</div>
                            <md-icon slot="start">group</md-icon>
                        </md-menu-item>
                    </md-menu>
                </div>
            </div>

            <md-filled-tonal-button href="../cerrar_sesion.php" style="margin-left: 16px;">
                <md-icon slot="icon">logout</md-icon> Salir
            </md-filled-tonal-button>
        </div>
    </header>

    <aside class="md3-drawer" id="mobile-drawer">
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px;">
            <span style="font-size: 1.25rem; font-weight: 500;">Menú Supervisor</span>
            <md-icon-button onclick="toggleDrawer()"><md-icon>close</md-icon></md-icon-button>
        </div>
        <md-list>
            <md-list-item href="validarInventarios.php">
                <div slot="headline">Validar Inventarios</div>
                <md-icon slot="start">fact_check</md-icon>
            </md-list-item>
            <md-list-item href="listaPromotores.php">
                <div slot="headline">Mis Promotores</div>
                <md-icon slot="start">group</md-icon>
            </md-list-item>
        </md-list>
    </aside>

    <main class="panel-content">
        <div class="md3-hero-card">
            <h2 style="font-size: 2.25rem; margin: 0;">Panel de Supervisión</h2>
            <p style="font-size: 1.1rem; opacity: 0.9; margin: 8px 0 20px;">
                Bienvenido, <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong>. 
                Tienes reportes pendientes de validación para el ciclo actual.
            </p>
            <md-filled-button onclick="location.href='validarInventarios.php'">
                <md-icon slot="icon">visibility</md-icon>
                Revisar Pendientes
            </md-filled-button>
        </div>

        <h3 style="margin-bottom: 16px;">Acciones de Control</h3>
        <div class="md3-dashboard-grid">
            <a href="estadisticas.php" class="md3-action-card">
                <div class="action-card-icon" style="background: var(--md-sys-color-secondary-container);">
                    <md-icon>monitoring</md-icon>
                </div>
                <h4 class="action-card-title">Estadísticas</h4>
                <p class="action-card-desc">Análisis de distribución y consumo por zona.</p>
            </a>

            <a href="validarInventarios.php" class="md3-action-card">
                <div class="action-card-icon">
                    <md-icon>assignment_turned_in</md-icon>
                </div>
                <h4 class="action-card-title">Validar Cierres</h4>
                <p class="action-card-desc">Autoriza los inventarios enviados por los promotores.</p>
            </a>
        </div>
    </main>

    <script src="../js/temas_md3.js"></script>
    <script>
        function abrirMenu(id) {
            document.querySelectorAll('md-menu').forEach(menu => {
                if (menu.id !== id) menu.open = false;
            });
            const menu = document.getElementById(id);
            menu.open = !menu.open;
        }

        function toggleDrawer() {
            document.getElementById('mobile-drawer').classList.toggle('open');
        }
    </script>
</body>
</html>