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
                <strong class="texto-logotipo">Inventario mensual de leche en polvo.</strong>
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
                    <a href="./inicio.php" class="navbar-item">Inicio</a>
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
    <section class="section">
        <div class="container">
<h2 class="title is-4 titulo-seccion-dinamico mb-5">
    <i class="fas fa-edit mr-2"></i> Datos generales del inventario.
</h2>

            <div class="box glass-menu" style="background-color: rgba(46, 48, 52, 0.4) !important;">
                <form action="procesarInventario.php" method="POST">

                    <div class="columns is-multiline">
                        <div class="column is-4">
                            <div class="field">
                                <label class="label has-text-white">Fecha</label>
                                <div class="control">
                                    <input class="input entradasTexto" type="date" name="fecha"
                                        value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="column is-4">
                            <div class="field">
                                <label class="label has-text-white">Clave Punto de Venta</label>
                                <div class="control">
                                    <input class="input entradasTexto" type="text" name="clave_punto_venta"
                                        placeholder="Ej. PV-OAX-001" required>
                                </div>
                            </div>
                        </div>

                        <div class="column is-4">
                            <div class="field">
                                <label class="label has-text-white">Clave de la Tienda</label>
                                <div class="control">
                                    <input class="input entradasTexto" type="text" name="clave_tienda"
                                        placeholder="Ej. T-12345" required>
                                </div>
                            </div>
                        </div>

                        <div class="column is-4">
                            <div class="field">
                                <label class="label has-text-white">Almacén que surte</label>
                                <div class="control">
                                    <div class="select is-fullwidth select-temas">
                                        <select name="almacen">
                                            <option value="valles">Guadalupe (Valles)</option>
                                            <option value="mixteca">Mixteca</option>
                                            <option value="istmo">Istmo</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="column is-4">
                            <div class="field">
                                <label class="label has-text-white">Municipio</label>
                                <div class="control">
                                    <input class="input entradasTexto" type="text" name="municipio"
                                        placeholder="Ej. Oaxaca de Juárez" required>
                                </div>
                            </div>
                        </div>

                        <div class="column is-4">
                            <div class="field">
                                <label class="label has-text-white">Comunidad</label>
                                <div class="control">
                                    <input class="input entradasTexto" type="text" name="comunidad"
                                        placeholder="Ej. Centro" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
    <script src="../js/temas.js"> </script>
</body>

</html>