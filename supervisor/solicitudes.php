<?php
require_once __DIR__ . '/../includes/session_guard.php';
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    header('Location: ../iniciosesionSupervisor.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes de Cambio - Supervisor</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../main_md3.css">
    <link rel="stylesheet" href="../estilos/iniciocards.css">
    <style>
        .sol-card {
            background: var(--md-sys-color-surface-container);
            border: 1px solid var(--md-sys-color-outline-variant);
            border-radius: 16px; padding: 16px 18px; margin-bottom: 12px;
            display: flex; flex-direction: column; gap: 10px;
        }
        .sol-card.pendiente { border-left: 4px solid var(--md-sys-color-error); }
        .sol-card.resuelto  { border-left: 4px solid var(--md-sys-color-primary); }
        .sol-card.rechazado { border-left: 4px solid var(--md-sys-color-outline); }
        .sol-meta { font-size: .82rem; color: var(--md-sys-color-on-surface-variant); display: flex; flex-wrap: wrap; gap: 8px 18px; }
        .pill { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:999px; font-size:.8rem; font-weight:600; }
        .pill-pend  { background:color-mix(in srgb,var(--md-sys-color-error) 18%,transparent); color:var(--md-sys-color-error); }
        .pill-ok    { background:color-mix(in srgb,var(--md-sys-color-primary) 20%,transparent); color:var(--md-sys-color-primary); }
        .pill-rech  { background:color-mix(in srgb,var(--md-sys-color-outline) 30%,transparent); color:var(--md-sys-color-on-surface-variant); }
        .pill-reporte      { background:color-mix(in srgb,var(--md-sys-color-tertiary) 22%,transparent); color:var(--md-sys-color-tertiary); }
        .pill-requerimiento{ background:color-mix(in srgb,var(--md-sys-color-secondary) 22%,transparent); color:var(--md-sys-color-secondary); }
        .filtro-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
        .ftab { cursor:pointer; padding:8px 18px; border-radius:20px; border:1px solid var(--md-sys-color-outline-variant);
                background:var(--md-sys-color-surface-container); color:var(--md-sys-color-on-surface); font-size:.88rem; font-weight:500; }
        .ftab.active { background:var(--md-sys-color-primary); color:var(--md-sys-color-on-primary); border-color:transparent; }
    </style>
    <script type="importmap">{ "imports": { "@material/web/": "https://esm.run/@material/web/" } }</script>
    <script type="module"> import '@material/web/all.js'; </script>
</head>
<body>
    <header class="md3-top-app-bar">
        <div class="app-bar-start">
            <md-icon-button class="mobile-menu-btn" onclick="toggleDrawer()"><md-icon>menu</md-icon></md-icon-button>
            <div class="app-brand"><span>Liconsa - Supervisión</span></div>
        </div>
        <div class="app-bar-end">
            <div class="desktop-nav">
                <md-text-button href="inicio.php"><md-icon slot="icon">home</md-icon>Inicio</md-text-button>
                <md-text-button href="lecherias.php"><md-icon slot="icon">storefront</md-icon>Lecherías</md-text-button>
                <md-text-button href="solicitudes.php" style="color:var(--md-sys-color-primary);">
                    <md-icon slot="icon">inbox</md-icon>Solicitudes
                </md-text-button>
            </div>
            <md-filled-tonal-button href="../cerrar_sesionsupervisor.php" style="margin-left:16px;">
                <md-icon slot="icon">logout</md-icon>Salir
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
            <md-list-item href="inicio.php" type="button"><div slot="headline">Inicio</div><md-icon slot="start">home</md-icon></md-list-item>
            <md-list-item href="lecherias.php" type="button"><div slot="headline">Lecherías</div><md-icon slot="start">storefront</md-icon></md-list-item>
            <md-list-item href="solicitudes.php" type="button"><div slot="headline">Solicitudes</div><md-icon slot="start">inbox</md-icon></md-list-item>
        </md-list>
    </aside>

    <main class="panel-content">
        <div class="md3-card" style="display:flex;align-items:center;gap:16px;margin-bottom:16px;">
            <div style="background:var(--md-sys-color-primary-container);border-radius:16px;padding:10px;display:flex;">
                <md-icon style="color:var(--md-sys-color-on-primary-container);font-size:32px;width:32px;height:32px;">inbox</md-icon>
            </div>
            <div>
                <h2 style="margin:0;font-size:1.5rem;font-weight:500;">Solicitudes de cambio</h2>
                <p style="margin:4px 0 0;font-size:.9rem;color:var(--md-sys-color-on-surface-variant);">
                    Promotores que piden modificar su reporte mensual o requerimiento.
                </p>
            </div>
        </div>

        <div class="filtro-tabs" id="filtroTabs">
            <button class="ftab active" data-estado="pendiente">Pendientes</button>
            <button class="ftab" data-estado="en_proceso">En proceso</button>
            <button class="ftab" data-estado="resuelto">Resueltas</button>
            <button class="ftab" data-estado="rechazado">Rechazadas</button>
            <button class="ftab" data-estado="todas">Todas</button>
        </div>

        <div id="listaSolicitudes">
            <div style="text-align:center;padding:40px;color:var(--md-sys-color-on-surface-variant);">
                <span class="material-symbols-outlined" style="font-size:40px;display:block;opacity:.4;">hourglass_empty</span>
                Cargando...
            </div>
        </div>
    </main>

    <!-- Modal resolución -->
    <div id="modalResolucion" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);
         display:none;align-items:center;justify-content:center;z-index:100001;">
        <div style="background:var(--md-sys-color-surface-container-high);color:var(--md-sys-color-on-surface);
                    padding:24px;border-radius:24px;max-width:460px;width:92%;box-shadow:0 8px 24px rgba(0,0,0,.3);">
            <h3 id="modalTitulo" style="margin:0 0 8px;font-weight:500;">Resolver solicitud</h3>
            <p id="modalDesc" style="margin:0 0 12px;font-size:.85rem;color:var(--md-sys-color-on-surface-variant);"></p>
            <p style="margin:0 0 6px;font-size:.85rem;font-weight:500;">Nota para el promotor (opcional):</p>
            <textarea id="txtNota" rows="3" placeholder="Ej: Cambio autorizado, ya puedes re-enviar el reporte."
                style="width:100%;border-radius:10px;padding:10px;font-size:.9rem;resize:none;box-sizing:border-box;
                       background:var(--md-sys-color-surface-container);color:var(--md-sys-color-on-surface);
                       border:1px solid var(--md-sys-color-outline-variant);"></textarea>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px;">
                <md-text-button id="btnCancelarModal">Cancelar</md-text-button>
                <md-outlined-button id="btnRechazarModal" style="color:var(--md-sys-color-error);">Rechazar</md-outlined-button>
                <md-filled-button id="btnResolverModal">Autorizar cambio</md-filled-button>
            </div>
        </div>
    </div>

    <script src="../js/temas_md3.js"></script>
    <script>
    function toggleDrawer() {
        document.getElementById('mobile-drawer')?.classList.toggle('open');
        document.getElementById('drawer-scrim')?.classList.toggle('open');
    }

    const meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                   'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

    let estadoActivo = 'pendiente';
    let solicitudActiva = null;

    function notificar(msg, tipo='info') {
        let cont = document.getElementById('toast-container-md3');
        if (!cont) {
            cont = document.createElement('div');
            cont.id = 'toast-container-md3';
            Object.assign(cont.style, { position:'fixed', top:'24px', left:'50%', transform:'translateX(-50%)',
                display:'flex', flexDirection:'column', gap:'10px', zIndex:'99999', pointerEvents:'none' });
            document.body.appendChild(cont);
        }
        const isErr = tipo === 'error';
        const t = document.createElement('div');
        Object.assign(t.style, { backgroundColor: isErr ? 'var(--md-sys-color-error-container)' : 'var(--md-sys-color-surface-container-highest)',
            color: isErr ? 'var(--md-sys-color-on-error-container)' : 'var(--md-sys-color-on-surface)',
            padding:'12px 20px', borderRadius:'8px', boxShadow:'0px 4px 12px rgba(0,0,0,0.3)',
            display:'flex', alignItems:'center', gap:'12px', minWidth:'300px', maxWidth:'90vw',
            pointerEvents:'auto', opacity:'0', transform:'translateY(-20px)', transition:'all 0.3s cubic-bezier(0.2,0,0,1)' });
        t.innerHTML = `<span class="material-symbols-outlined" style="color:${isErr?'var(--md-sys-color-error)':'var(--md-sys-color-primary)'};font-size:24px;">${isErr?'error':'check_circle'}</span>
            <span style="flex:1;font-size:.9rem;font-weight:500;">${msg}</span>`;
        cont.appendChild(t);
        requestAnimationFrame(() => { t.style.opacity='1'; t.style.transform='translateY(0)'; });
        setTimeout(() => { t.style.opacity='0'; t.style.transform='translateY(-20px)'; setTimeout(()=>t.remove(),300); }, 5000);
    }

    async function cargarSolicitudes(estado) {
        const lista = document.getElementById('listaSolicitudes');
        lista.innerHTML = `<div style="text-align:center;padding:40px;color:var(--md-sys-color-on-surface-variant);">
            <span class="material-symbols-outlined" style="font-size:40px;display:block;opacity:.4;">hourglass_empty</span>Cargando...</div>`;
        try {
            const r = await fetch(`api_solicitudes.php?estado=${estado}`);
            const d = await r.json();
            if (!d.success) throw new Error();
            renderSolicitudes(d.solicitudes || [], lista);
        } catch (_) {
            lista.innerHTML = `<div style="text-align:center;padding:32px;color:var(--md-sys-color-error);">Error al cargar solicitudes.</div>`;
        }
    }

    function renderSolicitudes(soles, lista) {
        if (!soles.length) {
            lista.innerHTML = `<div style="text-align:center;padding:40px;color:var(--md-sys-color-on-surface-variant);">
                <span class="material-symbols-outlined" style="font-size:48px;display:block;opacity:.4;">task_alt</span>
                No hay solicitudes en este estado.</div>`;
            return;
        }
        lista.innerHTML = '';
        soles.forEach(s => {
            const div = document.createElement('div');
            div.className = `sol-card ${s.estado}`;

            const pillEstado = s.estado === 'pendiente' ? `<span class="pill pill-pend">Pendiente</span>` :
                               s.estado === 'resuelto'  ? `<span class="pill pill-ok">Autorizado</span>` :
                               s.estado === 'rechazado' ? `<span class="pill pill-rech">Rechazado</span>` :
                                                          `<span class="pill pill-pend">En proceso</span>`;
            const pillTipo = `<span class="pill pill-${s.tipo}">${s.tipo === 'reporte' ? 'Reporte mensual' : 'Requerimiento'}</span>`;
            const mesLabel = (meses[parseInt(s.mes)] || s.mes) + ' ' + s.anio;

            let botonesHtml = '';
            if (s.estado === 'pendiente' || s.estado === 'en_proceso') {
                botonesHtml = `<div style="display:flex;gap:8px;justify-content:flex-end;margin-top:4px;">
                    <md-outlined-button onclick="abrirModal(${s.id})">
                        <md-icon slot="icon">rate_review</md-icon>Revisar y responder
                    </md-outlined-button>
                </div>`;
            }

            div.innerHTML = `
                <div style="display:flex;align-items:flex-start;gap:10px;flex-wrap:wrap;">
                    <div style="flex:1;min-width:220px;">
                        <div style="font-weight:500;font-size:.95rem;margin-bottom:4px;">
                            Promotor: <strong>${s.promotor_usr}</strong>
                        </div>
                        <div class="sol-meta">
                            <span>${pillTipo}</span>
                            <span>${pillEstado}</span>
                            <span>📅 ${mesLabel}</span>
                            <span>Lechería: ${s.clave_lecheria}</span>
                        </div>
                    </div>
                </div>
                ${s.motivo ? `<div style="font-size:.88rem;padding:8px 12px;background:var(--md-sys-color-surface-container-highest);border-radius:10px;">
                    <strong>Motivo:</strong> ${s.motivo}</div>` : ''}
                ${s.nota_supervisor ? `<div style="font-size:.88rem;color:var(--md-sys-color-on-surface-variant);">
                    <strong>Nota:</strong> ${s.nota_supervisor}</div>` : ''}
                <div style="font-size:.78rem;color:var(--md-sys-color-on-surface-variant);">
                    Solicitado: ${(s.fecha_solicitud||'').slice(0,16)}
                    ${s.fecha_resolucion ? ' · Resuelto: ' + s.fecha_resolucion.slice(0,16) : ''}
                </div>
                ${botonesHtml}`;
            lista.appendChild(div);
        });
    }

    function abrirModal(id) {
        solicitudActiva = id;
        const modal = document.getElementById('modalResolucion');
        document.getElementById('txtNota').value = '';
        modal.style.display = 'flex';
    }

    document.getElementById('btnCancelarModal').addEventListener('click', () => {
        document.getElementById('modalResolucion').style.display = 'none';
    });

    async function responder(accion) {
        const nota = document.getElementById('txtNota').value.trim();
        try {
            const r = await fetch('api_solicitudes.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ accion, id: solicitudActiva, nota })
            });
            const d = await r.json();
            document.getElementById('modalResolucion').style.display = 'none';
            if (d.success) {
                notificar(accion === 'resolver' ? 'Cambio autorizado. El promotor fue notificado.' : 'Solicitud rechazada.', 'info');
                cargarSolicitudes(estadoActivo);
            } else {
                notificar(d.mensaje || 'Error al responder.', 'error');
            }
        } catch (_) { notificar('Error de conexión.', 'error'); }
    }

    document.getElementById('btnResolverModal').addEventListener('click', () => responder('resolver'));
    document.getElementById('btnRechazarModal').addEventListener('click', () => responder('rechazar'));

    // Tabs filtro
    document.getElementById('filtroTabs').addEventListener('click', e => {
        const btn = e.target.closest('.ftab');
        if (!btn) return;
        document.querySelectorAll('.ftab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        estadoActivo = btn.dataset.estado;
        cargarSolicitudes(estadoActivo);
    });

    cargarSolicitudes('pendiente');
    </script>
</body>
</html>
