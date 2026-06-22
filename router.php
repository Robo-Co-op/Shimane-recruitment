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

// Route /apply/ja to the Japanese application form
if ($uri === '/apply/ja' || $uri === '/apply/ja/') {
    chdir(__DIR__);
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/apply/ja/index.php';
    $_SERVER['PHP_SELF'] = '/apply/ja/index.php';
    require __DIR__ . '/apply/ja/index.php';
    return true;
}

// Route /apply to the application form
if ($uri === '/apply' || $uri === '/apply/') {
    chdir(__DIR__);
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/apply/index.php';
    $_SERVER['PHP_SELF'] = '/apply/index.php';
    require __DIR__ . '/apply/index.php';
    return true;
}

// Default: serve the Japanese homepage
chdir(__DIR__);
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
require __DIR__ . '/index.php';
return true;
