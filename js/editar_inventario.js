document.addEventListener('DOMContentLoaded', () => {

    /* ── Referencias DOM ── */
    const grid          = document.getElementById('lecherasGrid');
    const inputFiltro   = document.getElementById('inputFiltro');
    const filterCount   = document.getElementById('filterCount');
    const seccionGrid   = document.getElementById('seccionGrid');
    const formPrincipal = document.getElementById('formularioPrincipal');
    const bannerEdicion = document.getElementById('bannerEdicion');
    const modal         = document.getElementById('modalRegistros');
    const modalTitulo   = document.getElementById('modalTitulo');
    const modalSub      = document.getElementById('modalSubtitulo');
    const modalBody     = document.getElementById('modalBody');
    const filtroFecha   = document.getElementById('filtroFecha');
    const btnCerrar     = document.getElementById('btnCerrarModal');
    const btnLimpiar    = document.getElementById('btnLimpiarFiltro');

    let todasLecherias = [];
    let claveActual    = '';

    /* ════════════════════════════════════════════════════════
       1. CARGAR LECHERÍAS
    ════════════════════════════════════════════════════════ */
    fetch('mis_lecherias.php')
        .then(r => r.json())
        .then(datos => {
            if (datos.error) {
                grid.innerHTML = `<div class="empty-state">
                    <span class="material-symbols-outlined">error</span>
                    <p>${datos.mensaje ?? 'Error al cargar lecherías.'}</p>
                </div>`;
                return;
            }

            todasLecherias = Array.isArray(datos) ? datos : [];

            const conInv = todasLecherias.filter(l => (l.TOTAL_INVENTARIOS ?? 0) > 0).length;
            document.getElementById('statTotal').textContent  = todasLecherias.length;
            document.getElementById('statConInv').textContent = conInv;
            document.getElementById('statSinInv').textContent = todasLecherias.length - conInv;

            renderGrid(todasLecherias);
        })
        .catch(() => {
            grid.innerHTML = `<div class="empty-state">
                <span class="material-symbols-outlined">wifi_off</span>
                <p>No se pudo conectar con el servidor.</p>
            </div>`;
        });

    /* ════════════════════════════════════════════════════════
       2. FILTRO EN TIEMPO REAL
    ════════════════════════════════════════════════════════ */
    inputFiltro.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        const filtradas = q
            ? todasLecherias.filter(l =>
                String(l.LECHER).toLowerCase().includes(q) ||
                (l.NOMBRELECH ?? '').toLowerCase().includes(q) ||
                (l.MUNICIPIO  ?? '').toLowerCase().includes(q)
              )
            : todasLecherias;
        renderGrid(filtradas);
    });

    /* ════════════════════════════════════════════════════════
       3. RENDER GRID DE CARDS
    ════════════════════════════════════════════════════════ */
    function renderGrid(lista) {
        filterCount.textContent = lista.length < todasLecherias.length
            ? `${lista.length} de ${todasLecherias.length} lecherías`
            : `${lista.length} lecherías`;

        if (lista.length === 0) {
            grid.innerHTML = `<div class="empty-state">
                <span class="material-symbols-outlined">search_off</span>
                <p>No se encontraron lecherías con ese criterio.</p>
            </div>`;
            return;
        }

        grid.innerHTML = '';
        lista.forEach(l => grid.appendChild(crearCard(l)));
    }

    /* ════════════════════════════════════════════════════════
       4. CREAR CARD
    ════════════════════════════════════════════════════════ */
    function crearCard(l) {
        const totalInv = parseInt(l.TOTAL_INVENTARIOS ?? 0);
        const hogares  = parseInt(l.TOTAL_HOGARES    ?? 0);
        const benef    = parseInt(l.TOTAL_INFANTILES ?? 0) + parseInt(l.TOTAL_RESTO ?? 0);
        const tieneInv = totalInv > 0;

        let ultimoTxt = 'Sin inventarios';
        if (l.ULTIMO_INVENTARIO) {
            const d = new Date(l.ULTIMO_INVENTARIO + 'T12:00:00');
            ultimoTxt = 'Último: ' + d.toLocaleDateString('es-MX', {
                day: '2-digit', month: 'short', year: 'numeric'
            });
        }

        const chipInv = tieneInv
            ? `<span class="lech-chip chip-inv">
                   <span class="material-symbols-outlined">edit_document</span>
                   ${totalInv} inventario${totalInv > 1 ? 's' : ''}
               </span>`
            : `<span class="lech-chip chip-sin chip-sin-edit">
                   <span class="material-symbols-outlined">do_not_disturb_on</span>
                   Sin inventarios
               </span>`;

        const badgeEdit = tieneInv
            ? `<span class="lech-card-edit-badge">
                   <span class="material-symbols-outlined" style="font-size:12px;vertical-align:middle;">edit</span>
                   Editar
               </span>`
            : '';

        const card = document.createElement('div');
        card.className = 'lech-card' + (tieneInv ? '' : ' no-inv');
        card.style.position = 'relative';
        card.innerHTML = `
            ${badgeEdit}
            <div class="lech-card-top">
                <div class="lech-card-avatar">
                    <span class="material-symbols-outlined">${tieneInv ? 'edit_note' : 'storefront'}</span>
                </div>
                <div class="lech-card-header">
                    <div class="lech-card-num">Lechería #${l.LECHER}</div>
                    <div class="lech-card-nombre">${l.NOMBRELECH ?? '—'}</div>
                </div>
            </div>
            <div class="lech-card-body">
                <div class="lech-card-meta">
                    <span class="material-symbols-outlined">location_on</span>
                    ${l.MUNICIPIO ?? ''} · ${l.COMUNIDAD ?? ''}
                </div>
                <div class="lech-card-meta">
                    <span class="material-symbols-outlined">group</span>
                    ${hogares} hogares · ${benef} beneficiarios
                </div>
                <div class="lech-card-chips">${chipInv}</div>
            </div>
            <div class="lech-card-footer">
                <span class="lech-card-footer-txt">${ultimoTxt}</span>
                <span class="material-symbols-outlined">${tieneInv ? 'edit' : 'block'}</span>
            </div>
        `;

        if (tieneInv) {
            card.addEventListener('click', () => {
                claveActual = l.LECHER;
                abrirModal(l.LECHER, l.NOMBRELECH ?? '');
            });
        }

        return card;
    }

    /* ════════════════════════════════════════════════════════
       5. MODAL DE SELECCIÓN DE INVENTARIO
    ════════════════════════════════════════════════════════ */
    function abrirModal(clave, nombre) {
        modalTitulo.textContent = `Lechería ${clave} — ${nombre}`;
        modalSub.textContent    = 'Selecciona el inventario mensual que deseas editar';
        filtroFecha.value       = '';
        modal.classList.add('open');
        cargarRegistros(clave, '');
    }

    function cerrarModal() {
        modal.classList.remove('open');
    }

    btnCerrar.addEventListener('click', cerrarModal);
    modal.addEventListener('click', e => { if (e.target === modal) cerrarModal(); });

    filtroFecha.addEventListener('change', () => cargarRegistros(claveActual, filtroFecha.value));
    btnLimpiar.addEventListener('click', () => {
        filtroFecha.value = '';
        cargarRegistros(claveActual, '');
    });

    /* ════════════════════════════════════════════════════════
       6. CARGAR REGISTROS DEL MODAL
    ════════════════════════════════════════════════════════ */
    function cargarRegistros(clave, mesISO) {
        modalBody.innerHTML = `
            <div class="modal-loading">
                <span class="material-symbols-outlined" style="font-size:32px;opacity:.5;">hourglass_empty</span>
                <p>Buscando inventarios...</p>
            </div>`;

        const params = new URLSearchParams({ clave });
        if (mesISO) params.append('fecha', mesISO);

        fetch('obtener_inventarios_por_lecheria.php?' + params.toString())
            .then(r => r.json())
            .then(rows => {
                if (!Array.isArray(rows) || rows.length === 0) {
                    modalBody.innerHTML = `
                        <div class="modal-empty">
                            <span class="material-symbols-outlined">inventory_2</span>
                            <p>No se encontraron inventarios${mesISO ? ' para ese mes' : ''}.</p>
                        </div>`;
                    return;
                }

                modalBody.innerHTML = '';
                rows.forEach(inv => {
                    const esEditado = (inv.ESTADO ?? '').toLowerCase() === 'editado';
                    const iconCls   = esEditado ? 'icon-editado'  : 'icon-guardado';
                    const pillCls   = esEditado ? 'pill-editado'  : 'pill-guardado';
                    const iconName  = esEditado ? 'edit_document' : 'description';

                    const fechaFmt = inv.FECHA
                        ? new Date(inv.FECHA + 'T12:00:00').toLocaleDateString('es-MX', {
                            day: '2-digit', month: 'long', year: 'numeric'
                          })
                        : inv.FECHA;

                    const item = document.createElement('div');
                    item.className = 'inv-item';
                    item.innerHTML = `
                        <div class="inv-item-icon ${iconCls}">
                            <span class="material-symbols-outlined">${iconName}</span>
                        </div>
                        <div class="inv-item-info">
                            <div class="inv-item-fecha">${fechaFmt}</div>
                            <div class="inv-item-meta">
                                ${inv.MUNICIPIO ?? ''} · ${inv.COMUNIDAD ?? ''}
                                &nbsp;·&nbsp; Inv. final: ${inv.FIN_CAJA ?? 0} cajas / ${inv.FIN_LITROS ?? 0} L
                            </div>
                        </div>
                        <span class="estado-pill ${pillCls}">${inv.ESTADO ?? 'guardado'}</span>
                        <span class="material-symbols-outlined inv-item-arrow">chevron_right</span>
                    `;

                    item.addEventListener('click', () => {
                        cerrarModal();
                        cargarInventarioEnFormulario(inv.ID);
                    });

                    modalBody.appendChild(item);
                });
            })
            .catch(err => {
                console.error(err);
                modalBody.innerHTML = `
                    <div class="modal-empty">
                        <span class="material-symbols-outlined">wifi_off</span>
                        <p>Error de conexión al buscar registros.</p>
                    </div>`;
            });
    }

    /* ════════════════════════════════════════════════════════
       7. CARGAR INVENTARIO EN FORMULARIO
    ════════════════════════════════════════════════════════ */
    function cargarInventarioEnFormulario(id) {
        fetch('obtener_inventario.php?id=' + id)
            .then(r => r.json())
            .then(res => {
                if (res.status !== 'success') {
                    alert('Error: ' + res.mensaje);
                    return;
                }

                const inv = res.datos;

                document.getElementById('inventario_id').value = inv.ID;

                /* Datos generales (readonly, solo mostrar) */
                document.getElementById('inputFecha').value     = inv.FECHA          ?? '';
                document.getElementById('inputLecheria').value  = inv.CLAVE_LECHERIA ?? '';
                document.getElementById('campoTienda').value    = inv.CLAVE_TIENDA   ?? '';
                document.getElementById('campoAlmacen').value   = inv.ALMACEN        ?? '';
                document.getElementById('campoMunicipio').value = inv.MUNICIPIO      ?? '';
                document.getElementById('campoComunidad').value = inv.COMUNIDAD      ?? '';

                /* Sección I — llenamos los 6 campos de cada fila
                   Los campos readonly (inv_ini, abasto, dif, fin) se llenan aquí.
                   Los editables (venta, litros_reg) también se pre-llenan con lo guardado. */
                document.getElementById('inv_ini_caja').value      = inv.INV_INI_CAJA   ?? 0;
                document.getElementById('inv_ini_sobres').value    = inv.INV_INI_SOBRES ?? 0;
                document.getElementById('inv_ini_litros').value    = inv.INV_INI_LITROS ?? 0;

                document.getElementById('abasto_caja').value       = inv.ABASTO_CAJA    ?? 0;
                document.getElementById('abasto_sobres').value     = inv.ABASTO_SOBRES  ?? 0;
                document.getElementById('abasto_litros').value     = inv.ABASTO_LITROS  ?? 0;

                document.getElementById('venta_caja').value        = inv.VENTA_CAJA     ?? 0;
                document.getElementById('venta_sobres').value      = inv.VENTA_SOBRES   ?? 0;
                document.getElementById('venta_litros').value      = inv.VENTA_LITROS   ?? 0;

                document.getElementById('litros_reg_caja').value   = inv.REG_CAJA       ?? 0;
                document.getElementById('litros_reg_sobres').value = inv.REG_SOBRES     ?? 0;
                document.getElementById('litros_reg_litros').value = inv.REG_LITROS     ?? 0;

                document.getElementById('dif_caja').value          = inv.DIF_CAJA       ?? 0;
                document.getElementById('dif_sobres').value        = inv.DIF_SOBRES     ?? 0;
                document.getElementById('dif_litros').value        = inv.DIF_LITROS     ?? 0;

                document.getElementById('inv_fin_caja').value      = inv.FIN_CAJA       ?? 0;
                document.getElementById('inv_fin_sobres').value    = inv.FIN_SOBRES     ?? 0;
                document.getElementById('inv_fin_litros').value    = inv.FIN_LITROS     ?? 0;

                /* Sección II */
                document.getElementById('surt_fecha').value     = inv.SURT_FECHA     ?? '';
                document.getElementById('surt_cajas').value     = inv.SURT_CAJAS     ?? 0;
                document.getElementById('surt_litros').value    = inv.SURT_LITROS    ?? 0;
                document.getElementById('surt_factura').value   = inv.SURT_FACTURA   ?? '';
                document.getElementById('surt_caducidad').value = inv.SURT_CADUCIDAD ?? '';

                /* Sección III */
                document.getElementById('campoHogares').value  = inv.HOGARES  ?? 0;
                document.getElementById('campoMenores').value  = inv.MENORES  ?? 0;
                document.getElementById('campoMayores').value  = inv.MAYORES  ?? 0;
                document.getElementById('campoDotacion').value = inv.DOTACION ?? 0;

                /* Mostrar formulario, ocultar grid */
                seccionGrid.style.display   = 'none';
                bannerEdicion.style.display = 'flex';
                formPrincipal.style.display = 'block';
                setTimeout(() => {
                    formPrincipal.style.opacity = '1';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }, 60);
            })
            .catch(err => {
                console.error(err);
                alert('No se pudo cargar el inventario.');
            });
    }

    /* ── Botón "Cambiar lechería": vuelve al grid ── */
    const btnCambiar = document.getElementById('btnCambiarLecheria');
    if (btnCambiar) {
        btnCambiar.addEventListener('click', () => {
            formPrincipal.style.opacity = '0';
            setTimeout(() => {
                formPrincipal.style.display = 'none';
                bannerEdicion.style.display = 'none';
                document.getElementById('inventario_id').value = '';
                seccionGrid.style.display = 'block';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }, 200);
        });
    }

    /* ════════════════════════════════════════════════════════
       8. RECOLECTAR DATOS DEL FORMULARIO
    ════════════════════════════════════════════════════════ */
    function recolectarDatos() {
        return {
            inventario_id:  document.getElementById('inventario_id').value,
            fecha:          document.getElementById('inputFecha').value,
            lecheria:       document.getElementById('inputLecheria').value,
            tienda:         document.getElementById('campoTienda').value,
            almacen:        document.getElementById('campoAlmacen').value,
            municipio:      document.getElementById('campoMunicipio').value,
            comunidad:      document.getElementById('campoComunidad').value,

            inv_ini_caja:   document.getElementById('inv_ini_caja').value,
            inv_ini_sobres: document.getElementById('inv_ini_sobres').value,
            inv_ini_litros: document.getElementById('inv_ini_litros').value,

            abasto_caja:    document.getElementById('abasto_caja').value,
            abasto_sobres:  document.getElementById('abasto_sobres').value,
            abasto_litros:  document.getElementById('abasto_litros').value,

            venta_caja:     document.getElementById('venta_caja').value,
            venta_sobres:   document.getElementById('venta_sobres').value,
            venta_litros:   document.getElementById('venta_litros').value,

            reg_caja:       document.getElementById('litros_reg_caja').value,
            reg_sobres:     document.getElementById('litros_reg_sobres').value,
            reg_litros:     document.getElementById('litros_reg_litros').value,

            dif_caja:       document.getElementById('dif_caja').value,
            dif_sobres:     document.getElementById('dif_sobres').value,
            dif_litros:     document.getElementById('dif_litros').value,

            fin_caja:       document.getElementById('inv_fin_caja').value,
            fin_sobres:     document.getElementById('inv_fin_sobres').value,
            fin_litros:     document.getElementById('inv_fin_litros').value,

            surt_fecha:     document.getElementById('surt_fecha').value,
            surt_cajas:     document.getElementById('surt_cajas').value,
            surt_litros:    document.getElementById('surt_litros').value,
            surt_factura:   document.getElementById('surt_factura').value,
            surt_caducidad: document.getElementById('surt_caducidad').value,

            hogares:        document.getElementById('campoHogares').value,
            menores:        document.getElementById('campoMenores').value,
            mayores:        document.getElementById('campoMayores').value,
            dotacion:       document.getElementById('campoDotacion').value,
        };
    }

    /* ════════════════════════════════════════════════════════
       9. GUARDAR — ACTUALIZAR BD + GENERAR PDF
    ════════════════════════════════════════════════════════ */
    const btnActualizar = document.getElementById('btnActualizarPDF');
    if (btnActualizar) {
        btnActualizar.addEventListener('click', async () => {
            if (!document.getElementById('inventario_id').value) {
                alert('No hay inventario cargado.'); return;
            }
            if (!confirm('¿Confirmas actualizar este inventario? El PDF anterior será reemplazado.')) return;

            btnActualizar.disabled = true;
            btnActualizar.classList.add('is-loading');

            const datos = recolectarDatos();

            try {
                /* Paso 1: actualizar en BD */
                const r1 = await fetch('actualizar_inventario.php', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify(datos)
                });
                const j1 = await r1.json();
                if (j1.status !== 'success') throw new Error(j1.mensaje);

                /* Paso 2: regenerar PDF */
                const r2 = await fetch('generar_pdf.php', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify(datos)
                });
                if (!r2.ok) throw new Error('Error al generar el PDF.');

                const blob = await r2.blob();
                window.open(URL.createObjectURL(blob), '_blank');
                alert('✔ Inventario y PDF actualizados correctamente.');

            } catch (err) {
                console.error(err);
                alert('Error: ' + err.message);
            } finally {
                btnActualizar.disabled = false;
                btnActualizar.classList.remove('is-loading');
            }
        });
    }

}); // DOMContentLoaded