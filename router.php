<?php
/**
 * Router for PHP built-in development server.
 * Usage: php -S localhost:8000 router.php
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve static files (if they exist on disk) directly
$file = __DIR__ . $uri;
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

// Route /en and /en/ to the English page
if ($uri === '/en' || $uri === '/en/') {
    chdir(__DIR__);
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/en/index.php';
    $_SERVER['PHP_SELF'] = '/en/index.php';
    require __DIR__ . '/en/index.php';
    return true;
}

// Default: serve the Japanese homepage
chdir(__DIR__);
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
require __DIR__ . '/index.php';
return true;
