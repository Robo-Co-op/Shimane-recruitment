<?php
// Temporary diagnostic — will be deleted after testing
$checks = [];
$checks['pdo_loaded']      = extension_loaded('pdo') ? 'YES' : 'NO';
$checks['pdo_pgsql_loaded'] = extension_loaded('pdo_pgsql') ? 'YES' : 'NO';
$checks['config_exists']   = file_exists(__DIR__ . '/config.php') ? 'YES' : 'NO';

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
    $checks['DB_HOST']   = defined('DB_HOST')   ? '✓ set (' . substr(DB_HOST,0,8) . '...)' : 'NOT DEFINED';
    $checks['DB_SCHEMA'] = defined('DB_SCHEMA') ? '✓ set (' . DB_SCHEMA . ')' : 'NOT DEFINED';
}

try {
    if (extension_loaded('pdo_pgsql') && defined('DB_HOST')) {
        $pdo = new PDO('pgsql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME, DB_USER, DB_PASSWORD);
        $checks['db_connect'] = 'OK';
    }
} catch (Exception $e) {
    $checks['db_connect'] = 'FAILED: ' . $e->getMessage();
}

foreach ($checks as $k => $v) echo "$k: $v\n";
