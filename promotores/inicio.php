<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
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
    <title>Inicio - Promotor</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../main_md3.css">

    <style>
        .md3-hero-card {
            overflow: hidden;
            isolation: isolate;
            position: relative;
            background-color: var(--md-sys-color-surface-container-low, #1e1e1e);
        }

        .md3-hero-card>*:not(canvas) {
            position: relative;
            z-index: 2;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.5);
        }

        .md3-hero-card canvas {
            will-change: transform, opacity;
            z-index: 1;
        }
    </style>
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
                <span>Leche del bienestar</span>
            </div>
        </div>

        <div class="app-bar-end">
            <div class="desktop-nav">

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
                        <md-menu-item href="#">
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
                        <md-menu-item href="#">
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

    <aside class="md3-drawer" id="mobile-drawer">

        <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 16px 8px 24px;">
            <span style="font-size: 1.25rem; font-weight: 500; color: var(--md-sys-color-on-surface);">Menú</span>
            <md-icon-button onclick="toggleDrawer()">
                <md-icon>close</md-icon>
            </md-icon-button>
        </div>
        <div style="overflow-y: auto; flex-grow: 1;">
            <md-list style="background: transparent;">
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

    <main class="panel-content">

        <div class="md3-hero-card">
            <h2 style="font-size: 2.25rem; font-weight: 500; margin: 0; letter-spacing: -0.5px;">¡Hola,
                <?php echo htmlspecialchars($nombre_usuario); ?>!
            </h2>
            <p style="font-size: 1.1rem; margin: 0; max-width: 600px; line-height: 1.5; opacity: 0.9;">
                Bienvenido al panel principal.
                Aquí tienes acceso rápido a todas las herramientas proporcionadas por nuestra aplicación web Bulma, para
                facilitar la operación de tus lecherías asignadas, buena suerte.
            </p>
            <div style="margin-top: 12px;">
                <md-filled-button onclick="location.href='generarinventarioMensual.php'"
                    style="--md-filled-button-container-shape: 16px; height: 48px;">
                    <md-icon slot="icon">add_box</md-icon>
                    Nuevo Inventario
                </md-filled-button>
            </div>
        </div>

        <h3
            style="font-size: 1.25rem; font-weight: 500; color: var(--md-sys-color-on-surface); margin-top: 16px; margin-bottom: 0;">
            Accesos Rápidos</h3>
        <div class="md3-dashboard-grid">
            <a href="editarinventarioMensual.php" class="md3-action-card">
                <div class="action-card-icon"
                    style="background-color: var(--md-sys-color-tertiary-container); color: var(--md-sys-color-on-tertiary-container);">
                    <md-icon>edit</md-icon>
                </div>
                <h4 class="action-card-title">Editar reporte</h4>
                <p class="action-card-desc">Edite diferentes campos del reporte o agregue su factura a su inventario mensual.</p>
                </a>

                <a href="consultarinventarioMensual.php" class="md3-action-card">
                <div class="action-card-icon">
                    <md-icon>search</md-icon>
                </div>
                <h4 class="action-card-title">Consultar Inventarios</h4>
                <p class="action-card-desc">Revisa y descarga los inventarios mensuales generados previamente.</p>
            </a>
        </div>
    </main>
    <script src="../js/temas_md3.js"></script>
    <script src="../js/hero_physics.js"></script>
    <script>
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
            drawer.classList.toggle('open');
            scrim.classList.toggle('open');
        }
        document.addEventListener('click', (event) => {
            if (!event.target.closest('md-menu') && !event.target.closest('md-text-button')) {
                document.querySelectorAll('md-menu').forEach(menu => menu.open = false);
            }
        });
    </script>
</body>

</html>