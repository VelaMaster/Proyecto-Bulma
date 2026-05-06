// js/inicio_supervisor.js

document.addEventListener('DOMContentLoaded', () => {
    cargarPromotores();
});

async function cargarPromotores() {
    const grid = document.getElementById('promotoresGrid');
    
    try {
        const response = await fetch('api_supervisor.php');
        const data = await response.json();

        if (data.status === 'success') {
            grid.innerHTML = ''; 
            
            if(data.promotores.length === 0) {
                grid.innerHTML = '<p style="color: var(--md-sys-color-on-surface-variant); width: 100%; text-align: center; grid-column: 1/-1;">No tienes promotores asignados actualmente.</p>';
                return;
            }

            data.promotores.forEach(promotor => {
                const card = document.createElement('div');
                card.className = 'promotor-card';
                
                const inicial = promotor.nombre.charAt(0).toUpperCase();
                
                // Diseño Premium MD3 para cada elemento de la lista
                let listaItems = '';
                if (promotor.lecherias && promotor.lecherias.length > 0) {
                    promotor.lecherias.forEach(lech => {
                        listaItems += `
                            <li style="padding: 12px; border: 1px solid var(--md-sys-color-outline-variant); border-radius: 12px; display: flex; align-items: center; gap: 12px; background: var(--md-sys-color-surface-container);">
                                <div style="background: var(--md-sys-color-secondary-container); color: var(--md-sys-color-on-secondary-container); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <md-icon>storefront</md-icon>
                                </div>
                                <div style="display: flex; flex-direction: column;">
                                    <strong style="color: var(--md-sys-color-on-surface); font-size: 1rem;">${lech.numero}</strong>
                                    <span style="font-size: 0.85rem; color: var(--md-sys-color-on-surface-variant);">${lech.nombre}</span>
                                </div>
                            </li>`;
                    });
                } else {
                    listaItems = `<li style="padding: 12px; color: var(--md-sys-color-on-surface-variant);">Sin lecherías asignadas.</li>`;
                }
                
                card.innerHTML = `
                    <div class="card-main-content">
                        <div class="promotor-header">
                            <div class="promotor-avatar">${inicial}</div>
                            <div class="promotor-info">
                                <h4 style="margin: 0 0 4px 0; font-size: 1.1rem; color: var(--md-sys-color-on-surface);">${promotor.nombre}</h4>
                                <p style="margin: 0; color: var(--md-sys-color-primary); font-weight: 500; font-size: 0.9rem;">${promotor.cantidad_lecherias} lecherías</p>
                            </div>
                        </div>
                        
                        <div class="detalles-wrapper">
                            <div class="detalles-inner">
                                <ul class="lista-interna-lecherias" style="list-style: none; margin: 0 0 16px 0; padding: 0; max-height: 280px; overflow-y: auto;">
                                    ${listaItems}
                                </ul>
                                <div style="display: flex; justify-content: flex-end; width: 100%; padding-top: 16px; border-top: 1px solid var(--md-sys-color-outline-variant);">
                                    <md-filled-button onclick="location.href='validarInventarios.php?promotor=${promotor.id}'">
                                        <md-icon slot="icon">fact_check</md-icon> Validar Inventarios
                                    </md-filled-button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-top: auto; padding-top: 12px; display: flex; justify-content: flex-end; width: 100%;">
                        <md-text-button class="btn-toggle-detalles">
                            <md-icon slot="icon" class="icon-toggle">expand_more</md-icon>
                            <span class="text-toggle">Ver detalles</span>
                        </md-text-button>
                    </div>
                `;

                grid.appendChild(card);
            });

            // Lógica de apertura/cierre
            document.querySelectorAll('.btn-toggle-detalles').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation(); 
                    
                    const cardElement = btn.closest('.promotor-card');
                    const icon = btn.querySelector('.icon-toggle');
                    const text = btn.querySelector('.text-toggle');

                    if (!cardElement.classList.contains('expanded')) {
                        // Cierra las demás suavemente
                        document.querySelectorAll('.promotor-card.expanded').forEach(openCard => {
                            if(openCard !== cardElement) {
                                openCard.classList.remove('expanded');
                                openCard.querySelector('.icon-toggle').textContent = 'expand_more';
                                openCard.querySelector('.text-toggle').textContent = 'Ver detalles';
                            }
                        });

                        // Abre la actual
                        cardElement.classList.add('expanded');
                        icon.textContent = 'expand_less';
                        text.textContent = 'Ocultar detalles';
                    } else {
                        // Cierra la actual
                        cardElement.classList.remove('expanded');
                        icon.textContent = 'expand_more';
                        text.textContent = 'Ver detalles';
                    }
                });
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