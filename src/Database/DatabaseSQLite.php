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

        // ── Tablas del supervisor ─────────────────────────────────────
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS requerimiento_supervisor (
                id               INTEGER PRIMARY KEY AUTOINCREMENT,
                mes              INTEGER NOT NULL,
                anio             INTEGER NOT NULL,
                precio           TEXT    NOT NULL,
                supervisor_usr   TEXT    NOT NULL,
                pdf_nombre       TEXT,
                total_general    INTEGER DEFAULT 0,
                total_lecherias  INTEGER DEFAULT 0,
                fecha_captura    TEXT DEFAULT (datetime('now','localtime')),
                UNIQUE (mes, anio, precio, supervisor_usr)
            );

            CREATE TABLE IF NOT EXISTS solicitudes_cambio (
                id               INTEGER PRIMARY KEY AUTOINCREMENT,
                tipo             TEXT NOT NULL,  -- 'reporte' | 'requerimiento'
                clave_lecheria   TEXT NOT NULL,
                mes              INTEGER NOT NULL,
                anio             INTEGER NOT NULL,
                promotor_usr     TEXT NOT NULL,
                supervisor_clave INTEGER,
                motivo           TEXT,
                estado           TEXT DEFAULT 'pendiente',  -- pendiente|en_proceso|resuelto|rechazado
                nota_supervisor  TEXT,
                fecha_solicitud  TEXT DEFAULT (datetime('now','localtime')),
                fecha_resolucion TEXT
            );

            CREATE INDEX IF NOT EXISTS idx_sol_supervisor
                ON solicitudes_cambio (supervisor_clave, estado);

            CREATE INDEX IF NOT EXISTS idx_sol_promotor
                ON solicitudes_cambio (promotor_usr, estado);
        ");

        // Migraciones idempotentes: agregar columnas nuevas si no existen
        foreach ([
            "ALTER TABLE reporte_mensual_lecher  ADD COLUMN pdf_nombre   TEXT",
            "ALTER TABLE requerimiento_dotacion  ADD COLUMN pdf_nombre   TEXT",
            "ALTER TABLE reporte_mensual_lecher  ADD COLUMN bloqueado    INTEGER DEFAULT 0",
            "ALTER TABLE requerimiento_dotacion  ADD COLUMN bloqueado    INTEGER DEFAULT 0",
            // Fase 3 — espejo de Firebird
            "ALTER TABLE lecheria                ADD COLUMN EN_OPERACION INTEGER DEFAULT 0",
            "ALTER TABLE lecheria                ADD COLUMN SUPERVISOR   INTEGER",
            "ALTER TABLE lecheria                ADD COLUMN RESSURTI     INTEGER",
            "ALTER TABLE usuarios_inventarios    ADD COLUMN ACTIVO       INTEGER DEFAULT 1",
        ] as $alter) {
            try { $pdo->exec($alter); } catch (\Throwable $e) { /* columna ya existe */ }
        }

        // ── Espejo de Firebird (poblado por el sincronizador admin) ───
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS lecheria (
                LECHER             INTEGER PRIMARY KEY,
                NOMBRELECH         TEXT,
                EFD_NUMERO         INTEGER,
                MUN_NUMERO         INTEGER,
                LOC_NUMERO         INTEGER,
                NUM_TIENDA         TEXT,
                TIPO_PUNTO_VENTA   TEXT,
                ALMACEN_RURAL      TEXT,
                PROMOTOR           INTEGER,
                SUPERVISOR         INTEGER,
                CC_FAM             INTEGER DEFAULT 0,
                CC_BT1             INTEGER DEFAULT 0,
                CC_BT2             INTEGER DEFAULT 0,
                CC_BT3             INTEGER DEFAULT 0,
                CC_BT4             INTEGER DEFAULT 0,
                CC_BT5             INTEGER DEFAULT 0,
                CC_BT6             INTEGER DEFAULT 0,
                CC_BT7             INTEGER DEFAULT 0,
                EN_OPERACION       INTEGER DEFAULT 0,
                synced_at          TEXT DEFAULT (datetime('now','localtime'))
            );

            CREATE INDEX IF NOT EXISTS idx_lecheria_promotor ON lecheria (PROMOTOR);
            CREATE INDEX IF NOT EXISTS idx_lecheria_supervisor ON lecheria (SUPERVISOR);
            CREATE INDEX IF NOT EXISTS idx_lecheria_almacen ON lecheria (ALMACEN_RURAL);

            CREATE TABLE IF NOT EXISTS usuarios_inventarios (
                USUARIO            TEXT PRIMARY KEY,
                CONTRASENA         TEXT,
                NOMBRE             TEXT,
                ROL                TEXT,        -- promotor | supervisor | distribucion
                CLAVE_ROL          INTEGER,
                ACTIVO             INTEGER DEFAULT 1,
                synced_at          TEXT DEFAULT (datetime('now','localtime'))
            );

            CREATE INDEX IF NOT EXISTS idx_usr_rol ON usuarios_inventarios (ROL);

            CREATE TABLE IF NOT EXISTS municipio (
                EFD_NUMERO         INTEGER NOT NULL,
                MUN_NUMERO         INTEGER NOT NULL,
                MUN_DESCRIPCION    TEXT,
                synced_at          TEXT DEFAULT (datetime('now','localtime')),
                PRIMARY KEY (EFD_NUMERO, MUN_NUMERO)
            );

            CREATE TABLE IF NOT EXISTS localidad (
                EFD_NUMERO         INTEGER NOT NULL,
                MUN_NUMERO         INTEGER NOT NULL,
                LOC_NUMERO         INTEGER NOT NULL,
                LOC_DESCRIPCION    TEXT,
                synced_at          TEXT DEFAULT (datetime('now','localtime')),
                PRIMARY KEY (EFD_NUMERO, MUN_NUMERO, LOC_NUMERO)
            );

            CREATE TABLE IF NOT EXISTS promotor (
                PMT_NUMERO         INTEGER PRIMARY KEY,
                PMT_NOMBRE         TEXT,
                PMT_ACTIVO         TEXT,         -- 'S' | 'N'
                synced_at          TEXT DEFAULT (datetime('now','localtime'))
            );

            CREATE TABLE IF NOT EXISTS supervisor (
                ID_SUPERVISOR      INTEGER PRIMARY KEY,
                NOMBRE_SUPERVISOR  TEXT,
                synced_at          TEXT DEFAULT (datetime('now','localtime'))
            );

            CREATE TABLE IF NOT EXISTS mapeo_supervisor_lecheria (
                ID_SUPERVISOR      INTEGER NOT NULL,
                LECHER             INTEGER NOT NULL,
                synced_at          TEXT DEFAULT (datetime('now','localtime')),
                PRIMARY KEY (ID_SUPERVISOR, LECHER)
            );

            CREATE INDEX IF NOT EXISTS idx_mapeo_supervisor ON mapeo_supervisor_lecheria (ID_SUPERVISOR);
            CREATE INDEX IF NOT EXISTS idx_mapeo_lecher     ON mapeo_supervisor_lecheria (LECHER);

            -- ── INVENTARIOS_MENSUALES — transaccional, sync MERGE no replace ──
            CREATE TABLE IF NOT EXISTS inventarios_mensuales (
                ID                INTEGER PRIMARY KEY AUTOINCREMENT,
                CLAVE_LECHERIA    TEXT    NOT NULL,
                MES_PERIODO       INTEGER NOT NULL,
                ANIO_PERIODO      INTEGER NOT NULL,
                FECHA             TEXT,
                CLAVE_TIENDA      TEXT,
                ALMACEN           TEXT,
                MUNICIPIO         TEXT,
                COMUNIDAD         TEXT,
                PRECIO            TEXT,
                HOGARES           INTEGER DEFAULT 0,
                MENORES           INTEGER DEFAULT 0,
                MAYORES           INTEGER DEFAULT 0,
                INV_INI_CAJA      INTEGER DEFAULT 0,
                INV_INI_SOBRES    INTEGER DEFAULT 0,
                INV_INI_LITROS    INTEGER DEFAULT 0,
                SURT_CAJAS        INTEGER DEFAULT 0,
                SURT_LITROS       INTEGER DEFAULT 0,
                SURT_FECHA        TEXT,
                SURT_FACTURA      TEXT,
                SURT_CADUCIDAD    TEXT,
                ABASTO_CAJA       INTEGER DEFAULT 0,
                ABASTO_SOBRES     INTEGER DEFAULT 0,
                ABASTO_LITROS     INTEGER DEFAULT 0,
                VENTA_CAJA        INTEGER DEFAULT 0,
                VENTA_SOBRES      INTEGER DEFAULT 0,
                VENTA_LITROS      INTEGER DEFAULT 0,
                REG_CAJA          INTEGER DEFAULT 0,
                REG_SOBRES        INTEGER DEFAULT 0,
                REG_LITROS        INTEGER DEFAULT 0,
                DIF_CAJA          INTEGER DEFAULT 0,
                DIF_SOBRES        INTEGER DEFAULT 0,
                DIF_LITROS        INTEGER DEFAULT 0,
                FIN_CAJA          INTEGER DEFAULT 0,
                FIN_SOBRES        INTEGER DEFAULT 0,
                FIN_LITROS        INTEGER DEFAULT 0,
                ESTADO            TEXT,
                PDF_RUTA          TEXT,
                USUARIO_CAPTURA   TEXT,
                FECHA_CAPTURA     TEXT DEFAULT (datetime('now','localtime')),
                UNIQUE (CLAVE_LECHERIA, MES_PERIODO, ANIO_PERIODO)
            );

            CREATE INDEX IF NOT EXISTS idx_inv_mes ON inventarios_mensuales (MES_PERIODO, ANIO_PERIODO);
            CREATE INDEX IF NOT EXISTS idx_inv_lecher ON inventarios_mensuales (CLAVE_LECHERIA);
        ");

        // ── Configuración admin (Firebird) + logs ─────────────────────
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_config (
                clave   TEXT PRIMARY KEY,
                valor   TEXT
            );

            CREATE TABLE IF NOT EXISTS errores_log (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                tipo        TEXT,        -- php | pdo | sync | app
                nivel       TEXT,        -- error|warning|notice|info
                mensaje     TEXT,
                archivo     TEXT,
                linea       INTEGER,
                contexto    TEXT,        -- JSON
                usuario     TEXT,
                fecha       TEXT DEFAULT (datetime('now','localtime'))
            );

            CREATE INDEX IF NOT EXISTS idx_err_fecha ON errores_log (fecha);
            CREATE INDEX IF NOT EXISTS idx_err_tipo  ON errores_log (tipo);

            CREATE TABLE IF NOT EXISTS sync_log (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                tabla       TEXT,
                filas       INTEGER DEFAULT 0,
                duracion_ms INTEGER DEFAULT 0,
                ok          INTEGER DEFAULT 0,
                mensaje     TEXT,
                fecha       TEXT DEFAULT (datetime('now','localtime'))
            );
        ");

        // Valores por defecto de Firebird (sobreescribibles desde /admin/config)
        $defaults = [
            'fb_host'    => '172.24.10.251',
            'fb_port'    => '3050',
            'fb_user'    => 'SYSDBA',
            'fb_pass'    => '290990',
            'fb_db_path' => 'C:/SisDLL20/BD/DB_SIDIST.FDB',
            'fb_charset' => 'NONE',
        ];
        $ins = $pdo->prepare("INSERT OR IGNORE INTO admin_config (clave, valor) VALUES (?, ?)");
        foreach ($defaults as $k => $v) $ins->execute([$k, $v]);
    }

    /** Lee un valor de admin_config */
    public static function getConfig(string $clave, ?string $default = null): ?string
    {
        $st = self::getInstance()->prepare("SELECT valor FROM admin_config WHERE clave=?");
        $st->execute([$clave]);
        $v = $st->fetchColumn();
        return $v !== false ? $v : $default;
    }

    /** Escribe un valor en admin_config (upsert) */
    public static function setConfig(string $clave, string $valor): void
    {
        self::getInstance()->prepare(
            "INSERT INTO admin_config (clave, valor) VALUES (?, ?)
             ON CONFLICT(clave) DO UPDATE SET valor=excluded.valor"
        )->execute([$clave, $valor]);
    }

    /** Ruta al archivo .db (útil para backups) */
    public static function getPath(): string { return self::$dbPath; }
}
