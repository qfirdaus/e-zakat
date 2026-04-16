<?php
declare(strict_types=1);

define('ALLOW_ANON_AJAX', true);
require_once __DIR__ . '/../configuration/db_config.php';
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/bdr_distance_api_bootstrap.php';
require_once __DIR__ . '/../controllers/BdrDistanceController.php';
$bootstrap = bdr_distance_api_bootstrap([
    'log_file' => 'bdr-distance-nearby-api.log',
    'key_env' => 'BDR_DISTANCE_NEARBY_API_KEY',
    'allowed_ips_env' => 'BDR_DISTANCE_NEARBY_API_ALLOWED_IPS',
    'config_key' => 'api.bdr_distance_nearby_api_key',
    'request_context' => [],
]);

$controller = new BdrDistanceController(true);
$controller->handleApiNearbyStaffRecords();