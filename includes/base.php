<?php
if (!defined('BASE_URL')) {
    $dir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
    define('BASE_URL', ($dir === '/' || $dir === '') ? '' : $dir);

    if (BASE_URL !== '') {
        $base = BASE_URL;
        ob_start(function (string $html) use ($base): string {
            return preg_replace(
                '/(href|src|action)="\//i',
                '$1="' . $base . '/',
                $html
            );
        });
    }
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string {
        return BASE_URL . $path;
    }
}
