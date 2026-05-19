<?php
/**
 * RecuerdameServicio.php
 *
 * Gestión de tokens "Recordar sesión" sin modificar la base de datos.
 * Los tokens se almacenan como archivos JSON en /datos/sesiones/.
 *
 * Seguridad:
 *  - El token es 64 bytes aleatorios (128 hex chars).
 *  - En disco se guarda el hash SHA-256 del token (nunca el token crudo).
 *  - La cookie lleva el token crudo (HttpOnly, SameSite=Strict, Secure cuando hay HTTPS).
 *  - Cada usuario tiene máximo 1 token activo: el nuevo borra al anterior.
 *  - Expiración configurable (por defecto 30 días).
 */
class RecuerdameServicio
{
    const COOKIE_NAME   = 'bulma_rm';
    const DIAS_EXPIRY   = 30;
    const DIR_SESIONES  = __DIR__ . '/../../datos/sesiones';

    /* ── Crear token y setear cookie ─────────────────────────────── */
    public static function crear(array $datosUsuario): void
    {
        self::inicializarDirectorio();

        /* Borrar tokens anteriores de este usuario */
        self::revocarPorUsuario($datosUsuario['usuario']);

        /* Generar token criptográficamente seguro */
        $tokenRaw  = bin2hex(random_bytes(64));          // 128 chars hex
        $tokenHash = hash('sha256', $tokenRaw);          // lo que guardamos en disco

        $expiry = time() + (self::DIAS_EXPIRY * 86400);

        $datos = [
            'usuario'    => $datosUsuario['usuario'],
            'nombre'     => $datosUsuario['nombre'],
            'rol'        => $datosUsuario['rol'],
            'clave_rol'  => $datosUsuario['clave_rol'],
            'creado_en'  => date('c'),
            'expira_en'  => $expiry,
        ];

        /* Guardar en disco: nombre del archivo = hash del token */
        file_put_contents(
            self::DIR_SESIONES . '/' . $tokenHash . '.json',
            json_encode($datos, JSON_UNESCAPED_UNICODE)
        );

        /* Setear cookie en el navegador */
        $secure   = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        setcookie(self::COOKIE_NAME, $tokenRaw, [
            'expires'  => $expiry,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Strict',
            'secure'   => $secure,
        ]);
    }

    /* ── Validar cookie y devolver datos del usuario ─────────────── */
    public static function validar(): ?array
    {
        $tokenRaw = $_COOKIE[self::COOKIE_NAME] ?? null;
        if (!$tokenRaw) return null;

        $tokenHash = hash('sha256', $tokenRaw);
        $archivo   = self::DIR_SESIONES . '/' . $tokenHash . '.json';

        if (!file_exists($archivo)) return null;

        $datos = json_decode(file_get_contents($archivo), true);
        if (!$datos) return null;

        /* Verificar expiración */
        if (($datos['expira_en'] ?? 0) < time()) {
            @unlink($archivo);
            return null;
        }

        return $datos;
    }

    /* ── Revocar la cookie actual (logout) ───────────────────────── */
    public static function revocarActual(): void
    {
        $tokenRaw = $_COOKIE[self::COOKIE_NAME] ?? null;
        if ($tokenRaw) {
            $tokenHash = hash('sha256', $tokenRaw);
            $archivo   = self::DIR_SESIONES . '/' . $tokenHash . '.json';
            @unlink($archivo);
        }

        /* Eliminar la cookie del navegador */
        setcookie(self::COOKIE_NAME, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }

    /* ── Revocar todos los tokens de un usuario ──────────────────── */
    public static function revocarPorUsuario(string $usuario): void
    {
        self::inicializarDirectorio();
        foreach (glob(self::DIR_SESIONES . '/*.json') as $archivo) {
            $datos = json_decode(file_get_contents($archivo), true);
            if (($datos['usuario'] ?? '') === $usuario) {
                @unlink($archivo);
            }
        }
    }

    /* ── Limpiar tokens expirados (se puede llamar periódicamente) ── */
    public static function limpiarExpirados(): void
    {
        self::inicializarDirectorio();
        foreach (glob(self::DIR_SESIONES . '/*.json') as $archivo) {
            $datos = json_decode(file_get_contents($archivo), true);
            if (($datos['expira_en'] ?? 0) < time()) {
                @unlink($archivo);
            }
        }
    }

    /* ── Crear directorio si no existe ──────────────────────────── */
    private static function inicializarDirectorio(): void
    {
        if (!is_dir(self::DIR_SESIONES)) {
            mkdir(self::DIR_SESIONES, 0700, true);

            /* .htaccess de seguridad: bloquear acceso web directo */
            file_put_contents(
                self::DIR_SESIONES . '/.htaccess',
                "Deny from all\n"
            );
        }
    }
}
