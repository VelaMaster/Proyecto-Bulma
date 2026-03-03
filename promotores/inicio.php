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
    <section class="section">
        <div class="container">
            <div class="columns is-centered">
                <div class="column is-10">

                    <div class="columns is-multiline">
                        <div class="column is-4">
                            <div class="box has-text-centered" style="transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                                <span class="icon is-large has-text-success">
                                    <i class="fas fa-truck-loading fa-3x"></i>
                                </span>
                                <h3 class="title is-4 mt-4">Inventario mensual de leche en polvo.</h3>
                                <p>Generar, revisar o actualizar inventariio.</p>
                                <button class="button is-success is-rounded is-fullwidth mt-4">Entrar</button>
                            </div>
                        </div>

                        <div class="column is-4">
                            <div class="box has-text-centered" style="transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                                <span class="icon is-large has-text-warning">
                                    <i class="fas fa-file-invoice fa-3x"></i>
                                </span>
                                <h3 class="title is-4 mt-4">Reporte mensual de la operacion en lecherias con venta de leche en polvo.</h3>
                                <p>Generar, revisar y descargar reportes mensuales.</p>
                                <button class="button is-warning is-rounded is-fullwidth mt-4">Entrar</button>
                            </div>
                        </div>
                        <div class="column is-4">
                            <div class="box has-text-centered" style="transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                                <span class="icon is-large has-text-warning">
                                    <i class="fas fa-file-invoice fa-3x"></i>
                                </span>
                                <h3 class="title is-4 mt-4">Requerimiento de leche.</h3>
                                <p>Generar, revisar y descargar reportes mensuales.</p>
                                <button class="button is-loading is-rounded is-fullwidth mt-4">Entrar</button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
    <script src="../js/temas.js"> </script>
</body>

</html>