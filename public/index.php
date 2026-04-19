<?php
$__ssoDebugLog = static function (string $message, array $context = []): void {
    $enabled = $_ENV['SSO_DEBUG_LOG_ENABLED'] ?? getenv('SSO_DEBUG_LOG_ENABLED');
    if (!is_string($enabled) || !in_array(strtolower(trim($enabled)), ['1', 'true', 'yes', 'on'], true)) {
        return;
    }

    $dir = __DIR__ . '/log';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if ($context !== []) {
        $line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    $line .= PHP_EOL;
    @file_put_contents($dir . '/sso-debug.log', $line, FILE_APPEND | LOCK_EX);
};

$hasIncomingSsoToken = isset($_GET['new_sso_cre']) && trim((string)$_GET['new_sso_cre']) !== '';
if (!$hasIncomingSsoToken && isset($_COOKIE['sso_cre'])) {
    echo '<script>document.cookie = "sso_cre=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";</script>';
}

$version = time();

// ✅ Secure Session Settings
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
ini_set('session.cookie_httponly', '1');

if (session_status() === PHP_SESSION_NONE) session_start();

// ✅ Include & Init
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/functions-db.php';
require_once __DIR__ . '/includes/sso-config.php';
require_once __DIR__ . '/classes/Config.php';

if ($hasIncomingSsoToken) {
    $__ssoDebugLog('INDEX_CALLBACK_ENTRY', [
        'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
        'session_id' => session_id(),
        'has_cookie' => isset($_COOKIE['sso_cre']),
    ]);
    redirect('sso_sp_client.php?new_sso_cre=' . rawurlencode((string)$_GET['new_sso_cre']));
}

$authPolicy = function_exists('get_auth_policy_config') ? get_auth_policy_config() : [];
$ssoEnabled = !empty($authPolicy['sso']['enabled']);
$ssoMode = strtoupper(trim((string)($authPolicy['sso']['mode'] ?? 'MANUAL')));
$ssoHybrid = is_array($authPolicy['sso']['hybrid'] ?? null) ? $authPolicy['sso']['hybrid'] : [];
$hasActiveSsoRoute = false;
if ($ssoEnabled) {
    if ($ssoMode === 'ALL') {
        $hasActiveSsoRoute = true;
    } elseif ($ssoMode === 'HYBRID') {
        foreach (['staf', 'pelajar', 'umum'] as $hybridCategory) {
            if (strtoupper(trim((string)($ssoHybrid[$hybridCategory] ?? 'MANUAL'))) === 'SSO') {
                $hasActiveSsoRoute = true;
                break;
            }
        }
    }
}
$ssoConfig = function_exists('sso_shared_config') ? sso_shared_config() : [
    'idp_host' => 'oneid.upnm.edu.my',
    'launcher_url' => 'https://oneid.upnm.edu.my/?site_id=V8LN57YMGZ',
];
$oneIdLauncherUrl = (string)($ssoConfig['launcher_url'] ?? '');
$oneIdLoginUrl = base_url('sso_sp_client.php');
$referer = trim((string)($_SERVER['HTTP_REFERER'] ?? ''));
$refererHost = strtolower((string)(parse_url($referer, PHP_URL_HOST) ?? ''));
$isOneIdReferer = $refererHost !== '' && $refererHost === strtolower((string)($ssoConfig['idp_host'] ?? ''));
$showOneIdButton = $hasActiveSsoRoute && $oneIdLoginUrl !== '';

$loginManualCategories = [];
$authCategories = is_array($authPolicy['categories'] ?? null) ? $authPolicy['categories'] : [];
$isManualLoginEnabledForCategory = static function (string $category) use ($authCategories, $ssoEnabled, $ssoMode, $ssoHybrid): bool {
    if (empty($authCategories[$category])) {
        return false;
    }

    if (!$ssoEnabled) {
        return true;
    }

    if ($ssoMode === 'ALL') {
        return $category === 'umum';
    }

    if ($ssoMode === 'HYBRID') {
        return strtoupper(trim((string)($ssoHybrid[$category] ?? 'MANUAL'))) === 'MANUAL';
    }

    return true;
};

foreach (['staf', 'pelajar', 'umum'] as $loginCategory) {
    if ($isManualLoginEnabledForCategory($loginCategory)) {
        $loginManualCategories[] = $loginCategory;
    }
}

$loginPlaceholderParts = [];
foreach ($loginManualCategories as $loginCategory) {
    if ($loginCategory === 'staf') {
        $loginPlaceholderParts[] = (string)__('login_userid_placeholder_staff');
        continue;
    }
    if ($loginCategory === 'pelajar') {
        $loginPlaceholderParts[] = (string)__('login_userid_placeholder_student');
        continue;
    }
    if ($loginCategory === 'umum') {
        $loginPlaceholderParts[] = (string)__('login_userid_placeholder_public');
    }
}

$loginPlaceholderList = '';
$loginPlaceholderCount = count($loginPlaceholderParts);
if ($loginPlaceholderCount === 1) {
    $loginPlaceholderList = $loginPlaceholderParts[0];
} elseif ($loginPlaceholderCount === 2) {
    $loginPlaceholderList = $loginPlaceholderParts[0] . (string)__('login_userid_placeholder_joiner_last') . $loginPlaceholderParts[1];
} elseif ($loginPlaceholderCount > 2) {
    $lastPart = array_pop($loginPlaceholderParts);
    $loginPlaceholderList = implode((string)__('login_userid_placeholder_joiner'), $loginPlaceholderParts)
        . (string)__('login_userid_placeholder_joiner_last')
        . $lastPart;
}

$loginIdPlaceholder = $loginPlaceholderList !== ''
    ? sprintf((string)__('login_userid_placeholder_format'), $loginPlaceholderList)
    : (string)__('login_userid_placeholder_unavailable');

$hasPendingLoginAlert = !empty($_SESSION['alert']);
if ($showOneIdButton && $isOneIdReferer && empty($_SESSION['f_stafID']) && !$hasPendingLoginAlert) {
    $__ssoDebugLog('INDEX_ONEID_REFERER_HANDOFF', [
        'referer' => $referer,
        'session_id' => session_id(),
        'target' => $oneIdLoginUrl,
    ]);
    redirect('sso_sp_client.php');
}
if ($showOneIdButton && $isOneIdReferer && empty($_SESSION['f_stafID']) && $hasPendingLoginAlert) {
    $__ssoDebugLog('INDEX_ONEID_REFERER_KEEP_ALERT', [
        'referer' => $referer,
        'session_id' => session_id(),
    ]);
}

// ✅ Language Detection
$uri  = $_SERVER['REQUEST_URI'];
$configModel = class_exists('Database') ? new Config(Database::getInstance('mysql')->getConnection()) : null;
$activeLanguages = $configModel ? $configModel->getBahasaAktif() : ['ms', 'en'];
$defaultLanguage = $configModel ? ($configModel->getDefaultBahasa($activeLanguages[0] ?? 'ms') ?? 'ms') : 'ms';
$globalThemeSettings = $configModel ? ($configModel->getTema() ?: []) : [];
if (!in_array($defaultLanguage, $activeLanguages, true)) {
    $defaultLanguage = $activeLanguages[0] ?? 'ms';
}
$lang = $_SESSION['lang'] ?? $defaultLanguage;

if (isset($_GET['lang']) && in_array($_GET['lang'], $activeLanguages, true)) {
    if ($lang !== $_GET['lang']) {
        $_SESSION['lang'] = $_GET['lang'];
        header("Location: " . strtok($uri, '?'));
        exit;
    }
}
$lang = $_SESSION['lang'] ?? $defaultLanguage;

$loginLanguageOptions = array_values(array_intersect([$defaultLanguage, 'ms', 'en'], ['ms', 'en']));
$loginLanguageOptions = array_values(array_unique($loginLanguageOptions));
$remainingLanguageOptions = array_values(array_diff(['ms', 'en'], $loginLanguageOptions));
$loginLanguageOptions = array_merge($loginLanguageOptions, $remainingLanguageOptions);

// ✅ CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ✅ Redirect if already logged in
if (!empty($_SESSION['f_stafID'])) {
    redirect(app_config('site.default_home', 'pages/dashboard.php'));
}

// ✅ Optional login flags
$login_failed   = $login_failed ?? false;
$locked_seconds = $locked_seconds ?? 0;
$attempts_left  = $attempts_left ?? 3;

$defaultHome      = app_config('site.default_home', 'pages/dashboard.php');
$loginHeaderLogo  = app_config('branding.login_header_logo', 'assets/images/logo-upnm.png');
$loginPanelLogo   = app_config('branding.login_panel_logo', 'assets/images/upnm30-logo.png');
$supportEmail     = trim((string)app_config('system.support', ''));
$systemName       = trim((string)app_config('system.name', 'Sistem Pengurusan Fasiliti (e-Facility)'));
$organizationName = trim((string)app_config('organization.name', app_config('system.name', 'Sistem Pengurusan Fasiliti (e-Facility)')));
$organizationWebsite = trim((string)app_config('organization.website', ''));
$sidebarTheme = strtolower(trim((string)($globalThemeSettings['sidebarColor'] ?? $_SESSION['theme.menu'] ?? 'light')));
$themeStyleMap = [
  'light' => ['start' => '#6f86a3', 'end' => '#8ea2bb', 'primary' => '#64748b', 'primaryStrong' => '#475569', 'accent' => '#94a3b8', 'primaryRgb' => '100, 116, 139', 'accentRgb' => '148, 163, 184'],
  'dark' => ['start' => '#111827', 'end' => '#1f2937', 'primary' => '#374151', 'primaryStrong' => '#111827', 'accent' => '#6b7280', 'primaryRgb' => '55, 65, 81', 'accentRgb' => '107, 114, 128'],
  'brand' => ['start' => '#0b4fd6', 'end' => '#0f9db1', 'primary' => '#0f4fd6', 'primaryStrong' => '#0b3caa', 'accent' => '#0f9db1', 'primaryRgb' => '15, 79, 214', 'accentRgb' => '15, 157, 177'],
  'emerald' => ['start' => '#0f766e', 'end' => '#34d399', 'primary' => '#10b981', 'primaryStrong' => '#0f766e', 'accent' => '#6ee7b7', 'primaryRgb' => '16, 185, 129', 'accentRgb' => '110, 231, 183'],
  'navy' => ['start' => '#0c1b32', 'end' => '#173b6b', 'primary' => '#1d4ed8', 'primaryStrong' => '#0c1b32', 'accent' => '#60a5fa', 'primaryRgb' => '29, 78, 216', 'accentRgb' => '96, 165, 250'],
  'sunset' => ['start' => '#b45309', 'end' => '#f97316', 'primary' => '#ea580c', 'primaryStrong' => '#b45309', 'accent' => '#fb923c', 'primaryRgb' => '234, 88, 12', 'accentRgb' => '251, 146, 60'],
  'mist' => ['start' => '#475569', 'end' => '#64748b', 'primary' => '#64748b', 'primaryStrong' => '#475569', 'accent' => '#94a3b8', 'primaryRgb' => '100, 116, 139', 'accentRgb' => '148, 163, 184'],
  'strawberry' => ['start' => '#be185d', 'end' => '#f43f5e', 'primary' => '#e11d48', 'primaryStrong' => '#be185d', 'accent' => '#fb7185', 'primaryRgb' => '225, 29, 72', 'accentRgb' => '251, 113, 133'],
  'matcha' => ['start' => '#3f6212', 'end' => '#65a30d', 'primary' => '#65a30d', 'primaryStrong' => '#3f6212', 'accent' => '#a3e635', 'primaryRgb' => '101, 163, 13', 'accentRgb' => '163, 230, 53'],
];
$activeThemeStyle = $themeStyleMap[$sidebarTheme] ?? $themeStyleMap['light'];
$contactNote = __('login_contact');
$contactParts = [];
if ($supportEmail !== '') {
    $contactParts[] = '<a href="mailto:' . htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') . '" class="text-blue-600 hover:underline">' . htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') . '</a>';
}
if ($organizationWebsite !== '' && $organizationWebsite !== '#') {
    $contactParts[] = '<a href="' . htmlspecialchars($organizationWebsite, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">' . htmlspecialchars($organizationWebsite, ENT_QUOTES, 'UTF-8') . '</a>';
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= __('login_title') ?> | <?= htmlspecialchars(app_config('site.title', 'Sistem Pengurusan Fasiliti (e-Facility)')) ?></title>
  <link rel="icon" href="<?= base_url(app_config('site.favicon', 'assets/images/default.ico')) ?>" type="image/x-icon">

  <link rel="stylesheet" href="<?= base_url('assets/css/icons.min.css?v=' . $version) ?>">
  <link rel="stylesheet" href="<?= base_url('assets/css/app.min.css?v=' . $version) ?>">
  <link rel="stylesheet" href="<?= base_url('assets/css/output.css?v=' . $version) ?>">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js?v=<?= $version ?>"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11?v=<?= $version ?>"></script>

  <style>
    :root {
      --facility-bg: linear-gradient(180deg, #edf2f8 0%, #e3ebf5 100%);
      --facility-card: #ffffff;
      --facility-card-soft: #f6f9fc;
      --facility-card-ink: #0f1e33;
      --facility-line: rgba(148, 163, 184, 0.24);
      --facility-line-strong: rgba(100, 116, 139, 0.22);
      --facility-text: #10213a;
      --facility-muted: #5b6b82;
      --facility-primary: <?= htmlspecialchars($activeThemeStyle['primary'], ENT_QUOTES, 'UTF-8') ?>;
      --facility-primary-strong: <?= htmlspecialchars($activeThemeStyle['primaryStrong'], ENT_QUOTES, 'UTF-8') ?>;
      --facility-accent: <?= htmlspecialchars($activeThemeStyle['accent'], ENT_QUOTES, 'UTF-8') ?>;
      --facility-primary-rgb: <?= htmlspecialchars($activeThemeStyle['primaryRgb'], ENT_QUOTES, 'UTF-8') ?>;
      --facility-accent-rgb: <?= htmlspecialchars($activeThemeStyle['accentRgb'], ENT_QUOTES, 'UTF-8') ?>;
      --facility-navy: #0c1b32;
      --facility-navy-soft: #12355f;
      --facility-shadow: 0 28px 70px rgba(15, 23, 42, 0.12);
      --facility-soft-shadow: 0 18px 36px rgba(15, 23, 42, 0.08);
      --facility-header-start: <?= htmlspecialchars($activeThemeStyle['start'], ENT_QUOTES, 'UTF-8') ?>;
      --facility-header-end: <?= htmlspecialchars($activeThemeStyle['end'], ENT_QUOTES, 'UTF-8') ?>;
    }

    body.facility-login-page {
      font-family: 'Plus Jakarta Sans', sans-serif;
      min-height: 100vh;
      margin: 0;
      background:
        radial-gradient(circle at top left, rgba(var(--facility-primary-rgb), 0.12), transparent 28%),
        radial-gradient(circle at 100% 0%, rgba(var(--facility-accent-rgb), 0.12), transparent 26%),
        var(--facility-bg);
      color: var(--facility-text);
      overflow-x: hidden;
    }

    .facility-auth-shell {
      min-height: 100vh;
      padding: 28px;
      position: relative;
    }

    .facility-auth-shell::before {
      content: "";
      position: absolute;
      inset: 0 0 auto 0;
      height: 320px;
      background: linear-gradient(135deg, var(--facility-header-start), var(--facility-header-end));
      z-index: 0;
      border-bottom-left-radius: 36px;
      border-bottom-right-radius: 36px;
    }

    .facility-workspace {
      position: relative;
      z-index: 1;
      width: min(1360px, 100%);
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      gap: 22px;
    }

    .facility-masthead {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      padding: 10px 4px 0;
      color: #ffffff;
    }

    .facility-brand-lockup {
      display: flex;
      align-items: center;
      gap: 18px;
      min-width: 0;
    }

    .facility-brand-mark {
      width: 156px;
      max-width: 100%;
      display: block;
      flex: 0 0 auto;
      filter: drop-shadow(0 12px 24px rgba(2, 6, 23, 0.18));
    }

    .facility-brand-meta {
      min-width: 0;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .facility-brand-meta strong {
      font-size: 14px;
      font-weight: 800;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: rgba(255,255,255,0.9);
    }

    .facility-brand-meta span {
      font-size: 13px;
      line-height: 1.7;
      color: rgba(226, 232, 240, 0.88);
      max-width: 540px;
    }

    .facility-masthead-actions {
      display: flex;
      flex-wrap: wrap;
      justify-content: flex-end;
      gap: 12px;
      align-items: center;
    }

    .facility-version-chip,
    .facility-top-link,
    .facility-top-button {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      min-height: 44px;
      padding: 0 16px;
      border-radius: 999px;
      border: 1px solid rgba(255,255,255,0.14);
      background: rgba(255,255,255,0.08);
      color: #ffffff;
      font-size: 11px;
      font-weight: 800;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      backdrop-filter: blur(16px);
      transition: transform .18s ease, background-color .18s ease, border-color .18s ease;
    }

    .facility-top-link:hover,
    .facility-top-button:hover {
      background: rgba(255,255,255,0.14);
      border-color: rgba(255,255,255,0.24);
      transform: translateY(-1px);
    }

    .facility-auth-board {
      display: grid;
      grid-template-columns: minmax(0, 1.25fr) minmax(360px, 430px);
      gap: 24px;
      align-items: stretch;
    }

    .facility-overview-panel,
    .facility-auth-panel {
      border-radius: 30px;
      background: var(--facility-card);
      box-shadow: var(--facility-shadow);
      border: 1px solid rgba(255,255,255,0.78);
      overflow: hidden;
    }

    .facility-overview-panel {
      display: flex;
      flex-direction: column;
      padding: 28px;
      gap: 22px;
    }

    .facility-visual-panel {
      position: relative;
      min-height: 380px;
      border-radius: 28px;
      overflow: hidden;
      background: #0c1b32;
      box-shadow: var(--facility-soft-shadow);
    }

    .facility-showcase-media,
    .facility-showcase-media::after {
      position: absolute;
      inset: 0;
    }

    .facility-showcase-media::after {
      content: "";
      background:
        linear-gradient(90deg, rgba(12,27,50,0.82) 0%, rgba(12,27,50,0.38) 48%, rgba(12,27,50,0.72) 100%),
        linear-gradient(180deg, rgba(var(--facility-primary-rgb), 0.12), rgba(var(--facility-accent-rgb), 0.26));
      z-index: 1;
      pointer-events: none;
    }

    .facility-showcase-media img {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .facility-visual-content {
      position: relative;
      z-index: 2;
      min-height: 380px;
      padding: 30px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      color: #ffffff;
    }

    .facility-visual-top {
      display: flex;
      justify-content: space-between;
      gap: 12px;
      align-items: flex-start;
      flex-wrap: wrap;
    }

    .facility-system-chip {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 11px 14px;
      border-radius: 999px;
      background: rgba(255,255,255,0.12);
      border: 1px solid rgba(255,255,255,0.16);
      backdrop-filter: blur(14px);
      font-size: 11px;
      font-weight: 800;
      letter-spacing: 0.1em;
      text-transform: uppercase;
    }

    .facility-visual-body {
      display: flex;
      align-items: flex-end;
    }

    .facility-visual-copy {
      display: flex;
      flex-direction: column;
      gap: 16px;
      max-width: 520px;
    }

    .facility-visual-copy h2 {
      margin: 0;
      font-size: clamp(28px, 3vw, 42px);
      line-height: 1.05;
      letter-spacing: -0.05em;
      font-weight: 800;
      color: #ffffff;
    }

    .facility-visual-copy p {
      margin: 0;
      font-size: 14px;
      line-height: 1.8;
      color: rgba(241, 245, 249, 0.9);
      max-width: 440px;
    }

    .facility-info-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 16px;
    }

    .facility-info-card {
      padding: 20px;
      border-radius: 24px;
      background: var(--facility-card-soft);
      border: 1px solid var(--facility-line);
      box-shadow: inset 0 1px 0 rgba(255,255,255,0.8);
    }

    .facility-info-card .facility-card-label {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 12px;
      font-size: 11px;
      font-weight: 800;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--facility-primary-strong);
    }

    .facility-info-card h3 {
      margin: 0 0 8px;
      font-size: 18px;
      line-height: 1.4;
      color: var(--facility-card-ink);
    }

    .facility-info-card p {
      margin: 0;
      font-size: 13px;
      line-height: 1.75;
      color: var(--facility-muted);
    }

    .facility-info-card a {
      color: var(--facility-primary-strong);
      font-weight: 700;
      text-decoration: none;
    }

    .facility-info-card a:hover {
      text-decoration: underline;
    }

    .facility-auth-panel {
      padding: 28px;
      display: flex;
      flex-direction: column;
      gap: 22px;
      background:
        linear-gradient(180deg, rgba(255,255,255,0.98), rgba(248,250,252,0.98));
    }

    .facility-auth-card {
      padding: 26px;
      border-radius: 26px;
      background: #ffffff;
      border: 1px solid rgba(226, 232, 240, 0.92);
      box-shadow: var(--facility-soft-shadow);
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .facility-form-head {
      display: flex;
      align-items: flex-start;
      gap: 16px;
    }

    .facility-panel-logo {
      width: 68px;
      height: 68px;
      border-radius: 22px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, rgba(var(--facility-primary-rgb), 0.12), rgba(var(--facility-accent-rgb), 0.12));
      box-shadow: inset 0 0 0 1px rgba(255,255,255,0.8);
      flex: 0 0 auto;
    }

    .facility-panel-logo img {
      max-width: 46px;
      max-height: 46px;
    }

    .facility-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 10px;
      color: var(--facility-primary-strong);
      font-size: 11px;
      font-weight: 800;
      letter-spacing: 0.14em;
      text-transform: uppercase;
    }

    .facility-form-title {
      margin: 0 0 8px;
      font-size: 30px;
      line-height: 1.04;
      letter-spacing: -0.04em;
      font-weight: 800;
      color: var(--facility-card-ink);
    }

    .facility-form-subcopy {
      margin: 0;
      font-size: 13px;
      line-height: 1.75;
      color: var(--facility-muted);
    }

    .facility-auth-form {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .facility-field {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .facility-field label {
      font-size: 11px;
      font-weight: 800;
      color: #334155;
      letter-spacing: 0.12em;
      text-transform: uppercase;
    }

    .facility-field-control {
      position: relative;
    }

    .facility-field-control i {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: #64748b;
      font-size: 16px;
      pointer-events: none;
    }

    .facility-field-control input {
      width: 100%;
      border-radius: 18px;
      border: 1px solid rgba(148, 163, 184, 0.2);
      background: #f8fafc;
      padding: 15px 16px 15px 46px;
      font-size: 14px;
      color: #0f172a;
      transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
    }

    .facility-field-control input:focus {
      border-color: rgba(var(--facility-primary-rgb), 0.4);
      background: #ffffff;
      box-shadow: 0 0 0 4px rgba(var(--facility-primary-rgb), 0.12);
      outline: none;
    }

    .facility-form-meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      font-size: 12px;
      color: var(--facility-muted);
    }

    .facility-form-meta a {
      color: var(--facility-primary-strong);
      font-weight: 700;
      text-decoration: none;
    }

    .facility-form-meta a:hover {
      text-decoration: underline;
    }

    .facility-submit-btn {
      border: 0;
      border-radius: 18px;
      background: linear-gradient(135deg, var(--facility-primary-strong), var(--facility-primary) 58%, var(--facility-accent));
      color: #ffffff;
      font-size: 13px;
      font-weight: 800;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      padding: 16px 18px;
      box-shadow: 0 18px 36px rgba(var(--facility-primary-rgb), 0.22);
      transition: transform .18s ease, box-shadow .18s ease;
    }

    .facility-submit-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 20px 40px rgba(var(--facility-primary-rgb), 0.28);
    }

    .facility-oneid-block {
      display: flex;
      flex-direction: column;
      gap: 12px;
      padding-top: 4px;
      border-top: 1px dashed rgba(148, 163, 184, 0.28);
    }

    .facility-oneid-label {
      color: var(--facility-muted);
      font-size: 11px;
      font-weight: 800;
      letter-spacing: 0.12em;
      text-transform: uppercase;
    }

    .facility-oneid-btn {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      border-radius: 20px;
      padding: 15px 16px;
      background: linear-gradient(135deg, rgba(240,249,255,1), rgba(239,246,255,1));
      border: 1px solid rgba(19, 181, 200, 0.18);
      color: #0f172a;
      box-shadow: 0 12px 26px rgba(15, 23, 42, 0.07);
      transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }

    .facility-oneid-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 18px 32px rgba(15, 23, 42, 0.1);
      border-color: rgba(var(--facility-primary-rgb), 0.24);
    }

    .facility-oneid-main {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .facility-oneid-badge {
      width: 42px;
      height: 42px;
      border-radius: 14px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, var(--facility-primary), var(--facility-accent));
      color: #fff;
      font-size: 12px;
      font-weight: 800;
      letter-spacing: 0.08em;
      box-shadow: 0 10px 20px rgba(var(--facility-primary-rgb), 0.18);
    }

    .facility-oneid-copy strong,
    .facility-oneid-copy span {
      display: block;
    }

    .facility-oneid-copy strong {
      font-size: 13px;
      font-weight: 800;
      color: #0f172a;
    }

    .facility-oneid-copy span {
      margin-top: 4px;
      color: #64748b;
      font-size: 11px;
      line-height: 1.55;
    }

    .facility-support-panel {
      padding: 20px 22px;
      border-radius: 24px;
      background: var(--facility-card-soft);
      border: 1px solid var(--facility-line);
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .facility-support-panel h3 {
      margin: 0;
      font-size: 13px;
      font-weight: 800;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--facility-primary-strong);
    }

    .facility-support-panel p {
      margin: 0;
      font-size: 13px;
      line-height: 1.75;
      color: var(--facility-muted);
    }

    .facility-support-links {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .facility-support-links a {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      min-height: 42px;
      padding: 0 14px;
      border-radius: 999px;
      background: #ffffff;
      border: 1px solid rgba(148, 163, 184, 0.22);
      color: var(--facility-card-ink);
      font-size: 12px;
      font-weight: 700;
      text-decoration: none;
    }

    .facility-support-links a:hover {
      border-color: rgba(var(--facility-primary-rgb), 0.24);
    }

    @media (max-width: 1200px) {
      .facility-auth-board {
        grid-template-columns: minmax(0, 1fr) minmax(340px, 400px);
      }

      .facility-visual-body {
        grid-template-columns: 1fr;
      }

      .facility-info-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 1024px) {
      .facility-auth-shell {
        padding: 18px;
      }

      .facility-masthead,
      .facility-auth-board,
      .facility-visual-body {
        grid-template-columns: 1fr;
      }

      .facility-masthead {
        flex-direction: column;
        align-items: flex-start;
      }

      .facility-masthead-actions {
        justify-content: flex-start;
      }

      .facility-auth-board {
        display: grid;
      }
    }

    @media (max-width: 768px) {
      .facility-auth-shell {
        padding: 0;
      }

      .facility-auth-shell::before {
        border-radius: 0;
        height: 260px;
      }

      .facility-workspace {
        gap: 16px;
      }

      .facility-masthead,
      .facility-overview-panel,
      .facility-auth-panel {
        border-radius: 0;
      }

      .facility-masthead,
      .facility-overview-panel,
      .facility-auth-panel,
      .facility-visual-content {
        padding-left: 18px;
        padding-right: 18px;
      }

      .facility-brand-lockup,
      .facility-form-head,
      .facility-form-meta {
        flex-direction: column;
        align-items: flex-start;
      }

      .facility-brand-mark {
        width: 132px;
      }

      .facility-overview-panel,
      .facility-auth-panel {
        padding-top: 22px;
        padding-bottom: 22px;
      }

      .facility-auth-card,
      .facility-visual-panel,
      .facility-overview-sidecard,
      .facility-info-card,
      .facility-support-panel {
        border-radius: 22px;
      }

      .facility-info-grid,
      .facility-visual-body {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body class="authentication-bg facility-login-page" x-data="{ active: 0, banners: ['banner1.jpg', 'banner2.jpg', 'banner3.jpg', 'banner4.jpg'] }" x-init="setInterval(() => { active = (active + 1) % banners.length }, 5200)">
<div class="facility-auth-shell">
  <div class="facility-workspace">
    <header class="facility-masthead">
      <div class="facility-brand-lockup">
        <img class="facility-brand-mark" src="<?= base_url($loginHeaderLogo) ?>" alt="UPNM Logo">
        <div class="facility-brand-meta">
          <strong><?= htmlspecialchars($systemName !== '' ? $systemName : 'Sistem Pengurusan Fasiliti (e-Facility)', ENT_QUOTES, 'UTF-8') ?></strong>
          <span><?= htmlspecialchars($organizationName !== '' ? $organizationName : ($lang === 'en' ? 'Official digital access for facility operations, service requests, and campus support workflows.' : 'Akses digital rasmi untuk operasi fasiliti, permohonan perkhidmatan, dan aliran sokongan kampus.'), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
      </div>

      <div class="facility-masthead-actions">
        <div class="facility-version-chip">
          <i class="ri-shield-check-line"></i>
          <span><?= htmlspecialchars(app_current_version_label(), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <a class="facility-top-link" href="<?= h(base_url('index.php')) ?>"><i class="ri-home-5-line"></i> <?= __('login_nav.home') ?></a>
        <button class="facility-top-button" type="button" @click="$store.faq?.showFaq?.()"><i class="ri-question-line"></i> <?= __('login_nav.faq') ?></button>
        <a class="facility-top-link" href="https://directory.upnm.edu.my" target="_blank" rel="noopener noreferrer"><i class="ri-building-line"></i> <?= __('login_nav.directory') ?></a>
      </div>
    </header>

    <div class="facility-auth-board">
      <section class="facility-overview-panel">
        <div class="facility-visual-panel">
          <div class="facility-showcase-media">
            <template x-for="(banner, index) in banners" :key="index">
              <img :src="`<?= base_url('assets/images/') ?>${banner}`"
                   alt="Banner"
                   class="transition-opacity duration-700 ease-in-out"
                   :class="{ 'opacity-0': active !== index, 'opacity-100': active === index }">
            </template>
          </div>

          <div class="facility-visual-content">
            <div class="facility-visual-body">
              <div class="facility-visual-copy">
                <h2><?= htmlspecialchars($lang === 'en' ? 'Centralized facility access with clearer governance and operational continuity.' : 'Akses fasiliti berpusat dengan tadbir urus dan kesinambungan operasi yang lebih jelas.', ENT_QUOTES, 'UTF-8') ?></h2>
                <p><?= htmlspecialchars($lang === 'en' ? 'Built to support maintenance coordination, service accountability, and secure sign-in across the institution.' : 'Dibina untuk menyokong penyelarasan penyelenggaraan, akauntabiliti perkhidmatan, dan log masuk selamat merentas institusi.', ENT_QUOTES, 'UTF-8') ?></p>
              </div>
            </div>
          </div>
        </div>

        <div class="facility-info-grid">
          <article class="facility-info-card">
            <span class="facility-card-label"><i class="ri-layout-masonry-line"></i> <?= $lang === 'en' ? 'Platform' : 'Platform' ?></span>
            <h3><?= htmlspecialchars($systemName !== '' ? $systemName : 'Sistem Pengurusan Fasiliti (e-Facility)', ENT_QUOTES, 'UTF-8') ?></h3>
            <p><?= htmlspecialchars($lang === 'en' ? 'Designed to consolidate requests, approvals, and service execution within a more controlled institutional channel.' : 'Direka untuk menyatukan permohonan, kelulusan, dan pelaksanaan perkhidmatan dalam saluran institusi yang lebih terkawal.', ENT_QUOTES, 'UTF-8') ?></p>
          </article>

          <article class="facility-info-card">
            <span class="facility-card-label"><i class="ri-customer-service-2-line"></i> <?= __('login_contact_title') ?></span>
            <h3><?= htmlspecialchars($lang === 'en' ? 'Support and reference' : 'Sokongan dan rujukan', ENT_QUOTES, 'UTF-8') ?></h3>
            <p>
              <?php if (!empty($contactParts)): ?>
                <?= implode(' <span aria-hidden="true">|</span> ', $contactParts) ?>
              <?php else: ?>
                <?= htmlspecialchars($contactNote, ENT_QUOTES, 'UTF-8') ?>
              <?php endif; ?>
            </p>
          </article>

          <article class="facility-info-card">
            <span class="facility-card-label"><i class="ri-shield-keyhole-line"></i> <?= $lang === 'en' ? 'Security' : 'Keselamatan' ?></span>
            <h3><?= htmlspecialchars($lang === 'en' ? 'Protected institutional sign-in' : 'Log masuk institusi yang dilindungi', ENT_QUOTES, 'UTF-8') ?></h3>
            <p><?= htmlspecialchars($lang === 'en' ? 'Session protection, CSRF handling, and identity routing remain enforced behind this interface.' : 'Perlindungan sesi, pengendalian CSRF, dan routing identiti kekal dikuatkuasakan di sebalik antaramuka ini.', ENT_QUOTES, 'UTF-8') ?></p>
          </article>
        </div>
      </section>

      <aside class="facility-auth-panel">
        <div class="facility-auth-card">
          <div class="facility-form-head">
            <div class="facility-panel-logo">
              <img src="<?= base_url($loginPanelLogo) ?>" alt="Logo">
            </div>
            <div>
              <span class="facility-eyebrow"><i class="ri-login-circle-line"></i> <?= __('login_heading') ?></span>
              <h2 class="facility-form-title"><?= __('login_title') ?></h2>
              <p class="facility-form-subcopy"><?= htmlspecialchars($lang === 'en' ? 'Enter your credentials to proceed to the official facility management workspace.' : 'Masukkan kelayakan anda untuk meneruskan ke ruang kerja rasmi pengurusan fasiliti.', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
          </div>

          <form method="POST" action="<?= base_url('login.php') ?>" autocomplete="off" class="facility-auth-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="facility-field">
              <label for="f_loginID"><?= h((__('login_userid_label') ?: 'Login ID')) ?></label>
              <div class="facility-field-control">
                <i class="ri-user-3-line"></i>
                <input id="f_loginID" name="f_loginID" type="text" required
                       placeholder="<?= htmlspecialchars($loginIdPlaceholder, ENT_QUOTES, 'UTF-8') ?>"
                       autocomplete="username">
              </div>
            </div>

            <div class="facility-field">
              <label for="f_password"><?= __('login_password') ?></label>
              <div class="facility-field-control">
                <i class="ri-lock-password-line"></i>
                <input id="f_password" name="f_password" type="password" required
                       placeholder="******"
                       autocomplete="current-password">
              </div>
            </div>

            <div class="facility-form-meta">
              <span><?= htmlspecialchars($lang === 'en' ? 'Protected access session' : 'Sesi akses dilindungi', ENT_QUOTES, 'UTF-8') ?></span>
              <a href="<?= h(base_url('forgot-password.php')) ?>"><?= __('login_forgot') ?></a>
            </div>

            <button type="submit" class="facility-submit-btn">
              <?= __('login_btnLogin') ?>
            </button>
          </form>

          <?php if ($showOneIdButton): ?>
            <div class="facility-oneid-block">
              <span class="facility-oneid-label">Alternative Access</span>
              <a href="<?= h($oneIdLoginUrl) ?>" class="facility-oneid-btn">
                <span class="facility-oneid-main">
                  <span class="facility-oneid-badge">ID</span>
                  <span class="facility-oneid-copy">
                    <strong><?= h(__('login_btnOneId') ?: 'OneID Login') ?></strong>
                    <span><?= htmlspecialchars($lang === 'en' ? 'Use central identity authentication when your access path requires single sign-on.' : 'Gunakan autentikasi identiti pusat apabila laluan akses anda memerlukan single sign-on.', ENT_QUOTES, 'UTF-8') ?></span>
                  </span>
                </span>
                <i class="ri-arrow-right-up-line"></i>
              </a>
            </div>
          <?php endif; ?>
        </div>

        <div class="facility-support-panel">
          <h3><?= htmlspecialchars($lang === 'en' ? 'Official support channels' : 'Saluran sokongan rasmi', ENT_QUOTES, 'UTF-8') ?></h3>
          <p><?= htmlspecialchars($lang === 'en' ? 'Use the official contact and reference channels below if you require assistance with access or account matters.' : 'Gunakan saluran hubungan dan rujukan rasmi di bawah jika anda memerlukan bantuan berkaitan akses atau akaun.', ENT_QUOTES, 'UTF-8') ?></p>
          <div class="facility-support-links">
            <?php if ($supportEmail !== ''): ?>
              <a href="mailto:<?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?>"><i class="ri-mail-line"></i> <?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?></a>
            <?php endif; ?>
          </div>
        </div>
      </aside>
    </div>
  </div>
</div>

<!-- ✅ Alert rendering -->
<?php if (function_exists('render_alert')) render_alert(); ?>

</body>
</html>
