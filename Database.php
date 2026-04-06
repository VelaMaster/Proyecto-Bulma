<?php
// classes/Database.php
class Database {
    private static $instance = null;
    private $pdo;
    private function __construct() {
        $host_remote = '172.24.10.251';
        $host_local  = 'proyecto-bulma-db-1'; 
        $puerto = 3050;
        $socket = @fsockopen($host_remote, $puerto, $errno, $errstr, 2);
        
        if ($socket) {
            $host = $host_remote;
            $db_path = 'C:/SisDLL20/BD/DB_SIDIST.FDB';
            $user = 'SYSDBA';
            $pass = '290990';
            fclose($socket);
        } else {
            $host = $host_local; 
            $db_path = '/firebird/data/inventario.fdb';
            $user = 'SYSDBA';
            $pass = 'masterkey';
        }
        $dsn = "firebird:dbname=$host/$puerto:$db_path;charset=UTF8";
        $this->pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->pdo;
    }
}