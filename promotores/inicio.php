<?php
require_once __DIR__ . '/../includes/session_guard.php';
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://esm.run">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../main_md3.css">
    <link rel="stylesheet" href="../estilos/iniciocards.css">

    <meta name="theme-color" content="#6750A4">
    <link rel="apple-touch-icon" href="/imagenes/Logos/icon-192.png">

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
                <img src="/imagenes/Logos/Logo_lecheparaelbienestar.png" alt="Logo"
                     style="height: 44px; vertical-align: middle; border-radius: 4px; margin-right: 12px;">
                <span>Promotor</span>
            </div>
        </div>

        <div class="app-bar-end">
            <div class="desktop-nav">
                <md-text-button href="generarinventarioMensual.php">
                    <md-icon slot="icon">add_box</md-icon>
                    Inventario mensual
                </md-text-button>

                <md-text-button href="generarreporteMensual.php">
                    <md-icon slot="icon">receipt_long</md-icon>
                    Reporte mensual
                </md-text-button>

                <md-text-button href="requerimiento.php">
                    <md-icon slot="icon">inventory</md-icon>
                    Requerimiento
                </md-text-button>
            </div>

            <md-text-button href="../cambiar_contrasena.php" style="margin-left: 8px;">
                <md-icon slot="icon">key</md-icon>
                Contraseña
            </md-text-button>

            <md-filled-tonal-button href="../cerrar_sesion.php" style="margin-left: 16px;">
                <md-icon slot="icon">logout</md-icon>
                Salir
            </md-filled-tonal-button>

        </div>
    </header>

    <div class="md3-drawer-scrim" id="drawer-scrim" onclick="toggleDrawer()"></div>
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
                <md-list-item href="generarinventarioMensual.php" type="button">
                    <div slot="headline">Inventario mensual</div>
                    <md-icon slot="start">add_box</md-icon>
                </md-list-item>
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

        <h3 style="font-size:1.25rem;font-weight:500;color:var(--md-sys-color-on-surface);margin-top:16px;margin-bottom:4px;">
            Accesos Rápidos</h3>

        <!-- Inventario Mensual -->
        <p style="font-size:0.8rem;font-weight:500;color:var(--md-sys-color-primary);text-transform:uppercase;letter-spacing:.08em;margin:8px 0 4px;">
            Inventario Mensual</p>
        <div class="md3-dashboard-grid">
            <a href="generarinventarioMensual.php" class="md3-action-card">
                <div class="action-card-icon" style="background-color:var(--md-sys-color-primary-container);color:var(--md-sys-color-on-primary-container);">
                    <md-icon>add_box</md-icon>
                </div>
                <h4 class="action-card-title">Inventario Mensual</h4>
                <p class="action-card-desc">Generar</p>
            </a>
            <a href="consultarinventarioMensual.php" class="md3-action-card">
                <div class="action-card-icon" style="background-color:var(--md-sys-color-tertiary-container);color:var(--md-sys-color-on-tertiary-container);">
                    <md-icon>search</md-icon>
                </div>
                <h4 class="action-card-title">Inventario Mensual</h4>
                <p class="action-card-desc">Consultar inventarios guardados previamente.</p>
            </a>
        </div>

        <!-- Reporte Mensual -->
        <p style="font-size:0.8rem;font-weight:500;color:var(--md-sys-color-primary);text-transform:uppercase;letter-spacing:.08em;margin:16px 0 4px;">
            Reporte Mensual</p>
        <div class="md3-dashboard-grid">
            <a href="generarreporteMensual.php" class="md3-action-card">
                <div class="action-card-icon" style="background-color:var(--md-sys-color-secondary-container);color:var(--md-sys-color-on-secondary-container);">
                    <md-icon>receipt_long</md-icon>
                </div>
                <h4 class="action-card-title">Reporte Mensual</h4>
                <p class="action-card-desc">Generar</p>
            </a>
        </div>

        <!-- Requerimiento -->
        <p style="font-size:0.8rem;font-weight:500;color:var(--md-sys-color-primary);text-transform:uppercase;letter-spacing:.08em;margin:16px 0 4px;">
            Requerimiento</p>
        <div class="md3-dashboard-grid">
            <a href="requerimiento.php" class="md3-action-card">
                <div class="action-card-icon" style="background-color:var(--md-sys-color-tertiary-container);color:var(--md-sys-color-on-tertiary-container);">
                    <md-icon>inventory</md-icon>
                </div>
                <h4 class="action-card-title">Requerimiento</h4>
                <p class="action-card-desc">Generar</p>
            </a>
        </div>

        <!-- Avisos y Solicitudes -->
        <p style="font-size:0.8rem;font-weight:500;color:var(--md-sys-color-primary);text-transform:uppercase;letter-spacing:.08em;margin:16px 0 4px;">
            Avisos y Solicitudes</p>
        <div class="md3-dashboard-grid">
            <a href="mis_notificaciones.php" class="md3-action-card" style="position:relative;">
                <div class="action-card-icon" style="background-color:var(--md-sys-color-secondary-container);color:var(--md-sys-color-on-secondary-container);">
                    <md-icon>notifications</md-icon>
                </div>
                <h4 class="action-card-title">Mis avisos</h4>
                <p class="action-card-desc">Respuestas y comunicados de tu supervisor.</p>
                <span id="badgeNotifProm" style="display:none;position:absolute;top:12px;right:12px;
                    background:var(--md-sys-color-error);color:var(--md-sys-color-on-error);
                    font-size:.7rem;font-weight:700;min-width:20px;height:20px;border-radius:999px;
                    align-items:center;justify-content:center;padding:0 6px;"></span>
            </a>
            <a href="javascript:void(0)" onclick="mostrarComoSolicitar()" class="md3-action-card">
                <div class="action-card-icon" style="background-color:var(--md-sys-color-tertiary-container);color:var(--md-sys-color-on-tertiary-container);">
                    <md-icon>edit_note</md-icon>
                </div>
                <h4 class="action-card-title">Solicitar un cambio</h4>
                <p class="action-card-desc">¿Necesitas corregir un reporte o requerimiento ya enviado?</p>
            </a>
        </div>

        <h3 style="font-size: 1.25rem; font-weight: 500; color: var(--md-sys-color-on-surface); margin-top: 24px; margin-bottom: 8px;">
            Mis lecherias
        </h3>

        <!-- Filtro de periodo -->
        <div class="lech-filtros" style="display:flex;flex-wrap:wrap;gap:12px;align-items:end;margin-bottom:16px;">
            <md-outlined-select id="filtroMes" label="Mes" style="min-width:160px;">
                <md-select-option value="" selected><div slot="headline">Todos</div></md-select-option>
                <md-select-option value="1"><div slot="headline">Enero</div></md-select-option>
                <md-select-option value="2"><div slot="headline">Febrero</div></md-select-option>
                <md-select-option value="3"><div slot="headline">Marzo</div></md-select-option>
                <md-select-option value="4"><div slot="headline">Abril</div></md-select-option>
                <md-select-option value="5"><div slot="headline">Mayo</div></md-select-option>
                <md-select-option value="6"><div slot="headline">Junio</div></md-select-option>
                <md-select-option value="7"><div slot="headline">Julio</div></md-select-option>
                <md-select-option value="8"><div slot="headline">Agosto</div></md-select-option>
                <md-select-option value="9"><div slot="headline">Septiembre</div></md-select-option>
                <md-select-option value="10"><div slot="headline">Octubre</div></md-select-option>
                <md-select-option value="11"><div slot="headline">Noviembre</div></md-select-option>
                <md-select-option value="12"><div slot="headline">Diciembre</div></md-select-option>
            </md-outlined-select>
            <md-outlined-select id="filtroAnio" label="Año" style="min-width:140px;">
                <?php
                $anioActual = (int)date('Y');
                for ($a = $anioActual; $a >= $anioActual - 4; $a--) {
                    $sel = ($a === $anioActual) ? 'selected' : '';
                    echo "<md-select-option value=\"$a\" $sel><div slot=\"headline\">$a</div></md-select-option>";
                }
                ?>
            </md-outlined-select>
            <md-text-button id="btnLimpiarFiltro">
                <md-icon slot="icon">clear_all</md-icon>
                Limpiar
            </md-text-button>
            <div id="filtroResumen" style="font-size:.85rem;opacity:.75;margin-left:auto;align-self:center;"></div>
        </div>

        <div class="lecherias-grid" id="lecherasGrid">
            <div class="lech-card is-skeleton">
                <div class="lech-card-top"></div>
                <div class="lech-card-body">
                    <div class="sk-line skeleton" style="width:60%"></div>
                    <div class="sk-line skeleton" style="width:80%"></div>
                    <div class="sk-line skeleton" style="width:40%"></div>
                </div>
            </div>
            <div class="lech-card is-skeleton">
                <div class="lech-card-top"></div>
                <div class="lech-card-body">
                    <div class="sk-line skeleton" style="width:70%"></div>
                    <div class="sk-line skeleton" style="width:50%"></div>
                </div>
            </div>
        </div>
    </main>

<!-- Diálogo: cómo solicitar un cambio -->
<div class="md3-dialog-backdrop" id="modalComoSolicitar">
    <div class="md3-dialog-surface">
        <div class="md3-dialog-header">
            <div>
                <h3 class="md3-dialog-title">¿Cómo solicito un cambio?</h3>
                <p class="md3-dialog-subtitle">Para corregir un reporte o requerimiento ya enviado.</p>
            </div>
            <md-icon-button onclick="document.getElementById('modalComoSolicitar').classList.remove('open')">
                <md-icon>close</md-icon>
            </md-icon-button>
        </div>
        <div class="md3-dialog-content" style="padding: 0 24px 8px; font-size:.92rem; line-height:1.55;">
            <ol style="padding-left:20px; margin:8px 0;">
                <li>Entra a <strong>Reporte mensual</strong> o <strong>Requerimiento</strong> según lo que quieras modificar.</li>
                <li>Selecciona el <strong>mes y año</strong> del registro ya enviado.</li>
                <li>Aparecerá un aviso rojo: <em>"Este registro ya fue enviado y está bloqueado"</em> con un botón <strong>Solicitar cambio</strong>.</li>
                <li>Escribe el motivo y envía la solicitud. Tu supervisor te responderá y podrás verlo en <strong>Mis avisos</strong>.</li>
            </ol>
        </div>
        <div class="md3-dialog-actions">
            <md-filled-button onclick="document.getElementById('modalComoSolicitar').classList.remove('open');location.href='generarreporteMensual.php'">
                <md-icon slot="icon">receipt_long</md-icon> Ir a Reporte
            </md-filled-button>
            <md-outlined-button onclick="document.getElementById('modalComoSolicitar').classList.remove('open');location.href='requerimiento.php'">
                <md-icon slot="icon">inventory</md-icon> Ir a Requerimiento
            </md-outlined-button>
        </div>
    </div>
</div>

<div class="md3-dialog-backdrop" id="modalOpcionesLecheria">
        <div class="md3-dialog-surface">
            <div class="md3-dialog-header">
                <div>
                    <h3 class="md3-dialog-title" id="modalOpcionesTitulo">Lechería</h3>
                    <p class="md3-dialog-subtitle">¿Qué deseas hacer?</p>
                </div>
                <md-icon-button id="btnCerrarModalOpciones">
                    <md-icon>close</md-icon>
                </md-icon-button>
            </div>
            
            <div class="md3-dialog-actions">
                <md-filled-button id="btnIrGenerar">
                    <md-icon slot="icon">add_box</md-icon> Generar/Editar inventario
                </md-filled-button>                
                <md-outlined-button id="btnIrConsultar">
                    <md-icon slot="icon">search</md-icon> Consultar Inventario
                </md-outlined-button>
            </div>
        </div>
    </div>

    <script src="../js/temas_md3.js"></script>
    <script src="../js/hero_physics.js"></script>
    <script src="../js/inicio_lecherias.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Badge de notificaciones de solicitudes resueltas
        fetch('mis_solicitudes.php?accion=contar_nuevas')
            .then(r => r.json()).then(d => {
                const badge = document.getElementById('badgeNotifProm');
                if (badge && d.count > 0) {
                    badge.textContent = d.count;
                    badge.style.display = 'flex';
                }
            }).catch(() => {});
    });
    </script>
    <script>
        function mostrarComoSolicitar() {
            document.getElementById('modalComoSolicitar').classList.add('open');
        }
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