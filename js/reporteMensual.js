document.addEventListener('DOMContentLoaded', () => {
    const selectAlmacen = document.getElementById('selectAlmacen');
    const selectTipoVenta = document.getElementById('selectTipoVenta');
    const selectMesReporte = document.getElementById('selectMesReporte');
    const inputAnioReporte = document.getElementById('inputAnioReporte');
    const precioDisplay = document.getElementById('precioDisplay');
    const filasTabla = document.querySelectorAll('#tablaBody tr');
    
    const cardEstadoInventarios = document.getElementById('cardEstadoInventarios');
    const listaEstadoInventarios = document.getElementById('listaEstadoInventarios');

    const L_X_CAJA = 72;
    const L_X_SOBRE = 2;
    const nombresMeses = ["", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

    function formatearA_CajasYSobres(litrosTotales) {
        if (isNaN(litrosTotales) || litrosTotales < 0) return { cajas: 0, sobres: 0 };
        const cajas = Math.floor(litrosTotales / L_X_CAJA);
        const sobres = Math.floor((litrosTotales % L_X_CAJA) / L_X_SOBRE);
        return { cajas, sobres };
    }

    function calcularFila(fila) {
        const invIniCajas = parseFloat(fila.querySelector('input[name="inv_ini_cajas[]"]').value) || 0;
        const invIniSobres = parseFloat(fila.querySelector('input[name="inv_ini_sobres[]"]').value) || 0;
        const dotRecibCajas = parseFloat(fila.querySelector('input[name="dot_recibida_cajas[]"]').value) || 0;
        
        const dotVendCajas = parseFloat(fila.querySelector('input[name="dot_vend_cajas[]"]').value) || 0;
        const dotVendSobres = parseFloat(fila.querySelector('input[name="dot_vend_sobres[]"]').value) || 0;

        const litrosIni = (invIniCajas * L_X_CAJA) + (invIniSobres * L_X_SOBRE);
        const litrosDotRecibida = (dotRecibCajas * L_X_CAJA);
        
        const totalLitros = litrosIni + litrosDotRecibida;
        const fmtTotal = formatearA_CajasYSobres(totalLitros);
        
        fila.querySelector('input[name="total_cajas[]"]').value = fmtTotal.cajas;
        fila.querySelector('input[name="total_sobres[]"]').value = fmtTotal.sobres;

        const litrosVendidos = (dotVendCajas * L_X_CAJA) + (dotVendSobres * L_X_SOBRE);
        const litrosFinales = totalLitros - litrosVendidos;

        if (litrosFinales >= 0) {
            const fmtFin = formatearA_CajasYSobres(litrosFinales);
            fila.querySelector('input[name="inv_fin_cajas[]"]').value = fmtFin.cajas;
            fila.querySelector('input[name="inv_fin_sobres[]"]').value = fmtFin.sobres;
        } else {
            fila.querySelector('input[name="inv_fin_cajas[]"]').value = 0;
            fila.querySelector('input[name="inv_fin_sobres[]"]').value = 0;
        }
    }

    document.getElementById('tablaBody').addEventListener('input', (e) => {
        if (e.target.tagName === 'INPUT' && e.target.type === 'number') {
            const fila = e.target.closest('tr');
            if (fila && !fila.querySelector('input[name="punto_venta[]"]').disabled) {
                e.target.style.borderColor = ""; 
                calcularFila(fila);
            }
        }
    });

    function mostrarNotificacion(mensaje, tipo = 'info') {
        let contenedor = document.getElementById('toast-container-md3');
        if (!contenedor) {
            contenedor = document.createElement('div');
            contenedor.id = 'toast-container-md3';
            contenedor.style.position = 'fixed';
            contenedor.style.top = '24px';
            contenedor.style.left = '50%';
            contenedor.style.transform = 'translateX(-50%)';
            contenedor.style.display = 'flex';
            contenedor.style.flexDirection = 'column';
            contenedor.style.gap = '10px';
            contenedor.style.zIndex = '99999';
            contenedor.style.pointerEvents = 'none';
            document.body.appendChild(contenedor);
        }

        const toast = document.createElement('div');
        const isError = tipo === 'error';
        const bgColor = isError ? 'var(--md-sys-color-error-container)' : 'var(--md-sys-color-surface-container-highest)';
        const textColor = isError ? 'var(--md-sys-color-on-error-container)' : 'var(--md-sys-color-on-surface)';
        const iconColor = isError ? 'var(--md-sys-color-error)' : 'var(--md-sys-color-primary)';
        const iconName = isError ? 'error' : 'check_circle';

        toast.style.backgroundColor = bgColor;
        toast.style.color = textColor;
        toast.style.padding = '12px 20px';
        toast.style.borderRadius = '8px';
        toast.style.boxShadow = '0px 4px 12px rgba(0, 0, 0, 0.3)';
        toast.style.display = 'flex';
        toast.style.alignItems = 'center';
        toast.style.gap = '12px';
        toast.style.minWidth = '300px';
        toast.style.maxWidth = '90vw';
        toast.style.pointerEvents = 'auto';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-20px)';
        toast.style.transition = 'all 0.3s cubic-bezier(0.2, 0, 0, 1)';
        
        toast.innerHTML = `
            <span class="material-symbols-outlined" style="color: ${iconColor}; font-size: 24px;">${iconName}</span>
            <span style="flex-grow: 1; font-size: 0.9rem; font-weight: 500;">${mensaje}</span>
            <span class="material-symbols-outlined btn-cerrar" style="cursor: pointer; font-size: 20px; opacity: 0.7;">close</span>
        `;

        contenedor.appendChild(toast);
        requestAnimationFrame(() => { toast.style.opacity = '1'; toast.style.transform = 'translateY(0)'; });

        const cerrarToast = () => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-20px)';
            setTimeout(() => toast.remove(), 300);
        };

        const timeout = setTimeout(cerrarToast, 6000);
        toast.querySelector('.btn-cerrar').addEventListener('click', () => {
            clearTimeout(timeout);
            cerrarToast();
        });
    }

    fetch('obtenerAlmacenes.php')
        .then(response => response.json())
        .then(data => {
            selectAlmacen.innerHTML = '<md-select-option value="">-- Selecciona tu almacén --</md-select-option>';
            if (data.error) return;
            data.forEach(item => {
                const option = document.createElement('md-select-option');
                option.value = item.ALMACEN_RURAL;
                option.textContent = item.ALMACEN_RURAL;
                selectAlmacen.appendChild(option);
            });
        });

    function cargarLecherias() {
        const almacenEscogido = selectAlmacen ? selectAlmacen.value : '';
        const tipoVenta = selectTipoVenta ? selectTipoVenta.value : '0'; 
        const mesReporte = selectMesReporte ? selectMesReporte.value : '';
        const anioReporte = inputAnioReporte ? inputAnioReporte.value : '';
        
        if (precioDisplay) {
            precioDisplay.textContent = tipoVenta === '0' ? '$4.50/LITRO' : '$6.50/LITRO';
        }

        if(almacenEscogido === "" || mesReporte === "" || anioReporte === "") {
            filasTabla.forEach(fila => {
                fila.querySelectorAll('input, select').forEach(input => {
                    input.value = ''; input.disabled = true; input.style.borderColor = ""; input.placeholder = "";
                });
            });
            cardEstadoInventarios.style.display = 'none';
            return;
        }

        const url = `obtenerLecheriasPorAlmacen.php?almacen=${encodeURIComponent(almacenEscogido)}&tipo_venta=${tipoVenta}&mes_reporte=${mesReporte}&anio_reporte=${anioReporte}`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.error) return mostrarNotificacion(data.mensaje, 'error');
                
                if (data.length === 0) {
                    mostrarNotificacion(`No hay lecherías de ese precio en este almacén.`, 'error');
                    cardEstadoInventarios.style.display = 'none';
                    return;
                }

                let htmlTarjetas = '';
                let cantidadDesfasadas = 0;

                for(let i = 0; i < 17; i++) {
                    const fila = filasTabla[i];
                    const inputs = fila.querySelectorAll('input, select');
                    inputs.forEach(input => { input.value = ''; input.disabled = true; input.style.borderColor = ""; input.placeholder = "";});

                    if (data[i]) {
                        const inputPunto = fila.querySelector('input[name="punto_venta[]"]');
                        const inputClave = fila.querySelector('input[name="clave_tienda[]"]');
                        const inputCajas = fila.querySelector('input[name="inv_ini_cajas[]"]');
                        const inputSobres = fila.querySelector('input[name="inv_ini_sobres[]"]');
                        const inputDotCajas = fila.querySelector('input[name="dot_recibida_cajas[]"]');
                        
                        const inputVendCajas = fila.querySelector('input[name="dot_vend_cajas[]"]');
                        const inputVendSobres = fila.querySelector('input[name="dot_vend_sobres[]"]');

                        // NUEVOS INPUTS PARA RETIRO DE VENTAS
                        const inputRetiroCajas = fila.querySelector('input[name="retiro_cajas[]"]');
                        const inputRetiroSobres = fila.querySelector('input[name="retiro_sobres[]"]');

                        inputPunto.value = data[i].LECHER || '';
                        inputClave.value = data[i].NUM_TIENDA || '';
                        
                        inputs.forEach(input => { input.disabled = false; });
                        inputPunto.readOnly = true; inputClave.readOnly = true;
                        fila.querySelector('input[name="total_cajas[]"]').readOnly = true;
                        fila.querySelector('input[name="total_sobres[]"]').readOnly = true;

                        if (data[i].encontrado) {
                            // 1. INVENTARIO INICIAL
                            const litrosBDD = parseFloat(data[i].inventario_inicial) || 0; 
                            const fmtInv = formatearA_CajasYSobres(litrosBDD);
                            inputCajas.value = fmtInv.cajas;
                            inputSobres.value = fmtInv.sobres;

                            // 2. SURTIMIENTO (Viene directo en cajas)
                            inputDotCajas.value = parseFloat(data[i].surtimiento) || 0;
                            
                            // 3. VENTA REAL (Viene en litros, convertimos a cajas y sobres)
                            const litrosVenta = parseFloat(data[i].venta_real) || 0;
                            const fmtVenta = formatearA_CajasYSobres(litrosVenta);
                            inputVendCajas.value = fmtVenta.cajas;
                            inputVendSobres.value = fmtVenta.sobres;

                            // 4. SEGÚN REG. DE RETIRO DE VENTAS (Viene en litros, convertimos a cajas y sobres)
                            const litrosRetiro = parseFloat(data[i].venta_libro_retiro) || 0;
                            const fmtRetiro = formatearA_CajasYSobres(litrosRetiro);
                            inputRetiroCajas.value = fmtRetiro.cajas;
                            inputRetiroSobres.value = fmtRetiro.sobres;
                            
                            htmlTarjetas += `
                                <div style="padding: 10px; border-left: 4px solid var(--md-sys-color-primary); background: var(--md-sys-color-surface-container-highest); border-radius: 6px;">
                                    <strong style="color: var(--md-sys-color-on-surface); font-size: 0.95rem;">${data[i].LECHER}</strong><br>
                                    <span style="font-size: 0.8rem; color: var(--md-sys-color-on-surface-variant);">Inv: ${nombresMeses[data[i].mes_anterior]} ${data[i].anio_anterior}</span>
                                </div>
                            `;
                        } else {
                            inputCajas.style.borderColor = "var(--md-sys-color-error)";
                            inputSobres.style.borderColor = "var(--md-sys-color-error)";
                            inputDotCajas.style.borderColor = "var(--md-sys-color-error)";
                            inputVendCajas.style.borderColor = "var(--md-sys-color-error)";
                            inputVendSobres.style.borderColor = "var(--md-sys-color-error)";
                            inputRetiroCajas.style.borderColor = "var(--md-sys-color-error)";
                            inputRetiroSobres.style.borderColor = "var(--md-sys-color-error)";
                            
                            inputCajas.placeholder = "FALTA";
                            inputSobres.placeholder = "FALTA";
                            inputDotCajas.placeholder = "FALTA";
                            inputVendCajas.placeholder = "FALTA";
                            inputVendSobres.placeholder = "FALTA";
                            inputRetiroCajas.placeholder = "FALTA";
                            inputRetiroSobres.placeholder = "FALTA";
                            
                            cantidadDesfasadas++;

                            htmlTarjetas += `
                                <div style="padding: 10px; border-left: 4px solid var(--md-sys-color-error); background: var(--md-sys-color-error-container); border-radius: 6px;">
                                    <strong style="color: var(--md-sys-color-on-error-container); font-size: 0.95rem;">${data[i].LECHER}</strong><br>
                                    <span style="font-size: 0.8rem; color: var(--md-sys-color-error); font-weight: 500;">Falta ${nombresMeses[data[i].mes_anterior]} ${data[i].anio_anterior}</span>
                                </div>
                            `;
                        }
                        
                        calcularFila(fila);
                    }
                }

                listaEstadoInventarios.innerHTML = htmlTarjetas;
                cardEstadoInventarios.style.display = 'block';

                if (cantidadDesfasadas > 0) {
                    mostrarNotificacion(`Faltan los inventarios anteriores de ${cantidadDesfasadas} lecherías. Están marcadas en rojo.`, 'error');
                } else {
                    mostrarNotificacion(`Se cargaron ${data.length} lecherías listas para trabajar.`, 'info');
                }
            })
            .catch(error => mostrarNotificacion('Error conectando al servidor.', 'error'));
    }

    if (selectAlmacen) selectAlmacen.addEventListener('change', cargarLecherias);
    if (selectTipoVenta) selectTipoVenta.addEventListener('change', cargarLecherias);
    if (selectMesReporte) selectMesReporte.addEventListener('change', cargarLecherias);
    if (inputAnioReporte) inputAnioReporte.addEventListener('change', cargarLecherias);
});