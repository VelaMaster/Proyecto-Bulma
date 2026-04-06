<?php
require_once 'Database.php'; // Ajusta la ruta a tu Database.php

try {
    $db = Database::getInstance();
    echo "<h1>Diagnóstico de Base de Datos: " . Database::getEnvName() . "</h1>";

    // 1. REVISAR TRIGGERS (Disparadores)
    echo "<h2>1. Triggers en INVENTARIOS_MENSUALES</h2>";
    $sqlTriggers = "SELECT RDB\$TRIGGER_NAME as NOMBRE, RDB\$TRIGGER_SOURCE as CODIGO 
                    FROM RDB\$TRIGGERS 
                    WHERE RDB\$RELATION_NAME = 'INVENTARIOS_MENSUALES' 
                    AND RDB\$SYSTEM_FLAG = 0";
    
    $stmt = $db->query($sqlTriggers);
    $triggers = $stmt->fetchAll();

    if (empty($triggers)) {
        echo "<p>No hay triggers personalizados.</p>";
    } else {
        foreach ($triggers as $t) {
            echo "<strong>Trigger: " . $t['NOMBRE'] . "</strong>";
            echo "<pre style='background:#f4f4f4; padding:10px; border:1px solid #ccc;'>" . htmlspecialchars($t['CODIGO']) . "</pre>";
        }
    }

    // 2. REVISAR EL VALOR ACTUAL DE LA SECUENCIA (ID)
    echo "<h2>2. Valor de la Secuencia (ID)</h2>";
    $sqlGen = "SELECT GEN_ID(seq_inventarios_mens_id, 0) as VALOR_ACTUAL FROM RDB\$DATABASE";
    try {
        $gen = $db->query($sqlGen)->fetch();
        echo "El ID actual es: <strong>" . $gen['VALOR_ACTUAL'] . "</strong>";
        if ($gen['VALOR_ACTUAL'] > 2147483647) {
            echo "<p style='color:red;'>⚠️ ERROR: La secuencia superó el límite de INTEGER (2,147,483,647).</p>";
        }
    } catch (Exception $e) {
        echo "No se pudo leer la secuencia: " . $e->getMessage();
    }

    // 3. REVISAR SI HAY COLUMNAS COMPUTADAS (Calculadas automáticamente)
    echo "<h2>3. Columnas Calculadas</h2>";
    $sqlComp = "SELECT RDB\$FIELD_NAME as COLUMNA, RDB\$COMPUTED_SOURCE as FORMULA
                FROM RDB\$RELATION_FIELDS
                WHERE RDB\$RELATION_NAME = 'INVENTARIOS_MENSUALES'
                AND RDB\$COMPUTED_SOURCE IS NOT NULL";
    
    $comp = $db->query($sqlComp)->fetchAll();
    if (empty($comp)) {
        echo "<p>No hay columnas calculadas.</p>";
    } else {
        foreach ($comp as $c) {
            echo "Columna: <b>" . trim($c['COLUMNA']) . "</b> Formula: <code>" . $c['FORMULA'] . "</code><br>";
        }
    }

} catch (Exception $e) {
    die("Error en diagnóstico: " . $e->getMessage());
}