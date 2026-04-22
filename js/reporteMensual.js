document.addEventListener('DOMContentLoaded', () => {
    const selectAlmacen = document.getElementById('selectAlmacen');
    const filasTabla = document.querySelectorAll('#tablaBody tr');

    const L_X_CAJA = 72;
    const L_X_SOBRE = 2;
    // --- FUNCIONES MATEMÁTICAS ---
    function formatearA_CajasYSobres(litrosTotales) {
        if (isNaN(litrosTotales) || litrosTotales < 0) return { cajas: 0, sobres: 0 };
        const cajas = Math.floor(litrosTotales / L_X_CAJA);
        const sobres = Math.floor((litrosTotales % L_X_CAJA) / L_X_SOBRE);
        return { cajas, sobres };
    }
    // Calcula los totales y el inventario final de una fila específica
    function calcularFila(fila) {
        // Entradas
        const invIniCajas = parseFloat(fila.querySelector('input[name="inv_ini_cajas[]"]').value) || 0;
        const invIniSobres = parseFloat(fila.querySelector('input[name="inv_ini_sobres[]"]').value) || 0;
        const dotRecibCajas = parseFloat(fila.querySelector('input[name="dot_recibida_cajas[]"]').value) || 0;
        
        const dotVendCajas = parseFloat(fila.querySelector('input[name="dot_vend_cajas[]"]').value) || 0;
        const dotVendSobres = parseFloat(fila.querySelector('input[name="dot_vend_sobres[]"]').value) || 0;

        // 1. Convertir todo a litros para hacer la suma exacta
        const litrosIni = (invIniCajas * L_X_CAJA) + (invIniSobres * L_X_SOBRE);
        const litrosDotRecibida = (dotRecibCajas * L_X_CAJA);
        
        // TOTAL (Inventario Inicial + Dotación Recibida)
        const totalLitros = litrosIni + litrosDotRecibida;
        const fmtTotal = formatearA_CajasYSobres(totalLitros);
        
        fila.querySelector('input[name="total_cajas[]"]').value = fmtTotal.cajas;
        fila.querySelector('input[name="total_sobres[]"]').value = fmtTotal.sobres;

        // 2. Calcular el Inventario Final (Total - Dotación Vendida)
        const litrosVendidos = (dotVendCajas * L_X_CAJA) + (dotVendSobres * L_X_SOBRE);
        const litrosFinales = totalLitros - litrosVendidos;

        if (litrosFinales >= 0) {
            const fmtFin = formatearA_CajasYSobres(litrosFinales);
            fila.querySelector('input[name="inv_fin_cajas[]"]').value = fmtFin.cajas;
            fila.querySelector('input[name="inv_fin_sobres[]"]').value = fmtFin.sobres;
        } else {
            // Si venden más de lo que tienen, no permitimos negativos en el visual
            fila.querySelector('input[name="inv_fin_cajas[]"]').value = 0;
            fila.querySelector('input[name="inv_fin_sobres[]"]').value = 0;
        }
    }

    // Escuchar cualquier cambio en los inputs numéricos de la tabla para recalcular
    document.getElementById('tablaBody').addEventListener('input', (e) => {
        if (e.target.tagName === 'INPUT' && e.target.type === 'number') {
            const fila = e.target.closest('tr');
            if (fila && !fila.querySelector('input[name="punto_venta[]"]').disabled) {
                calcularFila(fila);
            }
        }
    });

    // --- FUNCIONES PARA NOTIFICACIONES ---
    function mostrarNotificacion(mensaje, tipo = 'info') {
        let contenedor = document.getElementById('toast-container');
        if (!contenedor) {
            contenedor = document.createElement('div');
            contenedor.id = 'toast-container';
            document.body.appendChild(contenedor);
        }

        const toast = document.createElement('div');
        toast.className = 'notificacion-glass';
        toast.innerHTML = `
            <span style="flex-grow: 1; padding-right: 15px; line-height: 1.4;">
                ${tipo === 'error' ? '⚠️' : '✅'} ${mensaje}
            </span>
        `;
        contenedor.appendChild(toast);
        
        setTimeout(() => toast.classList.add('mostrar'), 10);
        setTimeout(() => {
            toast.style.transform = 'translateX(120%)';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 400);
        }, 4000);
    }

    // --- INICIALIZACIÓN Y FETCH DE DATOS ---
    
    // 1. Cargar los almacenes al iniciar
    fetch('obtenerAlmacenes.php')
        .then(response => response.json())
        .then(data => {
            selectAlmacen.innerHTML = '<md-select-option value="">-- Selecciona tu almacén --</md-select-option>';
            if (data.error) return mostrarNotificacion('Error: ' + data.mensaje, 'error');
            
            data.forEach(item => {
                const option = document.createElement('md-select-option');
                option.value = item.ALMACEN_RURAL;
                option.textContent = item.ALMACEN_RURAL;
                selectAlmacen.appendChild(option);
            });
        })
        .catch(error => console.error('Error cargando almacenes:', error));

    // 2. Cargar lecherías al seleccionar un almacén
    selectAlmacen.addEventListener('change', () => {
        const almacenEscogido = selectAlmacen.value;

        // Bloquear y limpiar tabla si elige la opción vacía
        if(almacenEscogido === "") {
            filasTabla.forEach(fila => {
                fila.querySelectorAll('input, select').forEach(input => {
                    input.value = '';
                    input.disabled = true;
                });
            });
            return;
        }

        fetch(`obtenerLecheriasPorAlmacen.php?almacen=${encodeURIComponent(almacenEscogido)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) return mostrarNotificacion(data.mensaje, 'error');
                mostrarNotificacion(`Cargando ${data.length} lecherías...`, 'info');

                // Llenamos las 17 filas
                for(let i = 0; i < 17; i++) {
                    const fila = filasTabla[i];
                    const inputs = fila.querySelectorAll('input, select');

                    // Limpiar y bloquear fila
                    inputs.forEach(input => { input.value = ''; input.disabled = true; });

                    if (data[i]) {
                        const inputPunto = fila.querySelector('input[name="punto_venta[]"]');
                        const inputClave = fila.querySelector('input[name="clave_tienda[]"]');

                        inputPunto.value = data[i].LECHER || '';
                        inputClave.value = data[i].NUM_TIENDA || '';
                        
                        // Desbloquear para capturar
                        inputs.forEach(input => { input.disabled = false; });
                        inputPunto.readOnly = true;
                        inputClave.readOnly = true;
                        fila.querySelector('input[name="total_cajas[]"]').readOnly = true;
                        fila.querySelector('input[name="total_sobres[]"]').readOnly = true;

                        // => 3. Buscar automáticamente el inventario anterior para esta lechería
                        cargarInventarioMesAnterior(data[i].LECHER, fila);
                    }
                }
            })
            .catch(error => mostrarNotificacion('Error cargando la base de datos.', 'error'));
    });

    // Función para buscar el inventario de la BDD
    async function cargarInventarioMesAnterior(lecher, fila) {
        try {
            const response = await fetch(`obtenerInventarioAnterior.php?lecher=${lecher}`);
            const data = await response.json();

            if (!data.error && data.encontrado) {
                // Suponiendo que la BD te devuelve LITROS totales (Ajusta si te devuelve sobres)
                // Si te devuelve SOBRES, multiplícalo por 2: (parseFloat(data.inventario_inicial) * L_X_SOBRE)
                const litrosBDD = parseFloat(data.inventario_inicial) || 0; 
                
                const fmt = formatearA_CajasYSobres(litrosBDD);
                
                fila.querySelector('input[name="inv_ini_cajas[]"]').value = fmt.cajas;
                fila.querySelector('input[name="inv_ini_sobres[]"]').value = fmt.sobres;
                
                // Forzamos el cálculo de la fila para que se actualice el "Total"
                calcularFila(fila);
            }
        } catch (error) {
            console.error(`Error buscando inventario anterior de ${lecher}`, error);
        }
    }
});