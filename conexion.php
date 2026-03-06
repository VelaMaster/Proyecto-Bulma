<?php
$origen_conexion = "Desconocido"; 

$host_remote = '172.24.10.251';
$host_local  = 'proyecto-bulma-db-1'; 
$puerto = 3050;

$socket = @fsockopen($host_remote, $puerto, $errno, $errstr, 2);

if ($socket) {
    $host = $host_remote;
    $base_datos = 'C:/SisDLL20/BD/DB_SIDIST.FDB';
    $user = 'SYSDBA';
    $pass = '290990';
    $origen_conexion = "SERVIDOR REAL (LICONSA)";
    fclose($socket);
} else {
    $host = $host_local; 
    $base_datos = '/firebird/data/inventario.fdb';
    $user = 'SYSDBA';
    $pass = 'masterkey';
    $origen_conexion = "DOCKER LOCAL (PRUEBAS)";
}

try {
    $dsn = "firebird:dbname=$host/$puerto:$base_datos;charset=UTF8";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $error_db = $e->getMessage();
}