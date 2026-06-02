<?php
/**
 * admin/guard.php
 * Allowlist de IPs para el panel /admin. Sin login: quien entre debe venir de la LAN privada.
 * Incluir AL INICIO de cada archivo dentro de /admin.
 */

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

    // IPv4
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) &&
        filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $ipL  = ip2long($ip);
        $subL = ip2long($subnet);
        $maskL = -1 << (32 - $mask);
        return ($ipL & $maskL) === ($subL & $maskL);
    }
    // IPv6 (loopback solamente)
    return $ip === $subnet;
}

function _admin_ip_permitida(string $ip): bool {
    $allow = [
        '127.0.0.1',
        '::1',
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
    ];
    foreach ($allow as $cidr) {
        if (_admin_ip_in_cidr($ip, $cidr)) return true;
    }
    return false;
}

$_clientIp = _admin_client_ip();
if (!_admin_ip_permitida($_clientIp)) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>403</title></head><body style='font-family:Roboto,sans-serif;background:#141218;color:#E6E1E5;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;'><div style='text-align:center'><h1 style='font-size:4rem;margin:0;color:#B3261E'>403</h1><p>Acceso restringido. IP no autorizada: <code>" . htmlspecialchars($_clientIp) . "</code></p></div></body></html>";
    exit;
}
