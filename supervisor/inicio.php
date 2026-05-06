<?php
session_start();
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
    <style>
        .md3-hero-card {
            overflow: hidden;
            isolation: isolate;
            position: relative;
            background-color: var(--md-sys-color-surface-container-low, #1e1e1e);
            border-radius: 28px;
            padding: 32px;
            margin-bottom: 24px;
        }
        .md3-hero-card>*:not(canvas) {
            position: relative;
            z-index: 2;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.5);
        }
        .md3-hero-card canvas {
            will-change: transform, opacity;
            z-index: 1;
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
        }

/* Grid específico para Promotores del Supervisor */
        .promotores-grid {
            display: grid;
            /* 280px permite que quepan 3 columnas perfectamente en pantallas normales */
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
            gap: 16px;
            margin-top: 16px;
            align-items: start;
            grid-auto-flow: dense; /* ✨ LA MAGIA QUE RELLENA LOS HUECOS ESTILO TETRIS ✨ */
        }

        .promotor-card {
            background: var(--md-sys-color-surface-container);
            border-radius: 16px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            /* Animación MD3 ultra suave con curva cubic-bezier */
            transition: all 0.5s cubic-bezier(0.2, 0, 0, 1); 
            cursor: pointer;
            border: 1px solid transparent;
            overflow: hidden;
            position: relative;
        }

        .promotor-card:hover {
            background: var(--md-sys-color-surface-container-high);
            transform: translateY(-2px); /* Ligero levante al pasar el cursor */
        }

        /* --- ESTADO EXPANDIDO --- */
        .promotor-card.expanded {
            grid-column: 1 / -1; /* Ocupa toda la fila */
            background: var(--md-sys-color-surface-container-low); /* Contraste perfecto para tema oscuro */
            border-color: var(--md-sys-color-outline-variant);
            border-radius: 28px; /* Bordes más redondeados al abrirse */
            cursor: default;
            transform: translateY(0); /* Quita el efecto hover */
            box-shadow: 0 12px 24px rgba(0,0,0,0.2); /* Sombra de elevación MD3 */
        }

        .card-main-content {
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        /* --- ANIMACIÓN TIPO ACORDEÓN PERFECTA --- */
        .detalles-wrapper {
            display: grid;
            grid-template-rows: 0fr; /* Empieza colapsado */
            transition: grid-template-rows 0.5s cubic-bezier(0.2, 0, 0, 1);
        }
        
        .detalles-inner {
            min-height: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            opacity: 0;
            transform: translateY(-10px); /* Efecto de caída suave */
            transition: opacity 0.4s ease, transform 0.4s ease;
        }

        .promotor-card.expanded .detalles-wrapper {
            grid-template-rows: 1fr; /* Expande suavemente */
        }

        .promotor-card.expanded .detalles-inner {
            opacity: 1;
            transform: translateY(0);
            padding-top: 20px;
        }

        /* --- DISEÑO A 2 COLUMNAS EN PC CUANDO ESTÁ ABIERTO --- */
        @media (min-width: 768px) {
            .promotor-card.expanded .card-main-content {
                flex-direction: row;
                gap: 32px;
                align-items: stretch;
            }
            
            .promotor-card.expanded .promotor-header {
                flex: 0 0 220px;
                border-right: 1px solid var(--md-sys-color-outline-variant);
                padding-right: 24px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
            }

            .promotor-card.expanded .promotor-avatar {
                width: 80px;
                height: 80px;
                font-size: 2.5rem;
                margin-bottom: 16px;
                background-color: var(--md-sys-color-primary);
                color: var(--md-sys-color-on-primary);
            }

            .promotor-card.expanded .detalles-wrapper {
                flex: 1;
            }

            .lista-interna-lecherias {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
                gap: 12px;
            }
        }

/* Clases base del avatar y texto de la tarjeta cerrada */
        .promotor-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background-color: var(--md-sys-color-primary-container);
            color: var(--md-sys-color-on-primary-container);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            transition: all 0.4s ease;
            
            /* LA MAGIA ANTI-APLASTAMIENTO */
            flex-shrink: 0; 
        }

        .promotor-header {
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.4s ease;
        }

        .promotor-info h4 {
            margin: 0;
            font-size: 1.1rem;
            color: var(--md-sys-color-on-surface);
        }

        .promotor-info p {
            margin: 0;
            font-size: 0.9rem;
            color: var(--md-sys-color-on-surface-variant);
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
            <md-icon-button class="mobile-menu-btn" onclick="toggleDrawer()">
                <md-icon>menu</md-icon>
            </md-icon-button>
            <div class="app-brand">
                <span>Liconsa - Supervisión</span>
            </div>
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
                
                <div class="drawer-section-title">Revisión de Inventarios</div>
                <md-list-item href="validarInventarios.php" type="button">
                    <div slot="headline">Validar Pendientes</div>
                    <md-icon slot="start">fact_check</md-icon>
                </md-list-item>
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

        <h3 style="font-size: 1.25rem; font-weight: 500; color: var(--md-sys-color-on-surface); margin-top: 24px; margin-bottom: 16px;">
            Mis Promotores Asignados
        </h3>
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
</body>
</html>