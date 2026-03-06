<?php 
require_once 'conexion.php'; 
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">

<head>
    <meta charset="UTF-8">
    <title>Bulma - Promotor </title>
    <link rel="stylesheet" href="mainprincipal.css">
</head>

<body>

    <div style="position: absolute; top: 20px; right: 20px; display: flex; gap: 10px; align-items: center; z-index: 10;">
        <button onclick="cambiarModo()" class="button is-small is-rounded">
            🌓 Modo
        </button>

        <div class="select-temas">
            <select onchange="cambiarAcento(this.value)" id="selectorColor">
                <option value="violeta">Tema Violeta</option>
                <option value="verde">Tema Verde</option>
                <option value="naranja">Tema Naranja</option>
            </select>
        </div>
    </div>
    <div class="pantallaCentrada">
        <div class="contenedor-centrado">
            <h1 class="title is-4">Inventarios de Leche en polvo</h1>

            <div class="botones-horizontal buttons has-addons">
                <button id="btn-promotor" class="button is-rounded is-selected" onclick="location.href='iniciosesionPromotor.php';">Promotor</button>
                <button id="btn-supervisor" class="button" onclick="location.href='iniciosesionSupervisor.php';">Supervisor</button>
                <button id="btn-distribucion" class="button is-rounded" onclick="location.href='iniciosesionDistribucion.php';">Distribución</button>
            </div>

            <form action="login_proceso.php" method="POST" style="width: 100%; display: flex; flex-direction: column; align-items: center; gap: 15px;">
                <input type="hidden" name="rol_id" value="0">

                <input type="text" name="usuario" class="entradasTexto" placeholder="Usuario" required>
                <input type="password" name="password" class="entradasTexto" placeholder="Contraseña" required>

                <a href="#" class="olvido-pass">¿Olvidó su contraseña?</a>

                <button type="submit" class="botonTemas">
                    Ingresar
                </button>
            </form>
            <p class="is-size-7 mt-4" style="color: var(--bulma-text-soft); opacity: 0.8; font-weight: bold;">
             Conectado a: <span style="color: var(--bulma-link);"><?php echo $origen_conexion; ?></span>
            </p>
        </div>
    </div>
    <script src="js/temas.js"></script>
</body>

</html>