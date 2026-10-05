<?php
require __DIR__ . '/includes/core.php';
$page = preg_replace('/[^a-z_]/', '', $_GET['p'] ?? 'home') ?: 'home';
$file = __DIR__ . '/pages/' . $page . '.php';
if (!is_file($file)) { http_response_code(404); $page = '404'; $file = __DIR__ . '/pages/404.php'; }
if (!cfg('installed') && $page !== 'install') redirect('install');
ob_start();
$title = '';
require $file;
$content = ob_get_clean();
require __DIR__ . '/includes/layout.php';
