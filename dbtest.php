<?php
// Temporary diagnostic — REMOVE AFTER DEBUGGING
if (($_GET['k'] ?? '') !== 'robodiag2026') {
    http_response_code(404); die('Not found.');
}
error_reporting(E_ALL); ini_set('display_errors', 1);

$cfg = __DIR__ . '/config.php';
if (!file_exists($cfg)) { die("config.php NOT FOUND"); }
require_once $cfg;

$host   = defined('DB_HOST')     ? DB_HOST     : '(not set)';
$port   = defined('DB_PORT')     ? DB_PORT     : '(not set)';
$dbname = defined('DB_NAME')     ? DB_NAME     : '(not set)';
$user   = defined('DB_USER')     ? DB_USER     : '(not set)';
$schema = defined('DB_SCHEMA')   ? DB_SCHEMA   : '(not set)';

echo "<pre>\n";
echo "DB_HOST: " . (defined('DB_HOST') && DB_HOST ? '[SET - ' . strlen(DB_HOST) . ' chars]' : '[EMPTY]') . "\n";
echo "DB_PORT: $port\n";
echo "DB_NAME: $dbname\n";
echo "DB_USER: " . (defined('DB_USER') && DB_USER ? '[SET - ' . strlen(DB_USER) . ' chars]' : '[EMPTY]') . "\n";
echo "DB_SCHEMA: $schema\n";

// Test TCP reachability
echo "\nTCP reachability test (port $port on DB host):\n";
if (defined('DB_HOST') && DB_HOST) {
    $fp = @fsockopen(DB_HOST, (int)($port ?: 5432), $errno, $errstr, 5);
    if ($fp) {
        echo "  TCP OPEN — port is reachable\n";
        fclose($fp);
    } else {
        echo "  TCP FAILED: $errno $errstr\n";
    }
} else {
    echo "  Skipped (DB_HOST not set)\n";
}

// Test PDO connection
echo "\nPDO connection test:\n";
try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname",
        defined('DB_USER') ? DB_USER : '',
        defined('DB_PASSWORD') ? DB_PASSWORD : '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "  SUCCESS — connected to PostgreSQL\n";
    $pdo->exec("SET search_path TO $schema");
    echo "  Schema '$schema' set OK\n";
    $n = $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
    echo "  admin_users count: $n\n";
} catch (Exception $e) {
    echo "  FAILED: " . $e->getMessage() . "\n";
}
echo "</pre>";
