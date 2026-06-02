<?php
require_once __DIR__ . '/../Database/DatabaseSQLite.php';
require_once __DIR__ . '/LoggerErrores.php';

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
    /*  Conexión a Firebird (lee config desde SQLite)                 */
    /* ────────────────────────────────────────────────────────────── */
    private function conectarFirebird(): PDO
    {
        $host    = DatabaseSQLite::getConfig('fb_host', '172.24.10.251');
        $port    = DatabaseSQLite::getConfig('fb_port', '3050');
        $user    = DatabaseSQLite::getConfig('fb_user', 'SYSDBA');
        $pass    = DatabaseSQLite::getConfig('fb_pass', 'masterkey');
        $dbPath  = DatabaseSQLite::getConfig('fb_db_path', '/firebird/data/DB_SIDISTLOCAL.FDB');
        $charset = DatabaseSQLite::getConfig('fb_charset', 'NONE');

        $dsn = "firebird:dbname=$host/$port:$dbPath;charset=$charset";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    }

    /* ────────────────────────────────────────────────────────────── */
    /*  Tablas individuales                                           */
    /* ────────────────────────────────────────────────────────────── */
    private function _syncUsuarios(PDO $fb, PDO $sqlite): int
    {
        // Firebird real no tiene columna ACTIVO. En SQLite la columna existe con DEFAULT 1.
        $rows = $fb->query("SELECT USUARIO, CONTRASENA, NOMBRE, ROL, CLAVE_ROL FROM USUARIOS_INVENTARIOS")
                   ->fetchAll();
        return $this->_reemplazarTabla($sqlite, 'usuarios_inventarios',
            ['USUARIO','CONTRASENA','NOMBRE','ROL','CLAVE_ROL'], $rows);
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
                       PROMOTOR,
                       CC_FAM, CC_BT1, CC_BT2, CC_BT3, CC_BT4, CC_BT5, CC_BT6, CC_BT7,
                       EN_OPERACION
                FROM LECHERIA WHERE EFD_NUMERO=20";
        $rows = $fb->query($sql)->fetchAll();
        return $this->_reemplazarTabla($sqlite, 'lecheria',
            ['LECHER','NOMBRELECH','EFD_NUMERO','MUN_NUMERO','LOC_NUMERO',
             'NUM_TIENDA','TIPO_PUNTO_VENTA','ALMACEN_RURAL',
             'PROMOTOR',
             'CC_FAM','CC_BT1','CC_BT2','CC_BT3','CC_BT4','CC_BT5','CC_BT6','CC_BT7',
             'EN_OPERACION'],
            $rows);
    }

    private function _syncPromotor(PDO $fb, PDO $sqlite): int
    {
        $rows = $fb->query("SELECT PMT_NUMERO, PMT_NOMBRE, PMT_ACTIVO FROM PROMOTOR")->fetchAll();
        return $this->_reemplazarTabla($sqlite, 'promotor',
            ['PMT_NUMERO','PMT_NOMBRE','PMT_ACTIVO'], $rows);
    }

    private function _syncSupervisor(PDO $fb, PDO $sqlite): int
    {
        $rows = $fb->query("SELECT ID_SUPERVISOR, NOMBRE_SUPERVISOR FROM SUPERVISOR")->fetchAll();
        return $this->_reemplazarTabla($sqlite, 'supervisor',
            ['ID_SUPERVISOR','NOMBRE_SUPERVISOR'], $rows);
    }

    private function _syncMapeo(PDO $fb, PDO $sqlite): int
    {
        $rows = $fb->query("SELECT ID_SUPERVISOR, LECHER FROM MAPEO_SUPERVISOR_LECHERIA")->fetchAll();
        return $this->_reemplazarTabla($sqlite, 'mapeo_supervisor_lecheria',
            ['ID_SUPERVISOR','LECHER'], $rows);
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
