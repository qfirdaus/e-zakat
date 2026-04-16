<?php
declare(strict_types=1);

define('ALLOW_ANON_AJAX', true);
require_once __DIR__ . '/../configuration/db_config.php';
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/bdr_distance_api_bootstrap.php';
require_once __DIR__ . '/../controllers/BdrDistanceController.php';
$bootstrap = bdr_distance_api_bootstrap([
    'log_file' => 'bdr-distance-api.log',
    'key_env' => 'BDR_DISTANCE_API_KEY',
    'allowed_ips_env' => 'BDR_DISTANCE_API_ALLOWED_IPS',
    'config_key' => 'api.bdr_distance_api_key',
    'request_context' => [
        'staff_no' => trim((string)($_GET['staff_no'] ?? $_GET['staff_id'] ?? '')),
        'site' => trim((string)($_GET['site'] ?? 'upnm_kampus')),
    ],
]);

$controller = new BdrDistanceController(true);
$controller->handleApiSingleRecord();
