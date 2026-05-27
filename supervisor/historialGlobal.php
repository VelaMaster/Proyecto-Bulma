<?php
require_once __DIR__ . '/../includes/session_guard.php';
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    header('Location: ../iniciosesionSupervisor.php');
    exit();
}
$nombre_usuario = $_SESSION['nombre'] ?? $_SESSION['usuario'];
$supervisor_usr = $_SESSION['usuario'];

require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

$historial = [];
try {
    $db = DatabaseSQLite::getInstance();
    $stmt = $db->prepare("
        SELECT mes, anio, precio, pdf_nombre, total_general, total_lecherias, fecha_captura
        FROM requerimiento_supervisor
        WHERE supervisor_usr = ?
        ORDER BY anio DESC, mes DESC, fecha_captura DESC
    ");
    $stmt->execute([$supervisor_usr]);
    $historial = $stmt->fetchAll();
} catch (Throwable $e) {
    $historial = [];
}

$meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
          'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$dirPdf = __DIR__ . '/../datos/supervisores/requerimientos_dotacion/';
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial General - Supervisor</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../main_md3.css">
    <link rel="stylesheet" href="../estilos/iniciocards.css">
    <style>
        .hist-table { width:100%; border-collapse:collapse; font-size:0.9rem; }
        .hist-table th, .hist-table td {
            padding:10px 14px; border-bottom:1px solid var(--md-sys-color-outline-variant); text-align:left;
        }
        .hist-table th { font-weight:600; color:var(--md-sys-color-on-surface-variant); }
        .hist-table tr:hover td { background:var(--md-sys-color-surface-container); }
        .pill {
            display:inline-flex; align-items:center; gap:5px;
            padding:3px 10px; border-radius:999px; font-size:0.8rem; font-weight:600;
        }
        .pill-ok    { background:color-mix(in srgb,var(--md-sys-color-primary) 20%,transparent); color:var(--md-sys-color-primary); }
        .pill-falta { background:color-mix(in srgb,var(--md-sys-color-error) 18%,transparent);   color:var(--md-sys-color-error);   }
    </style>
    <script type="importmap">{ "imports": { "@material/web/": "https://esm.run/@material/web/" } }</script>
    <script type="module"> import '@material/web/all.js'; </script>
</head>
<body>
    <header class="md3-top-app-bar">
        <div class="app-bar-start">
            <md-icon-button class="mobile-menu-btn" onclick="toggleDrawer()">
                <md-icon>menu</md-icon>
            </md-icon-button>
            <div class="app-brand"><span>Liconsa - Supervisión</span></div>
        </div>
        <div class="app-bar-end">
            <div class="desktop-nav">
                <md-text-button href="inicio.php">
                    <md-icon slot="icon">home</md-icon>Inicio
                </md-text-button>
                <md-text-button href="lecherias.php">
                    <md-icon slot="icon">storefront</md-icon>Lecherías
                </md-text-button>
                <md-text-button href="requerimientodedotacion.php">
                    <md-icon slot="icon">description</md-icon>Requerimiento
                </md-text-button>
            </div>
            <md-filled-tonal-button href="../cerrar_sesionsupervisor.php" style="margin-left:16px;">
                <md-icon slot="icon">logout</md-icon>Salir
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
            <md-list-item href="lecherias.php" type="button">
                <div slot="headline">Lecherías</div><md-icon slot="start">storefront</md-icon>
            </md-list-item>
            <md-list-item href="requerimientodedotacion.php" type="button">
                <div slot="headline">Requerimiento</div><md-icon slot="start">description</md-icon>
            </md-list-item>
            <md-list-item href="historialGlobal.php" type="button">
                <div slot="headline">Historial General</div><md-icon slot="start">history</md-icon>
            </md-list-item>
        </md-list>
    </aside>

    <main class="panel-content">
        <div class="md3-card" style="display:flex;align-items:center;gap:16px;margin-bottom:16px;">
            <div style="background:var(--md-sys-color-primary-container);border-radius:16px;padding:10px;display:flex;">
                <md-icon style="color:var(--md-sys-color-on-primary-container);font-size:32px;width:32px;height:32px;">history</md-icon>
            </div>
            <div>
                <h2 style="margin:0;font-size:1.5rem;font-weight:500;">Historial General</h2>
                <p style="margin:4px 0 0;font-size:0.9rem;color:var(--md-sys-color-on-surface-variant);">
                    Requerimientos de dotación generados por tu zona.
                </p>
            </div>
        </div>

        <div class="md3-card">
            <?php if (empty($historial)): ?>
                <div style="text-align:center;padding:40px;color:var(--md-sys-color-on-surface-variant);">
                    <span class="material-symbols-outlined" style="font-size:48px;display:block;margin-bottom:12px;opacity:.5;">inbox</span>
                    Aún no has generado requerimientos.
                </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="hist-table">
                    <thead>
                        <tr>
                            <th>Mes / Año</th>
                            <th>Precio</th>
                            <th>Lecherías</th>
                            <th>Total dotación</th>
                            <th>Fecha captura</th>
                            <th>PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($historial as $r):
                        $label    = ($meses[(int)$r['mes']] ?? $r['mes']) . ' ' . $r['anio'];
                        $pdfExiste = $r['pdf_nombre'] && file_exists($dirPdf . $r['pdf_nombre']);
                        $pdfUrl   = $pdfExiste
                            ? 'ver_pdf_supervisor.php?archivo=' . urlencode($r['pdf_nombre'])
                            : null;
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($label) ?></strong></td>
                            <td>$<?= htmlspecialchars($r['precio']) ?>/L</td>
                            <td><?= (int)$r['total_lecherias'] ?></td>
                            <td><?= number_format((int)$r['total_general']) ?></td>
                            <td style="font-size:0.82rem;color:var(--md-sys-color-on-surface-variant);">
                                <?= htmlspecialchars(substr($r['fecha_captura'] ?? '', 0, 16)) ?>
                            </td>
                            <td>
                            <?php if ($pdfExiste): ?>
                                <md-outlined-button onclick="window.open('<?= $pdfUrl ?>','_blank')">
                                    <md-icon slot="icon">picture_as_pdf</md-icon>Ver PDF
                                </md-outlined-button>
                            <?php else: ?>
                                <span class="pill pill-falta">
                                    <span class="material-symbols-outlined" style="font-size:14px;">block</span>
                                    Sin archivo
                                </span>
                            <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="../js/temas_md3.js"></script>
    <script>
        function toggleDrawer() {
            document.getElementById('mobile-drawer')?.classList.toggle('open');
            document.getElementById('drawer-scrim')?.classList.toggle('open');
        }
    </script>
</body>
</html>
