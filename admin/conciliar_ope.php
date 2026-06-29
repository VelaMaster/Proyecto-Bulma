<?php
// admin/conciliar_ope.php — RETIRADO.
// El admin no modifica datos de operación. Para refrescar el estado real de las
// lecherías, usar /admin/sync.php (sincronización con Firebird de Liconsa).
require_once __DIR__ . '/guard.php';
http_response_code(410);
header('Location: /admin/index.php');
exit;
