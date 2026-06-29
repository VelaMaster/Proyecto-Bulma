<?php
require_once __DIR__ . '/../includes/session_guard.php';
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    header('Location: ../iniciosesionSupervisor.php');
    exit();
}
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

$nombre_usuario = $_SESSION['nombre'] ?? $_SESSION['usuario'];
$id_supervisor  = $_SESSION['clave_rol'] ?? 0;
$pdo            = DatabaseSQLite::getInstance();

$mes  = isset($_GET['mes'])  ? (int)$_GET['mes']  : (int)date('n');
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');

// ── Catálogo base de la zona del supervisor ───────────────────────────
$stmt = $pdo->prepare("
    SELECT TRIM(L.ALMACEN_RURAL) AS ALMACEN,
           L.TIPO_PUNTO_VENTA    AS TIPO,
           L.LECHER              AS LECHER,
           L.PROMOTOR            AS PROMOTOR
    FROM lecheria L
    JOIN mapeo_supervisor_lecheria M
          ON M.LECHER = L.LECHER AND M.ID_SUPERVISOR = :sup
    WHERE COALESCE(L.EN_OPERACION, 0) = 0
");
$stmt->execute([':sup' => $id_supervisor]);
$lecherias = $stmt->fetchAll();

$totalLecherias = count($lecherias);
$totalPromotores = count(array_unique(array_map(fn($r) => (int)$r['PROMOTOR'], $lecherias)));
$almacenes = array_unique(array_map(fn($r) => trim((string)$r['ALMACEN']), $lecherias));
$almacenes = array_values(array_filter($almacenes));
sort($almacenes);

$porTipo = ['0' => 0, '1' => 0, '2' => 0];
foreach ($lecherias as $r) {
    $t = (string)(int)$r['TIPO'];
    if (isset($porTipo[$t])) $porTipo[$t]++;
}

// ── Inventarios mensuales capturados en el mes/año seleccionado ───────
$claves = array_map(fn($r) => (string)$r['LECHER'], $lecherias);
$captInv = 0;
if (!empty($claves)) {
    $ph = implode(',', array_fill(0, count($claves), '?'));
    // Soporta variantes con sufijo "00" (DM)
    $clavesExt = array_merge($claves, array_map(fn($k) => $k . '00', $claves));
    $ph2 = implode(',', array_fill(0, count($clavesExt), '?'));
    $st = $pdo->prepare("
        SELECT COUNT(DISTINCT CLAVE_LECHERIA)
        FROM inventarios_mensuales
        WHERE MES_PERIODO  = ?
          AND ANIO_PERIODO = ?
          AND CLAVE_LECHERIA IN ($ph2)
    ");
    $st->execute(array_merge([$mes, $anio], $clavesExt));
    $captInv = (int)$st->fetchColumn();
}
$pctAvance = $totalLecherias > 0 ? round(($captInv * 100) / $totalLecherias) : 0;

// ── Reportes generados / aprobados ────────────────────────────────────
$st = $pdo->prepare("
    SELECT COUNT(DISTINCT usuario_captura)              AS generados,
           SUM(CASE WHEN aprobado = 1 THEN 1 ELSE 0 END) AS lecherias_aprob
    FROM reporte_mensual_lecher
    WHERE mes = ? AND anio = ?
      AND usuario_captura IN (
            SELECT 'promotor_' || P.PMT_NUMERO FROM promotor P
            UNION
            SELECT U.USUARIO FROM usuarios_inventarios U WHERE U.ROL IN ('0','promotor')
          )
");
$st->execute([$mes, $anio]);
$rep = $st->fetch() ?: ['generados' => 0, 'lecherias_aprob' => 0];

$meses = ['', 'Enero','Febrero','Marzo','Abril','Mayo','Junio',
          'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas de Zona — Supervisor</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../main_md3.css">
    <link rel="stylesheet" href="../estilos/iniciocards.css">
    <style>
        .stats-grid{
            display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
            gap:14px; margin-top:16px;
        }
        .stat-card{
            padding:18px 22px; border-radius:16px;
            background:var(--md-sys-color-surface-container);
            border:1px solid var(--md-sys-color-outline-variant);
            display:flex; flex-direction:column; gap:6px;
        }
        .stat-card .label{
            font-size:.75rem; color:var(--md-sys-color-on-surface-variant);
            text-transform:uppercase; letter-spacing:.5px;
        }
        .stat-card .value{ font-size:1.9rem; font-weight:600; color:var(--md-sys-color-primary); }
        .stat-card .sub  { font-size:.78rem; color:var(--md-sys-color-on-surface-variant); }
        .filtros-card{ display:flex; flex-wrap:wrap; align-items:center; gap:14px; padding:16px 20px; margin-top:16px; }
        .lista{ display:flex; flex-wrap:wrap; gap:8px; margin-top:8px; }
        .chip{
            padding:4px 12px; border-radius:999px;
            background:var(--md-sys-color-secondary-container);
            color:var(--md-sys-color-on-secondary-container);
            font-size:.78rem; font-weight:500;
        }
        .progress-bar{
            position:relative; height:10px; border-radius:999px;
            background:var(--md-sys-color-surface-container-high); overflow:hidden;
        }
        .progress-bar .fill{
            position:absolute; inset:0; width:<?= (int)$pctAvance ?>%;
            background:var(--md-sys-color-primary); border-radius:999px;
            transition:width .4s ease;
        }
    </style>
    <script type="importmap">{ "imports": { "@material/web/": "https://esm.run/@material/web/" } }</script>
    <script type="module"> import '@material/web/all.js'; </script>
</head>
<body>
    <header class="md3-top-app-bar">
        <div class="app-bar-start">
            <md-icon-button onclick="toggleDrawer()"><md-icon>menu</md-icon></md-icon-button>
            <div class="app-brand"><span>Liconsa — Supervisión</span></div>
        </div>
        <div class="app-bar-end">
            <div class="desktop-nav">
                <md-text-button href="inicio.php"><md-icon slot="icon">home</md-icon> Inicio</md-text-button>
                <md-text-button href="listadoReportesPromotores.php"><md-icon slot="icon">receipt_long</md-icon> Reporte Mensual</md-text-button>
                <md-text-button href="requerimientodedotacion.php"><md-icon slot="icon">fact_check</md-icon> Requerimiento de Dotación</md-text-button>
                <md-text-button href="inventario_almacen.php"><md-icon slot="icon">warehouse</md-icon> Inventario de Almacén</md-text-button>
            </div>
            <md-filled-tonal-button href="../cerrar_sesionsupervisor.php" style="margin-left:16px;">
                <md-icon slot="icon">logout</md-icon> Salir
            </md-filled-tonal-button>
        </div>
    </header>

    <div id="drawer-scrim" class="md3-drawer-scrim" onclick="toggleDrawer()"></div>
    <aside class="md3-drawer" id="mobile-drawer">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 16px 8px 24px;">
            <span style="font-size:1.25rem;font-weight:500;">Menú Supervisor</span>
            <md-icon-button onclick="toggleDrawer()"><md-icon>close</md-icon></md-icon-button>
        </div>
        <md-list style="background:transparent;">
            <md-list-item href="inicio.php" type="button">
                <div slot="headline">Inicio</div><md-icon slot="start">home</md-icon>
            </md-list-item>
            <md-divider style="margin:8px 0;"></md-divider>
            <md-list-item href="listadoReportesPromotores.php" type="button">
                <div slot="headline">Reporte Mensual</div><md-icon slot="start">receipt_long</md-icon>
            </md-list-item>
            <md-list-item href="requerimientodedotacion.php" type="button">
                <div slot="headline">Requerimiento de Dotación</div><md-icon slot="start">fact_check</md-icon>
            </md-list-item>
            <md-list-item href="inventario_almacen.php" type="button">
                <div slot="headline">Inventario de Almacén</div><md-icon slot="start">warehouse</md-icon>
            </md-list-item>
        </md-list>
    </aside>

    <main class="panel-content">
        <div class="md3-card md3-hero-card">
            <div style="display:flex; align-items:center; gap:16px;">
                <div style="background:var(--md-sys-color-tertiary-container); border-radius:16px; padding:10px; display:flex;">
                    <md-icon style="color:var(--md-sys-color-on-tertiary-container); font-size:32px; width:32px; height:32px;">monitoring</md-icon>
                </div>
                <div>
                    <h2 style="margin:0; font-size:1.6rem; font-weight:500;">Estadísticas de Zona</h2>
                    <p style="margin:4px 0 0; font-size:.9rem; color:var(--md-sys-color-on-surface-variant);">
                        Resumen de tu padrón y avance mensual de captura.
                    </p>
                </div>
            </div>
        </div>

        <form method="get" class="md3-card filtros-card">
            <md-outlined-select label="Mes" name="mes" id="selMes" style="min-width:140px;">
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <md-select-option value="<?= $i ?>" <?= $i === $mes ? 'selected' : '' ?>>
                        <div slot="headline"><?= htmlspecialchars($meses[$i]) ?></div>
                    </md-select-option>
                <?php endfor; ?>
            </md-outlined-select>
            <md-outlined-text-field label="Año" name="anio" type="number"
                value="<?= $anio ?>" style="max-width:110px;"></md-outlined-text-field>
            <md-filled-tonal-button type="submit">
                <md-icon slot="icon">filter_alt</md-icon> Aplicar
            </md-filled-tonal-button>
            <span style="flex:1;"></span>
            <span style="color:var(--md-sys-color-on-surface-variant); font-size:.85rem;">
                Supervisor: <strong><?= htmlspecialchars($nombre_usuario) ?></strong>
            </span>
        </form>

        <h3 style="font-size:1rem; font-weight:500; margin-top:18px; margin-bottom:4px; color:var(--md-sys-color-on-surface-variant);">
            Padrón asignado
        </h3>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Lecherías totales</div>
                <div class="value"><?= $totalLecherias ?></div>
                <div class="sub">en tu zona</div>
            </div>
            <div class="stat-card">
                <div class="label">Promotores asignados</div>
                <div class="value"><?= $totalPromotores ?></div>
                <div class="sub">activos</div>
            </div>
            <div class="stat-card">
                <div class="label">Almacenes rurales</div>
                <div class="value"><?= count($almacenes) ?></div>
                <div class="sub">distintos</div>
            </div>
            <div class="stat-card">
                <div class="label">Lecherías $4.50</div>
                <div class="value"><?= $porTipo['0'] ?></div>
                <div class="sub">tipo 0</div>
            </div>
            <div class="stat-card">
                <div class="label">Lecherías $6.50</div>
                <div class="value"><?= $porTipo['1'] ?></div>
                <div class="sub">tipo 1</div>
            </div>
            <div class="stat-card">
                <div class="label">Distrib. Mercantil</div>
                <div class="value"><?= $porTipo['2'] ?></div>
                <div class="sub">tipo 2 — DM</div>
            </div>
        </div>

        <h3 style="font-size:1rem; font-weight:500; margin-top:24px; margin-bottom:4px; color:var(--md-sys-color-on-surface-variant);">
            Avance — <?= htmlspecialchars($meses[$mes]) ?> <?= $anio ?>
        </h3>
        <div class="stats-grid">
            <div class="stat-card" style="grid-column:span 2; min-width:280px;">
                <div class="label">Captura de inventario mensual</div>
                <div class="value"><?= $captInv ?> / <?= $totalLecherias ?>  <span style="font-size:1rem; color:var(--md-sys-color-on-surface-variant);">(<?= $pctAvance ?>%)</span></div>
                <div class="progress-bar"><div class="fill"></div></div>
                <div class="sub">lecherías con inventario capturado</div>
            </div>
            <div class="stat-card">
                <div class="label">Reportes generados</div>
                <div class="value"><?= (int)$rep['generados'] ?></div>
                <div class="sub">por tus promotores</div>
            </div>
            <div class="stat-card">
                <div class="label">Lecherías aprobadas</div>
                <div class="value"><?= (int)$rep['lecherias_aprob'] ?></div>
                <div class="sub">listas para Distribución</div>
            </div>
        </div>

        <?php if (!empty($almacenes)): ?>
        <h3 style="font-size:1rem; font-weight:500; margin-top:24px; margin-bottom:4px; color:var(--md-sys-color-on-surface-variant);">
            Almacenes en tu zona
        </h3>
        <div class="md3-card" style="padding:16px 20px;">
            <div class="lista">
                <?php foreach ($almacenes as $a): ?>
                    <span class="chip"><?= htmlspecialchars($a) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <script src="../js/temas_md3.js"></script>
    <script>
        function toggleDrawer(){
            document.getElementById('mobile-drawer')?.classList.toggle('open');
            document.getElementById('drawer-scrim')?.classList.toggle('open');
        }
        // Auto-submit al cambiar filtros (sin botón Recargar)
        document.getElementById('selMes')?.addEventListener('change',
            (e) => e.target.closest('form').submit());
    </script>
</body>
</html>
