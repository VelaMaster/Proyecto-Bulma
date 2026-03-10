// Inicia funciones de clase de generarinventarioMensual.php//
document.addEventListener('DOMContentLoaded', () => {
    const invCaja = document.getElementById('inv_ini_caja');
    const invSobres = document.getElementById('inv_ini_sobres');
    const invLitros = document.getElementById('inv_ini_litros');
    // Constantes de conversión
    const SOBRES_POR_CAJA = 36;
    const LITROS_POR_SOBRE = 2;
    const LITROS_POR_CAJA = SOBRES_POR_CAJA * LITROS_POR_SOBRE; // 72
    // Función para crear y mostrar la notificación
    function mostrarNotificacion(mensaje, tipo = 'info') {
        const existente = document.getElementById('toast-glass');
        if (existente) existente.remove();

        const toast = document.createElement('div');
        toast.id = 'toast-glass';
        toast.className = 'notificacion-glass';
        
        const colorIcono = tipo === 'error' ? '#ff3860' : 'var(--bulma-link)';
        const claseIcono = tipo === 'error' ? 'fa-exclamation-triangle' : 'fa-info-circle';
        
        toast.innerHTML = `<i class="fas ${claseIcono} is-size-5" style="color: ${colorIcono};"></i> <span>${mensaje}</span>`;
        document.body.appendChild(toast);

        setTimeout(() => toast.classList.add('mostrar'), 10);

        setTimeout(() => {
            toast.classList.remove('mostrar');
            setTimeout(() => toast.remove(), 500);
        }, 7500); 
    }
    // 1. Lógica al teclear en Cajas
    invCaja.addEventListener('input', () => {
        const cajas = parseFloat(invCaja.value);
        
        if (isNaN(cajas) || cajas <= 0) {
            invSobres.value = '';
            invLitros.value = '';
            return;
        }

        invSobres.value = cajas * SOBRES_POR_CAJA;
        invLitros.value = cajas * LITROS_POR_CAJA;
    });

    // 2. Lógica MIENTRAS teclea en Litros (Instantáneo)
    invLitros.addEventListener('input', () => {
        const litros = parseFloat(invLitros.value);
        
        if (isNaN(litros)) {
            invCaja.value = '';
            invSobres.value = '';
            return;
        }

        if (litros % LITROS_POR_CAJA === 0 && litros !== 0) {
            const cajas = litros / LITROS_POR_CAJA;
            invCaja.value = cajas;
            invSobres.value = cajas * SOBRES_POR_CAJA;
        } else {
            invCaja.value = '';
            invSobres.value = '';
        }
    });
    // --- NUEVO: FUNCIÓN SEPARADA PARA VALIDAR LITROS ---
    function validarLitros() {
        if (invLitros.value.trim() === '') return;
        let litros = parseFloat(invLitros.value);
        if (!isNaN(litros) && litros % LITROS_POR_CAJA !== 0) {
            let residuo = litros % LITROS_POR_CAJA;
            let litrosAjustados = 0;
            let mensaje = '';
            if (residuo < (LITROS_POR_CAJA / 2)) {
                litrosAjustados = litros - residuo;
                mensaje = `Se bajó a ${litrosAjustados}L porque no se aceptan cajas incompletas.`;
            } else {
                litrosAjustados = litros + (LITROS_POR_CAJA - residuo);
                mensaje = `Se redondeó a ${litrosAjustados}L para completar la caja.`;
            }
            mostrarNotificacion(mensaje, 'error');

            if (litrosAjustados === 0) {
                invLitros.value = '';
                invCaja.value = '';
                invSobres.value = '';
            } else {
                invLitros.value = litrosAjustados;
                const cajas = litrosAjustados / LITROS_POR_CAJA;
                invCaja.value = cajas;
                invSobres.value = cajas * SOBRES_POR_CAJA;
            }
        }
    }
    invLitros.addEventListener('blur', validarLitros);

    // --- NUEVO: EVENTO PARA LA TECLA ENTER ---
    invLitros.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault(); // Evita que el formulario se envíe por accidente
            validarLitros();
            invLitros.blur(); // Opcional: quita el foco del input para indicar que ya se procesó
        }
    });
    // --- NUEVO: VALIDACIÓN ESTRICTA (Sin letras, sin decimales) ---
    function bloquearCaracteresInvalidos(e) {
        if (['Backspace', 'Tab', 'Enter', 'ArrowLeft', 'ArrowRight', 'Delete'].includes(e.key)) {
            return;
        }
        if (!/^[0-9]$/.test(e.key)) {
            e.preventDefault();
        }
    }
    function bloquearPegadoInvalido(e) {
        const textoPegado = e.clipboardData.getData('text');
        if (!/^\d+$/.test(textoPegado)) {
            e.preventDefault();
        }
    }
    invCaja.addEventListener('keydown', bloquearCaracteresInvalidos);
    invLitros.addEventListener('keydown', bloquearCaracteresInvalidos);
    
    invCaja.addEventListener('paste', bloquearPegadoInvalido);
    invLitros.addEventListener('paste', bloquearPegadoInvalido);

    // Si haces scroll en Cajas, salta de 1 en 1
    invCaja.addEventListener('wheel', (e) => aplicarScroll(e, invCaja, 1));
    // Si haces scroll en Litros, salta de 72 en 72
    invLitros.addEventListener('wheel', (e) => aplicarScroll(e, invLitros, LITROS_POR_CAJA));
});
// Termina funciones de clase de generarinventarioMensual.php//