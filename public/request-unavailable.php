<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

if (function_exists('set_alert')) {
    set_alert([
        'type' => 'sweet',
        'icon' => 'warning',
        'title' => 'access_notice_title',
        'text' => 'access_notice_text',
        'confirm' => true,
        'position' => 'center',
        'is_key' => true,
    ]);
}

$redirect = function_exists('base_path') ? base_path('pages/dashboard.php') : '/pages/dashboard.php';
header('Location: ' . $redirect, true, 302);
exit;
