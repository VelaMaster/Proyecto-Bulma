<?php
// admin/poblar_usuarios.php
// Crea cuentas en usuarios_inventarios para promotores y supervisores que aún
// no tengan acceso. Convención solicitada:
//   • Promotores: USUARIO = PMT_NUMERO (nómina), CONTRASENA = misma nómina (hash).
//   • Supervisores: USUARIO = ID_SUPERVISOR, CONTRASENA = mismo ID (hash).
// Sólo se procesan promotores activos (PMT_ACTIVO='S') y supervisores con ID > 0.
// NO sobrescribe usuarios existentes (apodos, cuentas manuales).

require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';
require_once __DIR__ . '/../src/Servicio/PasswordServicio.php';

$pdo = DatabaseSQLite::getInstance();

// ── Cálculo del preview (qué se insertaría / qué ya existe) ─────────
$promotoresActivos = $pdo->query(
    "SELECT PMT_NUMERO, PMT_NOMBRE
       FROM promotor
      WHERE PMT_ACTIVO = 'S'
      ORDER BY PMT_NUMERO"
)->fetchAll(PDO::FETCH_ASSOC);

$supervisoresActivos = $pdo->query(
    "SELECT ID_SUPERVISOR, NOMBRE_SUPERVISOR
       FROM supervisor
      WHERE ID_SUPERVISOR > 0
        AND COALESCE(ACTIVO, 1) = 1
      ORDER BY ID_SUPERVISOR"
)->fetchAll(PDO::FETCH_ASSOC);

// Existentes en usuarios_inventarios, separando por rol y por CLAVE_ROL
$existentesPromotor = [];
$existentesSupervisor = [];
foreach ($pdo->query("SELECT USUARIO, ROL, CLAVE_ROL FROM usuarios_inventarios") as $u) {
    if ((string)$u['ROL'] === '0' && $u['CLAVE_ROL'] !== null) {
        $existentesPromotor[(int)$u['CLAVE_ROL']] = $u['USUARIO'];
    } elseif ((string)$u['ROL'] === '1' && $u['CLAVE_ROL'] !== null) {
        $existentesSupervisor[(int)$u['CLAVE_ROL']] = $u['USUARIO'];
    }
}

// Lista de "nuevos a crear"
$nuevosProm = [];
foreach ($promotoresActivos as $p) {
    $num = (int)$p['PMT_NUMERO'];
    // Si la nómina ya está como USUARIO o como CLAVE_ROL existente de un promotor, se omite
    if (isset($existentesPromotor[$num])) continue;
    // Comprobar también si ya existe un usuario con USUARIO = num (por seguridad)
    $check = $pdo->prepare("SELECT 1 FROM usuarios_inventarios WHERE USUARIO = :u");
    $check->execute([':u' => (string)$num]);
    if ($check->fetchColumn()) continue;
    $nuevosProm[] = $p;
}

$nuevosSup = [];
foreach ($supervisoresActivos as $s) {
    $id = (int)$s['ID_SUPERVISOR'];
    if (isset($existentesSupervisor[$id])) continue;
    $check = $pdo->prepare("SELECT 1 FROM usuarios_inventarios WHERE USUARIO = :u");
    $check->execute([':u' => (string)$id]);
    if ($check->fetchColumn()) continue;
    $nuevosSup[] = $s;
}

// ── Aplicar inserción ───────────────────────────────────────────────
$resumen = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'aplicar') {
    $insP = 0; $insS = 0; $errores = [];
    $stmt = $pdo->prepare(
        "INSERT INTO usuarios_inventarios (USUARIO, NOMBRE, CONTRASENA, ROL, CLAVE_ROL, ACTIVO)
         VALUES (:u, :n, :c, :r, :k, 1)"
    );
    $pdo->beginTransaction();
    try {
        foreach ($nuevosProm as $p) {
            $num  = (string)(int)$p['PMT_NUMERO'];
            try {
                $stmt->execute([
                    ':u' => $num,
                    ':n' => trim((string)$p['PMT_NOMBRE']),
                    ':c' => PasswordServicio::hashear($num),
                    ':r' => '0',
                    ':k' => (int)$p['PMT_NUMERO'],
                ]);
                $insP++;
            } catch (\Throwable $e) { $errores[] = "Promotor $num: " . $e->getMessage(); }
        }
        foreach ($nuevosSup as $s) {
            $id = (string)(int)$s['ID_SUPERVISOR'];
            try {
                $stmt->execute([
                    ':u' => $id,
                    ':n' => trim((string)$s['NOMBRE_SUPERVISOR']),
                    ':c' => PasswordServicio::hashear($id),
                    ':r' => '1',
                    ':k' => (int)$s['ID_SUPERVISOR'],
                ]);
                $insS++;
            } catch (\Throwable $e) { $errores[] = "Supervisor $id: " . $e->getMessage(); }
        }
        $pdo->commit();
        $resumen = ['ok' => true, 'prom' => $insP, 'sup' => $insS, 'errores' => $errores];
        // Refrescar preview tras inserción
        header('Location: poblar_usuarios.php?ok=' . urlencode("Creados: $insP promotores, $insS supervisores"));
        exit;
    } catch (\Throwable $e) {
        $pdo->rollBack();
        $resumen = ['ok' => false, 'mensaje' => $e->getMessage()];
    }
}

$msgOk = $_GET['ok'] ?? null;
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">
<head>
<meta charset="UTF-8">
<title>Admin · Poblar usuarios</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
<link rel="stylesheet" href="../main_md3.css">
<link rel="stylesheet" href="../estilos/admin_md3.css">
</head>
<body>
<div class="wrap">
  <div class="crumb"><a href="index.php">← Panel</a> / Poblar usuarios</div>
  <h1>Crear cuentas masivas (promotores y supervisores)</h1>
  <p class="sub">
    Crea acceso para todos los promotores activos y supervisores que aún no tengan cuenta.
    El <strong>usuario</strong> y la <strong>contraseña inicial</strong> son su <strong>número de nómina</strong>
    (los promotores podrán cambiarla luego desde <em>Contraseña</em> en su panel).
  </p>

  <?php if ($msgOk): ?>
    <div class="alert alert-ok">✔ <?= htmlspecialchars($msgOk) ?></div>
  <?php endif; ?>
  <?php if ($resumen && !$resumen['ok']): ?>
    <div class="alert alert-err">✘ <?= htmlspecialchars($resumen['mensaje']) ?></div>
  <?php endif; ?>

  <!-- ── Resumen numérico ─────────────────────────────────────────── -->
  <div class="admin-grid">
    <div class="stat-card">
      <div class="label">Promotores activos en BDD</div>
      <div class="value"><?= count($promotoresActivos) ?></div>
    </div>
    <div class="stat-card">
      <div class="label">Promotores con cuenta</div>
      <div class="value"><?= count($existentesPromotor) ?></div>
    </div>
    <div class="stat-card is-accent">
      <div class="label">Promotores a crear</div>
      <div class="value"><?= count($nuevosProm) ?></div>
    </div>
    <div class="stat-card">
      <div class="label">Supervisores activos</div>
      <div class="value"><?= count($supervisoresActivos) ?></div>
    </div>
    <div class="stat-card">
      <div class="label">Supervisores con cuenta</div>
      <div class="value"><?= count($existentesSupervisor) ?></div>
    </div>
    <div class="stat-card is-accent">
      <div class="label">Supervisores a crear</div>
      <div class="value"><?= count($nuevosSup) ?></div>
    </div>
  </div>

  <!-- ── Botón aplicar ─────────────────────────────────────────────── -->
  <?php if (count($nuevosProm) + count($nuevosSup) > 0): ?>
    <div class="panel">
      <form method="POST" onsubmit="return confirm('Se crearán <?= count($nuevosProm) ?> promotores y <?= count($nuevosSup) ?> supervisores. Las contraseñas iniciales son el número de nómina. ¿Continuar?');">
        <input type="hidden" name="accion" value="aplicar">
        <button class="btn btn-primary btn-big" type="submit">
          <span class="material-symbols-outlined">group_add</span>
          Crear <?= count($nuevosProm) + count($nuevosSup) ?> cuentas (con contraseña hasheada)
        </button>
        <p class="hint">Los usuarios ya existentes (incluidos los apodos) NO se tocan.</p>
      </form>
    </div>
  <?php else: ?>
    <div class="alert alert-ok">✔ Todos los promotores activos y supervisores ya tienen cuenta. Nada que crear.</div>
  <?php endif; ?>

  <!-- ── Preview: lista de promotores a crear ─────────────────────── -->
  <?php if (count($nuevosProm) > 0): ?>
  <div class="panel">
    <h3 style="margin-top:0;font-weight:500;font-size:1rem">Promotores a crear (<?= count($nuevosProm) ?>)</h3>
    <table>
      <thead><tr><th>USUARIO (nómina)</th><th>Nombre</th><th>Contraseña inicial</th></tr></thead>
      <tbody>
        <?php foreach ($nuevosProm as $p): $num = (int)$p['PMT_NUMERO']; ?>
          <tr>
            <td><code><?= $num ?></code></td>
            <td><?= htmlspecialchars(trim((string)$p['PMT_NOMBRE'])) ?></td>
            <td><code><?= $num ?></code></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <!-- ── Preview: lista de supervisores a crear ───────────────────── -->
  <?php if (count($nuevosSup) > 0): ?>
  <div class="panel">
    <h3 style="margin-top:0;font-weight:500;font-size:1rem">Supervisores a crear (<?= count($nuevosSup) ?>)</h3>
    <table>
      <thead><tr><th>USUARIO (ID supervisor)</th><th>Nombre</th><th>Contraseña inicial</th></tr></thead>
      <tbody>
        <?php foreach ($nuevosSup as $s): $id = (int)$s['ID_SUPERVISOR']; ?>
          <tr>
            <td><code><?= $id ?></code></td>
            <td><?= htmlspecialchars(trim((string)$s['NOMBRE_SUPERVISOR'])) ?></td>
            <td><code><?= $id ?></code></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
