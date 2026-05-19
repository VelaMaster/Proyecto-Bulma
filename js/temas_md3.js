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

const COLORES_ACENTO = {
    violeta: '#6750A4',
    verde:   '#386A20',
    naranja: '#8F4C38',
};

function actualizarThemeColor(acento) {
    const color = COLORES_ACENTO[acento] || '#6750A4';
    let meta = document.querySelector('meta[name="theme-color"]');
    if (!meta) {
        meta = document.createElement('meta');
        meta.name = 'theme-color';
        document.head.appendChild(meta);
    }
    meta.content = color;
}

function cambiarAcento(color) {
    document.documentElement.setAttribute('data-theme-accent', color);
    localStorage.setItem('acento', color);
    actualizarThemeColor(color);
}

function cargarPreferencias() {
    const temaGuardado = localStorage.getItem('tema') || 'dark';
    const acentoGuardado = localStorage.getItem('acento') || 'violeta';

    document.documentElement.setAttribute('data-theme', temaGuardado);
    document.documentElement.setAttribute('data-theme-accent', acentoGuardado);

    const selector = document.getElementById('selectorColor');
    if (selector) selector.value = acentoGuardado;
    actualizarIconoModo(temaGuardado);
    actualizarThemeColor(acentoGuardado);
}

cargarPreferencias();