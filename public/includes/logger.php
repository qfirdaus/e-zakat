<?php
// app/includes/logger.php
// Lightweight application logger writing to app/log/app.log

if (!function_exists('app_log')) {
    function app_log(string $message, string $level = 'INFO'): void {
        $dir = realpath(__DIR__ . '/../log') ?: (__DIR__ . '/../log');
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $file = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'app.log';
        $time = date('Y-m-d H:i:s');
        $pid = getmypid() ?: null;
        $entry = sprintf("[%s] [%s] [pid:%s] %s\n", $time, $level, $pid, trim($message));
        // Use LOCK_EX to avoid concurrent write races
        @file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
        // Restrict permissions where possible
        if (is_file($file)) {
            @chmod($file, 0660);
        }
    }
}

// Also provide a convenience wrapper to mirror error_log but with app_log
if (!function_exists('app_error_log')) {
    function app_error_log(string $message): void {
        app_log($message, 'ERROR');
    }
}
