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

if (!function_exists('forgot_password_check_rate_limit')) {
    function forgot_password_check_rate_limit(string $key, int $maxRequests = 5, int $windowSeconds = 900): bool
    {
        $now = time();
        $sessionKey = 'forgot_password_rate_' . $key;
        $state = $_SESSION[$sessionKey] ?? ['count' => 0, 'reset_at' => $now + $windowSeconds];

        if ($now >= (int)($state['reset_at'] ?? 0)) {
            $state = ['count' => 0, 'reset_at' => $now + $windowSeconds];
        }

        if ((int)($state['count'] ?? 0) >= $maxRequests) {
            $_SESSION[$sessionKey] = $state;
            return false;
        }

        $state['count'] = (int)($state['count'] ?? 0) + 1;
        $_SESSION[$sessionKey] = $state;
        return true;
    }
}

if (!function_exists('forgot_password_normalize_category')) {
    function forgot_password_normalize_category(?string $category): string
    {
        $normalized = strtoupper(trim((string)$category));
        return in_array($normalized, ['STAF', 'PELAJAR', 'UMUM'], true) ? $normalized : 'UMUM';
    }
}

if (!function_exists('forgot_password_manual_login_allowed')) {
    function forgot_password_manual_login_allowed(array $user): bool
    {
        $policy = function_exists('get_auth_policy_config') ? get_auth_policy_config() : [];
        if (!is_array($policy) || $policy === []) {
            return true;
        }

        if (!empty($policy['maintenance_mode'])) {
            return false;
        }

        $category = strtolower(forgot_password_normalize_category((string)($user['f_categoryUser'] ?? 'UMUM')));
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

if (!function_exists('forgot_password_resolve_email')) {
    function forgot_password_resolve_email(array $user): ?string
    {
        $candidates = [
            $user['f_email'] ?? null,
            $user['f_loginID'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $candidate = strtolower(trim((string)$candidate));
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                return $candidate;
            }
        }

        return null;
    }
}

if (!function_exists('forgot_password_client_ip')) {
    function forgot_password_client_ip(): ?string
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

if (!function_exists('forgot_password_display_timezone')) {
    function forgot_password_display_timezone(): DateTimeZone
    {
        try {
            return new DateTimeZone('Asia/Kuala_Lumpur');
        } catch (Throwable $e) {
            return new DateTimeZone(date_default_timezone_get() ?: 'UTC');
        }
    }
}

$pdo = Database::getInstance('mysql')->getConnection();
$userModel = new User($pdo);
$featureAvailable = $userModel->passwordResetTableExists();
$runtimePasswordPolicy = function_exists('get_auth_password_policy_config') ? get_auth_password_policy_config() : [];
$resetTokenMinutes = max(5, (int)($runtimePasswordPolicy['reset_token_minutes'] ?? 30));
$requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$errors = [];
$submitted = false;
$loginIdValue = trim((string)($_POST['login_id'] ?? ''));
$successReference = trim((string)($_GET['ref'] ?? ''));
$showSuccessAlert = trim((string)($_GET['status'] ?? '')) === 'sent';

if ($requestMethod === 'POST') {
    $submitted = true;
    $csrfToken = (string)($_POST['csrf_token'] ?? '');

    if ($csrfToken === '' || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), $csrfToken)) {
        $errors[] = __('forgot_password_error_csrf');
    } elseif (!$featureAvailable) {
        $errors[] = __('forgot_password_feature_unavailable');
    } elseif (!forgot_password_check_rate_limit('request', 5, 900)) {
        $errors[] = __('forgot_password_error_rate_limited');
    } else {
        $loginId = trim($loginIdValue);
        if ($loginId === '') {
            $errors[] = __('forgot_password_error_required');
        } else {
            $candidate = $userModel->findPasswordResetCandidate($loginId);
            $canIssueReset = false;
            $recipientEmail = null;

            if ($candidate) {
                $recipientEmail = forgot_password_resolve_email($candidate);
                $canIssueReset = (int)($candidate['f_flag'] ?? 0) === 1
                    && $recipientEmail !== null
                    && forgot_password_manual_login_allowed($candidate);
            }

            if ($canIssueReset && $recipientEmail !== null) {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expiresAt = (new DateTimeImmutable('now', forgot_password_display_timezone()))
                    ->modify('+' . $resetTokenMinutes . ' minutes')
                    ->format('Y-m-d H:i:s');
                $resetUrl = base_url('reset-password.php') . '?token=' . rawurlencode($token);
                $displayName = trim((string)($candidate['f_nama'] ?? $candidate['f_nickname'] ?? $candidate['f_loginID'] ?? ''));

                $created = $userModel->createPasswordResetToken(
                    (string)$candidate['f_loginID'],
                    $recipientEmail,
                    $tokenHash,
                    null,
                    forgot_password_client_ip(),
                    substr(trim((string)($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 255),
                    $resetTokenMinutes
                );

                if ($created) {
                    [$mailHtml, $mailText] = Mailer::render('password-reset-request', [
                        'displayName' => $displayName !== '' ? $displayName : (string)$candidate['f_loginID'],
                        'loginId' => (string)$candidate['f_loginID'],
                        'resetUrl' => $resetUrl,
                        'expiresAt' => $expiresAt,
                        'expiresInMinutes' => $resetTokenMinutes,
                        'siteTitle' => app_config('site.title', 'e-Prestasi'),
                    ]);

                    $subject = (string)(__('forgot_password_mail_subject') ?: 'Reset kata laluan akaun anda');
                    $sent = Mailer::quickSend($pdo, $recipientEmail, $subject, $mailHtml, $mailText);

                    if (!$sent) {
                        error_log('[forgot-password] Failed sending reset mail to ' . $recipientEmail);
                    }

                    if (function_exists('audit_event')) {
                        try {
                            audit_event([
                                'event_type' => 'UPDATE',
                                'severity' => 'SECURITY',
                                'outcome' => $sent ? 'SUCCESS' : 'PENDING',
                                'target_type' => 'auth',
                                'target_id' => (string)$candidate['f_loginID'],
                                'target_label' => 'Password reset request',
                                'message' => 'Password reset link requested',
                                'request_id' => $GLOBALS['__AUDIT_REQUEST_ID'] ?? null,
                                'session_id' => session_id() ?: null,
                                'user_id' => !empty($candidate['f_userID']) && is_numeric($candidate['f_userID']) ? (int)$candidate['f_userID'] : null,
                            'meta' => [
                                'login_id' => (string)$candidate['f_loginID'],
                                'reason_code' => $sent ? 'password_reset_requested' : 'password_reset_mail_pending',
                                'auth_method' => 'MANUAL',
                                'auth_flow' => 'forgot_password',
                                'category' => forgot_password_normalize_category((string)($candidate['f_categoryUser'] ?? 'UMUM')),
                                'client_ip' => forgot_password_client_ip(),
                                'user_agent' => trim((string)($_SERVER['HTTP_USER_AGENT'] ?? '')) ?: null,
                                'email' => $recipientEmail,
                                'mail_sent' => $sent,
                                'source' => 'forgot_password_public_page',
                                ],
                            ]);
                        } catch (Throwable $auditError) {
                            error_log('[forgot-password] Audit logging failed: ' . $auditError->getMessage());
                        }
                    }
                }
            } elseif (function_exists('audit_event')) {
                try {
                    audit_event([
                        'event_type' => 'UPDATE',
                        'severity' => 'SECURITY',
                        'outcome' => 'IGNORED',
                        'target_type' => 'auth',
                        'target_id' => $loginId,
                        'target_label' => 'Password reset request',
                        'message' => 'Password reset link requested for ineligible account',
                        'request_id' => $GLOBALS['__AUDIT_REQUEST_ID'] ?? null,
                        'session_id' => session_id() ?: null,
                        'meta' => [
                            'login_id' => $loginId,
                            'reason_code' => 'password_reset_ineligible',
                            'auth_method' => 'MANUAL',
                            'auth_flow' => 'forgot_password',
                            'client_ip' => forgot_password_client_ip(),
                            'user_agent' => trim((string)($_SERVER['HTTP_USER_AGENT'] ?? '')) ?: null,
                            'source' => 'forgot_password_public_page',
                        ],
                    ]);
                } catch (Throwable $auditError) {
                    error_log('[forgot-password] Audit logging failed: ' . $auditError->getMessage());
                }
            }
        }
    }

    if ($errors === []) {
        $successMessage = trim((string)__('forgot_password_success_msg'));
        $referenceLabel = trim((string)__('forgot_password_success_reference'));
        if ($loginIdValue !== '') {
            $successMessage .= "\n\n" . $referenceLabel . ': ' . $loginIdValue;
        }

        $redirectTarget = 'forgot-password.php?status=sent';
        if ($loginIdValue !== '') {
            $redirectTarget .= '&ref=' . rawurlencode($loginIdValue);
        }
        redirect($redirectTarget);
    }
}

$version = time();
?>
<!DOCTYPE html>
<html lang="<?= h($_SESSION['lang'] ?? 'ms') ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h(__('forgot_password_page_title')) ?> | <?= h(app_config('site.title', 'e-Prestasi')) ?></title>
  <link rel="icon" href="<?= base_url(app_config('site.favicon', 'assets/images/default.ico')) ?>" type="image/x-icon">
  <link rel="stylesheet" href="<?= base_url('assets/css/output.css?v=' . $version) ?>">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11?v=<?= $version ?>"></script>
  <style>
    :root {
      --fp-shell-bg:
        radial-gradient(circle at top left, rgba(14, 165, 233, 0.14), transparent 34%),
        radial-gradient(circle at bottom right, rgba(249, 115, 22, 0.14), transparent 28%),
        linear-gradient(135deg, #eef7ff 0%, #f8fbff 48%, #fff7ed 100%);
      --fp-panel-bg: rgba(255, 255, 255, 0.94);
      --fp-border: rgba(148, 163, 184, 0.18);
      --fp-shadow: 0 26px 60px rgba(15, 23, 42, 0.12);
      --fp-text: #0f172a;
      --fp-muted: #64748b;
      --fp-accent: #0369a1;
      --fp-warning-bg: rgba(255, 247, 237, 0.94);
      --fp-warning-border: rgba(249, 115, 22, 0.24);
      --fp-warning-text: #9a3412;
    }

    body {
      min-height: 100vh;
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: var(--fp-shell-bg);
      color: var(--fp-text);
    }

    .fp-shell {
      min-height: 100vh;
      display: grid;
      place-items: center;
      padding: 24px 16px;
    }

    .fp-card {
      width: min(100%, 580px);
      border: 1px solid var(--fp-border);
      border-radius: 22px;
      background: var(--fp-panel-bg);
      box-shadow: var(--fp-shadow);
      padding: 28px 26px 24px;
      backdrop-filter: blur(18px);
    }

    .fp-kicker {
      display: inline-flex;
      align-items: center;
      border-radius: 999px;
      padding: 7px 12px;
      background: rgba(3, 105, 161, 0.1);
      color: var(--fp-accent);
      font-size: 11px;
      font-weight: 700;
      letter-spacing: .08em;
      text-transform: uppercase;
    }

    .fp-title {
      margin: 16px 0 8px;
      font-size: 28px;
      line-height: 1.1;
      letter-spacing: -.03em;
    }

    .fp-copy {
      margin: 0 0 18px;
      color: var(--fp-muted);
      line-height: 1.75;
      font-size: 13px;
    }

    .fp-warning {
      margin-bottom: 18px;
      padding: 14px 16px;
      border-radius: 16px;
      border: 1px solid var(--fp-warning-border);
      background: var(--fp-warning-bg);
      color: var(--fp-warning-text);
      font-size: 12px;
      line-height: 1.7;
    }

    .fp-success {
      margin-bottom: 18px;
      padding: 14px 16px;
      border-radius: 16px;
      border: 1px solid rgba(15, 118, 110, 0.18);
      background: rgba(240, 253, 250, 0.94);
      color: #0f766e;
      font-size: 12px;
      line-height: 1.7;
    }

    .fp-errors {
      margin: 0 0 18px;
      padding: 14px 16px;
      border-radius: 16px;
      border: 1px solid rgba(185, 28, 28, 0.18);
      background: rgba(254, 242, 242, 0.95);
      color: #991b1b;
      font-size: 12px;
      line-height: 1.7;
    }

    .fp-errors ul {
      margin: 0;
      padding-left: 18px;
    }

    .fp-form {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .fp-field {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .fp-field label {
      font-size: 12px;
      font-weight: 600;
      color: #334155;
    }

    .fp-field input {
      width: 100%;
      border-radius: 12px;
      border: 1px solid rgba(148, 163, 184, 0.22);
      background: rgba(248, 250, 252, 0.9);
      padding: 13px 15px;
      font-size: 13px;
      outline: none;
      transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
    }

    .fp-field input:focus {
      border-color: rgba(14, 165, 233, 0.46);
      box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.12);
      background: #fff;
    }

    .fp-hint {
      color: var(--fp-muted);
      font-size: 11px;
      line-height: 1.65;
    }

    .fp-submit {
      width: 100%;
      border: 0;
      border-radius: 12px;
      background: linear-gradient(135deg, #0284c7, #0ea5e9);
      color: #fff;
      padding: 13px 16px;
      font-weight: 700;
      letter-spacing: .01em;
      box-shadow: 0 16px 28px rgba(2, 132, 199, 0.22);
      transition: transform .18s ease, box-shadow .18s ease, opacity .18s ease;
      cursor: pointer;
    }

    .fp-submit:hover {
      transform: translateY(-1px);
      box-shadow: 0 20px 34px rgba(2, 132, 199, 0.28);
    }

    .fp-submit:disabled {
      opacity: .55;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }

    .fp-footer {
      margin-top: 18px;
      display: flex;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
      align-items: center;
    }

    .fp-back {
      color: var(--fp-accent);
      text-decoration: none;
      font-size: 12px;
      font-weight: 600;
    }

    .fp-back:hover {
      text-decoration: underline;
    }

    .fp-meta {
      color: #94a3b8;
      font-size: 11px;
      line-height: 1.7;
      text-align: right;
    }

    @media (max-width: 640px) {
      .fp-card {
        padding: 22px 18px 20px;
      }

      .fp-title {
        font-size: 24px;
      }

      .fp-footer {
        flex-direction: column;
        align-items: flex-start;
      }

      .fp-meta {
        text-align: left;
      }
    }
  </style>
</head>
<body>
  <div class="fp-shell">
    <div class="fp-card">
      <span class="fp-kicker"><?= h(__('forgot_password_kicker')) ?></span>
      <h1 class="fp-title"><?= h(__('forgot_password_heading')) ?></h1>
      <p class="fp-copy"><?= h(__('forgot_password_intro')) ?></p>

      <?php if (!$featureAvailable): ?>
        <div class="fp-warning"><?= h(__('forgot_password_feature_unavailable')) ?></div>
      <?php endif; ?>

      <?php if ($showSuccessAlert): ?>
        <div class="fp-success">
          <?= h(__('forgot_password_success_msg')) ?>
          <?php if ($successReference !== ''): ?>
            <br><strong><?= h(__('forgot_password_success_reference')) ?>:</strong> <?= h($successReference) ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ($errors !== []): ?>
        <div class="fp-errors">
          <ul>
            <?php foreach ($errors as $error): ?>
              <li><?= h((string)$error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?= h(base_path('forgot-password.php')) ?>" class="fp-form" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= h((string)($_SESSION['csrf_token'] ?? '')) ?>">

        <div class="fp-field">
          <label for="login_id"><?= h(__('forgot_password_login_id_label')) ?></label>
          <input
            id="login_id"
            name="login_id"
            type="text"
            required
            maxlength="150"
            value="<?= h($loginIdValue) ?>"
            placeholder="<?= h(__('forgot_password_login_id_placeholder')) ?>"
            autocomplete="username"
          >
          <div class="fp-hint"><?= h(__('forgot_password_login_id_hint')) ?></div>
        </div>

        <button type="submit" class="fp-submit" <?= $featureAvailable ? '' : 'disabled' ?>>
          <?= h(__('forgot_password_submit_btn')) ?>
        </button>
      </form>

      <div class="fp-footer">
        <a class="fp-back" href="<?= h(base_path('index.php')) ?>"><?= h(__('forgot_password_back_to_login')) ?></a>
        <div class="fp-meta">
          <?= h(app_config('system.name', 'e-Prestasi')) ?><br>
          <?= h(__('forgot_password_footer_note')) ?>
        </div>
      </div>
    </div>
  </div>

</body>
</html>
