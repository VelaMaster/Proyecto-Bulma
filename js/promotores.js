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

    // ─── FUNCIONES MATEMÁTICAS ────────────────────────────────────────────────

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

    function calcularDiferencia() {
        const ventaL = parseFloat(ventaLitros.value) || 0;
        const regL   = parseFloat(regLitros.value)   || 0;

        const radioSi  = document.querySelector('input[name="venta_igual"][value="Si"]');
        const radioNo  = document.querySelector('input[name="venta_igual"][value="No"]');
        const causasDiv = document.getElementById('causas_diferencia');

        if (ventaLitros.value === '' && regLitros.value === '') {
            difCaja.value = ''; difSobres.value = ''; difLitros.value = '';
            if (radioSi) radioSi.checked = false;
            if (radioNo) radioNo.checked = false;
            if (causasDiv) causasDiv.style.display = 'none';
            return;
        }

        const difL = Math.abs(ventaL - regL);
        const fmt  = formatearCantidades(difL);
        difCaja.value   = fmt.cajas;
        difSobres.value = fmt.sobres;
        difLitros.value = fmt.litros;

        if (regLitros.value !== '' && ventaLitros.value !== '') {
            if (regL === ventaL) {
                if (radioSi)  radioSi.checked  = true;
                if (radioNo)  radioNo.checked  = false;
                if (causasDiv) causasDiv.style.display = 'none';
            } else {
                if (radioNo)  radioNo.checked  = true;
                if (radioSi)  radioSi.checked  = false;
                if (causasDiv) causasDiv.style.display = 'block';
            }
        }
    }

    function calcularInventarioFinal(silencioso = true) {
        const abastoL = parseFloat(abastoLitros.value) || 0;
        const ventaL  = parseFloat(ventaLitros.value)  || 0;

        if (abastoLitros.value === '' && ventaLitros.value === '') {
            finCaja.value = ''; finSobres.value = ''; finLitros.value = '';
            calcularDiferencia();
            return;
        }

        const invFinalL = abastoL - ventaL;

        if (invFinalL < 0) {
            finCaja.value = 0; finSobres.value = 0; finLitros.value = 0;
            if (!silencioso) mostrarNotificacion('La venta real excede el abasto total del mes.', 'error');
        } else {
            const fmt = formatearCantidades(invFinalL);
            finCaja.value   = fmt.cajas;
            finSobres.value = fmt.sobres;
            finLitros.value = fmt.litros;
        }

        calcularDiferencia();
    }

    function actualizarAbastoTotal() {
        const invTotalL  = parseFloat(invLitros.value)  || 0;
        const surtTotalL = parseFloat(surtLitros.value) || 0;
        const abastoL    = invTotalL + surtTotalL;

        if (invLitros.value === '' && surtLitros.value === '') {
            abastoCaja.value = ''; abastoSobres.value = ''; abastoLitros.value = '';
        } else {
            const fmt = formatearCantidades(abastoL);
            abastoCaja.value   = fmt.cajas;
            abastoSobres.value = fmt.sobres;
            abastoLitros.value = fmt.litros;
        }

        calcularInventarioFinal(true);
    }

    // ─── VALIDACIÓN ───────────────────────────────────────────────────────────

    function validarLitrosPares(inputElement, origen) {
        if (inputElement.value.trim() === '') {
            inputElement.style.borderColor = '';
            return;
        }
        const litros = parseFloat(inputElement.value);
        if (!isNaN(litros) && litros % L_X_SOBRE !== 0) {
            inputElement.style.borderColor = '#ff3860';
            mostrarNotificacion(
                `Error en ${origen}: Has ingresado ${litros}L. Recuerda que 1 sobre equivale a 2L, corrige a una cantidad par.`,
                'error'
            );
        } else {
            inputElement.style.borderColor = '';
        }
    }

    // ─── EVENTOS: INVENTARIO INICIAL ─────────────────────────────────────────

    function actualizarInvDesdeCajasSobres() {
        invLitros.value = (invCaja.value === '' && invSobres.value === '')
            ? '' : obtenerLitrosDeFila(invCaja, invSobres);
        actualizarAbastoTotal();
    }

    invCaja.addEventListener('input', actualizarInvDesdeCajasSobres);
    invSobres.addEventListener('input', actualizarInvDesdeCajasSobres);

    invLitros.addEventListener('input', () => {
        if (invLitros.value === '') {
            invCaja.value = ''; invSobres.value = '';
            invLitros.style.borderColor = '';
            actualizarAbastoTotal();
            return;
        }
        const fmt = formatearCantidades(parseFloat(invLitros.value));
        invCaja.value   = fmt.cajas;
        invSobres.value = fmt.sobres;
        actualizarAbastoTotal();
    });

    invLitros.addEventListener('blur', () => validarLitrosPares(invLitros, 'inventario inicial'));

    // ─── EVENTOS: VENTA REAL ─────────────────────────────────────────────────

    function actualizarVentaDesdeCajasSobres() {
        ventaLitros.value = (ventaCaja.value === '' && ventaSobres.value === '')
            ? '' : obtenerLitrosDeFila(ventaCaja, ventaSobres);
        calcularInventarioFinal(true);
    }

    ventaCaja.addEventListener('input', actualizarVentaDesdeCajasSobres);
    ventaSobres.addEventListener('input', actualizarVentaDesdeCajasSobres);

    ventaLitros.addEventListener('input', () => {
        if (ventaLitros.value === '') {
            ventaCaja.value = ''; ventaSobres.value = '';
            ventaLitros.style.borderColor = '';
            calcularInventarioFinal(true);
            return;
        }
        const fmt = formatearCantidades(parseFloat(ventaLitros.value));
        ventaCaja.value   = fmt.cajas;
        ventaSobres.value = fmt.sobres;
        calcularInventarioFinal(true);
    });

    ventaLitros.addEventListener('blur', () => validarLitrosPares(ventaLitros, 'venta real'));

    // ─── EVENTOS: LITROS REGISTRADOS ─────────────────────────────────────────

    function actualizarRegDesdeCajasSobres() {
        regLitros.value = (regCaja.value === '' && regSobres.value === '')
            ? '' : obtenerLitrosDeFila(regCaja, regSobres);
        calcularDiferencia();
    }

    regCaja.addEventListener('input', actualizarRegDesdeCajasSobres);
    regSobres.addEventListener('input', actualizarRegDesdeCajasSobres);

    regLitros.addEventListener('input', () => {
        if (regLitros.value === '') {
            regCaja.value = ''; regSobres.value = '';
            regLitros.style.borderColor = '';
            calcularDiferencia();
            return;
        }
        const fmt = formatearCantidades(parseFloat(regLitros.value));
        regCaja.value   = fmt.cajas;
        regSobres.value = fmt.sobres;
        calcularDiferencia();
    });

    regLitros.addEventListener('blur', () => validarLitrosPares(regLitros, 'litros registrados'));

    // ─── EVENTOS: SURTIMIENTOS ────────────────────────────────────────────────

    surtCajas.addEventListener('input', () => {
        if (surtCajas.value === '') {
            surtLitros.value = '';
            surtCajas.style.borderColor = '';
            surtLitros.style.borderColor = '';
            actualizarAbastoTotal();
            return;
        }
        surtLitros.value = parseFloat(surtCajas.value) * L_X_CAJA;
        surtCajas.style.borderColor = '';
        surtLitros.style.borderColor = '';
        actualizarAbastoTotal();
    });

    surtLitros.addEventListener('input', () => {
        if (surtLitros.value === '') {
            surtCajas.value = '';
            surtCajas.style.borderColor = '';
            surtLitros.style.borderColor = '';
            actualizarAbastoTotal();
            return;
        }
        const litros = parseFloat(surtLitros.value);
        surtCajas.value = Number.isInteger(litros / L_X_CAJA)
            ? (litros / L_X_CAJA)
            : (litros / L_X_CAJA).toFixed(2);
        if (litros % L_X_CAJA === 0) {
            surtCajas.style.borderColor = '';
            surtLitros.style.borderColor = '';
        }
        actualizarAbastoTotal();
    });

    surtLitros.addEventListener('blur', () => {
        if (surtLitros.value.trim() === '') return;
        const litros = parseFloat(surtLitros.value);
        if (!isNaN(litros) && litros % L_X_CAJA !== 0) {
            surtCajas.style.borderColor  = '#ff3860';
            surtLitros.style.borderColor = '#ff3860';
            const cajaInferior = Math.floor(litros / L_X_CAJA);
            const cajaSuperior = Math.ceil(litros / L_X_CAJA);
            mostrarNotificacion(
                `Aviso en Surtimiento: Ingresaste ${litros}L. Recuerda que se surte en múltiplos de 72L.<br>
                 Sugerencia más próxima: <strong>${cajaInferior * L_X_CAJA}L</strong> (${cajaInferior} cajas) o
                 <strong>${cajaSuperior * L_X_CAJA}L</strong> (${cajaSuperior} cajas).`,
                'error'
            );
        }
    });

    // ─── EVENTOS: RADIOS Y CHECKBOXES ────────────────────────────────────────

    document.querySelectorAll('input[name="venta_no_incluida"]').forEach(r =>
        r.addEventListener('change', (e) => {
            const div = document.getElementById('motivo_no_incluida');
            if (div) div.style.display = e.target.value === 'Si' ? 'block' : 'none';
            const m = document.querySelector('input[name="motivo_venta_no_incluida"]');
            if (e.target.value === 'No' && m) m.value = '';
        })
    );

    document.querySelectorAll('input[name="falta_surtimiento"]').forEach(r =>
        r.addEventListener('change', (e) => {
            const div = document.getElementById('causas_falta_surtimiento');
            if (div) div.style.display = e.target.value === 'Si' ? 'block' : 'none';
            if (e.target.value === 'No') {
                const d = document.querySelector('input[name="causa_falta_descripcion"]'); if (d) d.value = '';
                const a = document.querySelector('input[name="causa_falta_a"]'); if (a) a.checked = false;
                const b = document.querySelector('input[name="causa_falta_b"]'); if (b) b.checked = false;
                const c = document.querySelector('input[name="causa_falta_c_texto"]'); if (c) c.value = '';
            }
        })
    );

    document.querySelectorAll('input[name="continuar_venta"]').forEach(r =>
        r.addEventListener('change', (e) => {
            const div = document.getElementById('alternativas_solucion');
            if (div) div.style.display = e.target.value === 'No' ? 'block' : 'none';
            if (e.target.value === 'Si') {
                const g = document.querySelector('input[name="alternativa_general"]'); if (g) g.value = '';
                const a = document.querySelector('input[name="alt_a"]'); if (a) a.checked = false;
                const b = document.querySelector('input[name="alt_b"]'); if (b) b.checked = false;
                const c = document.querySelector('input[name="alt_c"]'); if (c) c.checked = false;
                const d = document.querySelector('input[name="alt_d_texto"]'); if (d) d.value = '';
            }
        })
    );

    // ─── EVENTO: LECHERÍA SELECCIONADA (solo en modo NUEVO) ───────────────────
    // 
    //  IMPORTANTE: Este evento solo debe calcular surtimiento sugerido cuando
    //  estamos creando un inventario nuevo. En modo edición los datos ya vienen
    //  cargados desde la BD y no queremos sobreescribirlos.
    //
    document.addEventListener('lecheriaSeleccionada', () => {
        // Si el objeto Estado existe y estamos en modo edición → salir
        if (typeof Estado !== 'undefined' && Estado.modo === 'edicion') return;

        const lecheria = document.getElementById('inputLecheria').value.trim();
        const menores  = parseInt(document.getElementById('campoMenores').value) || 0;
        const mayores  = parseInt(document.getElementById('campoMayores').value) || 0;

        if (!lecheria) return;

        surtCajas.placeholder = 'IA...';
        surtLitros.placeholder = 'IA...';

        fetch('calcularSurtimiento.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ lecher: lecheria, menores, mayores })
        })
        .then(r => r.json())
        .then(data => {
            if (data.exito) {
                let litrosInicialesBDD = 0;
                if (data.litros_iniciales !== undefined) {
                    litrosInicialesBDD = Math.round(parseFloat(data.litros_iniciales));
                } else if (data.cajas_iniciales !== undefined) {
                    litrosInicialesBDD = Math.round(parseFloat(data.cajas_iniciales) * L_X_CAJA);
                }

                const invFmt = formatearCantidades(litrosInicialesBDD);
                invCaja.value   = invFmt.cajas;
                invSobres.value = invFmt.sobres;
                invLitros.value = invFmt.litros;

                const surtL = parseFloat(data.litros_surtir) || 0;
                surtLitros.value = surtL;
                surtCajas.value  = surtL / L_X_CAJA;

                actualizarAbastoTotal();
                mostrarNotificacion(data.mensaje, 'info');
            } else {
                mostrarNotificacion(data.mensaje, 'error');
            }

            // Restaurar placeholders
            surtCajas.placeholder  = '0';
            surtLitros.placeholder = '0';
        })
        .catch(() => {
            mostrarNotificacion('Error de conexión con el servidor.', 'error');
            surtCajas.placeholder  = '0';
            surtLitros.placeholder = '0';
        });
    });

    // ─── BLOQUEO DE TECLADO ───────────────────────────────────────────────────

    function bloquearCaracteresInvalidos(e) {
        if (['Backspace','Tab','Enter','ArrowLeft','ArrowRight','Delete','-'].includes(e.key)) return;
        if (!/^[0-9]$/.test(e.key)) e.preventDefault();
    }

    [invCaja, invSobres, invLitros, ventaCaja, ventaSobres, ventaLitros,
     regCaja, regSobres, regLitros, surtCajas, surtLitros].forEach(input => {
        if (!input) return;
        input.addEventListener('keydown', bloquearCaracteresInvalidos);
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); input.blur(); }
        });
    });
});