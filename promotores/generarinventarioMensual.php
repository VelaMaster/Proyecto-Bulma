<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario Mensual - Promotor</title>
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
            <div class="navbar-item has-dropdown is-hoverable">
                <a class="navbar-link is-arrowless nav-enlace"> </a>
                <div class="navbar-dropdown is-boxed glass-menu">
                     <a href = "./inicio.php" class = "navbar-item">Inicio</a>
                </div>
            </div>

            <div class="navbar-item has-dropdown is-hoverable">
                <a class="navbar-link is-arrowless nav-enlace">Reporte mensual de la operacion en lecherias con venta de leche en polvo. </a>
                <div class="navbar-dropdown is-boxed glass-menu">
                    <a class="navbar-item">Generar</a>
                    <a class="navbar-item">Consultar</a>
                </div>
            </div>
            <div class="navbar-item has-dropdown is-hoverable">
                <a class="navbar-link is-arrowless nav-enlace">Requerimiento de leche. </a>
                <div class="navbar-dropdown is-boxed glass-menu">
                    <a class="navbar-item">Generar</a>
                    <a class="navbar-item">Consultar</a>
                    <a class="navbar-item">Enviar reportes a supervisor</a>
                </div>
            </div>

        </div>
        </div>
    </nav>
    <script src="../js/temas.js"> </script>
</body>

</html>