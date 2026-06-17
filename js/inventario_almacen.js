// js/inventario_almacen.js
// Carga datos del endpoint api_inventario_almacen.php, arma el preview editable
// y envía el JSON a generar_pdf_inventario_almacen.php

const $ = (sel) => document.querySelector(sel);
const API = 'api_inventario_almacen.php';

const meses = ['', 'ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO',
                   'JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];

let estado = null;  // estructura cargada desde el endpoint (editable)

// -- Cargar catálogo de almacenes al inicio ----------------------------------
async function cargarCatalogo() {
    try {
        const r = await fetch(`${API}?almacen=&mes=1&anio=${new Date().getFullYear()}`);
        const j = await r.json();
        if (j.status !== 'success') return;
        const sel = $('#selAlmacen');
        (j.cat_almacenes || []).forEach(a => {
            const opt = document.createElement('md-select-option');
            opt.value = a;
            opt.innerHTML = `<div slot="headline">${a}</div>`;
            sel.appendChild(opt);
        });
    } catch (e) { console.error(e); }
}

// -- Cargar datos del almacén/mes seleccionado -------------------------------
async function cargar() {
    const almacen = $('#selAlmacen').value;
    const mes  = parseInt($('#selMes').value, 10);
    const anio = parseInt($('#selAnio').value, 10);
    if (!almacen) { alert('Selecciona un almacén.'); return; }

    $('#contenido').innerHTML = '<div class="skel-loader">Cargando…</div>';
    $('#btnPDF').disabled = true;

    try {
        const url = `${API}?almacen=${encodeURIComponent(almacen)}&mes=${mes}&anio=${anio}`;
        const r = await fetch(url);
        const j = await r.json();
        if (j.status !== 'success') {
            $('#contenido').innerHTML = `<div class="skel-loader">${j.message || 'Error.'}</div>`;
            return;
        }
        estado = j;
        // valores capturables manualmente (no están en BD): default vacíos
        estado.encargado   = '';
        estado.no_almacen  = '';
        estado.fecha       = new Date().toISOString().slice(0,10);
        estado.observaciones = '';
        render();
        $('#btnPDF').disabled = false;
    } catch (e) {
        $('#contenido').innerHTML = `<div class="skel-loader">Error: ${e.message}</div>`;
    }
}

// -- Render del preview editable ---------------------------------------------
function render() {
    const c = $('#contenido');
    const r05 = estado.r05;
    const r4  = estado.r07_450;
    const r6  = estado.r07_650;

    c.innerHTML = `
      <!-- Cabecera capturable -->
      <div class="seccion">
        <div class="seccion-titulo"><md-icon>edit_note</md-icon> Datos de cabecera (editables)</div>
        <div class="seccion-cuerpo grid-2col">
          <md-outlined-text-field id="inpEncargado" label="Encargado del almacén" value=""></md-outlined-text-field>
          <md-outlined-text-field id="inpNoAlmacen" label="No. de almacén" value=""></md-outlined-text-field>
          <md-outlined-text-field id="inpFecha" type="date" label="Fecha del inventario" value="${estado.fecha}"></md-outlined-text-field>
        </div>
      </div>

      <!-- R05 -->
      <div class="seccion">
        <div class="seccion-titulo">
          <md-icon>inventory_2</md-icon> R05 — Existencia física en almacén
          <span class="pill" style="margin-left:auto;">${estado.almacen} · ${meses[estado.mes]} ${estado.anio}</span>
        </div>
        <div class="seccion-cuerpo">
          <h4 style="margin:0 0 8px;font-size:.85rem;opacity:.8;">PROGRAMA DE POBREZA EXTREMA</h4>
          <div class="totales">
            <div class="total-cell">
              <md-outlined-text-field class="r05-edit" data-k="pe.buen_cajas" type="number" label="Buen estado · cajas" value="${r05.pe.buen_cajas}" style="width:100%;"></md-outlined-text-field></div>
            <div class="total-cell">
              <md-outlined-text-field class="r05-edit" data-k="pe.buen_sobres" type="number" label="Buen estado · sobres" value="${r05.pe.buen_sobres}" style="width:100%;"></md-outlined-text-field></div>
            <div class="total-cell">
              <md-outlined-text-field class="r05-edit" data-k="pe.mal_cajas" type="number" label="Mal estado · cajas" value="${r05.pe.mal_cajas}" style="width:100%;"></md-outlined-text-field></div>
            <div class="total-cell">
              <md-outlined-text-field class="r05-edit" data-k="pe.mal_sobres" type="number" label="Mal estado · sobres" value="${r05.pe.mal_sobres}" style="width:100%;"></md-outlined-text-field></div>
          </div>

          <h4 style="margin:14px 0 8px;font-size:.85rem;opacity:.8;">PROGRAMA I.N.I. (en ceros)</h4>
          <div class="totales">
            <div class="total-cell"><label>Buen · cajas</label><strong>0</strong></div>
            <div class="total-cell"><label>Buen · sobres</label><strong>0</strong></div>
            <div class="total-cell"><label>Mal · cajas</label><strong>0</strong></div>
            <div class="total-cell"><label>Mal · sobres</label><strong>0</strong></div>
          </div>

          <h4 style="margin:14px 0 8px;font-size:.85rem;opacity:.8;">Lecherías a las que corresponde la leche en existencia (P.P.E.)</h4>
          <div class="tabla-scroll">
            <table class="inv-table">
              <thead><tr>
                <th>Punto de venta</th><th>No. tienda</th>
                <th>Cajas</th><th>Sobres</th><th>Mes corresponde</th>
              </tr></thead>
              <tbody>
                ${r05.lecherias_pe.length
                    ? r05.lecherias_pe.map((l,i)=>`<tr>
                        <td>${l.punto_venta}</td>
                        <td>${l.num_tienda}</td>
                        <td><md-outlined-text-field class="r05-row" data-i="${i}" data-k="cajas" type="number" value="${l.cajas}" style="max-width:100px;"></md-outlined-text-field></td>
                        <td><md-outlined-text-field class="r05-row" data-i="${i}" data-k="sobres" type="number" value="${l.sobres}" style="max-width:100px;"></md-outlined-text-field></td>
                        <td><md-outlined-text-field class="r05-row" data-i="${i}" data-k="mes_corresponde" value="${l.mes_corresponde}" style="max-width:120px;"></md-outlined-text-field></td>
                      </tr>`).join('')
                    : `<tr><td colspan="5" style="opacity:.6;">Sin saldos pendientes en almacén.</td></tr>`
                }
              </tbody>
            </table>
          </div>

          <h4 style="margin:14px 0 6px;font-size:.85rem;opacity:.8;">Observaciones generales</h4>
          <md-outlined-text-field id="inpObs" type="textarea" label="Observaciones del inventario" rows="3" style="width:100%;"></md-outlined-text-field>
        </div>
      </div>

      ${renderR07(r4, '$4.50', '450')}
      ${renderR07(r6, '$6.50', '650')}
    `;

    // Bind editables a estado
    c.querySelectorAll('.r05-edit').forEach(inp => {
        inp.addEventListener('input', e => {
            const [g, k] = e.target.dataset.k.split('.');
            estado.r05[g][k] = parseInt(e.target.value || '0', 10);
        });
    });
    c.querySelectorAll('.r05-row').forEach(inp => {
        inp.addEventListener('input', e => {
            const i = +e.target.dataset.i;
            const k = e.target.dataset.k;
            const val = (k === 'mes_corresponde') ? e.target.value : parseInt(e.target.value || '0', 10);
            estado.r05.lecherias_pe[i][k] = val;
        });
    });
    c.querySelectorAll('.r07-edit').forEach(inp => {
        inp.addEventListener('input', e => {
            const grupo = e.target.dataset.g; // '450' o '650'
            const i = +e.target.dataset.i;
            const k = e.target.dataset.k;
            const t = e.target.type;
            const val = (t === 'number') ? parseInt(e.target.value || '0', 10) : e.target.value;
            estado['r07_' + grupo].rows[i][k] = val;
        });
    });
    $('#inpEncargado').addEventListener('input', e => estado.encargado = e.target.value);
    $('#inpNoAlmacen').addEventListener('input', e => estado.no_almacen = e.target.value);
    $('#inpFecha').addEventListener('input', e => estado.fecha = e.target.value);
    $('#inpObs').addEventListener('input', e => estado.observaciones = e.target.value);
}

function renderR07(r07, label, grupo) {
    if (!r07 || !r07.rows.length) {
        return `
        <div class="seccion">
          <div class="seccion-titulo">
            <md-icon>receipt_long</md-icon> R07 — Conciliación ${label}
          </div>
          <div class="seccion-cuerpo" style="opacity:.6;">Sin surtimientos registrados a este precio en el mes.</div>
        </div>`;
    }
    return `
    <div class="seccion">
      <div class="seccion-titulo">
        <md-icon>receipt_long</md-icon> R07 — Conciliación ${label}
        <span class="pill" style="margin-left:auto;">Total cajas: ${r07.total_recibidas}</span>
      </div>
      <div class="seccion-cuerpo tabla-scroll">
        <table class="inv-table">
          <thead><tr>
            <th>Punto de venta</th><th>Tienda</th>
            <th>Cajas recib.</th><th>Fecha recep.</th><th>Guías dist.</th>
            <th>No. factura</th><th>Cajas env.</th><th>Fecha env.</th><th>Obs.</th>
          </tr></thead>
          <tbody>
            ${r07.rows.map((r,i)=>`<tr>
              <td>${r.punto_venta}</td>
              <td>${r.num_tienda}</td>
              <td><md-outlined-text-field class="r07-edit" data-g="${grupo}" data-i="${i}" data-k="cajas_recibidas" type="number" value="${r.cajas_recibidas}" style="max-width:90px;"></md-outlined-text-field></td>
              <td><md-outlined-text-field class="r07-edit" data-g="${grupo}" data-i="${i}" data-k="fecha_recepcion" value="${r.fecha_recepcion}" style="max-width:110px;"></md-outlined-text-field></td>
              <td><md-outlined-text-field class="r07-edit" data-g="${grupo}" data-i="${i}" data-k="guias_distribucion" type="number" value="${r.guias_distribucion}" style="max-width:90px;"></md-outlined-text-field></td>
              <td><md-outlined-text-field class="r07-edit" data-g="${grupo}" data-i="${i}" data-k="no_factura" value="${r.no_factura}" style="max-width:110px;"></md-outlined-text-field></td>
              <td><md-outlined-text-field class="r07-edit" data-g="${grupo}" data-i="${i}" data-k="no_cajas_enviadas" type="number" value="${r.no_cajas_enviadas}" style="max-width:90px;"></md-outlined-text-field></td>
              <td><md-outlined-text-field class="r07-edit" data-g="${grupo}" data-i="${i}" data-k="fecha_enviada" value="${r.fecha_enviada}" style="max-width:110px;"></md-outlined-text-field></td>
              <td><md-outlined-text-field class="r07-edit" data-g="${grupo}" data-i="${i}" data-k="observaciones" value="" style="max-width:120px;"></md-outlined-text-field></td>
            </tr>`).join('')}
          </tbody>
        </table>
      </div>
    </div>`;
}

// -- Generar PDF ---------------------------------------------------------------
function generarPDF() {
    if (!estado) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'generar_pdf_inventario_almacen.php';
    form.target = '_blank';
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'datos';
    input.value = JSON.stringify(estado);
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// -- Auto-cargar al cambiar cualquier filtro -----------------------------------
function autoCarga() {
    if ($('#selAlmacen').value) cargar();
}

// -- Init ----------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    cargarCatalogo();
    $('#selAlmacen').addEventListener('change', autoCarga);
    $('#selMes').addEventListener('change', autoCarga);
    $('#selAnio').addEventListener('change', autoCarga);
    $('#btnPDF').addEventListener('click', generarPDF);
});
