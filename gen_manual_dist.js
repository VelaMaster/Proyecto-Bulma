const fs = require("fs");
const {
  Document, Packer, Paragraph, TextRun, Header, Footer, AlignmentType,
  HeadingLevel, PageNumber, PageBreak, TabStopType, TabStopPosition, LevelFormat
} = require("docx");

function p(text, opts = {}) {
  const runs = typeof text === "string"
    ? [new TextRun({ text, bold: opts.bold, italics: opts.italics, size: opts.size || 24, font: "Arial" })]
    : text;
  return new Paragraph({ children: runs, spacing: { after: opts.after || 120, before: opts.before || 0, line: 276 },
    alignment: opts.align || AlignmentType.JUSTIFIED, heading: opts.heading });
}
function b(t) { return new TextRun({ text: t, bold: true, size: 24, font: "Arial" }); }
function n(t) { return new TextRun({ text: t, size: 24, font: "Arial" }); }
function it(t) { return new TextRun({ text: t, italics: true, size: 24, font: "Arial" }); }
function h1(t) { return new Paragraph({ heading: HeadingLevel.HEADING_1, children: [new TextRun({ text: t, bold: true, size: 32, font: "Arial" })], spacing: { before: 360, after: 200 } }); }
function h2(t) { return new Paragraph({ heading: HeadingLevel.HEADING_2, children: [new TextRun({ text: t, bold: true, size: 28, font: "Arial" })], spacing: { before: 280, after: 160 } }); }
function fig(t) { return new Paragraph({ children: [new TextRun({ text: t, italics: true, size: 20, font: "Arial", color: "666666" })], alignment: AlignmentType.CENTER, spacing: { after: 240, before: 80 } }); }
function noteP(text) { return new Paragraph({ children: [b("Nota: "), n(text)], spacing: { after: 200, before: 100 } }); }
const sp = (a = 200) => new Paragraph({ spacing: { after: a } });
// Placeholder for image — user will add manually
function imgPlaceholder(figNum, desc) {
  return new Paragraph({ children: [
    new TextRun({ text: `[Insertar captura: ${desc}]`, italics: true, size: 22, font: "Arial", color: "999999" })
  ], alignment: AlignmentType.CENTER, spacing: { after: 40, before: 120 } });
}

const doc = new Document({
  styles: {
    default: { document: { run: { font: "Arial", size: 24 } } },
    paragraphStyles: [
      { id: "Heading1", name: "Heading 1", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 32, bold: true, font: "Arial" },
        paragraph: { spacing: { before: 240, after: 240 }, outlineLevel: 0 } },
      { id: "Heading2", name: "Heading 2", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 28, bold: true, font: "Arial" },
        paragraph: { spacing: { before: 180, after: 180 }, outlineLevel: 1 } },
    ]
  },
  sections: [{
    properties: {
      page: { size: { width: 12240, height: 15840 }, margin: { top: 1440, right: 1440, bottom: 1440, left: 1440 } }
    },
    headers: {
      default: new Header({ children: [new Paragraph({ children: [n("Manual de usuario")], alignment: AlignmentType.LEFT })] }),
    },
    footers: {
      default: new Footer({ children: [new Paragraph({ children: [
        new TextRun({ text: "Leche para el bienestar S.A. de C.V. \u2014 Sistema de inventarios de Leche en polvo GEO            ", size: 16, font: "Arial", color: "AAAAAA" }),
        new TextRun({ children: [PageNumber.CURRENT], size: 16, font: "Arial", color: "AAAAAA" }),
      ] })] }),
    },
    children: [

      // ===== ACCESO DISTRIBUCIÓN =====
      h1("14. Acceso al sistema Distribuci\u00F3n"),
      p("Para ingresar al m\u00F3dulo de Distribuci\u00F3n, abra Google Chrome y escriba en la barra de direcciones la URL proporcionada por el \u00E1rea de inform\u00E1tica. En la pantalla de inicio de sesi\u00F3n, seleccione la pesta\u00F1a Distribuci\u00F3n."),
      imgPlaceholder("3.1", "Pantalla de inicio de sesi\u00F3n con pesta\u00F1a Distribuci\u00F3n seleccionada"),
      fig("Figura 3.1 \u2014 Pantalla de inicio de sesi\u00F3n (Distribuci\u00F3n)"),

      h2("Pasos para iniciar sesi\u00F3n"),
      p([n("En el campo "), b("Usuario"), n(", escriba el nombre de usuario asignado por el \u00E1rea de inform\u00E1tica (por ejemplo: \"renato\").")]),
      p([n("En el campo "), b("Contrase\u00F1a"), n(", escriba su contrase\u00F1a. Si desea verla, haga clic en el \u00EDcono de ojo al final del campo.")]),
      p([n("Si trabaja en su propio equipo, active el interruptor "), b("Recordar sesi\u00F3n"), n(" para no volver a escribir sus datos.")]),
      p([n("Haga clic en el bot\u00F3n verde "), b("Ingresar"), n(". El sistema verificar\u00E1 sus credenciales y lo llevar\u00E1 al Centro de Distribuci\u00F3n.")]),
      noteP("Si aparece un mensaje de error, verifique que su usuario y contrase\u00F1a est\u00E9n escritos correctamente. Recuerde que la contrase\u00F1a distingue entre may\u00FAsculas y min\u00FAsculas."),

      // ===== CENTRO DE DISTRIBUCIÓN =====
      h1("15. Centro de Distribuci\u00F3n"),
      p("Al iniciar sesi\u00F3n, el sistema lo dirigir\u00E1 al Centro de Distribuci\u00F3n. Esta es la pantalla principal y \u00FAnica del m\u00F3dulo. Desde aqu\u00ED se consolidan los datos de requerimientos de todos los supervisores, se descargan los formatos oficiales (OPE, Requerimiento, Minuta) y se visualiza el estado de captura de todas las lecher\u00EDas de la gerencia."),
      imgPlaceholder("3.2", "Centro de Distribuci\u00F3n \u2014 parte superior con banner y descargas"),
      fig("Figura 3.2 \u2014 Centro de Distribuci\u00F3n (Dashboard)"),

      h2("Elementos del Centro de Distribuci\u00F3n"),
      p([b("[1] "), n("Banner de bienvenida")]),
      p("Panel informativo que da la bienvenida al usuario con su nombre y describe la funci\u00F3n principal: consolidar los requerimientos de todos los supervisores de la gerencia. En la barra superior se muestra el enlace de Salir para cerrar sesi\u00F3n."),
      sp(),

      // ===== OPE =====
      p([b("[2] "), n("Secci\u00F3n OPE Diconsa (mensual)")]),
      p([n("Esta secci\u00F3n permite descargar el formato "), b("OPE"), n(" (Operaci\u00F3n de Programa Especial) en formato Excel (.xlsx). El OPE se genera con los datos autorizados por los supervisores para el mes vigente. Se dispone de tres opciones de descarga:")]),
      p([b("OPE completo"), n(" \u2014 descarga el archivo con todas las lecher\u00EDas, tanto las de precio $4.50 como las de $6.50.")]),
      p([b("Solo $4.50"), n(" \u2014 descarga \u00FAnicamente las lecher\u00EDas que manejan el precio de $4.50 por litro.")]),
      p([b("Solo $6.50"), n(" \u2014 descarga \u00FAnicamente las lecher\u00EDas que manejan el precio de $6.50 por litro.")]),
      p([n("El archivo generado tendr\u00E1 un nombre como "), b("OPE062026DICONSA.xlsx"), n(" donde 06 es el mes y 2026 el a\u00F1o.")]),
      noteP("El OPE se genera con base en los datos que los supervisores ya autorizaron. Si un supervisor a\u00FAn no ha autorizado el mes, sus lecher\u00EDas no aparecer\u00E1n en el documento."),

      // ===== REQUERIMIENTO =====
      p([b("[3] "), n("Secci\u00F3n Requerimiento de leche (formato Excel)")]),
      p([n("Esta secci\u00F3n permite descargar el formato de "), b("Requerimiento de leche"), n(" en formato Excel (.xlsx). El archivo contiene una hoja \"POR ALMAC\u00C9N\" con el desglose por cada almac\u00E9n y una hoja \"TOTAL\" con el consolidado por sucursal (Huajuapan, Istmo-Costa, Valles Centrales). Se dispone de dos opciones de descarga:")]),
      p([b("REQ $6.50"), n(" \u2014 descarga el requerimiento de lecher\u00EDas a precio $6.50.")]),
      p([b("REQ $4.50"), n(" \u2014 descarga el requerimiento de lecher\u00EDas a precio $4.50.")]),
      sp(),

      // ===== MINUTA =====
      p([b("[4] "), n("Secci\u00F3n Minuta de conciliaci\u00F3n mensual")]),
      p([n("Permite descargar la plantilla de "), b("Minuta de conciliaci\u00F3n"), n(" precargada con los datos de embarques, total de puntos de venta y el mes del per\u00EDodo. Haga clic en "), b("Descargar Minuta"), n(" para obtener el archivo.")]),
      sp(),

      // ===== FILTROS Y EXPORTACIÓN =====
      p([b("[5] "), n("Filtros de exportaci\u00F3n")]),
      p("Debajo de las secciones de descarga se encuentran los controles de filtrado y exportaci\u00F3n que permiten personalizar la vista de la tabla principal:"),
      p([b("Filtrar exportaci\u00F3n"), n(" \u2014 permite seleccionar qu\u00E9 datos incluir en la exportaci\u00F3n.")]),
      p([b("Excel (.csv)"), n(" \u2014 exporta la tabla completa de lecher\u00EDas en formato CSV para procesamiento en hojas de c\u00E1lculo.")]),
      p([b("PDF"), n(" \u2014 exporta la tabla en formato PDF para impresi\u00F3n o archivo.")]),
      sp(),

      // ===== RESUMEN GLOBAL =====
      imgPlaceholder("3.3", "Resumen global y tabla de lecher\u00EDas por supervisor"),
      fig("Figura 3.3 \u2014 Resumen global y tabla de lecher\u00EDas"),
      p([b("[6] "), n("Resumen global")]),
      p("En la parte central de la pantalla se muestra un panel con cinco contadores que resumen el estado general de la gerencia:"),
      p([b("SUPERVISORES"), n(" \u2014 n\u00FAmero total de supervisores registrados en la gerencia.")]),
      p([b("PROMOTORES"), n(" \u2014 n\u00FAmero total de promotores activos.")]),
      p([b("LECHER\u00CDAS $4.50"), n(" \u2014 cantidad de lecher\u00EDas que operan con el precio de $4.50 por litro.")]),
      p([b("LECHER\u00CDAS $6.50"), n(" \u2014 cantidad de lecher\u00EDas que operan con el precio de $6.50 por litro.")]),
      p([b("CAPTURADAS"), n(" \u2014 n\u00FAmero de lecher\u00EDas que ya tienen inventario capturado para el per\u00EDodo actual.")]),
      sp(),

      // ===== LEYENDA DE ESTADOS =====
      p([b("[7] "), n("Leyenda de estados")]),
      p("Debajo del resumen global se muestra una leyenda que explica los indicadores de estado utilizados en la tabla principal:"),
      p([b("VERIF"), n(" (icono verde con palomita) \u2014 el supervisor ya dio su visto bueno al inventario de esa lecher\u00EDa. El dato es confiable y se incluir\u00E1 en los formatos de descarga.")]),
      p([b("CAPT"), n(" (icono amarillo con l\u00E1piz) \u2014 el promotor envi\u00F3 el inventario pero a\u00FAn est\u00E1 pendiente de la verificaci\u00F3n del supervisor.")]),
      p([b("EST"), n(" (icono azul con gr\u00E1fica) \u2014 avance estimado. El dato se calcula autom\u00E1ticamente y no es confiable hasta que sea verificado.")]),
      p([b("FALTA"), n(" (en rojo) \u2014 la lecher\u00EDa a\u00FAn no tiene inventario capturado para el per\u00EDodo actual.")]),
      sp(),

      // ===== TABLA PRINCIPAL =====
      p([b("[8] "), n("Tabla principal de lecher\u00EDas")]),
      p("La tabla principal ocupa la mayor parte de la pantalla y organiza toda la informaci\u00F3n de la gerencia en una estructura jer\u00E1rquica:"),
      p([b("Nivel 1 \u2014 Supervisor: "), n("cada supervisor aparece como un encabezado con su nombre, el total de cajas consolidadas y el porcentaje de captura de sus lecher\u00EDas.")]),
      p([b("Nivel 2 \u2014 Almac\u00E9n: "), n("dentro de cada supervisor, las lecher\u00EDas se agrupan por el almac\u00E9n que las surte. El encabezado del almac\u00E9n muestra el nombre, la cantidad de lecher\u00EDas verificadas y el total de lecher\u00EDas del almac\u00E9n.")]),
      p([b("Nivel 3 \u2014 Punto de venta: "), n("cada fila de la tabla muestra una lecher\u00EDa individual con las columnas: Punto de venta (clave de la lecher\u00EDa), Tienda (n\u00FAmero de tienda), Estado (VERIF, CAPT, EST o FALTA), y Req. (cantidad de cajas del requerimiento).")]),
      p("Al final de cada grupo de almac\u00E9n se muestra un subtotal con la suma de cajas requeridas. Esto permite a Distribuci\u00F3n conocer las necesidades de surtimiento por almac\u00E9n y planificar los embarques."),
      sp(),

      // ===== FLUJO DE TRABAJO =====
      new Paragraph({ children: [new PageBreak()] }),
      h1("16. Flujo de trabajo de Distribuci\u00F3n"),
      p("El m\u00F3dulo de Distribuci\u00F3n opera como el punto final del proceso de captura de inventarios. El flujo completo es el siguiente:"),
      p([b("Paso 1. "), n("Los promotores capturan los inventarios mensuales de cada lecher\u00EDa asignada.")]),
      p([b("Paso 2. "), n("Los promotores generan sus requerimientos de dotaci\u00F3n y reportes mensuales.")]),
      p([b("Paso 3. "), n("Los supervisores revisan, editan y aprueban los reportes y requerimientos de sus promotores. Al aprobar, marcan las lecher\u00EDas como "), b("VERIF"), n(".")]),
      p([b("Paso 4. "), n("El \u00E1rea de Distribuci\u00F3n ingresa al Centro de Distribuci\u00F3n y verifica el resumen global. El contador "), b("CAPTURADAS"), n(" debe coincidir con el total de lecher\u00EDas para garantizar que toda la informaci\u00F3n est\u00E9 completa.")]),
      p([b("Paso 5. "), n("Distribuci\u00F3n descarga el "), b("OPE"), n(" para enviarlo a Diconsa, el "), b("Requerimiento"), n(" para planificar el surtimiento por almac\u00E9n, y la "), b("Minuta de conciliaci\u00F3n"), n(" para las reuniones mensuales.")]),
      p([b("Paso 6. "), n("Opcionalmente, puede exportar la tabla completa en formato "), b("Excel (.csv)"), n(" o "), b("PDF"), n(" para respaldo o an\u00E1lisis adicional.")]),
      noteP("Antes de descargar los formatos oficiales, verifique que todos los supervisores hayan autorizado sus datos. Las lecher\u00EDas con estado FALTA o CAPT no se incluyen en el OPE ni en el Requerimiento oficial."),
      sp(),

      // ===== CERRAR SESIÓN =====
      h1("17. Cerrar sesi\u00F3n"),
      p([n("Para cerrar sesi\u00F3n, haga clic en el enlace "), b("Salir"), n(" ubicado en la esquina superior derecha de la barra de navegaci\u00F3n. El sistema invalidar\u00E1 su sesi\u00F3n y lo redirigir\u00E1 a la pantalla de inicio de sesi\u00F3n.")]),
      noteP("Siempre cierre sesi\u00F3n al terminar de trabajar, especialmente si comparte el equipo con otras personas."),

    ] // end children
  }] // end sections
});

Packer.toBuffer(doc).then(buffer => {
  fs.writeFileSync(__dirname + "/Manual_Distribucion.docx", buffer);
  console.log("OK - Manual Distribuci\u00F3n generado");
});
