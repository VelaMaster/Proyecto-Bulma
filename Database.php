<?php
class Database {
    private static $instance = null;
    private static $envName = "Desconocido";

    private function __construct() {
    }
    public static function getInstance() {
        if (self::$instance !== null) return self::$instance;

        $host_remote = '172.24.10.251';
        $host_local  = 'db';
        $puerto      = 3050;

        // Cache del resultado de la prueba de red en /tmp para evitar
        // el overhead de fsockopen en cada request (antes: 2s/req).
        // Se re-verifica cada 5 minutos o si el entorno cambia.
        $cacheFile = sys_get_temp_dir() . '/liconsa_dbhost.cache';
        $useRemote = false;

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
            $useRemote = (trim(file_get_contents($cacheFile)) === '1');
        } else {
            // Timeout reducido a 0.3 s — si el servidor real no responde, cae rápido al local
            $socket    = @fsockopen($host_remote, $puerto, $errno, $errstr, 0.3);
            $useRemote = (bool)$socket;
            if ($socket) fclose($socket);
            @file_put_contents($cacheFile, $useRemote ? '1' : '0');
        }

        if ($useRemote) {
            $host    = $host_remote;
            $db_path = 'C:/SisDLL20/BD/DB_SIDIST.FDB';
            $user    = 'SYSDBA';
            $pass    = '290990';
            self::$envName = "SERVIDOR REAL (LICONSA)";
        } else {
            $host    = $host_local;
            $db_path = '/firebird/data/DB_SIDISTLOCAL.FDB';
            $user    = 'SYSDBA';
            $pass    = 'masterkey';
            self::$envName = "DOCKER LOCAL (PRUEBAS)";
        }

        try {
            $dsn = "firebird:dbname=$host/$puerto:$db_path;charset=NONE";
            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            // Si falla con el remoto cacheado, limpia caché y reintenta con local
            if ($useRemote) {
                @unlink($cacheFile);
                self::$instance = null;
                self::$envName  = "Desconocido";
                return self::getInstance();
            }
            die("Error de conexión: " . $e->getMessage());
        }

        return self::$instance;
    }
    public static function getEnvName() {
        if (self::$instance === null) {
            self::getInstance();
        }
        return self::$envName;
    }
}