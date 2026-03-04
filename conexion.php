<?php
$host = '172.24.10.251';
$base_datos = 'C:/SisDLL20/BD/DB_SIDIST.FDB';
$user = 'SYSDBA';
$pass = '290990';

try {
    $dsn = "firebird:dbname=$host/3050:$base_datos;charset=UTF8";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // No hacer nada, $pdo quedará null
    // El archivo que hizo require_once debe verificar si $pdo existe
    $pdo = null;
}