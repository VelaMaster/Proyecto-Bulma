<?php
require_once __DIR__ . '/../includes/session_guard.php';
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'supervisor') {
    header('Location: ../iniciosesionSupervisor.php');
    exit();
}
$nombre_usuario = $_SESSION['nombre'] ?? $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark" data-theme-accent="violeta">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario de Almacén — Supervisor</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../main_md3.css">
    <link rel="stylesheet" href="../estilos/iniciocards.css">
    <style>
        .filtros-card{
            display:flex; flex-wrap:wrap; align-items:end; gap:14px;
            padding:16px 20px; margin-bottom:16px;
            background:var(--md-sys-color-surface-container);
            border:1px solid var(--md-sys-color-outline-variant);
            border-radius:14px;
        }
        .seccion{
            background:var(--md-sys-color-surface-container);
            border:1px solid var(--md-sys-color-outline-variant);
            border-radius:14px; overflow:hidden; margin-bottom:16px;
        }
        .seccion-titulo{
            display:flex; align-items:center; gap:10px;
            padding:10px 14px;
            background:var(--md-sys-color-secondary-container);
            color:var(--md-sys-color-on-secondary-container);
            font-weight:600; font-size:0.95rem;
        }
        .seccion-cuerpo{ padding:14px; }
        .grid-2col{
            display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
            gap:10px;
        }
        .tabla-scroll{ overflow-x:auto; }
        .inv-table{
            width:100%; border-collapse:collapse; font-size:.82rem; min-width:760px;
        }
        .inv-table th, .inv-table td{
            padding:5px 9px; border-bottom:1px solid var(--md-sys-color-outline-variant);
            text-align:center; white-space:nowrap;
        }
        .inv-table th{
            font-weight:600; color:var(--md-sys-color-on-surface-variant);
            background:var(--md-sys-color-surface-container-high);
            font-size:.72rem; text-transform:uppercase; letter-spacing:.4px;
        }
        .inv-table td input{
            width:100%; max-width:120px; padding:4px 6px;
            border:1px solid var(--md-sys-color-outline-variant);
            border-radius:6px; background:var(--md-sys-color-surface);
            color:var(--md-sys-color-on-surface); font-size:.82rem; text-align:center;
        }
        .totales{
            display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr));
            gap:10px; margin-top:10px;
        }
        .total-cell{
            padding:10px 14px; border-radius:10px;
            background:var(--md-sys-color-surface-container-high);
            border:1px solid var(--md-sys-color-outline-variant);
        }
        .total-cell label{ font-size:.7rem; opacity:.75; display:block; }
        .total-cell strong{ font-size:1.05rem; }
        .pill{
            display:inline-block; padding:2px 10px; border-radius:999px;
            background:color-mix(in srgb, var(--md-sys-color-primary) 18%, transparent);
            color:var(--md-sys-color-primary); font-weight:600; font-size:.72rem;
        }
        .obs-text{
            width:100%; min-height:60px; padding:8px;
            border:1px solid var(--md-sys-color-outline-variant);
            border-radius:8px; background:var(--md-sys-color-surface);
            color:var(--md-sys-color-on-surface); font-size:.85rem;
            font-family:inherit; resize:vertical;
        }
        .skel-loader{
            text-align:center; padding:40px;
            color:var(--md-sys-color-on-surface-variant);
        }
    </style>
    <script type="importmap">
        { "imports": { "@material/web/": "https://esm.run/@material/web/" } }
    </script>
    <script type="module"> import '@material/web/all.js'; </script>
</head>
<body>
<header class="md3-top-app-bar">
    <div class="app-bar-start">
        <md-icon-button onclick="history.back()"><md-icon>arrow_back</md-icon></md-icon-button>
        <div class="app-brand"><span>Inventario de Almacén</span></div>
    </div>
    <div class="app-bar-end">
        <md-text-button href="inicio.php"><md-icon slot="icon">home</md-icon> Inicio</md-text-button>
    </div>
</header>

<main class="panel-content">
    <div class="md3-hero-card" style="padding:20px 24px;">
        <h2 style="font-size:1.6rem; font-weight:500; margin:0;">Inventario de Leche en Polvo en Almacenes</h2>
        <p style="font-size:.95rem; opacity:.85; margin:8px 0 0;">
            Supervisor: <strong><?= htmlspecialchars($nombre_usuario) ?></strong>.
            Consolida R05 + R07 ($4.50 y $6.50) por almacén y mes.
        </p>
    </div>

    <div class="filtros-card">
        <md-outlined-select id="selAlmacen" label="Almacén" style="min-width:260px;">
            <md-select-option value=""><div slot="headline">— Selecciona —</div></md-select-option>
        </md-outlined-select>

        <md-outlined-select id="selMes" label="Mes" style="min-width:140px;">
            <?php
            $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                          'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
            $mesActual  = (int)date('n');
            for ($i = 1; $i <= 12; $i++) {
                $sel = $i === $mesActual ? 'selected' : '';
                echo "<md-select-option value='$i' $sel><div slot='headline'>{$meses[$i]}</div></md-select-option>";
            }
            ?>
        </md-outlined-select>

        <md-outlined-select id="selAnio" label="Año" style="min-width:120px;">
            <?php
            $anioActual = (int)date('Y');
            for ($y = $anioActual - 2; $y <= $anioActual + 1; $y++) {
                $sel = $y === $anioActual ? 'selected' : '';
                echo "<md-select-option value='$y' $sel><div slot='headline'>$y</div></md-select-option>";
            }
            ?>
        </md-outlined-select>

        <md-filled-button id="btnCargar"><md-icon slot="icon">search</md-icon> Cargar</md-filled-button>
        <md-filled-tonal-button id="btnPDF" disabled><md-icon slot="icon">picture_as_pdf</md-icon> Generar PDF</md-filled-tonal-button>
    </div>

    <div id="contenido"></div>
</main>

<script src="../js/inventario_almacen.js"></script>
</body>
</html>
