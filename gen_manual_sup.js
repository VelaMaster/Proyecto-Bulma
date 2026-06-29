const fs = require("fs");
const {
  Document, Packer, Paragraph, TextRun, ImageRun, Table, TableRow, TableCell,
  Header, Footer, AlignmentType, HeadingLevel, BorderStyle, WidthType,
  ShadingType, PageNumber, PageBreak, TabStopType, TabStopPosition, LevelFormat
} = require("docx");

const dir = __dirname + "/manual_screenshots/supervisor/";
const imgs = {
  login: fs.readFileSync(dir + "01_login_sup.jpg"),
  dashboard: fs.readFileSync(dir + "02_dashboard_sup.jpg"),
  reporte: fs.readFileSync(dir + "03_reporte_mensual_sup.jpg"),
  requerimiento: fs.readFileSync(dir + "04_requerimiento_sup.jpg"),
  invAlmacen: fs.readFileSync(dir + "05_inventario_almacen_sup.jpg"),
  lecherias: fs.readFileSync(dir + "06_lecherias_sup.jpg"),
  solicitudes: fs.readFileSync(dir + "07_solicitudes_sup.jpg"),
  estadisticas: fs.readFileSync(dir + "08_estadisticas_sup.jpg"),
  historial: fs.readFileSync(dir + "09_historial_sup.jpg"),
};

function img(data, w, h) {
  return new ImageRun({ type: "jpg", data, transformation: { width: w, height: h },
    altText: { title: "Cap", description: "Captura del sistema", name: "cap" } });
}

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
function imgP(data, w, h) { return new Paragraph({ children: [img(data, w, h)], alignment: AlignmentType.CENTER, spacing: { after: 80, before: 120 } }); }
function noteP(text) {
  return new Paragraph({ children: [b("Nota: "), n(text)], spacing: { after: 200, before: 100 } });
}
const sp = (a = 200) => new Paragraph({ spacing: { after: a } });

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

      // ===== SECTION 5: ACCESO AL SISTEMA SUPERVISOR =====
      h1("5. Acceso al sistema supervisor"),
      p("Para ingresar al m\u00F3dulo de Supervisor, abra Google Chrome y escriba en la barra de direcciones la URL proporcionada por el \u00E1rea de inform\u00E1tica. En la pantalla de inicio de sesi\u00F3n, seleccione la pesta\u00F1a Supervisor."),
      imgP(imgs.login, 500, 330),
      fig("Figura 2.1 \u2014 Pantalla de inicio de sesi\u00F3n (Supervisor)"),

      h2("Pasos para iniciar sesi\u00F3n"),
      p([n("En el campo "), b("Usuario (Supervisor)"), n(", escriba el nombre de usuario asignado por el \u00E1rea de inform\u00E1tica (por ejemplo: \"marino\").")]),
      p([n("En el campo "), b("Contrase\u00F1a"), n(", escriba su contrase\u00F1a. Si desea verla, haga clic en el \u00EDcono de ojo al final del campo.")]),
      p([n("Si trabaja en su propio equipo y desea no volver a escribir sus datos, active el interruptor "), b("Recordar sesi\u00F3n"), n(".")]),
      p([n("Haga clic en el bot\u00F3n verde "), b("Ingresar"), n(". El sistema verificar\u00E1 sus credenciales y lo llevar\u00E1 al Panel de Supervisi\u00F3n.")]),
      noteP("Si aparece un mensaje de error, verifique que su usuario y contrase\u00F1a est\u00E9n escritos correctamente. Recuerde que la contrase\u00F1a distingue entre may\u00FAsculas y min\u00FAsculas."),

      // ===== SECTION 6: PANEL DE SUPERVISION =====
      h1("6. Panel de Supervisi\u00F3n"),
      p("Al iniciar sesi\u00F3n correctamente, el sistema lo dirigir\u00E1 al Panel de Supervisi\u00F3n. Desde aqu\u00ED tiene acceso r\u00E1pido a todas las funciones del m\u00F3dulo Supervisor."),
      imgP(imgs.dashboard, 500, 620),
      fig("Figura 2.2 \u2014 Panel de Supervisi\u00F3n (Dashboard)"),

      h2("Elementos del Panel de Supervisi\u00F3n"),
      p([b("[1] "), n("Barra informativa")]),
      p([n("Panel de bienvenida con el nombre del supervisor en sesi\u00F3n. Incluye la tarjeta de acceso r\u00E1pido "), b("Ir al Reporte Mensual"), n(" que redirecciona directamente a la pantalla de reportes. "), it("Figura 2.3")]),
      sp(),
      p([b("[2] "), n("Accesos r\u00E1pidos")]),
      p([n("La tarjeta "), b("Estad\u00EDsticas de Zona"), n(" permite revisar el rendimiento, distribuci\u00F3n y consumo de las lecher\u00EDas a su cargo. "), it("Figura 2.8")]),
      p([n("La tarjeta "), b("Autorizar Mes (Distribuci\u00F3n)"), n(" marca las lecher\u00EDas como listas para que Distribuci\u00F3n descargue el OPE.")]),
      p([n("La tarjeta "), b("Lecher\u00EDas"), n(" consulta el cat\u00E1logo de lecher\u00EDas a su cargo. "), it("Figura 2.6")]),
      p([n("La tarjeta "), b("Historial General"), n(" muestra el historial global de requerimientos de su zona. "), it("Figura 2.9")]),
      p([n("La tarjeta "), b("Solicitudes"), n(" muestra la bandeja de solicitudes pendientes de sus promotores. El n\u00FAmero rojo indica solicitudes sin atender. "), it("Figura 2.7")]),
      sp(),
      p([b("[3] "), n("Barra de navegaci\u00F3n")]),
      p([b("Reporte Mensual"), n(" lo dirigir\u00E1 a la pantalla donde puede revisar, editar y aprobar los reportes de sus promotores. "), it("Figura 2.3")]),
      p([b("Requerimiento de Dotaci\u00F3n"), n(" lo dirigir\u00E1 a la pantalla para revisar los requerimientos de abasto. "), it("Figura 2.4")]),
      p([b("Inventario de Almac\u00E9n"), n(" lo dirigir\u00E1 a la pantalla para generar el inventario consolidado del almac\u00E9n. "), it("Figura 2.5")]),
      p([b("Contrase\u00F1a"), n(" lo dirigir\u00E1 a la pantalla donde puede cambiar su contrase\u00F1a.")]),
      p([b("Salir"), n(" al presionar se cerrar\u00E1 su sesi\u00F3n activa y lo dirigir\u00E1 a la pantalla de inicio de sesi\u00F3n.")]),
      sp(),
      p([b("[4] "), n("Mis Promotores Asignados")]),
      p("En la parte inferior del panel se muestran las tarjetas de los promotores asignados al supervisor. Cada tarjeta muestra el nombre del promotor, la cantidad de lecher\u00EDas que tiene asignadas, el avance de inventarios del mes (con indicador verde si est\u00E1 completo o rojo si falta), y una barra de progreso. Al hacer clic en \"Ver detalles\" se expande la informaci\u00F3n del promotor."),
      p("Los filtros de mes y a\u00F1o en la parte superior de esta secci\u00F3n permiten consultar el avance de un per\u00EDodo espec\u00EDfico."),
      noteP("Si no cierra la sesi\u00F3n y \u00FAnicamente cierra el navegador o la pesta\u00F1a, su sesi\u00F3n seguir\u00E1 abierta. Si usa una computadora compartida, cierre su sesi\u00F3n como medida de seguridad."),

      // ===== SECTION 7: REPORTE MENSUAL SUPERVISOR =====
      new Paragraph({ children: [new PageBreak()] }),
      h1("7. Reporte Mensual"),
      p("La pantalla de Reporte Mensual del Supervisor muestra un listado de todos los promotores asignados junto con el estado de sus reportes para el per\u00EDodo seleccionado. Desde aqu\u00ED puede revisar, editar, aprobar o rechazar cada reporte antes de enviarlo a Distribuci\u00F3n."),
      p([n("C\u00F3mo acceder: "), b("Reporte Mensual"), n(" en la barra de navegaci\u00F3n.")]),
      imgP(imgs.reporte, 530, 530),
      fig("Figura 2.3 \u2014 Reporte Mensual del Supervisor"),

      h2("Elementos de la pantalla"),
      p([b("[1] "), n("Filtros de periodo")]),
      p([n("Seleccione el "), b("Mes"), n(" y "), b("A\u00F1o"), n(" para cargar los reportes de ese per\u00EDodo.")]),
      sp(),
      p([b("[2] "), n("Panel de estad\u00EDsticas")]),
      p("Tres contadores muestran de un vistazo: el n\u00FAmero de reportes aprobados, los pendientes de aprobar y los que a\u00FAn no han sido capturados por el promotor."),
      sp(),
      p([b("[3] "), n("Tabla de promotores")]),
      p("La tabla muestra cada promotor con las columnas: nombre y n\u00FAmero de empleado, per\u00EDodo del reporte, n\u00FAmero de lecher\u00EDas capturadas respecto al total asignado, estado del reporte (En progreso, Falta, Aprobado), fecha de captura, y la acci\u00F3n \"Ver avance\" para abrir el detalle del promotor."),
      sp(),
      h2("Pasos para revisar un reporte"),
      p([b("[1] "), n("Seleccione el mes y a\u00F1o del per\u00EDodo que desea revisar.")]),
      p([b("[2] "), n("Identifique en la tabla los promotores con estado "), b("En progreso"), n(" o "), b("Falta"), n(".")]),
      p([b("[3] "), n("Haga clic en "), b("Ver avance"), n(" junto al promotor que desea revisar.")]),
      p([b("[4] "), n("En la pantalla de detalle podr\u00E1 ver el reporte completo del promotor, editarlo si es necesario y aprobarlo para Distribuci\u00F3n.")]),
      noteP("Solo puede aprobar reportes cuando el promotor ha completado todos los inventarios del per\u00EDodo. Los reportes con estado \"Falta\" indican que el promotor a\u00FAn no ha capturado ning\u00FAn inventario."),

      // ===== SECTION 8: REQUERIMIENTO DE DOTACIÓN =====
      new Paragraph({ children: [new PageBreak()] }),
      h1("8. Requerimiento de Dotaci\u00F3n"),
      p("La pantalla de Requerimiento de Dotaci\u00F3n permite al supervisor revisar y autorizar los requerimientos de abasto de leche que generan sus promotores. Cada requerimiento contiene las cantidades solicitadas por lecher\u00EDa agrupadas por almac\u00E9n."),
      p([n("C\u00F3mo acceder: "), b("Requerimiento de Dotaci\u00F3n"), n(" en la barra de navegaci\u00F3n.")]),
      imgP(imgs.requerimiento, 530, 350),
      fig("Figura 2.4 \u2014 Requerimiento de Dotaci\u00F3n"),

      h2("Pasos para revisar un requerimiento"),
      p([b("[1] "), n("Seleccione el per\u00EDodo (mes y a\u00F1o) del requerimiento que desea revisar.")]),
      p([b("[2] "), n("Revise la tabla con las cantidades solicitadas por cada promotor para cada almac\u00E9n.")]),
      p([b("[3] "), n("Si las cantidades son correctas, apruebe el requerimiento. Si necesita ajustes, puede editar los valores directamente desde esta pantalla.")]),
      p([b("[4] "), n("Haga clic en "), b("Guardar"), n(" para confirmar los cambios y enviar la autorizaci\u00F3n.")]),
      noteP("El requerimiento autorizado se enviar\u00E1 al \u00E1rea de Distribuci\u00F3n para que programe el surtimiento a las lecher\u00EDas."),

      // ===== SECTION 9: INVENTARIO DE ALMACÉN =====
      new Paragraph({ children: [new PageBreak()] }),
      h1("9. Inventario de Almac\u00E9n"),
      p("La pantalla de Inventario de Almac\u00E9n permite al supervisor generar un inventario consolidado de todas las lecher\u00EDas que surte un almac\u00E9n espec\u00EDfico. Este documento consolida la informaci\u00F3n de existencias, ventas y surtimientos de todas las lecher\u00EDas de la zona."),
      p([n("C\u00F3mo acceder: "), b("Inventario de Almac\u00E9n"), n(" en la barra de navegaci\u00F3n.")]),
      imgP(imgs.invAlmacen, 530, 350),
      fig("Figura 2.5 \u2014 Inventario de Almac\u00E9n"),

      h2("Pasos para generar el inventario de almac\u00E9n"),
      p([b("[1] "), n("Seleccione el "), b("Almac\u00E9n"), n(" del que desea generar el inventario.")]),
      p([b("[2] "), n("Seleccione el "), b("Mes"), n(" y "), b("A\u00F1o"), n(" del per\u00EDodo a consolidar.")]),
      p([b("[3] "), n("El sistema cargar\u00E1 autom\u00E1ticamente todas las lecher\u00EDas que pertenecen a ese almac\u00E9n con sus datos de inventario.")]),
      p([b("[4] "), n("Revise los datos consolidados y haga clic en "), b("Generar PDF"), n(" para descargar el documento.")]),
      noteP("El inventario de almac\u00E9n solo incluye lecher\u00EDas cuyos inventarios mensuales ya fueron guardados por los promotores. Verifique que todos los promotores hayan completado sus inventarios antes de generar este consolidado."),

      // ===== SECTION 10: LECHERÍAS =====
      new Paragraph({ children: [new PageBreak()] }),
      h1("10. Lecher\u00EDas"),
      p("La pantalla de Lecher\u00EDas muestra el cat\u00E1logo completo de lecher\u00EDas asignadas al supervisor. Desde aqu\u00ED puede consultar la informaci\u00F3n de cada lecher\u00EDa, el promotor asignado, el n\u00FAmero de beneficiarios y el estado de sus inventarios."),
      p([n("C\u00F3mo acceder: tarjeta "), b("Lecher\u00EDas"), n(" en la secci\u00F3n de Accesos r\u00E1pidos del Panel de Supervisi\u00F3n.")]),
      imgP(imgs.lecherias, 530, 350),
      fig("Figura 2.6 \u2014 Cat\u00E1logo de Lecher\u00EDas"),

      h2("Contenido de la pantalla"),
      p("La pantalla muestra una lista con todas las lecher\u00EDas bajo la supervisi\u00F3n del usuario en sesi\u00F3n. Para cada lecher\u00EDa se muestra: la clave del punto de venta, el nombre de la lecher\u00EDa, el municipio y la comunidad donde se ubica, el nombre del promotor asignado, y el n\u00FAmero de beneficiarios. Al hacer clic sobre una lecher\u00EDa puede ver su informaci\u00F3n detallada."),

      // ===== SECTION 11: SOLICITUDES =====
      new Paragraph({ children: [new PageBreak()] }),
      h1("11. Solicitudes"),
      p("La pantalla de Solicitudes muestra la bandeja de solicitudes de cambio enviadas por los promotores. Desde aqu\u00ED el supervisor puede aprobar o rechazar cada solicitud."),
      p([n("C\u00F3mo acceder: tarjeta "), b("Solicitudes"), n(" en la secci\u00F3n de Accesos r\u00E1pidos del Panel de Supervisi\u00F3n. El n\u00FAmero rojo indica solicitudes pendientes de atender.")]),
      imgP(imgs.solicitudes, 530, 350),
      fig("Figura 2.7 \u2014 Bandeja de Solicitudes"),

      h2("Contenido de la pantalla"),
      p("Cada solicitud aparece como una tarjeta que indica: el tipo de documento que el promotor desea modificar (Reporte o Requerimiento), el per\u00EDodo al que corresponde, el motivo escrito por el promotor y la fecha de env\u00EDo."),
      h2("Pasos para atender una solicitud"),
      p([b("[1] "), n("Revise el motivo de la solicitud del promotor.")]),
      p([b("[2] "), n("Si considera que la solicitud es v\u00E1lida, haga clic en "), b("Aprobar"), n(". El sistema desbloquear\u00E1 el documento para que el promotor lo edite.")]),
      p([b("[3] "), n("Si la solicitud no procede, haga clic en "), b("Rechazar"), n(" y escriba el motivo del rechazo. El promotor recibir\u00E1 la notificaci\u00F3n.")]),
      noteP("Las solicitudes pendientes aparecen con un indicador rojo en la tarjeta del Panel de Supervisi\u00F3n. At\u00E9ndalas lo antes posible para que los promotores puedan corregir sus documentos a tiempo."),

      // ===== SECTION 12: ESTADÍSTICAS DE ZONA =====
      new Paragraph({ children: [new PageBreak()] }),
      h1("12. Estad\u00EDsticas de Zona"),
      p("La pantalla de Estad\u00EDsticas de Zona muestra indicadores de rendimiento, distribuci\u00F3n y consumo de las lecher\u00EDas a cargo del supervisor. Incluye gr\u00E1ficos y tablas que permiten identificar tendencias, comparar el desempe\u00F1o entre promotores y detectar anomal\u00EDas."),
      p([n("C\u00F3mo acceder: tarjeta "), b("Estad\u00EDsticas de Zona"), n(" en la secci\u00F3n de Accesos r\u00E1pidos del Panel de Supervisi\u00F3n.")]),
      imgP(imgs.estadisticas, 530, 480),
      fig("Figura 2.8 \u2014 Estad\u00EDsticas de Zona"),

      h2("Contenido de la pantalla"),
      p("La pantalla presenta indicadores clave como: el total de lecher\u00EDas bajo su supervisi\u00F3n, el total de beneficiarios, el consumo promedio por lecher\u00EDa, el porcentaje de avance de inventarios del mes y comparativas entre per\u00EDodos. Los datos se actualizan en tiempo real con base en los inventarios capturados por los promotores."),

      // ===== SECTION 13: HISTORIAL GENERAL =====
      new Paragraph({ children: [new PageBreak()] }),
      h1("13. Historial General"),
      p("La pantalla de Historial General muestra el historial global de requerimientos de la zona del supervisor. Permite consultar todos los requerimientos generados por los promotores en per\u00EDodos anteriores para an\u00E1lisis y referencia."),
      p([n("C\u00F3mo acceder: tarjeta "), b("Historial General"), n(" en la secci\u00F3n de Accesos r\u00E1pidos del Panel de Supervisi\u00F3n.")]),
      imgP(imgs.historial, 530, 300),
      fig("Figura 2.9 \u2014 Historial General de Requerimientos"),

      h2("Contenido de la pantalla"),
      p("La pantalla muestra un listado cronol\u00F3gico de todos los requerimientos generados en la zona. Para cada requerimiento se indica: el per\u00EDodo, el promotor que lo gener\u00F3, la fecha de creaci\u00F3n, el estado (pendiente, aprobado, enviado) y la opci\u00F3n de descargar el PDF correspondiente. Puede filtrar por promotor, per\u00EDodo o estado usando los controles de la parte superior."),
      noteP("El historial es de solo lectura. Para modificar un requerimiento activo, utilice la pantalla de Requerimiento de Dotaci\u00F3n."),

    ] // end children
  }] // end sections
});

Packer.toBuffer(doc).then(buffer => {
  fs.writeFileSync(__dirname + "/Manual_Supervisor.docx", buffer);
  console.log("OK - Manual Supervisor generado exitosamente");
});
