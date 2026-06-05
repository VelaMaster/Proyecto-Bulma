<?php
// distribucion/comparar_lecherias.php
// Compara lecherías en BDD SQLite (tabla 'lecheria') contra el catálogo
// derivado del OPE042026DICONSA.xls (referencia: lecherias_ope_abril.php).
//
// Salida HTML simple con 3 secciones:
//   - Sobran   (en BDD pero NO en OPE)
//   - Faltan   (en OPE pero NO en BDD)
//   - Coinciden (resumen)
//
// URL: /distribucion/comparar_lecherias.php

require_once __DIR__ . '/../includes/session_guard.php';
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'distribucion') {
    http_response_code(403); exit('Acceso denegado.');
}

$catOpe = require __DIR__ . '/catalogos/lecherias_ope_abril.php';
$catAlmZon = require __DIR__ . '/catalogos/almacen_zona.php';

// Set de todas las lecherías esperadas (OPE)
$opeSet = [];
$opePorAlmacen = [];
foreach ($catOpe as $alm => $lechs) {
    foreach ($lechs as $l) {
        $opeSet[$l] = $alm;
        $opePorAlmacen[$alm][] = $l;
    }
}

// Lecherías en BDD
$pdo = DatabaseSQLite::getInstance();
$rowsBdd = $pdo->query("
    SELECT LECHER, TRIM(ALMACEN_RURAL) AS ALMACEN, TIPO_PUNTO_VENTA, EN_OPERACION
    FROM lecheria
    WHERE COALESCE(EN_OPERACION, 0) = 0
    ORDER BY ALMACEN_RURAL, LECHER
")->fetchAll(PDO::FETCH_ASSOC);

$bddSet = [];
$bddPorAlmacen = [];
foreach ($rowsBdd as $r) {
    $l = (int)$r['LECHER'];
    $bddSet[$l] = $r['ALMACEN'];
    $bddPorAlmacen[$r['ALMACEN']][] = $l;
}

// Normalizar almacenes (alias)
$alias = $catAlmZon['alias'];
function norm($s, $alias) {
    $s = strtoupper(trim((string)$s));
    $s = strtr($s, ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N']);
    return $alias[$s] ?? $s;
}

$sobran  = array_diff(array_keys($bddSet), array_keys($opeSet));
$faltan  = array_diff(array_keys($opeSet), array_keys($bddSet));
$coincid = array_intersect(array_keys($bddSet), array_keys($opeSet));

?>
<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">
<head>
<meta charset="UTF-8">
<title>Comparación BDD ↔ OPE Abril</title>
<link rel="stylesheet" href="../main_md3.css">
<style>
    body { padding: 24px; font-family: 'Roboto', sans-serif; }
    .grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-top: 16px; }
    .stat { padding: 16px 20px; border-radius: 14px; }
    .stat.ok   { background: color-mix(in srgb, var(--md-sys-color-primary-container) 70%, transparent); }
    .stat.warn { background: color-mix(in srgb, var(--md-sys-color-error-container) 70%, transparent); }
    .stat.info { background: color-mix(in srgb, var(--md-sys-color-secondary-container) 70%, transparent); }
    .stat .num { font-size: 2rem; font-weight: 500; }
    details { background: var(--md-sys-color-surface-container); padding: 12px 16px; border-radius: 12px; margin-bottom: 10px; }
    summary { cursor: pointer; font-weight: 500; padding: 4px 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { padding: 6px 10px; text-align: left; border-bottom: 1px solid var(--md-sys-color-outline-variant); font-size: 0.88rem; }
    th { color: var(--md-sys-color-on-surface-variant); font-weight: 500; text-transform: uppercase; font-size: 0.72rem; }
    .lecher-list { font-family: monospace; font-size: 0.82rem; line-height: 1.6; }
</style>
</head>
<body>
    <h1 style="margin:0 0 4px;">Comparación BDD ↔ OPE Abril 2026</h1>
    <p style="margin:0; color: var(--md-sys-color-on-surface-variant);">
        Lecherías en BDD (<code>lecheria</code> con <code>EN_OPERACION=0</code>) vs catálogo OPE042026DICONSA.xls (632 lecherías).
    </p>

    <div class="grid">
        <div class="stat ok">
            <div style="font-size:.8rem; opacity:.8;">COINCIDEN</div>
            <div class="num"><?= count($coincid) ?></div>
            <div style="font-size:.78rem;">Lecherías en ambos</div>
        </div>
        <div class="stat warn">
            <div style="font-size:.8rem; opacity:.8;">SOBRAN (BDD)</div>
            <div class="num"><?= count($sobran) ?></div>
            <div style="font-size:.78rem;">En BDD pero NO en OPE</div>
        </div>
        <div class="stat warn">
            <div style="font-size:.8rem; opacity:.8;">FALTAN (BDD)</div>
            <div class="num"><?= count($faltan) ?></div>
            <div style="font-size:.78rem;">En OPE pero NO en BDD</div>
        </div>
    </div>

    <h2 style="margin-top:32px;">Sobran en BDD (no aparecen en OPE)</h2>
    <?php if (empty($sobran)): ?>
        <p style="color: var(--md-sys-color-primary);">✓ Ninguna lechería sobra.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>LECHER</th><th>Almacén (BDD)</th><th>Tipo</th></tr></thead>
            <tbody>
            <?php foreach ($sobran as $l):
                $r = array_values(array_filter($rowsBdd, fn($x)=>(int)$x['LECHER']===$l))[0] ?? [];
            ?>
                <tr>
                    <td><code><?= htmlspecialchars((string)$l) ?></code></td>
                    <td><?= htmlspecialchars($r['ALMACEN'] ?? '?') ?></td>
                    <td><?= (int)($r['TIPO_PUNTO_VENTA'] ?? 0) === 0 ? '$4.50' : '$6.50' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2 style="margin-top:32px;">Faltan en BDD (están en OPE pero no en BDD)</h2>
    <?php if (empty($faltan)): ?>
        <p style="color: var(--md-sys-color-primary);">✓ Ninguna lechería falta.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>LECHER</th><th>Almacén (OPE)</th><th>Zona</th></tr></thead>
            <tbody>
            <?php foreach ($faltan as $l):
                $alm = $opeSet[$l];
                $almLookup = strtoupper(strtr($alm, ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N']));
                $zona = $catAlmZon['zona_de'][$almLookup] ?? '?';
            ?>
                <tr>
                    <td><code><?= htmlspecialchars((string)$l) ?></code></td>
                    <td><?= htmlspecialchars($alm) ?></td>
                    <td><?= htmlspecialchars($zona) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2 style="margin-top:32px;">Detalle por almacén</h2>
    <?php foreach ($catOpe as $alm => $lechs):
        $bddLechs = $bddPorAlmacen[$alm] ?? [];
        $diff_sobra = array_diff($bddLechs, $lechs);
        $diff_falta = array_diff($lechs, $bddLechs);
    ?>
        <details>
            <summary>
                <?= htmlspecialchars($alm) ?>
                <span style="opacity:.75;">— OPE: <?= count($lechs) ?> · BDD: <?= count($bddLechs) ?></span>
                <?php if (count($diff_sobra) || count($diff_falta)): ?>
                    <span style="color:var(--md-sys-color-error); margin-left:8px;">
                        ⚠ <?= count($diff_falta) ?> faltan, <?= count($diff_sobra) ?> sobran
                    </span>
                <?php else: ?>
                    <span style="color:var(--md-sys-color-primary); margin-left:8px;">✓ coinciden</span>
                <?php endif; ?>
            </summary>
            <?php if ($diff_falta): ?>
                <p style="margin:8px 0 4px;"><strong>Faltan en BDD:</strong></p>
                <div class="lecher-list"><?= implode(', ', $diff_falta) ?></div>
            <?php endif; ?>
            <?php if ($diff_sobra): ?>
                <p style="margin:8px 0 4px;"><strong>Sobran en BDD (no están en OPE):</strong></p>
                <div class="lecher-list"><?= implode(', ', $diff_sobra) ?></div>
            <?php endif; ?>
        </details>
    <?php endforeach; ?>

    <p style="margin-top:24px;">
        <a href="inicio.php">← Volver a Distribución</a>
    </p>
</body>
</html>
