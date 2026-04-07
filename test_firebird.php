<?php
// test_firebird.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Prueba Definitiva - SQL Crudo (Mata-Dinosaurios)</h2>";

$host = '172.24.10.251';
$puerto = 3050;
$db_path = 'C:/SisDLL20/BD/DB_SIDIST.FDB';
$user = 'SYSDBA';
$pass = '290990';

try {
    // 1. CONEXIÓN DIRECTA
    $dsn = "firebird:dbname=$host/$puerto:$db_path;charset=NONE";
    $db = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p style='color:green;'>1. Conexión exitosa a Liconsa.</p>";

    // 2. FUNCIONES AYUDANTES (Para formatear textos y números sin PDO binds)
    $q = function ($val) {
        if ($val === null || $val === "") return 'NULL';
        // Escapamos comillas simples para evitar inyección SQL
        return "'" . str_replace("'", "''", $val) . "'";
    };

    $n = function ($val) {
        if ($val === null || $val === "") return 0;
        return (int)$val;
    };

    // 3. CONSTRUCCIÓN DE LA CONSULTA COMPLETA CON LOS DATOS DEL JSON
    $sql = "INSERT INTO INVENTARIOS_MENSUALES (
        FECHA, CLAVE_LECHERIA, CLAVE_TIENDA, ALMACEN, MUNICIPIO, COMUNIDAD,
        SURT_FECHA, SURT_CAJAS, SURT_LITROS, SURT_FACTURA, SURT_CADUCIDAD,
        INV_INI_CAJA, INV_INI_SOBRES, INV_INI_LITROS,
        ABASTO_CAJA, ABASTO_SOBRES, ABASTO_LITROS,
        VENTA_CAJA, VENTA_SOBRES, VENTA_LITROS,
        REG_CAJA, REG_SOBRES, REG_LITROS,
        DIF_CAJA, DIF_SOBRES, DIF_LITROS,
        FIN_CAJA, FIN_SOBRES, FIN_LITROS,
        HOGARES, MENORES, MAYORES, DOTACION,
        PDF_RUTA, USUARIO, ESTADO, ID
    ) VALUES (
        " . $q('2026-04-07') . ", " . $q('2012051000') . ", " . $q('11') . ", " . $q('CHALCATONGO') . ", 
        " . $q('SANTA CRUZ ITUNDUJIA') . ", " . $q('SANTA CRUZ ITUNDUJIA') . ", 

        " . $q('2026-04-07') . ", " . $n(61) . ", " . $n(4392) . ", NULL, NULL, 

        " . $n(0) . ", " . $n(0) . ", " . $n(0) . ", 
        " . $n(61) . ", " . $n(0) . ", " . $n(4392) . ", 
        " . $n(0) . ", " . $n(0) . ", " . $n(0) . ", 
        " . $n(0) . ", " . $n(0) . ", " . $n(0) . ", 
        " . $n(0) . ", " . $n(0) . ", " . $n(0) . ", 
        " . $n(61) . ", " . $n(0) . ", " . $n(4392) . ", 

        " . $n(244) . ", " . $n(141) . ", " . $n(212) . ", " . $n(5648) . ", 

        " . $q('Inv_2012051000.pdf') . ", " . $q('Diego') . ", 'guardado', 
        GEN_ID(seq_inventarios_mens_id, 1)
    )";

    // 4. EJECUCIÓN DIRECTA
    $db->exec($sql);
    echo "<h3 style='color:green;'>2. ¡INSERCIÓN EXITOSA CON SQL CRUDO!</h3>";
    
    // Imprimimos el SQL generado para que veas la "magia"
    echo "<p><b>SQL Ejecutado:</b><br><textarea style='width:100%; height:200px;' readonly>$sql</textarea></p>";

} catch (PDOException $e) {
    echo "<h3 style='color:red;'>2. ¡EXPLOTÓ EL DINOSAURIO!</h3>";
    echo "<b>Mensaje de error:</b> " . $e->getMessage() . "<hr>";
    echo "<p><b>SQL Intentado:</b><br><textarea style='width:100%; height:200px;' readonly>$sql</textarea></p>";
}
?>



