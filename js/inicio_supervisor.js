// js/inicio_supervisor.js
// Lista de promotores del supervisor + avance del mes (X de Y).

let _promotoresCache = null;

document.addEventListener('DOMContentLoaded', () => {
    cargarPromotores();

    const selMes  = document.getElementById('avance_mes');
    const inpAnio = document.getElementById('avance_anio');
    if (selMes)  selMes.addEventListener('change',  cargarAvance);
    if (inpAnio) inpAnio.addEventListener('change', cargarAvance);
});

async function cargarPromotores() {
    const grid = document.getElementById('promotoresGrid');

    try {
        const response = await fetch('api_supervisor.php');
        const data = await response.json();

        if (data.status === 'success') {
            grid.innerHTML = '';

            if (data.promotores.length === 0) {
                grid.innerHTML = '<p style="color: var(--md-sys-color-on-surface-variant); width: 100%; text-align: center; grid-column: 1/-1;">No tienes promotores asignados actualmente.</p>';
                return;
            }

            _promotoresCache = data.promotores;
            renderPromotores(data.promotores);
            cargarAvance();   // pinta el avance del mes seleccionado
        } else {
            console.error('Error del servidor:', data.message);
            grid.innerHTML = `<p style="color: red;">Error: ${data.message}</p>`;
        }

    } catch (error) {
        console.error('Error de red:', error);
        grid.innerHTML = '<p>Error de conexión al cargar datos.</p>';
    }
}

function renderPromotores(promotores) {
    const grid = document.getElementById('promotoresGrid');

    promotores.forEach(promotor => {
        const card = document.createElement('div');
        card.className = 'promotor-card';
        card.dataset.promotorId = promotor.id;

        const inicial = (promotor.nombre || '?').charAt(0).toUpperCase();

        let listaItems = '';
        if (promotor.lecherias && promotor.lecherias.length > 0) {
            promotor.lecherias.forEach(lech => {
                listaItems += `
                    <li style="padding:12px; border:1px solid var(--md-sys-color-outline-variant); border-radius:12px;
                               display:flex; align-items:center; gap:12px; background:var(--md-sys-color-surface-container);">
                        <div style="background:var(--md-sys-color-secondary-container); color:var(--md-sys-color-on-secondary-container);
                                    width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <md-icon>storefront</md-icon>
                        </div>
                        <div style="display:flex; flex-direction:column;">
                            <strong style="color:var(--md-sys-color-on-surface); font-size:1rem;">${lech.numero}</strong>
                            <span style="font-size:0.85rem; color:var(--md-sys-color-on-surface-variant);">${lech.nombre}</span>
                        </div>
                    </li>`;
            });
        } else {
            listaItems = `<li style="padding:12px; color:var(--md-sys-color-on-surface-variant);">Sin lecherías asignadas.</li>`;
        }

        card.innerHTML = `
            <div class="card-main-content">
                <div class="promotor-header">
                    <div class="promotor-avatar">${inicial}</div>
                    <div class="promotor-info" style="flex-grow:1; min-width:0;">
                        <h4 style="margin:0 0 4px 0; font-size:1.1rem; color:var(--md-sys-color-on-surface);">${promotor.nombre}</h4>
                        <p style="margin:0; color:var(--md-sys-color-primary); font-weight:500; font-size:0.9rem;">${promotor.cantidad_lecherias} lecherías</p>

                        <!-- Avance del mes -->
                        <div class="avance-block" style="margin-top:8px;">
                            <div class="avance-badge" style="display:inline-flex; align-items:center; gap:6px; padding:3px 10px; border-radius:999px;
                                 font-size:0.78rem; font-weight:600;
                                 background:var(--md-sys-color-surface-container-high); color:var(--md-sys-color-on-surface-variant);">
                                <span class="material-symbols-outlined" style="font-size:14px;">hourglass_empty</span>
                                <span class="avance-text">Cargando avance…</span>
                            </div>
                            <div class="avance-progress" style="margin-top:6px; height:4px; background:var(--md-sys-color-surface-container-high);
                                 border-radius:999px; overflow:hidden;">
                                <div class="avance-bar" style="height:100%; width:0%; background:var(--md-sys-color-primary); transition:width .4s ease;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="detalles-wrapper">
                    <div class="detalles-inner">
                        <ul class="lista-interna-lecherias" style="list-style:none; margin:0 0 16px 0; padding:0; max-height:280px; overflow-y:auto;">
                            ${listaItems}
                        </ul>
                        <div style="display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap;
                                    width:100%; padding-top:16px; border-top:1px solid var(--md-sys-color-outline-variant);">
                            <md-outlined-button onclick="location.href='lecherias.php?promotor=${promotor.id}'">
                                <md-icon slot="icon">storefront</md-icon> Ver lecherías
                            </md-outlined-button>
                            <md-filled-button onclick="location.href='validarInventarios.php?promotor=${promotor.id}'">
                                <md-icon slot="icon">fact_check</md-icon> Validar inventarios
                            </md-filled-button>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top:auto; padding-top:12px; display:flex; justify-content:flex-end; width:100%;">
                <md-text-button class="btn-toggle-detalles">
                    <md-icon slot="icon" class="icon-toggle">expand_more</md-icon>
                    <span class="text-toggle">Ver detalles</span>
                </md-text-button>
            </div>
        `;

        grid.appendChild(card);
    });

    // Lógica de apertura/cierre + carga de detalle del mes
    document.querySelectorAll('.btn-toggle-detalles').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const cardElement = btn.closest('.promotor-card');
            const icon = btn.querySelector('.icon-toggle');
            const text = btn.querySelector('.text-toggle');

            if (!cardElement.classList.contains('expanded')) {
                document.querySelectorAll('.promotor-card.expanded').forEach(openCard => {
                    if (openCard !== cardElement) {
                        openCard.classList.remove('expanded');
                        openCard.querySelector('.icon-toggle').textContent = 'expand_more';
                        openCard.querySelector('.text-toggle').textContent = 'Ver detalles';
                    }
                });
                cardElement.classList.add('expanded');
                icon.textContent = 'expand_less';
                text.textContent = 'Ocultar detalles';
                cargarDetalleMesPromotor(cardElement);
            } else {
                cardElement.classList.remove('expanded');
                icon.textContent = 'expand_more';
                text.textContent = 'Ver detalles';
            }
        });
    });
}

// ────────────────────────────────────────────────────────────────────
//  Detalle por mes al expandir: trae el estado de cada lechería del
//  promotor (capturado/falta) y lo pinta dentro de su tarjeta.
// ────────────────────────────────────────────────────────────────────
async function cargarDetalleMesPromotor(cardEl) {
    const promotorId = cardEl.dataset.promotorId;
    const selMes  = document.getElementById('avance_mes');
    const inpAnio = document.getElementById('avance_anio');
    if (!promotorId || !selMes || !inpAnio) return;

    const mes  = selMes.value;
    const anio = inpAnio.value;

    // Inserta encabezado "X de Y inventarios capturados" arriba de la lista
    let header = cardEl.querySelector('.detalle-mes-header');
    if (!header) {
        header = document.createElement('div');
        header.className = 'detalle-mes-header';
        header.style.cssText = 'display:flex; align-items:center; gap:10px; padding:10px 14px; margin-bottom:10px; border-radius:12px;';
        const lista = cardEl.querySelector('.lista-interna-lecherias');
        if (lista) lista.parentElement.insertBefore(header, lista);
    }
    header.style.background = 'var(--md-sys-color-surface-container-high)';
    header.style.color = 'var(--md-sys-color-on-surface)';
    header.innerHTML = `
        <span class="material-symbols-outlined" style="color:var(--md-sys-color-primary);">calendar_month</span>
        <strong>${_nombreMesSv(mes)} ${anio}</strong>
        <span style="margin-left:auto; opacity:.7;">Cargando...</span>
    `;

    try {
        const r = await fetch(`api_estado_promotor.php?promotor=${promotorId}&mes=${mes}&anio=${anio}`);
        const data = await r.json();
        if (data.status !== 'success') throw new Error(data.message || 'Error');

        const lechs      = data.lecherias || [];
        const capturadas = lechs.filter(l => l.tiene_inventario).length;
        const total      = lechs.length;

        const tipo = capturadas === total && total > 0 ? 'ok'
                   : capturadas === 0 ? 'falta' : 'parcial';
        const colorBg = tipo === 'ok'    ? 'color-mix(in srgb, var(--md-sys-color-primary) 18%, transparent)'
                      : tipo === 'falta' ? 'color-mix(in srgb, var(--md-sys-color-error) 18%, transparent)'
                                         : 'color-mix(in srgb, var(--md-sys-color-tertiary) 18%, transparent)';
        const colorTx = tipo === 'ok'    ? 'var(--md-sys-color-primary)'
                      : tipo === 'falta' ? 'var(--md-sys-color-error)'
                                         : 'var(--md-sys-color-tertiary)';
        const ico = tipo === 'ok' ? 'check_circle' : (tipo === 'falta' ? 'pending' : 'progress_activity');

        header.style.background = colorBg;
        header.style.color = colorTx;
        header.innerHTML = `
            <span class="material-symbols-outlined">${ico}</span>
            <strong>${_nombreMesSv(mes)} ${anio}</strong>
            <span style="margin-left:auto; font-weight:600;">${capturadas} de ${total} inventarios</span>
        `;

        // Refrescar la lista interna pintando cada lechería con su estado
        const ul = cardEl.querySelector('.lista-interna-lecherias');
        if (ul) {
            const mapa = {};
            lechs.forEach(l => { mapa[l.lecher] = l; });

            ul.querySelectorAll('li').forEach(li => {
                const strongEl = li.querySelector('strong');
                if (!strongEl) return;
                const clave = strongEl.textContent.trim();
                const info  = mapa[clave];

                let pill = li.querySelector('.estado-pill-lech');
                if (!pill) {
                    pill = document.createElement('span');
                    pill.className = 'estado-pill-lech';
                    pill.style.cssText = 'margin-left:auto; padding:3px 10px; border-radius:999px; font-size:0.75rem; font-weight:600; display:inline-flex; align-items:center; gap:4px;';
                    li.appendChild(pill);
                }
                if (info && info.tiene_inventario) {
                    pill.innerHTML = `<span class="material-symbols-outlined" style="font-size:14px;">check_circle</span>Capturado`;
                    pill.style.background = 'color-mix(in srgb, var(--md-sys-color-primary) 18%, transparent)';
                    pill.style.color = 'var(--md-sys-color-primary)';
                } else {
                    pill.innerHTML = `<span class="material-symbols-outlined" style="font-size:14px;">pending</span>Falta`;
                    pill.style.background = 'color-mix(in srgb, var(--md-sys-color-error) 18%, transparent)';
                    pill.style.color = 'var(--md-sys-color-error)';
                }
            });
        }
    } catch (e) {
        console.error('Error cargando detalle mes promotor:', e);
        header.style.background = 'color-mix(in srgb, var(--md-sys-color-error) 18%, transparent)';
        header.style.color = 'var(--md-sys-color-error)';
        header.innerHTML = `<span class="material-symbols-outlined">error</span><strong>No se pudo cargar el detalle</strong>`;
    }
}

function _nombreMesSv(m) {
    const meses = ["", "Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];
    return meses[parseInt(m)] || '';
}

async function cargarAvance() {
    const selMes  = document.getElementById('avance_mes');
    const inpAnio = document.getElementById('avance_anio');
    if (!selMes || !inpAnio) return;

    const mes  = selMes.value;
    const anio = inpAnio.value;
    if (!mes || !anio) return;

    // Reset visual mientras carga
    document.querySelectorAll('.promotor-card .avance-text').forEach(el => el.textContent = 'Cargando…');
    document.querySelectorAll('.promotor-card .avance-bar').forEach(el => el.style.width = '0%');

    try {
        const r = await fetch(`api_avance_promotores.php?mes=${mes}&anio=${anio}`);
        const data = await r.json();
        if (data.status !== 'success') return;

        const mapa = {};
        data.promotores.forEach(p => { mapa[p.id] = p; });

        document.querySelectorAll('.promotor-card[data-promotor-id]').forEach(card => {
            const id = parseInt(card.dataset.promotorId);
            const a = mapa[id];

            const block  = card.querySelector('.avance-block');
            const badge  = card.querySelector('.avance-badge');
            const text   = card.querySelector('.avance-text');
            const icon   = card.querySelector('.avance-badge .material-symbols-outlined');
            const bar    = card.querySelector('.avance-bar');

            if (!a || a.total_lecherias === 0) {
                if (text) text.textContent = 'Sin lecherías asignadas';
                if (icon) icon.textContent = 'help_outline';
                if (bar)  bar.style.width = '0%';
                if (badge) {
                    badge.style.background = 'var(--md-sys-color-surface-container-high)';
                    badge.style.color = 'var(--md-sys-color-on-surface-variant)';
                }
                return;
            }

            text.textContent = `${a.capturadas} de ${a.total_lecherias} inventarios`;
            bar.style.width = a.porcentaje + '%';

            // Colores según avance
            if (a.capturadas === a.total_lecherias) {
                badge.style.background = 'color-mix(in srgb, var(--md-sys-color-primary) 22%, transparent)';
                badge.style.color = 'var(--md-sys-color-primary)';
                bar.style.background = 'var(--md-sys-color-primary)';
                icon.textContent = 'check_circle';
            } else if (a.capturadas === 0) {
                badge.style.background = 'color-mix(in srgb, var(--md-sys-color-error) 18%, transparent)';
                badge.style.color = 'var(--md-sys-color-error)';
                bar.style.background = 'var(--md-sys-color-error)';
                icon.textContent = 'pending';
            } else {
                badge.style.background = 'color-mix(in srgb, var(--md-sys-color-tertiary) 22%, transparent)';
                badge.style.color = 'var(--md-sys-color-tertiary)';
                bar.style.background = 'var(--md-sys-color-tertiary)';
                icon.textContent = 'progress_activity';
            }
        });

        // Si hay una tarjeta expandida, refresca su detalle por mes
        document.querySelectorAll('.promotor-card.expanded').forEach(c => {
            cargarDetalleMesPromotor(c);
        });
    } catch (e) {
        console.error('Error cargando avance:', e);
    }
}
