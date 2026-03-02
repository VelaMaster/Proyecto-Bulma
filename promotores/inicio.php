<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    header("Location: ../iniciosesionPromotor.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio Promotorr</title>
</head>
<body>
    <h1>Bienvenido, <?php echo $_SESSION['nombre']; ?></h1>
    <p>Promotores.</p>
    <a href="../logout.php">Cerrar Sesión</a>
</body>
</html>