document.addEventListener('DOMContentLoaded', () => {
    const grid = document.getElementById('lecherasGrid');

    const modalOpciones = document.getElementById('modalOpcionesLecheria');
    const modalTitulo = document.getElementById('modalOpcionesTitulo');
    const btnCerrar = document.getElementById('btnCerrarModalOpciones');
    
    const btnGenerar = document.getElementById('btnIrGenerar');
    const btnEditar = document.getElementById('btnIrEditar');
    const btnConsultar = document.getElementById('btnIrConsultar');
    
    let todasLecherias = [];
    let lecheriaSeleccionada = ''; // Esta es la variable buena
    /* ════════════════════════════════════════════════════════
       1. CARGAR LECHERÍAS
    ════════════════════════════════════════════════════════ */
    fetch('mis_lecherias.php')
        .then(async r => {
            const texto = await r.text();
            try {
                return JSON.parse(texto);
            } catch (e) {
                console.error("❌ RESPUESTA SUCIA DE PHP:", texto);
                throw new Error("Error al parsear JSON"); 
            }
        })
        .then(datos => {
            if (datos.error) {
                mostrarErrorVacio(datos.mensaje ?? 'Error al cargar lecherías.');
                return;
            }

            todasLecherias = Array.isArray(datos) ? datos : [];
            renderGrid(todasLecherias);
        })
        .catch(err => {
            mostrarErrorVacio('Error al cargar las lecherías asignadas.');
        });

    function mostrarErrorVacio(mensaje) {
        grid.innerHTML = `
            <div class="empty-state">
                <span class="material-symbols-outlined" style="color:var(--md-sys-color-error);">error</span>
                <p style="color:var(--md-sys-color-error); font-weight: 500;">${mensaje}</p>
            </div>
        `;
    }

    /* ════════════════════════════════════════════════════════
       2. RENDER GRID DE CARDS
    ════════════════════════════════════════════════════════ */
    function renderGrid(lista) {
        if (lista.length === 0) {
            grid.innerHTML = `
                <div class="empty-state">
                    <span class="material-symbols-outlined">store_off</span>
                    <p>No tienes lecherías asignadas.</p>
                </div>
            `;
            return;
        }

        grid.innerHTML = '';
        lista.forEach(l => grid.appendChild(crearCard(l)));
    }

    /* ════════════════════════════════════════════════════════
       3. CREAR CARD INDIVIDUAL USANDO EL CSS DEL USUARIO
    ════════════════════════════════════════════════════════ */
    function crearCard(l) {
        const totalInv = parseInt(l.TOTAL_INVENTARIOS ?? 0);
        const benef    = parseInt(l.TOTAL_INFANTILES ?? 0) + parseInt(l.TOTAL_RESTO ?? 0);
        const tieneInv = totalInv > 0;

        // Texto del pie de la tarjeta
        let ultimoTxt = 'Sin inventarios aún';
        if (l.ULTIMO_INVENTARIO) {
            const d = new Date(l.ULTIMO_INVENTARIO + 'T12:00:00');
            ultimoTxt = 'Último: ' + d.toLocaleDateString('es-MX', {
                day: '2-digit', month: 'short', year: 'numeric'
            });
        }

        // Crear el Chip usando las clases del CSS
        const chipInv = tieneInv
            ? `<span class="lech-chip chip-inv">
                   <span class="material-symbols-outlined">check_circle</span>
                   ${totalInv} inventario${totalInv > 1 ? 's' : ''}
               </span>`
            : `<span class="lech-chip chip-sin">
                   <span class="material-symbols-outlined">pending</span>
                   Sin inventarios
               </span>`;

        // Crear el contenedor principal usando la clase principal del CSS
        const card = document.createElement('div');
        card.className = 'lech-card'; 
        
        // Estructura interna exacta a la que soporta el CSS
        card.innerHTML = `
            <div class="lech-card-top">
                <div class="lech-card-avatar">
                    <span class="material-symbols-outlined">storefront</span>
                </div>
                <div class="lech-card-header">
                    <div class="lech-card-num">Lechería #${l.LECHER}</div>
                    <div class="lech-card-nombre">${l.NOMBRELECH ?? '—'}</div>
                </div>
            </div>
            
            <div class="lech-card-body">
                <div class="lech-card-meta">
                    <span class="material-symbols-outlined">location_on</span>
                    ${l.MUNICIPIO ?? 'Sin municipio'}
                </div>
                <div class="lech-card-meta">
                    <span class="material-symbols-outlined">group</span>
                    ${benef} beneficiarios
                </div>
                <div class="lech-card-chips">
                    ${chipInv}
                </div>
            </div>
            
            <div class="lech-card-footer">
                <span class="lech-card-footer-txt">${ultimoTxt}</span>
                <span class="material-symbols-outlined">touch_app</span>
            </div>
        `;

        // Al hacer clic, abrimos el modal
        card.addEventListener('click', () => {
            abrirModalOpciones(l.LECHER, l.NOMBRELECH ?? 'Lechería');
        });

        return card;
    }
/* ════════════════════════════════════════════════════════
       4. MODAL DE OPCIONES
    ════════════════════════════════════════════════════════ */
    function abrirModalOpciones(clave, nombre) {
        lecheriaSeleccionada = clave;
        modalTitulo.textContent = `#${clave} - ${nombre}`;
        modalOpciones.classList.add('open');
    }
    function cerrarModalOpciones() {
        modalOpciones.classList.remove('open');
        setTimeout(() => {
            lecheriaSeleccionada = '';
        }, 300);
    }
    btnCerrar.addEventListener('click', cerrarModalOpciones);
    modalOpciones.addEventListener('click', (e) => {
        if (e.target === modalOpciones) cerrarModalOpciones();
    });
    btnGenerar.addEventListener('click', () => {
        window.location.href = `generarinventarioMensual.php?lecher=${lecheriaSeleccionada}`;
    });
    btnEditar.addEventListener('click', () => {
        window.location.href = `editarinventarioMensual.php?lecher=${lecheriaSeleccionada}`;
    });
    btnConsultar.addEventListener('click', () => {
        window.location.href = `consultarinventarioMensual.php?lecher=${lecheriaSeleccionada}`;
    });
});