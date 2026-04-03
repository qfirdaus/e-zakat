<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_login();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

echo json_encode([
    'ok' => true,
    'ts' => time(),
], JSON_UNESCAPED_UNICODE);
exit;
