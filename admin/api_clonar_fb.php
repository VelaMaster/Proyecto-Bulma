<?php
// admin/api_clonar_fb.php
// Endpoint que dispara ClonadorFirebird::clonarRemotoHaciaLocal() y devuelve
// el resumen en JSON. Se usa desde el botón "Clonar Liconsa -> Docker" del
// panel de sincronización (admin/sync.php) y puede invocarse por cron:
//   curl -sS -X POST 'http://localhost:8080/admin/api_clonar_fb.php?key=TOKEN'

require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../src/Servicio/ClonadorFirebird.php';

header('Content-Type: application/json; charset=utf-8');

// Tope de tiempo amplio: gbak puede tardar minutos con BDDs grandes.
@set_time_limit(0);
@ini_set('memory_limit', '256M');

$clon = new ClonadorFirebird();
echo json_encode($clon->clonarRemotoHaciaLocal(), JSON_UNESCAPED_UNICODE);
