<?php 
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

// Set alert
if (function_exists('set_alert')) {
    set_alert([
        'type' => 'sweet',
        'icon' => 'warning',
        'title' => 'access_notice_title',
        'text' => 'access_missing_page_text',
        'confirm' => false,
        'timer' => 2000, // auto close 2 sec
        'position' => 'center',
        'is_key' => true,
    ]);
}

// target redirect
$redirect = function_exists('base_path') 
    ? base_path('pages/dashboard.php') 
    : '/pages/dashboard.php';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Redirecting...</title>
</head>
<body>

<?php 
// Render alert
if (function_exists('render_alert')) {
    render_alert();
}
?>

<script>
    setTimeout(function() {
        window.location.href = "<?= $redirect ?>";
    }, 2000);
</script>

</body>
</html>