<?php
require_once __DIR__ . '/../Database/DatabaseSQLite.php';
require_once __DIR__ . '/LoggerErrores.php';
require_once __DIR__ . '/PasswordServicio.php';

/**
 * SincronizadorFirebird
 * Copia tablas catálogo de Firebird → SQLite usando la configuración guardada
 * en admin_config (host, puerto, usuario, contraseña, ruta de BD).
 *
 * Uso:
 *   $sync = new SincronizadorFirebird();
 *   $sync->sincronizarTodo();              // todas las tablas catálogo
 *   $sync->sincronizar('lecheria');        // una sola
 */
class SincronizadorFirebird
{
    /** Mapa lógico tabla → callback que la sincroniza. */
    private array $mapa;

    /** Entorno resuelto en la última conexión: 'liconsa' | 'docker' | null. */
    private static ?string $envCodigo = null;
    private static string  $envNombre = 'Desconocido';
    private static string  $envHost   = '';
    private static string  $envDbPath = '';

    public function __construct()
    {
        $this->mapa = [
            'usuarios_inventarios'      => fn($fb, $sqlite) => $this->_syncUsuarios($fb, $sqlite),
            'promotor'                  => fn($fb, $sqlite) => $this->_syncPromotor($fb, $sqlite),
            'supervisor'                => fn($fb, $sqlite) => $this->_syncSupervisor($fb, $sqlite),
            'municipio'                 => fn($fb, $sqlite) => $this->_syncMunicipio($fb, $sqlite),
            'localidad'                 => fn($fb, $sqlite) => $this->_syncLocalidad($fb, $sqlite),
            'lecheria'                  => fn($fb, $sqlite) => $this->_syncLecheria($fb, $sqlite),
            'mapeo_supervisor_lecheria' => fn($fb, $sqlite) => $this->_syncMapeo($fb, $sqlite),
            'inventarios_mensuales'     => fn($fb, $sqlite) => $this->_syncInventariosMensuales($fb, $sqlite),
        ];
    }

    /** Tablas disponibles para sincronizar. */
    public function tablas(): array
    {
        return array_keys($this->mapa);
    }

    /** Sincroniza todas las tablas, en orden. Devuelve array de resultados por tabla. */
    public function sincronizarTodo(): array
    {
        $resultados = [];
        foreach ($this->tablas() as $t) {
            $resultados[$t] = $this->sincronizar($t);
        }
        return $resultados;
    }

    /** Sincroniza una sola tabla por nombre lógico. */
    public function sincronizar(string $tabla): array
    {
        if (!isset($this->mapa[$tabla])) {
            return ['ok' => false, 'filas' => 0, 'duracion_ms' => 0, 'mensaje' => "Tabla desconocida: $tabla"];
        }
        $t0 = microtime(true);
        try {
            $fb     = $this->conectarFirebird();
            $sqlite = DatabaseSQLite::getInstance();
            $filas  = ($this->mapa[$tabla])($fb, $sqlite);
            $dur    = (int)((microtime(true) - $t0) * 1000);
            LoggerErrores::registrarSync($tabla, $filas, $dur, true, 'OK');
            DatabaseSQLite::setConfig("last_sync_$tabla", date('Y-m-d H:i:s'));
            return ['ok' => true, 'filas' => $filas, 'duracion_ms' => $dur, 'mensaje' => 'OK'];
        } catch (\Throwable $e) {
            $dur = (int)((microtime(true) - $t0) * 1000);
            LoggerErrores::registrarSync($tabla, 0, $dur, false, $e->getMessage());
            LoggerErrores::registrar('sync', 'error', $e->getMessage(), $e->getFile(), $e->getLine());
            return ['ok' => false, 'filas' => 0, 'duracion_ms' => $dur, 'mensaje' => $e->getMessage()];
        }
    }

    /** Prueba la conexión a Firebird con la config actual. */
    public function probarConexion(): array
    {
        $t0 = microtime(true);
        try {
            $pdo = $this->conectarFirebird();
            // Query trivial
            $pdo->query("SELECT 1 FROM RDB\$DATABASE")->fetch();
            return ['ok' => true, 'duracion_ms' => (int)((microtime(true)-$t0)*1000), 'mensaje' => 'Conexión exitosa'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'duracion_ms' => (int)((microtime(true)-$t0)*1000), 'mensaje' => $e->getMessage()];
        }
    }

    /* ────────────────────────────────────────────────────────────── */
    /*  Conexión a Firebird — Liconsa real primero, Docker fallback   */
    /* ────────────────────────────────────────────────────────────── */
    /**
     * Igual que Database::getInstance(): intenta primero el servidor real
     * de Liconsa (172.24.10.251 / DB_SIDIST.FDB / pass 290990). Si no
     * responde en 0.3 s, cae al Firebird del contenedor Docker
     * (host 'db' / DB_SIDISTLOCAL.FDB / masterkey).
     *
     * El admin puede sobreescribir el host remoto desde /admin/config.php
     * guardando 'fb_host', 'fb_port', etc. en admin_config; si no hay
     * configuración, se usan los defaults institucionales.
     */
    private function conectarFirebird(): PDO
    {
        $hostRemoto = DatabaseSQLite::getConfig('fb_host_remoto', '172.24.10.251');
        $portRemoto = DatabaseSQLite::getConfig('fb_port',        '3050');
        $hostLocal  = DatabaseSQLite::getConfig('fb_host_local',  'db');
        $charset    = DatabaseSQLite::getConfig('fb_charset',     'NONE');

        // Cache de 5 min para no pagar fsockopen en cada sync — mismo patrón
        // que Database.php para mantener el comportamiento idéntico.
        $cacheFile = sys_get_temp_dir() . '/liconsa_dbhost.cache';
        $useRemote = false;
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
            $useRemote = (trim((string)@file_get_contents($cacheFile)) === '1');
        } else {
            $sock = @fsockopen($hostRemoto, (int)$portRemoto, $errno, $errstr, 0.3);
            $useRemote = (bool)$sock;
            if ($sock) fclose($sock);
            @file_put_contents($cacheFile, $useRemote ? '1' : '0');
        }

        if ($useRemote) {
            $host    = $hostRemoto;
            $dbPath  = DatabaseSQLite::getConfig('fb_db_path_remoto', 'C:/SisDLL20/BD/DB_SIDIST.FDB');
            $user    = DatabaseSQLite::getConfig('fb_user_remoto',   'SYSDBA');
            $pass    = DatabaseSQLite::getConfig('fb_pass_remoto',   '290990');
            self::$envCodigo = 'liconsa';
            self::$envNombre = 'SERVIDOR REAL (LICONSA)';
        } else {
            $host    = $hostLocal;
            $dbPath  = DatabaseSQLite::getConfig('fb_db_path_local', '/firebird/data/DB_SIDISTLOCAL.FDB');
            $user    = DatabaseSQLite::getConfig('fb_user_local',   'SYSDBA');
            $pass    = DatabaseSQLite::getConfig('fb_pass_local',   'masterkey');
            self::$envCodigo = 'docker';
            self::$envNombre = 'DOCKER LOCAL (PRUEBAS)';
        }
        self::$envHost   = $host;
        self::$envDbPath = $dbPath;

        try {
            $dsn = "firebird:dbname=$host/$portRemoto:$dbPath;charset=$charset";
            return new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            // Si falla el remoto cacheado, limpia cache y reintenta con local.
            if ($useRemote) {
                @unlink($cacheFile);
                self::$envCodigo = null;
                self::$envNombre = 'Desconocido';
                return $this->conectarFirebird();
            }
            throw $e;
        }
    }

    /** Devuelve metadatos del entorno Firebird al que se conectó por última vez. */
    public function entornoActual(): array
    {
        if (self::$envCodigo === null) {
            // Forzar una resolución silenciosa probando socket sin abrir PDO.
            $this->probarConexion();
        }
        return [
            'codigo' => self::$envCodigo ?? 'desconocido',
            'nombre' => self::$envNombre,
            'host'   => self::$envHost,
            'bdd'    => self::$envDbPath,
        ];
    }

    /* ────────────────────────────────────────────────────────────── */
    /*  Tablas individuales                                           */
    /* ────────────────────────────────────────────────────────────── */
    private function _syncUsuarios(PDO $fb, PDO $sqlite): int
    {
        // Firebird real no tiene columna ACTIVO. En SQLite la columna existe con DEFAULT 1.
        // UPSERT (NO truncar):
        //   • Usuarios locales (sin equivalente en Firebird) se preservan.
        //   • Si el usuario ya existe en SQLite y CONTRASENA local YA es hash → NO se sobrescribe
        //     (evita perder la migración a password_hash; RNF-08).
        //   • Si el usuario es nuevo o la local sigue en texto plano → se hashea la de Firebird al insertar.
        $rows = $fb->query("SELECT USUARIO, CONTRASENA, NOMBRE, ROL, CLAVE_ROL FROM USUARIOS_INVENTARIOS")
                   ->fetchAll();

        $sqlite->beginTransaction();
        try {
            $sel = $sqlite->prepare("SELECT CONTRASENA FROM usuarios_inventarios WHERE USUARIO = :u");
            $ins = $sqlite->prepare(
                "INSERT INTO usuarios_inventarios (USUARIO, CONTRASENA, NOMBRE, ROL, CLAVE_ROL)
                 VALUES (:u, :c, :n, :r, :k)
                 ON CONFLICT(USUARIO) DO UPDATE SET
                    CONTRASENA = CASE
                                    WHEN length(usuarios_inventarios.CONTRASENA) >= 50
                                         AND usuarios_inventarios.CONTRASENA LIKE '$%'
                                    THEN usuarios_inventarios.CONTRASENA
                                    ELSE excluded.CONTRASENA
                                 END,
                    NOMBRE     = excluded.NOMBRE,
                    ROL        = excluded.ROL,
                    CLAVE_ROL  = excluded.CLAVE_ROL"
            );

            $n = 0;
            foreach ($rows as $r) {
                $usuario = trim((string)($r['USUARIO']    ?? ''));
                $passFb  = trim((string)($r['CONTRASENA'] ?? ''));
                $nombre  = trim((string)($r['NOMBRE']     ?? ''));
                $rol     = trim((string)($r['ROL']        ?? ''));
                $clave   = $r['CLAVE_ROL'] ?? null;
                if ($usuario === '') continue;

                // Hashea la contraseña de Firebird (sólo se usará si la local no era hash).
                $passParaInsertar = $passFb !== '' ? PasswordServicio::hashear($passFb) : '';

                $ins->execute([
                    ':u' => $usuario,
                    ':c' => $passParaInsertar,
                    ':n' => $nombre,
                    ':r' => $rol,
                    ':k' => $clave,
                ]);
                $n++;
            }
            $sqlite->commit();
            return $n;
        } catch (\Throwable $e) {
            $sqlite->rollBack();
            throw $e;
        }
    }

    private function _syncMunicipio(PDO $fb, PDO $sqlite): int
    {
        $rows = $fb->query("SELECT EFD_NUMERO, MUN_NUMERO, MUN_DESCRIPCION FROM MUNICIPIO WHERE EFD_NUMERO=20")
                   ->fetchAll();
        return $this->_reemplazarTabla($sqlite, 'municipio',
            ['EFD_NUMERO','MUN_NUMERO','MUN_DESCRIPCION'], $rows);
    }

    private function _syncLocalidad(PDO $fb, PDO $sqlite): int
    {
        $rows = $fb->query("SELECT EFD_NUMERO, MUN_NUMERO, LOC_NUMERO, LOC_DESCRIPCION FROM LOCALIDAD WHERE EFD_NUMERO=20")
                   ->fetchAll();
        return $this->_reemplazarTabla($sqlite, 'localidad',
            ['EFD_NUMERO','MUN_NUMERO','LOC_NUMERO','LOC_DESCRIPCION'], $rows);
    }

    private function _syncLecheria(PDO $fb, PDO $sqlite): int
    {
        // LECHERIA en Firebird real no tiene columna SUPERVISOR — la relación
        // supervisor↔lechería viene por MAPEO_SUPERVISOR_LECHERIA.
        $sql = "SELECT LECHER, NOMBRELECH, EFD_NUMERO, MUN_NUMERO, LOC_NUMERO,
                       NUM_TIENDA, TIPO_PUNTO_VENTA, ALMACEN_RURAL,
                       PROMOTOR, RESSURTI,
                       CC_FAM, CC_BT1, CC_BT2, CC_BT3, CC_BT4, CC_BT5, CC_BT6, CC_BT7,
                       EN_OPERACION
                FROM LECHERIA WHERE EFD_NUMERO=20";
        $rows = $fb->query($sql)->fetchAll();
        return $this->_reemplazarTabla($sqlite, 'lecheria',
            ['LECHER','NOMBRELECH','EFD_NUMERO','MUN_NUMERO','LOC_NUMERO',
             'NUM_TIENDA','TIPO_PUNTO_VENTA','ALMACEN_RURAL',
             'PROMOTOR','RESSURTI',
             'CC_FAM','CC_BT1','CC_BT2','CC_BT3','CC_BT4','CC_BT5','CC_BT6','CC_BT7',
             'EN_OPERACION'],
            $rows);
    }

    private function _syncPromotor(PDO $fb, PDO $sqlite): int
    {
        // UPSERT: NO truncar. Solo actualiza NOMBRE; preserva PMT_ACTIVO local
        // (los activos los maneja el concentrado XLSX, no Firebird).
        $rows = $fb->query("SELECT PMT_NUMERO, PMT_NOMBRE, PMT_ACTIVO FROM PROMOTOR")->fetchAll();
        $sqlite->beginTransaction();
        try {
            $stmt = $sqlite->prepare(
                "INSERT INTO promotor (PMT_NUMERO, PMT_NOMBRE, PMT_ACTIVO) VALUES (?,?,?)
                 ON CONFLICT(PMT_NUMERO) DO UPDATE SET PMT_NOMBRE=excluded.PMT_NOMBRE"
            );
            $n = 0;
            foreach ($rows as $r) {
                $stmt->execute([
                    $r['PMT_NUMERO'],
                    is_string($r['PMT_NOMBRE']) ? trim($r['PMT_NOMBRE']) : $r['PMT_NOMBRE'],
                    is_string($r['PMT_ACTIVO']) ? trim($r['PMT_ACTIVO']) : $r['PMT_ACTIVO'],
                ]);
                $n++;
            }
            $sqlite->commit();
            return $n;
        } catch (\Throwable $e) { $sqlite->rollBack(); throw $e; }
    }

    private function _syncSupervisor(PDO $fb, PDO $sqlite): int
    {
        // UPSERT: NO truncar. Solo actualiza NOMBRE; preserva supervisor.ACTIVO local
        // y los supervisores agregados manualmente desde el concentrado XLSX.
        $rows = $fb->query("SELECT ID_SUPERVISOR, NOMBRE_SUPERVISOR FROM SUPERVISOR")->fetchAll();
        $sqlite->beginTransaction();
        try {
            $stmt = $sqlite->prepare(
                "INSERT INTO supervisor (ID_SUPERVISOR, NOMBRE_SUPERVISOR) VALUES (?,?)
                 ON CONFLICT(ID_SUPERVISOR) DO UPDATE SET NOMBRE_SUPERVISOR=excluded.NOMBRE_SUPERVISOR"
            );
            $n = 0;
            foreach ($rows as $r) {
                $stmt->execute([
                    $r['ID_SUPERVISOR'],
                    is_string($r['NOMBRE_SUPERVISOR']) ? trim($r['NOMBRE_SUPERVISOR']) : $r['NOMBRE_SUPERVISOR'],
                ]);
                $n++;
            }
            $sqlite->commit();
            return $n;
        } catch (\Throwable $e) { $sqlite->rollBack(); throw $e; }
    }

    private function _syncMapeo(PDO $fb, PDO $sqlite): int
    {
        // INSERT OR IGNORE: NO truncar. La verdad del mapeo vive en SQLite (XLSX
        // concentrado + lecheria.PROMOTOR); Firebird sólo aporta filas nuevas
        // que no contradigan las locales. Las correcciones manuales se preservan.
        $rows = $fb->query("SELECT ID_SUPERVISOR, LECHER FROM MAPEO_SUPERVISOR_LECHERIA")->fetchAll();
        $sqlite->beginTransaction();
        try {
            $stmt = $sqlite->prepare(
                "INSERT OR IGNORE INTO mapeo_supervisor_lecheria (ID_SUPERVISOR, LECHER) VALUES (?, ?)"
            );
            $n = 0;
            foreach ($rows as $r) {
                $stmt->execute([$r['ID_SUPERVISOR'], $r['LECHER']]);
                $n++;
            }
            $sqlite->commit();
            return $n;
        } catch (\Throwable $e) { $sqlite->rollBack(); throw $e; }
    }

    /**
     * INVENTARIOS_MENSUALES — tabla transaccional.
     * Modo MERGE (INSERT OR REPLACE por CLAVE_LECHERIA+MES+ANIO). NO trunca.
     * Esto importa data legacy de Firebird sin pisar capturas hechas localmente
     * cuyo (clave, mes, año) no existan en Firebird.
     */
    private function _syncInventariosMensuales(PDO $fb, PDO $sqlite): int
    {
        // Columnas que SÍ existen en Firebird real (PRECIO no existe; el usuario se llama USUARIO).
        $colsFB = [
            'CLAVE_LECHERIA','MES_PERIODO','ANIO_PERIODO','FECHA',
            'CLAVE_TIENDA','ALMACEN','MUNICIPIO','COMUNIDAD',
            'HOGARES','MENORES','MAYORES',
            'INV_INI_CAJA','INV_INI_SOBRES','INV_INI_LITROS',
            'SURT_CAJAS','SURT_LITROS','SURT_FECHA','SURT_FACTURA','SURT_CADUCIDAD',
            'ABASTO_CAJA','ABASTO_SOBRES','ABASTO_LITROS',
            'VENTA_CAJA','VENTA_SOBRES','VENTA_LITROS',
            'REG_CAJA','REG_SOBRES','REG_LITROS',
            'DIF_CAJA','DIF_SOBRES','DIF_LITROS',
            'FIN_CAJA','FIN_SOBRES','FIN_LITROS',
            'ESTADO','PDF_RUTA','USUARIO',
        ];
        $sql  = "SELECT " . implode(',', $colsFB) . " FROM INVENTARIOS_MENSUALES";
        $rows = $fb->query($sql)->fetchAll();

        // Mapeo a las columnas SQLite (USUARIO → USUARIO_CAPTURA).
        $rowsMap = [];
        foreach ($rows as $r) {
            $r['USUARIO_CAPTURA'] = $r['USUARIO'] ?? null;
            unset($r['USUARIO']);
            $rowsMap[] = $r;
        }
        $colsSQLite = $colsFB;
        $colsSQLite[array_search('USUARIO', $colsSQLite, true)] = 'USUARIO_CAPTURA';
        return $this->_mergeTabla($sqlite, 'inventarios_mensuales', $colsSQLite, $rowsMap);
    }

    /**
     * MERGE: INSERT OR REPLACE por clave única (no trunca la tabla).
     * Usado para tablas transaccionales donde no queremos perder filas locales.
     */
    private function _mergeTabla(PDO $sqlite, string $tabla, array $cols, array $rows): int
    {
        if (empty($rows)) return 0;
        $sqlite->beginTransaction();
        try {
            $placeholders = '(' . rtrim(str_repeat('?,', count($cols)), ',') . ')';
            $stmt = $sqlite->prepare(
                "INSERT OR REPLACE INTO $tabla (" . implode(',', $cols) . ") VALUES $placeholders"
            );
            $n = 0;
            foreach ($rows as $r) {
                $valores = [];
                foreach ($cols as $c) {
                    $v = $r[$c] ?? $r[strtoupper($c)] ?? $r[strtolower($c)] ?? null;
                    if (is_string($v)) $v = trim($v);
                    $valores[] = $v;
                }
                $stmt->execute($valores);
                $n++;
            }
            $sqlite->commit();
            return $n;
        } catch (\Throwable $e) {
            $sqlite->rollBack();
            throw $e;
        }
    }

    /** TRUNCATE + INSERT en una transacción. Trim de strings (Firebird suele venir con padding). */
    private function _reemplazarTabla(PDO $sqlite, string $tabla, array $cols, array $rows): int
    {
        $sqlite->beginTransaction();
        try {
            $sqlite->exec("DELETE FROM $tabla");
            if (empty($rows)) { $sqlite->commit(); return 0; }

            $placeholders = '(' . rtrim(str_repeat('?,', count($cols)), ',') . ')';
            $stmt = $sqlite->prepare("INSERT INTO $tabla (" . implode(',', $cols) . ") VALUES $placeholders");

            $n = 0;
            foreach ($rows as $r) {
                $valores = [];
                foreach ($cols as $c) {
                    $v = $r[$c] ?? $r[strtoupper($c)] ?? $r[strtolower($c)] ?? null;
                    if (is_string($v)) $v = trim($v);
                    $valores[] = $v;
                }
                $stmt->execute($valores);
                $n++;
            }
            $sqlite->commit();
            return $n;
        } catch (\Throwable $e) {
            $sqlite->rollBack();
            throw $e;
        }
    }
}
