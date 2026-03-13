document.addEventListener('DOMContentLoaded', () => {
    // Inputs Tabla I
    const invCaja = document.getElementById('inv_ini_caja');
    const invSobres = document.getElementById('inv_ini_sobres');
    const invLitros = document.getElementById('inv_ini_litros');
    
    const abastoCaja = document.getElementById('abasto_caja');
    const abastoSobres = document.getElementById('abasto_sobres');
    const abastoLitros = document.getElementById('abasto_litros');

    const ventaCaja = document.getElementById('venta_caja');
    const ventaSobres = document.getElementById('venta_sobres');
    const ventaLitros = document.getElementById('venta_litros');

    const regCaja = document.getElementById('litros_reg_caja');
    const regSobres = document.getElementById('litros_reg_sobres');
    const regLitros = document.getElementById('litros_reg_litros');

    const difCaja = document.getElementById('dif_caja');
    const difSobres = document.getElementById('dif_sobres');
    const difLitros = document.getElementById('dif_litros');

    const finCaja = document.getElementById('inv_fin_caja');
    const finSobres = document.getElementById('inv_fin_sobres');
    const finLitros = document.getElementById('inv_fin_litros');

    // Inputs Tabla II
    const surtCajas = document.getElementById('surt_cajas');
    const surtLitros = document.getElementById('surt_litros');

    const SOBRES_POR_CAJA = 36;
    const LITROS_POR_CAJA = 72;

    // --- FUNCIONES MATEMÁTICAS EN CASCADA ---

    // 1. Calcula la Diferencia: (Venta Real - Litros Registrados)
    function calcularDiferencia() {
        const ventaReal = parseFloat(ventaCaja.value) || 0;
        const registrado = parseFloat(regCaja.value) || 0;
        
        // Elementos de la sección 1.1
        const radioSi = document.querySelector('input[name="venta_igual"][value="Si"]');
        const radioNo = document.querySelector('input[name="venta_igual"][value="No"]');
        const causasDiv = document.getElementById('causas_diferencia');

        // Si ambas están vacías, limpiamos
        if (ventaCaja.value === '' && regCaja.value === '') {
            difCaja.value = ''; difSobres.value = ''; difLitros.value = '';
            if(radioSi) radioSi.checked = false;
            if(radioNo) radioNo.checked = false;
            if(causasDiv) causasDiv.style.display = 'none';
            return;
        }

        // FÓRMULA CORREGIDA: Venta Real - Registrado
        const diferencia = ventaReal - registrado;

        difCaja.value = diferencia;
        difSobres.value = diferencia * SOBRES_POR_CAJA;
        difLitros.value = diferencia * LITROS_POR_CAJA;

        // Automatización 1.1: ¿Registrada == Real?
        if (regCaja.value !== '' && ventaCaja.value !== '') {
            if (registrado === ventaReal) {
                if(radioSi) radioSi.checked = true;
                if(radioNo) radioNo.checked = false;
                if(causasDiv) causasDiv.style.display = 'none';
            } else {
                if(radioNo) radioNo.checked = true;
                if(radioSi) radioSi.checked = false;
                if(causasDiv) causasDiv.style.display = 'block';
            }
        }
    }

    // 2. Calcula Inventario Final: Abasto - Venta Real
    function calcularInventarioFinal(silencioso = true) {
        const abastoActual = parseFloat(abastoCaja.value) || 0;
        const ventaActual = parseFloat(ventaCaja.value) || 0;

        if (abastoCaja.value === '' && ventaCaja.value === '') {
            finCaja.value = ''; finSobres.value = ''; finLitros.value = '';
            calcularDiferencia();
            return;
        }

        const invFinal = abastoActual - ventaActual;

        if (invFinal < 0) {
            finCaja.value = 0; finSobres.value = 0; finLitros.value = 0;
            if (!silencioso) {
                mostrarNotificacion("La venta real excede el abasto total del mes.", "error");
            }
        } else {
            finCaja.value = invFinal;
            finSobres.value = invFinal * SOBRES_POR_CAJA;
            finLitros.value = invFinal * LITROS_POR_CAJA;
        }

        calcularDiferencia();
    }

    // 3. Calcula Abasto Total: Inicial + Surtimiento
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

        calcularInventarioFinal(true);
    }


    // --- EVENTOS: VENTA REAL ---
    ventaCaja.addEventListener('input', () => {
        if (ventaCaja.value === '') { ventaSobres.value = ''; ventaLitros.value = ''; calcularInventarioFinal(true); return; }
        const cajas = parseFloat(ventaCaja.value);
        if (cajas < 0) { ventaCaja.value = ''; return; }

        ventaSobres.value = cajas * SOBRES_POR_CAJA;
        ventaLitros.value = cajas * LITROS_POR_CAJA;
        calcularInventarioFinal(true); 
    });

    ventaCaja.addEventListener('blur', () => calcularInventarioFinal(false));

    ventaLitros.addEventListener('input', () => {
        if (ventaLitros.value === '') { ventaCaja.value = ''; ventaSobres.value = ''; calcularInventarioFinal(true); return; }
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


    // --- EVENTOS: LITROS REGISTRADOS ---
    regCaja.addEventListener('input', () => {
        if (regCaja.value === '') { regSobres.value = ''; regLitros.value = ''; calcularDiferencia(); return; }
        const cajas = parseFloat(regCaja.value);
        if (cajas < 0) { regCaja.value = ''; return; }

        regSobres.value = cajas * SOBRES_POR_CAJA;
        regLitros.value = cajas * LITROS_POR_CAJA;
        calcularDiferencia();
    });

    regLitros.addEventListener('input', () => {
        if (regLitros.value === '') { regCaja.value = ''; regSobres.value = ''; calcularDiferencia(); return; }
        const litros = parseFloat(regLitros.value);
        if (litros % LITROS_POR_CAJA === 0) {
            const cajas = litros / LITROS_POR_CAJA;
            regCaja.value = cajas;
            regSobres.value = cajas * SOBRES_POR_CAJA;
            calcularDiferencia();
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
        calcularDiferencia();
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


    // --- LÓGICA SECCIÓN 1.2 (Venta no registrada) ---
    const radiosVentaNoIncluida = document.querySelectorAll('input[name="venta_no_incluida"]');
    const divMotivoNoIncluida = document.getElementById('motivo_no_incluida');
    
    radiosVentaNoIncluida.forEach(radio => {
        radio.addEventListener('change', (e) => {
            if (e.target.value === 'Si') {
                divMotivoNoIncluida.style.display = 'block';
            } else {
                divMotivoNoIncluida.style.display = 'none';
                document.querySelector('input[name="motivo_venta_no_incluida"]').value = '';
            }
        });
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

    // --- CONEXIÓN A LA IA AL SELECCIONAR LECHERÍA ---
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
// --- LÓGICA SECCIÓN 2.1 (Falta de surtimiento) ---
    const radiosFaltaSurtimiento = document.querySelectorAll('input[name="falta_surtimiento"]');
    const divCausasFalta = document.getElementById('causas_falta_surtimiento');
    
    radiosFaltaSurtimiento.forEach(radio => {
        radio.addEventListener('change', (e) => {
            if (e.target.value === 'Si') {
                divCausasFalta.style.display = 'block';
            } else {
                divCausasFalta.style.display = 'none';
                
                // Limpiamos los campos automáticamente si cambian de opinión y le dan a "No"
                document.querySelector('input[name="causa_falta_descripcion"]').value = '';
                document.querySelector('input[name="causa_falta_a"]').checked = false;
                document.querySelector('input[name="causa_falta_b"]').checked = false;
                document.querySelector('input[name="causa_falta_c_texto"]').value = '';
            }
        });
    });
// --- LÓGICA SECCIÓN 4.1 (Alternativas de solución) ---
    const radiosContinuarVenta = document.querySelectorAll('input[name="continuar_venta"]');
    const divAlternativas = document.getElementById('alternativas_solucion');
    
    radiosContinuarVenta.forEach(radio => {
        radio.addEventListener('change', (e) => {
            if (e.target.value === 'No') {
                // Mostrar con estilo
                divAlternativas.style.display = 'block';
            } else {
                // Ocultar y limpiar datos
                divAlternativas.style.display = 'none';
                
                document.querySelector('input[name="alternativa_general"]').value = '';
                document.querySelector('input[name="alt_a"]').checked = false;
                document.querySelector('input[name="alt_b"]').checked = false;
                document.querySelector('input[name="alt_c"]').checked = false;
                document.querySelector('input[name="alt_d_texto"]').value = '';
            }
        });
    });
});
