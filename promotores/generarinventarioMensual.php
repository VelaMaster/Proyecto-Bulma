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
                <a href="./inicio.php" class="navbar-inicio">Inicio</a>
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
                Datos generales del inventario
            </h2>

            <div class="box liquid-glass-box">
                <form id="formInventario">
                    <div class="columns is-multiline">
                        <div class="column is-4">
                            <div class="field">
                                <label class="label label-dinamico">Fecha</label>
                                <div class="control">
                                    <input class="input entradasTexto" type="date" name="fecha"
                                        value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="column is-4">
                            <div class="field">
                                <label class="label label-dinamico">Clave del punto de venta (LECHER)</label>
                                <div class="control is-expanded has-icons-left">
                                    <input class="input entradasTexto" type="text" id="inputLecheria"
                                        name="clave_punto_venta" autocomplete="off"
                                        placeholder="Escribe clave o nombre..." required>
                                    <span class="icon is-small is-left"><i class="fas fa-search"></i></span>
                                    <div id="dropdown-menu" class="dropdown-menu"
                                        style="display:none; position:absolute; width:100%; z-index:100;">
                                        <div class="dropdown-content glass-menu" id="lista-sugerencias"
                                            style="max-height: 200px; overflow-y: auto;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="column is-4">
                            <div class="field">
                                <label class="label label-dinamico">Clave de la tienda</label>
                                <div class="control">
                                    <input class="input entradasTexto" type="text" id="campoTienda" name="clave_tienda"
                                        placeholder="Automático" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="column is-4">
                            <div class="field">
                                <label class="label label-dinamico">Almacén que surte</label>
                                <div class="control">
                                    <input class="input entradasTexto" type="text" id="campoAlmacen"
                                        name="almacen_nombre" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="column is-4">
                            <div class="field">
                                <label class="label label-dinamico">Municipio</label>
                                <div class="control">
                                    <input class="input entradasTexto" type="text" id="campoMunicipio" name="municipio"
                                        readonly>
                                </div>
                            </div>
                        </div>

                        <div class="column is-4">
                            <div class="field">
                                <label class="label label-dinamico">Comunidad</label>
                                <div class="control">
                                    <input class="input entradasTexto" type="text" id="campoComunidad" name="comunidad"
                                        readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

<section class="section">
        <div class="container">
            <h2 class="title is-4 titulo-seccion-dinamico mb-5">
                l. Existencia de Leche
            </h2>

            <div class="box liquid-glass-box" style="overflow-x: auto;"> <form id="formInventario">
                    <table class="table is-fullwidth tabla-glass">
                        <thead>
                            <tr>
                                <th> </th>
                                <th>Inventario inicial</th>
                                <th>Abasto total en el mes</th>
                                <th>Ventas real del mes</th>
                                <th>Litros registrados</th>
                                <th>Diferencia entre venta registrada y venta real</th>
                                <th>Inventario final del mes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                
                            </tr>
                            <tr>
                                <td>Dato</td><td>Dato</td><td>Dato</td><td>Dato</td><td>Dato</td><td>Dato</td><td>Dato</td>
                            </tr>
                            <tr>
                                <td>Dato</td><td>Dato</td><td>Dato</td><td>Dato</td><td>Dato</td><td>Dato</td><td>Dato</td>
                            </tr>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2 class="title is-4 titulo-seccion-dinamico mb-5">
                ll. Surtimientos
            </h2>

            <div class="box liquid-glass-box" style="overflow-x: auto;">
                <form id="formInventario">
                    <table class="table is-fullwidth tabla-glass">
                        <thead>
                            <tr>
                                <th>Fecha </th>
                                <th>Cajas </th>
                                <th>Litros </th>
                                <th>Facturas </th>
                                <th>Caducidad </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Dato</td><td>Dato</td><td>Dato</td><td>Dato</td><td>Dato</td>
                            </tr>
                            <tr>
                                <td>Dato</td><td>Dato</td><td>Dato</td><td>Dato</td><td>Dato</td>
                            </tr>
                            <tr>
                                <td>Dato</td><td>Dato</td><td>Dato</td><td>Dato</td><td>Dato</td>
                            </tr>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2 class="title is-4 titulo-seccion-dinamico mb-5">
                lll. Cobertura social y dotación asignada según padrón de beneficiarios
            </h2>

            <div class="box liquid-glass-box">
                <div class="columns is-multiline">
                    <div class="column is-4">
                        <div class="field">
                            <label class="label label-dinamico">Número de Hogares</label>
                            <div class="control">
                                <input class="input entradasTexto" type="text" id="campoHogares" readonly placeholder="0">
                            </div>
                        </div>
                    </div>

                    <div class="column is-4">
                        <div class="field">
                            <label class="label label-dinamico">Menores de 12 años</label>
                            <div class="control">
                                <input class="input entradasTexto" type="text" id="campoMenores" readonly placeholder="0">
                            </div>
                        </div>
                    </div>

                    <div class="column is-4">
                        <div class="field">
                            <label class="label label-dinamico">Mayores de 12 años</label>
                            <div class="control">
                                <input class="input entradasTexto" type="text" id="campoMayores" readonly placeholder="0">
                            </div>
                        </div>
                    </div>
                </div>
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
                        .then(response => response.json())
                        .then(datos => {
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
                                    // Rellenar Sección I
                                    inputLecheria.value = item.LECHER;
                                    document.getElementById('campoTienda').value = item.NUM_TIENDA ?? '';
                                    document.getElementById('campoAlmacen').value = item.ALMACEN_RURAL ?? '';
                                    document.getElementById('campoMunicipio').value = item.MUNICIPIO_NOMBRE ?? '';
                                    document.getElementById('campoComunidad').value = item.LOCALIDAD_DESC ?? '';

                                    // Rellenar Sección III (Cobertura Social)
                                    document.getElementById('campoHogares').value = item.TOTAL_HOGARES ?? 0;
                                    document.getElementById('campoMenores').value = item.TOTAL_INFANTILES ?? 0;
                                    document.getElementById('campoMayores').value = item.TOTAL_RESTO ?? 0;

                                    dropdown.style.display = 'none';
                                });

                                listaSugerencias.appendChild(option);
                            });

                            dropdown.style.display = 'block';
                        })
                        .catch(error => console.error('Error:', error));
                }, 300);
            });

            document.addEventListener('click', (e) => {
                if (!inputLecheria.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>