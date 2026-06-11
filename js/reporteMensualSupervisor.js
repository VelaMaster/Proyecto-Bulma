// js/reporteMensualSupervisor.js
// Variante del reporte mensual para SUPERVISOR.
// Reutiliza la estructura visual del promotor (mismo layout/tabla),
// pero apunta a los endpoints /supervisor/*_promotor.php y agrega
// el botón "Aprobar para Distribución".

document.addEventListener('DOMContentLoaded', () => {
    const ctx = window.REPORTE_CTX || {};
    if (ctx.modo !== 'supervisor') return;

    const contenedorTablas = document.getElementById('contenedorTablas');
    const periodoInicio    = document.getElementById('periodo_inicio');
    const periodoFin       = document.getElementById('periodo_fin');
    const btnGuardar       = document.getElementById('btnGuardar');
    const btnAprobar       = document.getElementById('btnAprobar');
    const chkGenerarPDF    = document.getElementById('chkGenerarPDF');

    const L_X_CAJA  = 72;
    const L_X_SOBRE = 2;
    const nombresMeses = ["", "Enero","Febrero","Marzo","Abril","Mayo","Junio",
                          "Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];

    // ─── Helpers ───────────────────────────────────────────────────
    const litrosACS = (l) => isNaN(l) || l < 0
        ? { c: 0, s: 0 }
        : { c: Math.floor(l / L_X_CAJA), s: Math.floor((l % L_X_CAJA) / L_X_SOBRE) };

    const precioDeTipo = (t) => parseInt(t) === 0 ? '$4.50' : '$6.50';
    const claveTiendaMostrar = (l) => parseInt(l.TIPO_PUNTO_VENTA) === 2 ? 'DM' : (l.NUM_TIENDA || '');

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
            <span style="flex-grow:1; font-size:0.9rem; font-weight:500;">${msg}</span>
            <span class="material-symbols-outlined btn-cerrar" style="cursor:pointer; font-size:20px; opacity:0.7;">close</span>`;
        cont.appendChild(t);
        requestAnimationFrame(() => { t.style.opacity = '1'; t.style.transform = 'translateY(0)'; });
        const cerrar = () => { t.style.opacity = '0'; t.style.transform = 'translateY(-20px)'; setTimeout(()=>t.remove(), 300); };
        const to = setTimeout(cerrar, 6000);
        t.querySelector('.btn-cerrar').addEventListener('click', () => { clearTimeout(to); cerrar(); });
    }

    function confirmar(mensaje, titulo = '¿Continuar?') {
        return new Promise((resolve) => {
            const back = document.createElement('div');
            Object.assign(back.style, {
                position:'fixed', inset:'0', background:'rgba(0,0,0,.45)',
                display:'flex', alignItems:'center', justifyContent:'center', zIndex:'100000'
            });
            back.innerHTML = `
                <div style="background:var(--md-sys-color-surface-container-high); color:var(--md-sys-color-on-surface);
                            padding:24px; border-radius:24px; max-width:420px; width:90%; box-shadow:0 8px 24px rgba(0,0,0,.3);">
                    <h3 style="margin:0 0 12px; font-weight:500; font-size:1.1rem;">${titulo}</h3>
                    <p style="margin:0 0 20px; font-size:0.95rem; line-height:1.4;">${mensaje}</p>
                    <div style="display:flex; gap:8px; justify-content:flex-end;">
                        <md-text-button class="btn-no">No</md-text-button>
                        <md-filled-button class="btn-si">Sí</md-filled-button>
                    </div>
                </div>`;
            document.body.appendChild(back);
            const cerrar = (r) => { back.remove(); resolve(r); };
            back.querySelector('.btn-si').addEventListener('click', () => cerrar(true));
            back.querySelector('.btn-no').addEventListener('click', () => cerrar(false));
            back.addEventListener('click', (e) => { if (e.target === back) cerrar(false); });
        });
    }

    function calcularFila(fila) {
        const num = (sel) => parseFloat(fila.querySelector(sel)?.value) || 0;
        const litrosIni   = num('input[name="inv_ini_cajas[]"]') * L_X_CAJA + num('input[name="inv_ini_sobres[]"]') * L_X_SOBRE;
        const litrosDot   = num('input[name="dot_recibida_cajas[]"]') * L_X_CAJA;
        const totalLitros = litrosIni + litrosDot;
        const fmtTotal    = litrosACS(totalLitros);
        fila.querySelector('input[name="total_cajas[]"]').value  = fmtTotal.c;
        fila.querySelector('input[name="total_sobres[]"]').value = fmtTotal.s;
        const litrosVend  = num('input[name="dot_vend_cajas[]"]') * L_X_CAJA + num('input[name="dot_vend_sobres[]"]') * L_X_SOBRE;
        const litrosFin   = totalLitros - litrosVend;
        const fmtFin      = litrosACS(litrosFin >= 0 ? litrosFin : 0);
        fila.querySelector('input[name="inv_fin_cajas[]"]').value  = fmtFin.c;
        fila.querySelector('input[name="inv_fin_sobres[]"]').value = fmtFin.s;
    }

    // ─── Construcción visual (idéntica a la del promotor) ─────────
    function thead() {
        return `
            <thead>
                <tr class="header-main">
                    <th rowspan="2" class="col-numero">N° PUNTO<br>DE VENTA</th>
                    <th rowspan="2" class="col-clave">CLAVE<br>TIENDA</th>
                    <th rowspan="2" class="col-precio">PRECIO</th>
                    <th colspan="2">INVENTARIO<br>INICIAL</th>
                    <th rowspan="2">DOTACIÓN<br>RECIBIDA<br>(CAJAS)</th>
                    <th colspan="2">TOTAL (INV INI<br>+ DOT REC.)</th>
                    <th colspan="2">DOT. VENDIDA<br>EN EL PERIODO</th>
                    <th colspan="2">INVENTARIO<br>FINAL</th>
                    <th colspan="2">SEGÚN REG. DE<br>RETIRO DE VENTAS</th>
                    <th rowspan="2">No. DE FAM.<br>QUE NO ACUD.</th>
                    <th colspan="2">SOBRES</th>
                    <th rowspan="2" class="col-obs">OBSERVACIONES</th>
                </tr>
                <tr class="header-sub">
                    <th class="col-cajas">CAJAS</th><th class="col-sobres">SOB.</th>
                    <th class="col-cajas">CAJAS</th><th class="col-sobres">SOB.</th>
                    <th class="col-cajas">CAJAS</th><th class="col-sobres">SOB.</th>
                    <th class="col-cajas">CAJAS</th><th class="col-sobres">SOB.</th>
                    <th class="col-cajas">CAJAS</th><th class="col-sobres">SOB.</th>
                    <th class="col-cajas">ROTOS</th><th class="col-cajas">FALT.</th>
                </tr>
            </thead>`;
    }
    function crearFila(lech) {
        const tr = document.createElement('tr');
        tr.dataset.lecher    = lech.LECHER;
        tr.dataset.tipoVenta = lech.TIPO_PUNTO_VENTA;
        const precio = precioDeTipo(lech.TIPO_PUNTO_VENTA);

        tr.innerHTML = `
            <td><input type="text"   class="cell-input" name="punto_venta[]"  value="${lech.LECHER}" readonly></td>
            <td><input type="text"   class="cell-input" name="clave_tienda[]" value="${claveTiendaMostrar(lech)}" readonly></td>
            <td><span class="precio-pill ${parseInt(lech.TIPO_PUNTO_VENTA)===0 ? 'precio-450' : 'precio-650'}">${precio}</span>
                <input type="hidden" name="precio[]"     value="${precio}">
                <input type="hidden" name="tipo_venta[]" value="${lech.TIPO_PUNTO_VENTA}"></td>
            <td><input type="number" class="cell-input" name="inv_ini_cajas[]"   value=""></td>
            <td><input type="number" class="cell-input" name="inv_ini_sobres[]"  value="0"></td>
            <td><input type="number" class="cell-input" name="dot_recibida_cajas[]" value="0"></td>
            <td class="td-total"><input type="number" class="cell-input" name="total_cajas[]"  readonly></td>
            <td class="td-total"><input type="number" class="cell-input" name="total_sobres[]" readonly></td>
            <td><input type="number" class="cell-input" name="dot_vend_cajas[]"  value="0"></td>
            <td><input type="number" class="cell-input" name="dot_vend_sobres[]" value="0"></td>
            <td><input type="number" class="cell-input" name="inv_fin_cajas[]"   readonly></td>
            <td><input type="number" class="cell-input" name="inv_fin_sobres[]"  readonly></td>
            <td><input type="number" class="cell-input" name="retiro_cajas[]"    value="0"></td>
            <td><input type="number" class="cell-input" name="retiro_sobres[]"   value="0"></td>
            <td><input type="number" class="cell-input" name="familias_no_acud[]" value="0"></td>
            <td><input type="number" class="cell-input" name="sobres_rotos[]"    value="0"></td>
            <td><input type="number" class="cell-input" name="sobres_falt[]"     value="0"></td>
            <td><input type="text"   class="cell-input" name="observaciones[]"   value=""></td>
        `;
        return tr;
    }
    function crearTablaAlmacen(nombre, lecherias) {
        const wrapper = document.createElement('div');
        wrapper.className = 'almacen-block';
        wrapper.dataset.almacen = nombre;
        wrapper.style.marginBottom = '24px';
        wrapper.innerHTML = `
            <div class="almacen-header" style="display:flex; align-items:center; gap:10px; padding:10px 14px; margin-bottom:8px;
                 background:var(--md-sys-color-secondary-container); color:var(--md-sys-color-on-secondary-container); border-radius:12px;">
                <span class="material-symbols-outlined" style="font-size:22px;">warehouse</span>
                <strong style="font-size:1rem;">Almacén: ${nombre || '(sin nombre)'}</strong>
                <span style="margin-left:auto; font-size:0.85rem; opacity:.85;">${lecherias.length} lechería${lecherias.length===1?'':'s'}</span>
            </div>
            <div class="reporte-wrapper">
                <table class="reporte-table">${thead()}<tbody></tbody></table>
            </div>`;
        const tbody = wrapper.querySelector('tbody');
        lecherias.forEach(l => {
            const fila = crearFila(l);
            tbody.appendChild(fila);
            if (l.encontrado) {
                fila.querySelector('input[name="inv_ini_cajas[]"]').value      = l.inv_ini_cajas || 0;
                fila.querySelector('input[name="inv_ini_sobres[]"]').value     = l.inv_ini_sobres || 0;
                fila.querySelector('input[name="dot_recibida_cajas[]"]').value = l.dot_recibida_cajas || 0;
                fila.querySelector('input[name="dot_vend_cajas[]"]').value     = l.venta_cajas || 0;
                fila.querySelector('input[name="dot_vend_sobres[]"]').value    = l.venta_sobres || 0;
                fila.querySelector('input[name="retiro_cajas[]"]').value       = l.retiro_cajas || 0;
                fila.querySelector('input[name="retiro_sobres[]"]').value      = l.retiro_sobres || 0;
            }
            calcularFila(fila);
            if (l.encontrado) {
                fila.querySelector('input[name="total_cajas[]"]').value    = l.abasto_cajas || 0;
                fila.querySelector('input[name="total_sobres[]"]').value   = l.abasto_sobres || 0;
                fila.querySelector('input[name="inv_fin_cajas[]"]').value  = l.inv_fin_cajas || 0;
                fila.querySelector('input[name="inv_fin_sobres[]"]').value = l.inv_fin_sobres || 0;
            }
        });
        return wrapper;
    }

    // ─── Carga inicial ─────────────────────────────────────────────
    async function cargar() {
        const url = `${ctx.endpoints.lecherias}?promotor=${ctx.promotor_id}&mes_reporte=${ctx.mes}&anio_reporte=${ctx.anio}`;
        try {
            const r = await fetch(url);
            const data = await r.json();
            if (data.error) { notificar(data.mensaje, 'error'); return; }
            if (!Array.isArray(data) || data.length === 0) {
                contenedorTablas.innerHTML = '<div style="text-align:center; padding:32px; color:var(--md-sys-color-error);">No se encontraron lecherías para este promotor.</div>';
                return;
            }
            const grupos = {};
            data.forEach(l => {
                const k = (l.ALMACEN_RURAL || '(sin almacén)').trim();
                (grupos[k] = grupos[k] || []).push(l);
            });
            contenedorTablas.innerHTML = '';
            Object.keys(grupos).sort().forEach(n => {
                contenedorTablas.appendChild(crearTablaAlmacen(n, grupos[n]));
            });
            await restaurarReporteGuardado();
            if (ctx.yaAprobado) bloquearEdicion();
        } catch (e) {
            notificar('Error al conectar con el servidor: ' + e.message, 'error');
        }
    }

    async function restaurarReporteGuardado() {
        try {
            const r = await fetch(`${ctx.endpoints.reporte}?promotor=${ctx.promotor_id}&mes=${ctx.mes}&anio=${ctx.anio}`);
            const d = await r.json();
            if (!d.encontrado || !Array.isArray(d.almacenes)) return;

            const mapa = {};
            d.almacenes.forEach(alm => (alm.lecherias || []).forEach(lec => mapa[lec.punto_venta] = lec));

            if (d.meta) {
                if (periodoInicio && d.meta.periodo_inicio) periodoInicio.value = d.meta.periodo_inicio;
                if (periodoFin    && d.meta.periodo_fin)    periodoFin.value    = d.meta.periodo_fin;
            }

            contenedorTablas.querySelectorAll('tbody tr[data-lecher]').forEach(fila => {
                const g = mapa[fila.dataset.lecher];
                if (!g) return;
                const set = (n, v) => {
                    const i = fila.querySelector(`input[name="${n}[]"]`);
                    if (i && v !== undefined && v !== null) i.value = v;
                };
                set('inv_ini_cajas',      g.inv_ini_cajas);
                set('inv_ini_sobres',     g.inv_ini_sobres);
                set('dot_recibida_cajas', g.dot_recib_cajas);
                set('dot_vend_cajas',     g.vend_cajas);
                set('dot_vend_sobres',    g.vend_sobres);
                set('retiro_cajas',       g.retiro_cajas);
                set('retiro_sobres',      g.retiro_sobres);
                set('familias_no_acud',   g.familias_no_acud);
                set('sobres_rotos',       g.sobres_rotos);
                set('sobres_falt',        g.sobres_falt);
                set('observaciones',      g.observaciones !== 'x' ? g.observaciones : '');
                calcularFila(fila);
                set('total_cajas',    g.total_cajas);
                set('total_sobres',   g.total_sobres);
                set('inv_fin_cajas',  g.inv_fin_cajas);
                set('inv_fin_sobres', g.inv_fin_sobres);
            });
            notificar(`Reporte de ${d.total_lecherias} lechería(s) cargado.`, 'info');
        } catch (_) { /* sin reporte previo */ }
    }

    function bloquearEdicion() {
        contenedorTablas.querySelectorAll('input').forEach(i => i.readOnly = true);
        if (btnGuardar) btnGuardar.disabled = true;
        if (btnAprobar) btnAprobar.disabled = true;
    }

    // Recalcular al editar
    contenedorTablas.addEventListener('input', (e) => {
        if (e.target.tagName === 'INPUT' && e.target.type === 'number') {
            const fila = e.target.closest('tr');
            if (fila) calcularFila(fila);
        }
    });

    // ─── Construcción del payload ──────────────────────────────────
    function construirPayload() {
        const almacenes = [];
        contenedorTablas.querySelectorAll('.almacen-block').forEach(b => {
            const lecherias = [];
            b.querySelectorAll('tbody tr[data-lecher]').forEach(f => {
                const v = (n) => f.querySelector(`input[name="${n}[]"]`)?.value ?? '';
                const obs = v('observaciones').trim();
                lecherias.push({
                    punto_venta:        v('punto_venta'),
                    clave_tienda:       v('clave_tienda'),
                    tipo_venta:         v('tipo_venta'),
                    precio:             v('precio'),
                    inv_ini_cajas:      v('inv_ini_cajas'),
                    inv_ini_sobres:     v('inv_ini_sobres'),
                    dot_recibida_cajas: v('dot_recibida_cajas'),
                    total_cajas:        v('total_cajas'),
                    total_sobres:       v('total_sobres'),
                    dot_vend_cajas:     v('dot_vend_cajas'),
                    dot_vend_sobres:    v('dot_vend_sobres'),
                    inv_fin_cajas:      v('inv_fin_cajas'),
                    inv_fin_sobres:     v('inv_fin_sobres'),
                    retiro_cajas:       v('retiro_cajas'),
                    retiro_sobres:      v('retiro_sobres'),
                    familias_no_acud:   v('familias_no_acud'),
                    sobres_rotos:       v('sobres_rotos'),
                    sobres_falt:        v('sobres_falt'),
                    observaciones:      obs || 'x'
                });
            });
            almacenes.push({ almacen: b.dataset.almacen, lecherias });
        });
        return {
            promotor_id:      ctx.promotor_id,
            mes_reporte:      ctx.mes,
            anio_reporte:     ctx.anio,
            periodo_inicio:   periodoInicio?.value || '',
            periodo_fin:      periodoFin?.value || '',
            supervisor:       document.getElementById('supervisor')?.value || '',
            promotor:         document.querySelector('.firma-name')?.textContent?.trim() || '',
            almacenes
        };
    }

    // ─── Guardar ───────────────────────────────────────────────────
    btnGuardar?.addEventListener('click', async () => {
        if (ctx.yaAprobado) { notificar('Reporte ya aprobado.', 'error'); return; }
        const datos = construirPayload();
        try {
            btnGuardar.disabled = true;
            const r = await fetch(ctx.endpoints.guardar, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datos)
            });
            const j = await r.json().catch(() => ({}));
            if (!r.ok || j.status !== 'success') {
                throw new Error(j.mensaje || 'No se pudo guardar.');
            }
            notificar('Cambios guardados.', 'info');
        } catch (e) {
            notificar(e.message, 'error');
        } finally {
            btnGuardar.disabled = ctx.yaAprobado;
        }
    });

    // ─── Aprobar ───────────────────────────────────────────────────
    btnAprobar?.addEventListener('click', async () => {
        if (ctx.yaAprobado) return;
        const ok = await confirmar(
            'Una vez aprobado, el reporte quedará liberado para el área de Distribución y ya no podrá editarse. ¿Continuar?',
            'Aprobar para Distribución'
        );
        if (!ok) return;

        // Guardar primero los cambios actuales
        try {
            btnAprobar.disabled = true;
            const datos = construirPayload();
            const rG = await fetch(ctx.endpoints.guardar, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datos)
            });
            const jG = await rG.json().catch(() => ({}));
            if (!rG.ok || jG.status !== 'success') throw new Error(jG.mensaje || 'No se pudieron guardar los cambios previos.');

            const rA = await fetch(ctx.endpoints.aprobar, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    promotor_id: ctx.promotor_id,
                    mes:  ctx.mes,
                    anio: ctx.anio
                })
            });
            const jA = await rA.json().catch(() => ({}));
            if (!rA.ok || jA.status !== 'success') throw new Error(jA.mensaje || 'No se pudo aprobar.');

            notificar('Reporte aprobado para Distribución.', 'info');
            ctx.yaAprobado = true;
            btnAprobar.innerHTML = '<md-icon slot="icon">verified</md-icon> Ya aprobado';
            bloquearEdicion();
        } catch (e) {
            notificar(e.message, 'error');
            btnAprobar.disabled = false;
        }
    });

    cargar();
});
