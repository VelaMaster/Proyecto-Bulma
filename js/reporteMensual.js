// ────────────────────────────────────────────────────────────────────
//  Reporte Mensual de Lecherías — Promotor
//  - El promotor solo elige MES; el sistema genera UNA TABLA POR ALMACÉN
//  - Cada fila lleva su columna PRECIO ($4.50 / $6.50)
//  - Periodo del 28 del mes anterior al 25 del mes seleccionado (editable)
//  - Auto-rellena con el inventario mensual del mes seleccionado
//  - Checkbox "Generar PDF al guardar" + confirmación
// ────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const selectMesReporte  = document.getElementById('selectMesReporte');
    const inputAnioReporte  = document.getElementById('inputAnioReporte');
    const periodoInicio     = document.getElementById('periodo_inicio');
    const periodoFin        = document.getElementById('periodo_fin');
    const contenedorTablas  = document.getElementById('contenedorTablas');
    const cardEstado        = document.getElementById('cardEstadoInventarios');
    const listaEstado       = document.getElementById('listaEstadoInventarios');
    const btnGuardar        = document.getElementById('btnGuardar');
    const chkGenerarPDF     = document.getElementById('chkGenerarPDF');

    const L_X_CAJA  = 72;
    const L_X_SOBRE = 2;
    const nombresMeses = ["", "Enero","Febrero","Marzo","Abril","Mayo","Junio",
                              "Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];

    // ──── Helpers ─────────────────────────────────────────────────
    function litrosACajasYSobres(litros) {
        if (isNaN(litros) || litros < 0) return { cajas: 0, sobres: 0 };
        return {
            cajas:  Math.floor(litros / L_X_CAJA),
            sobres: Math.floor((litros % L_X_CAJA) / L_X_SOBRE)
        };
    }
    function ultimoDiaDelMes(anio, mes) { return new Date(anio, mes, 0).getDate(); }
    function precioDeTipoVenta(tipo) { return parseInt(tipo) === 0 ? '$4.50' : '$6.50'; }

    function calcularPeriodo() {
        const mes  = parseInt(selectMesReporte.value);
        const anio = parseInt(inputAnioReporte.value);
        if (!mes || !anio) return;

        let mesIni  = mes - 1;
        let anioIni = anio;
        if (mesIni === 0) { mesIni = 12; anioIni--; }
        const diaIni = Math.min(28, ultimoDiaDelMes(anioIni, mesIni));
        periodoInicio.value = `${anioIni}-${String(mesIni).padStart(2,'0')}-${String(diaIni).padStart(2,'0')}`;

        const diaFin = Math.min(25, ultimoDiaDelMes(anio, mes));
        periodoFin.value = `${anio}-${String(mes).padStart(2,'0')}-${String(diaFin).padStart(2,'0')}`;
    }

    // ──── Notificación toast ─────────────────────────────────────
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

    // ──── Cálculo por fila ───────────────────────────────────────
    function calcularFila(fila) {
        const num = (sel) => parseFloat(fila.querySelector(sel)?.value) || 0;

        const litrosIni    = num('input[name="inv_ini_cajas[]"]') * L_X_CAJA + num('input[name="inv_ini_sobres[]"]') * L_X_SOBRE;
        const litrosDot    = num('input[name="dot_recibida_cajas[]"]') * L_X_CAJA;
        const totalLitros  = litrosIni + litrosDot;
        const fmtTotal     = litrosACajasYSobres(totalLitros);
        fila.querySelector('input[name="total_cajas[]"]').value  = fmtTotal.cajas;
        fila.querySelector('input[name="total_sobres[]"]').value = fmtTotal.sobres;

        const litrosVend   = num('input[name="dot_vend_cajas[]"]') * L_X_CAJA + num('input[name="dot_vend_sobres[]"]') * L_X_SOBRE;
        const litrosFin    = totalLitros - litrosVend;
        const fmtFin       = litrosACajasYSobres(litrosFin >= 0 ? litrosFin : 0);
        fila.querySelector('input[name="inv_fin_cajas[]"]').value  = fmtFin.cajas;
        fila.querySelector('input[name="inv_fin_sobres[]"]').value = fmtFin.sobres;
    }

    // ──── Construcción de la tabla por almacén ───────────────────
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
                    <th rowspan="2">No. DE DÍAS<br>DE VENTA</th>
                    <th rowspan="2" class="col-fecha">RECIBIDA<br>FECHA ENTRADA</th>
                    <th rowspan="2" class="col-fecha">CADUCIDAD<br>LECHE</th>
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

    function crearFila(lecheria, mesReporte, anioReporte) {
        const tr = document.createElement('tr');
        tr.dataset.lecher = lecheria.LECHER;
        tr.dataset.tipoVenta = lecheria.TIPO_PUNTO_VENTA;
        const precio = precioDeTipoVenta(lecheria.TIPO_PUNTO_VENTA);
        const mesFmt = String(mesReporte).padStart(2,'0');
        const fechaEntradaDef = `${anioReporte}-${mesFmt}-15`;
        const caducidadDef    = `${anioReporte}-12-31`;

        tr.innerHTML = `
            <td><input type="text"   class="cell-input" name="punto_venta[]"    value="${lecheria.LECHER}" readonly></td>
            <td><input type="text"   class="cell-input" name="clave_tienda[]"   value="${lecheria.NUM_TIENDA || ''}" readonly></td>
            <td><span class="precio-pill ${parseInt(lecheria.TIPO_PUNTO_VENTA)===0 ? 'precio-450' : 'precio-650'}">${precio}</span>
                <input type="hidden" name="precio[]"     value="${precio}">
                <input type="hidden" name="tipo_venta[]" value="${lecheria.TIPO_PUNTO_VENTA}"></td>

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
            <td><input type="number" class="cell-input" name="dias_venta[]"      value="24"></td>
            <td><input type="date"   class="cell-input" name="fecha_entrada[]"   value="${fechaEntradaDef}"></td>
            <td><input type="date"   class="cell-input" name="caducidad[]"       value="${caducidadDef}"></td>
            <td><input type="text"   class="cell-input" name="observaciones[]"   value=""></td>
        `;
        return tr;
    }

    function crearTablaAlmacen(almacenNombre, lecherias, mes, anio) {
        const wrapper = document.createElement('div');
        wrapper.className = 'almacen-block';
        wrapper.dataset.almacen = almacenNombre;
        wrapper.style.marginBottom = '24px';

        wrapper.innerHTML = `
            <div class="almacen-header" style="display:flex; align-items:center; gap:10px; padding:10px 14px; margin-bottom:8px;
                 background:var(--md-sys-color-secondary-container); color:var(--md-sys-color-on-secondary-container); border-radius:12px;">
                <span class="material-symbols-outlined" style="font-size:22px;">warehouse</span>
                <strong style="font-size:1rem;">Almacén: ${almacenNombre || '(sin nombre)'}</strong>
                <span style="margin-left:auto; font-size:0.85rem; opacity:.85;">${lecherias.length} lechería${lecherias.length===1?'':'s'}</span>
            </div>
            <div class="reporte-wrapper">
                <table class="reporte-table">${thead()}<tbody></tbody></table>
            </div>
        `;
        const tbody = wrapper.querySelector('tbody');
        let faltantes = 0;

        lecherias.forEach(lech => {
            const fila = crearFila(lech, mes, anio);
            tbody.appendChild(fila);

            if (lech.encontrado) {
                const fmtIni = litrosACajasYSobres(parseFloat(lech.inventario_inicial) || 0);
                fila.querySelector('input[name="inv_ini_cajas[]"]').value  = fmtIni.cajas;
                fila.querySelector('input[name="inv_ini_sobres[]"]').value = fmtIni.sobres;
                fila.querySelector('input[name="dot_recibida_cajas[]"]').value = parseFloat(lech.surtimiento) || 0;

                const fmtVenta = litrosACajasYSobres(parseFloat(lech.venta_real) || 0);
                fila.querySelector('input[name="dot_vend_cajas[]"]').value  = fmtVenta.cajas;
                fila.querySelector('input[name="dot_vend_sobres[]"]').value = fmtVenta.sobres;

                const fmtRet = litrosACajasYSobres(parseFloat(lech.venta_libro_retiro) || 0);
                fila.querySelector('input[name="retiro_cajas[]"]').value  = fmtRet.cajas;
                fila.querySelector('input[name="retiro_sobres[]"]').value = fmtRet.sobres;
            } else {
                ['inv_ini_cajas','inv_ini_sobres','dot_recibida_cajas','dot_vend_cajas','dot_vend_sobres','retiro_cajas','retiro_sobres'].forEach(n => {
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
        const mes  = selectMesReporte.value;
        const anio = inputAnioReporte.value;

        if (!mes || !anio) {
            contenedorTablas.innerHTML = '<div style="text-align:center; padding:32px; color:var(--md-sys-color-on-surface-variant);">Selecciona el mes para cargar tus lecherías agrupadas por almacén.</div>';
            if (cardEstado) cardEstado.style.display = 'none';
            return;
        }

        calcularPeriodo();

        // No mandamos almacen → todas las lecherías del promotor
        const url = `obtenerLecheriasPorAlmacen.php?mes_reporte=${mes}&anio_reporte=${anio}`;
        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (data.error) return notificar(data.mensaje, 'error');
                if (!Array.isArray(data) || data.length === 0) {
                    contenedorTablas.innerHTML = '<div style="text-align:center; padding:32px; color:var(--md-sys-color-error);">No se encontraron lecherías asignadas.</div>';
                    if (cardEstado) cardEstado.style.display = 'none';
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
                    const { wrapper, faltantes } = crearTablaAlmacen(nombre, grupos[nombre], mes, anio);
                    contenedorTablas.appendChild(wrapper);
                    totalFaltantes += faltantes;
                    totalLecherias += grupos[nombre].length;
                });

                if (totalFaltantes > 0) {
                    notificar(`Se cargaron ${totalLecherias} lecherías en ${nombresAlmacenes.length} almacén(es). Faltan ${totalFaltantes} inventarios anteriores (en rojo).`, 'error');
                } else {
                    notificar(`Se cargaron ${totalLecherias} lecherías en ${nombresAlmacenes.length} almacén(es).`, 'info');
                }
                if (cardEstado) cardEstado.style.display = 'none'; // ya no usamos esa card
            })
            .catch(() => notificar('Error al conectar con el servidor.', 'error'));
    }

    // Recalcular al cambiar inputs en cualquier tabla
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
            notificar('Selecciona el mes antes de guardar.', 'error');
            return;
        }
        const bloques = contenedorTablas.querySelectorAll('.almacen-block');
        if (bloques.length === 0) {
            notificar('No hay lecherías cargadas.', 'error');
            return;
        }

        const generarPDF = chkGenerarPDF && chkGenerarPDF.checked;
        if (generarPDF) {
            const ok = await confirmar('Vamos a guardar el reporte y abrir el PDF al terminar. ¿Continuamos?', 'Generar PDF');
            if (!ok) return;
        }

        const datos = construirPayload();

        try {
            btnGuardar.disabled = true;

            const resG = await fetch('guardarReporteMensual.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datos)
            });
            const jsG = await resG.json().catch(() => ({}));
            if (!resG.ok || jsG.status !== 'success') {
                throw new Error(jsG.mensaje || 'No se pudo guardar el reporte');
            }
            notificar('Reporte guardado correctamente.', 'info');

            if (generarPDF) {
                const resP = await fetch('generar_pdf_reporte.php', {
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
        const bloques = contenedorTablas.querySelectorAll('.almacen-block');
        const almacenes = [];
        bloques.forEach(b => {
            const filas = b.querySelectorAll('tbody tr[data-lecher]');
            const lecherias = [];
            filas.forEach(f => {
                const v = (n) => f.querySelector(`input[name="${n}[]"]`)?.value ?? '';
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
                    dias_venta:         v('dias_venta'),
                    fecha_entrada:      v('fecha_entrada'),
                    caducidad:          v('caducidad'),
                    observaciones:      v('observaciones')
                });
            });
            almacenes.push({ almacen: b.dataset.almacen, lecherias });
        });

        return {
            mes_reporte:    selectMesReporte.value,
            anio_reporte:   inputAnioReporte.value,
            periodo_inicio: periodoInicio.value,
            periodo_fin:    periodoFin.value,
            supervisor:     document.getElementById('supervisor')?.value || '',
            promotor:       document.querySelector('.firma-name')?.textContent?.trim() || '',
            almacenes
        };
    }
});
