<?php
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

header('Content-Type: application/json; charset=utf-8');

$body   = json_decode(file_get_contents('php://input'), true) ?: [];
$accion = $body['accion'] ?? '';
$pdo    = DatabaseSQLite::getInstance();

try {
    if ($accion === 'crear') {
        $usuario    = trim($body['usuario'] ?? '');
        $nombre     = trim($body['nombre'] ?? '');
        $contrasena = $body['contrasena'] ?? '';
        $rol        = $body['rol'] ?? 'promotor';
        $claveRol   = isset($body['clave_rol']) && $body['clave_rol']!=='' ? (int)$body['clave_rol'] : null;

        if ($usuario === '' || $contrasena === '') {
            throw new RuntimeException('Usuario y contraseña son obligatorios');
        }
        // Política temporal: la BDD original guarda la contraseña tal cual.
        // Cuando migremos auth a SQLite, aquí cambiar por password_hash().
        $pdo->prepare(
            "INSERT INTO usuarios_inventarios (USUARIO, NOMBRE, CONTRASENA, ROL, CLAVE_ROL, ACTIVO)
             VALUES (?, ?, ?, ?, ?, 1)"
        )->execute([$usuario, $nombre, $contrasena, $rol, $claveRol]);

        echo json_encode(['ok' => true]);
        exit;
    }

    if ($accion === 'eliminar') {
        $usuario = trim($body['usuario'] ?? '');
        if ($usuario === '') throw new RuntimeException('Falta usuario');
        $pdo->prepare("DELETE FROM usuarios_inventarios WHERE USUARIO=?")->execute([$usuario]);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($accion === 'actualizar') {
        $usuario = trim($body['usuario'] ?? '');
        if ($usuario === '') throw new RuntimeException('Falta usuario');
        $campos = [];
        $vals   = [];
        foreach (['NOMBRE'=>'nombre','CONTRASENA'=>'contrasena','ROL'=>'rol','CLAVE_ROL'=>'clave_rol','ACTIVO'=>'activo'] as $col=>$key) {
            if (array_key_exists($key, $body)) { $campos[] = "$col=?"; $vals[] = $body[$key]; }
        }
        if (!$campos) throw new RuntimeException('Nada que actualizar');
        $vals[] = $usuario;
        $pdo->prepare("UPDATE usuarios_inventarios SET " . implode(',', $campos) . " WHERE USUARIO=?")->execute($vals);
        echo json_encode(['ok' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['ok'=>false,'mensaje'=>'Acción desconocida']);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'mensaje'=>$e->getMessage()]);
}
