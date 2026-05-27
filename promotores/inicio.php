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

    <!-- PWA -->
    <!-- [OFFLINE DESACTIVADO] <link rel="manifest" href="/manifest.json"> -->
    <meta name="theme-color" content="#6750A4">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Inventarios">
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
                <span>Leche para el bienestar - Promotor</span>
            </div>
        </div>

        <div class="app-bar-end">
            <!-- Badge de datos pendientes de sincronizar -->
            <span id="offline-badge"
                  role="button" tabindex="0" aria-label="Ver cambios pendientes"
                  title="Cambios pendientes de sincronizar — toca para ver el detalle"
                  style="
                    display:none;
                    background:var(--md-sys-color-error,#B3261E);
                    color:#fff;
                    border-radius:12px;
                    font-size:0.72rem;
                    font-weight:700;
                    min-width:22px;
                    height:22px;
                    padding:0 7px;
                    align-items:center;
                    justify-content:center;
                    margin-right:6px;
                    cursor:pointer;
                    letter-spacing:0.2px;
                    box-shadow:0 1px 4px rgba(0,0,0,.3);
                    flex-shrink:0;
                  ">0</span>
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
                        <md-menu-item href="escaner.php">
                            <div slot="headline">Subir desde camara</div>
                            <md-icon slot="start">scan</md-icon>
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

                <a href="mis_notificaciones.php" style="position:relative;display:inline-flex;align-items:center;gap:6px;
                    padding:0 12px;height:40px;border-radius:20px;text-decoration:none;
                    color:var(--md-sys-color-on-surface);font-size:.875rem;font-weight:500;">
                    <md-icon>notifications</md-icon>
                    <span id="badgeNotifProm" style="display:none;position:absolute;top:4px;right:4px;
                        background:var(--md-sys-color-error);color:var(--md-sys-color-on-error);
                        font-size:.7rem;font-weight:700;min-width:18px;height:18px;border-radius:999px;
                        align-items:center;justify-content:center;padding:0 4px;"></span>
                </a>
            </div>

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
                <div class="drawer-section-title">Inventario mensual</div>
                <md-list-item href="generarinventarioMensual.php" type="button">
                    <div slot="headline">Generar</div>
                    <md-icon slot="start">add_box</md-icon>
                </md-list-item>
                <md-list-item href="consultarinventarioMensual.php" type="button">
                    <div slot="headline">Consultar</div>
                    <md-icon slot="start">search</md-icon>
                </md-list-item>
                <md-divider style="margin: 8px 0;"></md-divider>
                <div class="drawer-section-title">Reporte mensual</div>
                <md-list-item href="generarreporteMensual.php" type="button">
                    <div slot="headline">Generar</div>
                    <md-icon slot="start">receipt_long</md-icon>
                </md-list-item>
                <md-divider style="margin: 8px 0;"></md-divider>
                <div class="drawer-section-title">Requerimiento</div>
                <md-list-item href="requerimiento.php" type="button">
                    <div slot="headline">Generar</div>
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

        <!-- ── Tarjeta de estado offline (renderizada por JS) ── -->
        <div id="offline-status-card" style="
            display:none;
            background: var(--md-sys-color-surface-container, #1e1e2e);
            border: 1px solid var(--md-sys-color-outline-variant);
            border-radius: 16px;
            padding: 14px 20px;
            margin-top: 12px;
            font-family: Roboto, sans-serif;
            font-size: 0.875rem;
        "></div>

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
                <div class="action-card-icon">
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

        <h3 style="font-size: 1.25rem; font-weight: 500; color: var(--md-sys-color-on-surface); margin-top: 24px; margin-bottom: 16px;">
            Mis lecherias
        </h3>
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

    <!-- Guardar sesión en IndexedDB para acceso offline -->
    <script>
    window.__SESION_PHP__ = {
        usuario:   '<?= htmlspecialchars($_SESSION['usuario'],  ENT_QUOTES) ?>',
        nombre:    '<?= htmlspecialchars($_SESSION['nombre'],   ENT_QUOTES) ?>',
        rol:       '<?= htmlspecialchars($_SESSION['rol'],      ENT_QUOTES) ?>',
        clave_rol: '<?= htmlspecialchars($_SESSION['clave_rol'] ?? '', ENT_QUOTES) ?>',
    };
    </script>
    <script src="../js/temas_md3.js"></script>
    <script src="../js/hero_physics.js"></script>
    <script src="../js/inicio_lecherias.js"></script>
    <!-- [OFFLINE DESACTIVADO] <script src="../js/pwa_offline.js"></script> -->
    <script src="../js/offline_login.js"></script>
    <!-- [OFFLINE DESACTIVADO] <script src="../js/offline_preload.js"></script> -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Guardar sesión en IndexedDB para acceso offline
        if (window.__SESION_PHP__ && window.OfflineLogin) {
            window.OfflineLogin.guardarSesionOffline(window.__SESION_PHP__);
        }

        // ── Tarjeta de estado offline ─────────────────────────────────
        _renderOfflineStatusCard();

        // Badge de notificaciones de solicitudes resueltas
        fetch('mis_solicitudes.php?accion=contar_nuevas')
            .then(r => r.json()).then(d => {
                const badge = document.getElementById('badgeNotifProm');
                if (badge && d.count > 0) {
                    badge.textContent = d.count;
                    badge.style.display = 'flex';
                }
            }).catch(() => {});

        // Precargar datos offline (solo si pasaron >4h desde el último preload)
        if (window.OfflinePreload && navigator.onLine) {
            setTimeout(() => {
                window.OfflinePreload.runIfStale('/promotores');
            }, 2500);
        }
    });

    function _renderOfflineStatusCard() {
        /* [OFFLINE DESACTIVADO] — La app requiere conexión a WiFi. */
        const card = document.getElementById('offline-status-card');
        if (card) card.style.display = 'none';
        return;

        /* eslint-disable no-unreachable */
        let tsRaw = 0, lecherias = 0;
        try {
            tsRaw    = parseInt(localStorage.getItem('offline_preload_ts') || '0');
            lecherias = parseInt(localStorage.getItem('offline_preload_lecherias') || '0');
        } catch {}

        const ahora     = Date.now();
        const diffMs    = ahora - tsRaw;
        const diffH     = diffMs / (1000 * 60 * 60);
        const sincDate  = tsRaw ? new Date(tsRaw).toLocaleString('es-MX', {
            day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
        }) : null;

        let icono, color, titulo, subtitulo;

        if (!navigator.onLine) {
            // ── SIN CONEXIÓN ──
            if (tsRaw && diffH < 48) {
                icono    = 'offline_bolt';
                color    = 'var(--md-sys-color-primary)';
                titulo   = '✓ Listo para trabajar sin conexión';
                subtitulo = `${lecherias} lecherías precargadas · Sync: ${sincDate}`;
            } else {
                icono    = 'cloud_off';
                color    = 'var(--md-sys-color-error)';
                titulo   = 'Sin conexión — datos limitados';
                subtitulo = tsRaw ? `Último sync: ${sincDate}` : 'Nunca se sincronizó. Conecta a internet primero.';
            }
            card.innerHTML = `
                <div style="display:flex;align-items:center;gap:12px;">
                    <span class="material-symbols-outlined" style="color:${color};font-size:28px;flex-shrink:0">${icono}</span>
                    <div>
                        <div style="font-weight:500;color:var(--md-sys-color-on-surface)">${titulo}</div>
                        <div style="font-size:0.8rem;opacity:0.7;margin-top:2px">${subtitulo}</div>
                    </div>
                </div>`;
        } else {
            // ── CON CONEXIÓN ──
            if (!tsRaw) {
                icono    = 'cloud_sync';
                color    = 'var(--md-sys-color-tertiary)';
                titulo   = 'Datos offline no descargados aún';
                subtitulo = 'Sincroniza para poder trabajar sin conexión';
            } else if (diffH > 24) {
                icono    = 'sync_problem';
                color    = 'var(--md-sys-color-error)';
                titulo   = 'Datos desactualizados';
                subtitulo = `Último sync: ${sincDate} — Se recomienda sincronizar`;
            } else {
                icono    = 'cloud_done';
                color    = 'var(--md-sys-color-tertiary)';
                titulo   = '✓ Datos offline actualizados';
                subtitulo = `${lecherias} lecherías · Sync: ${sincDate}`;
            }
            card.innerHTML = `
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <span class="material-symbols-outlined" style="color:${color};font-size:28px;flex-shrink:0">${icono}</span>
                    <div style="flex:1;min-width:160px">
                        <div style="font-weight:500;color:var(--md-sys-color-on-surface)">${titulo}</div>
                        <div style="font-size:0.8rem;opacity:0.7;margin-top:2px">${subtitulo}</div>
                    </div>
                    <md-filled-tonal-button id="btnSincAhora" style="flex-shrink:0">
                        <md-icon slot="icon">sync</md-icon>
                        Sincronizar ahora
                    </md-filled-tonal-button>
                </div>`;

            document.getElementById('btnSincAhora')?.addEventListener('click', async () => {
                const btn = document.getElementById('btnSincAhora');
                if (btn) btn.disabled = true;
                try { localStorage.removeItem('offline_preload_ts'); } catch {}
                await window.OfflinePreload?.run({ base: '/promotores' });
                _renderOfflineStatusCard();
            });
        }

        card.style.display = 'flex';
    }
    </script>
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