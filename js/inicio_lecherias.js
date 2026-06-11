document.addEventListener('DOMContentLoaded', () => {
    const grid = document.getElementById('lecherasGrid');

    const modalOpciones = document.getElementById('modalOpcionesLecheria');
    const modalTitulo = document.getElementById('modalOpcionesTitulo');
    const btnCerrar = document.getElementById('btnCerrarModalOpciones');
    
    const btnGenerar   = document.getElementById('btnIrGenerar');
    const btnConsultar = document.getElementById('btnIrConsultar');

    let todasLecherias       = [];
    let lecheriaSeleccionada = '';
    let nombreSeleccionado   = '';

    const selMes    = document.getElementById('filtroMes');
    const selAnio   = document.getElementById('filtroAnio');
    const btnLimpiar= document.getElementById('btnLimpiarFiltro');
    const resumen   = document.getElementById('filtroResumen');
    const NOMBRE_MESES = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                          'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

    /* ════════════════════════════════════════════════════════
       1. CARGAR LECHERÍAS (con filtro opcional mes/anio)
    ════════════════════════════════════════════════════════ */
    function cargarLecherias() {
        const mes  = selMes  ? selMes.value  : '';
        const anio = selAnio ? selAnio.value : '';
        const params = new URLSearchParams();
        if (mes)  params.set('mes', mes);
        if (anio) params.set('anio', anio);
        const qs = params.toString() ? ('?' + params.toString()) : '';

        // Skeleton mientras carga
        grid.innerHTML = `
            <div class="lech-card is-skeleton"><div class="lech-card-top"></div>
                <div class="lech-card-body">
                    <div class="sk-line skeleton" style="width:60%"></div>
                    <div class="sk-line skeleton" style="width:80%"></div>
                </div></div>`;

        fetch('mis_lecherias.php' + qs)
            .then(async r => {
                const texto = await r.text();
                try { return JSON.parse(texto); }
                catch (e) {
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
                actualizarResumen();
                renderGrid(todasLecherias);
            })
            .catch(err => {
                mostrarErrorVacio('Error al cargar las lecherías asignadas.');
            });
    }

    function actualizarResumen() {
        if (!resumen) return;
        const mes  = selMes  ? parseInt(selMes.value, 10)  : 0;
        const anio = selAnio ? parseInt(selAnio.value, 10) : 0;
        if (!mes || !anio) { resumen.textContent = `${todasLecherias.length} lecherías`; return; }

        const sinInv = todasLecherias.filter(l => parseInt(l.TOTAL_INVENTARIOS ?? 0, 10) === 0).length;
        const sinRep = todasLecherias.filter(l => parseInt(l.TOTAL_REPORTES   ?? 0, 10) === 0).length;
        resumen.innerHTML =
            `<strong>${NOMBRE_MESES[mes]} ${anio}:</strong> ` +
            `${sinInv} sin inventario · ${sinRep} sin reporte`;
    }

    cargarLecherias();

    if (selMes)     selMes.addEventListener('change', cargarLecherias);
    if (selAnio)    selAnio.addEventListener('change', cargarLecherias);
    if (btnLimpiar) btnLimpiar.addEventListener('click', () => {
        if (selMes)  selMes.value  = '';
        if (selAnio) selAnio.value = (new Date()).getFullYear().toString();
        cargarLecherias();
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
        const totalRep = parseInt(l.TOTAL_REPORTES    ?? 0);
        const benef    = parseInt(l.TOTAL_INFANTILES  ?? 0) + parseInt(l.TOTAL_RESTO ?? 0);
        const tieneInv = totalInv > 0;
        const tieneRep = totalRep > 0;
        const mesFiltro  = l._FILTRO_MES  ?? null;
        const anioFiltro = l._FILTRO_ANIO ?? null;
        const conFiltro  = mesFiltro && anioFiltro;

        // Texto del pie de la tarjeta
        let ultimoTxt;
        if (conFiltro) {
            const etiqueta = `${NOMBRE_MESES[mesFiltro]} ${anioFiltro}`;
            ultimoTxt = tieneInv
                ? `Inventario de ${etiqueta} ✓`
                : `Sin inventario de ${etiqueta}`;
        } else if (l.ULTIMO_INVENTARIO) {
            const d = new Date(l.ULTIMO_INVENTARIO + 'T12:00:00');
            ultimoTxt = 'Último: ' + d.toLocaleDateString('es-MX', {
                day: '2-digit', month: 'short', year: 'numeric'
            });
        } else {
            ultimoTxt = 'Sin inventarios aún';
        }

        // Chip de inventario (cambia el texto si hay filtro)
        const chipInv = tieneInv
            ? `<span class="lech-chip chip-inv">
                   <span class="material-symbols-outlined">check_circle</span>
                   ${conFiltro ? 'Inventario hecho' : `${totalInv} inventario${totalInv > 1 ? 's' : ''}`}
               </span>`
            : `<span class="lech-chip chip-sin">
                   <span class="material-symbols-outlined">${conFiltro ? 'error' : 'pending'}</span>
                   ${conFiltro ? 'Falta inventario' : 'Sin inventarios'}
               </span>`;

        // Chip de reporte (solo se muestra cuando hay filtro de periodo)
        const chipRep = conFiltro
            ? (tieneRep
                ? `<span class="lech-chip chip-inv">
                       <span class="material-symbols-outlined">check_circle</span>
                       Reporte hecho
                   </span>`
                : `<span class="lech-chip chip-sin">
                       <span class="material-symbols-outlined">error</span>
                       Falta reporte
                   </span>`)
            : (tieneRep
                ? `<span class="lech-chip chip-inv" style="opacity:.85;">
                       <span class="material-symbols-outlined">receipt_long</span>
                       ${totalRep} reporte${totalRep > 1 ? 's' : ''}
                   </span>`
                : '');

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
                    ${chipRep}
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
        nombreSeleccionado   = nombre;
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
    btnConsultar.addEventListener('click', () => {
        window.location.href = `detalleInventarioMensual.php?clave=${encodeURIComponent(lecheriaSeleccionada)}&nombre=${encodeURIComponent(nombreSeleccionado)}`;
    });
});