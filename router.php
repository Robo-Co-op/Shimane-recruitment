<?php
/**
 * Router for PHP built-in development server.
 * Usage: php -S localhost:8000 router.php
 */
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($base !== '' && $base !== '/' && strpos($uri, $base) === 0) {
    $uri = substr($uri, strlen($base));
}
if ($uri === '' || $uri === null) $uri = '/';

// Serve static files directly
$file = __DIR__ . $uri;
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

// ── /admin/* routes ──────────────────────────────────────────────────────────
if (preg_match('#^/admin(/.*)?$#', $uri, $m)) {
    $sub = rtrim($m[1] ?? '', '/') ?: '';
    $map = [
        ''                  => 'admin/index.php',
        '/login'            => 'admin/login.php',
        '/logout'           => 'admin/logout.php',
        '/analytics'        => 'admin/analytics.php',
        '/submissions'      => 'admin/submissions.php',
        '/submission-edit'  => 'admin/submission-edit.php',
        '/team'             => 'admin/team.php',
        '/content'          => 'admin/content.php',
        '/export'           => 'admin/export.php',
        '/forms'            => 'admin/forms.php',
        '/form-editor'      => 'admin/form-editor.php',
        '/settings'         => 'admin/settings.php',
        '/accept-invite'    => 'admin/accept-invite.php',
        '/forgot-password'  => 'admin/forgot-password.php',
        '/reset-password'   => 'admin/reset-password.php',
        '/api/track'        => 'admin/api/track.php',
        '/api/save-draft'   => 'admin/api/save-draft.php',
        '/api/remind'       => 'admin/api/remind.php',
    ];
    $target = $map[$sub] ?? null;
    if ($target && file_exists(__DIR__ . '/' . $target)) {
        chdir(__DIR__);
        $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/' . $target;
        require __DIR__ . '/' . $target;
        return true;
    }
    http_response_code(404);
    echo '<h2>404 — Admin page not found</h2>';
    return true;
}

// ── /apply/ja ────────────────────────────────────────────────────────────────
if ($uri === '/apply/ja' || $uri === '/apply/ja/') {
    chdir(__DIR__);
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/apply/ja/index.php';
    $_SERVER['PHP_SELF'] = '/apply/ja/index.php';
    require __DIR__ . '/apply/ja/index.php';
    return true;
}

// ── /apply ───────────────────────────────────────────────────────────────────
if ($uri === '/apply' || $uri === '/apply/') {
    chdir(__DIR__);
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/apply/index.php';
    $_SERVER['PHP_SELF'] = '/apply/index.php';
    require __DIR__ . '/apply/index.php';
    return true;
}

// ── /en ──────────────────────────────────────────────────────────────────────
if ($uri === '/en' || $uri === '/en/') {
    chdir(__DIR__);
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/en/index.php';
    $_SERVER['PHP_SELF'] = '/en/index.php';
    require __DIR__ . '/en/index.php';
    return true;
}

// ── Default: Japanese homepage ───────────────────────────────────────────────
chdir(__DIR__);
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
require __DIR__ . '/index.php';
return true;
