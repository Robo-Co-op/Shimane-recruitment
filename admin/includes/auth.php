<?php
require_once __DIR__ . '/../../includes/base.php';
function require_auth(string $min_role = 'viewer'): array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['admin'])) {
        header('Location: ' . base_url('/admin/login'));
        exit;
    }
    $levels = ['viewer' => 1, 'editor' => 2, 'admin' => 3];
    if (($levels[$_SESSION['admin']['role']] ?? 0) < ($levels[$min_role] ?? 1)) {
        http_response_code(403);
        die('403 — Access denied.');
    }
    return $_SESSION['admin'];
}

function current_user(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return $_SESSION['admin'] ?? null;
}

function can(string $role): bool {
    $levels = ['viewer' => 1, 'editor' => 2, 'admin' => 3];
    $u = current_user();
    return ($levels[$u['role'] ?? 'viewer'] ?? 0) >= ($levels[$role] ?? 1);
}
