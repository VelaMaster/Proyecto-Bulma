document.addEventListener('DOMContentLoaded', () => {
    const selectAlmacen = document.getElementById('selectAlmacen');
    const selectTipoVenta = document.getElementById('selectTipoVenta');
    const selectMesReporte = document.getElementById('selectMesReporte');
    const inputAnioReporte = document.getElementById('inputAnioReporte');
    const precioDisplay = document.getElementById('precioDisplay');
    const filasTabla = document.querySelectorAll('#tablaBody tr');
    
    const cardEstadoRequerimiento = document.getElementById('cardEstadoRequerimiento');
    const listaEstadoRequerimiento = document.getElementById('listaEstadoRequerimiento');

    const L_X_CAJA = 72;
    const nombresMeses = ["", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

    // --- FUNCIÓN PARA ACTUALIZAR LOS TÍTULOS DE LA TABLA SEGÚN EL MES ---
    function actualizarCabeceras() {
        const mesVal = parseInt(selectMesReporte.value);
        if (!isNaN(mesVal) && mesVal > 0) {
            const thReqMs = document.getElementById('th_req_ms');
            const thReqActual = document.getElementById('th_req_actual');
            
            const mesActualNombre = nombresMeses[mesVal].toUpperCase();
            let mesAnteriorVal = mesVal - 1;
            if (mesAnteriorVal === 0) mesAnteriorVal = 12;
            const mesAnteriorNombre = nombresMeses[mesAnteriorVal].toUpperCase();

            if (thReqMs) thReqMs.innerHTML = `REQ. M.S.<br>${mesAnteriorNombre}`;
            if (thReqActual) thReqActual.innerHTML = `REQ.<br>${mesActualNombre}`;
        }
    }

    // --- MATEMÁTICA AUTOMÁTICA DE LA FILA ---
    function calcularFila(fila) {
        const invIni = parseFloat(fila.querySelector('input[name="inv_inicial[]"]').value) || 0;
        const surt = parseFloat(fila.querySelector('input[name="surtimiento[]"]').value) || 0;
        const ventas = parseFloat(fila.querySelector('input[name="ventas[]"]').value) || 0;

        // Inventario Final = Inicial + Surtimiento - Ventas
        const invFinal = invIni + surt - ventas;

        const inputInvFinal = fila.querySelector('input[name="inv_final[]"]');
        if (inputInvFinal) {
            inputInvFinal.value = invFinal >= 0 ? invFinal : 0; // Evitamos números negativos
        }
    }

    // Escuchamos si el usuario escribe algo en la tabla para recalcular
    document.getElementById('tablaBody').addEventListener('input', (e) => {
        if (e.target.tagName === 'INPUT' && e.target.type === 'number') {
            const fila = e.target.closest('tr');
            if (fila && !fila.querySelector('input[name="punto_venta[]"]').disabled) {
                e.target.style.borderColor = ""; 
                calcularFila(fila);
            }
        }
    });

    // --- FUNCIONES PARA NOTIFICACIONES MD3 ---
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

    // --- INICIALIZACIÓN DE ALMACENES ---
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

    // --- FUNCIÓN CENTRAL PARA CARGAR DATOS ---
    function cargarLecherias() {
        const almacenEscogido = selectAlmacen ? selectAlmacen.value : '';
        const tipoVenta = selectTipoVenta ? selectTipoVenta.value : '0'; 
        const mesReporte = selectMesReporte ? selectMesReporte.value : '';
        const anioReporte = inputAnioReporte ? inputAnioReporte.value : '';
        
        if (precioDisplay) {
            precioDisplay.textContent = tipoVenta === '0' ? '$4.50/LITRO' : '$6.50/LITRO';
        }
        actualizarCabeceras();

        if(almacenEscogido === "" || mesReporte === "" || anioReporte === "") {
            filasTabla.forEach(fila => {
                fila.querySelectorAll('input, select').forEach(input => {
                    input.value = ''; input.disabled = true; input.style.borderColor = ""; input.placeholder = "";
                });
            });
            if (cardEstadoRequerimiento) cardEstadoRequerimiento.style.display = 'none';
            return;
        }

       const url = `obtenerLecheriasRequerimiento.php?almacen=${encodeURIComponent(almacenEscogido)}&tipo_venta=${tipoVenta}&mes_reporte=${mesReporte}&anio_reporte=${anioReporte}`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.error) return mostrarNotificacion(data.mensaje, 'error');
                
                if (data.length === 0) {
                    mostrarNotificacion(`No hay lecherías de ese precio en este almacén.`, 'error');
                    if (cardEstadoRequerimiento) cardEstadoRequerimiento.style.display = 'none';
                    return;
                }

                let htmlTarjetas = '';
                let cantidadDesfasadas = 0;

                for(let i = 0; i < 17; i++) {
                    const fila = filasTabla[i];
                    const inputs = fila.querySelectorAll('input');
                    inputs.forEach(input => { input.value = ''; input.disabled = true; input.style.borderColor = ""; input.placeholder = "";});

                    if (data[i]) {
                        const inputPunto = fila.querySelector('input[name="punto_venta[]"]');
                        const inputClave = fila.querySelector('input[name="clave_tienda[]"]');
                        
                        // ATRAPAMOS LOS NUEVOS INPUTS DE LA TABLA
                        const inputFamilias = fila.querySelector('input[name="familias[]"]');
                        const inputBeneficiarios = fila.querySelector('input[name="beneficiarios[]"]');
                        const inputDotacion = fila.querySelector('input[name="dotacion_teorica[]"]');
                        
                        const inputCajasIni = fila.querySelector('input[name="inv_inicial[]"]');
                        const inputSurt = fila.querySelector('input[name="surtimiento[]"]');
                        const inputVentas = fila.querySelector('input[name="ventas[]"]');
                        const inputInvFinal = fila.querySelector('input[name="inv_final[]"]');

                        inputs.forEach(input => { input.disabled = false; });
                        
                        inputPunto.value = data[i].LECHER || '';
                        inputClave.value = data[i].NUM_TIENDA || '';
                        inputPunto.readOnly = true; 
                        inputClave.readOnly = true;
                        inputInvFinal.readOnly = true; 

                        // --- AQUÍ INYECTAMOS LOS DATOS DEMOGRÁFICOS ---
                        // 1. Familias (Hogares)
                        const hogares = parseInt(data[i].TOTAL_HOGARES) || 0;
                        inputFamilias.value = hogares;
                        
                        // 2. Beneficiarios (Niños + Resto)
                        const menores = parseInt(data[i].TOTAL_INFANTILES) || 0;
                        const mayores = parseInt(data[i].TOTAL_RESTO) || 0;
                        const totalBen = menores + mayores;
                        inputBeneficiarios.value = totalBen;
                        
                        // 3. Dotación Teórica (Litros al mes convertidos a cajas enteras)
                        // Fórmula que me pasaste: ((totalBen * 8) / 36 * 72) <-- Estos son litros
                        // Lo dividimos entre 72 (L_X_CAJA) para que se muestre en CAJAS en el reporte.
                        const litrosTeoricos = (totalBen * 8) / 36 * 72;
                        inputDotacion.value = Math.floor(litrosTeoricos / L_X_CAJA);

                        // --- LÓGICA DEL INVENTARIO ANTERIOR ---
                        if (data[i].encontrado) {
                            const litrosIni = parseFloat(data[i].inventario_inicial) || 0; 
                            inputCajasIni.value = Math.floor(litrosIni / L_X_CAJA);

                            inputSurt.value = parseFloat(data[i].surtimiento) || 0;
                            
                            const litrosVenta = parseFloat(data[i].venta_real) || 0;
                            inputVentas.value = Math.floor(litrosVenta / L_X_CAJA);
                            
                            htmlTarjetas += `
                                <div style="padding: 10px; border-left: 4px solid var(--md-sys-color-primary); background: var(--md-sys-color-surface-container-highest); border-radius: 6px;">
                                    <strong style="color: var(--md-sys-color-on-surface); font-size: 0.95rem;">${data[i].LECHER}</strong><br>
                                    <span style="font-size: 0.8rem; color: var(--md-sys-color-on-surface-variant);">OK - Inv: ${nombresMeses[data[i].mes_anterior]} ${data[i].anio_anterior}</span>
                                </div>
                            `;
                        } else {
                            inputCajasIni.style.borderColor = "var(--md-sys-color-error)";
                            inputSurt.style.borderColor = "var(--md-sys-color-error)";
                            inputVentas.style.borderColor = "var(--md-sys-color-error)";
                            
                            inputCajasIni.placeholder = "FALTA";
                            inputSurt.placeholder = "FALTA";
                            inputVentas.placeholder = "FALTA";
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

                if (listaEstadoRequerimiento) listaEstadoRequerimiento.innerHTML = htmlTarjetas;
                if (cardEstadoRequerimiento) cardEstadoRequerimiento.style.display = 'block';

                if (cantidadDesfasadas > 0) {
                    mostrarNotificacion(`Faltan inventarios anteriores de ${cantidadDesfasadas} lecherías. Están marcadas en rojo.`, 'error');
                } else {
                    mostrarNotificacion(`Se cargaron ${data.length} lecherías.`, 'info');
                }
            })
            .catch(error => mostrarNotificacion('Error conectando al servidor.', 'error'));
    }

    if (selectAlmacen) selectAlmacen.addEventListener('change', cargarLecherias);
    if (selectTipoVenta) selectTipoVenta.addEventListener('change', cargarLecherias);
    if (selectMesReporte) selectMesReporte.addEventListener('change', cargarLecherias);
    if (inputAnioReporte) inputAnioReporte.addEventListener('change', cargarLecherias);
});