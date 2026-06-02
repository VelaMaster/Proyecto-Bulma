<?php
/**
 * admin/cli_token.php
 * Uso desde shell:
 *   docker compose exec php php /var/www/html/admin/cli_token.php          → muestra el token
 *   docker compose exec php php /var/www/html/admin/cli_token.php set X    → fija X como token
 *   docker compose exec php php /var/www/html/admin/cli_token.php regen    → genera uno nuevo
 *
 * Solo funciona vía CLI (no por web).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Solo CLI.\n");
}

require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

$accion = $argv[1] ?? 'show';

switch ($accion) {
    case 'set':
        $nuevo = $argv[2] ?? '';
        if ($nuevo === '') exit("Falta el token. Uso: cli_token.php set <token>\n");
        DatabaseSQLite::setConfig('admin_token', $nuevo);
        echo "Token fijado.\n";
        break;

    case 'regen':
        $nuevo = bin2hex(random_bytes(24));
        DatabaseSQLite::setConfig('admin_token', $nuevo);
        echo "Nuevo token: $nuevo\n";
        break;

    case 'show':
    default:
        $t = DatabaseSQLite::getConfig('admin_token');
        echo ($t ?: '(aún no se genera — entra a /admin desde LAN o ejecuta: regen)') . "\n";
}

echo "URL: https://inventariosliconsaoaxaca.duckdns.org/admin/?key=" . DatabaseSQLite::getConfig('admin_token') . "\n";
