<?php
declare(strict_types=1);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; style-src 'self' https://fonts.googleapis.com 'unsafe-inline'; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; object-src 'none'; base-uri 'self'; frame-ancestors 'self';");

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

define('ALLOW_ANON_AJAX', true);
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/functions-db.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/Mailer.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('reset_password_normalize_category')) {
    function reset_password_normalize_category(?string $category): string
    {
        $normalized = strtoupper(trim((string)$category));
        return in_array($normalized, ['STAF', 'PELAJAR', 'UMUM'], true) ? $normalized : 'UMUM';
    }
}

if (!function_exists('reset_password_manual_login_allowed')) {
    function reset_password_manual_login_allowed(array $user): bool
    {
        $policy = function_exists('get_auth_policy_config') ? get_auth_policy_config() : [];
        if (!is_array($policy) || $policy === []) {
            return true;
        }

        if (!empty($policy['maintenance_mode'])) {
            return false;
        }

        $category = strtolower(reset_password_normalize_category((string)($user['f_categoryUser'] ?? 'UMUM')));
        if (isset($policy['categories'][$category]) && empty($policy['categories'][$category])) {
            return false;
        }

        $ssoConfig = is_array($policy['sso'] ?? null) ? $policy['sso'] : [];
        if (empty($ssoConfig['enabled'])) {
            return true;
        }

        $mode = strtoupper(trim((string)($ssoConfig['mode'] ?? 'MANUAL')));
        if ($mode === 'ALL') {
            return false;
        }
        if ($mode === 'HYBRID') {
            $hybridMode = strtoupper(trim((string)($ssoConfig['hybrid'][$category] ?? 'MANUAL')));
            return $hybridMode === 'MANUAL';
        }

        return true;
    }
}

if (!function_exists('reset_password_client_ip')) {
    function reset_password_client_ip(): ?string
    {
        if (class_exists('AuditLogger') && method_exists('AuditLogger', 'clientIp')) {
            try {
                $ip = AuditLogger::clientIp();
                return $ip !== '' ? $ip : null;
            } catch (Throwable $e) {
                return null;
            }
        }

        $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
        return $ip !== '' ? $ip : null;
    }
}

if (!function_exists('reset_password_policy_errors')) {
    function reset_password_runtime_policy(): array
    {
        $defaults = [
            'min_length' => 8,
            'expiry_days' => 90,
            'history_count' => 5,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_number' => true,
            'require_symbol' => false,
            'block_loginid_variants' => true,
        ];
        if (!function_exists('get_auth_password_policy_config')) {
            return $defaults;
        }

        $policy = get_auth_password_policy_config();
        return [
            'min_length' => (int)($policy['min_length'] ?? $defaults['min_length']),
            'expiry_days' => (int)($policy['expiry_days'] ?? $defaults['expiry_days']),
            'history_count' => (int)($policy['history_count'] ?? $defaults['history_count']),
            'require_uppercase' => !empty($policy['require_uppercase']),
            'require_lowercase' => !empty($policy['require_lowercase']),
            'require_number' => !empty($policy['require_number']),
            'require_symbol' => !empty($policy['require_symbol']),
            'block_loginid_variants' => !empty($policy['block_loginid_variants']),
        ];
    }

    function reset_password_min_length_message(int $minLength): string
    {
        $template = (string)(__('password_change_error_min_length_template') ?: 'Kata laluan mesti sekurang-kurangnya %d aksara.');
        return sprintf($template, $minLength);
    }

    /**
     * @return string[]
     */
    function reset_password_login_id_tokens(string $loginId): array
    {
        $tokens = [];
        $normalizedLoginId = strtolower(trim($loginId));
        if ($normalizedLoginId === '') {
            return [];
        }

        $tokens[] = $normalizedLoginId;

        $collapsed = preg_replace('/[^a-z0-9]/i', '', $normalizedLoginId);
        if (is_string($collapsed) && $collapsed !== '') {
            $tokens[] = $collapsed;
        }

        $parts = preg_split('/[^a-z0-9]+/i', $normalizedLoginId) ?: [];
        foreach ($parts as $part) {
            $part = strtolower(trim((string)$part));
            if ($part === '' || strlen($part) < 4) {
                continue;
            }
            $tokens[] = $part;
            $trimmedPart = ltrim($part, '0');
            if ($trimmedPart !== '' && strlen($trimmedPart) >= 3) {
                $tokens[] = $trimmedPart;
            }
        }

        $tokens = array_values(array_unique(array_filter($tokens, static fn($token) => is_string($token) && $token !== '')));
        usort($tokens, static fn($a, $b) => strlen((string)$b) <=> strlen((string)$a));

        return $tokens;
    }

    function reset_password_policy_errors(string $password, string $confirmPassword, string $loginId): array
    {
        $runtimePolicy = reset_password_runtime_policy();
        $minLength = max(8, (int)($runtimePolicy['min_length'] ?? 8));
        $errors = [];
        if ($password === '' || $confirmPassword === '') {
            $errors[] = __('password_change_error_required');
            return $errors;
        }
        if ($password !== $confirmPassword) {
            $errors[] = __('password_change_error_mismatch');
        }
        if (strlen($password) < $minLength) {
            $errors[] = reset_password_min_length_message($minLength);
        }
        if (!empty($runtimePolicy['require_uppercase']) && !preg_match('/[A-Z]/', $password)) {
            $errors[] = __('password_change_error_uppercase');
        }
        if (!empty($runtimePolicy['require_lowercase']) && !preg_match('/[a-z]/', $password)) {
            $errors[] = __('password_change_error_lowercase');
        }
        if (!empty($runtimePolicy['require_number']) && !preg_match('/\d/', $password)) {
            $errors[] = __('password_change_error_number');
        }
        if (!empty($runtimePolicy['require_symbol']) && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = __('password_change_error_symbol');
        }

        if (!empty($runtimePolicy['block_loginid_variants'])) {
            $normalizedPassword = strtolower($password);
            foreach (reset_password_login_id_tokens($loginId) as $token) {
                if ($token !== '' && str_contains($normalizedPassword, $token)) {
                    $errors[] = __('password_change_error_contains_login');
                    break;
                }
            }
        }

        return array_values(array_unique(array_filter($errors)));
    }
}

$pdo = Database::getInstance('mysql')->getConnection();
$userModel = new User($pdo);
$featureAvailable = $userModel->passwordResetTableExists();
$requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$token = trim((string)($_POST['token'] ?? $_GET['token'] ?? ''));
$tokenHash = $token !== '' ? hash('sha256', $token) : '';
$tokenRecord = ($featureAvailable && $tokenHash !== '') ? $userModel->findActivePasswordResetToken($tokenHash) : null;
$tokenUsable = $featureAvailable
    && $tokenRecord !== null
    && (int)($tokenRecord['f_flag'] ?? 0) === 1
    && reset_password_manual_login_allowed($tokenRecord);
$runtimePasswordPolicy = reset_password_runtime_policy();
$errors = [];

if ($requestMethod === 'POST') {
    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    if ($csrfToken === '' || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), $csrfToken)) {
        $errors[] = __('password_change_error_csrf');
    } elseif (!$featureAvailable) {
        $errors[] = __('forgot_password_feature_unavailable');
    } elseif ($token === '' || !$tokenUsable || $tokenRecord === null) {
        $errors[] = __('reset_password_token_invalid');
    } else {
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');
        $loginId = trim((string)($tokenRecord['f_loginID'] ?? ''));
        $errors = reset_password_policy_errors($newPassword, $confirmPassword, $loginId);

        if ($errors === [] && password_verify($newPassword, (string)($tokenRecord['f_password'] ?? ''))) {
            $errors[] = __('password_change_error_reuse_current');
        } elseif ($errors === [] && $userModel->isPasswordReusedInHistory($loginId, $newPassword, (int)($runtimePasswordPolicy['history_count'] ?? 5))) {
            $errors[] = __('password_change_error_reuse_history');
        }

        if ($errors === []) {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $expiresAt = (new DateTimeImmutable('now', new DateTimeZone('Asia/Kuala_Lumpur')))
                ->modify('+' . (int)($runtimePasswordPolicy['expiry_days'] ?? 90) . ' days')
                ->format('Y-m-d H:i:s');
            $updated = $userModel->updateManualPasswordByLoginID($loginId, $passwordHash, null, (int)($runtimePasswordPolicy['expiry_days'] ?? 90), 'reset_password');

            if (!$updated) {
                $errors[] = __('password_change_error_update_failed');
            } else {
                $notificationEmail = strtolower(trim((string)($tokenRecord['f_email'] ?? $tokenRecord['f_loginID'] ?? '')));
                if ($notificationEmail !== '' && filter_var($notificationEmail, FILTER_VALIDATE_EMAIL)) {
                    try {
                        [$mailHtml, $mailText] = Mailer::render('password-change-notification', [
                            'displayName' => trim((string)($tokenRecord['f_nama'] ?? $tokenRecord['f_nickname'] ?? $loginId)),
                            'loginId' => trim((string)($tokenRecord['f_loginID'] ?? $loginId)),
                            'changedAt' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Kuala_Lumpur')))->format('Y-m-d H:i:s'),
                            'siteTitle' => app_config('site.title', 'e-Prestasi'),
                        ]);
                        Mailer::quickSend(
                            $pdo,
                            $notificationEmail,
                            (string)(__('password_change_notification_subject') ?: 'Kata laluan anda telah dikemas kini'),
                            $mailHtml,
                            $mailText
                        );
                    } catch (Throwable $mailError) {
                        error_log('[reset-password] Notification mail failed: ' . $mailError->getMessage());
                    }
                }

                $userModel->markPasswordResetTokenUsed((int)$tokenRecord['id'], reset_password_client_ip());
                $userModel->invalidatePasswordResetTokensByLoginID($loginId);
                unset($_SESSION['pending_password_change']);

                if (function_exists('audit_event')) {
                    try {
                        $actorName = trim((string)($tokenRecord['f_nama'] ?? $tokenRecord['f_nickname'] ?? ''));
                        $actorLoginId = trim((string)($tokenRecord['f_loginID'] ?? ''));
                        $actorLabel = function_exists('audit_format_actor_label')
                            ? audit_format_actor_label($actorName !== '' ? $actorName : null, $actorLoginId)
                            : $actorLoginId;

                        audit_event([
                            'event_type' => 'UPDATE',
                            'severity' => 'SECURITY',
                            'outcome' => 'SUCCESS',
                            'target_type' => 'auth',
                            'target_id' => 'password',
                            'target_label' => $actorLoginId,
                            'message' => function_exists('audit_format_message')
                                ? audit_format_message('Password changed through reset link', $actorLabel)
                                : 'Password changed through reset link',
                            'request_id' => $GLOBALS['__AUDIT_REQUEST_ID'] ?? null,
                            'session_id' => session_id() ?: null,
                            'user_id' => !empty($tokenRecord['f_userID']) && is_numeric($tokenRecord['f_userID']) ? (int)$tokenRecord['f_userID'] : null,
                            'actor_label' => $actorLabel,
                            'meta' => [
                                'login_id' => $actorLoginId,
                                'reason_code' => 'password_reset_completed',
                                'auth_method' => 'MANUAL',
                                'auth_flow' => 'reset_password',
                                'category' => reset_password_normalize_category((string)($tokenRecord['f_categoryUser'] ?? 'UMUM')),
                                'client_ip' => reset_password_client_ip(),
                                'user_agent' => trim((string)($_SERVER['HTTP_USER_AGENT'] ?? '')) ?: null,
                                'source' => 'reset_password_public_page',
                                'password_expiry_at' => $expiresAt,
                            ],
                        ]);
                    } catch (Throwable $auditError) {
                        error_log('[reset-password] Audit logging failed: ' . $auditError->getMessage());
                    }
                }

                set_alert([
                    'type' => 'sweet',
                    'title' => 'reset_password_success_title',
                    'text' => 'reset_password_success_msg',
                    'icon' => 'success',
                    'confirm' => true,
                ]);
                redirect('index.php');
            }
        }
    }
}

$loginIdLabel = trim((string)($tokenRecord['f_loginID'] ?? ''));
$version = time();
?>
<!DOCTYPE html>
<html lang="<?= h($_SESSION['lang'] ?? 'ms') ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h(__('reset_password_page_title')) ?> | <?= h(app_config('site.title', 'e-Prestasi')) ?></title>
  <link rel="icon" href="<?= base_url(app_config('site.favicon', 'assets/images/default.ico')) ?>" type="image/x-icon">
  <link rel="stylesheet" href="<?= base_url('assets/css/output.css?v=' . $version) ?>">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11?v=<?= $version ?>"></script>
  <style>
    :root {
      --rp-shell-bg:
        radial-gradient(circle at top left, rgba(14, 165, 233, 0.16), transparent 34%),
        radial-gradient(circle at bottom right, rgba(20, 184, 166, 0.18), transparent 30%),
        linear-gradient(135deg, #eef7ff 0%, #f8fbff 48%, #eef8f4 100%);
      --rp-panel-bg: rgba(255, 255, 255, 0.94);
      --rp-border: rgba(148, 163, 184, 0.18);
      --rp-shadow: 0 26px 60px rgba(15, 23, 42, 0.12);
      --rp-text: #0f172a;
      --rp-muted: #64748b;
      --rp-accent: #0f766e;
    }

    body {
      min-height: 100vh;
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: var(--rp-shell-bg);
      color: var(--rp-text);
    }

    .rp-shell {
      min-height: 100vh;
      display: grid;
      place-items: center;
      padding: 24px 16px;
    }

    .rp-card {
      width: min(100%, 560px);
      border: 1px solid var(--rp-border);
      border-radius: 22px;
      background: var(--rp-panel-bg);
      box-shadow: var(--rp-shadow);
      padding: 28px 26px 24px;
      backdrop-filter: blur(18px);
    }

    .rp-kicker {
      display: inline-flex;
      align-items: center;
      border-radius: 999px;
      padding: 7px 12px;
      background: rgba(15, 118, 110, 0.1);
      color: var(--rp-accent);
      font-size: 11px;
      font-weight: 700;
      letter-spacing: .08em;
      text-transform: uppercase;
    }

    .rp-title {
      margin: 16px 0 8px;
      font-size: 28px;
      line-height: 1.1;
      letter-spacing: -.03em;
    }

    .rp-copy {
      margin: 0 0 18px;
      color: var(--rp-muted);
      line-height: 1.75;
      font-size: 13px;
    }

    .rp-banner,
    .rp-errors {
      margin-bottom: 18px;
      padding: 14px 16px;
      border-radius: 16px;
      font-size: 12px;
      line-height: 1.7;
    }

    .rp-banner {
      border: 1px solid rgba(14, 116, 144, 0.16);
      background: linear-gradient(180deg, rgba(240, 249, 255, 0.92), rgba(248, 250, 252, 0.94));
      color: #0f172a;
    }

    .rp-errors {
      border: 1px solid rgba(185, 28, 28, 0.18);
      background: rgba(254, 242, 242, 0.95);
      color: #991b1b;
    }

    .rp-errors ul {
      margin: 0;
      padding-left: 18px;
    }

    .rp-form {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .rp-row {
      display: grid;
      gap: 16px;
    }

    .rp-field {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .rp-field label {
      font-size: 12px;
      font-weight: 600;
      color: #334155;
    }

    .rp-field input {
      width: 100%;
      border-radius: 12px;
      border: 1px solid rgba(148, 163, 184, 0.22);
      background: rgba(248, 250, 252, 0.9);
      padding: 13px 15px;
      font-size: 13px;
      outline: none;
      transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
    }

    .rp-field input:focus {
      border-color: rgba(20, 184, 166, 0.42);
      box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.12);
      background: #fff;
    }

    .rp-field input[readonly] {
      background: rgba(241, 245, 249, 0.88);
      color: #475569;
    }

    .rp-password-wrap {
      position: relative;
    }

    .rp-password-wrap input {
      padding-right: 50px;
    }

    .rp-password-toggle {
      position: absolute;
      top: 50%;
      right: 9px;
      transform: translateY(-50%);
      width: 34px;
      height: 34px;
      border: 0;
      border-radius: 10px;
      background: transparent;
      color: #64748b;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: background-color .18s ease, color .18s ease;
    }

    .rp-password-toggle:hover {
      background: rgba(148, 163, 184, 0.12);
      color: #0f172a;
    }

    .rp-password-toggle:focus {
      outline: none;
      background: rgba(20, 184, 166, 0.12);
      color: #0f766e;
    }

    .rp-password-toggle svg {
      width: 18px;
      height: 18px;
      pointer-events: none;
    }

    .rp-hint {
      color: var(--rp-muted);
      font-size: 11px;
      line-height: 1.65;
    }

    .rp-rules {
      margin-top: -2px;
      padding: 14px 16px;
      border-radius: 14px;
      border: 1px solid rgba(148, 163, 184, 0.18);
      background: rgba(248, 250, 252, 0.94);
    }

    .rp-rules-title {
      margin: 0 0 10px;
      font-size: 12px;
      font-weight: 700;
      color: #0f172a;
    }

    .rp-rules-list {
      list-style: none;
      margin: 0;
      padding: 0;
      display: grid;
      gap: 8px;
    }

    .rp-rule {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 12px;
      line-height: 1.6;
      color: var(--rp-muted);
    }

    .rp-rule-dot {
      width: 18px;
      height: 18px;
      border-radius: 999px;
      border: 1px solid #cbd5e1;
      background: #fff;
      flex: 0 0 auto;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: background-color .18s ease, transform .18s ease, border-color .18s ease;
      font-size: 11px;
      font-weight: 700;
      color: #94a3b8;
    }

    .rp-rule.is-valid {
      color: #0f766e;
    }

    .rp-rule.is-valid .rp-rule-dot {
      background: #0f766e;
      border-color: #0f766e;
      transform: scale(1.08);
      color: #fff;
    }

    .rp-submit,
    .rp-back {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 12px;
      text-decoration: none;
      font-weight: 700;
      padding: 13px 16px;
      transition: transform .18s ease, box-shadow .18s ease;
    }

    .rp-submit {
      width: 100%;
      border: 0;
      background: linear-gradient(135deg, #0f766e, #14b8a6);
      color: #fff;
      box-shadow: 0 16px 28px rgba(15, 118, 110, 0.22);
      cursor: pointer;
    }

    .rp-submit:disabled {
      opacity: .55;
      cursor: not-allowed;
      box-shadow: none;
      transform: none;
    }

    .rp-submit:hover,
    .rp-back:hover {
      transform: translateY(-1px);
    }

    .rp-back {
      margin-top: 6px;
      border: 1px solid rgba(148, 163, 184, 0.2);
      color: #0f172a;
      background: rgba(255, 255, 255, 0.78);
      box-shadow: 0 10px 20px rgba(15, 23, 42, 0.06);
    }
  </style>
</head>
<body>
  <div class="rp-shell">
    <div class="rp-card">
      <span class="rp-kicker"><?= h(__('reset_password_kicker')) ?></span>
      <h1 class="rp-title"><?= h(__('reset_password_heading')) ?></h1>
      <p class="rp-copy"><?= h(__('reset_password_intro')) ?></p>

      <?php if (!$featureAvailable): ?>
        <div class="rp-errors">
          <ul><li><?= h(__('forgot_password_feature_unavailable')) ?></li></ul>
        </div>
      <?php elseif (!$tokenUsable): ?>
        <div class="rp-errors">
          <ul><li><?= h(__('reset_password_token_invalid')) ?></li></ul>
        </div>
        <a class="rp-back" href="<?= h(base_path('forgot-password.php')) ?>"><?= h(__('reset_password_request_new_link')) ?></a>
      <?php else: ?>
        <?php if ($errors !== []): ?>
          <div class="rp-errors">
            <ul>
              <?php foreach ($errors as $error): ?>
                <li><?= h((string)$error) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="POST" action="<?= h(base_path('reset-password.php')) ?>" class="rp-form" autocomplete="off">
          <input type="hidden" name="csrf_token" value="<?= h((string)($_SESSION['csrf_token'] ?? '')) ?>">
          <input type="hidden" name="token" value="<?= h($token) ?>">

          <div class="rp-row">
            <div class="rp-field">
              <label for="login_id"><?= h(__('password_change_login_id_label')) ?></label>
              <input id="login_id" type="text" value="<?= h($loginIdLabel) ?>" readonly>
            </div>

            <div class="rp-field">
              <label for="new_password"><?= h(__('password_change_new_password_label')) ?></label>
              <div class="rp-password-wrap">
                <input id="new_password" name="new_password" type="password" required autocomplete="new-password">
                <button type="button" class="rp-password-toggle" data-password-toggle="new_password" aria-label="Show password">
                  <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M2 12C4.8 7.6 8.1 5.4 12 5.4C15.9 5.4 19.2 7.6 22 12C19.2 16.4 15.9 18.6 12 18.6C8.1 18.6 4.8 16.4 2 12Z" stroke="currentColor" stroke-width="1.7"/>
                    <circle cx="12" cy="12" r="3.2" stroke="currentColor" stroke-width="1.7"/>
                  </svg>
                </button>
              </div>
            </div>

            <div class="rp-field">
              <label for="confirm_password"><?= h(__('password_change_confirm_password_label')) ?></label>
              <div class="rp-password-wrap">
                <input id="confirm_password" name="confirm_password" type="password" required autocomplete="new-password">
                <button type="button" class="rp-password-toggle" data-password-toggle="confirm_password" aria-label="Show password confirmation">
                  <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M2 12C4.8 7.6 8.1 5.4 12 5.4C15.9 5.4 19.2 7.6 22 12C19.2 16.4 15.9 18.6 12 18.6C8.1 18.6 4.8 16.4 2 12Z" stroke="currentColor" stroke-width="1.7"/>
                    <circle cx="12" cy="12" r="3.2" stroke="currentColor" stroke-width="1.7"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <div class="rp-rules" id="password-rules" data-login-id="<?= h($loginIdLabel) ?>">
            <div class="rp-rules-title"><?= h(__('password_change_live_title')) ?></div>
            <ul class="rp-rules-list">
                <li class="rp-rule" data-rule="length"><span class="rp-rule-dot" aria-hidden="true">&bull;</span><span><?= h(reset_password_min_length_message((int)($runtimePasswordPolicy['min_length'] ?? 8))) ?></span></li>
              <?php if (!empty($runtimePasswordPolicy['require_uppercase'])): ?><li class="rp-rule" data-rule="uppercase"><span class="rp-rule-dot" aria-hidden="true">&bull;</span><span><?= h(__('password_change_error_uppercase')) ?></span></li><?php endif; ?>
              <?php if (!empty($runtimePasswordPolicy['require_lowercase'])): ?><li class="rp-rule" data-rule="lowercase"><span class="rp-rule-dot" aria-hidden="true">&bull;</span><span><?= h(__('password_change_error_lowercase')) ?></span></li><?php endif; ?>
              <?php if (!empty($runtimePasswordPolicy['require_number'])): ?><li class="rp-rule" data-rule="number"><span class="rp-rule-dot" aria-hidden="true">&bull;</span><span><?= h(__('password_change_error_number')) ?></span></li><?php endif; ?>
              <?php if (!empty($runtimePasswordPolicy['require_symbol'])): ?><li class="rp-rule" data-rule="symbol"><span class="rp-rule-dot" aria-hidden="true">&bull;</span><span><?= h(__('password_change_error_symbol')) ?></span></li><?php endif; ?>
              <?php if (!empty($runtimePasswordPolicy['block_loginid_variants'])): ?><li class="rp-rule" data-rule="no-login"><span class="rp-rule-dot" aria-hidden="true">&bull;</span><span><?= h(__('password_change_rule_no_login_id')) ?></span></li><?php endif; ?>
              <li class="rp-rule" data-rule="match"><span class="rp-rule-dot" aria-hidden="true">&bull;</span><span><?= h(__('password_change_rule_confirm_match')) ?></span></li>
            </ul>
          </div>

          <button type="submit" class="rp-submit" id="rp-submit" disabled><?= h(__('reset_password_submit_btn')) ?></button>
          <a class="rp-back" href="<?= h(base_path('index.php')) ?>"><?= h(__('forgot_password_back_to_login')) ?></a>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <?php if (function_exists('render_alert')) render_alert(); ?>
  <script>
    (function () {
      var newPasswordEl = document.getElementById('new_password');
      var confirmPasswordEl = document.getElementById('confirm_password');
      var rulesRoot = document.getElementById('password-rules');
      if (!newPasswordEl || !confirmPasswordEl || !rulesRoot) {
        return;
      }

      var loginId = String((rulesRoot.getAttribute('data-login-id') || '')).trim().toLowerCase();
      var loginTokens = buildLoginTokens(loginId);
      var ruleEls = {
        length: rulesRoot.querySelector('[data-rule="length"]'),
        uppercase: rulesRoot.querySelector('[data-rule="uppercase"]'),
        lowercase: rulesRoot.querySelector('[data-rule="lowercase"]'),
        number: rulesRoot.querySelector('[data-rule="number"]'),
        symbol: rulesRoot.querySelector('[data-rule="symbol"]'),
        noLogin: rulesRoot.querySelector('[data-rule="no-login"]'),
        match: rulesRoot.querySelector('[data-rule="match"]'),
      };
      var submitBtn = document.getElementById('rp-submit');
      var requireUppercase = <?= !empty($runtimePasswordPolicy['require_uppercase']) ? 'true' : 'false' ?>;
      var requireLowercase = <?= !empty($runtimePasswordPolicy['require_lowercase']) ? 'true' : 'false' ?>;
      var requireNumber = <?= !empty($runtimePasswordPolicy['require_number']) ? 'true' : 'false' ?>;
      var requireSymbol = <?= !empty($runtimePasswordPolicy['require_symbol']) ? 'true' : 'false' ?>;
      var blockLoginVariants = <?= !empty($runtimePasswordPolicy['block_loginid_variants']) ? 'true' : 'false' ?>;

      function setRuleState(el, valid) {
        if (!el) return;
        el.classList.toggle('is-valid', Boolean(valid));
        var dot = el.querySelector('.rp-rule-dot');
        if (dot) {
          dot.innerHTML = valid ? '&#10003;' : '&bull;';
        }
      }

      function buildLoginTokens(rawLoginId) {
        var normalized = String(rawLoginId || '').trim().toLowerCase();
        if (!normalized) {
          return [];
        }

        var tokens = [normalized];
        var collapsed = normalized.replace(/[^a-z0-9]/g, '');
        if (collapsed) {
          tokens.push(collapsed);
          var trimmedCollapsed = collapsed.replace(/^0+/, '');
          if (trimmedCollapsed && trimmedCollapsed.length >= 3) {
            tokens.push(trimmedCollapsed);
          }
        }

        var parts = normalized.split(/[^a-z0-9]+/);
        for (var i = 0; i < parts.length; i += 1) {
          var part = String(parts[i] || '').trim().toLowerCase();
          if (part && part.length >= 4) {
            tokens.push(part);
            var trimmedPart = part.replace(/^0+/, '');
            if (trimmedPart && trimmedPart.length >= 3) {
              tokens.push(trimmedPart);
            }
          }
        }

        var seen = {};
        var unique = [];
        for (var j = 0; j < tokens.length; j += 1) {
          var token = tokens[j];
          if (!token || seen[token]) {
            continue;
          }
          seen[token] = true;
          unique.push(token);
        }

        unique.sort(function (a, b) {
          return b.length - a.length;
        });

        return unique;
      }

      function updateRules() {
        var password = String(newPasswordEl.value || '');
        var confirmPassword = String(confirmPasswordEl.value || '');
        var normalizedPassword = password.toLowerCase();

        var states = {
          length: password.length >= <?= (int)($runtimePasswordPolicy['min_length'] ?? 8) ?>,
          uppercase: !requireUppercase || /[A-Z]/.test(password),
          lowercase: !requireLowercase || /[a-z]/.test(password),
          number: !requireNumber || /\d/.test(password),
          symbol: !requireSymbol || /[^A-Za-z0-9]/.test(password),
          noLogin: !blockLoginVariants || (password.length > 0 ? loginTokens.every(function (token) {
            return normalizedPassword.indexOf(token) === -1;
          }) : false),
          match: password !== '' && confirmPassword !== '' && password === confirmPassword,
        };

        setRuleState(ruleEls.length, states.length);
        setRuleState(ruleEls.uppercase, states.uppercase);
        setRuleState(ruleEls.lowercase, states.lowercase);
        setRuleState(ruleEls.number, states.number);
        setRuleState(ruleEls.symbol, states.symbol);
        setRuleState(ruleEls.noLogin, states.noLogin);
        setRuleState(ruleEls.match, states.match);

        if (submitBtn) {
          var allValid =
            states.length &&
            states.uppercase &&
            states.lowercase &&
            states.number &&
            states.symbol &&
            states.noLogin &&
            states.match;
          submitBtn.disabled = !allValid;
        }
      }

      newPasswordEl.addEventListener('input', updateRules);
      confirmPasswordEl.addEventListener('input', updateRules);
      updateRules();

      var toggles = document.querySelectorAll('[data-password-toggle]');
      function revealInput(inputEl) {
        if (inputEl) inputEl.setAttribute('type', 'text');
      }
      function maskInput(inputEl) {
        if (inputEl) inputEl.setAttribute('type', 'password');
      }

      for (var k = 0; k < toggles.length; k += 1) {
        (function (toggleBtn) {
          var targetId = toggleBtn.getAttribute('data-password-toggle');
          var targetInput = targetId ? document.getElementById(targetId) : null;
          if (!targetInput) return;

          toggleBtn.addEventListener('mousedown', function () { revealInput(targetInput); });
          toggleBtn.addEventListener('mouseup', function () { maskInput(targetInput); });
          toggleBtn.addEventListener('mouseleave', function () { maskInput(targetInput); });
          toggleBtn.addEventListener('touchstart', function () { revealInput(targetInput); }, { passive: true });
          toggleBtn.addEventListener('touchend', function () { maskInput(targetInput); });
          toggleBtn.addEventListener('touchcancel', function () { maskInput(targetInput); });
          toggleBtn.addEventListener('blur', function () { maskInput(targetInput); });
        })(toggles[k]);
      }
    })();
  </script>
</body>
</html>
