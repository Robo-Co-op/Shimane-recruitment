<?php
if (!defined('BASE_URL')) {
    // base.php is always at <site-root>/includes/base.php.
    // dirname(__DIR__) gives the site root on the filesystem regardless of
    // which PHP script Apache happened to execute (direct directory index,
    // router.php, etc.), avoiding the "double-admin" / wrong-base bug.
    $site_fs  = str_replace('\\', '/', dirname(__DIR__));
    $doc_root = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');

    if ($doc_root !== '' && strpos($site_fs, $doc_root) === 0) {
        $base = rtrim(substr($site_fs, strlen($doc_root)), '/');
    } else {
        $base = '';
    }

    define('BASE_URL', $base);

    if (BASE_URL !== '') {
        $b = BASE_URL;
        ob_start(function (string $html) use ($b): string {
            return preg_replace('/(href|src|action)="\//i', '$1="' . $b . '/', $html);
        });
    }
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string {
        return BASE_URL . $path;
    }
}
