<?php
// La vista de "Avance Mensual" fue eliminada. Toda la funcionalidad del
// Reporte Mensual del supervisor vive ahora en listadoReportesPromotores.php.
require_once __DIR__ . '/../includes/session_guard.php';
header('Location: listadoReportesPromotores.php');
exit();
