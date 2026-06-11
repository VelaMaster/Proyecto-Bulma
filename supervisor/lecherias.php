<?php
require_once __DIR__ . '/../includes/session_guard.php';
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    header("Location: ../iniciosesionSupervisor.php");
    exit();
}
require_once __DIR__ . '/../src/Database/DatabaseSQLite.php';

$nombre_usuario = $_SESSION['nombre'] ?? $_SESSION['usuario'];
$id_supervisor  = $_SESSION['clave_rol'] ?? 0;
$promotor_pre   = isset($_GET['promotor']) ? (int)$_GET['promotor'] : 0;

// Promotores asignados al supervisor (server-side: evita el problema de
// upgrade tardío del md-outlined-select cuando los hijos se inyectan por JS).
$pdo = DatabaseSQLite::getInstance();
$stmt = $pdo->prepare("
    SELECT P.PMT_NUMERO AS id,
           TRIM(P.PMT_NOMBRE) AS nombre,
           COUNT(DISTINCT L.LECHER) AS cantidad_lecherias
    FROM promotor P
    JOIN lecheria L ON L.PROMOTOR = P.PMT_NUMERO
    JOIN mapeo_supervisor_lecheria M
          ON M.LECHER = L.LECHER AND M.ID_SUPERVISOR = :sup
    WHERE COALESCE(P.PMT_ACTIVO,'S') = 'S'
      AND COALESCE(L.EN_OPERACION, 0) = 0
    GROUP BY P.PMT_NUMERO, P.PMT_NOMBRE
    ORDER BY TRIM(P.PMT_NOMBRE)
");
$stmt->execute([':sup' => $id_supervisor]);
$promotores = $stmt->fetchAll() ?: [];
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecherías - Supervisor</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../main_md3.css">
    <link rel="stylesheet" href="../estilos/iniciocards.css">
    <style>
        .filtros-card {
            display: flex; flex-wrap: wrap; align-items: center; gap: 16px;
            padding: 16px 20px; margin-bottom: 16px;
        }
        .filtros-card label { font-size: 0.85rem; color: var(--md-sys-color-on-surface-variant); }
        .estado-pill {
            display:inline-flex; align-items:center; gap:6px;
            padding:4px 10px; border-radius:999px;
            font-size:0.8rem; font-weight:600;
        }
        .estado-ok    { background:color-mix(in srgb,var(--md-sys-color-primary)  22%,transparent); color:var(--md-sys-color-primary); }
        .estado-falta { background:color-mix(in srgb,var(--md-sys-color-error)    18%,transparent); color:var(--md-sys-color-error);   }
        .estado-info  { background:color-mix(in srgb,var(--md-sys-color-tertiary) 22%,transparent); color:var(--md-sys-color-tertiary);}
        .lechs-table {
            width: 100%; border-collapse: collapse; font-size: 0.9rem;
        }
        .lechs-table th, .lechs-table td {
            padding: 10px 12px; border-bottom: 1px solid var(--md-sys-color-outline-variant); text-align: left;
        }
        .lechs-table th { font-weight: 600; color: var(--md-sys-color-on-surface-variant); }
        .doc-block {
            display:flex; align-items:center; gap:14px; flex-wrap:wrap;
            padding:14px 16px; margin-top:8px;
            border:1px solid var(--md-sys-color-outline-variant);
            border-radius:14px;
        }
        .doc-block .doc-title { font-weight:500; min-width:160px; }
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
                <md-text-button href="listadoReportesPromotores.php">
                    <md-icon slot="icon">receipt_long</md-icon>Reporte Mensual
                </md-text-button>
                <md-text-button href="requerimientodedotacion.php">
                    <md-icon slot="icon">fact_check</md-icon>Requerimiento de Dotación
                </md-text-button>
                <md-text-button href="inventario_almacen.php">
                    <md-icon slot="icon">warehouse</md-icon>Inventario de Almacén
                </md-text-button>
            </div>

            <md-filled-tonal-button href="../cerrar_sesionsupervisor.php" style="margin-left: 16px;">
                <md-icon slot="icon">logout</md-icon> Salir
            </md-filled-tonal-button>
        </div>
    </header>

    <div id="drawer-scrim" class="md3-drawer-scrim" onclick="toggleDrawer()"></div>

    <aside class="md3-drawer" id="mobile-drawer">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:16px 16px 8px 24px;">
            <span style="font-size:1.25rem; font-weight:500;">Menú Supervisor</span>
            <md-icon-button onclick="toggleDrawer()"><md-icon>close</md-icon></md-icon-button>
        </div>
        <md-list style="background: transparent;">
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
        <div class="md3-card" style="display:flex; align-items:center; gap:16px;">
            <div style="background:var(--md-sys-color-primary-container); border-radius:16px; padding:10px; display:flex;">
                <md-icon style="color:var(--md-sys-color-on-primary-container); font-size:32px; width:32px; height:32px;">storefront</md-icon>
            </div>
            <div>
                <h2 style="margin:0; font-size:1.5rem; font-weight:500;">Lecherías por Promotor</h2>
                <p style="margin:4px 0 0; font-size:0.9rem; color:var(--md-sys-color-on-surface-variant);">
                    Selecciona el mes y un promotor para ver el estado de sus inventarios, reporte y requerimiento.
                </p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="md3-card filtros-card">
            <md-outlined-select id="selPromotor" label="Promotor" style="min-width:280px;">
                <md-select-option value=""><div slot="headline">— Selecciona un promotor —</div></md-select-option>
                <?php foreach ($promotores as $p):
                    $nom = mb_convert_encoding($p['nombre'], 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');
                ?>
                    <md-select-option value="<?= (int)$p['id'] ?>"
                        <?= ((int)$p['id'] === $promotor_pre) ? 'selected' : '' ?>>
                        <div slot="headline">
                            <?= htmlspecialchars($nom) ?>
                            (<?= (int)$p['cantidad_lecherias'] ?> lecherías)
                        </div>
                    </md-select-option>
                <?php endforeach; ?>
            </md-outlined-select>

            <md-outlined-select id="selMes" label="Mes" style="min-width:150px;">
                <?php $mn = (int)date('n');
                $nombresMes = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                               'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                foreach ($nombresMes as $i => $nom): $val = $i + 1; ?>
                    <md-select-option value="<?= $val ?>" <?= $mn === $val ? 'selected' : '' ?>>
                        <div slot="headline"><?= $nom ?></div>
                    </md-select-option>
                <?php endforeach; ?>
            </md-outlined-select>

            <md-outlined-text-field id="inpAnio" label="Año" type="number"
                value="<?= date('Y') ?>" style="max-width:110px;"></md-outlined-text-field>

            <div style="margin-left:auto;">
                <span id="resumen" class="estado-pill estado-info" style="display:none;"></span>
            </div>
        </div>

        <!-- Sección Inventarios -->
        <div class="md3-card" id="cardInventarios" style="display:none;">
            <h3 style="margin:0 0 12px; font-size:1.1rem;">
                <md-icon style="vertical-align:middle; color:var(--md-sys-color-primary);">inventory_2</md-icon>
                Inventarios mensuales
            </h3>
            <div style="overflow-x:auto;">
                <table class="lechs-table" id="tablaLecherias">
                    <thead>
                        <tr>
                            <th>Lechería</th>
                            <th>Tienda</th>
                            <th>Almacén</th>
                            <th>Estado</th>
                            <th style="text-align:right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <!-- Sección Reporte y Requerimiento -->
        <div class="md3-card" id="cardDocumentos" style="display:none;">
            <h3 style="margin:0 0 12px; font-size:1.1rem;">
                <md-icon style="vertical-align:middle; color:var(--md-sys-color-primary);">description</md-icon>
                Documentos consolidados del mes
            </h3>

            <div class="doc-block" id="docReporte">
                <div class="doc-title">
                    <md-icon style="vertical-align:middle; color:var(--md-sys-color-tertiary);">receipt_long</md-icon>
                    Reporte mensual
                </div>
                <span id="estadoReporte" class="estado-pill estado-falta">—</span>
                <div style="margin-left:auto;">
                    <md-outlined-button id="btnVerReporte" disabled>
                        <md-icon slot="icon">picture_as_pdf</md-icon>
                        Ver PDF
                    </md-outlined-button>
                </div>
            </div>

            <div class="doc-block" id="docRequerimiento">
                <div class="doc-title">
                    <md-icon style="vertical-align:middle; color:var(--md-sys-color-tertiary);">inventory</md-icon>
                    Requerimiento
                </div>
                <span id="estadoReq" class="estado-pill estado-falta">—</span>
                <div style="margin-left:auto;">
                    <md-outlined-button id="btnVerReq" disabled>
                        <md-icon slot="icon">picture_as_pdf</md-icon>
                        Ver PDF
                    </md-outlined-button>
                </div>
            </div>
        </div>

        <div id="placeholder" class="md3-card" style="text-align:center; color:var(--md-sys-color-on-surface-variant); padding:32px;">
            Selecciona un promotor para ver el estado de sus lecherías.
        </div>
    </main>

    <script src="../js/temas_md3.js"></script>
    <script>
        const PROMOTOR_PRESELECCIONADO = <?= json_encode($promotor_pre) ?>;

        function abrirMenu(id) {
            document.querySelectorAll('md-menu').forEach(m => { if (m.id !== id) m.open = false; });
            const menu = document.getElementById(id);
            menu.open = !menu.open;
        }
        function toggleDrawer() {
            document.getElementById('mobile-drawer')?.classList.toggle('open');
            document.getElementById('drawer-scrim')?.classList.toggle('open');
        }
        document.addEventListener('click', (e) => {
            if (!e.target.closest('md-menu') && !e.target.closest('md-text-button')) {
                document.querySelectorAll('md-menu').forEach(m => m.open = false);
            }
        });
    </script>
    <script src="../js/lecherias_supervisor.js"></script>
</body>
</html>
