const fs = require("fs");
const {
  Document, Packer, Paragraph, TextRun, ImageRun, Table, TableRow, TableCell,
  Header, Footer, AlignmentType, HeadingLevel, BorderStyle, WidthType,
  ShadingType, PageNumber, PageBreak, TabStopType, TabStopPosition, LevelFormat
} = require("docx");

const dir = __dirname + "/manual_screenshots/";
const imgs = {
  login: fs.readFileSync(dir + "01_login.jpg"),
  dashboard: fs.readFileSync(dir + "02_dashboard.jpg"),
  inventario: fs.readFileSync(dir + "03_inventario_mensual.jpg"),
  invScroll: fs.readFileSync(dir + "03b_inventario_scroll.jpg"),
  consultar: fs.readFileSync(dir + "04_consultar_inventario.jpg"),
  reporte: fs.readFileSync(dir + "05_reporte_mensual.jpg"),
  requerimiento: fs.readFileSync(dir + "06_requerimiento.jpg"),
  notificaciones: fs.readFileSync(dir + "07_notificaciones.jpg"),
  contrasena: fs.readFileSync(dir + "08_cambiar_contrasena.jpg"),
};

function img(data, w, h) {
  return new ImageRun({ type: "jpg", data, transformation: { width: w, height: h }, altText: { title: "Cap", description: "Cap", name: "cap" } });
}
function p(text, opts = {}) {
  const runs = typeof text === "string" ? [new TextRun({ text, bold: opts.bold, italics: opts.italics, size: opts.size || 24, font: "Arial" })] : text;
  return new Paragraph({ children: runs, spacing: { after: opts.after || 120, before: opts.before || 0, line: 276 }, alignment: opts.align || AlignmentType.JUSTIFIED, heading: opts.heading });
}
function b(t) { return new TextRun({ text: t, bold: true, size: 24, font: "Arial" }); }
function n(t) { return new TextRun({ text: t, size: 24, font: "Arial" }); }
function it(t) { return new TextRun({ text: t, italics: true, size: 24, font: "Arial" }); }
function h1(t) { return new Paragraph({ heading: HeadingLevel.HEADING_1, children: [new TextRun({ text: t, bold: true, size: 32, font: "Arial", color: "1B5E20" })], spacing: { before: 360, after: 200 } }); }
function h2(t) { return new Paragraph({ heading: HeadingLevel.HEADING_2, children: [new TextRun({ text: t, bold: true, size: 28, font: "Arial", color: "2E7D32" })], spacing: { before: 280, after: 160 } }); }
function h3(t) { return new Paragraph({ heading: HeadingLevel.HEADING_3, children: [new TextRun({ text: t, bold: true, size: 26, font: "Arial", color: "388E3C" })], spacing: { before: 200, after: 120 } }); }
function fig(t) { return new Paragraph({ children: [new TextRun({ text: t, italics: true, size: 20, font: "Arial", color: "666666" })], alignment: AlignmentType.CENTER, spacing: { after: 240, before: 80 } }); }
function imgP(data, w, h) { return new Paragraph({ children: [img(data, w, h)], alignment: AlignmentType.CENTER, spacing: { after: 80, before: 120 } }); }
function note(text) {
  const brd = { style: BorderStyle.SINGLE, size: 1, color: "A5D6A7" };
  return new Table({ width: { size: 9360, type: WidthType.DXA }, columnWidths: [9360], rows: [new TableRow({ children: [new TableCell({
    borders: { top: brd, bottom: brd, left: { style: BorderStyle.SINGLE, size: 6, color: "4CAF50" }, right: brd },
    shading: { fill: "E8F5E9", type: ShadingType.CLEAR }, margins: { top: 100, bottom: 100, left: 200, right: 200 },
    width: { size: 9360, type: WidthType.DXA },
    children: [new Paragraph({ children: [new TextRun({ text: "Nota: ", bold: true, size: 22, font: "Arial", color: "2E7D32" }), new TextRun({ text, size: 22, font: "Arial", color: "2E7D32" })], spacing: { after: 0 } })]
  })]})], });
}
function tip(title, text) {
  const brd = { style: BorderStyle.SINGLE, size: 1, color: "90CAF9" };
  return new Table({ width: { size: 9360, type: WidthType.DXA }, columnWidths: [9360], rows: [new TableRow({ children: [new TableCell({
    borders: { top: brd, bottom: brd, left: { style: BorderStyle.SINGLE, size: 6, color: "1976D2" }, right: brd },
    shading: { fill: "E3F2FD", type: ShadingType.CLEAR }, margins: { top: 100, bottom: 100, left: 200, right: 200 },
    width: { size: 9360, type: WidthType.DXA },
    children: [new Paragraph({ children: [new TextRun({ text: title+" ", bold: true, size: 22, font: "Arial", color: "1565C0" }), new TextRun({ text, size: 22, font: "Arial", color: "1565C0" })], spacing: { after: 0 } })]
  })]})], });
}
const sp = (a=200) => new Paragraph({ spacing: { after: a } });
