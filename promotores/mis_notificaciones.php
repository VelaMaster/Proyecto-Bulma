<?php
require_once __DIR__ . '/../includes/session_guard.php';
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    header('Location: ../iniciosesionPromotores.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis notificaciones - Promotor</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../main_md3.css">
    <link rel="stylesheet" href="../estilos/iniciocards.css">
    <style>
        .notif-card {
            background:var(--md-sys-color-surface-container);
            border:1px solid var(--md-sys-color-outline-variant);
            border-radius:14px; padding:14px 16px; margin-bottom:10px;
            display:flex; flex-direction:column; gap:8px;
        }
        .notif-card.nueva { border-left:4px solid var(--md-sys-color-primary); }
        .notif-card.resuelto { border-left:4px solid var(--md-sys-color-primary); }
        .notif-card.rechazado { border-left:4px solid var(--md-sys-color-error); }
        .pill { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:999px; font-size:.8rem; font-weight:600; }
        .pill-ok    { background:color-mix(in srgb,var(--md-sys-color-primary) 20%,transparent); color:var(--md-sys-color-primary); }
        .pill-err   { background:color-mix(in srgb,var(--md-sys-color-error) 18%,transparent); color:var(--md-sys-color-error); }
        .pill-pend  { background:color-mix(in srgb,var(--md-sys-color-tertiary) 22%,transparent); color:var(--md-sys-color-tertiary); }
    </style>
    <script type="importmap">{ "imports": { "@material/web/": "https://esm.run/@material/web/" } }</script>
    <script type="module"> import '@material/web/all.js'; </script>
</head>
<body>
    <header class="md3-top-app-bar">
        <div class="app-bar-start">
            <md-icon-button onclick="history.back()"><md-icon>arrow_back</md-icon></md-icon-button>
            <div class="app-brand"><span>Mis Notificaciones</span></div>
        </div>
        <div class="app-bar-end">
            <md-filled-tonal-button href="../cerrar_sesion.php" style="margin-left:16px;">
                <md-icon slot="icon">logout</md-icon>Salir
            </md-filled-tonal-button>
        </div>
    </header>

    <main class="panel-content">
        <div class="md3-card" style="display:flex;align-items:center;gap:16px;margin-bottom:16px;">
            <div style="background:var(--md-sys-color-primary-container);border-radius:16px;padding:10px;display:flex;">
                <md-icon style="color:var(--md-sys-color-on-primary-container);font-size:32px;width:32px;height:32px;">notifications</md-icon>
            </div>
            <div>
                <h2 style="margin:0;font-size:1.5rem;font-weight:500;">Avisos y solicitudes</h2>
                <p style="margin:4px 0 0;font-size:.9rem;color:var(--md-sys-color-on-surface-variant);">
                    Estado de tus solicitudes de cambio y avisos del supervisor.
                </p>
            </div>
        </div>

        <!-- Ayuda: cómo solicitar un cambio -->
        <details class="md3-card" style="margin-bottom:16px; padding:14px 18px;">
            <summary style="cursor:pointer; font-weight:500; display:flex; align-items:center; gap:10px;">
                <md-icon style="color:var(--md-sys-color-primary);">help_outline</md-icon>
                ¿Cómo solicito un cambio a mi supervisor?
            </summary>
            <ol style="padding-left:20px; margin:10px 0 4px; line-height:1.55; font-size:.92rem;">
                <li>Abre <strong>Reporte mensual</strong> o <strong>Requerimiento</strong> según lo que necesites corregir.</li>
                <li>Selecciona el <strong>mes y año</strong> del registro ya enviado.</li>
                <li>Verás un aviso rojo con el botón <strong>Solicitar cambio</strong>. Pulsa ahí.</li>
                <li>Escribe el motivo y envía. Te avisaremos aquí cuando el supervisor responda.</li>
            </ol>
            <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:8px;">
                <md-filled-tonal-button href="generarreporteMensual.php">
                    <md-icon slot="icon">receipt_long</md-icon> Ir a Reporte
                </md-filled-tonal-button>
                <md-filled-tonal-button href="requerimiento.php">
                    <md-icon slot="icon">inventory</md-icon> Ir a Requerimiento
                </md-filled-tonal-button>
            </div>
        </details>

        <div id="listaSolicitudes">
            <div style="text-align:center;padding:40px;color:var(--md-sys-color-on-surface-variant);">
                <span class="material-symbols-outlined" style="font-size:40px;display:block;opacity:.4;">hourglass_empty</span>
                Cargando...
            </div>
        </div>
    </main>

    <script src="../js/temas_md3.js"></script>
    <script>
    const meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                   'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

    async function cargar() {
        const lista = document.getElementById('listaSolicitudes');
        try {
            const r = await fetch('mis_solicitudes.php');
            const d = await r.json();
            if (!d.success || !d.solicitudes.length) {
                lista.innerHTML = `<div style="text-align:center;padding:40px;color:var(--md-sys-color-on-surface-variant);">
                    <span class="material-symbols-outlined" style="font-size:48px;display:block;opacity:.4;">task_alt</span>
                    Aún no tienes solicitudes de cambio.</div>`;
                return;
            }
            lista.innerHTML = '';
            d.solicitudes.forEach(s => {
                const div = document.createElement('div');
                const mesLabel = (meses[parseInt(s.mes)] ?? s.mes) + ' ' + s.anio;
                const esNueva = (s.estado === 'resuelto' || s.estado === 'rechazado') && !s.visto_promotor;

                const pillEstado = s.estado === 'pendiente'  ? `<span class="pill pill-pend">Pendiente</span>` :
                                   s.estado === 'en_proceso' ? `<span class="pill pill-pend">En proceso</span>` :
                                   s.estado === 'resuelto'   ? `<span class="pill pill-ok">✓ Autorizado</span>` :
                                                               `<span class="pill pill-err">Rechazado</span>`;

                div.className = `notif-card ${s.estado}${esNueva ? ' nueva' : ''}`;
                div.innerHTML = `
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        ${esNueva ? '<span class="material-symbols-outlined" style="color:var(--md-sys-color-primary);font-size:18px;">fiber_new</span>' : ''}
                        <strong>${s.tipo === 'reporte' ? 'Reporte mensual' : 'Requerimiento'}</strong>
                        <span style="font-size:.85rem;color:var(--md-sys-color-on-surface-variant);">${mesLabel}</span>
                        ${pillEstado}
                    </div>
                    ${s.motivo ? `<div style="font-size:.85rem;padding:8px 10px;background:var(--md-sys-color-surface-container-highest);border-radius:8px;">
                        <strong>Tu motivo:</strong> ${s.motivo}</div>` : ''}
                    ${s.nota_supervisor ? `<div style="font-size:.88rem;color:var(--md-sys-color-primary);">
                        <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">supervisor_account</span>
                        <strong> Supervisor:</strong> ${s.nota_supervisor}</div>` : ''}
                    ${s.estado === 'resuelto' ? `<div style="font-size:.85rem;color:var(--md-sys-color-on-surface-variant);">
                        Tu reporte ha sido desbloqueado. Ya puedes volver al formulario para corregirlo y enviarlo de nuevo.</div>` : ''}
                    <div style="font-size:.75rem;color:var(--md-sys-color-on-surface-variant);">
                        Enviada: ${(s.fecha_solicitud||'').slice(0,16)}
                        ${s.fecha_resolucion ? ' · Respondida: ' + s.fecha_resolucion.slice(0,16) : ''}
                    </div>`;
                lista.appendChild(div);
            });

            // Marcar como vistas
            fetch('mis_solicitudes.php', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({ accion: 'marcar_vistas' })
            }).catch(() => {});
        } catch (_) {
            lista.innerHTML = `<div style="text-align:center;padding:32px;color:var(--md-sys-color-error);">Error al cargar.</div>`;
        }
    }

    cargar();
    </script>
</body>
</html>
