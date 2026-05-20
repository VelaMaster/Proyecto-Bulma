<?php
session_start();
// Usuario con sesión activa → redirigir directo al panel (Google nunca tiene sesión)
if (!empty($_SESSION['usuario']) && !empty($_SESSION['rol'])) {
    $destino = '/promotores/inicio.php';
    if ($_SESSION['rol'] === 'supervisor')   $destino = '/supervisor/inicio.php';
    if ($_SESSION['rol'] === 'distribucion') $destino = '/distribucion/inicio.php';
    header("Location: $destino");
    exit();
}
// Sin sesión → redirigir al login
header("Location: iniciosesionPromotor.php");
exit();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0;url=iniciosesionPromotor.php">
    
    <title>Sistema de Inventarios Liconsa Oaxaca | Portal de Acceso</title>
    
    <meta name="description" content="Portal oficial para el control, registro y distribución de inventarios del programa de Leche Liconsa en el estado de Oaxaca.">
    <meta name="keywords" content="Inventarios Liconsa Oaxaca, Liconsa, Oaxaca, Promotor Liconsa, Inventarios de Leche">
    <meta name="robots" content="index, follow">

    <style>
        /* Un estilo rápido por si el navegador tarda medio segundo en redirigir */
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #121212;
            color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            text-align: center;
        }
        a { color: #bb86fc; text-decoration: none; }
    </style>
</head>
<body>

    <div>
        <h1>Sistema de Inventarios Liconsa Oaxaca</h1>
        <p>Cargando el portal de administración del programa de Leche...</p>
        <p>Si no eres redirigido automáticamente, haz <a href="iniciosesionPromotor.php">clic aquí para ingresar</a>.</p>
    </div>

</body>
</html>