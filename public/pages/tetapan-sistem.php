<?php
// pages/tetapan-sistem.php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_login();

/* ================= Authorization Check ================= */
// Akses halaman dikawal melalui menu & kumpulan pengguna (tiada semakan role di page)

/**
 * Ã¢Å¡Â Ã¯Â¸Â JANGAN tutup session sebelum controller proses POST.
 * Jika nak lepaskan lock, buat HANYA untuk GET:
 *
 * if ($_SERVER['REQUEST_METHOD'] === 'GET' && session_status() === PHP_SESSION_ACTIVE) session_write_close();
 */

/* ================= CSRF Protection ================= */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$PAGE_TITLE = (string)(__('config_system') ?? 'Konfigurasi Sistem');

/* ================= Controller & data ================= */
require_once __DIR__ . '/../controllers/TetapanSistemController.php';
require_once __DIR__ . '/../classes/SystemConfigConstants.php';
$controller = new TetapanSistemController();
$controller->handleRequest(); // Handle POST requests

$lang     = $controller->lang;
$profile  = $controller->profile;
$version  = (string)($_ENV['APP_ASSET_VER'] ?? date('ymdHis'));
$viewData = $controller->getPageViewData((($_GET['tab'] ?? '') === 'lang'));

$dbAktif = is_array($viewData['dbAktif'] ?? null) ? $viewData['dbAktif'] : [];
$mysqlInfo = is_array($viewData['mysqlInfo'] ?? null) ? $viewData['mysqlInfo'] : [];
$emailSettings = is_array($viewData['emailSettings'] ?? null) ? $viewData['emailSettings'] : [];
$generalSettings = is_array($viewData['generalSettings'] ?? null) ? $viewData['generalSettings'] : [];
$authSettings = is_array($viewData['authSettings'] ?? null) ? $viewData['authSettings'] : [];
$languageData = is_array($viewData['languageData'] ?? null) ? $viewData['languageData'] : [];
$dbRuntime = is_array($viewData['dbRuntime'] ?? null) ? $viewData['dbRuntime'] : [];
$additionalConnections = is_array($viewData['additionalConnections'] ?? null) ? $viewData['additionalConnections'] : [];
$themeSettings = is_array($viewData['themeSettings'] ?? null) ? $viewData['themeSettings'] : [];
$sidebarSmallImages = is_array($viewData['sidebarSmallImages'] ?? null) ? $viewData['sidebarSmallImages'] : [];
$systemVersion = app_current_version();

$senaraiBahasa = $languageData['list']   ?? [];
$bahasaAktif   = $languageData['active'] ?? [];
$bahasaDefault = $languageData['default'] ?? ($bahasaAktif[0] ?? SystemConfigConstants::DEFAULT_LANGUAGE);

$dbRenderEnvironment = (string)($dbRuntime['dbRenderEnvironment'] ?? SystemConfigConstants::DEFAULT_SYBASE_ENVIRONMENT);
$dbRenderOperationalMode = (string)($dbRuntime['dbRenderOperationalMode'] ?? SystemConfigConstants::DEFAULT_SYBASE_OPERATIONAL_MODE);
$activeLogical = (string)($dbRuntime['activeLogical'] ?? 'ehrmdb');
$activeBase = (string)($dbRuntime['activeBase'] ?? 'sybase_ehrmdb');
$runtimeStaffBase = (string)($dbRuntime['runtimeStaffBase'] ?? 'sybase_staff_prod');
$runtimeStudentBase = (string)($dbRuntime['runtimeStudentBase'] ?? 'sybase_student_prod');
$studentRuntimeLabel = (string)($dbRuntime['studentRuntimeLabel'] ?? (__('config_tab_db_runtime_disabled') ?? 'Disabled'));
$mainMysqlEnvironment = (string)($dbRuntime['mainMysqlEnvironment'] ?? SystemConfigConstants::DEFAULT_MAIN_DB_ENVIRONMENT);
$mysqlDriver = (string)($dbRuntime['mysqlDriver'] ?? 'mysql');
$mysqlDsn = (string)($dbRuntime['mysqlDsn'] ?? '');
$mysqlUser = (string)($dbRuntime['mysqlUser'] ?? '-');
$mysqlHost = (string)($dbRuntime['mysqlHost'] ?? '-');
$mysqlDatabase = (string)($dbRuntime['mysqlDatabase'] ?? '-');
$topbar = (string)($themeSettings['topbarColor'] ?? SystemConfigConstants::DEFAULT_THEME_TOPBAR);
$sidebar = (string)($themeSettings['sidebarColor'] ?? SystemConfigConstants::DEFAULT_THEME_SIDEBAR);
$layout = (string)($themeSettings['layoutMode'] ?? SystemConfigConstants::DEFAULT_THEME_LAYOUT);

$tetapanSistemJsKeys = [
  'config_alert_no',
  'config_js_berjaya',
  'config_js_btn_loading_save',
  'config_js_btn_ok',
  'config_js_btn_ya_simpan',
  'config_js_btn_ya_teruskan',
  'config_tab_auth',
  'config_auth_summary_warnings',
  'config_auth_summary_status_ok',
  'config_auth_summary_status_invalid_note',
  'config_auth_status_valid',
  'config_auth_status_warning',
  'config_auth_status_invalid',
  'config_auth_summary_maintenance_on',
  'config_auth_summary_maintenance_off',
  'config_auth_summary_staff_enabled',
  'config_auth_summary_staff_disabled',
  'config_auth_summary_student_enabled',
  'config_auth_summary_student_disabled',
  'config_auth_summary_public_enabled',
  'config_auth_summary_public_disabled',
  'config_auth_summary_sso_enabled',
  'config_auth_summary_sso_disabled',
  'config_auth_summary_staff_auto_provision_enabled',
  'config_auth_summary_staff_auto_provision_disabled',
  'config_auth_summary_student_auto_provision_enabled',
  'config_auth_summary_student_auto_provision_disabled',
  'config_auth_warning_sso_disabled_mode',
  'config_auth_warning_all_categories_blocked',
  'config_auth_warning_staff_auto_provision_group_missing',
  'config_auth_warning_student_auto_provision_group_missing',
  'config_auth_warning_staff_auto_provision_category_disabled',
  'config_auth_warning_student_auto_provision_category_disabled',
  'config_auth_warning_staff_auto_provision_route_manual',
  'config_auth_warning_student_auto_provision_route_manual',
  'config_auth_sso_mode_all_note',
  'config_auth_sso_mode_manual_note',
  'config_auth_sso_mode_hybrid_note',
  'config_auth_enabled',
  'config_auth_disabled',
  'config_auth_allowed',
  'config_auth_blocked',
  'config_auth_sso_mode_all',
  'config_auth_sso_mode_manual',
  'config_auth_sso_mode_hybrid',
  'config_js_confirm_bahasa',
  'config_js_confirm_db',
  'config_js_confirm_emel',
  'config_js_confirm_general',
  'config_js_confirm_auth',
  'config_js_confirm_tema',
  'config_js_confirm_uji_emel',
  'config_js_emel_berjaya',
  'config_js_emel_gagal',
  'config_js_input_uji_emel',
  'config_js_label_uji_emel',
  'config_js_pilih_bahasa',
  'config_js_pilih_bahasa_default',
  'config_js_placeholder_uji_emel',
  'config_js_ralat',
  'config_js_ralat_sistem',
  'config_js_tiada_bahasa',
  'config_js_tiada_bahasa_default',
  'config_js_uji_emel_btn',
  'config_js_uji_emel_btn_default',
  'config_js_uji_emel_btn_loading',
  'config_js_valid_email_format',
  'config_js_valid_email_full',
  'config_js_valid_emel_kosong',
  'config_js_invalid_input',
  'config_js_field_fallback_label',
  'config_js_invalid_server_response',
  'config_js_module_not_ready',
  'config_js_save_failed',
  'config_js_save_success_default',
  'config_js_save_system_error',
  'config_js_system_error_title',
  'config_js_valid_host_format',
  'config_js_valid_port_range',
];

$langFileForJs = __DIR__ . "/../lang/{$lang}.php";
if (is_file($langFileForJs)) {
  $langMapForJs = require $langFileForJs;
  if (is_array($langMapForJs)) {
    $translations_js = array_merge(
      $translations_js ?? [],
      array_intersect_key($langMapForJs, array_flip($tetapanSistemJsKeys))
    );
  }
}

?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>" data-bs-theme="<?= htmlspecialchars($_SESSION['theme.layout'] ?? 'light', ENT_QUOTES, 'UTF-8') ?>">

<head>
  <?php
    // Matikan plugin berat untuk page ni (kalau head.php guna flags)
    $NEED_DATERANGE  = false;
    $NEED_VECTORMAP  = false;
    $NEED_DATATABLES = false;
    $NEED_SELECT2    = false;
    include __DIR__ . '/../includes/head.php';
  ?>
  <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

  <link rel="stylesheet" href="<?= asset_url('css/datatables-standard.css') ?>?v=<?= urlencode($version) ?>">
  <link rel="stylesheet" href="<?= asset_url('css/pages/tetapan-sistem.css') ?>?v=<?= urlencode($version) ?>">

  <!-- Translation map (senyap) -->
  <script>
    window.__translations = <?= json_encode($translations_js ?? [], JSON_UNESCAPED_UNICODE) ?>;
    window.__ = function (key) {
      var dict = window.__translations || {};
      if (Object.prototype.hasOwnProperty.call(dict, key)) {
        var val = dict[key];
        return (val && val !== '') ? val : key;
      }
      return key;
    };
  </script>
</head>

<body id="body-layout"
      data-topbar-color="<?= htmlspecialchars($_SESSION['theme.topbar'] ?? 'light', ENT_QUOTES, 'UTF-8') ?>"
      data-menu-color="<?= htmlspecialchars($_SESSION['theme.menu']   ?? 'light', ENT_QUOTES, 'UTF-8') ?>"
      data-layout="vertical"
      data-sidebar-size="default">

  <div class="wrapper">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="content-page">
      <div class="content">
        <div class="container-fluid">

          <!-- Tajuk & Breadcrumb -->
          <div class="row mb-3">
            <div class="col-12">
              <div class="page-title-box d-flex justify-content-between align-items-center flex-wrap">
                <h4 class="page-title"><i class="ri-settings-3-line me-1"></i> <?= __('config_system') ?? 'Konfigurasi Sistem' ?></h4>
                <div class="page-title-right">
                  <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="dashboard.php"><i class="ri-home-4-line align-middle me-1"></i> <?= __('breadcrumb_home') ?? 'Home' ?></a></li>
                    <li class="breadcrumb-item active">
                      <i class="ri-settings-3-line align-middle me-1"></i> <?= __('config_system') ?? 'Konfigurasi Sistem' ?>
                    </li>
                  </ol>
                </div>
              </div>
            </div>
          </div>

          <!-- Tab Navigasi -->
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <ul class="nav nav-tabs flex-grow-1" role="tablist">
              <li class="nav-item">
                <a class="nav-link <?= (($_GET['tab'] ?? '') === 'general' || !isset($_GET['tab'])) ? 'active' : '' ?>" data-bs-toggle="tab" href="#general-tab" role="tab" aria-selected="<?= (($_GET['tab'] ?? '') === 'general' || !isset($_GET['tab'])) ? 'true' : 'false' ?>">
                  <i class="ri-settings-3-line me-1"></i> <?= __('config_tab_general') ?? 'Umum' ?>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?= ($_GET['tab'] ?? '') === 'auth' ? 'active' : '' ?>" data-bs-toggle="tab" href="#auth-tab" role="tab" aria-selected="<?= ($_GET['tab'] ?? '') === 'auth' ? 'true' : 'false' ?>">
                  <i class="ri-shield-keyhole-line me-1"></i> <?= __('config_tab_auth') ?? 'Login Policy' ?>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?= ($_GET['tab'] ?? '') === 'email' ? 'active' : '' ?>" data-bs-toggle="tab" href="#email-tab" role="tab" aria-selected="<?= ($_GET['tab'] ?? '') === 'email' ? 'true' : 'false' ?>">
                  <i class="ri-mail-settings-line me-1"></i> <?= __('config_tab_emel') ?? 'Emel' ?>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?= ($_GET['tab'] ?? '') === 'db' ? 'active' : '' ?>" data-bs-toggle="tab" href="#db-tab" role="tab" aria-selected="<?= ($_GET['tab'] ?? '') === 'db' ? 'true' : 'false' ?>">
                  <i class="ri-database-2-line me-1"></i> <?= __('config_tab_db') ?? 'Pangkalan Data' ?>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?= ($_GET['tab'] ?? '') === 'theme' ? 'active' : '' ?>" data-bs-toggle="tab" href="#theme-tab" role="tab" aria-selected="<?= ($_GET['tab'] ?? '') === 'theme' ? 'true' : 'false' ?>">
                  <i class="ri-palette-line me-1"></i> <?= __('config_tab_tema') ?? 'Tema' ?>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?= ($_GET['tab'] ?? '') === 'lang' ? 'active' : '' ?>" data-bs-toggle="tab" href="#lang-tab" role="tab" aria-selected="<?= ($_GET['tab'] ?? '') === 'lang' ? 'true' : 'false' ?>">
                  <i class="ri-translate-2 me-1"></i> <?= __('config_tab_bahasa') ?? 'Bahasa' ?>
                </a>
              </li>
            </ul>
            <div class="ms-auto">
              <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fw-semibold"><?= h(app_current_version_label()) ?></span>
            </div>
          </div>

          <!-- Kandungan Tab -->
          <div class="tab-content pt-3">

            <?php include __DIR__ . '/partials/tetapan-sistem/tab-general.php'; ?>

            <?php include __DIR__ . '/partials/tetapan-sistem/tab-login-policy.php'; ?>

            <?php include __DIR__ . '/partials/tetapan-sistem/tab-email.php'; ?>

            <?php include __DIR__ . '/partials/tetapan-sistem/tab-database.php'; ?>

            <?php include __DIR__ . '/partials/tetapan-sistem/tab-theme.php'; ?>

            <?php include __DIR__ . '/partials/tetapan-sistem/tab-language.php'; ?>

          </div><!-- /tab-content -->
        </div>
      </div>

      <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
  </div>

  <?php
    // Flags JS vendor - hanya yang perlu
    $NEED_JQUERY     = true;
    $NEED_SWEETALERT = true;
    $NEED_DT_JS      = false;
    $NEED_SELECT2_JS = false;
    include __DIR__ . '/../includes/script.php';
  ?>


  <script>
    window.__tetapanShowGeneralSubtab = function (paneId, trigger, event) {
      if (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
      }

      var pane = document.getElementById(paneId);
      if (!pane) {
        return false;
      }

      var nav = trigger ? trigger.closest('.general-subtabs') : document.querySelector('.general-subtabs');
      if (nav) {
        nav.querySelectorAll('.nav-link').forEach(function (item) {
          item.classList.remove('active');
          item.setAttribute('aria-selected', 'false');
        });
      }

      var container = pane.parentElement;
      if (container && container.classList.contains('tab-content')) {
        container.querySelectorAll(':scope > .tab-pane').forEach(function (item) {
          item.classList.remove('show', 'active');
        });
      }

      if (trigger) {
        trigger.classList.add('active');
        trigger.setAttribute('aria-selected', 'true');
      }

      pane.classList.add('show', 'active');

      try {
        window.sessionStorage.setItem('tetapan-sistem.general-subtab', paneId);
      } catch (storageError) {
        // ignore
      }

      return false;
    };

    window.__tetapanShowAuthSubtab = function (paneId, trigger, event) {
      if (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
      }

      var pane = document.getElementById(paneId);
      if (!pane) {
        return false;
      }

      var nav = trigger ? trigger.closest('.auth-subtabs') : document.querySelector('.auth-subtabs');
      if (nav) {
        nav.querySelectorAll('.nav-link').forEach(function (item) {
          item.classList.remove('active');
          item.setAttribute('aria-selected', 'false');
        });
      }

      var container = pane.parentElement;
      if (container && container.classList.contains('tab-content')) {
        container.querySelectorAll(':scope > .tab-pane').forEach(function (item) {
          item.classList.remove('show', 'active');
        });
      }

      if (trigger) {
        trigger.classList.add('active');
        trigger.setAttribute('aria-selected', 'true');
      }

      pane.classList.add('show', 'active');

      if (typeof window.__tetapanSyncAuthPolicyUi === 'function') {
        window.__tetapanSyncAuthPolicyUi();
      } else if (typeof window.__tetapanRefreshAuthPolicySummary === 'function') {
        window.__tetapanRefreshAuthPolicySummary();
      }

      try {
        window.sessionStorage.setItem('tetapan-sistem.auth-subtab', paneId);
      } catch (storageError) {
        // ignore
      }

      return false;
    };

    window.__tetapanSyncAuthPolicyUi = function () {
      var __ = window.__ || function (key) { return key; };
      var maintenanceInput = document.getElementById('auth_maintenance_mode');
      var staffInput = document.getElementById('auth_login_enable_staf');
      var studentInput = document.getElementById('auth_login_enable_pelajar');
      var publicInput = document.getElementById('auth_login_enable_umum');
      var ssoEnabledInput = document.getElementById('auth_sso_enabled');
      var ssoModeInput = document.getElementById('auth_sso_mode');
      var staffAutoProvisionInput = document.getElementById('auth_auto_provision_staf_sso');
      var studentAutoProvisionInput = document.getElementById('auth_auto_provision_pelajar_sso');
      var staffDefaultGroupInput = document.getElementById('auth_default_group_staff_code');
      var studentDefaultGroupInput = document.getElementById('auth_default_group_student_code');
      var staffHybridInput = document.getElementById('auth_sso_hybrid_staf');
      var studentHybridInput = document.getElementById('auth_sso_hybrid_pelajar');

      if (!maintenanceInput || !staffInput || !studentInput || !publicInput || !ssoEnabledInput || !ssoModeInput || !staffAutoProvisionInput || !studentAutoProvisionInput || !staffDefaultGroupInput || !studentDefaultGroupInput) {
        return;
      }

      var maintenanceOn = !!maintenanceInput.checked;
      var staffEnabled = !!staffInput.checked;
      var studentEnabled = !!studentInput.checked;
      var publicEnabled = !!publicInput.checked;
      var ssoEnabled = !!ssoEnabledInput.checked;
      var ssoMode = String(ssoModeInput.value || 'MANUAL').toUpperCase();
      var staffAutoProvision = !!staffAutoProvisionInput.checked;
      var studentAutoProvision = !!studentAutoProvisionInput.checked;
      var staffDefaultGroup = String((staffDefaultGroupInput.value || '').trim()).toUpperCase();
      var studentDefaultGroup = String((studentDefaultGroupInput.value || '').trim()).toUpperCase();
      var staffHybridMode = staffHybridInput ? String(staffHybridInput.value || 'SSO').toUpperCase() : 'SSO';
      var studentHybridMode = studentHybridInput ? String(studentHybridInput.value || 'SSO').toUpperCase() : 'SSO';
      var warnings = [];
      var staffLoginMethod = 'MANUAL';
      var studentLoginMethod = 'MANUAL';

      if (ssoEnabled) {
        if (ssoMode === 'ALL') {
          staffLoginMethod = 'SSO';
          studentLoginMethod = 'SSO';
        } else if (ssoMode === 'HYBRID') {
          staffLoginMethod = staffHybridMode === 'SSO' ? 'SSO' : 'MANUAL';
          studentLoginMethod = studentHybridMode === 'SSO' ? 'SSO' : 'MANUAL';
        }
      }

      function setBadgeState(element, active, activeText, inactiveText, activeClass, inactiveClass) {
        if (!element) {
          return;
        }
        element.className = 'badge bg-' + (active ? activeClass : inactiveClass) + '-subtle text-' + (active ? activeClass : inactiveClass);
        element.textContent = active ? activeText : inactiveText;
      }

      function renderListItems(target, items) {
        if (!target) {
          return;
        }
        target.innerHTML = '';
        (items || []).forEach(function (item) {
          var li = document.createElement('li');
          li.textContent = item;
          target.appendChild(li);
        });
      }

      setBadgeState(document.getElementById('auth-maintenance-state'), maintenanceOn, __('config_auth_enabled') || 'Enabled', __('config_auth_disabled') || 'Disabled', 'danger', 'secondary');
      setBadgeState(document.getElementById('auth-category-state-auth_login_enable_staf'), staffEnabled, __('config_auth_allowed') || 'Allowed', __('config_auth_blocked') || 'Blocked', 'success', 'secondary');
      setBadgeState(document.getElementById('auth-category-state-auth_login_enable_pelajar'), studentEnabled, __('config_auth_allowed') || 'Allowed', __('config_auth_blocked') || 'Blocked', 'success', 'secondary');
      setBadgeState(document.getElementById('auth-category-state-auth_login_enable_umum'), publicEnabled, __('config_auth_allowed') || 'Allowed', __('config_auth_blocked') || 'Blocked', 'success', 'secondary');
      setBadgeState(document.getElementById('auth-sso-enabled-state'), ssoEnabled, __('config_auth_enabled') || 'Enabled', __('config_auth_disabled') || 'Disabled', 'success', 'secondary');
      setBadgeState(document.getElementById('auth-auto-provision-state-staff'), staffAutoProvision, __('config_auth_enabled') || 'Enabled', __('config_auth_disabled') || 'Disabled', 'success', 'secondary');
      setBadgeState(document.getElementById('auth-auto-provision-state-student'), studentAutoProvision, __('config_auth_enabled') || 'Enabled', __('config_auth_disabled') || 'Disabled', 'success', 'secondary');

      var modeNote = document.getElementById('auth-sso-mode-note');
      if (modeNote) {
        if (ssoMode === 'ALL') {
          modeNote.innerHTML = '<i class="ri-information-line me-1"></i>' + ((__('config_auth_sso_mode_all_note')) || 'In ALL mode, Staff and Student users must use SSO. Public users may still log in manually.');
        } else if (ssoMode === 'HYBRID') {
          modeNote.innerHTML = '<i class="ri-information-line me-1"></i>' + ((__('config_auth_sso_mode_hybrid_note')) || 'In HYBRID mode, each category follows its own configured login method.');
        } else {
          modeNote.innerHTML = '<i class="ri-information-line me-1"></i>' + ((__('config_auth_sso_mode_manual_note')) || 'In MANUAL mode, all allowed categories use manual login.');
        }
      }

      var hybridBlock = document.getElementById('auth-hybrid-block');
      if (hybridBlock) {
        hybridBlock.classList.toggle('auth-hybrid-block-muted', ssoMode !== 'HYBRID');
      }

      var effectiveSummary = [
        maintenanceOn
          ? (__('config_auth_summary_maintenance_on') || 'Maintenance mode is enabled. Only Super Admin can log in.')
          : (__('config_auth_summary_maintenance_off') || 'Maintenance mode is disabled. Normal policy evaluation applies.'),
        staffEnabled
          ? (__('config_auth_summary_staff_enabled') || 'Staff login is enabled.')
          : (__('config_auth_summary_staff_disabled') || 'Staff login is disabled.'),
        studentEnabled
          ? (__('config_auth_summary_student_enabled') || 'Student login is enabled.')
          : (__('config_auth_summary_student_disabled') || 'Student login is disabled.'),
        publicEnabled
          ? (__('config_auth_summary_public_enabled') || 'Public login is enabled.')
          : (__('config_auth_summary_public_disabled') || 'Public login is disabled.'),
        ssoEnabled
          ? ((__('config_auth_summary_sso_enabled') || 'SSO is enabled in %s mode.').replace('%s', ssoMode))
          : (__('config_auth_summary_sso_disabled') || 'SSO is disabled. All allowed categories use manual login.'),
        staffAutoProvision
          ? ((__('config_auth_summary_staff_auto_provision_enabled') || 'Staff SSO auto provision is enabled with default group %s.').replace('%s', staffDefaultGroup || 'ADM-STAF'))
          : (__('config_auth_summary_staff_auto_provision_disabled') || 'Staff SSO auto provision is disabled.'),
        studentAutoProvision
          ? ((__('config_auth_summary_student_auto_provision_enabled') || 'Student SSO auto provision is enabled with default group %s.').replace('%s', studentDefaultGroup || 'ADM-STUDENT'))
          : (__('config_auth_summary_student_auto_provision_disabled') || 'Student SSO auto provision is disabled.')
      ];

      if (!ssoEnabled && ssoMode !== 'MANUAL') {
        warnings.push((__('config_auth_warning_sso_disabled_mode')) || 'SSO mode is configured but SSO is currently disabled.');
      }
      if (!staffEnabled && !studentEnabled && !publicEnabled) {
        warnings.push((__('config_auth_warning_all_categories_blocked')) || 'All login categories are blocked. Only Super Admin will remain able to log in.');
      }
      if (staffAutoProvision && !staffDefaultGroup) {
        warnings.push((__('config_auth_warning_staff_auto_provision_group_missing')) || 'Staff SSO auto provision is enabled but the default staff group code is empty.');
      }
      if (studentAutoProvision && !studentDefaultGroup) {
        warnings.push((__('config_auth_warning_student_auto_provision_group_missing')) || 'Student SSO auto provision is enabled but the default student group code is empty.');
      }
      if (staffAutoProvision && !staffEnabled) {
        warnings.push((__('config_auth_warning_staff_auto_provision_category_disabled')) || 'Staff SSO auto provision is enabled while staff login is disabled.');
      }
      if (studentAutoProvision && !studentEnabled) {
        warnings.push((__('config_auth_warning_student_auto_provision_category_disabled')) || 'Student SSO auto provision is enabled while student login is disabled.');
      }
      if (staffAutoProvision && staffLoginMethod !== 'SSO') {
        warnings.push((__('config_auth_warning_staff_auto_provision_route_manual')) || 'Staff SSO auto provision is enabled but the current staff login route is not SSO.');
      }
      if (studentAutoProvision && studentLoginMethod !== 'SSO') {
        warnings.push((__('config_auth_warning_student_auto_provision_route_manual')) || 'Student SSO auto provision is enabled but the current student login route is not SSO.');
      }

      renderListItems(document.getElementById('auth-summary-effective-list'), effectiveSummary);
      renderListItems(document.getElementById('auth-summary-warning-list'), warnings);

      var warningBox = document.getElementById('auth-summary-warning-box');
      if (warningBox) {
        warningBox.classList.toggle('d-none', warnings.length === 0);
      }

      var hasServerError = !!document.querySelector('#form-auth-aktif .auth-summary-box-error');
      var statusBadge = document.getElementById('auth-summary-status-badge');
      var statusText = document.getElementById('auth-summary-status-text');
      if (!hasServerError) {
        var hasWarnings = warnings.length > 0;
        if (statusBadge) {
          statusBadge.className = 'badge bg-' + (hasWarnings ? 'warning' : 'success') + '-subtle text-' + (hasWarnings ? 'warning' : 'success') + ' px-3 py-2';
          statusBadge.textContent = hasWarnings
            ? (__('config_auth_status_warning') || 'Valid with Warning')
            : (__('config_auth_status_valid') || 'Valid');
        }
        if (statusText) {
          statusText.className = (hasWarnings ? 'text-warning' : 'text-success') + ' small fw-semibold';
          statusText.textContent = hasWarnings
            ? ((__('config_auth_summary_warnings')) || 'Warnings') + ': ' + warnings[0]
            : (__('config_auth_summary_status_ok') || 'Policy snapshot is ready for runtime use.');
        }
      }
    };

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.__tetapanSyncAuthPolicyUi === 'function') {
          window.__tetapanSyncAuthPolicyUi();
        }
      });
    } else if (typeof window.__tetapanSyncAuthPolicyUi === 'function') {
      window.__tetapanSyncAuthPolicyUi();
    }

    window.__tetapanAjaxSubmit = function (event, form, buttonId, guardName) {
      function showInlineError(message) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
          window.Swal.fire({
            icon: 'error',
            title: (window.__ && window.__('config_js_system_error_title')) || 'Ralat Sistem',
            text: message || ((window.__ && window.__('config_js_module_not_ready')) || 'Modul tetapan sistem belum siap dimuatkan. Sila cuba semula.'),
            confirmButtonText: (window.__ && window.__('config_js_btn_ok')) || 'OK'
          });
        }
      }

      function inlineSetButtonLoading(button, loading) {
        if (!button) {
          return;
        }

        if (loading) {
          button.disabled = true;
          if (!button.dataset.originalHtml) {
            button.dataset.originalHtml = button.innerHTML;
          }
          button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> ' + (((window.__ && window.__('config_js_btn_loading_save')) || 'Saving...'));
          return;
        }

        button.disabled = false;
        if (button.dataset.originalHtml) {
          button.innerHTML = button.dataset.originalHtml;
          delete button.dataset.originalHtml;
        }
      }

      function inlineLanguageGuard(activeForm) {
        if (!activeForm) {
          return false;
        }
        var checked = activeForm.querySelectorAll('input[name="languages[]"]:checked');
        if (checked.length === 0) {
          if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
              icon: 'warning',
              title: (window.__ && window.__('config_js_tiada_bahasa')) || 'No Language Selected',
              text: (window.__ && window.__('config_js_pilih_bahasa')) || 'Please select at least one language.',
              confirmButtonText: (window.__ && window.__('config_js_btn_ok')) || 'OK'
            });
          }
          return false;
        }

        var defaultLang = activeForm.querySelector('input[name="default_language"]:checked');
        if (!defaultLang) {
          if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
              icon: 'warning',
              title: (window.__ && window.__('config_js_tiada_bahasa_default')) || 'No Default Language Selected',
              text: (window.__ && window.__('config_js_pilih_bahasa_default')) || 'Please select one default language from the active languages list.',
              confirmButtonText: (window.__ && window.__('config_js_btn_ok')) || 'OK'
            });
          }
          return false;
        }

        return true;
      }

      function inlineFallbackAjaxSubmit(targetForm, button) {
        if (!targetForm) {
          showInlineError();
          return false;
        }

        if (typeof targetForm.checkValidity === 'function' && !targetForm.checkValidity()) {
          if (typeof targetForm.reportValidity === 'function') {
            targetForm.reportValidity();
          }
          return false;
        }

        inlineSetButtonLoading(button, true);

        var formData = new FormData(targetForm);
        formData.set('ajax', '1');

        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

        fetch(targetForm.getAttribute('action') || window.location.href, {
          method: 'POST',
          body: formData,
          noLoader: true,
          headers: Object.assign({
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-No-Loader': '1'
          }, csrfToken ? { 'X-CSRF-Token': csrfToken } : {})
          })
            .then(function (response) {
              if (!response.ok) {
                console.warn('[tetapan-sistem] ajax submit non-ok response', {
                  status: response.status,
                  url: targetForm.getAttribute('action') || window.location.href
                });
              }
              return response.json().catch(function () {
                throw new Error(((window.__ && window.__('config_js_invalid_server_response')) || 'Respons pelayan tidak sah.'));
              });
          })
          .then(function (payload) {
            if (!payload || payload.success !== true) {
              showInlineError((payload && payload.message) || (((window.__ && window.__('config_js_save_failed')) || 'Gagal menyimpan tetapan.')));
              return;
            }

            if (window.Swal && typeof window.Swal.fire === 'function') {
              window.Swal.fire({
                icon: 'success',
                title: payload.title || (((window.__ && window.__('config_js_berjaya')) || 'Berjaya')),
                text: payload.message || (((window.__ && window.__('config_js_save_success_default')) || 'Tetapan berjaya disimpan.')),
                confirmButtonText: (window.__ && window.__('config_js_btn_ok')) || 'OK'
              });
            }
            })
            .catch(function (error) {
              console.warn('[tetapan-sistem] ajax submit failed', error);
              showInlineError((error && error.message) || (((window.__ && window.__('config_js_save_system_error')) || 'Ralat sistem semasa menyimpan tetapan.')));
            })
          .finally(function () {
            inlineSetButtonLoading(button, false);
          });

        return false;
      }

      if (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
      }

      var targetForm = typeof form === 'string' ? document.getElementById(form) : form;
      if (!targetForm) {
        return false;
      }

      if (guardName === 'language' && typeof window.__tetapanBeforeLanguageSubmit === 'function') {
        if (window.__tetapanBeforeLanguageSubmit(targetForm) === false) {
          return false;
        }
      } else if (guardName === 'language' && inlineLanguageGuard(targetForm) === false) {
        return false;
      }

      var button = buttonId ? document.getElementById(buttonId) : null;
      var submitHandler = (typeof window.__tetapanSubmitFormWithValidation === 'function')
        ? window.__tetapanSubmitFormWithValidation
        : inlineFallbackAjaxSubmit;

      if (guardName === 'auth' && window.Swal && typeof window.Swal.fire === 'function') {
        window.Swal.fire({
          icon: 'question',
          title: (window.__ && window.__('config_tab_auth')) || 'Login Policy',
          text: (window.__ && window.__('config_js_confirm_auth')) || 'Are you sure you want to save this login policy?',
          showCancelButton: true,
          confirmButtonText: (window.__ && window.__('config_js_btn_ya_simpan')) || 'Yes, Save',
          cancelButtonText: (window.__ && window.__('config_alert_no')) || 'Cancel'
        }).then(function (result) {
          if (!result.isConfirmed) {
            return;
          }
          submitHandler(targetForm, button);
        });
        return false;
      }

      if (typeof window.__tetapanSubmitFormWithValidation === 'function') {
        submitHandler(targetForm, button);
        return false;
      }

      return submitHandler(targetForm, button);
    };

    window.__tetapanOpenEmailTest = function (event) {
      function showInlineError(message) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
          window.Swal.fire({
            icon: 'error',
            title: (window.__ && window.__('config_js_system_error_title')) || 'Ralat Sistem',
            text: message || ((window.__ && window.__('config_js_module_not_ready')) || 'Modul tetapan sistem belum siap dimuatkan. Sila cuba semula.'),
            confirmButtonText: (window.__ && window.__('config_js_btn_ok')) || 'OK'
          });
        }
      }

      function inlineEmailTest() {
        var form = document.getElementById('form-emel-aktif');
        var btnUji = document.getElementById('btn-uji-emel');
        if (!form || !btnUji || !(window.Swal && typeof window.Swal.fire === 'function')) {
          showInlineError();
          return false;
        }

        var baseUrl = (window.tetapanSistemConfig && window.tetapanSistemConfig.baseUrl) || '';
        var mailFrom = form.querySelector('input[name="mail_from_address"]') ? form.querySelector('input[name="mail_from_address"]').value : '';
        var mailUsername = form.querySelector('input[name="mail_username"]') ? form.querySelector('input[name="mail_username"]').value : '';
        var defaultEmail = mailFrom || mailUsername || '';

        window.Swal.fire({
          title: (window.__ && window.__('config_js_input_uji_emel')) || 'Enter Test Email',
          input: 'email',
          inputLabel: (window.__ && window.__('config_js_label_uji_emel')) || 'Email address for test delivery',
          inputValue: defaultEmail,
          inputPlaceholder: (window.__ && window.__('config_js_placeholder_uji_emel')) || 'e.g.: apps_email@upnm.edu.my',
          showCancelButton: true,
          confirmButtonText: (window.__ && window.__('config_js_uji_emel_btn')) || 'Test Now',
          cancelButtonText: (window.__ && window.__('config_alert_no')) || 'Cancel',
          preConfirm: function (email) {
            if (!email) {
              window.Swal.showValidationMessage((window.__ && window.__('config_js_valid_emel_kosong')) || 'Email address cannot be empty');
              return false;
            }
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
              window.Swal.showValidationMessage((window.__ && window.__('config_js_valid_email_full')) || 'Invalid email format. Please enter a valid email address.');
              return false;
            }
            return email;
          }
        }).then(function (result) {
          if (!result.isConfirmed) {
            return;
          }

          var formData = new FormData(form);
          formData.append('uji_email', result.value);
          var csrfMeta = document.querySelector('meta[name="csrf-token"]');
          var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
          formData.append('csrf_token', csrfToken);

          btnUji.disabled = true;
          if (!btnUji.dataset.originalHtml) {
            btnUji.dataset.originalHtml = btnUji.innerHTML;
          }
          btnUji.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> ' + (((window.__ && window.__('config_js_uji_emel_btn_loading')) || 'Testing...'));

            fetch(baseUrl + 'ajax/uji-emel.php', {
              method: 'POST',
              body: formData,
              noLoader: true,
              headers: Object.assign({
                'X-No-Loader': '1'
              }, csrfToken ? { 'X-CSRF-Token': csrfToken } : {})
            })
              .then(function (res) {
                if (!res.ok) {
                  console.warn('[tetapan-sistem] uji-emel non-ok response', {
                    status: res.status,
                    url: baseUrl + 'ajax/uji-emel.php'
                  });
                }
                return res.json();
              })
            .then(function (data) {
              if (data && data.success) {
                window.Swal.fire({
                  icon: 'success',
                  title: ((window.__ && window.__('config_js_berjaya')) || 'Berjaya'),
                  html: data.message || ((window.__ && window.__('config_js_emel_berjaya')) || 'Emel berjaya dihantar.')
                });
                return;
              }

              window.Swal.fire({
                icon: 'error',
                title: ((window.__ && window.__('config_js_ralat')) || 'Ralat'),
                text: (data && data.message) || ((window.__ && window.__('config_js_emel_gagal')) || 'Gagal hantar emel.')
              });
            })
              .catch(function (error) {
                console.warn('[tetapan-sistem] uji-emel request failed', error);
                window.Swal.fire({
                  icon: 'error',
                  title: ((window.__ && window.__('config_js_ralat')) || 'Ralat'),
                text: ((window.__ && window.__('config_js_ralat_sistem')) || 'Ralat sistem semasa menguji sambungan.')
              });
            })
            .finally(function () {
              btnUji.disabled = false;
              btnUji.innerHTML = btnUji.dataset.originalHtml || '<i class="ri-mail-send-line me-1"></i> ' + (((window.__ && window.__('config_js_uji_emel_btn_default')) || 'Uji Sambungan Emel'));
            });
        });

        return false;
      }

      if (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
      }

      if (typeof window.__tetapanHandleEmailTest === 'function') {
        window.__tetapanHandleEmailTest();
        return false;
      }

      return inlineEmailTest();
    };

    window.tetapanSistemConfig = {
      baseUrl: <?= json_encode(rtrim(base_url(), '/') . '/', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
      csrfToken: <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
      initialDbSelection: {
        main_db_environment: <?= json_encode($mainMysqlEnvironment, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        sybase_environment: <?= json_encode($dbRenderEnvironment, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        sybase_operational_mode: <?= json_encode($dbRenderOperationalMode, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
      },
      additionalConnections: <?= json_encode($additionalConnections, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    };
  </script>
  <script>
    (function () {
      'use strict';

      var cfg = window.tetapanSistemConfig || {};
      var baseUrl = typeof cfg.baseUrl === 'string' ? cfg.baseUrl : '';
      var csrfToken = typeof cfg.csrfToken === 'string' ? cfg.csrfToken : '';
      var store = Array.isArray(cfg.additionalConnections) ? cfg.additionalConnections.slice() : [];

      function escapeHtml(value) {
        return String(value == null ? '' : value)
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#039;');
      }

      function getEl(id) {
        return document.getElementById(id);
      }

      function getModal() {
        var el = getEl('db-additional-modal');
        if (!el) {
          return null;
        }
        if (window.bootstrap && window.bootstrap.Modal) {
          return window.bootstrap.Modal.getOrCreateInstance(el);
        }
        return null;
      }

      function ensureModalMountedToBody() {
        var el = getEl('db-additional-modal');
        if (!el) {
          return null;
        }
        if (el.parentElement !== document.body) {
          document.body.appendChild(el);
        }
        return el;
      }

      function showModal() {
        var el = ensureModalMountedToBody();
        if (!el) {
          return;
        }
        var modal = getModal();
        if (modal) {
          modal.show();
          return;
        }
        el.style.display = 'block';
        el.classList.add('show');
        el.removeAttribute('aria-hidden');
        document.body.classList.add('modal-open');
        if (!document.querySelector('[data-db-additional-fallback-backdrop="1"]')) {
          var backdrop = document.createElement('div');
          backdrop.className = 'modal-backdrop fade show';
          backdrop.setAttribute('data-db-additional-fallback-backdrop', '1');
          backdrop.addEventListener('click', function () {
            hideModal();
          });
          document.body.appendChild(backdrop);
        }
      }

      function hideModal() {
        var el = ensureModalMountedToBody();
        if (!el) {
          return;
        }
        var modal = getModal();
        if (modal) {
          modal.hide();
          return;
        }
        el.style.display = 'none';
        el.classList.remove('show');
        el.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        document.querySelectorAll('[data-db-additional-fallback-backdrop="1"]').forEach(function (backdrop) {
          backdrop.remove();
        });
      }

      function alertError(message) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
          window.Swal.fire({
            icon: 'error',
            title: 'Ralat',
            text: message || 'Tindakan gagal diproses.',
            confirmButtonText: 'OK'
          });
          return;
        }
        window.alert(message || 'Tindakan gagal diproses.');
      }

      function alertSuccess(message) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
          window.Swal.fire({
            icon: 'success',
            title: 'Berjaya',
            text: message || 'Tindakan berjaya diproses.',
            confirmButtonText: 'OK'
          });
          return;
        }
      }

      function envSummary(envRows) {
        if (!Array.isArray(envRows) || !envRows.length) {
          return '<span class="text-muted small">No env rows</span>';
        }
        return envRows.map(function (row) {
          return '<span class="db-additional-pill">'
            + escapeHtml(String(row.f_environment || '-'))
            + ' / '
            + escapeHtml(String(row.f_os_family || 'any'))
            + ' / '
            + escapeHtml(String(row.f_driver || '-'))
            + '</span>';
        }).join('');
      }

      function lastTestSummary(envRows) {
        if (!Array.isArray(envRows) || !envRows.length) {
          return 'Belum diuji';
        }
        var rows = envRows.filter(function (row) { return row && row.f_last_tested_at; });
        if (!rows.length) {
          return 'Belum diuji';
        }
        rows.sort(function (a, b) {
          return String(b.f_last_tested_at || '').localeCompare(String(a.f_last_tested_at || ''));
        });
        return String(rows[0].f_last_test_status || '') + ' · ' + String(rows[0].f_last_tested_at || '');
      }

      function filteredConnections() {
        var searchEl = getEl('db-additional-search');
        var familyEl = getEl('db-additional-family-filter');
        var statusEl = getEl('db-additional-status-filter');
        var search = searchEl ? String(searchEl.value || '').trim().toLowerCase() : '';
        var family = familyEl ? String(familyEl.value || '').trim().toLowerCase() : '';
        var status = statusEl ? String(statusEl.value || '').trim().toLowerCase() : '';

        return store.filter(function (item) {
          var haystack = [
            item.f_code,
            item.f_name,
            item.f_family,
            item.f_purpose,
            item.f_notes
          ].join(' ').toLowerCase();
          var enabled = !!Number(item.f_is_enabled || 0);
          if (search && haystack.indexOf(search) === -1) {
            return false;
          }
          if (family && String(item.f_family || '').toLowerCase() !== family) {
            return false;
          }
          if (status === 'enabled' && !enabled) {
            return false;
          }
          if (status === 'disabled' && enabled) {
            return false;
          }
          return true;
        });
      }

      function renderTable() {
        var body = getEl('db-additional-table-body');
        var empty = getEl('db-additional-empty');
        var counter = getEl('db-additional-counter');
        if (!body) {
          return;
        }

        var rows = filteredConnections();
        if (counter) {
          counter.textContent = String(rows.length);
        }

        if (!rows.length) {
          body.innerHTML = '';
          if (empty) {
            empty.classList.remove('d-none');
          }
          return;
        }

        if (empty) {
          empty.classList.add('d-none');
        }

        body.innerHTML = rows.map(function (item) {
          var code = String(item.f_code || '');
          var enabled = !!Number(item.f_is_enabled || 0);
          return ''
            + '<tr data-connection-code="' + escapeHtml(code) + '">'
            + '<td><div class="db-additional-code">' + escapeHtml(code) + '</div></td>'
            + '<td>' + escapeHtml(String(item.f_name || code || '-')) + '</td>'
            + '<td><span class="badge bg-info-subtle text-info">' + escapeHtml(String(item.f_family || '-').toUpperCase()) + '</span></td>'
            + '<td>' + escapeHtml(String(item.f_purpose || '-')) + '</td>'
            + '<td><div class="db-additional-meta">' + envSummary(item.env_rows || []) + '</div></td>'
            + '<td>' + (enabled
                ? '<span class="badge bg-success-subtle text-success">Enabled</span>'
                : '<span class="badge bg-secondary-subtle text-secondary">Disabled</span>') + '</td>'
            + '<td><div class="db-additional-test-result">' + escapeHtml(lastTestSummary(item.env_rows || [])) + '</div></td>'
            + '<td class="text-end"><div class="db-additional-actions">'
            + '<button type="button" class="btn btn-sm btn-outline-secondary icon-btn" title="Schema Preview" aria-label="Schema Preview" onclick="return window.__tetapanSchemaPreviewAdditionalConnection(\'' + escapeHtml(code) + '\', this)"><i class="ri-table-line"></i></button>'
            + '<button type="button" class="btn btn-sm btn-outline-info icon-btn" title="Butiran Sambungan Tambahan" aria-label="Butiran Sambungan Tambahan" onclick="return window.__tetapanInspectAdditionalConnection(\'' + escapeHtml(code) + '\', this)"><i class="ri-eye-line"></i></button>'
            + '<button type="button" class="btn btn-sm btn-outline-primary icon-btn" title="Edit" aria-label="Edit" onclick="return window.__tetapanOpenAdditionalConnectionModal(\'' + escapeHtml(code) + '\')"><i class="ri-edit-line"></i></button>'
            + '<button type="button" class="btn btn-sm btn-outline-success icon-btn" title="Test Connection" aria-label="Test Connection" onclick="return window.__tetapanTestAdditionalConnection(\'' + escapeHtml(code) + '\', this)"><i class="ri-plug-line"></i></button>'
            + '<button type="button" class="btn btn-sm ' + (enabled ? 'btn-outline-warning' : 'btn-outline-secondary') + ' icon-btn" title="' + (enabled ? 'Disable' : 'Enable') + '" aria-label="' + (enabled ? 'Disable' : 'Enable') + '" onclick="return window.__tetapanToggleAdditionalConnection(\'' + escapeHtml(code) + '\', ' + (enabled ? 'false' : 'true') + ', this)"><i class="ri-power-line"></i></button>'
            + '</div></td>'
            + '</tr>';
        }).join('');
      }

      function buildEnvRow(row, index) {
        var safe = Object.assign({
          f_environment: 'production',
          f_os_family: 'any',
          f_driver: 'mysql',
          f_host: '',
          f_port: '',
          f_database_name: '',
          f_dsn_name: '',
          f_username: '',
          f_password_ciphertext: '',
          f_charset: 'utf8mb4',
          f_is_active: true
        }, row || {});

        return ''
          + '<div class="db-additional-env-row" data-env-row>'
          + '<div class="db-additional-env-row-header">'
          + '<div><div class="db-additional-env-row-index">Env Row ' + (index + 1) + '</div><div class="db-additional-inline-help">Satu row untuk satu kombinasi environment, OS, dan driver.</div></div>'
          + '<button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest(\'[data-env-row]\').remove(); return false;"><i class="ri-delete-bin-line me-1"></i>Remove</button>'
          + '</div>'
          + '<div class="row g-3">'
          + '<div class="col-md-3"><label class="form-label">Environment</label><select class="form-select" data-env-field="f_environment"><option value="production"' + (safe.f_environment === 'production' ? ' selected' : '') + '>Production</option><option value="development"' + (safe.f_environment === 'development' ? ' selected' : '') + '>Development</option></select></div>'
          + '<div class="col-md-3"><label class="form-label">OS Family</label><select class="form-select" data-env-field="f_os_family"><option value="any"' + (safe.f_os_family === 'any' ? ' selected' : '') + '>Any</option><option value="windows"' + (safe.f_os_family === 'windows' ? ' selected' : '') + '>Windows</option><option value="linux"' + (safe.f_os_family === 'linux' ? ' selected' : '') + '>Linux</option></select></div>'
          + '<div class="col-md-3"><label class="form-label">Driver</label><select class="form-select" data-env-field="f_driver"><option value="mysql"' + (safe.f_driver === 'mysql' ? ' selected' : '') + '>mysql</option><option value="odbc"' + (safe.f_driver === 'odbc' ? ' selected' : '') + '>odbc</option><option value="dblib"' + (safe.f_driver === 'dblib' ? ' selected' : '') + '>dblib</option><option value="sqlsrv"' + (safe.f_driver === 'sqlsrv' ? ' selected' : '') + '>sqlsrv</option></select></div>'
          + '<div class="col-md-3"><label class="form-label">Active</label><div class="form-check form-switch pt-2"><input class="form-check-input" type="checkbox" data-env-field="f_is_active"' + (safe.f_is_active ? ' checked' : '') + '></div></div>'
          + '<div class="col-md-4"><label class="form-label">Host</label><input type="text" class="form-control" data-env-field="f_host" value="' + escapeHtml(safe.f_host) + '"></div>'
          + '<div class="col-md-2"><label class="form-label">Port</label><input type="text" class="form-control" data-env-field="f_port" value="' + escapeHtml(safe.f_port) + '"></div>'
          + '<div class="col-md-3"><label class="form-label">Database</label><input type="text" class="form-control" data-env-field="f_database_name" value="' + escapeHtml(safe.f_database_name) + '"></div>'
          + '<div class="col-md-3"><label class="form-label">DSN</label><input type="text" class="form-control" data-env-field="f_dsn_name" value="' + escapeHtml(safe.f_dsn_name) + '"></div>'
          + '<div class="col-md-4"><label class="form-label">Username</label><input type="text" class="form-control" data-env-field="f_username" value="' + escapeHtml(safe.f_username) + '"></div>'
          + '<div class="col-md-4"><label class="form-label">Password</label><input type="password" class="form-control" data-env-field="f_password_ciphertext" value="' + escapeHtml(safe.f_password_ciphertext) + '"></div>'
          + '<div class="col-md-4"><label class="form-label">Charset</label><input type="text" class="form-control" data-env-field="f_charset" value="' + escapeHtml(safe.f_charset) + '"></div>'
          + '</div>'
          + '</div>';
      }

      function appendEnvRow(row) {
        var host = getEl('db-additional-env-rows');
        if (!host) {
          return;
        }
        var wrapper = document.createElement('div');
        wrapper.innerHTML = buildEnvRow(row, host.querySelectorAll('[data-env-row]').length);
        if (wrapper.firstElementChild) {
          host.appendChild(wrapper.firstElementChild);
        }
      }

      function resetForm() {
        var form = getEl('form-db-additional');
        if (form) {
          form.reset();
        }
        if (getEl('db-additional-form-type')) getEl('db-additional-form-type').value = 'db_additional_create';
        if (getEl('db-additional-existing-code')) getEl('db-additional-existing-code').value = '';
        if (getEl('db-additional-code')) {
          getEl('db-additional-code').readOnly = false;
          getEl('db-additional-code').value = '';
        }
        if (getEl('db-additional-enabled')) getEl('db-additional-enabled').checked = true;
        if (getEl('db-additional-supports-prod')) getEl('db-additional-supports-prod').checked = true;
        if (getEl('db-additional-supports-dev')) getEl('db-additional-supports-dev').checked = false;
        if (getEl('db-additional-modal-title')) {
          getEl('db-additional-modal-title').textContent = 'Add Additional Connection';
        }
        var rowsHost = getEl('db-additional-env-rows');
        if (rowsHost) {
          rowsHost.innerHTML = '';
        }
        appendEnvRow();
      }

      function serializeEnvRows() {
        var host = getEl('db-additional-env-rows');
        if (!host) {
          return [];
        }
        return Array.prototype.map.call(host.querySelectorAll('[data-env-row]'), function (row) {
          var payload = {};
          Array.prototype.forEach.call(row.querySelectorAll('[data-env-field]'), function (field) {
            var key = field.getAttribute('data-env-field');
            if (!key) {
              return;
            }
            if (field.type === 'checkbox') {
              payload[key] = !!field.checked;
              return;
            }
            payload[key] = String(field.value || '').trim();
          });
          return payload;
        });
      }

      function postAction(formType, data, buttonEl) {
        var fd = new FormData();
        fd.set('ajax', '1');
        fd.set('csrf_token', csrfToken);
        fd.set('form_type', formType);
        Object.keys(data || {}).forEach(function (key) {
          fd.set(key, data[key]);
        });
        if (buttonEl) {
          buttonEl.disabled = true;
        }
        return fetch(window.location.href, {
          method: 'POST',
          body: fd,
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-Token': csrfToken,
            'X-No-Loader': '1'
          }
        }).then(function (res) {
          return res.json();
        }).finally(function () {
          if (buttonEl) {
            buttonEl.disabled = false;
          }
        });
      }

      window.__tetapanOpenAdditionalConnectionModal = function (code) {
        resetForm();
        if (code) {
          var found = store.find(function (item) { return String(item.f_code || '') === String(code); }) || null;
          if (found) {
            if (getEl('db-additional-form-type')) getEl('db-additional-form-type').value = 'db_additional_update';
            if (getEl('db-additional-existing-code')) getEl('db-additional-existing-code').value = String(found.f_code || '');
            if (getEl('db-additional-code')) {
              getEl('db-additional-code').value = String(found.f_code || '');
              getEl('db-additional-code').readOnly = true;
            }
            if (getEl('db-additional-name')) getEl('db-additional-name').value = String(found.f_name || '');
            if (getEl('db-additional-purpose')) getEl('db-additional-purpose').value = String(found.f_purpose || '');
            if (getEl('db-additional-family')) getEl('db-additional-family').value = String(found.f_family || 'mysql');
            if (getEl('db-additional-driver-mode')) getEl('db-additional-driver-mode').value = String(found.f_driver_mode || 'auto');
            if (getEl('db-additional-notes')) getEl('db-additional-notes').value = String(found.f_notes || '');
            if (getEl('db-additional-enabled')) getEl('db-additional-enabled').checked = !!Number(found.f_is_enabled || 0);
            if (getEl('db-additional-supports-prod')) getEl('db-additional-supports-prod').checked = !!Number(found.f_supports_prod || 0);
            if (getEl('db-additional-supports-dev')) getEl('db-additional-supports-dev').checked = !!Number(found.f_supports_dev || 0);
            var rowsHost = getEl('db-additional-env-rows');
            if (rowsHost) rowsHost.innerHTML = '';
            (Array.isArray(found.env_rows) && found.env_rows.length ? found.env_rows : []).forEach(function (row) {
              appendEnvRow(row);
            });
            if (rowsHost && !rowsHost.querySelector('[data-env-row]')) {
              appendEnvRow();
            }
            if (getEl('db-additional-modal-title')) {
              getEl('db-additional-modal-title').textContent = 'Edit Additional Connection';
            }
          }
        }
        showModal();
        return false;
      };

      window.__tetapanRefreshAdditionalConnections = function (buttonEl) {
        postAction('db_additional_list', {}, buttonEl)
          .then(function (payload) {
            if (!payload || payload.success !== true) {
              throw new Error((payload && payload.message) || 'Gagal memuat semula sambungan tambahan.');
            }
            if (payload.data && Array.isArray(payload.data.additionalConnections)) {
              store = payload.data.additionalConnections.slice();
              window.tetapanSistemConfig.additionalConnections = store.slice();
              renderTable();
            }
          })
          .catch(function (error) {
            alertError(error && error.message ? error.message : 'Gagal memuat semula sambungan tambahan.');
          });
        return false;
      };

      window.__tetapanSaveAdditionalConnection = function (buttonEl) {
        var formType = getEl('db-additional-form-type') ? String(getEl('db-additional-form-type').value || 'db_additional_create') : 'db_additional_create';
        var payload = {
          f_code: getEl('db-additional-code') ? getEl('db-additional-code').value : '',
          f_name: getEl('db-additional-name') ? getEl('db-additional-name').value : '',
          f_purpose: getEl('db-additional-purpose') ? getEl('db-additional-purpose').value : '',
          f_family: getEl('db-additional-family') ? getEl('db-additional-family').value : '',
          f_driver_mode: getEl('db-additional-driver-mode') ? getEl('db-additional-driver-mode').value : '',
          f_notes: getEl('db-additional-notes') ? getEl('db-additional-notes').value : '',
          f_is_enabled: getEl('db-additional-enabled') && getEl('db-additional-enabled').checked ? '1' : '0',
          f_supports_prod: getEl('db-additional-supports-prod') && getEl('db-additional-supports-prod').checked ? '1' : '0',
          f_supports_dev: getEl('db-additional-supports-dev') && getEl('db-additional-supports-dev').checked ? '1' : '0',
          existing_code: getEl('db-additional-existing-code') ? getEl('db-additional-existing-code').value : '',
          env_rows: JSON.stringify(serializeEnvRows())
        };

        postAction(formType, payload, buttonEl)
          .then(function (response) {
            if (!response || response.success !== true) {
              throw new Error((response && response.message) || 'Gagal menyimpan sambungan tambahan.');
            }
            if (response.data && Array.isArray(response.data.additionalConnections)) {
              store = response.data.additionalConnections.slice();
              window.tetapanSistemConfig.additionalConnections = store.slice();
              renderTable();
            }
            hideModal();
            alertSuccess(response.message || 'Sambungan tambahan berjaya disimpan.');
          })
          .catch(function (error) {
            alertError(error && error.message ? error.message : 'Gagal menyimpan sambungan tambahan.');
          });
        return false;
      };

      window.__tetapanToggleAdditionalConnection = function (code, enabled, buttonEl) {
        postAction('db_additional_toggle', {
          connection_code: code,
          enabled: enabled ? '1' : '0'
        }, buttonEl)
          .then(function (response) {
            if (!response || response.success !== true) {
              throw new Error((response && response.message) || 'Gagal mengemas kini status sambungan tambahan.');
            }
            if (response.data && Array.isArray(response.data.additionalConnections)) {
              store = response.data.additionalConnections.slice();
              window.tetapanSistemConfig.additionalConnections = store.slice();
              renderTable();
            }
          })
          .catch(function (error) {
            alertError(error && error.message ? error.message : 'Gagal mengemas kini status sambungan tambahan.');
          });
        return false;
      };

      window.__tetapanTestAdditionalConnection = function (code, buttonEl) {
        var found = store.find(function (item) { return String(item.f_code || '') === String(code); }) || null;
        var firstEnv = found && Array.isArray(found.env_rows) && found.env_rows.length
          ? (found.env_rows.find(function (row) { return !!Number(row.f_is_active || 0); }) || found.env_rows[0])
          : null;
        postAction('db_additional_test', {
          connection_code: code,
          environment: firstEnv ? String(firstEnv.f_environment || 'production') : 'production',
          os_family: firstEnv ? String(firstEnv.f_os_family || 'any') : 'any',
          driver: firstEnv ? String(firstEnv.f_driver || '') : ''
        }, buttonEl)
          .then(function (response) {
            if (!response || response.success !== true) {
              throw new Error((response && response.message) || 'Ujian sambungan tambahan gagal.');
            }
            alertSuccess(response.message || 'Ujian sambungan tambahan berjaya.');
            return window.__tetapanRefreshAdditionalConnections();
          })
          .catch(function (error) {
            alertError(error && error.message ? error.message : 'Ujian sambungan tambahan gagal.');
          });
        return false;
      };

      window.__tetapanInspectAdditionalConnection = function (code, buttonEl) {
        var found = store.find(function (item) { return String(item.f_code || '') === String(code); }) || null;
        var inspectEnv = found && Array.isArray(found.env_rows) && found.env_rows.length
          ? (found.env_rows.find(function (row) { return !!Number(row.f_is_active || 0); }) || found.env_rows[0])
          : null;
        postAction('db_additional_inspect', {
          connection_code: code,
          environment: inspectEnv ? String(inspectEnv.f_environment || 'production') : 'production',
          os_family: inspectEnv ? String(inspectEnv.f_os_family || 'any') : 'any',
          driver: inspectEnv ? String(inspectEnv.f_driver || '') : ''
        }, buttonEl)
          .then(function (response) {
            if (!response || response.success !== true || !response.data || !response.data.probe) {
              throw new Error((response && response.message) || 'Gagal memuatkan butiran sambungan tambahan.');
            }

            var probe = response.data.probe || {};
            var value = function (key) {
              return escapeHtml(probe && probe[key] != null && probe[key] !== '' ? probe[key] : '-');
            };
            var html = ''
              + '<div class="text-start"><table class="table table-sm align-middle mb-0"><tbody>'
              + '<tr><th style="width:180px">Code</th><td><code>' + value('connection_code') + '</code></td></tr>'
              + '<tr><th>Name</th><td>' + value('connection_name') + '</td></tr>'
              + '<tr><th>Family</th><td>' + value('family') + '</td></tr>'
              + '<tr><th>Purpose</th><td>' + value('purpose') + '</td></tr>'
              + '<tr><th>Environment</th><td>' + value('environment') + '</td></tr>'
              + '<tr><th>OS Family</th><td>' + value('os_family') + '</td></tr>'
              + '<tr><th>Configured Driver</th><td>' + value('configured_driver') + '</td></tr>'
              + '<tr><th>Active Driver</th><td>' + value('active_driver') + '</td></tr>'
              + '<tr><th>Host</th><td>' + value('host') + '</td></tr>'
              + '<tr><th>Port</th><td>' + value('port') + '</td></tr>'
              + '<tr><th>Configured DB</th><td>' + value('database_name') + '</td></tr>'
              + '<tr><th>Current DB</th><td>' + value('current_database') + '</td></tr>'
              + '<tr><th>Current User</th><td>' + value('current_user') + '</td></tr>'
              + '<tr><th>Server Time</th><td>' + value('server_time') + '</td></tr>'
              + '<tr><th>Server Version</th><td>' + value('server_version') + '</td></tr>'
              + '<tr><th>Ping</th><td>' + value('ping') + '</td></tr>'
              + '</tbody></table></div>';

            if (window.Swal && typeof window.Swal.fire === 'function') {
              window.Swal.fire({
                icon: 'info',
                title: 'Butiran Sambungan Tambahan',
                html: html,
                width: 760,
                confirmButtonText: 'OK'
              });
              return;
            }

            alertSuccess(response.message || 'Maklumat sambungan tambahan berjaya dimuatkan.');
          })
          .catch(function (error) {
            alertError(error && error.message ? error.message : 'Gagal memuatkan butiran sambungan tambahan.');
          });
        return false;
      };

      window.__tetapanSchemaPreviewAdditionalConnection = function (code, buttonEl) {
        var found = store.find(function (item) { return String(item.f_code || '') === String(code); }) || null;
        var schemaEnv = found && Array.isArray(found.env_rows) && found.env_rows.length
          ? (found.env_rows.find(function (row) { return !!Number(row.f_is_active || 0); }) || found.env_rows[0])
          : null;
        postAction('db_additional_schema_preview', {
          connection_code: code,
          environment: schemaEnv ? String(schemaEnv.f_environment || 'production') : 'production',
          os_family: schemaEnv ? String(schemaEnv.f_os_family || 'any') : 'any',
          driver: schemaEnv ? String(schemaEnv.f_driver || '') : ''
        }, buttonEl)
          .then(function (response) {
            if (!response || response.success !== true || !response.data || !response.data.schemaPreview) {
              throw new Error((response && response.message) || 'Gagal memuatkan schema preview sambungan tambahan.');
            }

            var preview = response.data.schemaPreview || {};
            var objects = Array.isArray(preview.objects) ? preview.objects : [];
            var rowsHtml = objects.length
              ? objects.map(function (item) {
                  var code = encodeURIComponent(String(preview.connection_code || ''));
                  var objectName = encodeURIComponent(String(item.object_name || ''));
                  var environment = encodeURIComponent(String(preview.environment || 'production'));
                  var osFamily = encodeURIComponent(String(preview.os_family || 'any'));
                  var driver = encodeURIComponent(String(preview.driver || ''));
                  return '<tr><td>' + escapeHtml(item.object_name || '-') + '</td><td>' + escapeHtml(item.object_type || '-') + '</td><td class="text-end"><button type="button" class="btn btn-sm btn-outline-primary" onclick="return window.__tetapanDataPreviewAdditionalConnection(\'' + code + '\', \'' + objectName + '\', \'' + environment + '\', \'' + osFamily + '\', \'' + driver + '\', this)"><i class="ri-file-search-line"></i></button></td></tr>';
                }).join('')
              : '<tr><td colspan="3" class="text-muted text-center py-3">Tiada objek ditemui.</td></tr>';

            var html = ''
              + '<div class="text-start mb-3">'
              + '<div><strong>Code:</strong> <code>' + escapeHtml(preview.connection_code || '-') + '</code></div>'
              + '<div><strong>Family:</strong> ' + escapeHtml(preview.family || '-') + '</div>'
              + '<div><strong>Environment:</strong> ' + escapeHtml(preview.environment || '-') + '</div>'
              + '<div><strong>Database:</strong> ' + escapeHtml(preview.database_name || '-') + '</div>'
              + '</div>'
              + '<div class="table-responsive"><table class="table table-sm align-middle mb-0">'
              + '<thead><tr><th>Object Name</th><th style="width:140px">Type</th><th class="text-end" style="width:96px">Preview</th></tr></thead>'
              + '<tbody>' + rowsHtml + '</tbody>'
              + '</table></div>';

            if (window.Swal && typeof window.Swal.fire === 'function') {
              window.Swal.fire({
                icon: 'info',
                title: 'Schema Preview',
                html: html,
                width: 820,
                confirmButtonText: 'OK'
              });
              return;
            }

            alertSuccess(response.message || 'Schema preview berjaya dimuatkan.');
          })
          .catch(function (error) {
            alertError(error && error.message ? error.message : 'Gagal memuatkan schema preview sambungan tambahan.');
          });
        return false;
      };

      window.__tetapanDataPreviewAdditionalConnection = function (encodedCode, encodedObjectName, encodedEnvironment, encodedOsFamily, encodedDriver, buttonEl) {
        var code = decodeURIComponent(String(encodedCode || ''));
        var objectName = decodeURIComponent(String(encodedObjectName || ''));
        var environment = decodeURIComponent(String(encodedEnvironment || 'production'));
        var osFamily = decodeURIComponent(String(encodedOsFamily || 'any'));
        var driver = decodeURIComponent(String(encodedDriver || ''));

        postAction('db_additional_object_preview', {
          connection_code: code,
          object_name: objectName,
          environment: environment,
          os_family: osFamily,
          driver: driver
        }, buttonEl)
          .then(function (response) {
            if (!response || response.success !== true || !response.data || !response.data.objectPreview) {
              throw new Error((response && response.message) || 'Gagal memuatkan data preview sambungan tambahan.');
            }

            var preview = response.data.objectPreview || {};
            var columns = Array.isArray(preview.columns) ? preview.columns : [];
            var rows = Array.isArray(preview.rows) ? preview.rows : [];
            var headerHtml = columns.map(function (column) {
              return '<th>' + escapeHtml(column) + '</th>';
            }).join('');
            var bodyHtml = rows.length
              ? rows.map(function (row) {
                  return '<tr>' + columns.map(function (column) {
                    var value = row && row[column] != null ? String(row[column]) : '';
                    return '<td>' + escapeHtml(value) + '</td>';
                  }).join('') + '</tr>';
                }).join('')
              : '<tr><td colspan="' + Math.max(columns.length, 1) + '" class="text-muted text-center py-3">Tiada rekod ditemui.</td></tr>';

            var html = ''
              + '<div class="text-start mb-3">'
              + '<div><strong>Code:</strong> <code>' + escapeHtml(preview.connection_code || '-') + '</code></div>'
              + '<div><strong>Object:</strong> ' + escapeHtml(preview.object_name || '-') + '</div>'
              + '<div><strong>Environment:</strong> ' + escapeHtml(preview.environment || '-') + '</div>'
              + '<div><strong>Database:</strong> ' + escapeHtml(preview.database_name || '-') + '</div>'
              + '</div>'
              + '<div class="table-responsive"><table class="table table-sm align-middle mb-0">'
              + '<thead><tr>' + headerHtml + '</tr></thead>'
              + '<tbody>' + bodyHtml + '</tbody>'
              + '</table></div>';

            if (window.Swal && typeof window.Swal.fire === 'function') {
              window.Swal.fire({
                icon: 'info',
                title: 'Data Preview',
                html: html,
                width: 960,
                confirmButtonText: 'OK'
              });
              return;
            }

            alertSuccess(response.message || 'Data preview berjaya dimuatkan.');
          })
          .catch(function (error) {
            alertError(error && error.message ? error.message : 'Gagal memuatkan data preview sambungan tambahan.');
          });
        return false;
      };

      document.addEventListener('DOMContentLoaded', function () {
        ensureModalMountedToBody();
        var search = getEl('db-additional-search');
        var family = getEl('db-additional-family-filter');
        var status = getEl('db-additional-status-filter');
        var envAdd = getEl('btn-db-additional-env-add');
        if (search) search.addEventListener('input', renderTable);
        if (family) family.addEventListener('change', renderTable);
        if (status) status.addEventListener('change', renderTable);
        if (envAdd) {
          envAdd.addEventListener('click', function (event) {
            event.preventDefault();
            appendEnvRow();
          });
        }
        document.querySelectorAll('#db-additional-modal [data-bs-dismiss="modal"]').forEach(function (button) {
          button.addEventListener('click', function (event) {
            if (window.bootstrap && window.bootstrap.Modal) {
              return;
            }
            event.preventDefault();
            hideModal();
          });
        });
        renderTable();
      });
    })();
  </script>
  <script src="<?= asset_url('js/helpers/page-ui-helper.js') ?>?v=<?= urlencode($version) ?>"></script>
  <script src="<?= asset_url('js/pages/tetapan-sistem.js') ?>?v=<?= urlencode($version) ?>"></script>
  <script>
    (function () {
      'use strict';

      function showManualTab(trigger) {
        var selector = trigger.getAttribute('data-bs-target') || trigger.getAttribute('href');
        if (!selector || selector.charAt(0) !== '#') {
          return;
        }

        var targetPane = document.querySelector(selector);
        if (!targetPane) {
          return;
        }

        var nav = trigger.closest('.nav');
        if (nav) {
          nav.querySelectorAll('[data-bs-toggle="tab"], [data-bs-toggle="pill"]').forEach(function (item) {
            item.classList.remove('active');
            item.setAttribute('aria-selected', 'false');
          });
        }

        var paneContainer = targetPane.parentElement;
        if (paneContainer && paneContainer.classList.contains('tab-content')) {
          paneContainer.querySelectorAll(':scope > .tab-pane').forEach(function (pane) {
            pane.classList.remove('show', 'active');
          });
        }

        trigger.classList.add('active');
        trigger.setAttribute('aria-selected', 'true');
        targetPane.classList.add('show', 'active');
      }

      function bindTabFallback() {
        document.addEventListener('click', function (event) {
          var trigger = event.target.closest('[data-bs-toggle="tab"], [data-bs-toggle="pill"]');
          if (!trigger) {
            return;
          }

          event.preventDefault();

          if (window.bootstrap && window.bootstrap.Tab) {
            window.bootstrap.Tab.getOrCreateInstance(trigger).show();
          }

          showManualTab(trigger);
        });
      }

      function initGeneralSubtabs() {
        var nav = document.querySelector('.general-subtabs');
        if (!nav) {
          return;
        }

        var wanted = null;
        try {
          wanted = window.sessionStorage.getItem('tetapan-sistem.general-subtab');
        } catch (storageError) {
          wanted = null;
        }

        if (!wanted) {
          var activeTrigger = nav.querySelector('.nav-link.active');
          wanted = activeTrigger
            ? String((activeTrigger.getAttribute('data-bs-target') || '').replace(/^#/, ''))
            : 'general-subtab-site';
        }

        var trigger = nav.querySelector('[data-bs-target="#' + wanted + '"]');
        if (trigger) {
          window.__tetapanShowGeneralSubtab(wanted, trigger);
        }
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
          bindTabFallback();
          initGeneralSubtabs();
        });
      } else {
        bindTabFallback();
        initGeneralSubtabs();
      }
    })();
  </script>

  <script>
    (function () {
      try {
        var storedAuthSubtab = window.sessionStorage.getItem('tetapan-sistem.auth-subtab');
        if (!storedAuthSubtab) {
          return;
        }

        var pane = document.getElementById(storedAuthSubtab);
        var trigger = document.querySelector('.auth-subtabs [data-bs-target="#' + storedAuthSubtab + '"]');
        if (pane && trigger) {
          window.__tetapanShowAuthSubtab(storedAuthSubtab, trigger);
        }
      } catch (storageError) {
        // ignore
      }
    })();
  </script>
</body>
</html>
