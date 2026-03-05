<?php
$host = '172.24.10.251';
$base_datos = 'C:/SisDLL20/BD/DB_SIDIST.FDB';
$user = 'SYSDBA';
$pass = '290990';

try {
    // Definimos el DSN para Firebird con UTF8 para evitar errores de caracteres
    $dsn = "firebird:dbname=$host/3050:$base_datos;charset=UTF8";
    $pdo = new PDO($dsn, $user, $pass);
    
    // Configuramos para que cualquier error lance una excepción visible
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(["error" => true, "mensaje" => "Error de conexión: " . $e->getMessage()]);
    exit;
}