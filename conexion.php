<?php
$is_local = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || $_SERVER['SERVER_ADDR'] == '127.0.0.1');

if ($is_local) {
    $host = 'proyecto-bulma-db-1'; 
    $base_datos = '/firebird/data/inventario.fdb';
    $user = 'SYSDBA';
    $pass = 'masterkey';
} else {
    $host = '172.24.10.251';
    $base_datos = 'C:/SisDLL20/BD/DB_SIDIST.FDB';
    $user = 'SYSDBA';
    $pass = '290990';
}
try {
    $dsn = "firebird:dbname=$host/3050:$base_datos;charset=UTF8";
    $pdo = new PDO($dsn, $user, $pass);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        "error" => true, 
        "mensaje" => "Error de conexión en " . ($is_local ? "DOCKER" : "SERVIDOR") . ": " . $e->getMessage()
    ]);
    exit;
}