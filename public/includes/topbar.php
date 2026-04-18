<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../setting/function.php';
require_once __DIR__ . '/../classes/Config.php';
require_once __DIR__ . '/../setting/helper/config_helper.php';
require_once __DIR__ . '/../includes/functions-db.php';

// Helper escape
if (!function_exists('h')) {
  function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

// Helper: bina URL semasa + ganti/tambah 1 query param
if (!function_exists('url_with_param')) {
  function url_with_param(string $key, string $val): string {
    $req = $_SERVER['REQUEST_URI'] ?? '/';
    $p   = parse_url($req);
    $path= $p['path'] ?? '/';
    parse_str($p['query'] ?? '', $q);
    $q[$key] = $val;
    $qs = http_build_query($q);
    return $path . ($qs ? '?'.$qs : '');
  }
}

$pdo_mysql  = Database::getInstance('mysql')->getConnection();
$user       = new User($pdo_mysql);
$f_loginID  = $_SESSION['f_loginID'] ?? null;
$f_stafID   = $_SESSION['f_stafID'] ?? null;
$profile    = [];
if ($f_loginID) {
  $profile = $user->getProfileByLoginID((string)$f_loginID) ?: [];
}
if (!$profile && $f_stafID) {
  $profile = $user->getProfile((string)$f_stafID) ?: [];
}
if (!empty($profile['f_loginID']) && empty($_SESSION['f_loginID'])) {
  $_SESSION['f_loginID'] = (string)$profile['f_loginID'];
}

$nama_pengguna     = $profile['f_nama'] ?? ($profile['f_nickname'] ?? 'Pengguna');
$peranan_pengguna  = $profile['f_groupName'] ?? 'Pengguna';
$avatarUrl         = $profile['avatar_url'] ?? $profile['avatar'] ?? base_url('assets/images/no-image.jpg');
$lang              = $_SESSION['lang'] ?? 'ms';

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrfToken = $_SESSION['csrf_token'];

$defaultGroupId = (int)($_SESSION['group_default_id'] ?? ($profile['f_groupID'] ?? 0));
if (!isset($_SESSION['group_default_id']) && $defaultGroupId > 0) {
  $_SESSION['group_default_id'] = $defaultGroupId;
}
if (!isset($_SESSION['group_active_id']) && $defaultGroupId > 0) {
  $_SESSION['group_active_id'] = $defaultGroupId;
}
$defaultGroupId = (int)($_SESSION['group_default_id'] ?? $defaultGroupId);
$activeGroupId = (int)($_SESSION['group_active_id'] ?? $defaultGroupId);

// Fetch active group name for display (do not change user default role)
if ($activeGroupId > 0 && $activeGroupId !== $defaultGroupId) {
  try {
    $stmtAct = $pdo_mysql->prepare("SELECT f_groupName FROM tbl_m_group WHERE f_groupID = :gid LIMIT 1");
    $stmtAct->execute([':gid' => $activeGroupId]);
    $rowAct = $stmtAct->fetch(PDO::FETCH_ASSOC);
    if (!empty($rowAct['f_groupName'])) {
      $peranan_pengguna = (string)$rowAct['f_groupName'];
    }
  } catch (Throwable $e) {
    // keep original label on failure
  }
}

// Allowed roles for role switcher (tbl_ref_access + tbl_m_group)
$allowedRoles = [];
$hasExtraRole = false;
try {
  $userID = (int)($profile['f_userID'] ?? $_SESSION['f_userID'] ?? 0);
  $stafID = (string)($profile['f_stafID'] ?? $_SESSION['f_stafID'] ?? '');
  $stafRaw = trim($stafID);
  $stafNorm = str_replace('-', '', $stafRaw);
  if ($userID > 0 || $stafRaw !== '' || $stafNorm !== '') {
    $stmtHas = $pdo_mysql->prepare("\n      SELECT 1\n      FROM tbl_ref_access a\n      WHERE a.f_status = 1\n        AND (\n          a.f_userID = :uid\n          OR (\n            :uid_zero = 0\n            AND (TRIM(a.f_stafID) = :staf OR REPLACE(TRIM(a.f_stafID), '-', '') = :staf_norm)\n          )\n        )\n      LIMIT 1\n    ");
    $stmtHas->execute([':uid' => $userID, ':uid_zero' => $userID, ':staf' => $stafRaw, ':staf_norm' => $stafNorm]);
    $hasExtraRole = (bool)$stmtHas->fetchColumn();

    $stmtRoles = $pdo_mysql->prepare("\n      SELECT a.f_groupID, g.f_groupKod, g.f_groupName\n      FROM tbl_ref_access a\n      JOIN tbl_m_group g ON g.f_groupID = a.f_groupID\n      WHERE a.f_status = 1\n        AND (\n          a.f_userID = :uid\n          OR (\n            :uid_zero = 0\n            AND (TRIM(a.f_stafID) = :staf OR REPLACE(TRIM(a.f_stafID), '-', '') = :staf_norm)\n          )\n        )\n      ORDER BY g.f_groupName ASC, g.f_groupKod ASC\n    ");
    $stmtRoles->execute([':uid' => $userID, ':uid_zero' => $userID, ':staf' => $stafRaw, ':staf_norm' => $stafNorm]);
    $allowedRoles = $stmtRoles->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }
} catch (Throwable $e) {
  $allowedRoles = [];
  $hasExtraRole = false;
}

// Default role label (from tbl_m_user)
$defaultGroupName = $profile['f_groupName'] ?? 'Pengguna';

// Tetapan bahasa aktif dari DB
$config = new Config($pdo_mysql);
$bahasaAktif = array_values(array_filter($config->getBahasaAktif() ?: []));
if (!$bahasaAktif) $bahasaAktif = ['ms'];

// Bendera & label
$langFlag = [
  'ms' => 'malaysia.png',
  'en' => 'united-kingdom.png',
];
$langLabel = [
  'ms' => 'Bahasa Melayu',
  'en' => 'English',
];

$topbarColor = $_SESSION['theme.topbar'] ?? 'light';
$defaultHome = app_config('site.default_home', 'pages/dashboard.php');
$topbarLogoLight = app_config('branding.topbar_logo_light', 'assets/images/logo.png');
$topbarLogoDark = app_config('branding.topbar_logo_dark', 'assets/images/logo-dark.png');
$topbarLogoSm = app_config('branding.topbar_logo_sm', 'assets/images/logo-sm.png');

$sybaseEnvironment = function_exists('get_sybase_environment') ? get_sybase_environment() : 'unknown';
$sybaseOperationalMode = function_exists('get_sybase_operational_mode') ? get_sybase_operational_mode() : 'unknown';
$sybaseStaffRuntime = function_exists('get_sybase_staff_key') ? get_sybase_staff_key() : (function_exists('get_sybase_staff_base') ? get_sybase_staff_base() : 'unknown');
$sybaseStudentRuntime = (function_exists('is_student_mode_enabled') && is_student_mode_enabled())
  ? (function_exists('get_sybase_student_key') ? get_sybase_student_key() : (function_exists('get_sybase_student_base') ? get_sybase_student_base() : 'enabled'))
  : 'Disabled';
$sybaseEnvironmentLabel = match ((string)$sybaseEnvironment) {
  'production' => 'Production',
  'development' => 'Development',
  default => ucfirst((string)$sybaseEnvironment),
};
$sybaseOperationalModeLabel = match ((string)$sybaseOperationalMode) {
  'staff_only' => 'Staff Only',
  'staff_student' => 'Staff + Student',
  default => ucfirst(str_replace('_', ' ', (string)$sybaseOperationalMode)),
};

// Flash message for role switch success
$roleSwitchFlash = $_SESSION['role_switch_success'] ?? null;
if ($roleSwitchFlash !== null) {
  unset($_SESSION['role_switch_success']);
}
?>

<!-- Global Loader -->
<!-- <div id="global-loader" aria-live="polite" aria-busy="true">
  <div class="loader-inner">
    <span
      class="spinner"
      role="status"
      aria-label="<?= htmlspecialchars(__('config_js_loading') ?: 'Loading…', ENT_QUOTES, 'UTF-8') ?>">
    </span>
  </div>
</div> -->



<!-- ========== Development Mode Banner (Overlay) ========== -->
<?php if (function_exists('is_development_mode') && is_development_mode()): ?>
  <div class="dev-mode-tab" id="dev-mode-tab" role="alert" aria-live="polite">
    <div class="dev-mode-tab__inner container-fluid">
      <div class="dev-mode-tab__headline">
        <span class="dev-mode-tab__title-wrap">
          <i class="ri-code-s-slash-line fs-16"></i>
          <span class="fw-semibold">Development Mode</span>
        </span>
        <span class="dev-mode-tab__actions">
          <span class="dev-mode-tab__env"><?= h($sybaseEnvironmentLabel) ?></span>
          <button type="button" class="dev-mode-tab__toggle" id="dev-mode-tab-toggle" aria-expanded="false" aria-controls="dev-mode-tab-details" aria-label="Expand development mode details">
            <span class="dev-mode-tab__toggle-text">+</span>
          </button>
        </span>
      </div>
      <div class="dev-mode-tab__grid" id="dev-mode-tab-details" hidden>
        <div class="dev-mode-tab__item">
          <span class="dev-mode-tab__label">Environment</span>
          <span class="dev-mode-tab__value"><?= h($sybaseEnvironmentLabel) ?></span>
        </div>
        <div class="dev-mode-tab__item">
          <span class="dev-mode-tab__label">Operational Mode</span>
          <span class="dev-mode-tab__value"><?= h($sybaseOperationalModeLabel) ?></span>
        </div>
        <div class="dev-mode-tab__item">
          <span class="dev-mode-tab__label">Sybase Staff</span>
          <span class="dev-mode-tab__value dev-mode-tab__value--mono"><?= h($sybaseStaffRuntime) ?></span>
        </div>
        <div class="dev-mode-tab__item">
          <span class="dev-mode-tab__label">Sybase Student</span>
          <span class="dev-mode-tab__value dev-mode-tab__value--mono"><?= h($sybaseStudentRuntime) ?></span>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- ========== Topbar Start ========== -->
<div id="topbar" class="navbar-custom" data-topbar-color="<?= h($topbarColor) ?>">
  <div class="topbar container-fluid">
    <div class="d-flex align-items-center gap-lg-2 gap-1">
      <!-- Logo -->
      <div class="logo-topbar">
        <a href="<?= h(base_url($defaultHome)) ?>" class="logo-light">
          <span class="logo-lg"><img src="<?= h(base_url($topbarLogoLight)) ?>" alt="logo"></span>
          <span class="logo-sm"><img src="<?= h(base_url($topbarLogoSm)) ?>" alt="small logo"></span>
        </a>
        <a href="<?= h(base_url($defaultHome)) ?>" class="logo-dark">
          <span class="logo-lg"><img src="<?= h(base_url($topbarLogoDark)) ?>" alt="dark logo"></span>
          <span class="logo-sm"><img src="<?= h(base_url($topbarLogoSm)) ?>" alt="small logo"></span>
        </a>
      </div>

      <!-- Sidebar Menu Toggle Button -->
      <button class="button-toggle-menu">
        <i class="ri-menu-2-fill"></i>
      </button>
      
    </div>

    <ul class="topbar-menu d-flex align-items-center gap-3">

      <?php
        $activeLangCount = count($bahasaAktif);
        $currentFlag  = $langFlag[$lang] ?? 'malaysia.png';
        $currentLabel = $langLabel[$lang] ?? strtoupper($lang);
      ?>

      <!-- Language -->
      <?php if ($activeLangCount <= 1): ?>
        <li class="topbar-language">
          <div class="nav-link topbar-language-toggle">
            <img src="<?= h(base_url('assets/images/flags/'.$currentFlag)) ?>" height="16" class="topbar-language-flag" alt="flag">
            <span class="d-none d-lg-inline-block topbar-language-label"><?= h($currentLabel) ?></span>
          </div>
        </li>
      <?php else: ?>
        <li class="dropdown topbar-language">
          <a class="nav-link dropdown-toggle arrow-none topbar-language-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
            <img src="<?= h(base_url('assets/images/flags/'.$currentFlag)) ?>" height="16" class="topbar-language-flag" alt="flag">
            <span class="d-none d-lg-inline-block topbar-language-label"><?= h($currentLabel) ?></span>
            <i class="ri-arrow-down-s-line d-none d-sm-inline-block align-middle topbar-language-caret"></i>
          </a>
          <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated topbar-language-menu" data-bs-auto-close="outside">
            <?php foreach ($bahasaAktif as $key): if (!isset($langLabel[$key], $langFlag[$key])) continue; ?>
              <?php $href = url_with_param('lang', $key); ?>
              <a href="<?= h($href) ?>"
                 class="dropdown-item topbar-language-item<?= ($lang === $key ? ' active fw-semibold text-primary':'') ?>"
                 data-lang-link="1">
                <img src="<?= h(base_url('assets/images/flags/'.$langFlag[$key])) ?>" height="12" class="topbar-language-item-flag" alt="">
                <span class="topbar-language-item-label"><?= h($langLabel[$key]) ?></span>
                <?= ($lang === $key) ? '<i class="ri-check-line topbar-language-item-check"></i>' : '' ?>
              </a>
            <?php endforeach; ?>
          </div>
        </li>
      <?php endif; ?>

      <!-- Notification (dummy) -->
      <li class="dropdown notification-list">
        <a class="nav-link dropdown-toggle arrow-none" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
          <i class="ri-notification-3-fill fs-22"></i><span class="noti-icon-badge"></span>
        </a>
        <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated dropdown-lg py-0" data-bs-auto-close="outside">
          <div class="p-2 border-dashed border-top-0 d-flex justify-content-between align-items-center">
            <h6 class="fs-16 fw-medium m-0">Notification</h6>
            <a href="#" class="text-dark text-decoration-underline"><small>Clear All</small></a>
          </div>
          <div style="max-height: 300px;" data-simplebar>
            <h5 class="text-muted fs-12 fw-bold p-2 text-uppercase mb-0">Today</h5>
            <a href="#" class="dropdown-item p-0 notify-item unread-noti card m-0 shadow-none">
              <div class="card-body d-flex align-items-center">
                <div class="notify-icon bg-primary me-2"><i class="ri-message-3-line fs-18"></i></div>
                <div class="flex-grow-1 text-truncate">
                  <h5 class="noti-item-title fw-medium fs-14">Contoh Notifikasi
                    <small class="float-end text-muted ms-1">1 min ago</small>
                  </h5>
                  <small class="text-muted">Notifikasi sistem e-Prestasi</small>
                </div>
              </div>
            </a>
          </div>
          <a href="#" class="dropdown-item text-center text-primary fw-bold border-top py-2">View All</a>
        </div>
      </li>

      <!-- Theme / Fullscreen -->
      <li class="d-none d-sm-inline-block">
        <a class="nav-link" data-bs-toggle="offcanvas" href="#theme-settings-offcanvas" role="button" aria-controls="theme-settings-offcanvas">
          <i class="ri-settings-3-fill fs-22"></i>
        </a>
      </li>
      <li class="d-none d-sm-inline-block"><div class="nav-link" id="light-dark-mode"><i class="ri-moon-fill fs-22" id="theme-mode-icon"></i></div></li>
      <li class="d-none d-md-inline-block"><a class="nav-link" href="#" id="toggle-fullscreen"><i class="ri-fullscreen-line fs-22"></i></a></li>

      <!-- Akaun Pengguna -->
      <li class="dropdown me-md-2">
        <a class="nav-link dropdown-toggle nav-user px-2" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
          <span class="account-user-avatar">
            <img src="<?= h($avatarUrl) ?>" width="32" class="rounded-circle"
                 onerror="this.onerror=null;this.src='<?= h(base_url('assets/images/no-image.jpg')) ?>';" alt="">
          </span>
          <span class="d-lg-flex flex-column gap-1 d-none">
            <h5 class="my-0"><?= h($nama_pengguna) ?></h5>
            <h6 class="my-0 fw-normal" id="topbarCurrentRoleLabel"><?= h($peranan_pengguna) ?></h6>
          </span>
        </a>
        <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated profile-dropdown" data-bs-auto-close="outside">
          <div class="dropdown-header noti-title profile-dropdown-header">
            <h6 class="text-overflow m-0"><?= h(__('topbar_welcome')) ?></h6>
          </div>
          <a href="<?= h(base_url('pages/profile.php')) ?>" class="dropdown-item"><i class="ri-account-circle-fill me-1"></i> Profil Saya</a>
          <?php if ($hasExtraRole): ?>
            <a href="#" class="dropdown-item" id="btnSwitchRole">
              <i class="ri-shuffle-line me-1"></i> <?= h(__('topbar_switch_role') ?? 'Tukar Peranan') ?>
            </a>
          <?php endif; ?>
          <!-- <a href="<?= h(base_url('pages/pages-settings.html')) ?>" class="dropdown-item"><i class="ri-settings-4-fill me-1"></i> Tetapan</a>
          <a href="<?= h(base_url('pages/pages-faq.html')) ?>" class="dropdown-item"><i class="ri-customer-service-2-fill me-1"></i> Sokongan</a> -->
          <!-- <a href="<?= h(base_url('pages/auth-lock-screen.html')) ?>" class="dropdown-item"><i class="ri-lock-password-fill me-1"></i> Kunci Skrin</a> -->

          <!-- Logout -->
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item text-danger" onclick="return confirmLogout(event);" data-no-loader>
            <i class="ri-logout-box-fill me-1"></i> <?= __('topbar_keluar') ?>
          </a>

        </div>
      </li>

    </ul>
  </div>
</div>
<!-- ========== Topbar End ========== -->

<!-- ========== Role Switcher Modal (Topbar) ========== -->
<div class="modal fade modal-themed" id="switchRoleModal" tabindex="-1" aria-hidden="true" aria-labelledby="switchRoleTitle">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="switchRoleTitle">
          <i class="ri-shuffle-line me-2"></i> <?= h(__('topbar_switch_role_title') ?? 'Tukar Peranan') ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(__('userList_modal_btn_close')) ?>"></button>
      </div>
      <div class="modal-body">
        <form id="switchRoleForm" autocomplete="off">
          <input type="hidden" id="sr_csrf" value="<?= h($csrfToken) ?>">

          <div class="form-section">
            <div class="form-section-title">
              <i class="ri-shield-user-line me-1"></i> <?= h(__('topbar_switch_role_select') ?? 'Pilih Peranan') ?>
            </div>

            <div class="mb-2 text-muted small" id="switchRolePrimary">
              <?= h(__('topbar_switch_role_primary_label') ?? 'Peranan utama') ?>: <strong id="switchRolePrimaryName"><?= h($defaultGroupName) ?></strong>
            </div>
            <?php if (!empty($allowedRoles) || $defaultGroupId > 0): ?>
              <div class="role-list" id="switchRoleList">
                <?php if ($defaultGroupId > 0): ?>
                  <label class="role-item">
                    <input type="radio" name="group_active_id" value="<?= h((string)$defaultGroupId) ?>" <?= ($defaultGroupId === $activeGroupId ? 'checked' : '') ?>>
                    <span class="role-label">
                      <?= h($defaultGroupName) ?> <span class="text-muted">(<?= h(__('topbar_switch_role_primary_tag') ?? 'Peranan Utama') ?>)</span>
                    </span>
                  </label>
                <?php endif; ?>
                <?php foreach ($allowedRoles as $r): 
                  $rid  = (int)($r['f_groupID'] ?? 0);
                  $rkod = (string)($r['f_groupKod'] ?? '');
                  $rnam = (string)($r['f_groupName'] ?? '');
                  if ($rid <= 0 || $rid === $defaultGroupId) continue;
                  $checked = ($rid === $activeGroupId) ? 'checked' : '';
                ?>
                  <label class="role-item">
                    <input type="radio" name="group_active_id" value="<?= h((string)$rid) ?>" <?= $checked ?>>
                    <span class="role-label"><?= h($rnam) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
              <div class="text-muted d-none" id="switchRoleEmpty"><?= h(__('topbar_switch_role_none') ?? 'Tiada peranan lain yang dibenarkan.') ?></div>
            <?php else: ?>
              <div class="text-muted" id="switchRoleEmpty"><?= h(__('topbar_switch_role_none') ?? 'Tiada peranan lain yang dibenarkan.') ?></div>
              <div class="role-list d-none" id="switchRoleList"></div>
            <?php endif; ?>
          </div>
        </form>
        <div id="switchRoleError" class="modal-error alert alert-danger d-none mt-3"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="ri-close-line me-1"></i> <?= __('userList_modal_btn_cancel') ?>
        </button>
        <button type="button" class="btn btn-primary" id="switchRoleSaveBtn" <?= (empty($allowedRoles) && $defaultGroupId <= 0) ? 'disabled' : '' ?>>
          <i class="ri-save-3-line me-1"></i> <?= __('userList_modal_btn_save') ?>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ========== Development Mode Banner CSS ========== -->
<style>
  .topbar-language-toggle {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
  }
  .topbar-language-flag {
    flex: 0 0 auto;
    border-radius: 2px;
  }
  .topbar-language-label {
    line-height: 1;
  }
  .topbar-language-caret {
    margin-left: -0.125rem;
  }
  .topbar-language-menu {
    min-width: 12.5rem;
    padding: 0.5rem;
  }
  .topbar-language-item {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    margin: 0.12rem 0.18rem;
    padding: 0.5rem 0.7rem;
    border-radius: 0.55rem;
  }
  .topbar-language-item-flag {
    flex: 0 0 auto;
    width: 18px;
    height: auto;
      background: rgba(241, 245, 249, 0.96);
    }

  /* Role Switcher Modal - align with themed modals in kumpulan-pengguna */
  #switchRoleModal {
    z-index: 11020 !important;
  }
  .modal-backdrop.show {
    z-index: 11010 !important;
  }
  #switchRoleModal,
  #switchRoleModal .modal-dialog,
  #switchRoleModal .modal-dialog-centered,
  #switchRoleModal .modal-content,
  #switchRoleModal .modal-content::before,
  #switchRoleModal .modal-content::after {
    box-shadow: none !important;
    outline: 0 !important;
    filter: none !important;
  }
  #switchRoleModal .modal-dialog {
    max-width: 700px;
    border: 0 !important;
    background: transparent !important;
  }
  #switchRoleModal .modal-content {
    border: none;
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
  }
  #switchRoleModal .modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border-bottom: none;
    padding: 1.1rem 1.35rem;
  }
  #switchRoleModal .modal-header .modal-title {
    color: #fff;
    font-weight: 600;
    font-size: 1.25rem;
    letter-spacing: 0.3px;
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }
  #switchRoleModal .modal-header .modal-title i {
    font-size: 1.5rem;
    opacity: 0.95;
  }
  #switchRoleModal .modal-header .btn-close { filter: brightness(0) invert(1); opacity: 0.9; }
  #switchRoleModal .modal-header .btn-close:hover { opacity: 1; }
  #switchRoleModal .modal-body {
    padding: 1.35rem;
    background: #fff;
  }
  #switchRoleModal .modal-footer {
    padding: 1rem 1.35rem;
    background-color: #f8f9fa;
    border-top: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 0 0 8px 8px;
  }
  #switchRoleModal .form-section {
    margin-bottom: 1.5rem;
    padding-bottom: 1.25rem;
    border-bottom: 2px solid #e9ecef;
  }
  #switchRoleModal .form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
  }
  #switchRoleModal .form-section-title {
    font-size: 0.85rem;
    font-weight: 700;
    color: #495057;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 1.25rem;
    padding-bottom: 0.625rem;
    border-bottom: 3px solid #667eea;
    display: flex;
    align-items: center;
  }
  #switchRoleModal .form-section-title i { margin-right: 0.5rem; color: #667eea; }
  #switchRoleModal .modal-footer .btn {
    padding: 0.5rem 1.15rem;
    font-weight: 600;
    border-radius: 8px;
  }
  #switchRoleModal .role-list { display: grid; gap: 0.75rem; }
  #switchRoleModal .role-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    background: #fff;
    cursor: pointer;
    transition: all 0.2s ease;
  }
  #switchRoleModal .role-item:hover {
    border-color: #667eea;
    box-shadow: 0 4px 10px rgba(102, 126, 234, 0.12);
  }
  #switchRoleModal .role-item input[type="radio"] { transform: scale(1.1); }
  #switchRoleModal .role-label { font-weight: 600; color: #212529; }

  /* Development Mode Tab - RDC-like control tab centered at top edge */
  .dev-mode-tab {
    position: fixed;
    top: 0; /* flush to top edge */
    left: 50%;
    transform: translateX(-50%);
    z-index: 10001; /* above topbar */

    min-width: 460px;
    max-width: 760px;
    width: min(760px, calc(100% - 200px));

    display: block;

    background: linear-gradient(180deg, #12264a 0%, #0b2340 100%); /* dark blue gradient */
    color: #ffffff;
    padding: 0;

    border-top-left-radius: 0;
    border-top-right-radius: 0;
    border-bottom-left-radius: 14px;
    border-bottom-right-radius: 14px;

    box-shadow: 0 10px 26px rgba(0,0,0,0.28);

    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.02em;

    pointer-events: none;
  }

  .dev-mode-tab::after {
    content: "";
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    bottom: -12px;
    width: 72%;
    height: 18px;
    background: linear-gradient(180deg, rgba(11,35,64,0.95), rgba(8,24,46,0.95));
    border-bottom-left-radius: 999px;
    border-bottom-right-radius: 999px;
    box-shadow: 0 6px 10px rgba(0,0,0,0.25);
    pointer-events: none;
  }

  .dev-mode-tab__inner {
    padding: 0.5rem 0.9rem 0.7rem;
  }
  .dev-mode-tab__headline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0;
  }
  .dev-mode-tab__actions {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    pointer-events: auto;
  }
  .dev-mode-tab__title-wrap {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
  }
  .dev-mode-tab .fw-semibold {
    color: #fff !important;
    pointer-events: none;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-size: 0.72rem;
  }
  .dev-mode-tab i {
    color: rgba(255,255,255,0.95);
    pointer-events: none;
  }
  .dev-mode-tab__env {
    display: inline-flex;
    align-items: center;
    padding: 0.18rem 0.6rem;
    border-radius: 999px;
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.16);
    color: #fff;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
  }
  .dev-mode-tab__toggle {
    width: 26px;
    height: 26px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    border: 1px solid rgba(255,255,255,0.18);
    border-radius: 999px;
    background: rgba(255,255,255,0.08);
    color: #fff;
    cursor: pointer;
    pointer-events: auto;
  }
  .dev-mode-tab__toggle:hover {
    background: rgba(255,255,255,0.14);
  }
  .dev-mode-tab__toggle-text {
    font-size: 0.95rem;
    line-height: 1;
    font-weight: 700;
  }
  .dev-mode-tab__grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.45rem;
    margin-top: 0.45rem;
  }
  .dev-mode-tab__grid[hidden] {
    display: none;
  }
  .dev-mode-tab:not(.is-expanded) {
    min-width: 390px;
    max-width: 560px;
  }
  .dev-mode-tab:not(.is-expanded)::after {
    width: 58%;
    height: 14px;
    bottom: -10px;
  }
  .dev-mode-tab:not(.is-expanded) .dev-mode-tab__inner {
    padding-bottom: 0.7rem;
  }
  .dev-mode-tab__item {
    display: flex;
    flex-direction: column;
    gap: 0.18rem;
    min-width: 0;
    padding: 0.45rem 0.55rem;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 8px;
  }
  .dev-mode-tab__label {
    color: rgba(255,255,255,0.72);
    font-size: 0.58rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
  }
  .dev-mode-tab__value {
    color: #ffffff;
    font-size: 0.72rem;
    font-weight: 700;
    line-height: 1.3;
    word-break: break-word;
  }
  .dev-mode-tab__value--mono {
    font-family: Consolas, "Courier New", monospace;
    font-size: 0.68rem;
  }

  @media (max-width: 991.98px) {
    .dev-mode-tab {
      min-width: 0;
      width: calc(100% - 24px);
      max-width: 680px;
    }
    .dev-mode-tab__grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  @media (max-width: 575.98px) {
    .dev-mode-tab__inner {
      padding: 0.55rem 0.75rem 0.8rem;
    }
    .dev-mode-tab__headline {
      gap: 0.45rem;
    }
    .dev-mode-tab__grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<!-- ========== Topbar JS (kalis block) ========== -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const devModeTab = document.getElementById('dev-mode-tab');
    const devModeToggle = document.getElementById('dev-mode-tab-toggle');
    const devModeDetails = document.getElementById('dev-mode-tab-details');
    const devModeToggleText = devModeToggle?.querySelector('.dev-mode-tab__toggle-text');

    if (!devModeTab || !devModeToggle || !devModeDetails || !devModeToggleText) return;

    const storageKey = 'dev-mode-tab-expanded';
    const setExpanded = (expanded) => {
      devModeTab.classList.toggle('is-expanded', expanded);
      devModeDetails.hidden = !expanded;
      devModeToggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      devModeToggle.setAttribute('aria-label', expanded ? 'Minimize development mode details' : 'Expand development mode details');
      devModeToggleText.textContent = expanded ? '-' : '+';
      try {
        localStorage.setItem(storageKey, expanded ? '1' : '0');
      } catch (e) {
        // ignore storage failure
      }
    };

    let expanded = false;
    try {
      expanded = localStorage.getItem(storageKey) === '1';
    } catch (e) {
      expanded = false;
    }
    setExpanded(expanded);

    devModeToggle.addEventListener('click', function () {
      setExpanded(!devModeTab.classList.contains('is-expanded'));
    });
  });

  // Paksa toggle dropdown + auto-tutup dropdown lain dalam #topbar
  document.addEventListener('click', function (e) {
    const toggle = e.target.closest('#topbar [data-bs-toggle="dropdown"]');
      if (!toggle) return;

      e.preventDefault();
      e.stopImmediatePropagation();

      const thisLi = toggle.closest('.dropdown');

      // Tutup SEMUA dropdown lain yang sedang terbuka
      document.querySelectorAll('#topbar .dropdown-menu.show').forEach(menu => {
        const li = menu.closest('.dropdown');
        if (!thisLi || li !== thisLi) {
          const otherT = li?.querySelector('[data-bs-toggle="dropdown"]');
          if (otherT) bootstrap.Dropdown.getOrCreateInstance(otherT).hide();
          else menu.classList.remove('show'); // fallback
        }
      });

      // Toggle dropdown yang diklik
      const dd   = bootstrap.Dropdown.getOrCreateInstance(toggle);
      const menu = thisLi?.querySelector('.dropdown-menu');
      (menu && menu.classList.contains('show')) ? dd.hide() : dd.show();
    }, { capture: true });
</script>

<!-- ========== Role Switcher JS ========== -->
<script>
  (function(){
    let btn = null;
    let modalEl = null;
    let saveBtn = null;
    let errEl = null;
    let modal = null;
    let csrf = '';
    let listEl = null;
    let emptyEl = null;
    let primaryNameEl = null;
    const successTitleTpl = <?= json_encode(__('topbar_switch_role_success_title') ?? 'Peranan {role}', JSON_UNESCAPED_UNICODE) ?>;
    const successTextTpl = <?= json_encode(__('topbar_switch_role_success_text') ?? 'Paparan dan akses sistem telah dikemas kini mengikut pilihan peranan baru iaitu <strong>{role}</strong>.', JSON_UNESCAPED_UNICODE) ?>;
    const fallbackRedirectUrl = <?= json_encode(base_url(app_config('site.default_home', 'pages/dashboard.php')), JSON_UNESCAPED_UNICODE) ?>;

    function showRoleSwitchSuccess(roleName){
      const safeRole = String(roleName || 'peranan').trim() || 'peranan';
      const title = String(successTitleTpl || '').replace('{role}', safeRole);
      const html = String(successTextTpl || '').replace('{role}', safeRole);
      if (window.Swal && typeof Swal.fire === 'function') {
        Swal.fire({
          icon: 'success',
          title: title,
          html: html,
          confirmButtonText: 'OK',
          confirmButtonColor: '#198754'
        });
      }
    }

    function initRoleSwitch(){
      btn = document.getElementById('btnSwitchRole');
      modalEl = document.getElementById('switchRoleModal');
      saveBtn = document.getElementById('switchRoleSaveBtn');
      errEl = document.getElementById('switchRoleError');
      listEl = document.getElementById('switchRoleList');
      emptyEl = document.getElementById('switchRoleEmpty');
      primaryNameEl = document.getElementById('switchRolePrimaryName');
      if (!btn || !modalEl || !saveBtn || !window.bootstrap || !bootstrap.Modal) return;
      csrf = document.getElementById('sr_csrf')?.value || '';
      modal = bootstrap.Modal.getOrCreateInstance(modalEl);

      // Clean up duplicate backdrops and modal state
      function cleanupBackdrops(){
        const backs = document.querySelectorAll('.modal-backdrop');
        if (backs.length <= 1) return;
        for (let i = 0; i < backs.length - 1; i++) {
          backs[i].parentNode && backs[i].parentNode.removeChild(backs[i]);
        }
      }
      function cleanupModalState(){
        cleanupBackdrops();
        const anyOpen = document.querySelectorAll('.modal.show').length > 0;
        if (!anyOpen) {
          document.body.classList.remove('modal-open');
          document.body.style.removeProperty('overflow');
          document.body.style.removeProperty('padding-right');
        }
      }
      modalEl.addEventListener('shown.bs.modal', cleanupBackdrops);
      modalEl.addEventListener('hidden.bs.modal', cleanupModalState);

      btn.addEventListener('click', async function(e){
        e.preventDefault();
        hideErr();
        await loadRoleOptions();
        modal.show();
      });

      saveBtn.addEventListener('click', async function(){
        hideErr();
        const selected = modalEl.querySelector('input[name=\"group_active_id\"]:checked');
      if (!selected) {
        showErr('<?= h(__('topbar_switch_role_err_select') ?? 'Sila pilih peranan.') ?>');
        return;
      }
      const groupID = parseInt(selected.value || '0', 10);
      if (!groupID) {
        showErr('<?= h(__('topbar_switch_role_err_invalid') ?? 'Sila pilih peranan yang sah.') ?>');
        return;
      }

        saveBtn.disabled = true;
        const originalHtml = saveBtn.innerHTML;
      saveBtn.innerHTML = '<i class=\"ri-loader-4-line ri-spin me-1\"></i> <?= h(__('topbar_switch_role_saving') ?? 'Menyimpan...') ?>';
        try {
          const runRoleSwitch = async () => {
            const r = await fetch('<?= h(base_url('ajax/role-switch.php')) ?>', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': csrf
              },
              body: JSON.stringify({
                groupID,
                currentPath: window.AccessUiSync && typeof window.AccessUiSync.inferCurrentPagePath === 'function'
                  ? window.AccessUiSync.inferCurrentPagePath()
                  : ''
              })
            });
            const text = await r.text();
            let j = null;
            try { j = JSON.parse(text); } catch (e) {}
            if (!r.ok || !j || j.error) {
              throw new Error((j && j.message) || 'Gagal menukar peranan.');
            }
            modal.hide();

            const roleName = String(j.group_name || '').trim();
            if (window.AccessUiSync && typeof window.AccessUiSync.applyAccessState === 'function') {
              const result = await window.AccessUiSync.applyAccessState(j, {
                refreshSidebar: true,
                redirectOnDenied: true,
                onRedirect: ({ redirectUrl }) => {
                  window.location.href = String(redirectUrl || fallbackRedirectUrl || window.location.href);
                }
              });
              if (result && result.redirected) {
                return;
              }
            } else {
              window.location.href = String(j.redirect_url || fallbackRedirectUrl || window.location.href);
              return;
            }
            showRoleSwitchSuccess(roleName || selected.closest('.role-item')?.textContent || 'peranan');
          };

          if (window.AccessUiSync && typeof window.AccessUiSync.runExclusive === 'function') {
            await window.AccessUiSync.runExclusive(runRoleSwitch);
          } else {
            await runRoleSwitch();
          }
        } catch (e) {
          showErr(e.message || 'Gagal menukar peranan.');
        } finally {
          saveBtn.disabled = false;
          saveBtn.innerHTML = originalHtml;
        }
      });
    }

    function showErr(msg){
      if (!errEl) return;
      errEl.textContent = msg || 'Ralat tidak diketahui.';
      errEl.classList.remove('d-none');
    }
    function hideErr(){
      if (!errEl) return;
      errEl.classList.add('d-none');
    }

    async function loadRoleOptions(){
      if (!listEl || !emptyEl) return;
      try {
        const r = await fetch('<?= h(base_url('ajax/role-switch-roles.php')) ?>', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-Token': csrf },
          body: JSON.stringify({ action: 'list' })
        });
        const text = await r.text();
        let j = null;
        try { j = JSON.parse(text); } catch (e) {}
        if (!r.ok || !j || j.error) {
          return;
        }

        const defaultId = parseInt(j.default?.id || 0, 10);
        const defaultName = String(j.default?.name || '').trim();
        const activeId = parseInt(j.active_id || 0, 10);
        const roles = Array.isArray(j.roles) ? j.roles : [];

        if (primaryNameEl && defaultName) {
          primaryNameEl.textContent = defaultName;
        }

        listEl.innerHTML = '';
        const primaryTag = '<?= h(__('topbar_switch_role_primary_tag') ?? 'Peranan Utama') ?>';
        function addRole(id, name, isPrimary, checked){
          const label = document.createElement('label');
          label.className = 'role-item';
          const input = document.createElement('input');
          input.type = 'radio';
          input.name = 'group_active_id';
          input.value = String(id);
          if (checked) input.checked = true;
          const span = document.createElement('span');
          span.className = 'role-label';
          span.textContent = name || '';
          if (isPrimary) {
            const tag = document.createElement('span');
            tag.className = 'text-muted';
            tag.textContent = ` (${primaryTag})`;
            span.appendChild(tag);
          }
          label.appendChild(input);
          label.appendChild(span);
          listEl.appendChild(label);
        }

        if (defaultId > 0) {
          addRole(defaultId, defaultName || '<?= h(__('topbar_switch_role_primary_label') ?? 'Peranan utama') ?>', true, activeId ? activeId === defaultId : true);
        }
        roles.forEach(r => {
          const rid = parseInt(r.id || r.f_groupID || 0, 10);
          const rname = String(r.name || r.f_groupName || '').trim();
          if (!rid || rid === defaultId) return;
          addRole(rid, rname, false, activeId === rid);
        });

        const hasAny = listEl.querySelectorAll('input[name="group_active_id"]').length > 0;
        if (!hasAny) {
          emptyEl.classList.remove('d-none');
          listEl.classList.add('d-none');
        } else {
          emptyEl.classList.add('d-none');
          listEl.classList.remove('d-none');
        }
        saveBtn.disabled = !hasAny;
      } catch (e) {
        // silent fallback to existing server-rendered list
      }
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initRoleSwitch);
    } else {
      initRoleSwitch();
    }
  })();
</script>

<?php if (!empty($roleSwitchFlash) && is_array($roleSwitchFlash)): 
  $roleName = trim((string)($roleSwitchFlash['group_name'] ?? ''));
?>
<script>
  (function(){
    const roleName = <?= json_encode($roleName, JSON_UNESCAPED_UNICODE) ?> || 'peranan';
    const titleTpl = <?= json_encode(__('topbar_switch_role_success_title') ?? 'Peranan {role}', JSON_UNESCAPED_UNICODE) ?>;
    const textTpl = <?= json_encode(__('topbar_switch_role_success_text') ?? 'Paparan dan akses sistem telah dikemas kini mengikut pilihan peranan baru iaitu <strong>{role}</strong>.', JSON_UNESCAPED_UNICODE) ?>;
    const title = String(titleTpl).replace('{role}', roleName);
    const html = String(textTpl).replace('{role}', roleName);
    function showMsg(){
      if (window.Swal) {
        Swal.fire({
          icon: 'success',
          title: title,
          html: html,
          confirmButtonText: 'OK',
          confirmButtonColor: '#198754'
        });
      } else {
        alert(title + '\n' + html.replace(/<[^>]+>/g, ''));
      }
    }
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', showMsg);
    } else {
      showMsg();
    }
  })();
</script>
<?php endif; ?>

<!-- ========== TOGGLE BUTTON ========== -->
<script>
  (function(){
    const KEY = 'sidenav-size';                  // simpan state
    const html = document.documentElement;
    const body = document.body;
    const safeStorage = {
      get(k){ try { return localStorage.getItem(k); } catch(e){ return null; } },
      set(k,v){ try { localStorage.setItem(k,v); return true; } catch(e){ return false; } }
    };

    // Apply saved state on load (ikut layout asal)
    document.addEventListener('DOMContentLoaded', () => {
      const saved = safeStorage.get(KEY);
      if (saved === 'condensed' || saved === 'default') {
        html.setAttribute('data-sidenav-size', saved);
        body.setAttribute('data-sidebar-size', saved); // compat
      }
    });

    // Toggle via button template asal
    document.addEventListener('click', function(e){
      const btn = e.target.closest('.button-toggle-menu');
      if (!btn) return;
      e.preventDefault();
      e.stopImmediatePropagation();

      const curr = html.getAttribute('data-sidenav-size') || 'default';
      const next = (curr === 'condensed') ? 'default' : 'condensed';

      html.setAttribute('data-sidenav-size', next);
      body.setAttribute('data-sidebar-size', next); // compat lama
      safeStorage.set(KEY, next);

      // Optional: tutup dropdown yang terbuka supaya UI tak pelik
      document.querySelectorAll('#topbar .dropdown-menu.show').forEach(m => m.classList.remove('show'));

      // Optional: trigger resize untuk chart/table
      window.dispatchEvent(new Event('resize'));
    }, { capture: true });
  })();
</script>
