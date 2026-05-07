// ────────────────────────────────────────────────────────────────────
//  Pantalla "Lecherías" del Supervisor.
//  Flujo:
//    1. Carga lista de promotores (api_supervisor.php)
//    2. Al elegir promotor + mes + año -> api_estado_promotor.php
//    3. Pinta tabla de lecherías con su estado (capturado/pendiente, PDF)
//    4. Pinta los documentos consolidados (Reporte mensual y Requerimiento)
//    5. Soporta preselección de promotor por query string ?promotor=ID
// ────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const selPromotor = document.getElementById('selPromotor');
    const selMes      = document.getElementById('selMes');
    const inpAnio     = document.getElementById('inpAnio');
    const resumen     = document.getElementById('resumen');
    const cardInv     = document.getElementById('cardInventarios');
    const cardDocs    = document.getElementById('cardDocumentos');
    const placeholder = document.getElementById('placeholder');
    const tbody       = document.querySelector('#tablaLecherias tbody');

    const estadoReporte = document.getElementById('estadoReporte');
    const estadoReq     = document.getElementById('estadoReq');
    const btnVerReporte = document.getElementById('btnVerReporte');
    const btnVerReq     = document.getElementById('btnVerReq');

    const PRESELECCIONADO = (typeof PROMOTOR_PRESELECCIONADO !== 'undefined')
        ? PROMOTOR_PRESELECCIONADO : 0;

    // ──── Helpers ─────────────────────────────────────────────────
    function notificar(msg, tipo = 'info') {
        let cont = document.getElementById('toast-container-md3');
        if (!cont) {
            cont = document.createElement('div');
            cont.id = 'toast-container-md3';
            Object.assign(cont.style, {
                position:'fixed', top:'24px', left:'50%', transform:'translateX(-50%)',
                display:'flex', flexDirection:'column', gap:'10px',
                zIndex:'99999', pointerEvents:'none'
            });
            document.body.appendChild(cont);
        }
        const isErr = tipo === 'error';
        const t = document.createElement('div');
        Object.assign(t.style, {
            backgroundColor: isErr ? 'var(--md-sys-color-error-container)' : 'var(--md-sys-color-surface-container-highest)',
            color: isErr ? 'var(--md-sys-color-on-error-container)' : 'var(--md-sys-color-on-surface)',
            padding:'12px 20px', borderRadius:'8px',
            boxShadow:'0px 4px 12px rgba(0,0,0,0.3)',
            display:'flex', alignItems:'center', gap:'12px',
            minWidth:'300px', maxWidth:'90vw', pointerEvents:'auto',
            opacity:'0', transform:'translateY(-20px)',
            transition:'all 0.3s cubic-bezier(0.2,0,0,1)'
        });
        t.innerHTML = `
            <span class="material-symbols-outlined" style="color:${isErr?'var(--md-sys-color-error)':'var(--md-sys-color-primary)'}; font-size:24px;">${isErr?'error':'check_circle'}</span>
            <span style="flex-grow:1; font-size:0.9rem; font-weight:500;">${msg}</span>`;
        cont.appendChild(t);
        requestAnimationFrame(() => { t.style.opacity = '1'; t.style.transform = 'translateY(0)'; });
        setTimeout(() => { t.style.opacity = '0'; t.style.transform = 'translateY(-20px)'; setTimeout(()=>t.remove(), 300); }, 4000);
    }

    function setResumen(texto, tipo = 'info') {
        if (!resumen) return;
        if (!texto) { resumen.style.display = 'none'; return; }
        resumen.textContent = texto;
        resumen.className = 'estado-pill ' + (
            tipo === 'ok'    ? 'estado-ok'    :
            tipo === 'falta' ? 'estado-falta' : 'estado-info'
        );
        resumen.style.display = 'inline-flex';
    }

    function pillEstado(ok, textoOk = 'Capturado', textoFalta = 'Pendiente') {
        const cls = ok ? 'estado-ok' : 'estado-falta';
        const ico = ok ? 'check_circle' : 'pending';
        const txt = ok ? textoOk : textoFalta;
        return `<span class="estado-pill ${cls}">
                    <span class="material-symbols-outlined" style="font-size:14px;">${ico}</span>${txt}
                </span>`;
    }

    function urlVerPDF(tipo, promotorId, mes, anio, lecher = '') {
        const p = new URLSearchParams({ tipo, promotor: promotorId, mes, anio });
        if (lecher) p.set('lecher', lecher);
        return `ver_pdf.php?${p.toString()}`;
    }

    // ──── Cargar lista de promotores en el select ────────────────
    async function cargarPromotores() {
        try {
            const r = await fetch('api_supervisor.php');
            const data = await r.json();
            if (data.status !== 'success') throw new Error(data.message || 'No se pudo cargar promotores');

            selPromotor.innerHTML = '<option value="">-- Selecciona un promotor --</option>';
            data.promotores.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = `${p.nombre} (${p.cantidad_lecherias} lecherías)`;
                selPromotor.appendChild(opt);
            });

            // Preselección desde query string
            if (PRESELECCIONADO && data.promotores.some(p => p.id == PRESELECCIONADO)) {
                selPromotor.value = String(PRESELECCIONADO);
                cargarEstado();
            }
        } catch (e) {
            console.error(e);
            selPromotor.innerHTML = '<option value="">Error al cargar promotores</option>';
            notificar('No se pudo cargar la lista de promotores', 'error');
        }
    }

    // ──── Cargar estado del promotor seleccionado ────────────────
    async function cargarEstado() {
        const promotor = selPromotor.value;
        const mes      = selMes.value;
        const anio     = inpAnio.value;

        if (!promotor) {
            placeholder.style.display = 'block';
            cardInv.style.display     = 'none';
            cardDocs.style.display    = 'none';
            setResumen('');
            return;
        }
        if (!mes || !anio) return;

        // Estado "cargando"
        placeholder.style.display = 'none';
        cardInv.style.display     = 'block';
        cardDocs.style.display    = 'block';
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:24px; color:var(--md-sys-color-on-surface-variant);">Cargando...</td></tr>';
        setResumen('Cargando...', 'info');

        try {
            const url = `api_estado_promotor.php?promotor=${encodeURIComponent(promotor)}&mes=${mes}&anio=${anio}`;
            const r = await fetch(url);
            const data = await r.json();
            if (data.status !== 'success') throw new Error(data.message || 'Error al cargar estado');

            renderLecherias(data, promotor, mes, anio);
            renderDocumentos(data, promotor, mes, anio);
        } catch (e) {
            console.error(e);
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:24px; color:var(--md-sys-color-error);">${e.message}</td></tr>`;
            setResumen('Error al cargar', 'falta');
            notificar(e.message, 'error');
        }
    }

    function renderLecherias(data, promotor, mes, anio) {
        const lechs = data.lecherias || [];

        if (lechs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:24px; color:var(--md-sys-color-on-surface-variant);">Este promotor no tiene lecherías asignadas a tu zona.</td></tr>';
            setResumen('Sin lecherías', 'info');
            return;
        }

        const capturadas = lechs.filter(l => l.tiene_inventario).length;
        const total      = lechs.length;
        const tipo       = capturadas === total ? 'ok' : (capturadas === 0 ? 'falta' : 'info');
        setResumen(`${capturadas} de ${total} inventarios`, tipo);

        tbody.innerHTML = '';
        lechs.forEach(l => {
            const tr = document.createElement('tr');

            // Botón "Ver PDF" sólo si hay PDF generado
            const pdfBtn = l.pdf
                ? `<md-outlined-button onclick="window.open('${urlVerPDF('inv', promotor, mes, anio, l.lecher)}','_blank')">
                       <md-icon slot="icon">picture_as_pdf</md-icon> Ver PDF
                   </md-outlined-button>`
                : `<md-outlined-button disabled>
                       <md-icon slot="icon">picture_as_pdf</md-icon> Sin PDF
                   </md-outlined-button>`;

            tr.innerHTML = `
                <td>
                    <strong>${l.lecher}</strong><br>
                    <span style="font-size:0.8rem; color:var(--md-sys-color-on-surface-variant);">${l.nombre || ''}</span>
                </td>
                <td>${l.num_tienda || ''}</td>
                <td>${l.almacen || ''}</td>
                <td>${pillEstado(!!l.tiene_inventario, 'Capturado', 'Falta')}</td>
                <td style="text-align:right;">${pdfBtn}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    function pintarPill(pillEl, ok, textoOk, textoFalta) {
        if (!pillEl) return;
        pillEl.className = 'estado-pill ' + (ok ? 'estado-ok' : 'estado-falta');
        pillEl.innerHTML = `<span class="material-symbols-outlined" style="font-size:14px;">${ok?'check_circle':'pending'}</span>${ok?textoOk:textoFalta}`;
    }

    function renderDocumentos(data, promotor, mes, anio) {
        // Reporte mensual
        const rep = data.reporte || {};
        pintarPill(estadoReporte, !!rep.existe, 'Generado', 'No generado');
        if (rep.pdf) {
            btnVerReporte.disabled = false;
            btnVerReporte.onclick = () => window.open(urlVerPDF('rep', promotor, mes, anio), '_blank');
        } else {
            btnVerReporte.disabled = true;
            btnVerReporte.onclick = null;
        }

        // Requerimiento
        const req = data.requerimiento || {};
        pintarPill(estadoReq, !!req.existe, 'Generado', 'No generado');
        if (req.pdf) {
            btnVerReq.disabled = false;
            btnVerReq.onclick = () => window.open(urlVerPDF('req', promotor, mes, anio), '_blank');
        } else {
            btnVerReq.disabled = true;
            btnVerReq.onclick = null;
        }
    }

    // ──── Eventos ─────────────────────────────────────────────────
    selPromotor.addEventListener('change', cargarEstado);
    selMes     .addEventListener('change', cargarEstado);
    inpAnio    .addEventListener('change', cargarEstado);

    // Init
    cargarPromotores();
});
