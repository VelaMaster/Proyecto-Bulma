<?php
/**
 * admin/guard.php
 * Acceso al panel /admin SIN login formal.
 * Se permite si:
 *   (a) la IP cliente pertenece a la LAN privada, o
 *   (b) el cliente trae ?key=TOKEN válido o cookie admin_token previa.
 * Incluir AL INICIO de cada archivo dentro de /admin.
 */

require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

function _admin_client_ip(): string {
    return $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['HTTP_X_REAL_IP']
        ?? $_SERVER['REMOTE_ADDR']
        ?? '';
}

function _admin_ip_in_cidr(string $ip, string $cidr): bool {
    if (strpos($cidr, '/') === false) return $ip === $cidr;
    [$subnet, $mask] = explode('/', $cidr);
    $mask = (int)$mask;
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) &&
        filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $ipL  = ip2long($ip);
        $subL = ip2long($subnet);
        $maskL = -1 << (32 - $mask);
        return ($ipL & $maskL) === ($subL & $maskL);
    }
    return $ip === $subnet;
}

function _admin_ip_lan(string $ip): bool {
    foreach (['127.0.0.1','::1','10.0.0.0/8','172.16.0.0/12','192.168.0.0/16'] as $cidr) {
        if (_admin_ip_in_cidr($ip, $cidr)) return true;
    }
    return false;
}

/** Garantiza que exista un token. Si no, genera uno (48 chars). Devuelve el actual. */
function admin_token_actual(): string {
    $t = DatabaseSQLite::getConfig('admin_token');
    if (!$t) {
        $t = bin2hex(random_bytes(24));   // 48 caracteres hex
        DatabaseSQLite::setConfig('admin_token', $t);
    }
    return $t;
}

/** Comparación segura contra timing-attacks. */
function _admin_token_ok(?string $candidato): bool {
    if (!$candidato) return false;
    return hash_equals(admin_token_actual(), $candidato);
}

$_clientIp = _admin_client_ip();
$_okLan    = _admin_ip_lan($_clientIp);

// Token: por query string (?key=) o cookie previamente fijada
$_tokenQuery  = $_GET['key']                  ?? null;
$_tokenCookie = $_COOKIE['admin_token']       ?? null;
$_okToken     = _admin_token_ok($_tokenQuery) || _admin_token_ok($_tokenCookie);

// Si vino por query y es válido, fijamos cookie para esta sesión + 30 días
if (_admin_token_ok($_tokenQuery)) {
    setcookie('admin_token', $_tokenQuery, [
        'expires'  => time() + 30*86400,
        'path'     => '/admin',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
}

if (!$_okLan && !$_okToken) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>403</title></head><body style='font-family:Roboto,sans-serif;background:#141218;color:#E6E1E5;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;'><div style='text-align:center'><h1 style='font-size:4rem;margin:0;color:#B3261E'>403</h1><p>Acceso restringido. IP no autorizada: <code>" . htmlspecialchars($_clientIp) . "</code></p><p style='opacity:.6;font-size:.85rem'>Conéctate desde la LAN o accede con <code>?key=TOKEN</code>.</p></div></body></html>";
    exit;
}
