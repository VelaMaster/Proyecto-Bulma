<?php
class Database {
    private static $instance = null;
    private static $envName = "Desconocido";

    private function __construct() {
        // Evitamos instanciación externa
    }

    public static function getInstance() {
        if (self::$instance === null) {
            $host_remote = '172.24.10.251';
            $host_local  = 'proyecto-bulma-db-1'; 
            $puerto = 3050;

            $socket = @fsockopen($host_remote, $puerto, $errno, $errstr, 2);
            
            if ($socket) {
                $host = $host_remote;
                $db_path = 'C:/SisDLL20/BD/DB_SIDIST.FDB';
                $user = 'SYSDBA';
                $pass = '290990';
                self::$envName = "SERVIDOR REAL (LICONSA)";
                fclose($socket);
            } else {
                $host = $host_local; 
                $db_path = '/firebird/data/inventario.fdb';
                $user = 'SYSDBA';
                $pass = 'masterkey';
                self::$envName = "DOCKER LOCAL (PRUEBAS)";
            }

            try {
                $dsn = "firebird:dbname=$host/$puerto:$db_path;charset=UTF8";
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            } catch (PDOException $e) {
                die("Error de conexión: " . $e->getMessage());
            }
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