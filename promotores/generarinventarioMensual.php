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
                I. Existencia de Leche
            </h2>

            <div class="box liquid-glass-box" style="overflow-x: auto;">
                <form id="formInventarioExistencia">
                    <table class="table is-fullwidth tabla-glass">
                        <thead>
                            <tr>
                                <th></th>
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
                                <td><strong>Caja</strong></td>
                                <td><input type="number" id="inv_ini_caja" class="input entradasTexto" min="0" readonly
                                        placeholder="0"></td>
                                <td><input type="number" id="abasto_caja" class="input entradasTexto" readonly
                                        placeholder="0"></td>
                                <td><input type="number" id="venta_caja" class="input entradasTexto" placeholder="0">
                                </td>
                                <td><input type="number" id="litros_reg_caja" class="input entradasTexto"
                                        placeholder="0"></td>
                                <td><input type="number" id="dif_caja" class="input entradasTexto" readonly
                                        placeholder="0"></td>
                                <td><input type="number" id="inv_fin_caja" class="input entradasTexto" readonly
                                        placeholder="0"></td>
                            </tr>
                            <tr>
                                <td><strong>Sobres</strong></td>
                                <td><input type="number" id="inv_ini_sobres" class="input entradasTexto" readonly
                                        placeholder="0"></td>
                                <td><input type="number" id="abasto_sobres" class="input entradasTexto" readonly
                                        placeholder="0"></td>
                                <td><input type="number" id="venta_sobres" class="input entradasTexto" placeholder="0">
                                </td>
                                <td><input type="number" id="litros_reg_sobres" class="input entradasTexto"
                                        placeholder="0"></td>
                                <td><input type="number" id="dif_sobres" class="input entradasTexto" readonly
                                        placeholder="0"></td>
                                <td><input type="number" id="inv_fin_sobres" class="input entradasTexto" readonly
                                        placeholder="0"></td>
                            </tr>
                            <tr>
                                <td><strong>Total en litros</strong></td>
                                <td><input type="number" id="inv_ini_litros" class="input entradasTexto" min="0"
                                        step="72" readonly placeholder="0"></td>
                                <td><input type="number" id="abasto_litros" class="input entradasTexto" readonly
                                        placeholder="0"></td>
                                <td><input type="number" id="venta_litros" class="input entradasTexto" placeholder="0">
                                </td>
                                <td><input type="number" id="litros_reg_litros" class="input entradasTexto"
                                        placeholder="0"></td>
                                <td><input type="number" id="dif_litros" class="input entradasTexto" readonly
                                        placeholder="0"></td>
                                <td><input type="number" id="inv_fin_litros" class="input entradasTexto" readonly
                                        placeholder=""></td>
                            </tr>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
    </section>
<section class="section" style="padding-top: 0;">
        <div class="container">
            <div class="box liquid-glass-box" style="padding: 2rem;">
                
                <h2 class="title is-5 titulo-seccion-dinamico mb-3" style="border-bottom: 2px solid var(--bulma-border); padding-bottom: 5px;">1.1 DIFERENCIAS</h2>
                
                <div class="columns is-vcentered mb-4">
                    <div class="column is-narrow">
                        <label class="label label-dinamico mb-0">¿La venta registrada es igual a la venta real?</label>
                    </div>
                    <div class="column">
                        <div class="control" style="display: flex; gap: 20px; align-items: center;">
                            <label class="radio" style="color: var(--bulma-text); font-weight: 500;">
                                <input type="radio" name="venta_igual" value="No" disabled> No
                            </label>
                            <label class="radio" style="color: var(--bulma-text); font-weight: 500;">
                                <input type="radio" name="venta_igual" value="Si" disabled> Sí
                            </label>
                            <span class="help" style="color: var(--bulma-text-weak); margin: 0;">*Automático</span>
                        </div>
                    </div>
                </div>

                <div id="causas_diferencia" style="display: none; background: rgba(0,0,0,0.02); padding: 15px; border-radius: 8px; margin-bottom: 25px;">
                    <div class="field is-horizontal align-items-center mb-4">
                        <div class="field-label is-normal" style="flex-grow: 0; margin-right: 15px; text-align: left;">
                            <label class="label label-dinamico mb-0">¿Señale o describa la causa?</label>
                        </div>
                        <div class="field-body">
                            <div class="control" style="width: 100%;">
                                <input class="input entradasTexto" type="text" name="causa_descripcion" placeholder="Escriba aquí la causa general..." style="max-width: 100%;">
                            </div>
                        </div>
                    </div>

                    <div class="columns is-multiline" style="margin-left: 10px;">
                        <div class="column is-6 py-2">
                            <label class="checkbox" style="color: var(--bulma-text);">
                                <input type="checkbox" name="causa_a"> a) Falta de capacitación al responsable de la venta
                            </label>
                        </div>
                        <div class="column is-6 py-2">
                            <label class="checkbox" style="color: var(--bulma-text);">
                                <input type="checkbox" name="causa_b"> b) Omisión del responsable de la venta
                            </label>
                        </div>
                        <div class="column is-6 py-2">
                            <label class="checkbox" style="color: var(--bulma-text);">
                                <input type="checkbox" name="causa_c"> c) Resistencia de las personas titulares
                            </label>
                        </div>
                        <div class="column is-6 py-2">
                            <div class="field is-horizontal align-items-center">
                                <div class="field-label is-normal" style="flex-grow: 0; margin-right: 10px; text-align: left;">
                                    <label class="label label-dinamico mb-0">d) Otros:</label>
                                </div>
                                <div class="field-body">
                                    <div class="control">
                                        <input class="input entradasTexto is-small" type="text" name="causa_d_texto" placeholder="..." style="height: 30px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <h2 class="title is-5 titulo-seccion-dinamico mb-3 mt-5" style="border-bottom: 2px solid var(--bulma-border); padding-bottom: 5px;">1.2 VENTA NO REGISTRADA</h2>
                
                <div class="columns is-vcentered">
                    <div class="column is-narrow">
                        <label class="label label-dinamico mb-0">a) ¿Se vendió leche a personas no incluidas en el libro de retiro?</label>
                    </div>
                    <div class="column is-narrow">
                        <div class="control" style="display: flex; gap: 20px;">
                            <label class="radio" style="color: var(--bulma-text); font-weight: 500;">
                                <input type="radio" name="venta_no_incluida" value="No" checked> No
                            </label>
                            <label class="radio" style="color: var(--bulma-text); font-weight: 500;">
                                <input type="radio" name="venta_no_incluida" value="Si"> Sí
                            </label>
                        </div>
                    </div>
                </div>

                <div id="motivo_no_incluida" style="display: none; margin-top: 10px; padding-left: 20px;">
                    <div class="field is-horizontal align-items-center">
                        <div class="field-label is-normal" style="flex-grow: 0; margin-right: 15px; text-align: left;">
                            <label class="label label-dinamico mb-0">Anote el motivo:</label>
                        </div>
                        <div class="field-body">
                            <div class="control" style="width: 100%;">
                                <input class="input entradasTexto" type="text" name="motivo_venta_no_incluida" placeholder="Describa el motivo..." style="max-width: 600px;">
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2 class="title is-4 titulo-seccion-dinamico mb-5">
                II. Surtimientos sugeridos
            </h2>

            <div class="box liquid-glass-box" style="overflow-x: auto;">
                <form id="formSurtimiento">
                    <table class="table is-fullwidth tabla-glass">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Cajas</th>
                                <th>Litros</th>
                                <th>Facturas</th>
                                <th>Caducidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><input type="date" id="surt_fecha" class="input entradasTexto"
                                        value="<?php echo date('Y-m-d'); ?>"></td>
                                <td><input type="number" id="surt_cajas" class="input entradasTexto" placeholder="">
                                </td>
                                <td><input type="number" id="surt_litros" class="input entradasTexto" placeholder="">
                                </td>
                                <td><input type="text" id="surt_factura" class="input entradasTexto" placeholder="">
                                </td>
                                <td><input type="date" id="surt_caducidad" class="input entradasTexto"></td>
                            </tr>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
    </section>
    <section class="section" style="padding-top: 0;">
        <div class="container">
            <div class="box liquid-glass-box" style="padding: 2rem;">
                
                <h2 class="title is-5 titulo-seccion-dinamico mb-3" style="border-bottom: 2px solid var(--bulma-border); padding-bottom: 5px;">2.1 FALTA DE SURTIMIENTO</h2>
                
                <div class="columns is-vcentered mb-4">
                    <div class="column is-narrow">
                        <label class="label label-dinamico mb-0">¿Hubo falta de surtimiento?</label>
                    </div>
                    <div class="column">
                        <div class="control" style="display: flex; gap: 20px; align-items: center;">
                            <label class="radio" style="color: var(--bulma-text); font-weight: 500;">
                                <input type="radio" name="falta_surtimiento" value="No" checked> No
                            </label>
                            <label class="radio" style="color: var(--bulma-text); font-weight: 500;">
                                <input type="radio" name="falta_surtimiento" value="Si"> Sí
                            </label>
                        </div>
                    </div>
                </div>

                <div id="causas_falta_surtimiento" style="display: none; background: rgba(0,0,0,0.02); padding: 15px; border-radius: 8px;">
                    
                    <div class="field is-horizontal align-items-center mb-4">
                        <div class="field-label is-normal" style="flex-grow: 0; margin-right: 15px; text-align: left;">
                            <label class="label label-dinamico mb-0">¿Señale o describa la causa?</label>
                        </div>
                        <div class="field-body">
                            <div class="control" style="width: 100%;">
                                <input class="input entradasTexto" type="text" name="causa_falta_descripcion" placeholder="Escriba aquí la causa general..." style="max-width: 100%;">
                            </div>
                        </div>
                    </div>

                    <div class="columns is-multiline" style="margin-left: 10px;">
                        <div class="column is-6 py-2">
                            <label class="checkbox" style="color: var(--bulma-text);">
                                <input type="checkbox" name="causa_falta_a"> a) Adeudo del responsable de la venta
                            </label>
                        </div>
                        <div class="column is-6 py-2">
                            <label class="checkbox" style="color: var(--bulma-text);">
                                <input type="checkbox" name="causa_falta_b"> b) Retraso en la distribución
                            </label>
                        </div>
                        <div class="column is-12 py-2">
                            <div class="field is-horizontal align-items-center">
                                <div class="field-label is-normal" style="flex-grow: 0; margin-right: 10px; text-align: left;">
                                    <label class="label label-dinamico mb-0">c) Otros:</label>
                                </div>
                                <div class="field-body">
                                    <div class="control">
                                        <input class="input entradasTexto is-small" type="text" name="causa_falta_c_texto" placeholder="Especifique..." style="height: 30px; max-width: 400px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
    <section class="section">
        <div class="container">
            <h2 class="title is-4 titulo-seccion-dinamico mb-5">
                III. Cobertura social y dotación asignada según padrón de beneficiarios
            </h2>

            <div class="box liquid-glass-box">
                <div class="columns is-multiline">
                    <div class="column is-3">
                        <div class="field">
                            <label class="label label-dinamico">Número de Hogares</label>
                            <div class="control">
                                <input class="input entradasTexto" type="text" id="campoHogares" readonly
                                    placeholder="0">
                            </div>
                        </div>
                    </div>

                    <div class="column is-3">
                        <div class="field">
                            <label class="label label-dinamico">Menores de 12 años</label>
                            <div class="control">
                                <input class="input entradasTexto" type="text" id="campoMenores" readonly
                                    placeholder="0">
                            </div>
                        </div>
                    </div>

                    <div class="column is-3">
                        <div class="field">
                            <label class="label label-dinamico">Mayores de 12 años</label>
                            <div class="control">
                                <input class="input entradasTexto" type="text" id="campoMayores" readonly
                                    placeholder="0">
                            </div>
                        </div>
                    </div>

                    <div class="column is-3">
                        <div class="field">
                            <label class="label label-dinamico">Litros al mes</label>
                            <div class="control">
                                <input class="input entradasTexto" type="text" id="campoDotacion" readonly
                                    placeholder="0">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="section">
        <div class="container">
            <h2 class="title is-4 titulo-seccion-dinamico mb-5">
                IV. Problemas de operación en el punto de venta
            </h2>

            <div class="box liquid-glass-box">
                <div class="columns is-multiline mb-2">
                    <div class="column is-6 py-2">
                        <label class="checkbox" style="color: var(--bulma-text);">
                            <input type="checkbox" name="prob_a"> a) Cierre por reubicación de punto de venta
                        </label>
                    </div>
                    <div class="column is-6 py-2">
                        <label class="checkbox" style="color: var(--bulma-text);">
                            <input type="checkbox" name="prob_b"> b) Renuncia o baja del responsable
                        </label>
                    </div>
                    <div class="column is-6 py-2">
                        <label class="checkbox" style="color: var(--bulma-text);">
                            <input type="checkbox" name="prob_c"> c) Adeudo del responsable
                        </label>
                    </div>
                    <div class="column is-6 py-2">
                        <div class="field is-horizontal align-items-center">
                            <div class="field-label is-normal" style="flex-grow: 0; margin-right: 10px; text-align: left;">
                                <label class="label label-dinamico mb-0">d) Otros:</label>
                            </div>
                            <div class="field-body">
                                <div class="control">
                                    <input class="input entradasTexto is-small" type="text" name="prob_d_texto" placeholder="Especifique..." style="height: 30px; max-width: 400px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <h3 class="title is-5 titulo-seccion-dinamico mb-3 mt-5" style="border-bottom: 2px solid var(--bulma-border); padding-bottom: 5px;">
                    4.1 ¿Se puede continuar con la venta de leche Liconsa?
                </h3>
                <div class="field is-horizontal align-items-center mb-4">
                    <div class="field-body">
                        <div class="control" style="display: flex; gap: 20px; align-items: center;">
                            <label class="radio" style="color: var(--bulma-text); font-weight: 500;">
                                <input type="radio" name="continuar_venta" value="Si" checked> Sí
                            </label>
                            <label class="radio" style="color: var(--bulma-text); font-weight: 500;">
                                <input type="radio" name="continuar_venta" value="No"> No
                            </label>
                        </div>
                    </div>
                </div>

                <div id="alternativas_solucion" style="display: none; background: rgba(0,0,0,0.02); padding: 15px; border-radius: 8px;">
                    
                    <div class="field is-horizontal align-items-center mb-4">
                        <div class="field-label is-normal" style="flex-grow: 0; margin-right: 15px; text-align: left; width: auto;">
                            <label class="label label-dinamico mb-0">Alternativas de solución:</label>
                        </div>
                        <div class="field-body">
                            <div class="control" style="width: 100%;">
                                <input class="input entradasTexto" type="text" name="alternativa_general" placeholder="Describa la alternativa..." style="max-width: 100%;">
                            </div>
                        </div>
                    </div>

                    <div class="columns is-multiline" style="margin-left: 10px;">
                        <div class="column is-6 py-2">
                            <label class="checkbox" style="color: var(--bulma-text);">
                                <input type="checkbox" name="alt_a"> a) Propuesta de un nuevo local
                            </label>
                        </div>
                        <div class="column is-6 py-2">
                            <label class="checkbox" style="color: var(--bulma-text);">
                                <input type="checkbox" name="alt_b"> b) Fusión de beneficiarios
                            </label>
                        </div>
                        <div class="column is-6 py-2">
                            <label class="checkbox" style="color: var(--bulma-text);">
                                <input type="checkbox" name="alt_c"> c) Baja del padrón de beneficiarios
                            </label>
                        </div>
                        <div class="column is-6 py-2">
                            <div class="field is-horizontal align-items-center">
                                <div class="field-label is-normal" style="flex-grow: 0; margin-right: 10px; text-align: left;">
                                    <label class="label label-dinamico mb-0">d) Otra:</label>
                                </div>
                                <div class="field-body">
                                    <div class="control">
                                        <input class="input entradasTexto is-small" type="text" name="alt_d_texto" placeholder="Especifique..." style="height: 30px; max-width: 300px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <script src="../js/temas.js"></script>
    <script src="../js/promotores.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            const inputLecheria = document.getElementById('inputLecheria');
            const dropdown = document.getElementById('dropdown-menu');
            const listaSugerencias = document.getElementById('lista-sugerencias');

            let timeoutBusqueda;

            inputLecheria.addEventListener('input', function () {
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
                                    const hogares = item.TOTAL_HOGARES ?? 0;
                                    const menores = item.TOTAL_INFANTILES ?? 0;
                                    const mayores = item.TOTAL_RESTO ?? 0;

                                    document.getElementById('campoHogares').value = hogares;
                                    document.getElementById('campoMenores').value = menores;
                                    document.getElementById('campoMayores').value = mayores;

                                    // Tu fórmula: (Menores + Mayores) * 8 / 36
                                    const totalBeneficiarios = parseInt(menores) + parseInt(mayores);
                                    const dotacionCajas = ((totalBeneficiarios * 8) / 36 * 72).toFixed(0); // A 1 decimal
                                    document.getElementById('campoDotacion').value = dotacionCajas;

                                    dropdown.style.display = 'none';
                                    document.dispatchEvent(new Event('lecheriaSeleccionada'));
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