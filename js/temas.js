function cambiarModo() {
    const html = document.documentElement;
    const nuevo = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', nuevo);
    localStorage.setItem('tema', nuevo);
}

function cambiarAcento(color) {
    document.documentElement.setAttribute('data-theme-accent', color);
    localStorage.setItem('acento', color);
}

window.onload = () => {
    const t = localStorage.getItem('tema');
    const a = localStorage.getItem('acento');
    if(t) document.documentElement.setAttribute('data-theme', t);
    if(a) {
        document.documentElement.setAttribute('data-theme-accent', a);
        document.getElementById('selectorColor').value = a;
    }
}
function seleccionarRol(rol) {
    const botones = document.querySelectorAll('.botones-horizontal .button');
    botones.forEach(btn => btn.classList.remove('is-selected'));
    const btnActivo = document.getElementById(`btn-${rol}`);
    btnActivo.classList.add('is-selected');
    setTimeout(() => {
        window.location.href = `inicio${rol}.php`;
    }, 200);
}
// Función unificada para aplicar preferencias
function cargarPreferencias() {
    const t = localStorage.getItem('tema') || 'dark';
    const a = localStorage.getItem('acento') || 'violeta';
    
    document.documentElement.setAttribute('data-theme', t);
    document.documentElement.setAttribute('data-theme-accent', a);
    
    const selector = document.getElementById('selectorColor');
    if (selector) selector.value = a;
}
// Ejecutar inmediatamente para evitar parpadeo blanco
cargarPreferencias();

function cambiarModo() {
    const html = document.documentElement;
    const nuevo = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', nuevo);
    localStorage.setItem('tema', nuevo);
}
document.addEventListener('DOMContentLoaded', () => {
    const $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);
    if ($navbarBurgers.length > 0) {
        $navbarBurgers.forEach( el => {
            el.addEventListener('click', () => {
                const target = el.dataset.target;
                const $target = document.getElementById(target);
                el.classList.toggle('is-active');
                $target.classList.toggle('is-active');
            });
        });
    }
});