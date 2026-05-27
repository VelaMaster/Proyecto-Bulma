<?php
/**
 * DatabaseSQLite.php
 * Singleton PDO → SQLite para tablas propias del sistema
 * (reporte mensual, requerimiento de dotación).
 *
 * Archivo:  datos/local.db  (junto a datos/sesiones/)
 * Se crea automáticamente con los schemas en la primera llamada.
 */
class DatabaseSQLite
{
    private static ?PDO $pdo = null;
    private static string $dbPath = '';

    public static function getInstance(): PDO
    {
        if (self::$pdo !== null) return self::$pdo;

        $dir = dirname(__DIR__, 2) . '/datos';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);

        self::$dbPath = $dir . '/local.db';

        self::$pdo = new PDO('sqlite:' . self::$dbPath);
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        self::$pdo->exec('PRAGMA journal_mode=WAL');   // escrituras concurrentes seguras
        self::$pdo->exec('PRAGMA foreign_keys=ON');

        self::crearEsquema(self::$pdo);
        return self::$pdo;
    }

    private static function crearEsquema(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS reporte_mensual_lecher (
                clave_lecheria    TEXT    NOT NULL,
                mes               INTEGER NOT NULL,
                anio              INTEGER NOT NULL,
                almacen           TEXT,
                precio            TEXT,
                inv_ini_cajas     INTEGER DEFAULT 0,
                inv_ini_sobres    INTEGER DEFAULT 0,
                dot_recib_cajas   INTEGER DEFAULT 0,
                total_cajas       INTEGER DEFAULT 0,
                total_sobres      INTEGER DEFAULT 0,
                vend_cajas        INTEGER DEFAULT 0,
                vend_sobres       INTEGER DEFAULT 0,
                inv_fin_cajas     INTEGER DEFAULT 0,
                inv_fin_sobres    INTEGER DEFAULT 0,
                retiro_cajas      INTEGER DEFAULT 0,
                retiro_sobres     INTEGER DEFAULT 0,
                familias_no_acud  INTEGER DEFAULT 0,
                sobres_rotos      INTEGER DEFAULT 0,
                sobres_falt       INTEGER DEFAULT 0,
                observaciones     TEXT,
                periodo_inicio    TEXT,
                periodo_fin       TEXT,
                promotor          TEXT,
                supervisor        TEXT,
                usuario_captura   TEXT,
                fecha_captura     TEXT DEFAULT (datetime('now','localtime')),
                PRIMARY KEY (clave_lecheria, mes, anio)
            );

            CREATE TABLE IF NOT EXISTS requerimiento_dotacion (
                clave_lecheria    TEXT    NOT NULL,
                mes_base          INTEGER NOT NULL,
                anio_base         INTEGER NOT NULL,
                promotor          INTEGER,
                mes_destino       INTEGER,
                anio_destino      INTEGER,
                familias          INTEGER DEFAULT 0,
                beneficiarios     INTEGER DEFAULT 0,
                dotacion_teorica  INTEGER DEFAULT 0,
                inv_inicial       TEXT,
                surtimiento       INTEGER DEFAULT 0,
                ventas            TEXT,
                inv_final         TEXT,
                req_ms_anterior   INTEGER DEFAULT 0,
                vms               INTEGER DEFAULT 0,
                req_actual        INTEGER DEFAULT 0,
                observaciones     TEXT,
                usuario_captura   TEXT,
                fecha_captura     TEXT DEFAULT (datetime('now','localtime')),
                PRIMARY KEY (clave_lecheria, mes_base, anio_base)
            );

            CREATE INDEX IF NOT EXISTS idx_rpt_mes_usr
                ON reporte_mensual_lecher (mes, anio, usuario_captura);

            CREATE INDEX IF NOT EXISTS idx_req_mes
                ON requerimiento_dotacion (mes_base, anio_base);

            CREATE INDEX IF NOT EXISTS idx_req_promotor
                ON requerimiento_dotacion (promotor);
        ");

        // Migraciones idempotentes: agregar columnas nuevas si no existen
        foreach ([
            "ALTER TABLE reporte_mensual_lecher  ADD COLUMN pdf_nombre TEXT",
            "ALTER TABLE requerimiento_dotacion  ADD COLUMN pdf_nombre TEXT",
        ] as $alter) {
            try { $pdo->exec($alter); } catch (\Throwable $e) { /* columna ya existe */ }
        }
    }

    /** Ruta al archivo .db (útil para backups) */
    public static function getPath(): string { return self::$dbPath; }
}
