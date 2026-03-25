function cambiarModo() {
    const htmlEl = document.documentElement;
    const modoActual = htmlEl.getAttribute('data-theme');
    const nuevoModo = modoActual === 'dark' ? 'light' : 'dark';

    const aplicarCambio = () => {
        htmlEl.setAttribute('data-theme', nuevoModo);
        localStorage.setItem('tema', nuevoModo);
        actualizarIconoModo(nuevoModo);
    };
    if (document.startViewTransition) {
        document.startViewTransition(() => aplicarCambio());
    } else {
        aplicarCambio();
    }
}

function actualizarIconoModo(modo) {
    const btnIcon = document.querySelector('#btnModo md-icon');
    if (btnIcon) {
        btnIcon.textContent = modo === 'dark' ? 'light_mode' : 'dark_mode';
    }
}

function cambiarAcento(color) {
    document.documentElement.setAttribute('data-theme-accent', color);
    localStorage.setItem('acento', color);
}

function cargarPreferencias() {
    const temaGuardado = localStorage.getItem('tema') || 'dark';
    const acentoGuardado = localStorage.getItem('acento') || 'violeta';

    document.documentElement.setAttribute('data-theme', temaGuardado);
    document.documentElement.setAttribute('data-theme-accent', acentoGuardado);

    const selector = document.getElementById('selectorColor');
    if (selector) selector.value = acentoGuardado;
    actualizarIconoModo(temaGuardado);
}

cargarPreferencias();