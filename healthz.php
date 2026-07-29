<?php
// Lightweight health check — exercises the same DB connection /admin and /apply
// depend on, so a bad password / paused project / missing driver shows up here
// with a clear 503 instead of silently surfacing as "the admin link is broken".
require_once __DIR__ . '/admin/includes/db.php';

header('Content-Type: application/json');

try {
    $db = get_db();
    $db->query('SELECT 1');
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
} catch (\Throwable $e) {
    error_log('[healthz] DB check failed: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode(['status' => 'error']);
}
