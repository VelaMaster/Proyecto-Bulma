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

    <meta name="theme-color" content="#6750A4">
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
                <img src="/imagenes/Logos/Logo_lecheparaelbienestar.png" alt="Logo"
                     style="height: 44px; vertical-align: middle; border-radius: 4px; margin-right: 12px;">
                <span>Supervisión</span>
            </div>
        </div>

        <div class="app-bar-end">
            <div class="desktop-nav">
                <md-text-button href="listadoReportesPromotores.php">
                    <md-icon slot="icon">receipt_long</md-icon>
                    Reporte Mensual
                </md-text-button>

                <md-text-button href="requerimientodedotacion.php">
                    <md-icon slot="icon">fact_check</md-icon>
                    Requerimiento de Dotación
                </md-text-button>

                <md-text-button href="inventario_almacen.php">
                    <md-icon slot="icon">warehouse</md-icon>
                    Inventario de Almacén
                </md-text-button>
            </div>

            <md-text-button href="../cambiar_contrasena.php" style="margin-left: 8px;">
                <md-icon slot="icon">key</md-icon> Contraseña
            </md-text-button>

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
                <md-list-item href="inicio.php" type="button">
                    <div slot="headline">Inicio</div>
                    <md-icon slot="start">home</md-icon>
                </md-list-item>
                <md-divider style="margin: 8px 0;"></md-divider>

                <md-list-item href="listadoReportesPromotores.php" type="button">
                    <div slot="headline">Reporte Mensual</div>
                    <md-icon slot="start">receipt_long</md-icon>
                </md-list-item>

                <md-list-item href="requerimientodedotacion.php" type="button">
                    <div slot="headline">Requerimiento de Dotación</div>
                    <md-icon slot="start">fact_check</md-icon>
                </md-list-item>

                <md-list-item href="inventario_almacen.php" type="button">
                    <div slot="headline">Inventario de Almacén</div>
                    <md-icon slot="start">warehouse</md-icon>
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
                Aquí podrás gestionar a tus promotores asignados y revisar los reportes mensuales de sus lecherías.
            </p>
            <div style="margin-top: 12px;">
                <md-filled-button onclick="location.href='listadoReportesPromotores.php'" style="--md-filled-button-container-shape: 16px; height: 48px;">
                    <md-icon slot="icon">receipt_long</md-icon>
                    Ir al Reporte Mensual
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

            <a href="javascript:void(0)" id="cardAutorizarMes" class="md3-action-card">
                <div class="action-card-icon" style="background-color: var(--md-sys-color-secondary-container); color: var(--md-sys-color-on-secondary-container);">
                    <md-icon>lock_open</md-icon>
                </div>
                <h4 class="action-card-title">Autorizar Mes (Distribución)</h4>
                <p class="action-card-desc" id="estadoAutorizacionTexto">
                    Marca tus lecherías como listas para que Distribución descargue el OPE.
                </p>
                <span id="badgeAutorizado"
                      style="display:none; margin-top:8px; padding:4px 10px; border-radius:999px;
                             background:var(--md-sys-color-primary-container);
                             color:var(--md-sys-color-on-primary-container);
                             font-size:.78rem; font-weight:500;">
                    <md-icon style="font-size:14px; vertical-align:middle;">check_circle</md-icon>
                    <span id="badgeAutorizadoTxt">Autorizado</span>
                </span>
            </a>

            <a href="lecherias.php" class="md3-action-card">
                <div class="action-card-icon" style="background-color: var(--md-sys-color-primary-container); color: var(--md-sys-color-on-primary-container);">
                    <md-icon>storefront</md-icon>
                </div>
                <h4 class="action-card-title">Lecherías</h4>
                <p class="action-card-desc">Consulta el catálogo de lecherías a tu cargo.</p>
            </a>

            <a href="historialGlobal.php" class="md3-action-card">
                <div class="action-card-icon" style="background-color: var(--md-sys-color-tertiary-container); color: var(--md-sys-color-on-tertiary-container);">
                    <md-icon>history</md-icon>
                </div>
                <h4 class="action-card-title">Historial General</h4>
                <p class="action-card-desc">Historial global de requerimientos de tu zona.</p>
            </a>

            <a href="solicitudes.php" class="md3-action-card" style="position:relative;">
                <div class="action-card-icon" style="background-color: var(--md-sys-color-secondary-container); color: var(--md-sys-color-on-secondary-container);">
                    <md-icon>inbox</md-icon>
                </div>
                <h4 class="action-card-title">Solicitudes</h4>
                <p class="action-card-desc">Bandeja de solicitudes pendientes de tus promotores.</p>
                <span id="badgeSolicitudes" style="display:none;position:absolute;top:12px;right:12px;
                    background:var(--md-sys-color-error);color:var(--md-sys-color-on-error);
                    font-size:.7rem;font-weight:700;min-width:20px;height:20px;border-radius:999px;
                    align-items:center;justify-content:center;padding:0 6px;"></span>
            </a>
        </div>

        <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-top:24px; margin-bottom:16px;">
            <h3 style="font-size:1.25rem; font-weight:500; color:var(--md-sys-color-on-surface); margin:0;">
                Mis Promotores Asignados
            </h3>

            <div class="md3-card" style="display:flex; flex-wrap:wrap; align-items:center; gap:14px; padding:14px 18px; margin:0;">
                <span class="material-symbols-outlined" style="color:var(--md-sys-color-primary);">calendar_month</span>
                <span style="font-weight:500;">Avance del mes:</span>
                <md-outlined-select id="avance_mes" style="min-width:170px;">
                    <md-select-option value="1"  <?= date('n')==1  ? 'selected' : '' ?>><div slot="headline">Enero</div></md-select-option>
                    <md-select-option value="2"  <?= date('n')==2  ? 'selected' : '' ?>><div slot="headline">Febrero</div></md-select-option>
                    <md-select-option value="3"  <?= date('n')==3  ? 'selected' : '' ?>><div slot="headline">Marzo</div></md-select-option>
                    <md-select-option value="4"  <?= date('n')==4  ? 'selected' : '' ?>><div slot="headline">Abril</div></md-select-option>
                    <md-select-option value="5"  <?= date('n')==5  ? 'selected' : '' ?>><div slot="headline">Mayo</div></md-select-option>
                    <md-select-option value="6"  <?= date('n')==6  ? 'selected' : '' ?>><div slot="headline">Junio</div></md-select-option>
                    <md-select-option value="7"  <?= date('n')==7  ? 'selected' : '' ?>><div slot="headline">Julio</div></md-select-option>
                    <md-select-option value="8"  <?= date('n')==8  ? 'selected' : '' ?>><div slot="headline">Agosto</div></md-select-option>
                    <md-select-option value="9"  <?= date('n')==9  ? 'selected' : '' ?>><div slot="headline">Septiembre</div></md-select-option>
                    <md-select-option value="10" <?= date('n')==10 ? 'selected' : '' ?>><div slot="headline">Octubre</div></md-select-option>
                    <md-select-option value="11" <?= date('n')==11 ? 'selected' : '' ?>><div slot="headline">Noviembre</div></md-select-option>
                    <md-select-option value="12" <?= date('n')==12 ? 'selected' : '' ?>><div slot="headline">Diciembre</div></md-select-option>
                </md-outlined-select>
                <md-outlined-text-field id="avance_anio" type="number" value="<?= date('Y') ?>"
                                        min="2020" max="2099" style="max-width:110px;">
                </md-outlined-text-field>
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

    <!-- Diálogo MD3 reutilizable (sustituye confirm/alert nativos) -->
    <md-dialog id="md3Modal">
      <div slot="headline" id="md3ModalTitle">Mensaje</div>
      <form slot="content" id="md3ModalForm" method="dialog">
        <p id="md3ModalBody" style="margin:0; line-height:1.5;"></p>
      </form>
      <div slot="actions">
        <md-text-button form="md3ModalForm" value="cancel" id="md3ModalCancel">Cancelar</md-text-button>
        <md-filled-button form="md3ModalForm" value="ok" id="md3ModalOk">Aceptar</md-filled-button>
      </div>
    </md-dialog>

    <script>
    // Helpers MD3 para sustituir confirm/alert nativos
    window.md3Confirm = (title, body, okText='Confirmar', cancelText='Cancelar') => new Promise(res => {
        const d  = document.getElementById('md3Modal');
        const ok = document.getElementById('md3ModalOk');
        const cn = document.getElementById('md3ModalCancel');
        document.getElementById('md3ModalTitle').textContent = title;
        document.getElementById('md3ModalBody').textContent  = body;
        ok.textContent = okText; cn.textContent = cancelText;
        cn.style.display = '';
        const handler = () => { d.removeEventListener('close', handler); res(d.returnValue === 'ok'); };
        d.addEventListener('close', handler);
        d.show();
    });
    window.md3Alert = (title, body, okText='Aceptar') => new Promise(res => {
        const d  = document.getElementById('md3Modal');
        const ok = document.getElementById('md3ModalOk');
        const cn = document.getElementById('md3ModalCancel');
        document.getElementById('md3ModalTitle').textContent = title;
        document.getElementById('md3ModalBody').textContent  = body;
        ok.textContent = okText; cn.style.display = 'none';
        const handler = () => { d.removeEventListener('close', handler); cn.style.display=''; res(); };
        d.addEventListener('close', handler);
        d.show();
    });
    </script>

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
    <script>
    document.addEventListener('DOMContentLoaded', () => {
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
    <script>
    // ── Autorización de mes para Distribución ──────────────────────
    (function() {
        const card    = document.getElementById('cardAutorizarMes');
        const txt     = document.getElementById('estadoAutorizacionTexto');
        const badge   = document.getElementById('badgeAutorizado');
        const badgeT  = document.getElementById('badgeAutorizadoTxt');
        const selMes  = document.getElementById('avance_mes');
        const inpAnio = document.getElementById('avance_anio');
        if (!card || !selMes || !inpAnio) return;

        const NOMBRES_MES = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                             'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

        function getMesAnio() {
            return { mes: parseInt(selMes.value, 10), anio: parseInt(inpAnio.value, 10) };
        }

        async function refrescarEstado() {
            const { mes, anio } = getMesAnio();
            try {
                const r = await fetch(`autorizar_mes.php?mes=${mes}&anio=${anio}`);
                const d = await r.json();
                if (d.status === 'ok' && d.autorizado) {
                    badge.style.display = 'inline-block';
                    badgeT.textContent = `Autorizado · ${d.total_lecherias} lecherías · ${d.fecha}`;
                    txt.textContent = `Mes ${NOMBRES_MES[mes]} ${anio} ya está cerrado. Click para re-autorizar.`;
                } else {
                    badge.style.display = 'none';
                    txt.textContent = 'Marca tus lecherías como listas para que Distribución descargue el OPE.';
                }
            } catch (e) { /* silencioso */ }
        }

        card.addEventListener('click', async () => {
            const { mes, anio } = getMesAnio();
            if (!mes || !anio) { await md3Alert('Datos faltantes', 'Selecciona mes y año primero.'); return; }
            const ok = await md3Confirm(
                `Autorizar ${NOMBRES_MES[mes]} ${anio}`,
                `Distribución podrá descargar el OPE con las lecherías capturadas. ¿Continuar?`,
                'Autorizar', 'Cancelar'
            );
            if (!ok) return;

            const fd = new FormData();
            fd.append('mes',  mes);
            fd.append('anio', anio);
            try {
                const r = await fetch('autorizar_mes.php', { method: 'POST', body: fd });
                const d = await r.json();
                if (d.status === 'ok') {
                    refrescarEstado();
                    await md3Alert(
                        `${NOMBRES_MES[mes]} ${anio} autorizado`,
                        `${d.total_lecherias} ${d.total_lecherias === 1 ? 'lechería incluida' : 'lecherías incluidas'}.`
                    );
                } else {
                    await md3Alert('Error', d.message || 'No se pudo autorizar');
                }
            } catch (e) {
                await md3Alert('Error de red', e.message);
            }
        });

        selMes.addEventListener('change', refrescarEstado);
        inpAnio.addEventListener('change', refrescarEstado);
        refrescarEstado();
    })();
    </script>
</body>
</html>