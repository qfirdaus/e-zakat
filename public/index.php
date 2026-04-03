<?php
$__ssoDebugLog = static function (string $message, array $context = []): void {
    $dir = __DIR__ . '/log';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if ($context !== []) {
        $line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    $line .= PHP_EOL;
    @file_put_contents($dir . '/sso-debug.log', $line, FILE_APPEND);
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
    'idp_host' => 'oneid-dev.upnm.edu.my',
    'launcher_url' => 'https://oneid-dev.upnm.edu.my/?site_id=V8LN57YMGZ',
];
$oneIdLauncherUrl = (string)($ssoConfig['launcher_url'] ?? '');
$oneIdLoginUrl = base_path('sso_sp_client.php');
$referer = trim((string)($_SERVER['HTTP_REFERER'] ?? ''));
$refererHost = strtolower((string)(parse_url($referer, PHP_URL_HOST) ?? ''));
$isOneIdReferer = $refererHost !== '' && $refererHost === strtolower((string)($ssoConfig['idp_host'] ?? ''));
$showOneIdButton = $hasActiveSsoRoute && $oneIdLoginUrl !== '';

if ($showOneIdButton && $isOneIdReferer && empty($_SESSION['f_stafID'])) {
    $__ssoDebugLog('INDEX_ONEID_REFERER_HANDOFF', [
        'referer' => $referer,
        'session_id' => session_id(),
        'target' => $oneIdLoginUrl,
    ]);
    redirect('sso_sp_client.php');
}

// ✅ Language Detection
$uri  = $_SERVER['REQUEST_URI'];
$configModel = class_exists('Database') ? new Config(Database::getInstance('mysql')->getConnection()) : null;
$activeLanguages = $configModel ? $configModel->getBahasaAktif() : ['ms', 'en'];
$defaultLanguage = $configModel ? ($configModel->getDefaultBahasa($activeLanguages[0] ?? 'ms') ?? 'ms') : 'ms';
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
$loginHeaderLogo  = app_config('branding.login_header_logo', 'assets/images/e-prestasi-logo-upnm.png');
$loginPanelLogo   = app_config('branding.login_panel_logo', 'assets/images/e-prestasi-logo.png');
$supportEmail     = trim((string)app_config('system.support', ''));
$systemName       = trim((string)app_config('system.name', 'Base System'));
$organizationName = trim((string)app_config('organization.name', app_config('system.name', 'Base System')));
$organizationWebsite = trim((string)app_config('organization.website', ''));
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
  <title><?= __('login_title') ?> | <?= htmlspecialchars(app_config('site.title', 'e-Prestasi')) ?></title>
  <link rel="icon" href="<?= base_url(app_config('site.favicon', 'assets/images/default.ico')) ?>" type="image/x-icon">

  <link rel="stylesheet" href="<?= base_url('assets/css/output.css?v=' . $version) ?>">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js?v=<?= $version ?>"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11?v=<?= $version ?>"></script>

  <style>
    :root {
      --login-shell-bg:
        radial-gradient(circle at 0% 0%, rgba(14, 165, 233, 0.18), transparent 32%),
        radial-gradient(circle at 100% 0%, rgba(15, 118, 110, 0.12), transparent 28%),
        radial-gradient(circle at 100% 100%, rgba(2, 132, 199, 0.16), transparent 30%),
        linear-gradient(145deg, #eef6ff 0%, #f8fbff 46%, #f1fbf8 100%);
      --login-panel-bg: rgba(255, 255, 255, 0.94);
      --login-panel-soft: rgba(255, 255, 255, 0.72);
      --login-card-border: rgba(148, 163, 184, 0.16);
      --login-shadow: 0 28px 64px rgba(15, 23, 42, 0.12);
      --login-soft-shadow: 0 16px 34px rgba(15, 23, 42, 0.08);
      --login-text: #0f172a;
      --login-muted: #64748b;
      --login-accent: #0ea5e9;
      --login-accent-dark: #0369a1;
      --login-success: #0f766e;
      --login-teal: #0f766e;
      --login-divider: rgba(148, 163, 184, 0.18);
    }

    body {
      font-family: 'Poppins', sans-serif;
      font-size: 13px;
      min-height: 100vh;
      background: var(--login-shell-bg);
      color: var(--login-text);
    }

    .login-shell {
      max-width: 1240px;
      margin: 0 auto;
      padding: 30px 20px 36px;
    }

    .login-topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 22px;
      padding: 16px 20px;
      border: 1px solid rgba(255, 255, 255, 0.5);
      border-radius: 12px;
      background:
        linear-gradient(135deg, rgba(255, 255, 255, 0.82), rgba(255, 255, 255, 0.58));
      box-shadow: var(--login-soft-shadow);
      backdrop-filter: blur(20px);
    }

    .login-brand {
      display: flex;
      align-items: center;
      gap: 18px;
      min-width: 0;
    }

    .login-brand-copy {
      min-width: 0;
    }

    .login-brand-copy img {
      width: 158px;
      max-width: 100%;
      display: block;
    }

    .login-brand-copy p {
      margin: 10px 0 0;
      color: var(--login-muted);
      font-size: 11px;
      line-height: 1.7;
      letter-spacing: .04em;
      text-transform: uppercase;
    }

    .login-topnav {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 10px;
      justify-content: flex-end;
    }

    .login-topnav a,
    .login-topnav button {
      border: 1px solid rgba(148, 163, 184, 0.16);
      background: rgba(255, 255, 255, 0.82);
      color: #0f172a;
      border-radius: 999px;
      padding: 10px 16px;
      font-size: 11px;
      font-weight: 700;
      line-height: 1;
      letter-spacing: .04em;
      text-transform: uppercase;
      transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }

    .login-topnav a:hover,
    .login-topnav button:hover {
      transform: translateY(-1px);
      border-color: rgba(14, 165, 233, 0.26);
      box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
    }

    .login-main {
      margin-top: 24px;
      display: grid;
      grid-template-columns: minmax(0, 1.24fr) minmax(380px, 432px);
      gap: 24px;
      align-items: stretch;
    }

    .login-stage,
    .login-card {
      border: 1px solid var(--login-card-border);
      border-radius: 12px;
      background: var(--login-panel-bg);
      box-shadow: var(--login-shadow);
      backdrop-filter: blur(18px);
    }

    .login-stage {
      overflow: hidden;
      display: flex;
      flex-direction: column;
      min-height: 680px;
      position: relative;
    }

    .login-stage-hero {
      position: relative;
      min-height: 376px;
      overflow: hidden;
    }

    .login-stage-hero::after {
      content: "";
      position: absolute;
      inset: 0;
      background:
        linear-gradient(180deg, rgba(15, 23, 42, 0.02), rgba(15, 23, 42, 0.52)),
        linear-gradient(135deg, rgba(3, 105, 161, 0.12), rgba(15, 118, 110, 0.18));
      pointer-events: none;
    }

    .login-stage-hero img {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .login-stage-overlay {
      position: absolute;
      inset: auto 30px 28px 30px;
      z-index: 1;
      display: flex;
      flex-direction: column;
      gap: 14px;
      color: #fff;
    }

    .login-stage-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      width: max-content;
      padding: 9px 14px;
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      background: rgba(15, 23, 42, 0.24);
      font-size: 11px;
      font-weight: 700;
      letter-spacing: .12em;
      text-transform: uppercase;
      backdrop-filter: blur(12px);
    }

    .login-stage-text {
      max-width: 640px;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .login-stage-text-highlight {
      display: inline-flex;
      align-items: center;
      width: max-content;
      max-width: 100%;
      padding: 10px 14px;
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, 0.18);
      background: rgba(255, 255, 255, 0.14);
      font-size: 11px;
      font-weight: 700;
      letter-spacing: .12em;
      text-transform: uppercase;
      backdrop-filter: blur(12px);
      color: #fff;
    }

    .login-stage-title {
      margin: 0;
      font-size: 34px;
      line-height: 1.08;
      letter-spacing: -.04em;
      font-weight: 700;
      color: #ffffff;
      max-width: 620px;
    }

    .login-stage-lead {
      margin: 0;
      max-width: 600px;
      color: rgba(255, 255, 255, 0.84);
      font-size: 13px;
      line-height: 1.8;
    }

    .login-stage-body {
      padding: 30px;
      display: block;
      flex: 1 1 auto;
      background:
        linear-gradient(180deg, rgba(255,255,255,0.94), rgba(248,250,252,0.96)),
        radial-gradient(circle at top right, rgba(14, 165, 233, 0.08), transparent 40%);
    }

    .login-stage-panel {
      display: grid;
      grid-template-columns: minmax(0, 1.14fr) minmax(240px, 0.86fr);
      gap: 18px;
      width: 100%;
    }

    .login-stage-section {
      padding: 22px 22px;
      border-radius: 10px;
      border: 1px solid rgba(148, 163, 184, 0.14);
      background:
        linear-gradient(180deg, rgba(255,255,255,0.98), rgba(248,250,252,0.96));
      box-shadow: 0 12px 24px rgba(15, 23, 42, 0.04);
    }

    .login-stage-section--full {
      grid-column: 1 / -1;
    }

    .login-stage-section h2,
    .login-stage-section h3 {
      margin: 0 0 10px;
      font-size: 16px;
      font-weight: 700;
      color: #0f172a;
      letter-spacing: -.02em;
    }

    .login-stage-section p {
      margin: 0;
      color: var(--login-muted);
      line-height: 1.74;
      font-size: 12px;
    }

    .login-stage-section--accent {
      background:
        linear-gradient(145deg, rgba(240, 249, 255, 0.98), rgba(236, 253, 245, 0.94));
      border-color: rgba(14, 165, 233, 0.12);
    }

    .login-stage-stats {
      margin-top: 14px;
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 10px;
    }

    .login-stage-stat {
      border-radius: 8px;
      border: 1px solid rgba(148, 163, 184, 0.12);
      background: rgba(255, 255, 255, 0.76);
      padding: 12px 14px;
    }

    .login-stage-stat-label {
      display: block;
      margin-bottom: 4px;
      color: #64748b;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: .1em;
      text-transform: uppercase;
    }

    .login-stage-stat-value {
      color: #0f172a;
      font-size: 13px;
      font-weight: 700;
      line-height: 1.4;
    }

    .login-contact-lines {
      margin-top: 14px;
      display: flex;
      flex-direction: column;
      gap: 8px;
      color: #334155;
      font-size: 12px;
      line-height: 1.65;
    }

    .login-contact-lines a {
      color: var(--login-accent-dark);
      text-decoration: none;
      font-weight: 600;
    }

    .login-contact-lines a:hover {
      text-decoration: underline;
    }

    .login-card {
      padding: 30px 28px 24px;
      display: flex;
      flex-direction: column;
      position: relative;
      overflow: hidden;
    }

    .login-card::before {
      content: "";
      position: absolute;
      inset: 0 0 auto 0;
      height: 118px;
      background:
        radial-gradient(circle at top left, rgba(14, 165, 233, 0.12), transparent 56%),
        linear-gradient(180deg, rgba(240, 249, 255, 0.74), rgba(255,255,255,0));
      pointer-events: none;
    }

    .login-card-head {
      display: flex;
      align-items: flex-start;
      gap: 16px;
      margin-bottom: 24px;
      position: relative;
      z-index: 1;
    }

    .login-card-logo {
      width: 62px;
      height: 62px;
      border-radius: 10px;
      background:
        linear-gradient(135deg, rgba(14,165,233,0.18), rgba(20,184,166,0.14));
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex: 0 0 auto;
      box-shadow: 0 14px 26px rgba(14, 165, 233, 0.16);
    }

    .login-card-logo img {
      max-width: 46px;
      max-height: 46px;
    }

    .login-card-title {
      margin: 0;
      font-size: 24px;
      line-height: 1.1;
      letter-spacing: -.03em;
      font-weight: 700;
    }

    .login-card-subtitle {
      margin: 7px 0 0;
      color: var(--login-muted);
      font-size: 12px;
      line-height: 1.65;
    }

    .login-form {
      display: flex;
      flex-direction: column;
      gap: 16px;
      position: relative;
      z-index: 1;
    }

    .login-field {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .login-field label {
      font-size: 11px;
      font-weight: 600;
      color: #334155;
      text-transform: uppercase;
      letter-spacing: .08em;
    }

    .login-field input {
      width: 100%;
      border-radius: 8px;
      border: 1px solid rgba(148, 163, 184, 0.18);
      background: rgba(248, 250, 252, 0.88);
      padding: 14px 16px;
      font-size: 13px;
      outline: none;
      transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
    }

    .login-field input:focus {
      border-color: rgba(14, 165, 233, 0.46);
      box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.12);
      background: #fff;
    }

    .login-inline-meta {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      gap: 14px;
      margin-top: -2px;
    }

    .login-note-inline {
      color: var(--login-muted);
      font-size: 11px;
      line-height: 1.55;
    }

    .login-inline-meta a {
      color: var(--login-accent-dark);
      font-size: 12px;
      font-weight: 600;
      text-decoration: none;
    }

    .login-inline-meta a:hover {
      text-decoration: underline;
    }

    .login-submit-btn {
      width: 100%;
      border: 0;
      border-radius: 8px;
      background: linear-gradient(135deg, #0369a1, #0ea5e9 55%, #14b8a6);
      color: #fff;
      padding: 14px 16px;
      font-weight: 700;
      letter-spacing: .06em;
      text-transform: uppercase;
      box-shadow: 0 16px 30px rgba(2, 132, 199, 0.24);
      transition: transform .18s ease, box-shadow .18s ease;
    }

    .login-submit-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 20px 34px rgba(2, 132, 199, 0.28);
    }

    .login-alt-divider {
      display: flex;
      align-items: center;
      gap: 10px;
      color: #94a3b8;
      font-size: 11px;
      font-weight: 600;
      letter-spacing: .08em;
      text-transform: uppercase;
      position: relative;
      z-index: 1;
    }
    .login-alt-divider::before,
    .login-alt-divider::after {
      content: "";
      flex: 1 1 auto;
      height: 1px;
      background: linear-gradient(90deg, rgba(148, 163, 184, 0), rgba(148, 163, 184, .8), rgba(148, 163, 184, 0));
    }
    .login-oneid-btn {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      gap: 10px;
      width: 100%;
      padding: 0.78rem 0.88rem;
      border-radius: 8px;
      border: 1px solid rgba(14, 116, 144, .16);
      background:
        linear-gradient(135deg, rgba(240, 249, 255, 1), rgba(236, 253, 245, 1));
      color: #0f172a;
      font-weight: 600;
      font-size: 12px;
      box-shadow: 0 10px 20px rgba(15, 23, 42, .07);
      transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
      position: relative;
      z-index: 1;
    }
    .login-oneid-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 14px 24px rgba(15, 23, 42, .1);
      border-color: rgba(14, 116, 144, .28);
    }
    .login-oneid-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 36px;
      height: 36px;
      border-radius: 6px;
      background: linear-gradient(135deg, #0ea5e9, #14b8a6);
      color: #fff;
      box-shadow: 0 8px 16px rgba(14, 165, 233, .18);
      font-weight: 700;
      font-size: 11px;
      letter-spacing: .06em;
    }
    .login-oneid-caption {
      margin-top: 8px;
      color: #64748b;
      font-size: 10px;
      line-height: 1.55;
      position: relative;
      z-index: 1;
    }

    .login-card-footer {
      margin-top: auto;
      padding-top: 18px;
      display: flex;
      flex-direction: column;
      gap: 10px;
      position: relative;
      z-index: 1;
    }

    .login-footer-divider {
      width: 100%;
      height: 1px;
      background: linear-gradient(90deg, rgba(148, 163, 184, 0), rgba(148, 163, 184, 0.75), rgba(148, 163, 184, 0));
    }

    .login-system-meta {
      color: #94a3b8;
      font-size: 11px;
      line-height: 1.45;
      text-align: center;
    }

    .login-language-switch {
      text-align: center;
      color: var(--login-muted);
      font-size: 11px;
      line-height: 1.4;
    }

    .login-language-switch a {
      color: var(--login-accent-dark);
      text-decoration: none;
      font-weight: 600;
    }

    .login-language-switch a:hover {
      text-decoration: underline;
    }

    @media (max-width: 1024px) {
      .login-main {
        grid-template-columns: 1fr;
      }

      .login-stage {
        min-height: auto;
      }

      .login-stage-body {
        display: block;
      }

      .login-stage-panel {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 768px) {
      .login-shell {
        padding: 18px 14px 24px;
      }

      .login-topbar {
        flex-direction: column;
        align-items: stretch;
        padding: 14px 16px;
      }

      .login-topnav {
        justify-content: flex-start;
      }

      .login-stage-hero {
        min-height: 300px;
      }

      .login-stage-overlay {
        inset: auto 18px 18px 18px;
      }

      .login-stage-title {
        font-size: 28px;
      }

      .login-stage-body,
      .login-card {
        padding: 20px 18px;
      }

      .login-inline-meta {
        flex-direction: column;
        align-items: flex-start;
      }
    }
  </style>
</head>
<body class="bg-gray-100" x-data="{}">
<div class="login-shell">
  <header class="login-topbar">
    <div class="login-brand">
      <div class="login-brand-copy">
        <img src="<?= base_url($loginHeaderLogo) ?>" alt="UPNM Logo">
        <p><?= htmlspecialchars($organizationName !== '' ? $organizationName : (app_config('system.name', 'Base System')), ENT_QUOTES, 'UTF-8') ?></p>
      </div>
    </div>
    <nav class="login-topnav" id="navTabs">
      <button type="button"><?= __('login_nav.home') ?></button>
      <button type="button" @click="$store.faq?.showFaq?.()"><?= __('login_nav.faq') ?></button>
      <a href="https://directory.upnm.edu.my" target="_blank" rel="noopener noreferrer"><?= __('login_nav.directory') ?></a>
    </nav>
  </header>

  <main class="login-main">
    <section class="login-stage">
      <div class="login-stage-hero"
            x-data="{ active: 0, banners: ['banner3.jpg', 'banner4.jpg'] }"
            x-init="setInterval(() => { active = (active + 1) % banners.length }, 4500)">
        <template x-for="(banner, index) in banners" :key="index">
          <img :src="`<?= base_url('assets/images/') ?>${banner}`"
               alt="Banner"
               class="transition-opacity duration-700 ease-in-out"
               :class="{ 'opacity-0': active !== index, 'opacity-100': active === index }">
        </template>
          <div class="login-stage-overlay">
            <span class="login-stage-badge"><?= htmlspecialchars($systemName !== '' ? $systemName : 'Base System', ENT_QUOTES, 'UTF-8') ?></span>
            <div class="login-stage-text">
              <span class="login-stage-text-highlight">
                <?= htmlspecialchars(
                  $lang === 'en'
                    ? 'Welcome to the ' . ($systemName !== '' ? $systemName : 'Base System') . '. Please log in to continue.'
                    : __('login_info'),
                  ENT_QUOTES,
                    'UTF-8'
                  ) ?>
              </span>
              <h1 class="login-stage-title"><?= htmlspecialchars($organizationName !== '' ? $organizationName : ($systemName !== '' ? $systemName : 'Base System'), ENT_QUOTES, 'UTF-8') ?></h1>
              <p class="login-stage-lead"><?= htmlspecialchars(__('login_contact') ?: 'Sekiranya anda menghadapi sebarang masalah, sila hubungi pentadbir sistem.', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
          </div>
        </div>

      <div class="login-stage-body">
        <div class="login-stage-panel">
          <div class="login-stage-section login-stage-section--full">
            <h2><?= __('login_contact_title') ?></h2>
            <p><?= htmlspecialchars($contactNote, ENT_QUOTES, 'UTF-8') ?></p>
            <div class="login-contact-lines">
              <?php if ($organizationName !== ''): ?>
                <span><strong><?= htmlspecialchars($organizationName, ENT_QUOTES, 'UTF-8') ?></strong></span>
              <?php endif; ?>
              <?php if (!empty($contactParts)): ?>
                <span><?= implode(' <span class="text-gray-400">|</span> ', $contactParts) ?></span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </section>

    <aside class="login-card">
      <div class="login-card-head">
        <div class="login-card-logo">
          <img src="<?= base_url($loginPanelLogo) ?>" alt="Logo">
        </div>
        <div>
          <h2 class="login-card-title"><?= __('login_heading') ?></h2>
          <p class="login-card-subtitle"><?= htmlspecialchars(app_config('site.title', 'Base System'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
      </div>

      <form method="POST" action="<?= base_path('login.php') ?>" autocomplete="off" class="login-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <div class="login-field">
          <label for="f_loginID"><?= h((__('login_userid_label') ?: 'Login ID')) ?></label>
          <input id="f_loginID" name="f_loginID" type="text" required
                 placeholder="<?= __('login_userid_placeholder') ?>"
                 autocomplete="username">
        </div>

        <div class="login-field">
          <label for="f_password"><?= __('login_password') ?></label>
          <input id="f_password" name="f_password" type="password" required
                 placeholder="******"
                 autocomplete="current-password">
        </div>

        <div class="login-inline-meta">
          <a href="<?= h(base_path('forgot-password.php')) ?>"><?= __('login_forgot') ?></a>
        </div>

        <button type="submit" class="login-submit-btn">
          <?= __('login_btnLogin') ?>
        </button>
      </form>

      <?php if ($showOneIdButton): ?>
        <div class="mt-6">
          <a href="<?= h($oneIdLoginUrl) ?>" class="login-oneid-btn">
            <span class="login-oneid-badge">ID</span>
            <span><?= h(__('login_btnOneId') ?: 'OneID Login') ?></span>
          </a>
        </div>
      <?php endif; ?>

      <div class="login-card-footer">
        <div class="login-footer-divider"></div>
        <div class="login-system-meta">
          <?= htmlspecialchars(app_config('system.name', 'e-Prestasi'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars(app_config('system.version', '2.0.0'), ENT_QUOTES, 'UTF-8') ?><br>
          <?= htmlspecialchars(app_config('system.author', 'Hak Cipta © UPNM'), ENT_QUOTES, 'UTF-8') ?>
        </div>

        <div class="login-language-switch">
          <?= __('login_language') ?>:
          <?php foreach ($loginLanguageOptions as $index => $languageCode): ?>
            <?php
              $languageLabel = strtoupper($languageCode === 'ms' ? 'BM' : $languageCode);
              $languageUrl = '?lang=' . rawurlencode($languageCode);
            ?>
            <a href="<?= htmlspecialchars($languageUrl, ENT_QUOTES, 'UTF-8') ?>" class="<?= $lang === $languageCode ? 'font-bold' : '' ?>"><?= htmlspecialchars($languageLabel, ENT_QUOTES, 'UTF-8') ?></a><?= $index < count($loginLanguageOptions) - 1 ? ' |' : '' ?>
          <?php endforeach; ?>
        </div>
      </div>
    </aside>
  </main>
</div>

<!-- ✅ Alert rendering -->
<?php if (function_exists('render_alert')) render_alert(); ?>

</body>
</html>
