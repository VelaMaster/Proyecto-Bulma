<?php
/**
 * session_guard.php
 *
 * Reemplaza la llamada manual a session_start() en todas las páginas.
 * Responsabilidades:
 *  1. Ampliar el tiempo de vida de la sesión PHP a 8 horas por defecto.
 *  2. Si hay cookie "Recordar sesión" (bulma_rm) y la sesión PHP ya expiró,
 *     restaurar automáticamente $_SESSION con los datos guardados.
 *  3. (Opcional) Limpiar tokens expirados cada ~1% de los requests.
 *
 * USO en páginas protegidas:
 *   require_once __DIR__ . '/../includes/session_guard.php';
 *   // ya NO llamar session_start() después de esto
 *
 * Luego el guard de auth sigue igual:
 *   if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') { ... }
 */

// Ruta base del proyecto (desde includes/ subir un nivel)
$_BULMA_ROOT = dirname(__DIR__);

require_once $_BULMA_ROOT . '/src/Servicio/RecuerdameServicio.php';

/* ── 1. Configurar lifetime de sesión ────────────────────────────── */
$sessionLifetime = 8 * 3600; // 8 horas por defecto

// Si el usuario tiene cookie remember-me, la sesión PHP dura 30 días
// (para que no lo bote aunque cierre el navegador)
if (!empty($_COOKIE[RecuerdameServicio::COOKIE_NAME])) {
    $sessionLifetime = RecuerdameServicio::DIAS_EXPIRY * 86400;
}

$secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',   // Strict bloqueaba cookies en redirects del SW
    'secure'   => $secure,
]);
ini_set('session.gc_maxlifetime', $sessionLifetime);

session_start();

/* ── 2. Restaurar sesión desde remember-me si ya expiró ─────────── */
if (!isset($_SESSION['usuario'])) {
    $datosGuardados = RecuerdameServicio::validar();
    if ($datosGuardados) {
        // Restaurar todos los datos de sesión
        $_SESSION['usuario']        = $datosGuardados['usuario'];
        $_SESSION['nombre']         = $datosGuardados['nombre'];
        $_SESSION['rol']            = $datosGuardados['rol'];
        $_SESSION['clave_rol']      = $datosGuardados['clave_rol'];
        $_SESSION['clave_promotor'] = $datosGuardados['clave_rol'];

        // Regenerar ID de sesión por seguridad
        session_regenerate_id(true);
    }
}

/* ── 3. Limpieza periódica de tokens expirados (~1% de requests) ── */
if (mt_rand(1, 100) === 1) {
    RecuerdameServicio::limpiarExpirados();
}
