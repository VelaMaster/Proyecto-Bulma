<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    header("Location: ../iniciosesionPromotor.php");
    exit();
}
$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : $_SESSION['usuario'];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Promotor - Leche para el Bienestar</title>
    <link rel="stylesheet" href="../mainprincipal.css">
</head>

<body>
    <nav class="navbar nav-base-moderna" role="navigation" aria-label="main navigation">
        <div class="navbar-brand">
            <a class="navbar-item logo-ajustado" href="#">
                <span class="icon is-medium mr-2"><i class="fas fa-bolt fa-lg"></i></span>
                <strong class="texto-logotipo">Leche para el bienestar.</strong>
            </a>

            <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false" data-target="navMenuLiconsa">
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
            </a>
        </div>

        <div id="navMenuLiconsa" class="navbar-menu">
            <div class="navbar-start">

                <div class="navbar-item has-dropdown is-hoverable">
                    <a class="navbar-link is-arrowless nav-enlace">Inventarios</a>

                    <div class="navbar-dropdown is-boxed glass-menu">
                        <a class="navbar-item">Stock</a>
                        <a class="navbar-item">Entradas</a>
                        <a class="navbar-item">Kardex</a>
                    </div>
                </div>

            </div>
        </div>
    </nav>
    <script src="../js/temas.js"> </script>
</body>

</html>