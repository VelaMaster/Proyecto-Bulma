<?php
// Diagnóstico temporal — borrar después
$exts = get_loaded_extensions();
$sqlite = in_array('pdo_sqlite', $exts);
$pdo    = in_array('PDO', $exts);
$sqliteClass = class_exists('SQLite3');

$dbPath = __DIR__ . '/datos/local.db';
$dirOk  = is_dir(__DIR__ . '/datos');
$writable = is_writable(__DIR__ . '/datos');

$test = null;
if ($sqlite) {
    try {
        $db = new PDO('sqlite:' . $dbPath);
        $test = 'PDO SQLite OK';
    } catch (Exception $e) {
        $test = 'PDO Error: ' . $e->getMessage();
    }
}

echo json_encode([
    'pdo_sqlite' => $sqlite,
    'PDO'        => $pdo,
    'SQLite3'    => $sqliteClass,
    'datos_dir'  => $dirOk,
    'writable'   => $writable,
    'db_path'    => $dbPath,
    'db_exists'  => file_exists($dbPath),
    'test'       => $test,
    'all_pdo'    => array_filter($exts, fn($e) => str_starts_with(strtolower($e), 'pdo')),
    'php_version'=> PHP_VERSION,
]);
