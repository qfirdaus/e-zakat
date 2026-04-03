<?php
// Simpan log ke fail log.txt
function write_log($message, $file = 'log.txt') {
    $timestamp = date('Y-m-d H:i:s');
    $line = "[$timestamp] $message\n";
    file_put_contents($file, $line, FILE_APPEND);
}

// Simpan log dalam folder khas
function write_app_log($message, $filename = 'system.log') {
    $log_dir = __DIR__ . '/../../log';
    if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
    $file = $log_dir . '/' . $filename;
    $timestamp = date('Y-m-d H:i:s');
    $line = "[$timestamp] $message\n";
    file_put_contents($file, $line, FILE_APPEND);
}
