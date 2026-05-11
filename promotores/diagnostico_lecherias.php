<?php
// ────────────────────────────────────────────────────────────────────
//  Página de diagnóstico (temporal): muestra para el promotor logueado
//  todas sus lecherías con los campos que pueden indicar "baja":
//  EN_OPERACION + cualquier columna parecida que exista.
//  Sirve para identificar qué valor tiene Tovala Copalita y aplicar el
//  filtro correcto en mis_lecherias / reporte / requerimiento /
//  api_supervisor.
//
//  Una vez identificado el valor, se puede borrar este archivo.
// ────────────────────────────────────────────────────────────────────
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'promotor') {
    header("Location: ../iniciosesionPromotor.php");
    exit();
}
require_once __DIR__ . '/../Database.php';

$pdo = Database::getInstance();
$usuario = $_SESSION['usuario'];

try {
    // 1) Lecherías del promotor logueado
    $sql = "SELECT
                TRIM(L.LECHER)         AS LECHER,
                TRIM(L.NOMBRELECH)     AS NOMBRELECH,
                TRIM(L.NUM_TIENDA)     AS NUM_TIENDA,
                TRIM(L.ALMACEN_RURAL)  AS ALMACEN,
                L.EN_OPERACION         AS EN_OPERACION,
                L.TIPO_PUNTO_VENTA     AS TIPO_PUNTO_VENTA,
                L.PROMOTOR             AS PROMOTOR
            FROM LECHERIA L
            INNER JOIN USUARIOS_INVENTARIOS U ON L.PROMOTOR = U.CLAVE_ROL
            WHERE L.EFD_NUMERO = 20
              AND U.USUARIO = :usuario
            ORDER BY L.NOMBRELECH";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':usuario' => $usuario]);
    $lecherias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2) Conteo por valor de EN_OPERACION
    $conteoOp = [];
    foreach ($lecherias as $l) {
        $v = $l['EN_OPERACION'];
        $key = ($v === null || $v === '') ? '(NULL/vacío)' : (string)$v;
        $conteoOp[$key] = ($conteoOp[$key] ?? 0) + 1;
    }

    // 3) Lista de columnas de LECHERIA (para sugerir otras "candidato a baja")
    $sqlCols = "SELECT TRIM(RDB\$FIELD_NAME) AS NOMBRE
                FROM RDB\$RELATION_FIELDS
                WHERE RDB\$RELATION_NAME = 'LECHERIA'
                ORDER BY 1";
    $colsLech = [];
    try {
        $stCol = $pdo->query($sqlCols);
        $colsLech = $stCol->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) { /* ignoramos si el motor no expone metadata */ }

} catch (Exception $e) {
    $errorMsg = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico — Lecherías</title>
    <link rel="stylesheet" href="../main_md3.css">
    <style>
        body { padding: 24px; font-family: 'Roboto', sans-serif; }
        h1 { font-size: 1.5rem; margin: 0 0 8px; }
        h2 { font-size: 1.1rem; margin-top: 24px; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; margin-top: 8px; }
        th, td { padding: 8px 10px; border-bottom: 1px solid var(--md-sys-color-outline-variant); text-align: left; }
        th { background: var(--md-sys-color-surface-container-high); font-weight: 600; }
        .pill { display: inline-block; padding: 2px 8px; border-radius: 999px; font-weight: 600; font-size: 0.75rem; }
        .pill-ok { background: color-mix(in srgb, var(--md-sys-color-primary) 22%, transparent); color: var(--md-sys-color-primary); }
        .pill-warn { background: color-mix(in srgb, var(--md-sys-color-error) 18%, transparent); color: var(--md-sys-color-error); }
        .pill-null { background: color-mix(in srgb, var(--md-sys-color-tertiary) 22%, transparent); color: var(--md-sys-color-tertiary); }
        .nota { padding: 12px 16px; background: var(--md-sys-color-surface-container-high); border-radius: 12px; margin: 12px 0; font-size: 0.9rem; }
    </style>
</head>
<body>
    <h1>Diagnóstico de lecherías (temporal)</h1>
    <p>
        Sesión: <strong><?= htmlspecialchars($usuario) ?></strong>
        — Total lecherías asignadas: <strong><?= count($lecherias ?? []) ?></strong>
    </p>

    <?php if (!empty($errorMsg)): ?>
        <div class="nota" style="color: var(--md-sys-color-error);">
            ⚠️ Error: <?= htmlspecialchars($errorMsg) ?>
        </div>
    <?php endif; ?>

    <h2>1) Conteo por valor de EN_OPERACION</h2>
    <div class="nota">
        Si una lechería como <strong>Tovala Copalita</strong> está dada de baja, busca su valor en la tabla
        de abajo y dime cuál de estos valores es el de "baja". Con eso aplico el filtro.
    </div>
    <table>
        <thead><tr><th>Valor</th><th>Cuántas lecherías</th></tr></thead>
        <tbody>
            <?php foreach (($conteoOp ?? []) as $val => $n): ?>
                <tr>
                    <td><strong><?= htmlspecialchars((string)$val) ?></strong></td>
                    <td><?= (int)$n ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>2) Listado completo (busca Tovala Copalita)</h2>
    <table>
        <thead>
            <tr>
                <th>Lechería</th>
                <th>Nombre</th>
                <th>NUM_TIENDA</th>
                <th>Almacén</th>
                <th>EN_OPERACION</th>
                <th>TIPO_PUNTO_VENTA</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (($lecherias ?? []) as $l): ?>
                <?php
                    $v = $l['EN_OPERACION'];
                    $cls = ($v === null || $v === '') ? 'pill-null'
                         : (in_array(strtoupper(trim((string)$v)), ['S','A','1','SI','SÍ','OPERANDO']) ? 'pill-ok' : 'pill-warn');
                    $esTovala = stripos((string)$l['NOMBRELECH'], 'tovala') !== false
                              || stripos((string)$l['NOMBRELECH'], 'copalita') !== false;
                ?>
                <tr style="<?= $esTovala ? 'background: color-mix(in srgb, var(--md-sys-color-tertiary) 12%, transparent);' : '' ?>">
                    <td><strong><?= htmlspecialchars((string)$l['LECHER']) ?></strong>
                        <?= $esTovala ? '<span class="pill pill-warn" style="margin-left:8px;">⬅ esta</span>' : '' ?>
                    </td>
                    <td><?= htmlspecialchars((string)$l['NOMBRELECH']) ?></td>
                    <td><?= htmlspecialchars((string)$l['NUM_TIENDA']) ?></td>
                    <td><?= htmlspecialchars((string)$l['ALMACEN']) ?></td>
                    <td>
                        <span class="pill <?= $cls ?>">
                            <?= ($v === null || $v === '') ? '(NULL)' : htmlspecialchars((string)$v) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars((string)$l['TIPO_PUNTO_VENTA']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>3) Otras columnas de LECHERIA que podrían indicar baja</h2>
    <div class="nota">
        Si EN_OPERACION resulta no ser la columna que distingue baja, alguna de éstas sí.
        Búscalas con nombre tipo "BAJA", "ESTATUS", "ACTIVA", "ESTADO".
    </div>
    <?php if (!empty($colsLech)): ?>
        <div style="font-size:0.8rem; line-height:1.6;">
            <?php
                $candidatos = [];
                foreach ($colsLech as $c) {
                    if (preg_match('/BAJA|ACTIV|ESTATUS|ESTADO|VIGEN|OPERAC/i', $c)) {
                        $candidatos[] = $c;
                    }
                }
                if (empty($candidatos)) {
                    echo "<em>(No se encontraron columnas con nombres parecidos a estado/baja además de EN_OPERACION)</em>";
                } else {
                    foreach ($candidatos as $c) echo "<code style='margin-right:12px;'>$c</code>";
                }
            ?>
        </div>
        <details style="margin-top:8px;">
            <summary style="cursor:pointer; opacity:.7;">Ver todas las columnas de LECHERIA (<?= count($colsLech) ?>)</summary>
            <div style="font-size:0.75rem; opacity:.8; margin-top:8px;">
                <?= implode(', ', array_map('htmlspecialchars', $colsLech)) ?>
            </div>
        </details>
    <?php endif; ?>

    <p style="margin-top:32px;">
        <a href="inicio.php" style="color: var(--md-sys-color-primary);">← Volver al inicio</a>
    </p>
</body>
</html>
