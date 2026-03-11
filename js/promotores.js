document.addEventListener('DOMContentLoaded', () => {
    // Inputs Tabla I (Inventario Inicial)
    const invCaja = document.getElementById('inv_ini_caja');
    const invSobres = document.getElementById('inv_ini_sobres');
    const invLitros = document.getElementById('inv_ini_litros');
    
    // Inputs Tabla I (Abasto Total)
    const abastoCaja = document.getElementById('abasto_caja');
    const abastoSobres = document.getElementById('abasto_sobres');
    const abastoLitros = document.getElementById('abasto_litros');

    // Inputs Tabla I (Venta Real)
    const ventaCaja = document.getElementById('venta_caja');
    const ventaSobres = document.getElementById('venta_sobres');
    const ventaLitros = document.getElementById('venta_litros');

    // Inputs Tabla I (Litros Registrados)
    const regCaja = document.getElementById('litros_reg_caja');
    const regSobres = document.getElementById('litros_reg_sobres');
    const regLitros = document.getElementById('litros_reg_litros');

    // Inputs Tabla I (Diferencia)
    const difCaja = document.getElementById('dif_caja');
    const difSobres = document.getElementById('dif_sobres');
    const difLitros = document.getElementById('dif_litros');

    // Inputs Tabla I (Inventario Final)
    const finCaja = document.getElementById('inv_fin_caja');
    const finSobres = document.getElementById('inv_fin_sobres');
    const finLitros = document.getElementById('inv_fin_litros');

    // Inputs Tabla II (Surtimientos)
    const surtCajas = document.getElementById('surt_cajas');
    const surtLitros = document.getElementById('surt_litros');

    const SOBRES_POR_CAJA = 36;
    const LITROS_POR_CAJA = 72;

    // --- FUNCIONES MATEMÁTICAS EN CASCADA ESTRICTAS ---

    // 1. Calcula la Diferencia (Fórmula Liconsa: (Inv Inicial + Surtimiento) - Inv Final)
    function calcularDiferencia() {
        const inicial = parseFloat(invCaja.value) || 0;
        const surtimiento = parseFloat(surtCajas.value) || 0;
        const final = parseFloat(finCaja.value) || 0;
        
        // Si no hay datos base, limpiamos
        if (invCaja.value === '' && surtCajas.value === '' && finCaja.value === '') {
            difCaja.value = ''; difSobres.value = ''; difLitros.value = '';
            return;
        }

        const diferencia = (inicial + surtimiento) - final;

        difCaja.value = diferencia;
        difSobres.value = diferencia * SOBRES_POR_CAJA;
        difLitros.value = diferencia * LITROS_POR_CAJA;
    }

    // 2. Calcula el Inventario Final (Fórmula Liconsa: Abasto - Venta Real)
    function calcularInventarioFinal(silencioso = true) {
        const abastoActual = parseFloat(abastoCaja.value) || 0;
        const ventaActual = parseFloat(ventaCaja.value) || 0;

        if (abastoCaja.value === '' && ventaCaja.value === '') {
            finCaja.value = ''; finSobres.value = ''; finLitros.value = '';
            calcularDiferencia(); // Limpia abajo también
            return;
        }

        const invFinal = abastoActual - ventaActual;

        if (invFinal < 0) {
            finCaja.value = 0; finSobres.value = 0; finLitros.value = 0;
            if (!silencioso) {
                mostrarNotificacion("La venta real no puede ser mayor al abasto total del mes.", "error");
            }
        } else {
            finCaja.value = invFinal;
            finSobres.value = invFinal * SOBRES_POR_CAJA;
            finLitros.value = invFinal * LITROS_POR_CAJA;
        }

        // Obligatorio: Después de calcular Final, calculamos Diferencia
        calcularDiferencia();
    }

    // 3. Calcula el Abasto Total (Fórmula Liconsa: Inicial + Surtimiento)
    function actualizarAbastoTotal() {
        const inicialCajas = parseFloat(invCaja.value) || 0;
        const surtimientoCajas = parseFloat(surtCajas.value) || 0;
        
        const totalCajas = inicialCajas + surtimientoCajas;

        if (totalCajas === 0 && invCaja.value === '' && surtCajas.value === '') {
            abastoCaja.value = ''; abastoSobres.value = ''; abastoLitros.value = '';
        } else {
            abastoCaja.value = totalCajas;
            abastoSobres.value = totalCajas * SOBRES_POR_CAJA;
            abastoLitros.value = totalCajas * LITROS_POR_CAJA;
        }

        // Si el abasto cambia, todo hacia abajo cambia
        calcularInventarioFinal(true);
    }


    // --- EVENTOS: VENTA REAL DEL MES ---
    ventaCaja.addEventListener('input', () => {
        if (ventaCaja.value === '') { 
            ventaSobres.value = ''; ventaLitros.value = ''; 
            calcularInventarioFinal(true); 
            return; 
        }
        const cajas = parseFloat(ventaCaja.value);
        if (cajas < 0) { ventaCaja.value = ''; return; }

        ventaSobres.value = cajas * SOBRES_POR_CAJA;
        ventaLitros.value = cajas * LITROS_POR_CAJA;
        
        calcularInventarioFinal(true); 
    });

    ventaCaja.addEventListener('blur', () => {
        calcularInventarioFinal(false); // Al salir, alerta si venta > abasto
    });

    ventaLitros.addEventListener('input', () => {
        if (ventaLitros.value === '') { 
            ventaCaja.value = ''; ventaSobres.value = ''; 
            calcularInventarioFinal(true); 
            return; 
        }
        const litros = parseFloat(ventaLitros.value);
        if (litros % LITROS_POR_CAJA === 0) {
            const cajas = litros / LITROS_POR_CAJA;
            ventaCaja.value = cajas;
            ventaSobres.value = cajas * SOBRES_POR_CAJA;
            calcularInventarioFinal(true);
        } else {
            ventaCaja.value = ''; ventaSobres.value = '';
        }
    });

    ventaLitros.addEventListener('blur', () => {
        if (ventaLitros.value.trim() === '') { calcularInventarioFinal(false); return; }
        let litros = parseFloat(ventaLitros.value);
        if (!isNaN(litros) && litros % LITROS_POR_CAJA !== 0) {
            let residuo = litros % LITROS_POR_CAJA;
            let litrosAjustados = (residuo < (LITROS_POR_CAJA / 2)) ? litros - residuo : litros + (LITROS_POR_CAJA - residuo);
            
            ventaLitros.value = litrosAjustados;
            ventaCaja.value = litrosAjustados / LITROS_POR_CAJA;
            ventaSobres.value = (litrosAjustados / LITROS_POR_CAJA) * SOBRES_POR_CAJA;
            mostrarNotificacion(`La venta real se ajustó a ${litrosAjustados}L para coincidir con cajas completas.`, 'error');
        }
        calcularInventarioFinal(false);
    });


    // --- EVENTOS: LITROS REGISTRADOS (Solo convierten, no afectan Final ni Diferencia según reglas) ---
    regCaja.addEventListener('input', () => {
        if (regCaja.value === '') { regSobres.value = ''; regLitros.value = ''; return; }
        const cajas = parseFloat(regCaja.value);
        if (cajas < 0) { regCaja.value = ''; return; }

        regSobres.value = cajas * SOBRES_POR_CAJA;
        regLitros.value = cajas * LITROS_POR_CAJA;
    });

    regLitros.addEventListener('input', () => {
        if (regLitros.value === '') { regCaja.value = ''; regSobres.value = ''; return; }
        const litros = parseFloat(regLitros.value);
        if (litros % LITROS_POR_CAJA === 0) {
            const cajas = litros / LITROS_POR_CAJA;
            regCaja.value = cajas;
            regSobres.value = cajas * SOBRES_POR_CAJA;
        } else {
            regCaja.value = ''; regSobres.value = '';
        }
    });

    regLitros.addEventListener('blur', () => {
        if (regLitros.value.trim() === '') return;
        let litros = parseFloat(regLitros.value);
        if (!isNaN(litros) && litros % LITROS_POR_CAJA !== 0) {
            let residuo = litros % LITROS_POR_CAJA;
            let litrosAjustados = (residuo < (LITROS_POR_CAJA / 2)) ? litros - residuo : litros + (LITROS_POR_CAJA - residuo);
            
            regLitros.value = litrosAjustados;
            regCaja.value = litrosAjustados / LITROS_POR_CAJA;
            regSobres.value = (litrosAjustados / LITROS_POR_CAJA) * SOBRES_POR_CAJA;
            mostrarNotificacion(`Los litros registrados se ajustaron a ${litrosAjustados}L (cajas completas).`, 'error');
        }
    });


    // --- EVENTOS: SURTIMIENTOS MANUALES (TABLA II) ---
    surtCajas.addEventListener('input', () => {
        if (surtCajas.value === '') { surtLitros.value = ''; actualizarAbastoTotal(); return; }
        const cajas = parseFloat(surtCajas.value);
        if (cajas < 0) return;

        surtLitros.value = cajas * LITROS_POR_CAJA;
        actualizarAbastoTotal();
    });

    surtLitros.addEventListener('input', () => {
        if (surtLitros.value === '') { surtCajas.value = ''; actualizarAbastoTotal(); return; }
        const litros = parseFloat(surtLitros.value);
        if (litros % LITROS_POR_CAJA === 0 && litros !== 0) {
            surtCajas.value = litros / LITROS_POR_CAJA;
            actualizarAbastoTotal();
        } else {
            surtCajas.value = '';
        }
    });

    surtLitros.addEventListener('blur', () => {
        if (surtLitros.value.trim() === '') return;
        let litros = parseFloat(surtLitros.value);
        if (!isNaN(litros) && litros % LITROS_POR_CAJA !== 0) {
            let residuo = litros % LITROS_POR_CAJA;
            let litrosAjustados = (residuo < (LITROS_POR_CAJA / 2)) ? litros - residuo : litros + (LITROS_POR_CAJA - residuo);
            
            surtLitros.value = litrosAjustados;
            surtCajas.value = litrosAjustados / LITROS_POR_CAJA;
            mostrarNotificacion(`El surtimiento se ajustó a ${litrosAjustados}L por cajas completas.`, 'error');
            actualizarAbastoTotal();
        }
    });


    // --- SISTEMA DE NOTIFICACIONES ---
    function mostrarNotificacion(mensaje, tipo = 'info') {
        let contenedor = document.getElementById('toast-container');
        if (!contenedor) {
            contenedor = document.createElement('div');
            contenedor.id = 'toast-container';
            document.body.appendChild(contenedor);
        }

        const toast = document.createElement('div');
        toast.className = 'notificacion-glass';
        const colorIcono = tipo === 'error' ? '#ff3860' : 'var(--bulma-link)';
        const claseIcono = tipo === 'error' ? 'fa-exclamation-triangle' : 'fa-info-circle';
        
        toast.innerHTML = `
            <i class="fas ${claseIcono} is-size-5" style="color: ${colorIcono};"></i> 
            <span style="flex-grow: 1; padding-right: 15px; line-height: 1.4;">${mensaje}</span>
            <i class="fas fa-times btn-cerrar-toast" style="cursor: pointer; opacity: 0.6; font-size: 1.1rem; transition: opacity 0.2s;"></i>
        `;
        
        contenedor.appendChild(toast);
        let timeoutId; 

        const iniciarCierre = () => {
            timeoutId = setTimeout(() => {
                if (document.body.contains(toast)) {
                    toast.style.transform = 'translateX(120%)';
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 400);
                }
            }, 15000); 
        };

        toast.addEventListener('mouseenter', () => clearTimeout(timeoutId));
        toast.addEventListener('mouseleave', iniciarCierre);

        const btnCerrar = toast.querySelector('.btn-cerrar-toast');
        btnCerrar.addEventListener('click', () => {
            toast.style.transform = 'translateX(120%)';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 400);
        });

        setTimeout(() => toast.classList.add('mostrar'), 10);
        iniciarCierre(); 
    }

    // --- CONEXIÓN AL BACKEND (IA) ---
    document.addEventListener('lecheriaSeleccionada', () => {
        const lecheria = document.getElementById('inputLecheria').value.trim();
        const menores = parseInt(document.getElementById('campoMenores').value) || 0;
        const mayores = parseInt(document.getElementById('campoMayores').value) || 0;

        if (!lecheria) return;

        surtCajas.placeholder = "IA...";
        surtLitros.placeholder = "IA...";
        
        fetch('calcularSurtimiento.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ lecher: lecheria, menores: menores, mayores: mayores })
        })
        .then(response => response.json())
        .then(data => {
            if (data.exito) {
                invCaja.value = data.cajas_iniciales;
                invSobres.value = data.cajas_iniciales * SOBRES_POR_CAJA;
                invLitros.value = data.cajas_iniciales * LITROS_POR_CAJA;

                surtCajas.value = data.cajas_surtir;
                surtLitros.value = data.litros_surtir;
                
                // Actualizamos Abasto, y eso dispara Final y Diferencia.
                actualizarAbastoTotal();

                mostrarNotificacion(data.mensaje, 'info');
            } else {
                mostrarNotificacion(data.mensaje, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarNotificacion('Error de conexión con el servidor IA.', 'error');
        });
    });

    // --- BLOQUEO DE CARACTERES ---
    function bloquearCaracteresInvalidos(e) {
        if (['Backspace', 'Tab', 'Enter', 'ArrowLeft', 'ArrowRight', 'Delete', '-'].includes(e.key)) return;
        if (!/^[0-9]$/.test(e.key)) e.preventDefault();
    }
    
    [ventaCaja, ventaLitros, regCaja, regLitros, surtCajas, surtLitros].forEach(input => {
        if(input) {
            input.addEventListener('keydown', bloquearCaracteresInvalidos);
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') { e.preventDefault(); input.blur(); }
            });
        }
    });
});