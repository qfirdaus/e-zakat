<?php

$coreFile = __DIR__ . '/core/ms.php';
$customFile = __DIR__ . '/custom/ms.php';

$core = is_file($coreFile) ? require $coreFile : [];
$custom = is_file($customFile) ? require $customFile : [];

return array_replace(
    is_array($core) ? $core : [],
    is_array($custom) ? $custom : []
);
