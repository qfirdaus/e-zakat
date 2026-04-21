<?php
declare(strict_types=1);

define('BDR_NOTIFICATION_CONFIRM_PUBLIC_PAGE', true);
define('ALLOW_ANON_AJAX', true);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../controllers/BdrDistanceController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

$controller = new BdrDistanceController(true);
$token = trim((string)($_GET['token'] ?? ''));
$pageData = $controller->buildSelfConfirmPageData($token);
$isLoggedIn = !empty($_SESSION['f_stafID']);
$systemStaffNo = trim((string)($_SESSION['f_stafID'] ?? ''));
$selfConfirmIdentity = is_array($_SESSION['bdr_self_confirm_sso_identity'] ?? null)
    ? $_SESSION['bdr_self_confirm_sso_identity']
    : [];
$selfConfirmStaffNo = trim((string)($selfConfirmIdentity['staff_no'] ?? ''));
$selfConfirmToken = trim((string)($selfConfirmIdentity['token'] ?? ''));
$isSelfConfirmSsoVerified = $selfConfirmStaffNo !== '' && $selfConfirmToken !== '' && hash_equals($token, $selfConfirmToken);
$currentStaffNo = $isLoggedIn ? $systemStaffNo : ($isSelfConfirmSsoVerified ? $selfConfirmStaffNo : '');
$isAuthenticatedForConfirm = $isLoggedIn || $isSelfConfirmSsoVerified;
$status = (string)($pageData['status'] ?? 'invalid_token');
$requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($status === 'ready' && !$isAuthenticatedForConfirm) {
    $selfConfirmReturnPath = current_path();
    $_SESSION['auth_post_login_redirect'] = $selfConfirmReturnPath;
    $_SESSION['bdr_self_confirm_sso'] = [
        'active' => true,
        'token' => $token,
        'return_path' => $selfConfirmReturnPath,
        'issued_at' => time(),
    ];
} elseif ($status !== 'ready') {
    unset($_SESSION['bdr_self_confirm_sso']);
    unset($_SESSION['bdr_self_confirm_sso_identity']);
}

if ($status === 'ready' && $isAuthenticatedForConfirm && $currentStaffNo !== trim((string)($pageData['staff_no'] ?? ''))) {
    $status = 'staff_mismatch';
}

if ($requestMethod === 'POST' && $status === 'ready' && $isAuthenticatedForConfirm) {
    $requestCsrf = trim((string)($_POST['csrf_token'] ?? ''));
    $declared = !empty($_POST['confirm_declaration']);
    if ($requestCsrf === '' || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), $requestCsrf)) {
        $status = 'csrf_invalid';
    } else {
        $pageData = $controller->submitSelfConfirmToken(
            $token,
            $currentStaffNo,
            (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
            $declared
        );
        $status = (string)($pageData['status'] ?? 'invalid_token');
        if ($status === 'confirmed') {
            unset($_SESSION['bdr_self_confirm_sso']);
            unset($_SESSION['bdr_self_confirm_sso_identity']);
        }
    }
}

$version = (string)($_ENV['APP_ASSET_VER'] ?? date('ymdHis'));
$row = is_array($pageData['row'] ?? null) ? $pageData['row'] : [];
$issueLabel = trim((string)($pageData['issue_label'] ?? ''));
$issueLabels = is_array($pageData['issue_labels'] ?? null) ? array_values(array_filter(array_map('strval', $pageData['issue_labels']))) : [];
$staffNo = trim((string)($pageData['staff_no'] ?? $row['staff_no'] ?? ''));
$addressText = trim((string)($row['mailing_address'] ?? ''));
$distanceLabel = trim((string)($row['distance_label'] ?? ''));
$detectedState = trim((string)($row['detected_state'] ?? ''));
$officeLabel = trim((string)($pageData['office_label'] ?? $row['office_label'] ?? (function_exists('distance_office_label') ? distance_office_label() : 'UPNM')));
$officeAddress = trim((string)($pageData['office_address'] ?? $row['office_address'] ?? ''));
$expiresAt = trim((string)($pageData['expires_at'] ?? ''));
$declarationText = trim((string)($pageData['declaration_text'] ?? ''));
$confirmedAt = trim((string)($pageData['confirmed_at'] ?? ''));
$loginUrl = base_url('sso_sp_client.php');
$closeFallbackUrl = base_url('index.php');
$canConfirm = $status === 'ready' && $isAuthenticatedForConfirm;
$needsLogin = $status === 'ready' && !$isAuthenticatedForConfirm;
$showRecordDetails = !in_array($status, ['invalid_token', 'expired', 'already_confirmed', 'confirmed'], true);

if (!function_exists('h')) {
    function h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

function tr_page(string $key, string $fallback): string
{
    $translated = __($key);
    return ($translated === $key || $translated === null || $translated === '') ? $fallback : (string)$translated;
}

function destination_page_text(string $text, string $officeLabel): string
{
    $officeLabel = trim($officeLabel) !== '' ? trim($officeLabel) : 'UPNM';
    if (str_contains($text, '{{office_label}}')) {
        return str_replace('{{office_label}}', $officeLabel, $text);
    }

    return str_replace('UPNM', $officeLabel, $text);
}

function format_confirm_datetime(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    try {
        return (new DateTimeImmutable($value))->format('d/m/Y H:i:s');
    } catch (Throwable $e) {
        return $value;
    }
}

$statusMap = [
    'invalid_token' => [
        'title' => tr_page('bdr_distance_self_confirm_invalid_title', 'Pautan Tidak Sah'),
        'text' => tr_page('bdr_distance_self_confirm_invalid_text', 'Pautan pengesahan ini tidak sah atau tidak lagi tersedia.'),
        'alert' => 'danger',
    ],
    'expired' => [
        'title' => tr_page('bdr_distance_self_confirm_expired_title', 'Pautan Tamat Tempoh'),
        'text' => tr_page('bdr_distance_self_confirm_expired_text', 'Pautan pengesahan ini telah tamat tempoh. Sila rujuk emel notifikasi terkini atau hubungi pentadbir.'),
        'alert' => 'warning',
    ],
    'already_confirmed' => [
        'title' => tr_page('bdr_distance_self_confirm_already_title', 'Pautan Telah Digunakan'),
        'text' => tr_page('bdr_distance_self_confirm_already_text', 'Pautan ini telah tamat kerana pengesahan alamat telah dilaksanakan.'),
        'alert' => 'success',
    ],
    'not_allowed' => [
        'title' => tr_page('bdr_distance_self_confirm_not_allowed_title', 'Pengesahan Tidak Dibenarkan'),
        'text' => tr_page('bdr_distance_self_confirm_not_allowed_text', 'Isu ini tidak dibenarkan untuk pengesahan sendiri.'),
        'alert' => 'warning',
    ],
    'issue_resolved' => [
        'title' => tr_page('bdr_distance_self_confirm_resolved_title', 'Pengesahan Tidak Diperlukan'),
        'text' => tr_page('bdr_distance_self_confirm_resolved_text', 'Rekod ini tidak lagi memerlukan pengesahan sendiri untuk isu tersebut.'),
        'alert' => 'info',
    ],
    'staff_mismatch' => [
        'title' => tr_page('bdr_distance_self_confirm_staff_mismatch_title', 'Akses Tidak Dibenarkan'),
        'text' => tr_page('bdr_distance_self_confirm_staff_mismatch_text', 'Akaun SSO yang sedang log masuk tidak sepadan dengan rekod pengesahan ini.'),
        'alert' => 'danger',
    ],
    'declaration_required' => [
        'title' => tr_page('bdr_distance_self_confirm_declaration_required_title', 'Pengesahan Belum Lengkap'),
        'text' => tr_page('bdr_distance_self_confirm_declaration_required_text', 'Sila tandakan pengakuan sebelum menghantar pengesahan.'),
        'alert' => 'warning',
    ],
    'csrf_invalid' => [
        'title' => tr_page('bdr_distance_self_confirm_csrf_title', 'Sesi Tidak Sah'),
        'text' => tr_page('bdr_distance_self_confirm_csrf_text', 'Sesi anda tidak sah. Sila buka semula pautan daripada emel notifikasi.'),
        'alert' => 'danger',
    ],
    'confirmed' => [
        'title' => tr_page('bdr_distance_self_confirm_success_title', 'Pengesahan Berjaya'),
        'text' => tr_page('bdr_distance_self_confirm_success_text', 'Pengesahan alamat berjaya disimpan. Anda tidak lagi akan menerima notifikasi bagi isu ini untuk alamat semasa yang sama.'),
        'alert' => 'success',
    ],
    'ready' => [
        'title' => tr_page('bdr_distance_self_confirm_ready_title', 'Pengesahan Alamat'),
        'text' => tr_page('bdr_distance_self_confirm_ready_text', 'Sila semak maklumat berikut dan sahkan jika alamat ini benar-benar digunakan untuk berulang-alik bekerja.'),
        'alert' => 'primary',
    ],
];

$meta = $statusMap[$status] ?? $statusMap['invalid_token'];
$introText = $needsLogin
    ? tr_page('bdr_distance_self_confirm_login_required_text', 'Sila semak maklumat berikut. Untuk meneruskan pengesahan alamat, sila log masuk menggunakan OneID UPNM terlebih dahulu.')
    : (string)$meta['text'];
?>
<!DOCTYPE html>
<html lang="<?= h((string)($_SESSION['lang'] ?? 'ms')) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h(tr_page('bdr_distance_self_confirm_page_title', 'Pengesahan Alamat BDR')) ?></title>
  <link rel="icon" href="<?= h(base_url(app_config('site.favicon', 'assets/images/default.ico'))) ?>" type="image/x-icon">
  <link id="app-style" rel="stylesheet" href="<?= h(base_url('assets/css/app.css')) ?>?v=<?= h($version) ?>">
  <script src="<?= h(base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js')) ?>"></script>
  <style>
    body { background: radial-gradient(circle at top left, rgba(20, 184, 166, .18), transparent 34rem), linear-gradient(145deg, #f8fafc 0%, #e2e8f0 100%); min-height: 100vh; }
    .confirm-shell { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }
    .confirm-card { width: 100%; max-width: 820px; border: 0; box-shadow: 0 24px 70px rgba(15, 23, 42, 0.16); border-radius: 1.35rem; overflow: hidden; }
    .confirm-header { background: linear-gradient(135deg, #0f766e 0%, #115e59 55%, #164e63 100%); color: #fff; padding: 1.35rem 1.6rem; }
    .confirm-body { padding: 1.6rem; background: #fff; }
    .confirm-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
    .confirm-summary { border: 1px solid #dbe7f3; border-radius: 1rem; padding: 1rem; background: linear-gradient(180deg, #f8fbff 0%, #f1f7ff 100%); }
    .confirm-label { font-size: .78rem; font-weight: 800; letter-spacing: .04em; color: #64748b; text-transform: uppercase; margin-bottom: .3rem; }
    .confirm-value { color: #0f172a; font-weight: 600; }
    .confirm-address { white-space: pre-wrap; }
    .confirm-actions { margin-top: 1.25rem; display: flex; gap: .75rem; align-items: center; justify-content: flex-end; flex-wrap: wrap; }
    .confirm-declaration { border: 1px solid #cbd5e1; border-radius: 1rem; padding: 1rem 1rem 1rem 2.75rem; background: #f8fafc; }
    .confirm-declaration .form-check-input { margin-left: -1.75rem; }
    .confirm-close-fallback { display: none; }
    @media (max-width: 575.98px) {
      .confirm-actions { justify-content: stretch; }
      .confirm-actions .btn { width: 100%; }
    }
  </style>
</head>
<body>
  <div class="confirm-shell">
    <div class="card confirm-card">
      <div class="confirm-header">
        <h3 class="mb-1"><?= h($meta['title']) ?></h3>
        <div class="small opacity-75"><?= h(tr_page('bdr_distance_self_confirm_header_sub', 'Pengesahan alamat notifikasi BDR melalui OneID UPNM')) ?></div>
      </div>
      <div class="confirm-body">
        <div class="alert alert-<?= h((string)$meta['alert']) ?> mb-4"><?= h($introText) ?></div>

        <?php if ($showRecordDetails && ($staffNo !== '' || $addressText !== '' || $issueLabel !== '')): ?>
          <div class="confirm-summary confirm-grid mb-4">
            <div>
              <div class="confirm-label"><?= h(tr_page('bdr_distance_field_staff_no', 'No. Staf')) ?></div>
              <div class="confirm-value"><?= h($staffNo !== '' ? $staffNo : '-') ?></div>
            </div>
            <div>
              <div class="confirm-label"><?= h(tr_page('bdr_distance_self_confirm_issue_label', 'Isu')) ?></div>
              <div class="confirm-value">
                <?php if ($issueLabels !== []): ?>
                  <ul class="mb-0 ps-3">
                    <?php foreach ($issueLabels as $label): ?>
                      <li><?= h($label) ?></li>
                    <?php endforeach; ?>
                  </ul>
                <?php else: ?>
                  <?= h($issueLabel !== '' ? $issueLabel : '-') ?>
                <?php endif; ?>
              </div>
            </div>
            <div>
              <div class="confirm-label"><?= h(tr_page('bdr_distance_field_office_location', 'Lokasi Kerja')) ?></div>
              <div class="confirm-value"><?= h($officeLabel !== '' ? $officeLabel : '-') ?></div>
            </div>
            <div>
              <div class="confirm-label"><?= h(destination_page_text(tr_page('bdr_distance_field_distance', 'Jarak ke UPNM'), $officeLabel)) ?></div>
              <div class="confirm-value"><?= h($distanceLabel !== '' ? $distanceLabel : '-') ?></div>
            </div>
            <div>
              <div class="confirm-label"><?= h(tr_page('bdr_distance_email_reason_detected_state', 'Negeri dikesan:')) ?></div>
              <div class="confirm-value"><?= h($detectedState !== '' ? $detectedState : '-') ?></div>
            </div>
            <div class="w-100" style="grid-column: 1 / -1;">
              <div class="confirm-label"><?= h(tr_page('bdr_distance_field_staff_input_address', 'Alamat Input Staf')) ?></div>
              <div class="confirm-value confirm-address"><?= h($addressText !== '' ? $addressText : '-') ?></div>
            </div>
            <?php if ($officeAddress !== ''): ?>
            <div class="w-100" style="grid-column: 1 / -1;">
              <div class="confirm-label"><?= h(tr_page('bdr_distance_field_office_address', 'Alamat Lokasi Kerja')) ?></div>
              <div class="confirm-value confirm-address"><?= h($officeAddress) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($expiresAt !== ''): ?>
            <div>
              <div class="confirm-label"><?= h(tr_page('bdr_distance_self_confirm_expires_at', 'Token sah sehingga')) ?></div>
              <div class="confirm-value"><?= h(format_confirm_datetime($expiresAt)) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($confirmedAt !== ''): ?>
            <div>
              <div class="confirm-label"><?= h(tr_page('bdr_distance_self_confirm_confirmed_at', 'Disahkan pada')) ?></div>
              <div class="confirm-value"><?= h($confirmedAt) ?></div>
            </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($needsLogin): ?>
          <div class="confirm-actions">
            <a href="<?= h($loginUrl) ?>" class="btn btn-success px-4">
              <?= h(tr_page('bdr_distance_self_confirm_login_oneid', 'Login OneID')) ?>
            </a>
          </div>
        <?php elseif ($canConfirm): ?>
          <form method="post" action="<?= h(base_url('pages/bdr-notification-confirm.php?token=' . rawurlencode($token))) ?>">
            <input type="hidden" name="csrf_token" value="<?= h((string)($_SESSION['csrf_token'] ?? '')) ?>">
            <div class="form-check confirm-declaration">
              <input class="form-check-input" type="checkbox" value="1" id="confirmDeclaration" name="confirm_declaration" required>
              <label class="form-check-label fw-semibold" for="confirmDeclaration">
                <?= h($declarationText) ?>
              </label>
            </div>
            <div class="confirm-actions">
              <button type="submit" class="btn btn-success px-4" id="confirmSubmitBtn" disabled>
                <?= h(tr_page('bdr_distance_self_confirm_submit', 'Sahkan Alamat Ini')) ?>
              </button>
            </div>
          </form>
        <?php elseif ($status !== 'confirmed' && $status !== 'already_confirmed' && $status !== 'expired' && $status !== 'invalid_token'): ?>
          <div class="confirm-actions">
            <a href="<?= h('https://imap.upnm.edu.my') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary">
              <?= h(tr_page('bdr_distance_self_confirm_imap', 'Buka iMAP')) ?>
            </a>
          </div>
        <?php else: ?>
          <div class="confirm-actions">
            <button type="button" class="btn btn-outline-secondary <?= $status === 'confirmed' ? 'confirm-close-fallback' : '' ?>" id="closePageBtn">
              <?= h(tr_page('bdr_distance_self_confirm_close_page', 'Tutup Halaman')) ?>
            </button>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <script>
  document.addEventListener('DOMContentLoaded', function () {
    const declaration = document.getElementById('confirmDeclaration');
    const submitBtn = document.getElementById('confirmSubmitBtn');
    if (declaration && submitBtn) {
      const syncSubmit = function () { submitBtn.disabled = !declaration.checked; };
      declaration.addEventListener('change', syncSubmit);
      syncSubmit();
    }

    const closeFallbackUrl = <?= json_encode($closeFallbackUrl) ?>;
    const closeCurrentPage = function () {
      window.open('', '_self');
      window.close();
      setTimeout(function () {
        if (!document.hidden) {
          window.location.replace(closeFallbackUrl);
        }
      }, 350);
    };

    const closeBtn = document.getElementById('closePageBtn');
    if (closeBtn) {
      closeBtn.addEventListener('click', closeCurrentPage);
    }

    <?php if ($status === 'confirmed'): ?>
    const closeAfterConfirm = function () {
      closeCurrentPage();
      setTimeout(function () {
        if (closeBtn) closeBtn.style.display = 'inline-flex';
      }, 450);
    };
    if (window.Swal && typeof window.Swal.fire === 'function') {
      window.Swal.fire({
        icon: 'success',
        title: <?= json_encode(tr_page('bdr_distance_self_confirm_success_title', 'Pengesahan Berjaya')) ?>,
        text: <?= json_encode(tr_page('bdr_distance_self_confirm_success_text', 'Pengesahan alamat berjaya disimpan. Anda tidak lagi akan menerima notifikasi bagi isu ini untuk alamat semasa yang sama.')) ?>,
        confirmButtonText: <?= json_encode(tr_page('config_js_btn_ok', 'OK')) ?>,
        allowOutsideClick: false,
        allowEscapeKey: false
      }).then(closeAfterConfirm);
    } else {
      alert(<?= json_encode(tr_page('bdr_distance_self_confirm_success_text', 'Pengesahan alamat berjaya disimpan. Anda tidak lagi akan menerima notifikasi bagi isu ini untuk alamat semasa yang sama.')) ?>);
      closeAfterConfirm();
    }
    <?php endif; ?>
  });
  </script>
</body>
</html>
