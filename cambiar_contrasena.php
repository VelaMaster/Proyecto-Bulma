<?php
// /cambiar_contrasena.php
// Endpoint compartido para que promotores y supervisores cambien su contraseña.
// Verifica contraseña actual (texto plano, mismo esquema que login) y actualiza
// usuarios_inventarios.CONTRASENA del usuario en sesión.

require_once __DIR__ . '/includes/session_guard.php';
require_once __DIR__ . '/src/Database/DatabaseSQLite.php';

if (!isset($_SESSION['usuario']) || !in_array(($_SESSION['rol'] ?? ''), ['promotor', 'supervisor'], true)) {
    header("Location: /iniciosesionPromotor.php");
    exit();
}

$usuario   = $_SESSION['usuario'];
$nombre    = $_SESSION['nombre'] ?? $usuario;
$rol       = $_SESSION['rol'];
$mensaje   = null;
$tipo_msg  = null; // 'ok' | 'err'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actual    = trim($_POST['actual']   ?? '');
    $nueva     = trim($_POST['nueva']    ?? '');
    $confirmar = trim($_POST['confirmar'] ?? '');

    if ($actual === '' || $nueva === '' || $confirmar === '') {
        $mensaje = 'Completa todos los campos.';
        $tipo_msg = 'err';
    } elseif ($nueva !== $confirmar) {
        $mensaje = 'La nueva contraseña y la confirmación no coinciden.';
        $tipo_msg = 'err';
    } elseif (strlen($nueva) < 4) {
        $mensaje = 'La nueva contraseña debe tener al menos 4 caracteres.';
        $tipo_msg = 'err';
    } elseif ($nueva === $actual) {
        $mensaje = 'La nueva contraseña debe ser distinta a la actual.';
        $tipo_msg = 'err';
    } else {
        $db = DatabaseSQLite::getInstance();
        $stmt = $db->prepare("SELECT CONTRASENA FROM usuarios_inventarios WHERE USUARIO = :u AND COALESCE(ACTIVO,1)=1");
        $stmt->execute([':u' => $usuario]);
        $row = $stmt->fetch();
        if (!$row || (string)$row['CONTRASENA'] !== $actual) {
            $mensaje = 'La contraseña actual es incorrecta.';
            $tipo_msg = 'err';
        } else {
            $upd = $db->prepare("UPDATE usuarios_inventarios SET CONTRASENA = :p WHERE USUARIO = :u");
            $upd->execute([':p' => $nueva, ':u' => $usuario]);
            $mensaje = 'Contraseña actualizada correctamente.';
            $tipo_msg = 'ok';
        }
    }
}

$volver = $rol === 'supervisor' ? '/supervisor/inicio.php' : '/promotores/inicio.php';
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar contraseña</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="/main_md3.css">
    <script type="module" src="https://esm.run/@material/web@1.0.0/all.js"></script>
    <style>
        body { background: var(--md-sys-color-background, #1c1b1f); color: var(--md-sys-color-on-background, #e6e1e5); margin: 0; min-height:100vh; display:flex; align-items:center; justify-content:center; font-family: 'Roboto', sans-serif; }
        .card { background: var(--md-sys-color-surface-container, #211f26); padding: 28px 24px; border-radius: 20px; width: min(420px, 92vw); box-shadow: 0 8px 32px rgba(0,0,0,.35); }
        h1 { font-size: 1.35rem; margin: 0 0 6px; font-weight: 500; }
        .sub { opacity:.75; font-size:.9rem; margin: 0 0 18px; }
        .row { margin-bottom: 14px; }
        md-outlined-text-field { width: 100%; }
        .actions { display:flex; gap:10px; justify-content:flex-end; margin-top: 18px; }
        .msg { padding:10px 12px; border-radius:10px; margin-bottom:14px; font-size:.92rem; }
        .msg.ok  { background: rgba(76,175,80,.16); color:#a5d6a7; }
        .msg.err { background: rgba(244,67,54,.16); color:#ef9a9a; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Cambiar contraseña</h1>
        <p class="sub"><?= htmlspecialchars($nombre) ?> · <code><?= htmlspecialchars($usuario) ?></code></p>

        <?php if ($mensaje): ?>
            <div class="msg <?= $tipo_msg === 'ok' ? 'ok' : 'err' ?>"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <div class="row">
                <md-outlined-text-field type="password" label="Contraseña actual" name="actual" required></md-outlined-text-field>
            </div>
            <div class="row">
                <md-outlined-text-field type="password" label="Nueva contraseña" name="nueva" minlength="4" required></md-outlined-text-field>
            </div>
            <div class="row">
                <md-outlined-text-field type="password" label="Confirmar nueva contraseña" name="confirmar" minlength="4" required></md-outlined-text-field>
            </div>

            <div class="actions">
                <md-text-button type="button" href="<?= $volver ?>">Cancelar</md-text-button>
                <md-filled-button type="submit">
                    <md-icon slot="icon">save</md-icon>
                    Guardar
                </md-filled-button>
            </div>
        </form>
    </div>
</body>
</html>
