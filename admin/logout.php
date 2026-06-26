<?php
session_start();
require_once __DIR__ . '/../includes/base.php';
session_destroy();
header('Location: ' . base_url('/admin/login'));
