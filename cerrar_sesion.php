<?php
require_once __DIR__ . '/src/Servicio/RecuerdameServicio.php';

session_start();

// Limpiar token remember-me del servidor
RecuerdameServicio::revocarActual();

// Destruir sesión PHP
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Redirigir con flag para que el JS limpie la sesión offline en IndexedDB
header("Location: iniciosesionPromotor.php?logout=1");
exit();
