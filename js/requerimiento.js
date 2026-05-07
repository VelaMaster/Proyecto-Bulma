// ────────────────────────────────────────────────────────────────────
//  Requerimiento de Leche — Promotor
//  - El promotor solo elige MES BASE; el sistema genera UNA TABLA POR ALMACÉN
//  - "Mes base" = mes en que se hace el requerimiento
//      REQ. M.S. = mes base + 1
//      REQ.       = mes base + 2 (mes destino)
//  - Auto-rellena familias / beneficiarios / dotación teórica de la BDD
//    y los datos del inventario del mes base
//  - Checkbox "Generar PDF al guardar" + confirmación
// ────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const selectMesReporte  = document.getElementById('selectMesReporte');
    const inputAnioReporte  = document.getElementById('inputAnioReporte');
    const inputMesDestino   = document.getElementById('inputMesDestino');
    const mesDestinoLabel   = document.getElementById('mesDestinoLabel');
    const contenedorTablas  = document.getElementById('contenedorTablas');
    const btnGuardar        = document.getElementById('btnGuardar');
    const chkGenerarPDF     = document.getElementById('chkGenerarPDF');

    const L_X_CAJA = 72;
    const nombresMeses = ["", "Enero","Febrero","Marzo","Abril","Mayo","Junio",
                              "Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];

    // ──── Helpers ─────────────────────────────────────────────────
    function precioDeTipoVenta(tipo) {
        return parseInt(tipo) === 0 ? '$4.50' : '$6.50';
    }
    function sumarMeses(mes, anio, n) {
        let m = mes + n;
        let a = anio;
        while (m > 12) { m -= 12; a++; }
        while (m < 1)  { m += 12; a--; }
        return { mes: m, anio: a };
    }

    function actualizarCabecerasGlobal() {
        const mes  = parseInt(selectMesReporte.value);
        const anio = parseInt(inputAnioReporte.value);
        if (!mes || !anio) {
            if (inputMesDestino) inputMesDestino.value = '—';
            if (mesDestinoLabel) mesDestinoLabel.textContent = 'mes + 2';
            return null;
        }
        const ms = sumarMeses(mes, anio, 1);
        const md = sumarMeses(mes, anio, 2);

        if (inputMesDestino) inputMesDestino.value = `${nombresMeses[md.mes]} ${md.anio}`;
        if (mesDestinoLabel) mesDestinoLabel.textContent = `${nombresMeses[md.mes]} ${md.anio}`;
        return { ms, md };
    }

    // ──── Notificación toast ──────────────────────────────────────
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

    // ──── Cálculo automático del Inv. Final ──────────────────────
    function calcularFila(fila) {
        const num = (n) => parseFloat(fila.querySelector(`input[name="${n}[]"]`)?.value) || 0;
        const invFinal = num('inv_inicial') + num('surtimiento') - num('ventas');
        const inputInvFinal = fila.querySelector('input[name="inv_final[]"]');
        if (inputInvFinal) inputInvFinal.value = Math.max(0, invFinal);
    }

    // ──── Construcción de la tabla por almacén ───────────────────
    function thead(msNombre, mdNombre, mdAnio) {
        return `
            <thead>
                <tr class="header-main">
                    <th>NUMERO DE<br>PUNTO DE<br>VENTA</th>
                    <th>NO. DE<br>TIENDA</th>
                    <th>PRECIO</th>
                    <th>FAMILIAS</th>
                    <th>NO. DE<br>BENEFICIARIOS</th>
                    <th>DOTACION<br>TEORICA</th>
                    <th>INVENTARIO<br>INICIAL</th>
                    <th>SURT.</th>
                    <th>VENTAS</th>
                    <th>INVENTARIO<br>FINAL</th>
                    <th class="th-req-ms">REQ. M.S.<br>${msNombre}</th>
                    <th>V.M.S.</th>
                    <th class="th-req-actual">REQ.<br>${mdNombre} ${mdAnio}</th>
                    <th>OBSERVACIONES</th>
                </tr>
            </thead>`;
    }

    function crearFila(lech) {
        const tr = document.createElement('tr');
        tr.dataset.lecher = lech.LECHER;
        tr.dataset.tipoVenta = lech.TIPO_PUNTO_VENTA;
        const precio = precioDeTipoVenta(lech.TIPO_PUNTO_VENTA);
        tr.innerHTML = `
            <td><input type="text"   class="cell-input" name="punto_venta[]"      value="${lech.LECHER}" readonly></td>
            <td><input type="text"   class="cell-input" name="clave_tienda[]"     value="${lech.NUM_TIENDA || ''}" readonly></td>
            <td><span class="precio-pill ${parseInt(lech.TIPO_PUNTO_VENTA)===0 ? 'precio-450' : 'precio-650'}">${precio}</span>
                <input type="hidden" name="precio[]"     value="${precio}">
                <input type="hidden" name="tipo_venta[]" value="${lech.TIPO_PUNTO_VENTA}"></td>
            <td><input type="number" class="cell-input" name="familias[]"          readonly></td>
            <td><input type="number" class="cell-input" name="beneficiarios[]"     readonly></td>
            <td><input type="number" class="cell-input" name="dotacion_teorica[]"  readonly></td>
            <td><input type="number" class="cell-input" name="inv_inicial[]"       value=""></td>
            <td><input type="number" class="cell-input" name="surtimiento[]"       value="0"></td>
            <td><input type="number" class="cell-input" name="ventas[]"            value="0"></td>
            <td><input type="number" class="cell-input" name="inv_final[]"         readonly></td>
            <td><input type="number" class="cell-input" name="req_ms_anterior[]"   value=""></td>
            <td><input type="number" class="cell-input" name="vms[]"               value=""></td>
            <td><input type="number" class="cell-input" name="req_actual[]"        value=""></td>
            <td><input type="text"   class="cell-input" name="observaciones[]"     value=""></td>
        `;
        return tr;
    }

    function crearTablaAlmacen(almacenNombre, lecherias, ms, md) {
        const wrapper = document.createElement('div');
        wrapper.className = 'almacen-block';
        wrapper.dataset.almacen = almacenNombre;
        wrapper.style.marginBottom = '24px';

        const msNombre = nombresMeses[ms.mes].toUpperCase();
        const mdNombre = nombresMeses[md.mes].toUpperCase();

        wrapper.innerHTML = `
            <div class="almacen-header" style="display:flex; align-items:center; gap:10px; padding:10px 14px; margin-bottom:8px;
                 background:var(--md-sys-color-secondary-container); color:var(--md-sys-color-on-secondary-container); border-radius:12px;">
                <span class="material-symbols-outlined" style="font-size:22px;">warehouse</span>
                <strong style="font-size:1rem;">Almacén: ${almacenNombre || '(sin nombre)'}</strong>
                <span style="margin-left:auto; font-size:0.85rem; opacity:.85;">${lecherias.length} lechería${lecherias.length===1?'':'s'}</span>
            </div>
            <div class="reporte-wrapper">
                <table class="reporte-table">${thead(msNombre, mdNombre, md.anio)}<tbody></tbody></table>
            </div>
        `;

        const tbody = wrapper.querySelector('tbody');
        let faltantes = 0;

        lecherias.forEach(lech => {
            const fila = crearFila(lech);
            tbody.appendChild(fila);

            // Demográficos
            const hogares = parseInt(lech.TOTAL_HOGARES)    || 0;
            const menores = parseInt(lech.TOTAL_INFANTILES) || 0;
            const mayores = parseInt(lech.TOTAL_RESTO)      || 0;
            const totalBen = menores + mayores;

            fila.querySelector('input[name="familias[]"]').value      = hogares;
            fila.querySelector('input[name="beneficiarios[]"]').value = totalBen;

            const litrosTeoricos = (totalBen * 8) / 36 * 72;
            const dotTeorica = Math.floor(litrosTeoricos / L_X_CAJA);
            fila.querySelector('input[name="dotacion_teorica[]"]').value = dotTeorica;

            if (lech.encontrado) {
                const litrosIni = parseFloat(lech.inventario_inicial) || 0;
                fila.querySelector('input[name="inv_inicial[]"]').value = Math.floor(litrosIni / L_X_CAJA);
                fila.querySelector('input[name="surtimiento[]"]').value = parseFloat(lech.surtimiento) || 0;

                const litrosVenta = parseFloat(lech.venta_real) || 0;
                fila.querySelector('input[name="ventas[]"]').value = Math.floor(litrosVenta / L_X_CAJA);

                fila.querySelector('input[name="req_ms_anterior[]"]').value = dotTeorica;
                fila.querySelector('input[name="vms[]"]').value             = dotTeorica;
                fila.querySelector('input[name="req_actual[]"]').value      = dotTeorica;
            } else {
                ['inv_inicial','surtimiento','ventas','req_ms_anterior','vms','req_actual'].forEach(n => {
                    const i = fila.querySelector(`input[name="${n}[]"]`);
                    if (i) { i.style.borderColor = 'var(--md-sys-color-error)'; i.placeholder = 'FALTA'; }
                });
                faltantes++;
            }
            calcularFila(fila);
        });

        return { wrapper, faltantes };
    }

    // ──── Cargar lecherías y poblar tablas ───────────────────────
    function cargarLecherias() {
        const fechas = actualizarCabecerasGlobal();

        const mes  = selectMesReporte.value;
        const anio = inputAnioReporte.value;

        if (!mes || !anio || !fechas) {
            contenedorTablas.innerHTML = '<div style="text-align:center; padding:32px; color:var(--md-sys-color-on-surface-variant);">Selecciona el mes base para cargar tus lecherías agrupadas por almacén.</div>';
            return;
        }

        // No mandamos almacen → todas las lecherías del promotor
        const url = `obtenerLecheriasRequerimiento.php?mes_reporte=${mes}&anio_reporte=${anio}`;
        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (data.error) return notificar(data.mensaje, 'error');
                if (!Array.isArray(data) || data.length === 0) {
                    contenedorTablas.innerHTML = '<div style="text-align:center; padding:32px; color:var(--md-sys-color-error);">No se encontraron lecherías asignadas.</div>';
                    return;
                }

                // Agrupar por ALMACEN_RURAL
                const grupos = {};
                data.forEach(l => {
                    const k = (l.ALMACEN_RURAL || '(sin almacén)').trim();
                    if (!grupos[k]) grupos[k] = [];
                    grupos[k].push(l);
                });

                contenedorTablas.innerHTML = '';
                let totalFaltantes = 0;
                let totalLecherias = 0;
                const nombresAlmacenes = Object.keys(grupos).sort();

                nombresAlmacenes.forEach(nombre => {
                    const { wrapper, faltantes } = crearTablaAlmacen(nombre, grupos[nombre], fechas.ms, fechas.md);
                    contenedorTablas.appendChild(wrapper);
                    totalFaltantes += faltantes;
                    totalLecherias += grupos[nombre].length;
                });

                if (totalFaltantes > 0) {
                    notificar(`Se cargaron ${totalLecherias} lecherías en ${nombresAlmacenes.length} almacén(es). Faltan ${totalFaltantes} inventarios anteriores (en rojo).`, 'error');
                } else {
                    notificar(`Se cargaron ${totalLecherias} lecherías en ${nombresAlmacenes.length} almacén(es).`, 'info');
                }
            })
            .catch(() => notificar('Error al conectar con el servidor.', 'error'));
    }

    contenedorTablas.addEventListener('input', (e) => {
        if (e.target.tagName === 'INPUT' && e.target.type === 'number') {
            const fila = e.target.closest('tr');
            if (!fila) return;
            e.target.style.borderColor = '';
            calcularFila(fila);
        }
    });

    selectMesReporte.addEventListener('change', cargarLecherias);
    inputAnioReporte.addEventListener('change', cargarLecherias);

    // ──── Botón Guardar (con opción PDF) ─────────────────────────
    btnGuardar.addEventListener('click', async () => {
        if (!selectMesReporte.value) {
            notificar('Selecciona el mes base antes de guardar.', 'error');
            return;
        }
        const bloques = contenedorTablas.querySelectorAll('.almacen-block');
        if (bloques.length === 0) {
            notificar('No hay lecherías cargadas.', 'error');
            return;
        }

        const generarPDF = chkGenerarPDF && chkGenerarPDF.checked;
        if (generarPDF) {
            const ok = await confirmar('Vamos a guardar el requerimiento y abrir el PDF al terminar. ¿Continuamos?', 'Generar PDF');
            if (!ok) return;
        }

        const datos = construirPayload();

        try {
            btnGuardar.disabled = true;

            const resG = await fetch('guardarRequerimiento.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datos)
            });
            const jsG = await resG.json().catch(() => ({}));
            if (!resG.ok || jsG.status !== 'success') {
                throw new Error(jsG.mensaje || 'No se pudo guardar el requerimiento');
            }
            notificar('Requerimiento guardado correctamente.', 'info');

            if (generarPDF) {
                const resP = await fetch('generar_pdf_requerimiento.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(datos)
                });
                if (!resP.ok) throw new Error('No se pudo generar el PDF');
                const blob = await resP.blob();
                const url  = window.URL.createObjectURL(blob);
                window.open(url, '_blank');
                setTimeout(() => window.URL.revokeObjectURL(url), 1500);
                notificar('PDF generado. Se abrió en otra pestaña.', 'info');
            }
        } catch (e) {
            console.error(e);
            notificar(e.message || 'Error al guardar.', 'error');
        } finally {
            btnGuardar.disabled = false;
        }
    });

    // ──── Construir payload (multi-almacén) ──────────────────────
    function construirPayload() {
        const mes  = parseInt(selectMesReporte.value);
        const anio = parseInt(inputAnioReporte.value);
        const ms = sumarMeses(mes, anio, 1);
        const md = sumarMeses(mes, anio, 2);

        const bloques = contenedorTablas.querySelectorAll('.almacen-block');
        const almacenes = [];
        bloques.forEach(b => {
            const filas = b.querySelectorAll('tbody tr[data-lecher]');
            const lecherias = [];
            filas.forEach(f => {
                const v = (n) => f.querySelector(`input[name="${n}[]"]`)?.value ?? '';
                lecherias.push({
                    punto_venta:      v('punto_venta'),
                    clave_tienda:     v('clave_tienda'),
                    tipo_venta:       v('tipo_venta'),
                    precio:           v('precio'),
                    familias:         v('familias'),
                    beneficiarios:    v('beneficiarios'),
                    dotacion_teorica: v('dotacion_teorica'),
                    inv_inicial:      v('inv_inicial'),
                    surtimiento:      v('surtimiento'),
                    ventas:           v('ventas'),
                    inv_final:        v('inv_final'),
                    req_ms_anterior:  v('req_ms_anterior'),
                    vms:              v('vms'),
                    req_actual:       v('req_actual'),
                    observaciones:    v('observaciones')
                });
            });
            almacenes.push({ almacen: b.dataset.almacen, lecherias });
        });

        return {
            mes_base:     mes,
            anio_base:    anio,
            mes_ms:       ms.mes,
            anio_ms:      ms.anio,
            mes_destino:  md.mes,
            anio_destino: md.anio,
            mes_destino_nombre: nombresMeses[md.mes],
            mes_ms_nombre:      nombresMeses[ms.mes],
            supervisor:   document.getElementById('supervisor')?.value || '',
            promotor:     document.querySelector('.firma-name')?.textContent?.trim() || '',
            almacenes
        };
    }
});
