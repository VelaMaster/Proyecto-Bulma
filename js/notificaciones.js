// ============================================================
//  NOTIFICACIONES MD3 — REDISEÑO COMPLETO
//  Reemplaza las funciones mostrarNotificacion() y
//  mostrarConfirmacionMD3() en generarInventarioMensual.js
// ============================================================

// --- ESTILOS GLOBALES (inyectar una sola vez en <head>) ---
(function inyectarEstilos() {
    if (document.getElementById('md3-toast-styles')) return;
    const style = document.createElement('style');
    style.id = 'md3-toast-styles';
    style.textContent = `
        /* ── CONTENEDOR TOAST ── */
        #md3-toast-container {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            z-index: 99999;
            pointer-events: none;
            width: max-content;
            max-width: min(520px, 92vw);
        }

        /* ── TOAST BASE ── */
        .md3-toast {
            pointer-events: auto;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 14px;
            min-width: 300px;
            max-width: min(520px, 92vw);
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
            font-size: 0.875rem;
            line-height: 1.45;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);

            /* Entrada: empieza arriba y pequeño */
            opacity: 0;
            transform: translateY(-16px) scale(0.95);
            transition:
                opacity 0.28s cubic-bezier(0.2, 0, 0, 1),
                transform 0.28s cubic-bezier(0.2, 0, 0, 1);
        }

        .md3-toast.toast-visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .md3-toast.toast-saliendo {
            opacity: 0;
            transform: translateY(-12px) scale(0.95);
        }

        /* ── VARIANTE INFO ── */
        .md3-toast.toast-info {
            background: color-mix(in srgb, var(--md-sys-color-primary-container) 92%, transparent);
            border: 1px solid color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent);
            box-shadow:
                0 4px 16px color-mix(in srgb, var(--md-sys-color-primary) 18%, transparent),
                0 1px 4px rgba(0,0,0,0.08);
        }

        /* ── VARIANTE ERROR ── */
        .md3-toast.toast-error {
            background: color-mix(in srgb, var(--md-sys-color-error-container) 92%, transparent);
            border: 1px solid color-mix(in srgb, var(--md-sys-color-error) 35%, transparent);
            box-shadow:
                0 4px 16px color-mix(in srgb, var(--md-sys-color-error) 18%, transparent),
                0 1px 4px rgba(0,0,0,0.08);
        }

        /* ── ICONO ── */
        .toast-icono {
            flex-shrink: 0;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .toast-info .toast-icono {
            background: color-mix(in srgb, var(--md-sys-color-primary) 15%, transparent);
            color: var(--md-sys-color-primary);
        }

        .toast-error .toast-icono {
            background: color-mix(in srgb, var(--md-sys-color-error) 15%, transparent);
            color: var(--md-sys-color-error);
        }

        /* ── TEXTO ── */
        .toast-texto {
            flex: 1;
            font-weight: 500;
            padding-top: 7px;
        }

        .toast-info .toast-texto  { color: var(--md-sys-color-on-primary-container); }
        .toast-error .toast-texto { color: var(--md-sys-color-on-error-container); }

        /* ── BOTÓN CERRAR ── */
        .toast-cerrar {
            flex-shrink: 0;
            cursor: pointer;
            font-size: 18px;
            padding: 4px;
            border-radius: 50%;
            line-height: 1;
            transition: background 0.18s ease;
            margin-top: 4px;
        }

        .toast-info  .toast-cerrar { color: var(--md-sys-color-primary); }
        .toast-error .toast-cerrar { color: var(--md-sys-color-error);   }

        .toast-cerrar:hover {
            background: rgba(0,0,0,0.08);
        }

        /* ── BARRA DE PROGRESO ── */
        .toast-barra {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            border-radius: 0 0 14px 14px;
            width: 100%;
            transform-origin: left;
            animation: toastBarra 5s linear forwards;
        }

        .toast-info  .toast-barra { background: var(--md-sys-color-primary); }
        .toast-error .toast-barra { background: var(--md-sys-color-error);   }

        @keyframes toastBarra {
            from { transform: scaleX(1); }
            to   { transform: scaleX(0); }
        }

        .md3-toast { position: relative; overflow: hidden; }

        /* ── DARK MODE: ajuste de opacidad del fondo ── */
        html[data-theme="dark"] .md3-toast.toast-info {
            background: color-mix(in srgb, var(--md-sys-color-primary-container) 80%, #0008);
        }
        html[data-theme="dark"] .md3-toast.toast-error {
            background: color-mix(in srgb, var(--md-sys-color-error-container) 80%, #0008);
        }

        /* ── MODAL CONFIRMACIÓN ── */
        .md3-confirm-scrim {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.48);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 100000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
            opacity: 0;
            transition: opacity 0.22s ease;
        }

        .md3-confirm-scrim.visible { opacity: 1; }

        .md3-confirm-dialog {
            background: var(--md-sys-color-surface-container);
            color: var(--md-sys-color-on-surface);
            border: 1px solid var(--md-sys-color-outline-variant);
            border-radius: 20px;
            padding: 28px;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 12px 40px rgba(0,0,0,0.22);
            font-family: 'Roboto', sans-serif;

            transform: scale(0.93) translateY(10px);
            opacity: 0;
            transition:
                transform 0.26s cubic-bezier(0.2, 0, 0, 1),
                opacity   0.26s cubic-bezier(0.2, 0, 0, 1);
        }

        .md3-confirm-scrim.visible .md3-confirm-dialog {
            transform: scale(1) translateY(0);
            opacity: 1;
        }

        .md3-confirm-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 14px;
        }

        .md3-confirm-icono-wrap {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: color-mix(in srgb, var(--md-sys-color-error) 14%, transparent);
        }

        .md3-confirm-icono-wrap .material-symbols-outlined {
            color: var(--md-sys-color-error);
            font-size: 24px;
        }

        .md3-confirm-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--md-sys-color-on-surface);
            margin: 0;
        }

        .md3-confirm-body {
            font-size: 0.9rem;
            color: var(--md-sys-color-on-surface-variant);
            line-height: 1.6;
            margin-bottom: 24px;
            padding-left: 58px; /* alinea con el texto del header */
        }

        .md3-confirm-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .md3-confirm-btn {
            border: none;
            padding: 10px 20px;
            border-radius: 100px;
            font-weight: 500;
            font-family: 'Roboto', sans-serif;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.18s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .md3-confirm-btn-cancelar {
            background: transparent;
            color: var(--md-sys-color-primary);
        }

        .md3-confirm-btn-cancelar:hover {
            background: color-mix(in srgb, var(--md-sys-color-primary) 10%, transparent);
        }

        .md3-confirm-btn-aceptar {
            background: var(--md-sys-color-error);
            color: var(--md-sys-color-on-error);
            box-shadow: 0 2px 6px rgba(0,0,0,0.18);
        }

        .md3-confirm-btn-aceptar:hover {
            background: color-mix(in srgb, var(--md-sys-color-error) 85%, black);
            box-shadow: 0 4px 12px rgba(0,0,0,0.22);
            transform: translateY(-1px);
        }

        .md3-confirm-btn-aceptar:active { transform: translateY(0); }

        /* ── Divisor sutil antes de acciones ── */
        .md3-confirm-divider {
            height: 1px;
            background: var(--md-sys-color-outline-variant);
            margin: 0 0 20px;
            opacity: 0.6;
        }
    `;
    document.head.appendChild(style);
})();


// ── 1. NOTIFICACIONES ─────────────────────────────────────────────────────────
function mostrarNotificacion(mensaje, tipo = 'info') {
    let contenedor = document.getElementById('md3-toast-container');
    if (!contenedor) {
        contenedor = document.createElement('div');
        contenedor.id = 'md3-toast-container';
        document.body.appendChild(contenedor);
    }

    const esError = tipo === 'error';
    const icono   = esError ? 'error'        : 'check_circle';
    const clase   = esError ? 'toast-error'  : 'toast-info';

    const toast = document.createElement('div');
    toast.className = `md3-toast ${clase}`;
    toast.innerHTML = `
        <div class="toast-icono">
            <span class="material-symbols-outlined">${icono}</span>
        </div>
        <span class="toast-texto">${mensaje}</span>
        <span class="material-symbols-outlined toast-cerrar">close</span>
        <div class="toast-barra"></div>
    `;

    contenedor.appendChild(toast);

    // Animación de entrada
    requestAnimationFrame(() => {
        requestAnimationFrame(() => toast.classList.add('toast-visible'));
    });

    const cerrar = () => {
        toast.classList.add('toast-saliendo');
        toast.addEventListener('transitionend', () => toast.remove(), { once: true });
    };

    const timer = setTimeout(cerrar, 5000);

    // Pausar barra al hacer hover
    toast.addEventListener('mouseenter', () => {
        clearTimeout(timer);
        const barra = toast.querySelector('.toast-barra');
        if (barra) barra.style.animationPlayState = 'paused';
    });
    toast.addEventListener('mouseleave', () => {
        const barra = toast.querySelector('.toast-barra');
        if (barra) barra.style.animationPlayState = 'running';
        setTimeout(cerrar, 2000);
    });

    toast.querySelector('.toast-cerrar').addEventListener('click', () => {
        clearTimeout(timer);
        cerrar();
    });
}


// ── 2. MODAL DE CONFIRMACIÓN ──────────────────────────────────────────────────
function mostrarConfirmacionMD3(mensaje) {
    return new Promise((resolve) => {
        const scrim = document.createElement('div');
        scrim.className = 'md3-confirm-scrim';

        const mensajeHTML = mensaje.replace(/\n/g, '<br>');

        scrim.innerHTML = `
            <div class="md3-confirm-dialog">
                <div class="md3-confirm-header">
                    <div class="md3-confirm-icono-wrap">
                        <span class="material-symbols-outlined">warning</span>
                    </div>
                    <h3 class="md3-confirm-title">Atención</h3>
                </div>

                <div class="md3-confirm-body">${mensajeHTML}</div>

                <div class="md3-confirm-divider"></div>

                <div class="md3-confirm-actions">
                    <button class="md3-confirm-btn md3-confirm-btn-cancelar" id="btn-conf-cancelar">
                        Cancelar
                    </button>
                    <button class="md3-confirm-btn md3-confirm-btn-aceptar" id="btn-conf-aceptar">
                        <span class="material-symbols-outlined" style="font-size:18px;">save</span>
                        Guardar de todos modos
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(scrim);

        // Animación de entrada
        requestAnimationFrame(() => {
            requestAnimationFrame(() => scrim.classList.add('visible'));
        });

        const cerrar = (resultado) => {
            scrim.classList.remove('visible');
            scrim.addEventListener('transitionend', () => {
                scrim.remove();
                resolve(resultado);
            }, { once: true });
        };

        scrim.querySelector('#btn-conf-cancelar').addEventListener('click', () => cerrar(false));
        scrim.querySelector('#btn-conf-aceptar').addEventListener('click', () => cerrar(true));

        // Cerrar al hacer clic en el fondo
        scrim.addEventListener('click', (e) => {
            if (e.target === scrim) cerrar(false);
        });

        // Cerrar con Escape
        const onKey = (e) => {
            if (e.key === 'Escape') {
                document.removeEventListener('keydown', onKey);
                cerrar(false);
            }
        };
        document.addEventListener('keydown', onKey);
    });
}