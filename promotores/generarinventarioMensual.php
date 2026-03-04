<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario Mensual - Promotor</title>
    <link rel="stylesheet" href="../mainprincipal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            </a>
        </div>

        <div id="navMenuLiconsa" class="navbar-menu">
            <div class="navbar-item has-dropdown is-hoverable">
                <a href="./inicio.php" class="navbar-item">Inicio</a>
            </div>
            <div class="navbar-item has-dropdown is-hoverable">
                <a class="navbar-link is-arrowless nav-enlace">Reporte mensual lecherías</a>
                <div class="navbar-dropdown is-boxed glass-menu">
                    <a class="navbar-item">Generar</a>
                    <a class="navbar-item">Consultar</a>
                </div>
            </div>
        </div>
    </nav>

    <section class="section">
        <div class="container">
            <h2 class="title is-4 titulo-seccion-dinamico mb-5">
                Datos generales del inventario.
            </h2>

            <div class="box glass-menu" style="background-color: rgba(46, 48, 52, 0.4) !important;">
                <form action="procesarInventario.php" method="POST">
                    <div class="columns is-multiline">
                        <div class="column is-4">
                            <div class="field">
                                <label class="label has-text-white">Fecha</label>
                                <div class="control">
                                    <input class="input entradasTexto" type="date" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="column is-4">
                            <div class="field">
                                <label class="label has-text-white">Clave del punto de venta (LECHER)</label>
                                <div class="control is-expanded has-icons-left">
                                    <input class="input entradasTexto" type="text" id="inputLecheria" name="clave_punto_venta" autocomplete="off" placeholder="Escribe clave o nombre..." required>
                                    <span class="icon is-small is-left"><i class="fas fa-search"></i></span>
                                    <div id="dropdown-menu" class="dropdown-menu" style="display:none; position:absolute; width:100%; z-index:100;">
                                        <div class="dropdown-content glass-menu" id="lista-sugerencias" style="max-height: 200px; overflow-y: auto;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="column is-4">
                            <div class="field">
                                <label class="label has-text-white">Clave de la tienda</label>
                                <div class="control">
                                    <input class="input entradasTexto" type="text" name="clave_tienda" placeholder="Opcional">
                                </div>
                            </div>
                        </div>

                        <div class="column is-4">
                            <div class="field">
                                <label class="label has-text-white">Almacén que surte</label>
                                <div class="control">
                                    <input class="input entradasTexto" type="text" id="campoAlmacen" name="almacen_nombre" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="column is-4">
                            <div class="field">
                                <label class="label has-text-white">Municipio</label>
                                <div class="control">
                                    <input class="input entradasTexto" type="text" id="campoMunicipio" name="municipio" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="column is-4">
                            <div class="field">
                                <label class="label has-text-white">Comunidad</label>
                                <div class="control">
                                    <input class="input entradasTexto" type="text" id="campoComunidad" name="comunidad" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script src="../js/temas.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            const inputLecheria = document.getElementById('inputLecheria');
            const dropdown = document.getElementById('dropdown-menu');
            const listaSugerencias = document.getElementById('lista-sugerencias');

            let timeoutBusqueda;

            inputLecheria.addEventListener('input', function() {

                const texto = this.value.trim();

                clearTimeout(timeoutBusqueda);

                if (texto.length < 1) {
                    dropdown.style.display = 'none';
                    return;
                }

                timeoutBusqueda = setTimeout(() => {

                    fetch('buscarLecheria.php?q=' + encodeURIComponent(texto))
                        .then(response => {
                            if (!response.ok) {
                                throw new Error("Error HTTP " + response.status);
                            }
                            // Primero lee como texto para ver qué llegó realmente
                            return response.text();
                        })
                        .then(textoRaw => {
                            console.log("Respuesta raw del servidor:", textoRaw); // <- para depurar
                            const datos = JSON.parse(textoRaw); // ahora parsea

                            listaSugerencias.innerHTML = '';

                            if (!Array.isArray(datos) || datos.length === 0) {
                                dropdown.style.display = 'none';
                                return;
                            }

                            datos.forEach(item => {
                                const option = document.createElement('a');
                                option.className = 'dropdown-item';
                                option.style.cursor = 'pointer';

                                option.innerHTML = `
                <strong>${item.LECHER}</strong> - ${item.NOMBRELECH}
                <br>
                <small>${item.MUNICIPIO_NOMBRE ?? ''} - ${item.LOCALIDAD_DESC ?? ''}</small>
            `;

                                option.addEventListener('click', () => {
                                    inputLecheria.value = item.LECHER;
                                    document.getElementById('campoAlmacen').value = item.NOMBRELECH ?? '';
                                    document.getElementById('campoMunicipio').value = item.MUNICIPIO_NOMBRE ?? '';
                                    document.getElementById('campoComunidad').value = item.LOCALIDAD_DESC ?? '';
                                    dropdown.style.display = 'none';
                                });

                                listaSugerencias.appendChild(option);
                            });

                            dropdown.style.display = 'block';
                        })
                        .catch(error => {
                            console.error('Error en fetch:', error);
                        });

                }, 250);

            });

            // Ocultar al hacer click fuera
            document.addEventListener('click', function(e) {
                if (!inputLecheria.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.style.display = 'none';
                }
            });

        });
    </script>
</body>

</html>