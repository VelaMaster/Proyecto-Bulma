function cambiarModo() {
    const html = document.documentElement;
    const nuevo = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';

    // Función que realmente hace el cambio
    const aplicarCambio = () => {
        html.setAttribute('data-theme', nuevo);
        localStorage.setItem('tema', nuevo);
    };

    // MAGIA: Verifica si el navegador soporta View Transitions (Efecto coqueto)
    if (document.startViewTransition) {
        document.startViewTransition(() => aplicarCambio());
    } else {
        // Fallback para navegadores antiguos (cambio normal con transición CSS)
        aplicarCambio();
    }
}

function cambiarAcento(color) {
    document.documentElement.setAttribute('data-theme-accent', color);
    localStorage.setItem('acento', color);
}

function cargarPreferencias() {
    const t = localStorage.getItem('tema') || 'dark';
    const a = localStorage.getItem('acento') || 'violeta';
    
    document.documentElement.setAttribute('data-theme', t);
    document.documentElement.setAttribute('data-theme-accent', a);
    
    const selector = document.getElementById('selectorColor');
    if (selector) selector.value = a; // <- con verificación, no explota
}

cargarPreferencias();

document.addEventListener('DOMContentLoaded', () => {
    const $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);
    if ($navbarBurgers.length > 0) {
        $navbarBurgers.forEach(el => {
            el.addEventListener('click', () => {
                const target = el.dataset.target;
                const $target = document.getElementById(target);
                el.classList.toggle('is-active');
                $target.classList.toggle('is-active');
            });
        });
    }
});

function seleccionarRol(rol) {
    const botones = document.querySelectorAll('.botones-horizontal .button');
    botones.forEach(btn => btn.classList.remove('is-selected'));
    const btnActivo = document.getElementById(`btn-${rol}`);
    if (btnActivo) btnActivo.classList.add('is-selected');
    setTimeout(() => {
        window.location.href = `inicio${rol}.php`;
    }, 200);
}