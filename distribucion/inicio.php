<?php
session_start();
// Validación estricta para el rol de Distribución
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'distribucion') {
    header("Location: ../iniciosesionDistribucion.php");
    exit();
}
$nombre_usuario = $_SESSION['nombre'] ?? $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Logística - Distribución</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../main_md3.css">
    <link rel="stylesheet" href="../estilos/iniciocards.css">
    <style>
        /* Un toque visual distinto para Logística/Distribución */
        .md3-hero-card {
            background-color: var(--md-sys-color-tertiary-container, #3a2e4b);
            color: var(--md-sys-color-on-tertiary-container);
            border-radius: 28px;
            padding: 32px;
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
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
            <div class="app-brand"><span>Leche para el bienestar - Distribución</span></div>
        </div>

        <div class="app-bar-end">
            <div class="desktop-nav">
                <div style="position: relative;">
                    <md-text-button id="btn-log" onclick="abrirMenu('menu-log')">
                        Logística y Despacho
                        <md-icon slot="icon">arrow_drop_down</md-icon>
                    </md-text-button>
                    <md-menu id="menu-log" anchor="btn-log">
                        <md-menu-item href="#">
                            <div slot="headline">Control de Despachos</div>
                            <md-icon slot="start">local_shipping</md-icon>
                        </md-menu-item>
                        <md-menu-item href="#">
                            <div slot="headline">Rutas Asignadas</div>
                            <md-icon slot="start">route</md-icon>
                        </md-menu-item>
                    </md-menu>
                </div>

                <div style="position: relative;">
                    <md-text-button id="btn-rep" onclick="abrirMenu('menu-rep')">
                        Reportes
                        <md-icon slot="icon">arrow_drop_down</md-icon>
                    </md-text-button>
                    <md-menu id="menu-rep" anchor="btn-rep">
                        <md-menu-item href="#">
                            <div slot="headline">Bitácora de Entregas</div>
                            <md-icon slot="start">list_alt</md-icon>
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
            <span style="font-size: 1.25rem; font-weight: 500;">Menú Distribución</span>
            <md-icon-button onclick="toggleDrawer()"><md-icon>close</md-icon></md-icon-button>
        </div>
        <md-list>
            <div class="drawer-section-title">Logística</div>
            <md-list-item href="#">
                <div slot="headline">Control de Despachos</div>
                <md-icon slot="start">local_shipping</md-icon>
            </md-list-item>
            <md-list-item href="#">
                <div slot="headline">Rutas Asignadas</div>
                <md-icon slot="start">route</md-icon>
            </md-list-item>
            <md-divider></md-divider>
            <div class="drawer-section-title">Consultas</div>
            <md-list-item href="#">
                <div slot="headline">Bitácora de Entregas</div>
                <md-icon slot="start">list_alt</md-icon>
            </md-list-item>
        </md-list>
    </aside>

    <main class="panel-content">
        <div class="md3-hero-card">
            <h2 style="font-size: 2.25rem; margin: 0;">Centro de Distribución</h2>
            <p style="font-size: 1.1rem; opacity: 0.9; margin: 8px 0 20px; max-width: 600px;">
                Bienvenido, <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong>. 
                Desde aquí puedes coordinar el despacho de inventarios, asignar camiones y revisar el estatus de las rutas hacia las lecherías.
            </p>
            <md-filled-button onclick="location.href='#'" style="--md-filled-button-container-shape: 16px;">
                <md-icon slot="icon">local_shipping</md-icon>
                Registrar Despacho
            </md-filled-button>
        </div>

        <h3 style="margin-bottom: 16px;">Herramientas Operativas</h3>
        <div class="md3-dashboard-grid">
            <a href="#" class="md3-action-card">
                <div class="action-card-icon" style="background: var(--md-sys-color-tertiary); color: var(--md-sys-color-on-tertiary);">
                    <md-icon>map</md-icon>
                </div>
                <h4 class="action-card-title">Mapa de Rutas</h4>
                <p class="action-card-desc">Visualiza y optimiza los recorridos de entrega del día.</p>
            </a>

            <a href="#" class="md3-action-card">
                <div class="action-card-icon">
                    <md-icon>inventory_2</md-icon>
                </div>
                <h4 class="action-card-title">Carga de Camiones</h4>
                <p class="action-card-desc">Verifica la cantidad de dotación asignada por unidad.</p>
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
        
        document.addEventListener('click', (event) => {
            if (!event.target.closest('md-menu') && !event.target.closest('md-text-button')) {
                document.querySelectorAll('md-menu').forEach(menu => menu.open = false);
            }
        });
    </script>
</body>
</html>