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
