<?php
declare(strict_types=1);

function bdr_distance_api_json(int $status, array $payload): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function bdr_distance_api_log(string $filename, array $context): void
{
    $enabled = $_ENV['BDR_DISTANCE_API_LOG_ENABLED'] ?? getenv('BDR_DISTANCE_API_LOG_ENABLED');
    if (!is_string($enabled) || !in_array(strtolower(trim($enabled)), ['1', 'true', 'yes', 'on'], true)) {
        return;
    }

    $dir = __DIR__ . '/../log';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }

    $context['timestamp'] = date('c');
    @file_put_contents(
        $dir . '/' . ltrim($filename, '/\\'),
        json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

function bdr_distance_api_request_key(): string
{
    $headerKey = trim((string)($_SERVER['HTTP_X_API_KEY'] ?? ''));
    if ($headerKey !== '') {
        return $headerKey;
    }

    $auth = trim((string)($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
    if ($auth !== '' && preg_match('/^Bearer\s+(.+)$/i', $auth, $matches)) {
        return trim((string)($matches[1] ?? ''));
    }

    return '';
}

function bdr_distance_api_configured_key(string $envKey, string $configKey = ''): string
{
    if (function_exists('db_config_load_env_once')) {
        db_config_load_env_once();
    }

    if (function_exists('db_env')) {
        $value = trim((string)db_env($envKey, ''));
        if ($value !== '') {
            return $value;
        }
    }

    $env = getenv($envKey);
    if (is_string($env) && trim($env) !== '') {
        return trim($env);
    }

    $envValue = $_ENV[$envKey] ?? '';
    $value = trim((string)$envValue);
    if ($value !== '') {
        return $value;
    }

    if ($configKey !== '' && function_exists('app_config')) {
        return trim((string)app_config($configKey, ''));
    }

    return '';
}

function bdr_distance_api_allowed_ips(string $envKey): array
{
    if (function_exists('db_config_load_env_once')) {
        db_config_load_env_once();
    }

    if (function_exists('db_env')) {
        $raw = (string)db_env($envKey, '');
        if (trim($raw) !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $raw))));
        }
    }

    $raw = getenv($envKey);
    if (!is_string($raw) || trim($raw) === '') {
        $raw = (string)($_ENV[$envKey] ?? '');
    }
    if (trim($raw) === '') {
        return [];
    }

    return array_values(array_filter(array_map('trim', explode(',', $raw))));
}

function bdr_distance_api_client_ip(): string
{
    return trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
}

function bdr_distance_api_bootstrap(array $options): array
{
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $logFile = trim((string)($options['log_file'] ?? 'bdr-distance-api.log'));
    $keyEnv = trim((string)($options['key_env'] ?? ''));
    $allowedIpsEnv = trim((string)($options['allowed_ips_env'] ?? ''));
    $configKey = trim((string)($options['config_key'] ?? ''));
    $requestContext = is_array($options['request_context'] ?? null) ? $options['request_context'] : [];

    if ($method !== 'GET') {
        bdr_distance_api_json(405, [
            'success' => false,
            'message' => 'Method not allowed.',
        ]);
    }

    $configuredKey = $keyEnv !== '' ? bdr_distance_api_configured_key($keyEnv, $configKey) : '';
    if ($configuredKey === '') {
        bdr_distance_api_log($logFile, array_merge([
            'event' => 'config_missing',
            'ip' => bdr_distance_api_client_ip(),
        ], $requestContext));
        bdr_distance_api_json(503, [
            'success' => false,
            'message' => 'API access is not configured.',
        ]);
    }

    $requestKey = bdr_distance_api_request_key();
    if ($requestKey === '' || !hash_equals($configuredKey, $requestKey)) {
        bdr_distance_api_log($logFile, array_merge([
            'event' => 'auth_failed',
            'ip' => bdr_distance_api_client_ip(),
        ], $requestContext));
        bdr_distance_api_json(401, [
            'success' => false,
            'message' => 'Unauthorized.',
        ]);
    }

    $clientIp = bdr_distance_api_client_ip();
    $allowedIps = $allowedIpsEnv !== '' ? bdr_distance_api_allowed_ips($allowedIpsEnv) : [];
    if ($allowedIps !== [] && $clientIp !== '' && !in_array($clientIp, $allowedIps, true)) {
        bdr_distance_api_log($logFile, array_merge([
            'event' => 'ip_blocked',
            'ip' => $clientIp,
        ], $requestContext));
        bdr_distance_api_json(403, [
            'success' => false,
            'message' => 'Forbidden.',
        ]);
    }

    bdr_distance_api_log($logFile, array_merge([
        'event' => 'request_started',
        'ip' => $clientIp,
    ], $requestContext));

    return [
        'client_ip' => $clientIp,
    ];
}