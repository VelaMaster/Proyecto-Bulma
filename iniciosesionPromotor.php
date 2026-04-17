<?php
session_start();
require_once 'Database.php';
$origen_conexion = Database::getEnvName();
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio sesion Promotor</title>

    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">

    <link rel="stylesheet" href="main_md3.css">
    <link rel="stylesheet" href="loader_md3.css"> 

    <script type="importmap">
        {
      "imports": {
        "@material/web/": "https://esm.run/@material/web/"
      }
    }
    </script>
    <script type="module">
        import '@material/web/all.js';
    </script>
</head>

<body>

    <div class="top-controls">
        <md-outlined-button id="btnModo" onclick="cambiarModo()">
            <md-icon slot="icon">dark_mode</md-icon>
            Modo
        </md-outlined-button>

        <select id="selectorColor" class="md3-native-select" onchange="cambiarAcento(this.value)">
            <option value="violeta">Tema Violeta</option>
            <option value="verde">Tema Verde</option>
            <option value="naranja">Tema Naranja</option>
        </select>
    </div>

    <div class="pantallaCentrada">
        <div class="md3-surface-container">

            <h1 class="md3-title">Inventarios de Leche</h1>

            <div class="role-selector">
                <md-filled-button onclick="cambiarPagina('iniciosesionPromotor.php', 'Cargando módulo Promotor...')">Promotor</md-filled-button>
                <md-text-button onclick="cambiarPagina('iniciosesionSupervisor.php', 'Cargando módulo Supervisor...')">Supervisor</md-text-button>
                <md-text-button onclick="cambiarPagina('iniciosesionDistribucion.php', 'Cargando módulo Distribución...')">Distribución</md-text-button>
            </div>

            <form action="login_proceso.php" method="POST" class="md3-form" onsubmit="mostrarLoader('Verificando credenciales...')">
                <input type="hidden" name="rol_id" value="0">

                <md-outlined-text-field label="Usuario" name="usuario" required style="width: 100%;">
                    <md-icon slot="leading-icon">person</md-icon>
                </md-outlined-text-field>

                <md-outlined-text-field id="password-field" label="Contraseña" type="password" name="password" required style="width: 100%;">
                    <md-icon slot="leading-icon">lock</md-icon>
                    <md-icon-button slot="trailing-icon" id="toggle-password" type="button" onclick="togglePassword()">
                        <md-icon id="eye-icon">visibility</md-icon>
                    </md-icon-button>
                </md-outlined-text-field>

                <div class="form-actions">
                    <a href="#" class="md3-link">¿Olvidó su contraseña?</a>
                </div>

                <md-filled-button type="submit" style="width: 100%; margin-top: 10px;">
                    Ingresar
                    <md-icon slot="icon">login</md-icon>
                </md-filled-button>
            </form>

            <p class="conexion-text">
                Conectado a: <span><?php echo $origen_conexion; ?></span>
            </p>
        </div>
    </div>

<div id="pantalla-carga" class="loader-overlay">
        <div class="md3-loader-container">
            <canvas id="loader-canvas"></canvas>
        </div>
        
        <div class="texto-carga-container">
            <md-icon style="font-size: 20px;">hourglass_top</md-icon>
            Cargando, espere...
        </div>
    </div>

    <md-dialog id="dialogo-error">
        <div slot="headline" style="display: flex; align-items: center; gap: 8px; color: var(--md-sys-color-error, #B3261E);">
            <md-icon>error</md-icon>
            Credenciales incorrectas
        </div>
        <form slot="content" id="form-error" method="dialog">
            El usuario o la contraseña no coinciden. Por favor, verifica tus datos e intenta de nuevo.
        </form>
        <div slot="actions">
            <md-text-button form="form-error">Aceptar</md-text-button>
        </div>
    </md-dialog>

    <script>
        <?php if (isset($_GET['error'])): ?>
            document.addEventListener('DOMContentLoaded', () => {
                const dialog = document.getElementById('dialogo-error');
                dialog.show();
            });
        <?php endif; ?>

        function togglePassword() {
            const passField = document.getElementById('password-field');
            const eyeIcon = document.getElementById('eye-icon');

            if (passField.type === 'password') {
                passField.type = 'text';
                eyeIcon.textContent = 'visibility_off';
            } else {
                passField.type = 'password';
                eyeIcon.textContent = 'visibility';
            }
        }
    </script>

    <script src="js/temas_md3.js"></script>
    <script src="js/loader_md3.js"></script> </body>
</html>