/**
 * pdf_offline.js — Generador de PDF client-side con jsPDF
 * Replica el formato de _fn_pdf_inventario.php para uso sin conexión.
 *
 * Expone:  generarPDFInventarioOffline(datos) → Promise<Blob>
 *
 * El SW precachea jsPDF desde cdnjs, así que funciona sin internet
 * una vez que el usuario haya cargado la app al menos una vez online.
 */

const _JSPDF_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';

function _cargarJsPDF() {
    if (window.jspdf && window.jspdf.jsPDF) return Promise.resolve(window.jspdf.jsPDF);
    return new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = _JSPDF_CDN;
        s.onload = () => resolve(window.jspdf.jsPDF);
        s.onerror = () => reject(new Error('No se pudo cargar jsPDF (sin conexión y no estaba en caché)'));
        document.head.appendChild(s);
    });
}

async function generarPDFInventarioOffline(datos) {
    const jsPDF = await _cargarJsPDF();

    const doc  = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'letter' });
    const mL   = 15;          // margen izquierdo
    const pageW = 215.9;
    const usableW = pageW - mL - 15; // ≈185.9

    const v = (k) => String(datos[k] ?? '');

    let y = 14;

    // ── ENCABEZADO ────────────────────────────────────────────
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(11);
    doc.text('INVENTARIO MENSUAL DE LECHE EN POLVO', pageW / 2, y, { align: 'center' });
    y += 5;
    doc.setFont('helvetica', 'italic');
    doc.setFontSize(7);
    doc.setTextColor(150, 100, 0);
    doc.text('⚠ Generado sin conexión — el PDF oficial se reemplazará al sincronizar', pageW / 2, y, { align: 'center' });
    doc.setTextColor(0, 0, 0);
    y += 7;

    // ── DATOS GENERALES — fila 1 ─────────────────────────────
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    const labelCell = (lbl, val, x) => {
        doc.setFont('helvetica', 'normal'); doc.text(lbl, x, y);
        doc.setFont('helvetica', 'bold');   doc.text(val, x + doc.getTextWidth(lbl) + 1.5, y);
    };
    labelCell('Fecha:',                    v('fecha'),    mL);
    labelCell('  Clave del punto de venta:', v('lecheria'), mL + 40);
    labelCell('  Clave de tienda:', v('tienda'), mL + 113);
    y += 5;
    labelCell('Almacen que surte:', v('almacen'),   mL);
    labelCell('  Municipio:', v('municipio'), mL + 73);
    labelCell('  Comunidad:', v('comunidad'),  mL + 128);
    y += 8;

    // ── I. EXISTENCIA DE LECHE ────────────────────────────────
    doc.setFont('helvetica', 'bold'); doc.setFontSize(8);
    doc.text('I.- EXISTENCIA DE LECHE.', mL, y); y += 4;

    const wT    = [30, 25, 25, 25, 25, 25, 30];
    const hdrT  = ['', 'Inv. Inicial', 'Abasto\ntotal', 'Ventas\nreal', 'Litros\nReg.', 'Difs.', 'Inv. Final\nmes'];
    const rowsT = [
        ['Cajas',        v('inv_ini_caja'),    v('abasto_caja'),    v('venta_caja'),    v('reg_caja'),    v('dif_caja'),    v('fin_caja')],
        ['Sobres',       v('inv_ini_sobres'),  v('abasto_sobres'),  v('venta_sobres'),  v('reg_sobres'),  v('dif_sobres'),  v('fin_sobres')],
        ['Total litros', v('inv_ini_litros'),  v('abasto_litros'),  v('venta_litros'),  v('reg_litros'),  v('dif_litros'),  v('fin_litros')],
    ];

    function drawTableRow(cells, widths, rowH, bold) {
        let x = mL;
        doc.setFont('helvetica', bold ? 'bold' : 'normal');
        doc.setFontSize(7);
        cells.forEach((cell, i) => {
            doc.rect(x, y, widths[i], rowH);
            const lines = cell.split('\n');
            const lineH = 3.2;
            const totalH = lines.length * lineH;
            lines.forEach((ln, li) => {
                const ty = y + (rowH - totalH) / 2 + lineH * (li + 1) - 0.5;
                doc.text(ln, x + widths[i] / 2, ty, { align: 'center' });
            });
            x += widths[i];
        });
        y += rowH;
    }

    drawTableRow(hdrT, wT, 8, true);
    rowsT.forEach((r, i) => drawTableRow(r, wT, 6, i === 2));
    y += 5;

    // ── II. SURTIMIENTOS ─────────────────────────────────────
    doc.setFont('helvetica', 'bold'); doc.setFontSize(8);
    doc.text('II.- SURTIMIENTOS.', mL, y); y += 4;

    const wS   = [40, 25, 30, 60, 30];
    const hdrS = ['Fecha', 'Cajas', 'Litros', 'Facturas', 'Caducidad'];
    drawTableRow(hdrS, wS, 6, true);
    drawTableRow([v('surt_fecha'), v('surt_cajas'), v('surt_litros'), v('surt_factura'), v('surt_caducidad')], wS, 6, false);
    y += 8;

    // ── III. COBERTURA SOCIAL ────────────────────────────────
    doc.setFont('helvetica', 'bold'); doc.setFontSize(8);
    doc.text('III.- COBERTURA SOCIAL Y DOTACION ASIGNADA SEGUN PADRON.', mL, y); y += 4;

    const wC   = 46;
    const hdrC = ['HOGARES', 'MENORES', 'MAYORES', 'LITROS AL MES'];
    const valC = [v('hogares'), v('menores'), v('mayores'), v('dotacion')];
    // header
    let x = mL + 0.5;
    doc.setFont('helvetica', 'bold'); doc.setFontSize(7);
    hdrC.forEach(h => { doc.rect(x, y, wC, 6); doc.text(h, x + wC/2, y + 4, { align: 'center' }); x += wC; });
    y += 6;
    x = mL + 0.5;
    doc.setFont('helvetica', 'normal'); doc.setFontSize(10);
    valC.forEach(c => { doc.rect(x, y, wC, 7); doc.text(c, x + wC/2, y + 5, { align: 'center' }); x += wC; });
    y += 12;

    // ── FIRMAS ───────────────────────────────────────────────
    const firmaY = 279.4 - 28;
    doc.line(mL,      firmaY, mL + 87,  firmaY);
    doc.line(mL + 98, firmaY, mL + 185, firmaY);
    doc.setFont('helvetica', 'normal'); doc.setFontSize(7);
    doc.text('Nombre y firma del Promotor(a) Social',     mL + 43.5, firmaY + 5, { align: 'center' });
    doc.text('Nombre y firma del distribuidor mercantil', mL + 141.5, firmaY + 5, { align: 'center' });

    return doc.output('blob');
}

window.generarPDFInventarioOffline = generarPDFInventarioOffline;
