// js/inicio_supervisor.js
document.addEventListener('DOMContentLoaded', () => {
    cargarPromotores();
    configurarModal();
});

async function cargarPromotores() {
    const grid = document.getElementById('promotoresGrid');
    
    try {
        const response = await fetch('api_supervisor.php');
        const data = await response.json(); // <-- Ya no fallará

        if (data.status === 'success') {
            grid.innerHTML = ''; 
            
            if(data.promotores.length === 0) {
                grid.innerHTML = '<p style="color: var(--md-sys-color-on-surface-variant); grid-column: 1/-1; text-align: center;">No tienes promotores asignados actualmente.</p>';
                return;
            }

            data.promotores.forEach(promotor => {
                const card = document.createElement('div');
                card.className = 'promotor-card';
                const inicial = promotor.nombre.charAt(0).toUpperCase();
                
                card.innerHTML = `
                    <div class="promotor-header">
                        <div class="promotor-avatar">${inicial}</div>
                        <div class="promotor-info">
                            <h4>${promotor.nombre}</h4>
                            <p>${promotor.cantidad_lecherias} lecherías a cargo</p>
                        </div>
                    </div>
                `;

                card.addEventListener('click', () => abrirModalPromotor(promotor));
                grid.appendChild(card);
            });
        } else {
            console.error('Error del servidor:', data.message);
            grid.innerHTML = `<p style="color: red;">Error: ${data.message}</p>`;
        }

    } catch (error) {
        console.error('Error de red:', error);
        grid.innerHTML = '<p>Error de conexión al cargar datos.</p>';
    }
}
// ... [El resto de las funciones del modal se quedan igual]

const modal = document.getElementById('modalOpcionesPromotor');
const btnCerrar = document.getElementById('btnCerrarModalPromotor');

function configurarModal() {
    btnCerrar.addEventListener('click', () => {
        modal.classList.remove('active');
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });
}

function abrirModalPromotor(promotor) {
    document.getElementById('modalPromotorTitulo').textContent = `Promotor: ${promotor.nombre}`;
    const listaLecherias = document.getElementById('listaLecheriasModal');
    
    listaLecherias.innerHTML = '';

    if(promotor.lecherias && promotor.lecherias.length > 0) {
        promotor.lecherias.forEach(lech => {
            const item = document.createElement('md-list-item');
            item.innerHTML = `
                <div slot="headline">Lechería ${lech.numero}</div>
                <div slot="supporting-text">${lech.nombre}</div>
                <md-icon slot="start">store</md-icon>
            `;
            listaLecherias.appendChild(item);
        });
    } else {
         listaLecherias.innerHTML = '<p style="padding: 16px; color: gray;">Sin lecherías activas.</p>';
    }

    document.getElementById('btnIrValidarPromotor').onclick = () => {
        window.location.href = `validarInventarios.php?promotor=${promotor.id}`;
    };

    modal.classList.add('active');
}