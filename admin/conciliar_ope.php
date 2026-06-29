<?php
// admin/conciliar_ope.php
// RETIRADO. La conciliación con OPE quedó deshabilitada.
// Si necesitas reactivar lecherías que quedaron fuera de operación por una
// conciliación previa, usa el botón "Reactivar lecherías" en /admin/index.php.
require_once __DIR__ . '/guard.php';
http_response_code(410);
header('Content-Type: text/html; charset=utf-8');
?><!doctype html><html lang="es"><meta charset="utf-8">
<title>Función retirada</title>
<meta http-equiv="refresh" content="3; url=/admin/index.php">
<body style="font-family:Roboto,sans-serif;background:#141218;color:#E6E1E5;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;">
  <div style="text-align:center;max-width:520px;">
    <h1 style="font-size:1.5rem;font-weight:500;margin:0 0 12px;">Conciliación OPE retirada</h1>
    <p style="opacity:.8;">Esta función ya no forma parte del panel admin.<br>
       Redirigiendo a <a style="color:#D0BCFF" href="/admin/index.php">/admin/index.php</a>...</p>
  </div>
</body></html>
