// ────────────────────────────────────────────────────────────────────
//  Requerimiento de Leche — Promotor
// ────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const selectMesReporte  = document.getElementById('selectMesReporte');
    const inputAnioReporte  = document.getElementById('inputAnioReporte');
    const inputMesDestino   = document.getElementById('inputMesDestino');
    const mesDestinoLabel   = document.getElementById('mesDestinoLabel');
    const contenedorTablas  = document.getElementById('contenedorTablas');
    const btnGuardar        = document.getElementById('btnGuardar');
    const chkGenerarPDF     = document.getElementById('chkGenerarPDF');
    const nombreSupervisor  = document.getElementById('nombreSupervisor');
    const inputSupervisor   = document.getElementById('supervisor');

    fetch('obtenerSupervisorAsignado.php')
        .then(r => r.json())
        .then(d => {
            if (d.status === 'success' && d.supervisor) {
                if (nombreSupervisor) {
                    nombreSupervisor.textContent = d.supervisor.nombre.toUpperCase();
                    nombreSupervisor.style.opacity = '1';
                }
                if (inputSupervisor) inputSupervisor.value = d.supervisor.nombre;
            } else {
                if (nombreSupervisor) {
                    nombreSupervisor.textContent = '— Sin supervisor asignado —';
                    nombreSupervisor.style.opacity = '.6';
                }
            }
        }).catch(() => {
            if (nombreSupervisor) nombreSupervisor.textContent = '— Sin supervisor asignado —';
        });

    const L_X_CAJA = 72;
    const L_X_SOBRE = 2;
    const nombresMeses = ["", "Enero","Febrero","Marzo","Abril","Mayo","Junio",
                              "Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];

    function precioDeTipoVenta(tipo) { return parseInt(tipo) === 0 ? '$4.50' : '$6.50'; }
    function claveTiendaMostrar(lech) { return parseInt(lech.TIPO_PUNTO_VENTA) === 2 ? 'DM' : (lech.NUM_TIENDA || ''); }
    
    function sumarMeses(mes, anio, n) {
        let m = mes + n; let a = anio;
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

    function notificar(msg, tipo = 'info') {
        let cont = document.getElementById('toast-container-md3');
        if (!cont) {
            cont = document.createElement('div');
            cont.id = 'toast-container-md3';
            Object.assign(cont.style, { position:'fixed', top:'24px', left:'50%', transform:'translateX(-50%)', display:'flex', flexDirection:'column', gap:'10px', zIndex:'99999', pointerEvents:'none' });
            document.body.appendChild(cont);
        }
        const isErr = tipo === 'error';
        const t = document.createElement('div');
        Object.assign(t.style, { backgroundColor: isErr ? 'var(--md-sys-color-error-container)' : 'var(--md-sys-color-surface-container-highest)', color: isErr ? 'var(--md-sys-color-on-error-container)' : 'var(--md-sys-color-on-surface)', padding:'12px 20px', borderRadius:'8px', boxShadow:'0px 4px 12px rgba(0,0,0,0.3)', display:'flex', alignItems:'center', gap:'12px', minWidth:'300px', maxWidth:'90vw', pointerEvents:'auto', opacity:'0', transform:'translateY(-20px)', transition:'all 0.3s cubic-bezier(0.2,0,0,1)' });
        t.innerHTML = `<span class="material-symbols-outlined" style="color:${isErr?'var(--md-sys-color-error)':'var(--md-sys-color-primary)'}; font-size:24px;">${isErr?'error':'check_circle'}</span><span style="flex-grow:1; font-size:0.9rem; font-weight:500;">${msg}</span><span class="material-symbols-outlined btn-cerrar" style="cursor:pointer; font-size:20px; opacity:0.7;">close</span>`;
        cont.appendChild(t);
        requestAnimationFrame(() => { t.style.opacity = '1'; t.style.transform = 'translateY(0)'; });
        const cerrar = () => { t.style.opacity = '0'; t.style.transform = 'translateY(-20px)'; setTimeout(()=>t.remove(), 300); };
        const to = setTimeout(cerrar, 6000);
        t.querySelector('.btn-cerrar').addEventListener('click', () => { clearTimeout(to); cerrar(); });
    }

    // --- FUNCIÓN PARA LEER EL FORMATO "CAJAS -- SOBRES" ---
    function parseCS(val) {
        if (!val) return { c: 0, s: 0 };
        const parts = String(val).split('--').map(s => parseInt(s.trim()) || 0);
        return { c: parts[0] || 0, s: parts[1] || 0 };
    }

    function calcularFila(fila) {
        const ini  = parseCS(fila.querySelector('input[name="inv_inicial[]"]')?.value);
        const ven  = parseCS(fila.querySelector('input[name="ventas[]"]')?.value);
        const surt = parseFloat(fila.querySelector('input[name="surtimiento[]"]')?.value) || 0;

        // Convertimos todo a litros para hacer la matemática perfecta
        const litrosIni  = (ini.c * L_X_CAJA) + (ini.s * L_X_SOBRE);
        const litrosSurt = surt * L_X_CAJA;
        const litrosVen  = (ven.c * L_X_CAJA) + (ven.s * L_X_SOBRE);

        let litrosFin = litrosIni + litrosSurt - litrosVen;
        if (litrosFin < 0) litrosFin = 0;

        // Regresamos a formato Cajas -- Sobres
        const finCajas = Math.floor(litrosFin / L_X_CAJA);
        const finSobres = Math.floor((litrosFin % L_X_CAJA) / L_X_SOBRE);

        const inputInvFinal = fila.querySelector('input[name="inv_final[]"]');
        if (inputInvFinal) inputInvFinal.value = `${finCajas} -- ${finSobres}`;
    }

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
            <td><input type="text"   class="cell-input" name="clave_tienda[]"     value="${claveTiendaMostrar(lech)}" readonly></td>
            <td><span class="precio-pill ${parseInt(lech.TIPO_PUNTO_VENTA)===0 ? 'precio-450' : 'precio-650'}">${precio}</span>
                <input type="hidden" name="precio[]"     value="${precio}">
                <input type="hidden" name="tipo_venta[]" value="${lech.TIPO_PUNTO_VENTA}"></td>
            <td><input type="number" class="cell-input" name="familias[]"          readonly></td>
            <td><input type="number" class="cell-input" name="beneficiarios[]"     readonly></td>
            <td><input type="number" class="cell-input" name="dotacion_teorica[]"  readonly></td>
            
            <td><input type="text"   class="cell-input" name="inv_inicial[]"       placeholder="0 -- 0"></td>
            <td><input type="number" class="cell-input" name="surtimiento[]"       value="0"></td>
            <td><input type="text"   class="cell-input" name="ventas[]"            placeholder="0 -- 0"></td>
            <td><input type="text"   class="cell-input" name="inv_final[]"         readonly placeholder="0 -- 0"></td>
            
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
                // Inyectamos con el formato visual que pidieron
                fila.querySelector('input[name="inv_inicial[]"]').value = `${lech.inv_ini_cajas} -- ${lech.inv_ini_sobres}`;
                fila.querySelector('input[name="surtimiento[]"]').value = lech.surt_cajas || 0;
                fila.querySelector('input[name="ventas[]"]').value      = `${lech.venta_cajas} -- ${lech.venta_sobres}`;
                fila.querySelector('input[name="inv_final[]"]').value   = `${lech.fin_cajas} -- ${lech.fin_sobres}`;

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
        });

        return { wrapper, faltantes };
    }

    function cargarLecherias() {
        const fechas = actualizarCabecerasGlobal();
        const mes  = selectMesReporte.value;
        const anio = inputAnioReporte.value;

        if (!mes || !anio || !fechas) {
            contenedorTablas.innerHTML = '<div style="text-align:center; padding:32px; color:var(--md-sys-color-on-surface-variant);">Selecciona el mes base para cargar tus lecherías agrupadas por almacén.</div>';
            return;
        }

        const url = `obtenerLecheriasRequerimiento.php?mes_reporte=${mes}&anio_reporte=${anio}`;
        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (data.error) return notificar(data.mensaje, 'error');
                if (!Array.isArray(data) || data.length === 0) {
                    contenedorTablas.innerHTML = '<div style="text-align:center; padding:32px; color:var(--md-sys-color-error);">No se encontraron lecherías asignadas.</div>';
                    return;
                }

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
                    notificar(`Se cargaron ${totalLecherias} lecherías. Faltan ${totalFaltantes} inventarios (en rojo).`, 'error');
                } else {
                    notificar(`Se cargaron ${totalLecherias} lecherías correctamente.`, 'info');
                }
            })
            .catch(() => notificar('Error al conectar con el servidor.', 'error'));
    }

    // Escuchamos 'input' tanto en campos numéricos como de texto para actualizar
    contenedorTablas.addEventListener('input', (e) => {
        if (e.target.tagName === 'INPUT' && (e.target.type === 'number' || e.target.type === 'text')) {
            const fila = e.target.closest('tr');
            if (!fila) return;
            e.target.style.borderColor = '';
            calcularFila(fila);
        }
    });

    selectMesReporte.addEventListener('change', cargarLecherias);
    inputAnioReporte.addEventListener('change', cargarLecherias);

    btnGuardar.addEventListener('click', async () => {
        // ... (Tu código de guardado original se mantiene igual aquí) ...
        // Dado que el payload original tomaba los input values, ahora tomará "4 -- 16".
        // Si tu guardarRequerimiento.php necesita separarlos, me avisas, pero por ahora se enviarán tal cual.
        notificar('Guardado no implementado en esta vista de prueba.', 'info'); 
    });
});