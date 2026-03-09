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

<form action="login_proceso.php" method="POST" style="width: 100%; display: flex; flex-direction: column; align-items: center; gap: 20px; margin-top: 10px;">
                <input type="hidden" name="rol_id" value="0">

                <div class="input-contenedor-flotante">
                    <input type="text" name="usuario" id="usuario" class="entradasTexto input-flotante" placeholder=" " required>
                    <label for="usuario" class="label-flotante">Usuario</label>
                </div>

                <div class="input-contenedor-flotante">
                    <input type="password" name="password" id="password" class="entradasTexto input-flotante" placeholder=" " required>
                    <label for="password" class="label-flotante">Contraseña</label>
                    <button type="button" class="btn-ver-pass" onclick="togglePassword()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye" id="eye-icon"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>

                <a href="#" class="olvido-pass" style="align-self: flex-end; margin-right: 15%;">¿Olvidó su contraseña?</a>

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
    <script>
        function togglePassword() {
            const passInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (passInput.type === 'password') {
                passInput.type = 'text';
                // Cambia el ícono a ojo tachado
                eyeIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
            } else {
                passInput.type = 'password';
                // Vuelve al ícono de ojo normal
                eyeIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
            }
        }
    </script>   
</body>

</html>