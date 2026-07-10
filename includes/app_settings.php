<?php
// Shared helpers for application-period settings stored in db/app_settings.json.
// Included by popup.php, apply pages, and admin/settings.php.

if (!function_exists('get_app_deadline')) {

    function _app_settings_file(): string {
        return dirname(__DIR__) . '/db/app_settings.json';
    }

    function _load_app_settings(): array {
        $f = _app_settings_file();
        if (file_exists($f)) {
            $d = json_decode(file_get_contents($f), true);
            if (is_array($d)) return $d;
        }
        return [];
    }

    function get_app_deadline(): string {
        $s = _load_app_settings();
        return !empty($s['deadline']) ? $s['deadline'] : '2026-07-13';
    }

    function save_app_deadline(string $date): void {
        $dir = dirname(__DIR__) . '/db';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $s = _load_app_settings();
        $s['deadline'] = $date;
        file_put_contents(_app_settings_file(), json_encode($s));
    }

    function is_application_open(): bool {
        return date('Y-m-d') <= get_app_deadline();
    }
}
