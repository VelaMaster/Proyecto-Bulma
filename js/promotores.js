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

    // Constantes de medida
    const L_X_CAJA = 72;
    const L_X_SOBRE = 2;

    // --- FUNCIONES MATEMÁTICAS CENTRALIZADAS ---

    function formatearCantidades(litrosTotales) {
        if (isNaN(litrosTotales) || litrosTotales === '') return { cajas: '', sobres: '', litros: '' };
        
        const signo = litrosTotales < 0 ? -1 : 1;
        const absLitros = Math.abs(litrosTotales);
        
        const cajas = Math.floor(absLitros / L_X_CAJA) * signo;
        const sobres = Math.floor((absLitros % L_X_CAJA) / L_X_SOBRE) * signo;
        
        return { cajas, sobres, litros: litrosTotales };
    }

    function obtenerLitrosDeFila(inputCaja, inputSobres) {
        const cajas = parseFloat(inputCaja.value) || 0;
        const sobres = parseFloat(inputSobres.value) || 0;
        return (cajas * L_X_CAJA) + (sobres * L_X_SOBRE);
    }

    // --- FUNCIONES EN CASCADA ---

function calcularDiferencia() {
        const ventaL = parseFloat(ventaLitros.value) || 0;
        let regL = parseFloat(regLitros.value) || 0;
        
        const radioSi = document.querySelector('input[name="venta_igual"][value="Si"]');
        const radioNo = document.querySelector('input[name="venta_igual"][value="No"]');
        const causasDiv = document.getElementById('causas_diferencia');
        if (ventaLitros.value === '' && regLitros.value === '') {
            difCaja.value = ''; difSobres.value = ''; difLitros.value = '';
            if(radioSi) radioSi.checked = false;
            if(radioNo) radioNo.checked = false;
            if(causasDiv) causasDiv.style.display = 'none';
            return;
        }
        const difL = Math.abs(ventaL - regL);
        const fmt = formatearCantidades(difL);
        difCaja.value = fmt.cajas;
        difSobres.value = fmt.sobres;
        difLitros.value = fmt.litros;

        if (regLitros.value !== '' && ventaLitros.value !== '') {
            if (regL === ventaL) {
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

    function calcularInventarioFinal(silencioso = true) {
        const abastoL = parseFloat(abastoLitros.value) || 0;
        const ventaL = parseFloat(ventaLitros.value) || 0;

        if (abastoLitros.value === '' && ventaLitros.value === '') {
            finCaja.value = ''; finSobres.value = ''; finLitros.value = '';
            calcularDiferencia();
            return;
        }

        const invFinalL = abastoL - ventaL;

        if (invFinalL < 0) {
            finCaja.value = 0; finSobres.value = 0; finLitros.value = 0;
            if (!silencioso) {
                mostrarNotificacion("La venta real excede el abasto total del mes.", "error");
            }
        } else {
            const fmt = formatearCantidades(invFinalL);
            finCaja.value = fmt.cajas;
            finSobres.value = fmt.sobres;
            finLitros.value = fmt.litros;
        }

        calcularDiferencia();
    }

    function actualizarAbastoTotal() {
        const invTotalL = parseFloat(invLitros.value) || 0;
        const surtTotalL = parseFloat(surtLitros.value) || 0;
        
        const abastoL = invTotalL + surtTotalL;

        if (invLitros.value === '' && surtLitros.value === '') {
            abastoCaja.value = ''; abastoSobres.value = ''; abastoLitros.value = '';
        } else {
            const fmt = formatearCantidades(abastoL);
            abastoCaja.value = fmt.cajas;
            abastoSobres.value = fmt.sobres;
            abastoLitros.value = fmt.litros;
        }

        calcularInventarioFinal(true);
    }

    // --- FUNCIÓN: VALIDAR LITROS PARES (AVISA Y MARCA EN ROJO) ---
    function validarLitrosPares(inputElement, origen) {
        if (inputElement.value.trim() === '') {
            inputElement.style.borderColor = "";
            return;
        }
        let litros = parseFloat(inputElement.value);
        if (!isNaN(litros) && litros % L_X_SOBRE !== 0) {
            inputElement.style.borderColor = "#ff3860";
            mostrarNotificacion(`Error en ${origen}: Has ingresado ${litros}L. Recuerda que 1 sobre equivale a 2L, corrige a una cantidad par.`, 'error');
        } else {
            inputElement.style.borderColor = ""; // Quita el rojo si ya está bien
        }
    }

    // --- EVENTOS: INVENTARIO INICIAL ---
    function actualizarInvDesdeCajasSobres() {
        if (invCaja.value === '' && invSobres.value === '') {
            invLitros.value = '';
        } else {
            invLitros.value = obtenerLitrosDeFila(invCaja, invSobres);
        }
        actualizarAbastoTotal();
    }

    invCaja.addEventListener('input', actualizarInvDesdeCajasSobres);
    invSobres.addEventListener('input', actualizarInvDesdeCajasSobres);

    invLitros.addEventListener('input', () => {
        if (invLitros.value === '') { 
            invCaja.value = ''; invSobres.value = ''; 
            invLitros.style.borderColor = "";
            actualizarAbastoTotal(); 
            return; 
        }
        const fmt = formatearCantidades(parseFloat(invLitros.value));
        invCaja.value = fmt.cajas;
        invSobres.value = fmt.sobres;
        actualizarAbastoTotal();
    });

    invLitros.addEventListener('blur', () => validarLitrosPares(invLitros, 'inventario inicial'));

    // --- EVENTOS: VENTA REAL ---
    function actualizarVentaDesdeCajasSobres() {
        if (ventaCaja.value === '' && ventaSobres.value === '') {
            ventaLitros.value = '';
        } else {
            ventaLitros.value = obtenerLitrosDeFila(ventaCaja, ventaSobres);
        }
        calcularInventarioFinal(true);
    }

    ventaCaja.addEventListener('input', actualizarVentaDesdeCajasSobres);
    ventaSobres.addEventListener('input', actualizarVentaDesdeCajasSobres);

    ventaLitros.addEventListener('input', () => {
        if (ventaLitros.value === '') { 
            ventaCaja.value = ''; ventaSobres.value = ''; 
            ventaLitros.style.borderColor = "";
            calcularInventarioFinal(true); 
            return; 
        }
        const fmt = formatearCantidades(parseFloat(ventaLitros.value));
        ventaCaja.value = fmt.cajas;
        ventaSobres.value = fmt.sobres;
        calcularInventarioFinal(true);
    });

    ventaLitros.addEventListener('blur', () => validarLitrosPares(ventaLitros, 'venta real'));


    // --- EVENTOS: LITROS REGISTRADOS ---
    function actualizarRegDesdeCajasSobres() {
        if (regCaja.value === '' && regSobres.value === '') {
            regLitros.value = '';
        } else {
            regLitros.value = obtenerLitrosDeFila(regCaja, regSobres);
        }
        calcularDiferencia();
    }

    regCaja.addEventListener('input', actualizarRegDesdeCajasSobres);
    regSobres.addEventListener('input', actualizarRegDesdeCajasSobres);

    regLitros.addEventListener('input', () => {
        if (regLitros.value === '') { 
            regCaja.value = ''; regSobres.value = ''; 
            regLitros.style.borderColor = "";
            calcularDiferencia(); 
            return; 
        }
        const fmt = formatearCantidades(parseFloat(regLitros.value));
        regCaja.value = fmt.cajas;
        regSobres.value = fmt.sobres;
        calcularDiferencia();
    });

    regLitros.addEventListener('blur', () => validarLitrosPares(regLitros, 'litros registrados'));


    // --- EVENTOS: SURTIMIENTOS MANUALES (TABLA II) ---
    surtCajas.addEventListener('input', () => {
        if (surtCajas.value === '') { 
            surtLitros.value = ''; 
            surtCajas.style.borderColor = "";
            surtLitros.style.borderColor = "";
            actualizarAbastoTotal(); 
            return; 
        }
        surtLitros.value = parseFloat(surtCajas.value) * L_X_CAJA;
        surtCajas.style.borderColor = "";
        surtLitros.style.borderColor = "";
        actualizarAbastoTotal();
    });

    surtLitros.addEventListener('input', () => {
        if (surtLitros.value === '') { 
            surtCajas.value = ''; 
            surtCajas.style.borderColor = "";
            surtLitros.style.borderColor = "";
            actualizarAbastoTotal(); 
            return; 
        }
        
        let litros = parseFloat(surtLitros.value);
        // Si no es un entero, mostramos los decimales en caja para que note el error visualmente
        surtCajas.value = Number.isInteger(litros / L_X_CAJA) ? (litros / L_X_CAJA) : (litros / L_X_CAJA).toFixed(2);
        
        if (litros % L_X_CAJA === 0) {
            surtCajas.style.borderColor = "";
            surtLitros.style.borderColor = "";
        }
        actualizarAbastoTotal();
    });

    surtLitros.addEventListener('blur', () => {
        if (surtLitros.value.trim() === '') return;
        let litros = parseFloat(surtLitros.value);
        
        if (!isNaN(litros) && litros % L_X_CAJA !== 0) {
            surtCajas.style.borderColor = "#ff3860";
            surtLitros.style.borderColor = "#ff3860";
            
            // Calculamos las opciones correctas más cercanas
            let cajaInferior = Math.floor(litros / L_X_CAJA);
            let cajaSuperior = Math.ceil(litros / L_X_CAJA);
            let sugerencia1 = cajaInferior * L_X_CAJA;
            let sugerencia2 = cajaSuperior * L_X_CAJA;
            
            mostrarNotificacion(`Aviso en Surtimiento: Ingresaste ${litros}L. Recuerda que se surte en múltiplos de 72L.<br>Sugerencia más próxima: <strong>${sugerencia1}L</strong> (${cajaInferior} cajas) o <strong>${sugerencia2}L</strong> (${cajaSuperior} cajas).`, 'error');
        }
    });


    // --- LÓGICA SECCIÓN 1.2, 2.1 y 4.1 ---
    document.querySelectorAll('input[name="venta_no_incluida"]').forEach(r => r.addEventListener('change', (e) => {
        document.getElementById('motivo_no_incluida').style.display = e.target.value === 'Si' ? 'block' : 'none';
        if(e.target.value === 'No') document.querySelector('input[name="motivo_venta_no_incluida"]').value = '';
    }));

    document.querySelectorAll('input[name="falta_surtimiento"]').forEach(r => r.addEventListener('change', (e) => {
        document.getElementById('causas_falta_surtimiento').style.display = e.target.value === 'Si' ? 'block' : 'none';
        if(e.target.value === 'No') {
            document.querySelector('input[name="causa_falta_descripcion"]').value = '';
            document.querySelector('input[name="causa_falta_a"]').checked = false;
            document.querySelector('input[name="causa_falta_b"]').checked = false;
            document.querySelector('input[name="causa_falta_c_texto"]').value = '';
        }
    }));

    document.querySelectorAll('input[name="continuar_venta"]').forEach(r => r.addEventListener('change', (e) => {
        document.getElementById('alternativas_solucion').style.display = e.target.value === 'No' ? 'block' : 'none';
        if(e.target.value === 'Si') {
            document.querySelector('input[name="alternativa_general"]').value = '';
            document.querySelector('input[name="alt_a"]').checked = false;
            document.querySelector('input[name="alt_b"]').checked = false;
            document.querySelector('input[name="alt_c"]').checked = false;
            document.querySelector('input[name="alt_d_texto"]').value = '';
        }
    }));


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
            }, 10000); 
        };

        toast.addEventListener('mouseenter', () => clearTimeout(timeoutId));
        toast.addEventListener('mouseleave', iniciarCierre);

        toast.querySelector('.btn-cerrar-toast').addEventListener('click', () => {
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
                
                let litrosInicialesBDD = 0;
                
                if (data.litros_iniciales !== undefined) {
                    litrosInicialesBDD = Math.round(parseFloat(data.litros_iniciales));
                } else if (data.cajas_iniciales !== undefined) {
                    litrosInicialesBDD = Math.round(parseFloat(data.cajas_iniciales) * L_X_CAJA);
                }

                const invFmt = formatearCantidades(litrosInicialesBDD);
                invCaja.value = invFmt.cajas;
                invSobres.value = invFmt.sobres;
                invLitros.value = invFmt.litros;

                const surtL = parseFloat(data.litros_surtir) || 0;
                surtLitros.value = surtL;
                surtCajas.value = surtL / L_X_CAJA;
                
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
    
    [invCaja, invSobres, invLitros, ventaCaja, ventaSobres, ventaLitros, regCaja, regSobres, regLitros, surtCajas, surtLitros].forEach(input => {
        if(input) {
            input.addEventListener('keydown', bloquearCaracteresInvalidos);
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') { e.preventDefault(); input.blur(); }
            });
        }
    });

    // --- GENERAR PDF ---
    const btnGenerarPDF = document.getElementById('btnGenerarPDF');
    if (btnGenerarPDF) {
        btnGenerarPDF.addEventListener('click', () => {
            
            // CANDADO 2: Bloqueo de seguridad para que no avancen con errores
            if (!document.getElementById('inputLecheria').value) {
                mostrarNotificacion('Debe seleccionar un punto de venta primero.', 'error');
                return;
            }

            const sL = parseFloat(surtLitros.value) || 0;
            if (surtLitros.value !== '' && sL % L_X_CAJA !== 0) {
                mostrarNotificacion('Corrige el Surtimiento a un múltiplo de 72L antes de guardar el reporte.', 'error');
                return;
            }

            const checkInputs = [invLitros, ventaLitros, regLitros];
            for (let input of checkInputs) {
                if (input.value !== '' && parseFloat(input.value) % L_X_SOBRE !== 0) {
                    mostrarNotificacion('Existen casillas en rojo con litros impares. Corrígelo a cantidades pares (2L) antes de guardar.', 'error');
                    return;
                }
            }
            
            // Si todo está correcto, recopila y envía
            const datosPDF = {
                fecha: document.querySelector('input[name="fecha"]').value,
                lecheria: document.getElementById('inputLecheria').value,
                tienda: document.getElementById('campoTienda').value,
                almacen: document.getElementById('campoAlmacen').value,
                municipio: document.getElementById('campoMunicipio').value,
                comunidad: document.getElementById('campoComunidad').value,

                inv_ini_caja: document.getElementById('inv_ini_caja').value,
                inv_ini_sobres: document.getElementById('inv_ini_sobres').value,
                inv_ini_litros: document.getElementById('inv_ini_litros').value,
                
                abasto_caja: document.getElementById('abasto_caja').value,
                abasto_sobres: document.getElementById('abasto_sobres').value,
                abasto_litros: document.getElementById('abasto_litros').value,
                
                venta_caja: document.getElementById('venta_caja').value,
                venta_sobres: document.getElementById('venta_sobres').value,
                venta_litros: document.getElementById('venta_litros').value,

                reg_caja: document.getElementById('litros_reg_caja').value,
                reg_sobres: document.getElementById('litros_reg_sobres').value,
                reg_litros: document.getElementById('litros_reg_litros').value,

                dif_caja: document.getElementById('dif_caja').value,
                dif_sobres: document.getElementById('dif_sobres').value,
                dif_litros: document.getElementById('dif_litros').value,

                fin_caja: document.getElementById('inv_fin_caja').value,
                fin_sobres: document.getElementById('inv_fin_sobres').value,
                fin_litros: document.getElementById('inv_fin_litros').value,

                venta_igual: document.querySelector('input[name="venta_igual"]:checked')?.value || 'Si',
                causa_desc: document.querySelector('input[name="causa_descripcion"]').value,
                causa_a: document.querySelector('input[name="causa_a"]').checked,
                causa_b: document.querySelector('input[name="causa_b"]').checked,
                causa_c: document.querySelector('input[name="causa_c"]').checked,
                causa_d: document.querySelector('input[name="causa_d_texto"]').value,
                
                venta_no_incluida: document.querySelector('input[name="venta_no_incluida"]:checked')?.value || 'No',
                motivo_no_incluida: document.querySelector('input[name="motivo_venta_no_incluida"]').value,

                surt_fecha: document.getElementById('surt_fecha').value,
                surt_cajas: document.getElementById('surt_cajas').value,
                surt_litros: document.getElementById('surt_litros').value,
                surt_factura: document.getElementById('surt_factura').value,
                surt_caducidad: document.getElementById('surt_caducidad').value,

                falta_surt: document.querySelector('input[name="falta_surtimiento"]:checked')?.value || 'No',
                causa_falta_desc: document.querySelector('input[name="causa_falta_descripcion"]').value,
                causa_falta_a: document.querySelector('input[name="causa_falta_a"]').checked,
                causa_falta_b: document.querySelector('input[name="causa_falta_b"]').checked,
                causa_falta_c: document.querySelector('input[name="causa_falta_c_texto"]').value,

                hogares: document.getElementById('campoHogares').value,
                menores: document.getElementById('campoMenores').value,
                mayores: document.getElementById('campoMayores').value,
                dotacion: document.getElementById('campoDotacion').value,

                prob_a: document.querySelector('input[name="prob_a"]').checked,
                prob_b: document.querySelector('input[name="prob_b"]').checked,
                prob_c: document.querySelector('input[name="prob_c"]').checked,
                prob_d: document.querySelector('input[name="prob_d_texto"]').value,

                continuar: document.querySelector('input[name="continuar_venta"]:checked')?.value || 'Si',
                alt_general: document.querySelector('input[name="alternativa_general"]').value,
                alt_a: document.querySelector('input[name="alt_a"]').checked,
                alt_b: document.querySelector('input[name="alt_b"]').checked,
                alt_c: document.querySelector('input[name="alt_c"]').checked,
                alt_d: document.querySelector('input[name="alt_d_texto"]').value
            };

            btnGenerarPDF.classList.add('is-loading');

            fetch('generar_pdf.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datosPDF)
            })
            .then(response => {
                if (!response.ok) throw new Error('Error en el servidor');
                return response.blob(); 
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = `Inventario_${datosPDF.lecheria}_${datosPDF.fecha}.pdf`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                
                mostrarNotificacion('¡PDF generado exitosamente!', 'info');
                btnGenerarPDF.classList.remove('is-loading');
            })
            .catch(error => {
                console.error(error);
                mostrarNotificacion('Error al generar el PDF.', 'error');
                btnGenerarPDF.classList.remove('is-loading');
            });
        });
    }
});