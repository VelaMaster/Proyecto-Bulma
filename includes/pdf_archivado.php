<?php
// includes/pdf_archivado.php
// Helper común: guarda un PDF generado con FPDF a disco y registra metadatos en SQLite.
//
// Uso:
//   require_once __DIR__ . '/../includes/pdf_archivado.php';
//   archivarPdf($pdf, [
//       'tipo'     => 'reporte_mensual',           // identificador lógico
//       'modulo'   => 'supervisor',                // supervisor | distribucion | promotor
//       'subdir'   => 'reportes_mensuales',        // sub-carpeta bajo datos/<modulo>/
//       'mes'      => $mes,
//       'anio'     => $anio,
//       'usuario'  => $_SESSION['usuario'] ?? '',
//       'nombre'   => 'ReporteMensual_2026_05_marino.pdf',
//       'extras'   => ['total_lecherias'=>67, 'precio'=>'6.50'],
//   ]);
//
// Devuelve ['ruta'=>..., 'nombre'=>...]

require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

function archivarPdf(FPDF $pdf, array $opts): array
{
    $tipo    = (string)($opts['tipo']    ?? 'generico');
    $modulo  = (string)($opts['modulo']  ?? 'general');
    $subdir  = (string)($opts['subdir']  ?? $tipo);
    $mes     = (int)   ($opts['mes']     ?? 0);
    $anio    = (int)   ($opts['anio']    ?? 0);
    $usuario = (string)($opts['usuario'] ?? '');
    $nombre  = (string)($opts['nombre']  ?? '');
    $extras  = (array) ($opts['extras']  ?? []);

    if ($nombre === '') {
        $slug   = preg_replace('/[^A-Za-z0-9]/', '_', $usuario ?: 'anon');
        $nombre = sprintf('%s_%04d_%02d_%s.pdf', $tipo, $anio, $mes, $slug);
    }

    // Estructura: datos/<modulo>/<subdir>/AAAA-MM/<nombre>.pdf
    $carpetaMes = ($mes && $anio) ? sprintf('%04d-%02d', $anio, $mes) : 'sin_fecha';
    $baseDir = __DIR__ . '/../datos/' . $modulo . '/' . $subdir . '/' . $carpetaMes;
    if (!is_dir($baseDir)) @mkdir($baseDir, 0775, true);
    $rutaCompleta = $baseDir . '/' . $nombre;
    $rutaRelativa = 'datos/' . $modulo . '/' . $subdir . '/' . $carpetaMes . '/' . $nombre;

    // Vacía buffer de salida si está abierto, para que Output('F') no se contamine.
    if (ob_get_length()) { ob_end_clean(); }

    $pdf->Output('F', $rutaCompleta);

    // Registro en SQLite (no crítico)
    try {
        $db = DatabaseSQLite::getInstance();
        $db->exec("CREATE TABLE IF NOT EXISTS pdf_generados (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tipo TEXT NOT NULL,
            modulo TEXT NOT NULL,
            mes INTEGER, anio INTEGER,
            usuario TEXT,
            nombre_archivo TEXT,
            ruta_relativa TEXT,
            metadatos TEXT,
            fecha_generado TEXT DEFAULT (datetime('now','localtime'))
        )");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_pdfg_periodo
                   ON pdf_generados(tipo, anio, mes, usuario)");
        $stmt = $db->prepare("INSERT INTO pdf_generados
            (tipo, modulo, mes, anio, usuario, nombre_archivo, ruta_relativa, metadatos)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $tipo, $modulo, $mes ?: null, $anio ?: null, $usuario,
            $nombre, $rutaRelativa,
            json_encode($extras, JSON_UNESCAPED_UNICODE)
        ]);
    } catch (Throwable $e) { /* silencioso */ }

    return ['ruta' => $rutaCompleta, 'nombre' => $nombre, 'relativa' => $rutaRelativa];
}

// archivarArchivo: copia un archivo ya escrito en disco (XLSX/XLSM/CSV) a
// datos/<modulo>/<subdir>/AAAA-MM/<nombre> y registra metadatos.
// No elimina el original; el caller decide si lo envía al cliente y/o lo borra.
function archivarArchivo(string $rutaOrigen, array $opts): array
{
    if (!is_file($rutaOrigen)) {
        return ['ruta' => '', 'nombre' => '', 'relativa' => ''];
    }

    $tipo    = (string)($opts['tipo']    ?? 'archivo');
    $modulo  = (string)($opts['modulo']  ?? 'general');
    $subdir  = (string)($opts['subdir']  ?? $tipo);
    $mes     = (int)   ($opts['mes']     ?? 0);
    $anio    = (int)   ($opts['anio']    ?? 0);
    $usuario = (string)($opts['usuario'] ?? '');
    $nombre  = (string)($opts['nombre']  ?? basename($rutaOrigen));
    $extras  = (array) ($opts['extras']  ?? []);

    $carpetaMes = ($mes && $anio) ? sprintf('%04d-%02d', $anio, $mes) : 'sin_fecha';
    $baseDir = __DIR__ . '/../datos/' . $modulo . '/' . $subdir . '/' . $carpetaMes;
    if (!is_dir($baseDir)) @mkdir($baseDir, 0775, true);
    $rutaCompleta = $baseDir . '/' . $nombre;
    $rutaRelativa = 'datos/' . $modulo . '/' . $subdir . '/' . $carpetaMes . '/' . $nombre;

    @copy($rutaOrigen, $rutaCompleta);

    try {
        $db = DatabaseSQLite::getInstance();
        $db->exec("CREATE TABLE IF NOT EXISTS pdf_generados (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tipo TEXT NOT NULL,
            modulo TEXT NOT NULL,
            mes INTEGER, anio INTEGER,
            usuario TEXT,
            nombre_archivo TEXT,
            ruta_relativa TEXT,
            metadatos TEXT,
            fecha_generado TEXT DEFAULT (datetime('now','localtime'))
        )");
        $stmt = $db->prepare("INSERT INTO pdf_generados
            (tipo, modulo, mes, anio, usuario, nombre_archivo, ruta_relativa, metadatos)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $tipo, $modulo, $mes ?: null, $anio ?: null, $usuario,
            $nombre, $rutaRelativa,
            json_encode($extras, JSON_UNESCAPED_UNICODE)
        ]);
    } catch (Throwable $e) { /* silencioso */ }

    return ['ruta' => $rutaCompleta, 'nombre' => $nombre, 'relativa' => $rutaRelativa];
}
