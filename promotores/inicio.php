<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    header("Location: ../iniciosesionPromotor.php");
    exit();
}
$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : $_SESSION['usuario'];
?>

<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Promotor - Leche para el Bienestar</title>
    <link rel="stylesheet" href="../mainprincipal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <nav class="navbar is-dark" role="navigation" aria-label="main navigation" style="border-bottom: 1px solid var(--bulma-border);">
        <div class="navbar-brand">
            <a class="navbar-item" href="#">
                <strong style="color: var(--bulma-link);">LICONSA - PROMOTOR</strong>
            </a>
        </div>

        <div class="navbar-end">
            <div class="navbar-item">
                <div class="buttons">
                    <span class="tag is-link is-light mr-3">
                        <i class="fas fa-user mr-2"></i> <?php echo htmlspecialchars($nombre_usuario); ?>
                    </span>
                    <a href="../logout.php" class="button is-danger is-outlined is-small is-rounded">
                        <i class="fas fa-sign-out-alt mr-1"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <section class="section">
        <div class="container">
            <div class="columns is-centered">
                <div class="column is-10">
                    
                    <div class="box" style="background-color: var(--bulma-scheme-main-ter); border-radius: 15px;">
                        <h1 class="title is-3">Bienvenido, <?php echo htmlspecialchars($nombre_usuario); ?></h1>
                        <p class="subtitle is-5">Gestión de Inventarios de Leche en Polvo</p>
                    </div>

                    <div class="columns is-multiline">
                        <div class="column is-4">
                            <div class="box has-text-centered" style="transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                                <span class="icon is-large has-text-success">
                                    <i class="fas fa-truck-loading fa-3x"></i>
                                </span>
                                <h3 class="title is-4 mt-4">Entradas</h3>
                                <p>Registrar nueva recepción.</p>
                                <button class="button is-success is-rounded is-fullwidth mt-4">Registrar</button>
                            </div>
                        </div>

                        <div class="column is-4">
                            <div class="box has-text-centered" style="transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                                <span class="icon is-large has-text-warning">
                                    <i class="fas fa-file-invoice fa-3x"></i>
                                </span>
                                <h3 class="title is-4 mt-4">Reportes</h3>
                                <p>Generar resumen del día.</p>
                                <button class="button is-warning is-rounded is-fullwidth mt-4">Generar</button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

</body>
</html>