<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">
<head>
    <meta charset="UTF-8">
    <title>Inventario - Leche para el Bienestar</title>
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
            
            <input type="text" class="entradasTexto" placeholder="Usuario">
            <input type="password" class="entradasTexto" placeholder="Contraseña">
            
            <a href="#" class="olvido-pass">¿Olvidó su contraseña?</a>

            <button class="botonTemas">
                Ingresar
            </button>
        </div>
    </div>
    <script src="js/temas.js"></script>
</body>
</html>