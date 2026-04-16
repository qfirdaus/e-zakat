<?php
declare(strict_types=1);

$NEED_DATATABLES = true;

require_once __DIR__ . '/../includes/init.php';
require_login();

require_once __DIR__ . '/../controllers/BdrDistanceController.php';

$isDistanceLookup = isset($_GET['distance_lookup']) && $_GET['distance_lookup'] === '1';
$isDistancePrefetch = isset($_GET['distance_prefetch']) && $_GET['distance_prefetch'] === '1';
$isDistanceDetail = isset($_GET['distance_detail']) && $_GET['distance_detail'] === '1';
$isRowsData = isset($_GET['rows_data']) && $_GET['rows_data'] === '1';
$isRowSnapshot = isset($_GET['row_snapshot']) && $_GET['row_snapshot'] === '1';
$isExportXls = isset($_GET['export_xls']) && $_GET['export_xls'] === '1';
$isEmailReminderSend = isset($_GET['email_reminder_send']) && $_GET['email_reminder_send'] === '1';
$isBulkEmailStart = isset($_GET['bulk_email_start']) && $_GET['bulk_email_start'] === '1';
$isBulkEmailProcess = isset($_GET['bulk_email_process']) && $_GET['bulk_email_process'] === '1';
$isBulkEmailStatus = isset($_GET['bulk_email_status']) && $_GET['bulk_email_status'] === '1';
$isBulkEmailExclusionSave = isset($_GET['bulk_email_exclusion_save']) && $_GET['bulk_email_exclusion_save'] === '1';
$controllerClass = 'BdrDistanceController';
$skipRowLoad = $isDistanceLookup || $isDistancePrefetch || $isDistanceDetail || $isRowsData || $isRowSnapshot || $isExportXls || $isEmailReminderSend || $isBulkEmailStart || $isBulkEmailProcess || $isBulkEmailStatus || $isBulkEmailExclusionSave;
$controller = new $controllerClass($skipRowLoad);

if ($isDistanceLookup) {
    $controller->handleDistanceLookup();
}

if ($isDistancePrefetch) {
    $controller->handleDistancePrefetch();
}

if ($isDistanceDetail) {
    $controller->handleDistanceDetail();
}

if ($isRowsData) {
    $controller->handleRowsData();
}

if ($isRowSnapshot) {
  $controller->handleRowSnapshot();
}

if ($isExportXls) {
    $controller->handleExportXls();
}

if ($isEmailReminderSend) {
    $controller->handleEmailReminderSend();
}

if ($isBulkEmailStart) {
    $controller->handleBulkEmailStart();
}

if ($isBulkEmailProcess) {
    $controller->handleBulkEmailProcess();
}

if ($isBulkEmailStatus) {
    $controller->handleBulkEmailStatus();
}

if ($isBulkEmailExclusionSave) {
    $controller->handleBulkEmailExclusionSave();
}

$lang = $controller->lang ?? ($_SESSION['lang'] ?? 'ms');
$rows = $controller->rows ?? [];
$departmentOptions = $controller->departmentOptions ?? [];
$currentDepartment = $controller->currentDepartment ?? '';
$selectedDepartment = $controller->selectedDepartmentValue ?? '';
$officeAddress = $controller->officeAddress ?? distance_office_address();
$officeLabel = $controller->officeLabel ?? distance_office_label();
$siteCode = $controller->siteCode ?? distance_default_site_code();
$siteOptions = $controller->siteOptions ?? distance_site_registry();
$emailReminderTestRecipient = method_exists($controller, 'getEmailReminderTestRecipient')
  ? (string)$controller->getEmailReminderTestRecipient()
  : '';

if (!function_exists('h')) {
    function h($v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('t')) {
    function t(string $key, string $fallback): string
    {
        $value = __($key);
        return ($value === $key || $value === null || $value === '') ? $fallback : (string)$value;
    }
}

if (!function_exists('bdr_destination_text')) {
    function bdr_destination_text(string $text, string $officeLabel): string
    {
        $officeLabel = trim($officeLabel) !== '' ? trim($officeLabel) : 'UPNM';
        if (str_contains($text, '{{office_label}}')) {
            return str_replace('{{office_label}}', $officeLabel, $text);
        }

        return str_replace('UPNM', $officeLabel, $text);
    }
}

$PAGE_TITLE = t('bdr_distance_page_title', 'Jarak');
$version = (string)($_ENV['APP_ASSET_VER'] ?? date('ymdHis'));
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>">
<head>
  <?php include __DIR__ . '/../includes/head.php'; ?>
  <meta name="csrf-token" content="<?= h((string)($_SESSION['csrf_token'] ?? '')) ?>">
  <link href="<?= base_url('assets/css/datatables-standard.css') ?>?v=<?= h($version) ?>" rel="stylesheet">
  <link href="<?= base_url('assets/vendor/select2/css/select2.min.css') ?>?v=<?= h($version) ?>" rel="stylesheet">
  <link href="<?= base_url('assets/css/pages/bdr-distance.css') ?>?v=<?= h($version) ?>" rel="stylesheet">
  <script src="<?= base_url('assets/js/helpers/datatables-standard.js') ?>?v=<?= h($version) ?>"></script>
  <style>
    body.bdr-distance-page {
      opacity: 1 !important;
      visibility: visible !important;
    }
    body.bdr-distance-page #global-loader,
    body.bdr-distance-page .loader-overlay,
    body.bdr-distance-page #preloader,
    body.bdr-distance-page #status {
      display: none !important;
      opacity: 0 !important;
      visibility: hidden !important;
      pointer-events: none !important;
    }
    body.bdr-distance-page #nprogress {
      display: none !important;
    }
    .bdr-site-switch {
      display: flex;
      flex-wrap: wrap;
      gap: .5rem;
      margin-top: 1rem;
    }
    .bdr-site-switch__link {
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      border: 1px solid #d8e0ea;
      border-radius: 999px;
      color: #334155;
      font-weight: 700;
      padding: .55rem .9rem;
      text-decoration: none;
      background: #fff;
    }
    .bdr-site-switch__link:hover {
      border-color: #94a3b8;
      color: #0f172a;
      background: #f8fafc;
    }
    .bdr-site-switch__link.is-active {
      border-color: #0f766e;
      color: #0f766e;
      background: #ecfdf5;
      box-shadow: 0 8px 20px rgba(15, 118, 110, .12);
    }
  </style>
</head>
<body
  data-topbar-color="<?= h($_SESSION['theme.topbar'] ?? 'light') ?>"
  data-menu-color="<?= h($_SESSION['theme.menu'] ?? 'light') ?>"
  data-layout="vertical"
  data-sidebar-size="default"
  data-loader="off"
  class="loading bdr-distance-page">
<script>
  (function () {
    function clearPageLoadingState() {
      if (document.body) {
        document.body.classList.remove('loading');
        document.body.classList.add('bdr-distance-page');
        document.body.setAttribute('data-loader', 'off');
        var overlay = document.querySelector('.loader-overlay');
        if (overlay) {
          overlay.classList.remove('show');
          overlay.style.display = 'none';
        }
        var progress = document.getElementById('nprogress');
        if (progress) {
          progress.style.display = 'none';
        }
        var globalLoader = document.getElementById('global-loader');
        if (globalLoader) {
          globalLoader.classList.add('loader-hidden');
          globalLoader.style.display = 'none';
        }
      }
    }
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', clearPageLoadingState, { once: true });
    } else {
      clearPageLoadingState();
    }
    window.setTimeout(clearPageLoadingState, 1200);
  })();
</script>

<div class="wrapper">
  <?php include __DIR__ . '/../includes/topbar.php'; ?>
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="content-page">
    <div class="content">
      <div class="container-fluid">

        <div class="row mb-3">
          <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center flex-wrap">
              <h4 class="page-title"><i class="ri-stack-line me-1"></i> <?= h($PAGE_TITLE) ?></h4>
              <div class="page-title-right">
                <ol class="breadcrumb m-0">
                  <li class="breadcrumb-item">
                    <a href="dashboard.php">
                      <i class="ri-home-4-line align-middle me-1"></i> <?= h(__('breadcrumb_home')) ?>
                    </a>
                  </li>
                  <li class="breadcrumb-item active"><?= h($PAGE_TITLE) ?></li>
                </ol>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-12">
            <div class="card crud-datatable-card">
              <div class="card-body">
                <div class="crud-datatable-intro">
                  <div>
                    <h5 class="card-title mb-1"><?= h(t('bdr_distance_table_title', 'Staf Location')) ?></h5>
                    <p class="text-muted mb-0" id="bdrActiveOfficeText">
                      <i class="ri-map-pin-2-line me-1"></i><?= h($officeLabel) ?> · <?= h($officeAddress) ?>
                    </p>
                    <div class="bdr-site-switch" aria-label="<?= h(t('bdr_distance_site_switch_label', 'Pilih lokasi kerja')) ?>">
                      <?php foreach ($siteOptions as $optionCode => $option): ?>
                        <?php
                          $optionCode = (string)$optionCode;
                          $optionLabel = (string)($option['label'] ?? $optionCode);
                          $siteUrl = 'bdr-distance.php?site=' . rawurlencode($optionCode);
                        ?>
                        <a href="<?= h($siteUrl) ?>" class="bdr-site-switch__link <?= $optionCode === $siteCode ? 'is-active' : '' ?>" data-site-code="<?= h($optionCode) ?>">
                          <i class="ri-building-4-line"></i><?= h($optionLabel) ?>
                        </a>
                      <?php endforeach; ?>
                    </div>
                  </div>
                  <div class="crud-datatable-intro-actions">
                    <div class="table-switch-loader table-switch-loader--inline" id="tableSwitchLoader" aria-hidden="true">
                      <div class="table-switch-loader__inner" title="<?= h(t('bdr_distance_loading_department', 'Memuat data jabatan...')) ?>">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                      </div>
                    </div>
                    <button type="button" class="btn btn-outline-success stats-summary-btn" id="exportXlsBtn">
                      <i class="ri-file-excel-2-line me-1"></i><?= h(t('bdr_distance_btn_export_xls', 'Export XLS')) ?>
                    </button>
                    <button type="button" class="btn btn-outline-danger stats-summary-btn" id="openBulkEmailModal">
                      <i class="ri-mail-send-line me-1"></i><?= h(t('bdr_distance_btn_bulk_email', 'Bulk Email')) ?>
                    </button>
                    <button type="button" class="btn btn-outline-primary stats-summary-btn" id="openStatsSummary">
                      <i class="ri-bar-chart-box-line me-1"></i><?= h((string)count($rows)) ?> <?= h(t('bdr_distance_total_records', 'rekod')) ?>
                    </button>
                  </div>
                </div>
                <div class="table-responsive dt-standard" id="bdrDistanceTableWrap">
                  <table class="table table-bordered align-middle w-100" id="userDT">
                    <thead>
                      <tr>
                        <th class="col-bil"><?= h(t('bdr_distance_col_no', 'No.')) ?></th>
                        <th class="col-nama"><?= h(t('bdr_distance_col_name', 'Nama')) ?></th>
                        <th class="col-staf"><?= h(t('bdr_distance_col_staff_no', 'No. Staf')) ?></th>
                        <th class="col-alamat"><?= h(t('bdr_distance_col_mailing_address', 'Alamat (Map)')) ?></th>
                        <th class="col-status"><?= h(t('bdr_distance_col_status', 'Status')) ?></th>
                        <th class="col-jarak"><?= h(t('bdr_distance_col_distance', 'Jarak (KM)')) ?></th>
                        <th class="col-actions"><?= h(t('bdr_distance_col_actions', 'Tindakan')) ?></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($rows as $index => $row): ?>
                        <?php
                          $name = (string)($row['name'] ?? '');
                          $staffNo = (string)($row['staff_no'] ?? '');
                          $staffEmail = trim((string)($row['email'] ?? ''));
                          $department = trim((string)($row['department'] ?? ''));
                          $departmentShort = trim((string)($row['department_short'] ?? ''));
                          if ($departmentShort === '') {
                              $departmentShort = $department;
                          }
                          $mailingAddress = trim((string)($row['mailing_address'] ?? ''));
                          $addressStatus = trim((string)($row['address_status'] ?? 'VALID'));
                          $mapUrl = trim((string)($row['map_url'] ?? ''));
                          $distanceLabel = trim((string)($row['distance_label'] ?? ''));
                          $distanceSource = trim((string)($row['distance_source'] ?? ''));
                          $directionUrl = trim((string)($row['direction_url'] ?? ''));
                          $matchQuality = trim((string)($row['match_quality'] ?? ''));
                          $debugReason = trim((string)($row['debug_reason'] ?? ''));
                          $isAddressChanged = !empty($row['is_address_changed']) || $debugReason === 'address_changed';
                          $showEmailReminder = !empty($row['show_email_reminder']);
                          $isReviewLocation = !empty($row['is_review_location'])
                              || $matchQuality === 'LOW'
                              || $debugReason === 'low_match_quality'
                              || $distanceLabel === t('bdr_distance_status_review_location', 'Semak Lokasi');
                          $issueType = trim((string)($row['issue_type'] ?? 'none'));
                          $issueLabel = trim((string)($row['issue_label'] ?? ''));
                          $confirmedNotificationIssues = is_array($row['confirmed_notification_issues'] ?? null) ? array_values(array_map('strval', $row['confirmed_notification_issues'])) : [];
                          $isIssueSelfConfirmed = $issueType !== '' && in_array($issueType, $confirmedNotificationIssues, true);
                          $rowClass = '';
                          $addressTooltip = '';
                          $distanceTooltip = '';
                          $distanceInitialText = '-';
                          $distancePendingClass = '';
                          $distanceAttempted = !empty($row['distance_attempted']);
                          if ($addressStatus === 'EMPTY') {
                              $rowClass = 'table-danger-soft';
                              $addressTooltip = t('bdr_distance_status_no_address', 'Tiada Alamat');
                              $distanceTooltip = t('bdr_distance_msg_no_address', 'Jarak tidak dapat dikira kerana tiada alamat');
                              $distanceInitialText = t('bdr_distance_status_no_address', 'Tiada Alamat');
                          } elseif ($addressStatus === 'INVALID') {
                              $rowClass = 'table-warning-soft';
                              $addressTooltip = t('bdr_distance_status_incomplete', 'Alamat tidak lengkap');
                              $distanceTooltip = t('bdr_distance_msg_invalid', 'Jarak berdasarkan alamat yang mungkin tidak lengkap');
                              $distanceInitialText = t('bdr_distance_status_incomplete', 'Alamat tidak lengkap');
                          } elseif ($isAddressChanged) {
                              $rowClass = 'table-warning-soft';
                              $distanceTooltip = t('bdr_distance_msg_address_changed', 'Alamat telah berubah. Sila kira semula.');
                              $distanceInitialText = t('bdr_distance_not_calculated', 'Belum Dikira');
                              $distancePendingClass = 'distance-pending';
                          } else {
                              $distanceTooltip = $distanceAttempted
                                  ? t('bdr_distance_msg_failed_cached', 'Kiraan terdahulu gagal. Klik butang Kira untuk cuba semula')
                                  : t('bdr_distance_msg_checking_cache', 'Sedang menyemak cache jarak');
                              $distanceInitialText = $distanceAttempted
                                  ? t('bdr_distance_status_failed', 'Gagal Kira')
                                  : t('bdr_distance_not_calculated', 'Belum Dikira');
                              $distancePendingClass = $distanceAttempted ? '' : 'distance-pending';
                          }
                          if ($distanceLabel === t('bdr_distance_status_failed', 'Gagal Kira')) {
                              $distanceTooltip = t('bdr_distance_msg_failed_cached', 'Kiraan terdahulu gagal. Klik butang Kira untuk cuba semula');
                              $distanceInitialText = t('bdr_distance_status_failed', 'Gagal Kira');
                              $distancePendingClass = '';
                          }
                          if ($distanceLabel !== '') {
                              if ($distanceLabel !== t('bdr_distance_status_failed', 'Gagal Kira')) {
                                  $distanceTooltip = $distanceSource === 'route'
                                      ? bdr_destination_text(t('bdr_distance_msg_route', 'Jarak laluan pemanduan terdekat ke UPNM'), $officeLabel)
                                      : bdr_destination_text(t('bdr_distance_msg_straight', 'Anggaran jarak garis lurus ke UPNM'), $officeLabel);
                                  $distanceInitialText = $distanceLabel;
                                  $distancePendingClass = '';
                              }
                          } elseif ($distanceTooltip === '') {
                              $distanceTooltip = t('bdr_distance_msg_unknown', 'Jarak belum dapat ditentukan');
                          }
                          $distanceCellClass = '';
                          $distanceKmValue = is_numeric($row['distance_km'] ?? null) ? (float)$row['distance_km'] : null;
                          if ($distanceKmValue !== null) {
                              if ($distanceKmValue > 8) {
                                  $distanceCellClass = 'distance-far';
                              } elseif ($distanceKmValue === 8.0) {
                                  $distanceCellClass = 'distance-equal';
                              } elseif ($distanceKmValue < 8) {
                                  $distanceCellClass = 'distance-near';
                                  $rowClass = trim($rowClass . ' table-distance-near-soft');
                              }
                          } elseif ($addressStatus === 'EMPTY') {
                              $distanceCellClass = 'distance-no-address';
                          } elseif ($distanceAttempted && $addressStatus === 'VALID') {
                              $distanceCellClass = 'distance-failed';
                          } elseif ($addressStatus === 'INVALID') {
                              $distanceCellClass = 'distance-failed';
                          }
                          if ($isReviewLocation) {
                              $distanceCellClass = 'distance-failed';
                              $rowClass = trim(preg_replace('/\btable-distance-near-soft\b/', '', $rowClass) . ' table-danger-soft');
                              $distanceInitialText = t('bdr_distance_status_review_location', 'Semak Lokasi');
                              $distanceTooltip = t('bdr_distance_msg_low_match', 'Lokasi geocode meragukan. Sila semak alamat dan koordinat.');
                          } elseif ($isAddressChanged) {
                              $distanceCellClass = 'distance-failed';
                          }
                          $modalRow = [
                              'id' => (int)($row['id'] ?? ($index + 1)),
                              'name' => $name,
                              'staff_no' => $staffNo,
                              'email' => $staffEmail,
                              'department' => $department,
                              'site_code' => (string)($row['site_code'] ?? $siteCode),
                              'office_label' => (string)($row['office_label'] ?? $officeLabel),
                              'office_address' => (string)($row['office_address'] ?? $officeAddress),
                              'mailing_address' => $mailingAddress,
                              'address_status' => $addressStatus,
                              'distance_label' => $distanceLabel,
                              'distance_source' => (string)($row['distance_source'] ?? ''),
                              'route_provider' => (string)($row['route_provider'] ?? ''),
                              'cache_origin' => (string)($row['cache_origin'] ?? 'none'),
                              'matched_query' => (string)($row['matched_query'] ?? ''),
                              'provider_display_name' => (string)($row['provider_display_name'] ?? ($row['matched_query'] ?? '')),
                              'home_coords_label' => (string)($row['home_coords_label'] ?? ''),
                              'match_quality' => $matchQuality,
                              'debug_reason' => $debugReason,
                              'is_review_location' => $isReviewLocation,
                              'is_address_changed' => $isAddressChanged,
                              'show_email_reminder' => $showEmailReminder,
                              'issue_type' => $issueType,
                              'issue_label' => $issueLabel,
                              'notification_address_hash' => (string)($row['notification_address_hash'] ?? ''),
                              'selected_state' => (string)($row['selected_state'] ?? ''),
                              'detected_state' => (string)($row['detected_state'] ?? ''),
                              'notification_issues' => is_array($row['notification_issues'] ?? null) ? $row['notification_issues'] : [],
                              'active_notification_issues' => is_array($row['active_notification_issues'] ?? null) ? $row['active_notification_issues'] : [],
                              'excluded_notification_issues' => is_array($row['excluded_notification_issues'] ?? null) ? $row['excluded_notification_issues'] : [],
                              'confirmed_notification_issues' => $confirmedNotificationIssues,
                          ];
                          $rowJson = json_encode($modalRow, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
                        ?>
                        <?php $addressInputs = is_array($row['address_inputs'] ?? null) ? $row['address_inputs'] : []; ?>
                        <tr data-row-index="<?= h((string)$index) ?>" data-site-code="<?= h((string)($row['site_code'] ?? $siteCode)) ?>" data-department="<?= h($department) ?>" data-staf-id="<?= h($staffNo) ?>" data-email="<?= h($staffEmail) ?>" data-address="<?= h($mailingAddress) ?>" data-address-status="<?= h($addressStatus) ?>" data-distance-attempted="<?= h($distanceAttempted ? '1' : '0') ?>" data-match-quality="<?= h($matchQuality) ?>" data-debug-reason="<?= h($debugReason) ?>" data-review-location="<?= h($isReviewLocation ? '1' : '0') ?>" data-address-changed="<?= h($isAddressChanged ? '1' : '0') ?>" data-show-email-reminder="<?= h($showEmailReminder ? '1' : '0') ?>" data-issue-type="<?= h($issueType) ?>" data-issue-confirmed="<?= h($isIssueSelfConfirmed ? '1' : '0') ?>" data-address-hash="<?= h((string)($row['notification_address_hash'] ?? '')) ?>" data-detected-state="<?= h((string)($row['detected_state'] ?? '')) ?>" data-alamat1="<?= h((string)($addressInputs['alamat1'] ?? '')) ?>" data-alamat2="<?= h((string)($addressInputs['alamat2'] ?? '')) ?>" data-alamat3="<?= h((string)($addressInputs['alamat3'] ?? '')) ?>" data-poskod="<?= h((string)($addressInputs['poskod'] ?? '')) ?>" data-negeri="<?= h((string)($addressInputs['negeri'] ?? '')) ?>" data-negara="<?= h((string)($addressInputs['negara'] ?? '')) ?>" data-route-points="[]" class="<?= h($rowClass) ?>">
                          <td class="col-bil"></td>
                          <td class="col-nama">
                            <span class="truncate-1line" data-bs-toggle="tooltip" data-bs-custom-class="template-tooltip" title="<?= h($name) ?>">
                              <?= h($name !== '' ? $name : '-') ?>
                            </span>
                          </td>
                          <td class="col-staf"><span class="truncate-1line" data-bs-toggle="tooltip" data-bs-custom-class="template-tooltip" title="<?= h($staffNo) ?>"><?= h($staffNo !== '' ? $staffNo : '-') ?></span></td>
                          <td class="col-alamat">
                              <?php if ($mailingAddress !== ''): ?>
                              <div class="alamat-cell-wrap" data-bs-toggle="tooltip" data-bs-custom-class="template-tooltip" title="<?= h($addressTooltip !== '' ? $addressTooltip . ' | ' . $mailingAddress : $mailingAddress) ?>">
                                <div class="alamat-cell-copy">
                                  <span class="alamat-text truncate-1line"><?= h($mailingAddress) ?></span>
                                </div>
                                <a href="<?= h($mapUrl) ?>" target="_blank" rel="noopener noreferrer" class="alamat-map-link" aria-label="<?= h(t('bdr_distance_open_map', 'Buka peta')) ?>">
                                  <i class="ri-map-pin-line"></i>
                                </a>
                              </div>
                              <?php else: ?>
                              <span class="address-empty-badge truncate-1line" data-bs-toggle="tooltip" data-bs-custom-class="template-tooltip" title="<?= h($addressTooltip !== '' ? $addressTooltip : t('bdr_distance_status_no_address', 'Tiada Alamat')) ?>"><?= h(t('bdr_distance_status_no_address', 'Tiada Alamat')) ?></span>
                              <?php endif; ?>
                            </td>
                          <td class="col-status">
                            <?php if ($issueLabel !== ''): ?>
                              <span class="status-pill status-pill--<?= h($issueType) ?><?= $isIssueSelfConfirmed ? ' status-pill--confirmed' : '' ?>"><?= h($issueLabel) ?><?php if ($isIssueSelfConfirmed): ?><i class="ri-check-double-line status-confirmed-icon" data-bs-toggle="tooltip" data-bs-custom-class="template-tooltip" title="<?= h(t('bdr_distance_status_confirmed_tooltip', 'Staf telah mengesahkan alamat ini.')) ?>"></i><?php endif; ?></span>
                            <?php else: ?>
                              <span class="status-pill status-pill--none"><?= h(t('bdr_distance_status_no_issue', 'Tiada Isu')) ?></span>
                            <?php endif; ?>
                          </td>
                          <td class="col-jarak">
                            <span class="js-distance-cell <?= h(trim($distancePendingClass . ' ' . $distanceCellClass)) ?> truncate-1line" data-distance-km="<?= h($distanceKmValue !== null ? (string)$distanceKmValue : '') ?>" data-direction-url="<?= h($directionUrl) ?>" data-bs-toggle="tooltip" data-bs-custom-class="template-tooltip" title="<?= h($distanceTooltip) ?>"><?= h($distanceInitialText) ?></span>
                          </td>
                          <td class="col-actions">
                            <button type="button" class="btn btn-outline-success btn-sm js-calc-row" title="<?= h(t('bdr_distance_btn_calculate', 'Kira Jarak')) ?>">
                              <i class="ri-route-line"></i>
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm js-view-row" data-row='<?= h((string)$rowJson) ?>' title="<?= h(t('bdr_distance_btn_view', 'Lihat')) ?>">
                              <i class="ri-eye-line"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm js-email-reminder" style="<?= $showEmailReminder ? '' : 'display:none;' ?>" title="<?= h(t('bdr_distance_btn_email_reminder', 'Email Reminder')) ?>">
                              <i class="ri-mail-send-line"></i>
                            </button>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/script.php'; ?>
<script src="<?= base_url('assets/vendor/select2/js/select2.full.min.js') ?>?v=<?= h($version) ?>"></script>
<div class="modal fade sample-modal sample-modal--view" id="sampleViewModal" tabindex="-1" aria-hidden="true" aria-labelledby="sampleViewModalTitle">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="sampleViewModalTitle">
          <i class="ri-eye-line"></i> <?= h(t('bdr_distance_modal_view_title', 'Maklumat Staf')) ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('bdr_distance_btn_close', 'Close')) ?>"></button>
      </div>
      <div class="modal-body">
        <ul class="nav nav-tabs sample-view-tabs" id="sampleViewTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="sample-view-info-tab" data-bs-toggle="tab" data-bs-target="#sample-view-info-pane" type="button" role="tab" aria-controls="sample-view-info-pane" aria-selected="true"><?= h(t('bdr_distance_tab_info', 'Maklumat')) ?></button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="sample-view-exclusion-tab" data-bs-toggle="tab" data-bs-target="#sample-view-exclusion-pane" type="button" role="tab" aria-controls="sample-view-exclusion-pane" aria-selected="false"><?= h(t('bdr_distance_notification_exclusion_title', 'Pengecualian Notifikasi')) ?></button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="sample-view-route-tab" data-bs-toggle="tab" data-bs-target="#sample-view-route-pane" type="button" role="tab" aria-controls="sample-view-route-pane" aria-selected="false"><?= h(t('bdr_distance_tab_visual', 'Route Visual')) ?></button>
          </li>
        </ul>
        <div class="tab-content sample-view-tab-content pt-3">
          <div class="tab-pane fade show active" id="sample-view-info-pane" role="tabpanel" aria-labelledby="sample-view-info-tab" tabindex="0">
            <div class="sample-view-grid">
              <div>
                <div class="sample-view-label"><?= h(t('bdr_distance_field_name', 'Nama')) ?></div>
                <div class="sample-view-value" id="sampleViewName">-</div>
              </div>
              <div>
                <div class="sample-view-label"><?= h(t('bdr_distance_field_staff_no', 'No. Staf')) ?></div>
                <div class="sample-view-value" id="sampleViewStaffNo">-</div>
              </div>
              <div>
                <div class="sample-view-label"><?= h(t('bdr_distance_field_department', 'Jabatan')) ?></div>
                <div class="sample-view-value" id="sampleViewDepartment">-</div>
              </div>
              <div>
                <div class="sample-view-label"><?= h(bdr_destination_text(t('bdr_distance_field_distance', 'Jarak ke UPNM'), $officeLabel)) ?></div>
                <div class="sample-view-value d-flex align-items-center justify-content-between gap-2" id="sampleViewDistanceWrap"><span id="sampleViewDistance">-</span><span id="sampleViewDataBadge" class="distance-origin-badge distance-origin-badge--none">-</span></div>
              </div>
              <div>
                <div class="sample-view-label"><?= h(t('bdr_distance_field_provider_matched_address', 'Alamat Padanan Provider')) ?></div>
                <div class="sample-view-value" id="sampleViewMatchedQuery">-</div>
              </div>
              <div>
                <div class="sample-view-label"><?= h(t('bdr_distance_field_home_coords', 'Koordinat Rumah')) ?></div>
                <div class="sample-view-value" id="sampleViewHomeCoords">-</div>
              </div>
              <div>
                <div class="sample-view-label"><?= h(t('bdr_distance_field_match_quality', 'Kualiti Match')) ?></div>
                <div class="sample-view-value" id="sampleViewMatchQuality">-</div>
              </div>
              <div>
                <div class="sample-view-label"><?= h(t('bdr_distance_field_distance_source', 'Sumber Jarak')) ?></div>
                <div class="sample-view-value" id="sampleViewDistanceSource">-</div>
              </div>
              <div>
                <div class="sample-view-label"><?= h(t('bdr_distance_field_debug_reason', 'Sebab Gagal')) ?></div>
                <div class="sample-view-value" id="sampleViewDebugReason">-</div>
              </div>
              <div class="sample-view-span">
                <div class="sample-view-label"><?= h(t('bdr_distance_field_staff_input_address', 'Alamat Input Staf')) ?></div>
              <div class="sample-view-value" id="sampleViewMailingAddress">-</div>
              </div>
            </div>
          </div>
          <div class="tab-pane fade" id="sample-view-exclusion-pane" role="tabpanel" aria-labelledby="sample-view-exclusion-tab" tabindex="0">
            <div class="sample-view-grid">
              <div class="sample-view-span">
                <div class="sample-view-label"><?= h(t('bdr_distance_notification_exclusion_title', 'Pengecualian Notifikasi')) ?></div>
                <div class="sample-view-value">
                  <div class="small text-muted mb-2" id="sampleViewExclusionHint"><?= h(t('bdr_distance_notification_exclusion_hint', 'Tandakan isu yang tidak perlu lagi menerima emel notifikasi untuk alamat semasa ini.')) ?></div>
                  <div id="sampleViewExclusionOptions" class="d-flex flex-column gap-2 mb-3"></div>
                  <div class="row g-2">
                    <div class="col-md-5">
                      <input type="text" class="form-control form-control-sm" id="sampleViewExclusionReason" placeholder="<?= h(t('bdr_distance_notification_exclusion_reason_placeholder', 'Sebab ringkas')) ?>">
                    </div>
                    <div class="col-md-7">
                      <input type="text" class="form-control form-control-sm" id="sampleViewExclusionNotes" placeholder="<?= h(t('bdr_distance_notification_exclusion_notes_placeholder', 'Catatan tambahan (optional)')) ?>">
                    </div>
                  </div>
                  <div class="mt-2 d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="sampleViewSaveExclusionBtn"><?= h(t('bdr_distance_notification_exclusion_save_btn', 'Simpan Exclude')) ?></button>
                    <span class="small text-muted" id="sampleViewExclusionStatus"><?= h(t('bdr_distance_notification_exclusion_none', 'Tiada isu untuk dikecualikan bagi rekod ini.')) ?></span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="tab-pane fade" id="sample-view-route-pane" role="tabpanel" aria-labelledby="sample-view-route-tab" tabindex="0">
            <div class="sample-view-grid">
              <div class="sample-view-span">
                <div class="sample-view-label"><?= h(t('bdr_distance_field_route_visual', 'Route Visual')) ?></div>
                <div class="sample-view-value p-2">
                  <div id="sampleRoutePreview" class="route-preview-empty"><?= h(t('bdr_distance_route_preview_empty', 'Tiada visual laluan')) ?></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= h(t('bdr_distance_btn_close', 'Close')) ?></button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade sample-modal sample-modal--view" id="statsSummaryModal" tabindex="-1" aria-hidden="true" aria-labelledby="statsSummaryModalTitle">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="statsSummaryModalTitle">
          <i class="ri-bar-chart-box-line"></i> <?= h(t('bdr_distance_stats_modal_title', 'Statistik Lokasi Staf')) ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('bdr_distance_btn_close', 'Close')) ?>"></button>
      </div>
      <div class="modal-body">
        <div class="stats-summary-grid" id="statsSummaryGrid">
          <div class="stats-summary-card">
            <div class="stats-summary-label"><?= h(t('bdr_distance_stats_total', 'Jumlah Rekod')) ?></div>
            <div class="stats-summary-value" id="statsTotalRecords">0</div>
          </div>
          <div class="stats-summary-card">
            <div class="stats-summary-label"><?= h(t('bdr_distance_stats_calculated', 'Berjaya Dikira')) ?></div>
            <div class="stats-summary-value" id="statsCalculated">0</div>
          </div>
          <div class="stats-summary-card">
            <div class="stats-summary-label"><?= h(t('bdr_distance_stats_failed', 'Gagal Kira')) ?></div>
            <div class="stats-summary-value" id="statsFailed">0</div>
          </div>
          <div class="stats-summary-card">
            <div class="stats-summary-label"><?= h(t('bdr_distance_stats_empty', 'Tiada Alamat')) ?></div>
            <div class="stats-summary-value" id="statsEmptyAddress">0</div>
          </div>
          <div class="stats-summary-card">
            <div class="stats-summary-label"><?= h(t('bdr_distance_stats_invalid', 'Alamat Tak Lengkap')) ?></div>
            <div class="stats-summary-value" id="statsInvalidAddress">0</div>
          </div>
          <div class="stats-summary-card">
            <div class="stats-summary-label"><?= h(t('bdr_distance_stats_pending', 'Belum Dikira')) ?></div>
            <div class="stats-summary-value" id="statsPending">0</div>
          </div>
          <div class="stats-summary-card">
            <div class="stats-summary-label"><?= h(t('bdr_distance_stats_lt8', '< 8 KM')) ?></div>
            <div class="stats-summary-value text-danger" id="statsNear">0</div>
          </div>
          <div class="stats-summary-card">
            <div class="stats-summary-label"><?= h(t('bdr_distance_stats_eq8', '= 8 KM')) ?></div>
            <div class="stats-summary-value" id="statsEqual">0</div>
          </div>
          <div class="stats-summary-card">
            <div class="stats-summary-label"><?= h(t('bdr_distance_stats_gt8', '> 8 KM')) ?></div>
            <div class="stats-summary-value text-success" id="statsFar">0</div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= h(t('bdr_distance_btn_close', 'Close')) ?></button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade sample-modal sample-modal--edit" id="distanceFilterModal" tabindex="-1" aria-hidden="true" aria-labelledby="distanceFilterModalTitle">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="distanceFilterModalTitle">
          <i class="ri-filter-3-line"></i> <?= h(t('bdr_distance_filter_modal_title', 'Custom Distance Filter')) ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('bdr_distance_btn_close', 'Close')) ?>"></button>
      </div>
      <div class="modal-body">
        <div class="sample-form-shell">
          <div class="mb-3">
            <label for="distanceFilterOperator" class="form-label"><?= h(t('bdr_distance_filter_operator_label', 'Operator')) ?></label>
            <select id="distanceFilterOperator" class="form-select">
              <option value="=">=</option>
              <option value="!=">!=</option>
              <option value=">">&gt;</option>
              <option value="<">&lt;</option>
              <option value=">=">&gt;=</option>
              <option value="<=">&lt;=</option>
              <option value="between"><?= h(t('bdr_distance_filter_operator_between', 'between')) ?></option>
            </select>
          </div>
          <div class="mb-3" id="distanceFilterSingleWrap">
            <label for="distanceFilterValue1" class="form-label"><?= h(t('bdr_distance_filter_value_label', 'Value')) ?></label>
            <input type="number" step="0.01" min="0" class="form-control" id="distanceFilterValue1">
          </div>
          <div class="row g-2 d-none" id="distanceFilterBetweenWrap">
            <div class="col-6">
              <label for="distanceFilterValueMin" class="form-label"><?= h(t('bdr_distance_filter_min_label', 'Min')) ?></label>
              <input type="number" step="0.01" min="0" class="form-control" id="distanceFilterValueMin">
            </div>
            <div class="col-6">
              <label for="distanceFilterValueMax" class="form-label"><?= h(t('bdr_distance_filter_max_label', 'Max')) ?></label>
              <input type="number" step="0.01" min="0" class="form-control" id="distanceFilterValueMax">
            </div>
          </div>
          <div class="invalid-feedback d-block d-none" id="distanceFilterError"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" id="distanceFilterResetBtn"><?= h(t('bdr_distance_filter_reset_btn', 'Reset Filter')) ?></button>
        <button type="button" class="btn btn-primary" id="distanceFilterApplyBtn"><?= h(t('bdr_distance_filter_apply_btn', 'Apply')) ?></button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="emailReminderModal" tabindex="-1" aria-hidden="true" aria-labelledby="emailReminderModalTitle">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content bdr-email-modal-shell">
      <div class="modal-header bdr-email-modal-header">
        <div>
          <div class="bdr-email-modal-kicker"><?= h(t('bdr_distance_email_modal_kicker', 'Komunikasi Staf')) ?></div>
          <h5 class="modal-title" id="emailReminderModalTitle"><?= h(t('bdr_distance_email_modal_title', 'Email Reminder')) ?></h5>
          <p class="mb-0 text-white-50 small"><?= h(t('bdr_distance_email_modal_subtitle', 'Hantar emel reminder untuk semakan alamat BDR.')) ?></p>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?= h(t('bdr_distance_btn_close', 'Close')) ?>"></button>
      </div>
      <div class="modal-body">
        <div class="row g-4">
          <div class="col-xl-5">
            <div class="bdr-email-panel">
              <div class="bdr-email-section-head">
                <div>
                  <h6 class="bdr-email-section-title mb-1"><?= h(t('bdr_distance_email_staff_info_title', 'Maklumat Staf')) ?></h6>
                  <p class="bdr-email-section-subtitle mb-0"><?= h(t('bdr_distance_email_staff_info_subtitle', 'Semak butiran penerima sebelum emel reminder dihantar.')) ?></p>
                </div>
              </div>
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label"><?= h(t('bdr_distance_field_name', 'Nama')) ?></label>
                  <input type="text" class="form-control bdr-email-input" id="emailReminderStaffName" readonly>
                </div>
                <div class="col-md-6">
                  <label class="form-label"><?= h(t('bdr_distance_field_staff_no', 'No. Staf')) ?></label>
                  <input type="text" class="form-control bdr-email-input" id="emailReminderStaffNo" readonly>
                </div>
                <div class="col-md-6">
                  <label class="form-label"><?= h(t('bdr_distance_field_department', 'Jabatan')) ?></label>
                  <input type="text" class="form-control bdr-email-input" id="emailReminderDepartment" readonly>
                </div>
                <div class="col-12">
                  <label class="form-label"><?= h(t('bdr_distance_email_recipient_label', 'Emel Penerima')) ?></label>
                  <input type="email" class="form-control bdr-email-input" id="emailReminderRecipientEmail" readonly>
                </div>
                <div class="col-12">
                  <label class="form-label"><?= h(t('bdr_distance_email_issue_label', 'Isu Dikesan')) ?></label>
                  <input type="text" class="form-control bdr-email-input" id="emailReminderIssueLabel" readonly>
                </div>
                <div class="col-12">
                  <label class="form-label"><?= h(t('bdr_distance_field_mailing_address', 'Alamat Surat Menyurat')) ?></label>
                  <textarea class="form-control bdr-email-input bdr-email-textarea" id="emailReminderAddress" rows="4" readonly></textarea>
                </div>
                <div class="col-12">
                  <label class="form-label"><?= h(bdr_destination_text(t('bdr_distance_field_distance', 'Jarak ke UPNM'), $officeLabel)) ?></label>
                  <input type="text" class="form-control bdr-email-input" id="emailReminderDistanceLabel" readonly>
                </div>
              </div>
            </div>
          </div>
          <div class="col-xl-7">
            <div class="bdr-email-panel">
              <div class="bdr-email-hero">
                <div class="bdr-email-hero-icon">
                  <i class="ri-mail-send-line"></i>
                </div>
                <div>
                  <div class="bdr-email-hero-kicker"><?= h(t('bdr_distance_email_summary_kicker', 'Ringkasan Tindakan')) ?></div>
                  <h6 class="bdr-email-hero-title mb-1"><?= h(t('bdr_distance_email_summary_title', 'Reminder akan dihantar terus selepas pengesahan.')) ?></h6>
                  <p class="bdr-email-hero-text mb-0"><?= h(t('bdr_distance_email_summary_subtitle', 'Gunakan panel ini untuk semak penerima, isu, dan alamat ujian sebelum menghantar.')) ?></p>
                </div>
              </div>
              <div class="bdr-email-summary-card">
                <div class="bdr-email-summary-top">
                  <div>
                    <div class="bdr-email-summary-label"><?= h(t('bdr_distance_email_summary_recipient', 'Penerima Reminder')) ?></div>
                    <div class="bdr-email-summary-name" id="emailReminderSummaryName">-</div>
                  </div>
                  <div class="bdr-email-issue-chip" id="emailReminderSummaryIssue">-</div>
                </div>
                <div class="bdr-email-summary-grid">
                  <div class="bdr-email-summary-item">
                    <span class="bdr-email-summary-item-label"><?= h(t('bdr_distance_email_recipient_label', 'Emel Penerima')) ?></span>
                    <span class="bdr-email-summary-item-value" id="emailReminderSummaryEmail">-</span>
                  </div>
                  <div class="bdr-email-summary-item">
                    <span class="bdr-email-summary-item-label"><?= h(bdr_destination_text(t('bdr_distance_field_distance', 'Jarak ke UPNM'), $officeLabel)) ?></span>
                    <span class="bdr-email-summary-item-value" id="emailReminderSummaryDistance">-</span>
                  </div>
                  <div class="bdr-email-summary-item">
                    <span class="bdr-email-summary-item-label"><?= h(t('bdr_distance_field_department', 'Jabatan')) ?></span>
                    <span class="bdr-email-summary-item-value" id="emailReminderSummaryDepartment">-</span>
                  </div>
                  <div class="bdr-email-summary-item">
                    <span class="bdr-email-summary-item-label"><?= h(t('bdr_distance_field_staff_no', 'No. Staf')) ?></span>
                    <span class="bdr-email-summary-item-value" id="emailReminderSummaryStaffNo">-</span>
                  </div>
                </div>
              </div>
              <div class="bdr-email-note">
                <div class="bdr-email-note-title"><?= h(t('bdr_distance_email_dispatch_note_title', 'Nota Penghantaran')) ?></div>
                <p class="mb-0"><?= h(t('bdr_distance_email_test_recipient_note', 'Emel peringatan akan dihantar terus kepada alamat emel staf yang dipaparkan dalam rekod ini.')) ?></p>
              </div>
              <div class="bdr-email-checklist">
                <div class="bdr-email-checklist-title"><?= h(t('bdr_distance_email_checklist_title', 'Semakan Cepat')) ?></div>
                <div class="bdr-email-checklist-item"><i class="ri-check-line"></i><span><?= h(t('bdr_distance_email_checklist_1', 'Butiran staf dan jabatan telah dipaparkan dari rekod semasa.')) ?></span></div>
                <div class="bdr-email-checklist-item"><i class="ri-check-line"></i><span><?= h(t('bdr_distance_email_checklist_2', 'Isu yang dikesan akan disertakan dalam kandungan reminder.')) ?></span></div>
                <div class="bdr-email-checklist-item"><i class="ri-check-line"></i><span><?= h(t('bdr_distance_email_checklist_3', 'Tekan Hantar Emel untuk teruskan selepas pengesahan akhir.')) ?></span></div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer bdr-email-modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= h(t('bdr_distance_btn_close', 'Close')) ?></button>
        <button type="button" class="btn btn-success bdr-email-send-btn" id="btnEmailReminderSend">
          <i class="ri-mail-send-line me-1"></i><?= h(t('bdr_distance_email_send_btn', 'Hantar Emel')) ?>
        </button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="bulkEmailModal" tabindex="-1" aria-hidden="true" aria-labelledby="bulkEmailModalTitle">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content bdr-bulk-modal-shell">
      <div class="modal-header bdr-bulk-modal-header">
        <div>
          <h5 class="modal-title" id="bulkEmailModalTitle"><?= h(t('bdr_distance_bulk_email_modal_title', 'Bulk Email Reminder')) ?></h5>
          <p class="text-muted mb-0 small"><?= h(t('bdr_distance_bulk_email_modal_subtitle', 'Hanya rekod bermasalah dalam paparan semasa akan dimasukkan ke dalam penghantaran berperingkat.')) ?></p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('bdr_distance_btn_close', 'Close')) ?>"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-4">
            <div class="bdr-bulk-stat-card h-100">
              <div class="text-muted small"><?= h(t('bdr_distance_bulk_email_summary_filtered', 'Rekod Dipaparkan')) ?></div>
              <div class="fs-4 fw-semibold" id="bulkEmailFilteredCount">0</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="bdr-bulk-stat-card h-100">
              <div class="text-muted small"><?= h(t('bdr_distance_bulk_email_summary_eligible', 'Layak Dihantar')) ?></div>
              <div class="fs-4 fw-semibold text-success" id="bulkEmailEligibleCount">0</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="bdr-bulk-stat-card h-100">
              <div class="text-muted small"><?= h(t('bdr_distance_bulk_email_summary_skipped', 'Akan Diskip')) ?></div>
              <div class="fs-4 fw-semibold text-warning" id="bulkEmailSkippedCount">0</div>
            </div>
          </div>
          <div class="col-12">
            <div class="bdr-bulk-note mb-0">
              <div class="fw-semibold mb-1"><?= h(t('bdr_distance_bulk_email_dispatch_mode', 'Mod Penghantaran')) ?></div>
              <div id="bulkEmailDispatchNote"><?= h(t('bdr_distance_bulk_email_dispatch_note', 'Emel akan dihantar secara berperingkat terus kepada alamat emel staf yang layak.')) ?></div>
            </div>
          </div>
          <div class="col-12">
            <div class="bdr-bulk-preview-card">
              <div class="fw-semibold mb-2"><?= h(t('bdr_distance_bulk_email_issue_filter_title', 'Pilih Kategori Isu')) ?></div>
              <div class="small text-muted mb-2" id="bulkEmailIssueHelp"><?= h(t('bdr_distance_bulk_email_issue_filter_help', 'Hanya kategori isu yang wujud dalam paparan semasa akan dipaparkan di sini.')) ?></div>
              <div id="bulkEmailIssueOptions" class="d-flex flex-column gap-2 mb-3"></div>
              <div class="small text-muted mb-3" id="bulkEmailIssueEmptyText"><?= h(t('bdr_distance_bulk_email_issue_filter_empty', 'Tiada kategori isu tersedia untuk dihantar.')) ?></div>
              <div class="fw-semibold mb-2"><?= h(t('bdr_distance_bulk_email_preview_title', 'Ringkasan Pemilihan')) ?></div>
              <div class="small text-muted" id="bulkEmailPreviewText"><?= h(t('bdr_distance_bulk_email_preview_empty', 'Tiada rekod dipilih buat masa ini.')) ?></div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer bdr-bulk-modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= h(t('bdr_distance_btn_close', 'Close')) ?></button>
        <button type="button" class="btn btn-danger" id="bulkEmailStartBtn">
          <i class="ri-mail-send-line me-1"></i><?= h(t('bdr_distance_bulk_email_start_btn', 'Mulakan Bulk Email')) ?>
        </button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="bulkEmailProgressModal" tabindex="-1" aria-hidden="true" aria-labelledby="bulkEmailProgressModalTitle" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content bdr-bulk-modal-shell">
      <div class="modal-header bdr-bulk-modal-header">
        <div>
          <h5 class="modal-title" id="bulkEmailProgressModalTitle"><?= h(t('bdr_distance_bulk_email_progress_title', 'Progress Bulk Email')) ?></h5>
          <p class="text-muted mb-0 small"><?= h(t('bdr_distance_bulk_email_progress_subtitle', 'Emel dihantar dalam batch kecil untuk mengurangkan risiko spam dan elak beban mendadak pada sistem.')) ?></p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('bdr_distance_btn_close', 'Close')) ?>"></button>
      </div>
      <div class="modal-body">
        <div class="progress mb-3" style="height: 12px;">
          <div class="progress-bar progress-bar-striped progress-bar-animated" id="bulkEmailProgressBar" role="progressbar" style="width:0%" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">0%</div>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-md-3 col-6">
            <div class="bdr-bulk-stat-card bdr-bulk-stat-card--compact text-center h-100">
              <div class="text-muted small"><?= h(t('bdr_distance_bulk_email_stat_total', 'Jumlah')) ?></div>
              <div class="fs-5 fw-semibold" id="bulkEmailProgressTotal">0</div>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="bdr-bulk-stat-card bdr-bulk-stat-card--compact text-center h-100">
              <div class="text-muted small"><?= h(t('bdr_distance_bulk_email_stat_processed', 'Diproses')) ?></div>
              <div class="fs-5 fw-semibold" id="bulkEmailProgressProcessed">0</div>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="bdr-bulk-stat-card bdr-bulk-stat-card--compact text-center h-100">
              <div class="text-muted small"><?= h(t('bdr_distance_bulk_email_stat_success', 'Berjaya')) ?></div>
              <div class="fs-5 fw-semibold text-success" id="bulkEmailProgressSuccess">0</div>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="bdr-bulk-stat-card bdr-bulk-stat-card--compact text-center h-100">
              <div class="text-muted small"><?= h(t('bdr_distance_bulk_email_stat_failed', 'Gagal')) ?></div>
              <div class="fs-5 fw-semibold text-danger" id="bulkEmailProgressFailed">0</div>
            </div>
          </div>
        </div>
        <div class="bdr-bulk-progress-message mb-0" id="bulkEmailProgressMessage"><?= h(t('bdr_distance_bulk_email_status_idle', 'Menunggu bulk email job dimulakan.')) ?></div>
      </div>
      <div class="modal-footer bdr-bulk-modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= h(t('bdr_distance_btn_close', 'Close')) ?></button>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  document.addEventListener('DOMContentLoaded', function(){
    document.body.classList.remove('loading');
    if (!window.jQuery || !jQuery.fn || !jQuery.fn.DataTable) {
      return;
    }

    const viewModalEl = document.getElementById('sampleViewModal');
    const viewModal = (window.bootstrap && viewModalEl) ? new bootstrap.Modal(viewModalEl) : null;
    const statsModalEl = document.getElementById('statsSummaryModal');
    const statsModal = (window.bootstrap && statsModalEl) ? new bootstrap.Modal(statsModalEl) : null;
    const distanceFilterModalEl = document.getElementById('distanceFilterModal');
    const distanceFilterModal = (window.bootstrap && distanceFilterModalEl) ? new bootstrap.Modal(distanceFilterModalEl) : null;
    const emailReminderModalEl = document.getElementById('emailReminderModal');
    const emailReminderModal = (window.bootstrap && emailReminderModalEl) ? new bootstrap.Modal(emailReminderModalEl) : null;
    const bulkEmailModalEl = document.getElementById('bulkEmailModal');
    const bulkEmailModal = (window.bootstrap && bulkEmailModalEl) ? new bootstrap.Modal(bulkEmailModalEl) : null;
    const bulkEmailProgressModalEl = document.getElementById('bulkEmailProgressModal');
    const bulkEmailProgressModal = (window.bootstrap && bulkEmailProgressModalEl) ? new bootstrap.Modal(bulkEmailProgressModalEl) : null;
    const tableSwitchLoader = document.getElementById('tableSwitchLoader');
    const departmentOptions = <?= json_encode(array_values($departmentOptions), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const currentDepartment = <?= json_encode((string)$currentDepartment, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const selectedDepartment = <?= json_encode((string)$selectedDepartment, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let activeSiteCode = <?= json_encode((string)$siteCode, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let activeOfficeLabel = <?= json_encode((string)$officeLabel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let activeOfficeAddress = <?= json_encode((string)$officeAddress, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    function destinationText(text) {
      const officeLabel = activeOfficeLabel || 'UPNM';
      const value = String(text || '');
      return value.indexOf('{{office_label}}') !== -1
        ? value.replace(/\{\{office_label\}\}/g, officeLabel)
        : value.replace(/UPNM/g, officeLabel);
    }
    function withActiveSite(url) {
      const separator = String(url || '').indexOf('?') === -1 ? '?' : '&';
      return String(url || '') + separator + 'site=' + encodeURIComponent(activeSiteCode);
    }
    function pageUrlWithSite(department, siteCode) {
      const params = new URLSearchParams();
      params.set('site', siteCode || activeSiteCode);
      if (typeof department === 'string' && department !== '' && department !== '__ALL__') {
        params.set('department', department);
      } else if (department === '__ALL__') {
        params.set('department', 'all');
      }
      const query = params.toString();
      return 'bdr-distance.php' + (query ? ('?' + query) : '');
    }
    let distanceLookupUrl = '';
    let distanceDetailUrl = '';
    let rowSnapshotUrl = '';
    let rowsDataBaseUrl = '';
    let exportXlsUrl = '';
    let emailReminderSendUrl = '';
    let bulkEmailStartUrl = '';
    let bulkEmailProcessUrl = '';
    let bulkEmailStatusUrl = '';
    let bulkEmailExclusionSaveUrl = '';
    function refreshActiveSiteUrls() {
      distanceLookupUrl = withActiveSite(<?= json_encode('bdr-distance.php?distance_lookup=1') ?>);
      distanceDetailUrl = withActiveSite(<?= json_encode('bdr-distance.php?distance_detail=1') ?>);
      rowSnapshotUrl = withActiveSite(<?= json_encode('bdr-distance.php?row_snapshot=1') ?>);
      rowsDataBaseUrl = withActiveSite(<?= json_encode('bdr-distance.php?rows_data=1') ?>);
      exportXlsUrl = withActiveSite(<?= json_encode('bdr-distance.php?export_xls=1') ?>);
      emailReminderSendUrl = withActiveSite(<?= json_encode('bdr-distance.php?email_reminder_send=1') ?>);
      bulkEmailStartUrl = withActiveSite(<?= json_encode('bdr-distance.php?bulk_email_start=1') ?>);
      bulkEmailProcessUrl = withActiveSite(<?= json_encode('bdr-distance.php?bulk_email_process=1') ?>);
      bulkEmailStatusUrl = withActiveSite(<?= json_encode('bdr-distance.php?bulk_email_status=1') ?>);
      bulkEmailExclusionSaveUrl = withActiveSite(<?= json_encode('bdr-distance.php?bulk_email_exclusion_save=1') ?>);
    }
    refreshActiveSiteUrls();
    const emailReminderTestRecipient = <?= json_encode($emailReminderTestRecipient, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const bulkEmailPollDelayMs = 5000;
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    const sampleViewInfoTabEl = document.getElementById('sample-view-info-tab');
    const sampleViewRouteTabEl = document.getElementById('sample-view-route-tab');
    const sampleRoutePreviewEl = document.getElementById('sampleRoutePreview');
    const distanceSourceMap = <?= json_encode([
      'route' => t('bdr_distance_source_route', 'Laluan'),
      'straight' => t('bdr_distance_source_straight', 'Garis Lurus'),
      'deferred_lookup' => t('bdr_distance_source_deferred', 'Belum Disemak'),
      'not_available' => t('bdr_distance_source_unavailable', 'Tidak Tersedia'),
      'address_only' => t('bdr_distance_source_address_only', 'Alamat Sahaja'),
      'cached_failed_lookup' => t('bdr_distance_source_failed_cache', 'Cache Gagal'),
      'google' => t('bdr_distance_provider_google', 'Google'),
      'geoapify' => t('bdr_distance_provider_geoapify', 'Geoapify'),
      'tomtom' => t('bdr_distance_provider_tomtom', 'TomTom'),
      'ors' => t('bdr_distance_provider_ors', 'OpenRouteService'),
      'osrm' => t('bdr_distance_provider_osrm', 'OSRM'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const debugReasonMap = <?= json_encode([
      'deferred_lookup' => t('bdr_distance_debug_deferred', 'Belum disemak lagi'),
      'address_empty' => t('bdr_distance_debug_address_empty', 'Tiada alamat'),
      'address_status_invalid' => t('bdr_distance_debug_address_invalid', 'Alamat tidak lengkap'),
      'lookup_request_failed' => t('bdr_distance_debug_lookup_failed', 'Permintaan lookup gagal'),
      'geocode_failed' => t('bdr_distance_debug_geocode_failed', 'Geocode gagal'),
      'route_failed' => t('bdr_distance_debug_route_failed', 'Laluan gagal dijana'),
      'low_match_quality' => t('bdr_distance_debug_low_match', 'Padanan lokasi rendah'),
      'postcode_mismatch' => t('bdr_distance_debug_postcode_mismatch', 'Poskod berbeza dengan provider'),
      'ok' => t('bdr_distance_debug_ok', 'Berjaya'),
      'loaded_from_cache' => t('bdr_distance_debug_loaded_cache', 'Dibaca dari cache'),
      'cached_failed_lookup' => t('bdr_distance_debug_cached_failed', 'Kiraan terdahulu gagal'),
      'address_changed' => t('bdr_distance_debug_address_changed', 'Alamat telah berubah'),
      'manual_calculation_required' => t('bdr_distance_debug_manual_required', 'Perlu kiraan manual'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const cacheOriginMap = <?= json_encode([
      'db' => t('bdr_distance_origin_db', 'DB'),
      'google_live' => t('bdr_distance_origin_google_live', 'Google Live'),
      'recalculated' => t('bdr_distance_origin_recalculated', 'Recalculated'),
      'none' => t('bdr_distance_origin_none', 'Pending'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const reviewLocationLabel = <?= json_encode(t('bdr_distance_status_review_location', 'Semak Lokasi')) ?>;
    let routeDistanceTitle = '';
    let straightDistanceTitle = '';
    let calculatingDistanceTitle = '';
    function refreshDestinationTitles() {
      routeDistanceTitle = destinationText(<?= json_encode(t('bdr_distance_msg_route', 'Jarak laluan pemanduan terdekat ke UPNM')) ?>);
      straightDistanceTitle = destinationText(<?= json_encode(t('bdr_distance_msg_straight', 'Anggaran jarak garis lurus ke UPNM')) ?>);
      calculatingDistanceTitle = destinationText(<?= json_encode(t('bdr_distance_msg_calculating', 'Sedang mengira jarak ke UPNM')) ?>);
    }
    refreshDestinationTitles();
    const issueLabelMap = <?= json_encode([
      'no_address' => t('bdr_distance_status_no_address', 'Tiada Alamat'),
      'incomplete_address' => t('bdr_distance_status_incomplete', 'Alamat tidak lengkap'),
      'address_changed' => t('bdr_distance_email_issue_address_changed', 'Alamat telah berubah'),
      'review_location' => t('bdr_distance_status_review_location', 'Semak Lokasi'),
      'state_mismatch' => t('bdr_distance_issue_state_mismatch', 'Negeri Tidak Sepadan'),
      'too_close' => t('bdr_distance_issue_too_close', 'Jarak Terlalu Dekat'),
      'too_far' => t('bdr_distance_issue_too_far', 'Jarak Terlalu Jauh'),
      'outside_allowed_region' => t('bdr_distance_issue_outside_region', 'Luar Kawasan Semakan'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const issueConfirmedTooltip = <?= json_encode(t('bdr_distance_status_confirmed_tooltip', 'Staf telah mengesahkan alamat ini.')) ?>;
    const notificationAllowedStates = ['SELANGOR', 'WILAYAH PERSEKUTUAN KUALA LUMPUR', 'WILAYAH PERSEKUTUAN PUTRAJAYA'];
    const notifyTooCloseKm = 1;
    const notifyTooFarKm = 100;

    function labelizeDistanceMeta(value, map) {
      const key = String(value || '').trim();
      if (!key) return '-';
      return map[key] || key;
    }

    function escapeHtml(value) {
      return String((value === undefined || value === null) ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function extractStateFromAddressText(value) {
      const text = String(value || '').toUpperCase();
      if (!text) return '';
      const stateMap = [
        ['WILAYAH PERSEKUTUAN KUALA LUMPUR', ['WILAYAH PERSEKUTUAN KUALA LUMPUR', 'KUALA LUMPUR']],
        ['WILAYAH PERSEKUTUAN PUTRAJAYA', ['WILAYAH PERSEKUTUAN PUTRAJAYA', 'PUTRAJAYA']],
        ['SELANGOR', ['SELANGOR']],
        ['NEGERI SEMBILAN', ['NEGERI SEMBILAN']],
        ['MELAKA', ['MELAKA', 'MALACCA']],
        ['JOHOR', ['JOHOR']],
        ['PERAK', ['PERAK']],
        ['KEDAH', ['KEDAH']],
        ['PULAU PINANG', ['PULAU PINANG', 'PENANG']],
        ['PERLIS', ['PERLIS']],
        ['KELANTAN', ['KELANTAN']],
        ['TERENGGANU', ['TERENGGANU']],
        ['PAHANG', ['PAHANG']],
        ['SABAH', ['SABAH']],
        ['SARAWAK', ['SARAWAK']],
        ['LABUAN', ['LABUAN', 'WILAYAH PERSEKUTUAN LABUAN']]
      ];

      for (const entry of stateMap) {
        const normalized = entry[0];
        const aliases = entry[1];
        if (aliases.some(function(alias) { return text.indexOf(alias) !== -1; })) {
          return normalized;
        }
      }

      return '';
    }

    function buildImmediateNotificationIssues(rowData) {
      const issues = [];
      const addressStatus = String((rowData && rowData.address_status) || '').trim().toUpperCase();
      const selectedState = extractStateFromAddressText((rowData && rowData.selected_state) || '');
      const detectedState = extractStateFromAddressText(
        (rowData && rowData.provider_display_name)
        || (rowData && rowData.matched_query)
        || (rowData && rowData.mailing_address)
        || ''
      );
      const distanceKm = Number.isFinite(Number(rowData && rowData.distance_km)) ? Number(rowData.distance_km) : NaN;
      const isReviewLocation = !!(rowData && rowData.is_review_location);
      const isAddressChanged = !!(rowData && rowData.is_address_changed);

      function appendIssue(type) {
        if (!type || issues.some(function(issue) { return issue.type === type; })) return;
        issues.push({
          type: type,
          label: getIssueLabelFromType(type),
          reason: '',
          excluded: false,
          exclusion_source: '',
          confirmed_at: '',
          confirmed_by_staff_no: ''
        });
      }

      if (addressStatus === 'EMPTY') appendIssue('no_address');
      if (addressStatus === 'INVALID') appendIssue('incomplete_address');
      if (isAddressChanged) appendIssue('address_changed');
      if (isReviewLocation) appendIssue('review_location');

      if (addressStatus === 'VALID' && !isAddressChanged && !isReviewLocation && Number.isFinite(distanceKm)) {
        if (distanceKm < notifyTooCloseKm) appendIssue('too_close');
        if (distanceKm > notifyTooFarKm) appendIssue('too_far');
      }

      if (addressStatus === 'VALID' && selectedState && detectedState && selectedState !== detectedState && notificationAllowedStates.indexOf(detectedState) !== -1) {
        appendIssue('state_mismatch');
      }

      if (addressStatus === 'VALID' && detectedState && notificationAllowedStates.indexOf(detectedState) === -1) {
        appendIssue('outside_allowed_region');
      }

      return {
        selectedState: selectedState,
        detectedState: detectedState,
        issues: issues,
        activeIssues: issues.filter(function(issue) { return issue.excluded !== true; }),
        confirmedIssues: []
      };
    }

    function isDistanceCalculatedText(text) {
      const value = String(text || '').trim();
      if (!value) return false;
      if (value === <?= json_encode(t('bdr_distance_status_failed', 'Gagal Kira')) ?>) return false;
      if (value === <?= json_encode(t('bdr_distance_status_calculating', 'Mengira...')) ?>) return false;
      if (value === <?= json_encode(t('bdr_distance_status_no_address', 'Tiada Alamat')) ?>) return false;
      if (value === <?= json_encode(t('bdr_distance_status_incomplete', 'Alamat tidak lengkap')) ?>) return false;
      if (value === <?= json_encode(t('bdr_distance_not_calculated', 'Belum Dikira')) ?>) return false;
      return /[0-9]/.test(value);
    }

    function resolveDistanceStateClass(text) {
      const value = String(text || '').trim();
      if (!value) return '';
      if (value === <?= json_encode(t('bdr_distance_status_no_address', 'Tiada Alamat')) ?>) return 'distance-no-address';
      if (value === <?= json_encode(t('bdr_distance_status_incomplete', 'Alamat tidak lengkap')) ?>) return 'distance-failed';
      if (value === <?= json_encode(t('bdr_distance_not_calculated', 'Belum Dikira')) ?>) return 'distance-failed';
      if (value === <?= json_encode(t('bdr_distance_status_calculating', 'Mengira...')) ?>) return 'distance-failed';
      if (value === <?= json_encode(t('bdr_distance_status_failed', 'Gagal Kira')) ?>) return 'distance-failed';
      if (value === reviewLocationLabel) return 'distance-failed';

      const kmValue = parseFloat(value.replace(/[^0-9.]/g, ''));
      if (!Number.isFinite(kmValue)) return '';
      if (kmValue > 8) return 'distance-far';
      if (kmValue === 8) return 'distance-equal';
      if (kmValue < 8) return 'distance-near';
      return '';
    }

    function isReviewDistanceText(text) {
      return String(text || '').trim() === reviewLocationLabel;
    }

    function rowNeedsReminder(row) {
      if (!row) return false;
      return String(row.dataset.showEmailReminder || '') === '1';
    }

    function getIssueTypeFromState(addressStatus, isAddressChanged, isReviewLocation) {
      const normalizedAddressStatus = String(addressStatus || '').trim().toUpperCase();
      if (normalizedAddressStatus === 'EMPTY') return 'no_address';
      if (normalizedAddressStatus === 'INVALID') return 'incomplete_address';
      if (isAddressChanged) return 'address_changed';
      if (isReviewLocation) return 'review_location';
      return 'none';
    }

    function getIssueLabelFromType(issueType) {
      const key = String(issueType || 'none');
      return issueLabelMap[key] || <?= json_encode(t('bdr_distance_status_no_issue', 'Tiada Isu')) ?>;
    }

    function getIssueBadgeClass(issueType) {
      switch (String(issueType || 'none')) {
        case 'no_address':
          return 'status-pill status-pill--no_address';
        case 'incomplete_address':
          return 'status-pill status-pill--incomplete_address';
        case 'address_changed':
          return 'status-pill status-pill--address_changed';
        case 'review_location':
          return 'status-pill status-pill--review_location';
        case 'state_mismatch':
          return 'status-pill status-pill--state_mismatch';
        case 'too_close':
          return 'status-pill status-pill--too_close';
        case 'too_far':
          return 'status-pill status-pill--too_far';
        case 'outside_allowed_region':
          return 'status-pill status-pill--outside_allowed_region';
        default:
          return 'status-pill status-pill--none';
      }
    }

    function renderIssueStatusHtml(issueType, isConfirmed) {
      const label = getIssueLabelFromType(issueType);
      const confirmedIcon = isConfirmed
        ? '<i class="ri-check-double-line status-confirmed-icon" data-bs-toggle="tooltip" data-bs-custom-class="template-tooltip" title="' + escapeHtml(issueConfirmedTooltip) + '"></i>'
        : '';
      const confirmedClass = isConfirmed ? ' status-pill--confirmed' : '';
      return '<span class="' + escapeHtml(getIssueBadgeClass(issueType) + confirmedClass) + '">' + escapeHtml(label) + confirmedIcon + '</span>';
    }

    function syncReminderButton(row) {
      if (!row) return;
      const reminderBtn = row.querySelector('.js-email-reminder');
      if (!reminderBtn) return;
      reminderBtn.style.display = rowNeedsReminder(row) ? 'inline-flex' : 'none';
    }

    function getRowPayloadData(row) {
      if (!row) return {};
      return parseRowPayload(row.querySelector('.js-view-row'));
    }

    function getRowNotificationIssues(row) {
      const rowData = getRowPayloadData(row);
      return Array.isArray(rowData.notification_issues) ? rowData.notification_issues : [];
    }

    function getActiveNotificationIssues(row) {
      return getRowNotificationIssues(row).filter(function(issue) {
        return issue && issue.excluded !== true;
      });
    }

    function isIssueSelfConfirmed(rowData, issueType) {
      const targetIssueType = String(issueType || '').trim();
      if (!targetIssueType) return false;
      const confirmedTypes = Array.isArray(rowData && rowData.confirmed_notification_issues)
        ? rowData.confirmed_notification_issues.map(function(type) { return String(type || '').trim(); })
        : [];
      if (confirmedTypes.indexOf(targetIssueType) !== -1) {
        return true;
      }
      const issues = Array.isArray(rowData && rowData.notification_issues) ? rowData.notification_issues : [];
      return issues.some(function(issue) {
        return String(issue && issue.type ? issue.type : '').trim() === targetIssueType
          && !!(issue && issue.excluded)
          && String(issue && issue.exclusion_source ? issue.exclusion_source : '').trim().toUpperCase() === 'SELF_CONFIRM';
      });
    }

    function getSelectedBulkEmailIssueTypes() {
      return Array.from(document.querySelectorAll('.js-bulk-issue-filter:checked')).map(function(node) {
        return String(node.value || '').trim();
      }).filter(Boolean);
    }

    function syncReviewRowState(row) {
      if (!row) return;
      const reviewDistanceCell = row.querySelector('.js-distance-cell');
      const distanceText = String(reviewDistanceCell ? reviewDistanceCell.textContent : '').trim();
      const needsReview = String(row.dataset.reviewLocation || '') === '1'
        || String(row.dataset.matchQuality || '').toUpperCase() === 'LOW'
        || String(row.dataset.debugReason || '') === 'low_match_quality'
        || isReviewDistanceText(distanceText);
      const isAddressChanged = String(row.dataset.addressChanged || '') === '1' || String(row.dataset.debugReason || '') === 'address_changed';
      row.dataset.reviewLocation = needsReview ? '1' : '0';
      row.classList.remove('table-danger-soft', 'table-warning-soft');
      if (needsReview) {
        row.classList.remove('table-distance-near-soft');
        row.classList.add('table-danger-soft');
      } else if (isAddressChanged) {
        row.classList.remove('table-distance-near-soft');
        row.classList.add('table-warning-soft');
      }
      syncReminderButton(row);
    }

    function measureDepartmentFilterWidth(options, placeholder, minWidth = 260, maxWidth = 520) {
      const probe = document.createElement('span');
      probe.style.visibility = 'hidden';
      probe.style.position = 'fixed';
      probe.style.left = '-9999px';
      probe.style.top = '-9999px';
      probe.style.whiteSpace = 'pre';
      probe.style.fontSize = '14px';
      probe.style.fontFamily = 'inherit';
      probe.style.fontWeight = '400';
      document.body.appendChild(probe);

      let longest = String(placeholder || '');
      options.forEach(function(optionText) {
        const text = String(optionText || '');
        if (text.length > longest.length) {
          longest = text;
        }
      });

      probe.textContent = longest;
      const width = Math.ceil(probe.getBoundingClientRect().width + 86);
      probe.remove();

      return Math.max(minWidth, Math.min(width, maxWidth));
    }

    function applyDepartmentFilterWidth($select, widthPx) {
      if (!$select || !$select.length) return;
      $select.css({
        width: widthPx + 'px',
        minWidth: widthPx + 'px',
        maxWidth: widthPx + 'px'
      });

      const $container = $select.next('.select2-container');
      if ($container.length) {
        $container.css({
          width: widthPx + 'px',
          minWidth: widthPx + 'px',
          maxWidth: widthPx + 'px'
        });
      }
    }

    const dt = jQuery('#userDT').DataTable({
      pageLength: 10,
      lengthChange: true,
      lengthMenu: [10, 25, 50, 100, 200],
      ordering: true,
      order: [[1,'asc']],
      autoWidth: false,
      scrollX: false,
      dom:
        '<"row mb-2"<"col-sm-12 col-md-6 dt-top-left"l><"col-sm-12 col-md-6 d-flex justify-content-md-end dt-top-right"f>>' +
        't' +
        '<"dt-bottom-row mt-2 d-flex justify-content-between align-items-center"<"dt-info-left"i><"dt-paging-right d-flex justify-content-end"p>>',
      language: {
        lengthMenu: <?= json_encode(t('bdr_distance_dt_length_menu', 'Papar _MENU_ rekod')) ?>,
        search: '',
        info: <?= json_encode(t('bdr_distance_dt_info', 'Memaparkan _START_ hingga _END_ daripada _TOTAL_ rekod')) ?>,
        infoEmpty: <?= json_encode(t('bdr_distance_dt_info_empty', 'Memaparkan 0 hingga 0 daripada 0 rekod')) ?>,
        emptyTable: <?= json_encode(t('bdr_distance_dt_empty', 'Tiada rekod staf aktif ditemui')) ?>,
        paginate: {
          previous: <?= json_encode(t('bdr_distance_dt_previous', 'Sebelum')) ?>,
          next: <?= json_encode(t('bdr_distance_dt_next', 'Seterusnya')) ?>
        },
        zeroRecords: <?= json_encode(t('bdr_distance_dt_zero_records', 'Tiada rekod sepadan ditemui')) ?>
      },
      columns: [
        { width: '5%', orderable: false, searchable: false },
        { width: '20%' },
        { width: '10%' },
        { width: '32%' },
        { width: '13%' },
        { width: '10%' },
        { width: '10%', orderable: false, searchable: false }
      ],
      rowCallback: function(row, data, displayIndex){
        const api = this.api();
        const info = api.page.info();
        jQuery('td:eq(0)', row).text(info.start + displayIndex + 1);
      },
      initComplete: function() {
        if (window.DataTableStandard && typeof window.DataTableStandard.decorate === 'function') {
          window.DataTableStandard.decorate('#userDT', {
            searchPlaceholder: <?= json_encode(t('bdr_distance_dt_search_placeholder', 'Cari staf')) ?>
          });
        }
      }
    });

    const $topLeft = jQuery('#userDT_wrapper .dt-top-left').addClass('d-flex align-items-center gap-2 flex-nowrap');
    const $topRight = jQuery('#userDT_wrapper .dt-top-right').addClass('d-flex align-items-center justify-content-end gap-1 flex-nowrap');
    const $filter = jQuery('#userDT_filter');
    jQuery('#dtDepartmentFilter').remove();
    jQuery('#dtIssueFilter').remove();
    jQuery('#dtDistanceFilter').remove();
    const $dept = jQuery('<select id="dtDepartmentFilter" class="form-select dt-group-filter"><option value="__ALL__"><?= h(t('bdr_distance_department_filter_placeholder', 'Semua Jabatan')) ?></option></select>');
    const $issue = jQuery('<select id="dtIssueFilter" class="form-select dt-group-filter dt-issue-filter"><option value="all"><?= h(t('bdr_distance_filter_issue_all', 'Semua Isu')) ?></option><option value="no_address"><?= h(t('bdr_distance_status_no_address', 'Tiada Alamat')) ?></option><option value="incomplete_address"><?= h(t('bdr_distance_status_incomplete', 'Alamat tidak lengkap')) ?></option><option value="review_location"><?= h(t('bdr_distance_status_review_location', 'Semak Lokasi')) ?></option><option value="address_changed"><?= h(t('bdr_distance_email_issue_address_changed', 'Alamat telah berubah')) ?></option><option value="state_mismatch"><?= h(t('bdr_distance_issue_state_mismatch', 'Negeri Tidak Sepadan')) ?></option><option value="too_close"><?= h(t('bdr_distance_issue_too_close', 'Jarak Terlalu Dekat')) ?></option><option value="too_far"><?= h(t('bdr_distance_issue_too_far', 'Jarak Terlalu Jauh')) ?></option><option value="outside_allowed_region"><?= h(t('bdr_distance_issue_outside_region', 'Luar Kawasan Semakan')) ?></option></select>');
    const $distance = jQuery('<select id="dtDistanceFilter" class="form-select dt-group-filter dt-distance-filter"><option value="all"><?= h(t('bdr_distance_filter_all', 'Semua')) ?></option><option value="le8"><?= h(t('bdr_distance_filter_distance_le8_bdr', '≤ 8 KM (Tidak Layak Bekerja Dari Rumah)')) ?></option><option value="gt8"><?= h(t('bdr_distance_filter_distance_gt8_bdr', '> 8 KM (Layak Bekerja Dari Rumah)')) ?></option><option value="custom"><?= h(t('bdr_distance_filter_custom', 'Custom Filter')) ?></option></select>');
    const departmentPlaceholder = <?= json_encode(t('bdr_distance_department_filter_placeholder', 'Semua Jabatan')) ?>;
    const departmentFilterWidth = measureDepartmentFilterWidth(departmentOptions, departmentPlaceholder);
    const distancePlaceholder = <?= json_encode(t('bdr_distance_filter_distance_placeholder', 'Semua Jarak')) ?>;
    const distanceFilterWidth = measureDepartmentFilterWidth([
      <?= json_encode(t('bdr_distance_filter_all', 'Semua')) ?>,
      <?= json_encode(t('bdr_distance_filter_distance_le8_bdr', '≤ 8 KM (Tidak Layak Bekerja Dari Rumah)')) ?>,
      <?= json_encode(t('bdr_distance_filter_distance_gt8_bdr', '> 8 KM (Layak Bekerja Dari Rumah)')) ?>,
      <?= json_encode(t('bdr_distance_filter_custom', 'Custom Filter')) ?>
    ], distancePlaceholder, 118, 170);
    const issuePlaceholder = <?= json_encode(t('bdr_distance_filter_issue_all', 'Semua Isu')) ?>;
    const issueFilterWidth = measureDepartmentFilterWidth([
      <?= json_encode(t('bdr_distance_status_no_address', 'Tiada Alamat')) ?>,
      <?= json_encode(t('bdr_distance_status_incomplete', 'Alamat tidak lengkap')) ?>,
      <?= json_encode(t('bdr_distance_status_review_location', 'Semak Lokasi')) ?>,
      <?= json_encode(t('bdr_distance_email_issue_address_changed', 'Alamat telah berubah')) ?>
    ], issuePlaceholder, 154, 225);

    departmentOptions.forEach(function(name) {
      if (!name) return;
      $dept.append(new Option(String(name), String(name)));
    });

    if ($filter.length) {
      const $searchLabel = $filter.find('label');
      if ($searchLabel.length) {
        $searchLabel.before($dept);
        $dept.after($issue);
        $issue.after($distance);
      } else {
        $filter.prepend($dept);
        $dept.after($issue);
        $issue.after($distance);
      }
    } else {
      $topRight.append($dept);
      $topRight.append($issue);
      $topRight.append($distance);
    }

    function parseRowPayload(btn) {
      if (!btn) return {};
      try {
        return JSON.parse(btn.getAttribute('data-row') || '{}');
      } catch (err) {
        return {};
      }
    }

    function updateRowPayload(row, payload) {
      if (!row) return Object.assign({}, payload || {});
      const viewBtn = row.querySelector('.js-view-row');
      const existing = parseRowPayload(viewBtn);
      const merged = Object.assign({}, existing || {}, payload || {});
      const rowIndex = parseInt(String(row.getAttribute('data-row-index') || ''), 10);
      if (!Number.isFinite(Number(merged.id)) || Number(merged.id) <= 0) {
        merged.id = Number.isFinite(rowIndex) ? (rowIndex + 1) : Number(existing.id || 0) || 0;
      }
      if (!merged.site_code) {
        merged.site_code = activeSiteCode;
      }
      if (!merged.office_label) {
        merged.office_label = activeOfficeLabel;
      }
      if (!merged.office_address) {
        merged.office_address = activeOfficeAddress;
      }
      if (viewBtn) {
        viewBtn.setAttribute('data-row', JSON.stringify(merged));
      }
      return merged;
    }

    function findRowByStaffNo(staffNo) {
      const targetStaffNo = String(staffNo || '').trim();
      if (!targetStaffNo) return null;
      return dt.rows({ search: 'none' }).nodes().toArray().find(function(row) {
        return String(row.getAttribute('data-staf-id') || '').trim() === targetStaffNo;
      }) || null;
    }

    function applyLookupPayloadFallback(row, payload) {
      if (!row || !payload) return null;
      const existing = getRowPayloadData(row);
      const addressStatus = String(row.getAttribute('data-address-status') || (existing && existing.address_status) || '').trim();
      const normalizedMatchQuality = String(payload.match_quality || '').trim();
      const normalizedDebugReason = String(payload.debug_reason || '').trim();
      const isReviewLocation = !!payload.is_review_location
        || normalizedMatchQuality.toUpperCase() === 'LOW'
        || normalizedDebugReason === 'low_match_quality';
      const basePayload = Object.assign({}, existing || {}, {
        address_status: addressStatus,
        distance_km: Object.prototype.hasOwnProperty.call(payload, 'distance_km') ? payload.distance_km : (existing ? existing.distance_km : null),
        distance_label: String(payload.distance_label || ''),
        distance_source: String(payload.distance_source || ''),
        route_provider: String(payload.route_provider || ''),
        route_points: Array.isArray(payload.route_points) ? payload.route_points : [],
        cache_origin: String(payload.cache_origin || 'none'),
        matched_query: String(payload.matched_query || ''),
        provider_display_name: String(payload.provider_display_name || payload.matched_query || ''),
        home_coords_label: String(payload.home_coords_label || ''),
        direction_url: String(payload.direction_url || ''),
        match_quality: normalizedMatchQuality,
        debug_reason: normalizedDebugReason,
        distance_attempted: payload.distance_attempted !== false,
        is_review_location: isReviewLocation,
        is_address_changed: false,
        site_code: String((existing && existing.site_code) || activeSiteCode || ''),
        office_label: String((existing && existing.office_label) || activeOfficeLabel || ''),
        office_address: String((existing && existing.office_address) || activeOfficeAddress || ''),
        selected_state: String((existing && existing.selected_state) || row.getAttribute('data-negeri') || ''),
        mailing_address: String((existing && existing.mailing_address) || row.getAttribute('data-address') || ''),
        notification_address_hash: String((existing && existing.notification_address_hash) || row.getAttribute('data-address-hash') || '')
      });

      const notificationState = buildImmediateNotificationIssues(basePayload);
      const fallbackPayload = Object.assign({}, basePayload, {
        selected_state: notificationState.selectedState || basePayload.selected_state || '',
        detected_state: notificationState.detectedState || '',
        notification_issues: notificationState.issues,
        active_notification_issues: notificationState.activeIssues,
        excluded_notification_issues: [],
        confirmed_notification_issues: notificationState.confirmedIssues,
        issue_type: notificationState.activeIssues.length ? String(notificationState.activeIssues[0].type || 'none') : 'none',
        issue_label: notificationState.activeIssues.length ? String(notificationState.activeIssues[0].label || '') : getIssueLabelFromType('none'),
        show_email_reminder: notificationState.activeIssues.length > 0
      });

      return updateRowPayload(row, fallbackPayload);
    }

    function buildCanonicalDistanceCellPayload(rowData) {
      const addressStatus = String(rowData && rowData.address_status ? rowData.address_status : '').trim().toUpperCase();
      const isReviewLocation = !!(rowData && rowData.is_review_location)
        || String(rowData && rowData.match_quality ? rowData.match_quality : '').trim().toUpperCase() === 'LOW'
        || String(rowData && rowData.debug_reason ? rowData.debug_reason : '').trim() === 'low_match_quality';
      let distanceLabel = String(rowData && rowData.distance_label ? rowData.distance_label : '').trim();
      let title = <?= json_encode(t('bdr_distance_msg_unknown', 'Jarak belum dapat ditentukan')) ?>;

      if (addressStatus === 'EMPTY') {
        distanceLabel = <?= json_encode(t('bdr_distance_status_no_address', 'Tiada Alamat')) ?>;
        title = <?= json_encode(t('bdr_distance_msg_no_address', 'Jarak tidak dapat dikira kerana tiada alamat')) ?>;
      } else if (addressStatus === 'INVALID') {
        distanceLabel = <?= json_encode(t('bdr_distance_status_incomplete', 'Alamat tidak lengkap')) ?>;
        title = <?= json_encode(t('bdr_distance_status_incomplete', 'Alamat tidak lengkap')) ?>;
      } else if (isReviewLocation) {
        distanceLabel = reviewLocationLabel;
        title = <?= json_encode(t('bdr_distance_msg_low_match', 'Lokasi geocode meragukan. Sila semak alamat dan koordinat.')) ?>;
      } else if (distanceLabel) {
        title = String(rowData && rowData.distance_source ? rowData.distance_source : '') === 'route'
          ? routeDistanceTitle
          : straightDistanceTitle;
      } else if (rowData && rowData.distance_attempted) {
        distanceLabel = <?= json_encode(t('bdr_distance_status_failed', 'Gagal Kira')) ?>;
        title = <?= json_encode(t('bdr_distance_msg_failed_cached', 'Kiraan terdahulu gagal. Klik butang Kira untuk cuba semula')) ?>;
      } else {
        distanceLabel = <?= json_encode(t('bdr_distance_not_calculated', 'Belum Dikira')) ?>;
        title = <?= json_encode(t('bdr_distance_msg_checking_cache', 'Sedang menyemak cache jarak')) ?>;
      }

      return Object.assign({}, rowData || {}, {
        distance_label: distanceLabel,
        title: title,
        is_review_location: isReviewLocation
      });
    }

    function syncRowDatasetsFromPayload(row, payload) {
      if (!row || !payload) return;
      const addressInputs = payload.address_inputs && typeof payload.address_inputs === 'object' ? payload.address_inputs : {};
      row.setAttribute('data-site-code', String(payload.site_code || activeSiteCode || ''));
      row.setAttribute('data-department', String(payload.department || ''));
      row.setAttribute('data-staf-id', String(payload.staff_no || row.getAttribute('data-staf-id') || ''));
      row.setAttribute('data-email', String(payload.email || row.getAttribute('data-email') || ''));
      row.setAttribute('data-address', String(payload.mailing_address || ''));
      row.setAttribute('data-address-status', String(payload.address_status || row.getAttribute('data-address-status') || ''));
      row.setAttribute('data-address-hash', String(payload.notification_address_hash || ''));
      row.setAttribute('data-detected-state', String(payload.detected_state || ''));
      row.setAttribute('data-alamat1', String(addressInputs.alamat1 || ''));
      row.setAttribute('data-alamat2', String(addressInputs.alamat2 || ''));
      row.setAttribute('data-alamat3', String(addressInputs.alamat3 || ''));
      row.setAttribute('data-poskod', String(addressInputs.poskod || ''));
      row.setAttribute('data-negeri', String(addressInputs.negeri || ''));
      row.setAttribute('data-negara', String(addressInputs.negara || ''));

      const cell = row.querySelector('.js-distance-cell');
      if (cell) {
        updateDistanceCell(cell, buildCanonicalDistanceCellPayload(payload));
      }
    }

    function refreshOpenViewModalForRow(row) {
      if (!viewModalEl || !row || !viewModalEl.classList.contains('show')) return;
      const activeRowIndex = String(viewModalEl.dataset.activeRowIndex || '').trim();
      const rowIndex = String(row.getAttribute('data-row-index') || '').trim();
      if (!activeRowIndex || activeRowIndex !== rowIndex) return;
      openViewModal(getRowPayloadData(row), row, {
        keepOpen: true,
        preserveActiveTab: true
      });
    }

    function replaceRowWithPayload(row, payload) {
      if (!row || !payload) return null;
      const rowIndexAttr = String(row.getAttribute('data-row-index') || '').trim();
      const rowIndex = Number.isFinite(Number(rowIndexAttr)) ? Number(rowIndexAttr) : 0;
      const wrappedPayload = Object.assign({}, payload || {}, {
        id: rowIndex + 1
      });
      const wrapper = document.createElement('tbody');
      wrapper.innerHTML = buildRowHtml(wrappedPayload, rowIndex);
      const replacementRow = wrapper.firstElementChild;
      if (!replacementRow) return row;

      const rowApi = dt.row(row);
      rowApi.remove();
      dt.row.add(replacementRow);
      dt.draw(false);
      rebuildTooltips(document);

      const resolvedRow = findRowByStaffNo(String(payload.staff_no || row.getAttribute('data-staf-id') || '')) || replacementRow;
      refreshOpenViewModalForRow(resolvedRow);
      return resolvedRow;
    }

    function syncCanonicalRowState(row, payload) {
      if (!row || !payload) return null;
      return replaceRowWithPayload(row, payload);
    }

    function fetchCanonicalRowSnapshot(row) {
      if (!row) return Promise.resolve(null);
      const staffNo = String(row.getAttribute('data-staf-id') || '').trim();
      if (!staffNo) return Promise.resolve(null);

      const formData = new FormData();
      formData.append('staff_no', staffNo);
      formData.append('site_code', activeSiteCode);

      return fetch(rowSnapshotUrl, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(function(response) {
        return response.json().catch(function() { return null; }).then(function(payload) {
          if (!response.ok || !payload || payload.success !== true || !payload.item) {
            return null;
          }
          return payload.item;
        });
      })
      .catch(function() {
        return null;
      });
    }

    let departmentFilterValue = '';
    let issueFilterValue = 'all';
    const distanceFilterState = {
      mode: 'all',
      preset: 'all',
      custom: null
    };
    let rowsLoading = false;
    let currentLoadedDepartment = '';
    let queuedDepartmentLoad = null;
    let queuedSiteLoad = null;
    let loaderTimer = null;

    function setTableSwitchLoaderActive(active) {
      if (!tableSwitchLoader) return;
      tableSwitchLoader.classList.toggle('is-active', !!active);
      tableSwitchLoader.setAttribute('aria-hidden', active ? 'false' : 'true');
    }

    function navigateWithTableLoader(nextUrl) {
      setTableSwitchLoaderActive(true);
      window.setTimeout(function() {
        window.location.href = nextUrl;
      }, 40);
    }

    jQuery.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
      if (!settings || !settings.nTable || settings.nTable.id !== 'userDT') return true;
      const rowNode = dt.row(dataIndex).node();
      const rowDepartment = rowNode ? String(rowNode.getAttribute('data-department') || '') : '';
      const rowIssueType = rowNode ? String(rowNode.getAttribute('data-issue-type') || 'none') : 'none';
      const filterDistanceCell = rowNode ? rowNode.querySelector('.js-distance-cell') : null;
      const kmText = filterDistanceCell ? String(filterDistanceCell.dataset.distanceKm || '') : '';
      const kmValue = parseFloat(kmText);

      if (departmentFilterValue && rowDepartment !== departmentFilterValue) {
        return false;
      }

      if (issueFilterValue !== 'all' && rowIssueType !== issueFilterValue) {
        return false;
      }

      if (distanceFilterState.mode === 'all') {
        return true;
      }

      if (!Number.isFinite(kmValue)) {
        return false;
      }

      return evaluateDistanceFilter(kmValue);
    });

    function rebuildTooltips(scope) {
      if (!window.bootstrap) return;
      (scope || document).querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(element) {
        const existing = bootstrap.Tooltip.getInstance(element);
        if (existing) {
          existing.dispose();
        }
        new bootstrap.Tooltip(element, {
          container: 'body',
          boundary: document.body,
          placement: 'top',
          fallbackPlacements: ['top', 'bottom', 'left', 'right']
        });
      });
    }

    function setReminderButtonLoading(button, loading, label) {
      if (!button) return;
      if (loading) {
        button.disabled = true;
        if (!button.dataset.originalHtml) {
          button.dataset.originalHtml = button.innerHTML;
        }
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' + escapeHtml(label || '');
        return;
      }
      button.disabled = false;
      if (button.dataset.originalHtml) {
        button.innerHTML = button.dataset.originalHtml;
        delete button.dataset.originalHtml;
      }
    }

    function getReminderIssueLabel(row) {
      if (!row) return '';
      const labels = getActiveNotificationIssues(row).map(function(issue) {
        return String(issue.label || getIssueLabelFromType(issue.type || 'none')).trim();
      }).filter(Boolean);
      return labels.length ? labels.join(', ') : <?= json_encode(t('bdr_distance_email_issue_default', 'Semakan alamat BDR diperlukan')) ?>;
    }

    function getReminderIssueReason(row) {
      if (!row) return '';
      const rowData = getRowPayloadData(row);
      const reasons = getActiveNotificationIssues(row).map(function(issue) {
        return String(issue.reason || '').trim();
      }).filter(Boolean);
      if (rowData && rowData.provider_display_name) {
        reasons.push(<?= json_encode(t('bdr_distance_email_reason_provider_match', 'Alamat yang dipadankan oleh Google Maps:')) ?> + ' ' + String(rowData.provider_display_name).trim());
      }
      if (rowData && rowData.match_quality) {
        reasons.push(<?= json_encode(t('bdr_distance_email_reason_match_quality', 'Kualiti padanan:')) ?> + ' ' + String(rowData.match_quality).trim());
      }
      if (rowData && rowData.detected_state) {
        reasons.push(<?= json_encode(t('bdr_distance_email_reason_detected_state', 'Negeri dikesan:')) ?> + ' ' + String(rowData.detected_state).trim());
      }
      const activeIssueTypes = getActiveNotificationIssues(row).map(function(issue) {
        return String(issue.type || '').trim();
      }).filter(Boolean);
      if (activeIssueTypes.indexOf('state_mismatch') !== -1) {
        const selectedState = String((rowData && rowData.selected_state) || row.getAttribute('data-negeri') || '').trim();
        const detectedState = String((rowData && rowData.detected_state) || row.getAttribute('data-detected-state') || '').trim();
        if (selectedState) {
          reasons.push(<?= json_encode(t('bdr_distance_email_reason_selected_state', 'Negeri dalam rekod:')) ?> + ' ' + selectedState);
        }
        if (detectedState) {
          reasons.push(<?= json_encode(t('bdr_distance_email_reason_address_state', 'Negeri yang dikesan daripada alamat:')) ?> + ' ' + detectedState);
        }
      }
      if (!reasons.length) {
        const defaultReason = <?= json_encode(t('bdr_distance_email_reason_default', 'Maklumat alamat semasa memerlukan semakan semula bagi membolehkan sistem mengesahkan jarak rumah ke UPNM dengan tepat.')) ?>;
        return destinationText(defaultReason);
      }
      return reasons.map(function(reason) {
        const normalized = String(reason || '').trim().replace(/^[-*•]\s*/u, '').replace(/[.]+$/, '');
        return '- ' + normalized + '.';
      }).join('\n');
    }

    function getReminderPayloadFromRow(row) {
      if (!row) return null;
      const rowData = parseRowPayload(row.querySelector('.js-view-row'));
      const distanceCell = row.querySelector('.js-distance-cell');
      return {
        staff_name: String((rowData && rowData.name) || '').trim(),
        staff_no: String((rowData && rowData.staff_no) || row.getAttribute('data-staf-id') || '').trim(),
        recipient_email: String((rowData && rowData.email) || row.getAttribute('data-email') || '').trim(),
        department: String((rowData && rowData.department) || row.getAttribute('data-department') || '').trim(),
        mailing_address: String((rowData && rowData.mailing_address) || row.getAttribute('data-address') || '').trim(),
        distance_label: String(distanceCell ? distanceCell.textContent : ((rowData && rowData.distance_label) || '')).trim(),
        provider_matched_address: String((rowData && rowData.provider_display_name) || (rowData && rowData.matched_query) || '').trim(),
        match_quality: String((rowData && rowData.match_quality) || '').trim(),
        selected_state: String((rowData && rowData.selected_state) || row.getAttribute('data-negeri') || '').trim(),
        detected_state: String((rowData && rowData.detected_state) || row.getAttribute('data-detected-state') || '').trim(),
        issue_label: getReminderIssueLabel(row),
        issue_reason: getReminderIssueReason(row),
        notification_issues: getActiveNotificationIssues(row),
        all_notification_issues: getRowNotificationIssues(row),
        address_hash: String((rowData && rowData.notification_address_hash) || row.getAttribute('data-address-hash') || '').trim()
      };
    }

    function openEmailReminderModal(row) {
      if (!emailReminderModal || !row) return;
      const payload = getReminderPayloadFromRow(row);
      if (!payload) return;

      emailReminderModalEl.dataset.staffName = payload.staff_name;
      emailReminderModalEl.dataset.staffNo = payload.staff_no;
      emailReminderModalEl.dataset.recipientEmail = payload.recipient_email;
      emailReminderModalEl.dataset.department = payload.department;
      emailReminderModalEl.dataset.mailingAddress = payload.mailing_address;
      emailReminderModalEl.dataset.distanceLabel = payload.distance_label;
      emailReminderModalEl.dataset.issueLabel = payload.issue_label;
      emailReminderModalEl.dataset.issueReason = payload.issue_reason;
      emailReminderModalEl.dataset.providerMatchedAddress = payload.provider_matched_address;
      emailReminderModalEl.dataset.matchQuality = payload.match_quality;
      emailReminderModalEl.dataset.addressHash = payload.address_hash || '';
      emailReminderModalEl.dataset.notificationIssueTypes = JSON.stringify(Array.isArray(payload.notification_issues) ? payload.notification_issues.map(function(issue) {
        return String(issue && issue.type ? issue.type : '').trim();
      }).filter(Boolean) : []);

      const setValue = function(id, value) {
        const node = document.getElementById(id);
        if (node) {
          node.value = value || '';
        }
      };
      const setText = function(id, value) {
        const node = document.getElementById(id);
        if (node) {
          node.textContent = value || '-';
        }
      };

      setValue('emailReminderStaffName', payload.staff_name);
      setValue('emailReminderStaffNo', payload.staff_no);
      setValue('emailReminderDepartment', payload.department);
      setValue('emailReminderRecipientEmail', payload.recipient_email);
      setValue('emailReminderIssueLabel', payload.issue_label);
      setValue('emailReminderAddress', payload.mailing_address);
      setValue('emailReminderDistanceLabel', payload.distance_label);
      setText('emailReminderSummaryName', payload.staff_name);
      setText('emailReminderSummaryIssue', payload.issue_label);
      setText('emailReminderSummaryEmail', payload.recipient_email);
      setText('emailReminderSummaryDistance', payload.distance_label);
      setText('emailReminderSummaryDepartment', payload.department);
      setText('emailReminderSummaryStaffNo', payload.staff_no);
      emailReminderModal.show();
    }

    async function requestEmailReminder(endpointUrl) {
      if (!emailReminderModalEl) return;
      const formData = new FormData();
      formData.append('csrf_token', csrfToken);
      formData.append('staff_name', String(emailReminderModalEl.dataset.staffName || ''));
      formData.append('staff_no', String(emailReminderModalEl.dataset.staffNo || ''));
      formData.append('recipient_email', String(emailReminderModalEl.dataset.recipientEmail || document.getElementById('emailReminderRecipientEmail').value || ''));
      formData.append('department', String(emailReminderModalEl.dataset.department || ''));
      formData.append('mailing_address', String(emailReminderModalEl.dataset.mailingAddress || ''));
      formData.append('distance_label', String(emailReminderModalEl.dataset.distanceLabel || ''));
      formData.append('issue_label', String(emailReminderModalEl.dataset.issueLabel || ''));
      formData.append('issue_reason', String(emailReminderModalEl.dataset.issueReason || ''));
      formData.append('site_code', activeSiteCode);
      formData.append('provider_matched_address', String(emailReminderModalEl.dataset.providerMatchedAddress || ''));
      formData.append('match_quality', String(emailReminderModalEl.dataset.matchQuality || ''));
      formData.append('address_hash', String(emailReminderModalEl.dataset.addressHash || ''));
      formData.append('notification_issue_types', String(emailReminderModalEl.dataset.notificationIssueTypes || '[]'));

      const response = await fetch(endpointUrl, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': csrfToken
        },
        body: formData
      });

      const payload = await response.json();
      if (!response.ok || !payload || payload.success !== true) {
        throw new Error(String((payload && payload.message) || 'request_failed'));
      }
      return payload;
    }

    const openBulkEmailModalBtn = document.getElementById('openBulkEmailModal');
    const bulkEmailStartBtn = document.getElementById('bulkEmailStartBtn');
    const bulkEmailIssueOptionsEl = document.getElementById('bulkEmailIssueOptions');
    const bulkEmailIssueEmptyTextEl = document.getElementById('bulkEmailIssueEmptyText');
    const sampleViewSaveExclusionBtn = document.getElementById('sampleViewSaveExclusionBtn');
    let bulkEmailPollTimer = null;
    let activeBulkEmailJobId = 0;
    let activeBulkEmailItems = [];
    let activeBulkEmailIssueTypes = [];

    function isValidEmailAddress(value) {
      const email = String(value || '').trim();
      if (!email) return false;
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function clearBulkEmailPoll() {
      if (bulkEmailPollTimer) {
        window.clearTimeout(bulkEmailPollTimer);
        bulkEmailPollTimer = null;
      }
    }

    function collectBulkEmailCandidates() {
      const rows = collectFilteredRows();
      const selectedIssueTypes = getSelectedBulkEmailIssueTypes();
      const issueRows = rows.filter(function(row) {
        return getActiveNotificationIssues(row).some(function(issue) {
          return selectedIssueTypes.length === 0 || selectedIssueTypes.indexOf(String(issue.type || '')) !== -1;
        });
      });
      const dedupe = new Set();
      const eligibleItems = [];
      const issueCounts = {};

      issueRows.forEach(function(row) {
        const payload = getReminderPayloadFromRow(row);
        if (!payload) {
          return;
        }

        const matchedIssues = payload.notification_issues.filter(function(issue) {
          return selectedIssueTypes.length === 0 || selectedIssueTypes.indexOf(String(issue.type || '')) !== -1;
        });
        if (!matchedIssues.length) {
          return;
        }

        const recipientEmail = String(payload.recipient_email || '').trim().toLowerCase();
        if (!isValidEmailAddress(recipientEmail)) {
          return;
        }

        const dedupeKey = payload.staff_no
          ? ('staff:' + String(payload.staff_no).trim())
          : ('email:' + recipientEmail);
        if (dedupe.has(dedupeKey)) {
          return;
        }
        dedupe.add(dedupeKey);

        matchedIssues.forEach(function(issue) {
          const type = String(issue.type || '').trim();
          if (!type) return;
          issueCounts[type] = Number(issueCounts[type] || 0) + 1;
        });

        eligibleItems.push({
          staff_name: String(payload.staff_name || '').trim(),
          staff_no: String(payload.staff_no || '').trim(),
          recipient_email: recipientEmail,
          department: String(payload.department || '').trim(),
          mailing_address: String(payload.mailing_address || '').trim(),
          distance_label: String(payload.distance_label || '').trim(),
          issue_label: matchedIssues.map(function(issue) { return String(issue.label || '').trim(); }).filter(Boolean).join(', '),
          issue_reason: String(payload.issue_reason || '').trim(),
          issue_types: matchedIssues.map(function(issue) { return String(issue.type || '').trim(); }).filter(Boolean)
        });
      });

      return {
        filtered_count: rows.length,
        issue_count: issueRows.length,
        eligible_count: eligibleItems.length,
        skipped_count: Math.max(0, issueRows.length - eligibleItems.length),
        eligible_items: eligibleItems,
        issue_counts: issueCounts
      };
    }

    function renderBulkEmailIssueOptions(summary) {
      if (!bulkEmailIssueOptionsEl) return;
      const counts = summary && summary.issue_counts ? summary.issue_counts : {};
      const issueTypes = Object.keys(counts).sort();
      const selectedSet = new Set(activeBulkEmailIssueTypes);
      bulkEmailIssueOptionsEl.innerHTML = '';
      if (bulkEmailIssueEmptyTextEl) {
        bulkEmailIssueEmptyTextEl.style.display = issueTypes.length ? 'none' : '';
      }
      issueTypes.forEach(function(type) {
        const label = getIssueLabelFromType(type);
        const checked = selectedSet.size === 0 || selectedSet.has(type);
        const option = document.createElement('label');
        option.className = 'form-check';
        option.innerHTML =
          '<input class="form-check-input js-bulk-issue-filter" type="checkbox" value="' + escapeHtml(type) + '"' + (checked ? ' checked' : '') + '>' +
          '<span class="form-check-label">' + escapeHtml(label) + ' (' + escapeHtml(String(counts[type] || 0)) + ')</span>';
        bulkEmailIssueOptionsEl.appendChild(option);
      });
      activeBulkEmailIssueTypes = getSelectedBulkEmailIssueTypes();
    }

    function fillBulkEmailSummary() {
      const summary = collectBulkEmailCandidates();
      const filteredNode = document.getElementById('bulkEmailFilteredCount');
      const eligibleNode = document.getElementById('bulkEmailEligibleCount');
      const skippedNode = document.getElementById('bulkEmailSkippedCount');
      const previewNode = document.getElementById('bulkEmailPreviewText');
      const dispatchNode = document.getElementById('bulkEmailDispatchNote');

      if (filteredNode) filteredNode.textContent = String(summary.filtered_count);
      if (eligibleNode) eligibleNode.textContent = String(summary.eligible_count);
      if (skippedNode) skippedNode.textContent = String(summary.skipped_count);
      renderBulkEmailIssueOptions(summary);
      if (dispatchNode) {
        dispatchNode.textContent = <?= json_encode(t('bdr_distance_bulk_email_dispatch_direct', 'Bulk email akan dihantar terus kepada alamat emel staf yang layak.')) ?>;
      }
      if (previewNode) {
        if (summary.eligible_count <= 0) {
          previewNode.textContent = <?= json_encode(t('bdr_distance_bulk_email_preview_empty', 'Tiada rekod dipilih buat masa ini.')) ?>;
        } else {
          previewNode.textContent = <?= json_encode(t('bdr_distance_bulk_email_preview_prefix', 'Sistem akan masukkan rekod bermasalah yang sedang dipaparkan ke dalam queue bulk email berdasarkan isu yang dipilih.')) ?>;
        }
      }
      if (bulkEmailStartBtn) {
        bulkEmailStartBtn.disabled = summary.eligible_count <= 0;
      }

      activeBulkEmailItems = summary.eligible_items;
      activeBulkEmailIssueTypes = getSelectedBulkEmailIssueTypes();
      return summary;
    }

    function syncBulkEmailIssueSelection() {
      activeBulkEmailIssueTypes = getSelectedBulkEmailIssueTypes();
      return fillBulkEmailSummary();
    }

    async function startBulkEmailJob() {
      const response = await fetch(bulkEmailStartUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({
          csrf_token: csrfToken,
          site_code: activeSiteCode,
          department: String(currentLoadedDepartment || ''),
          eligible_count: Number(activeBulkEmailItems.length || 0),
          issue_types: activeBulkEmailIssueTypes
        })
      });

      const payload = await parseJsonResponse(response, 'bulk_email_start_failed');
      if (!response.ok || !payload || payload.success !== true) {
        throw new Error(String((payload && payload.message) || 'bulk_email_start_failed'));
      }
      return payload;
    }

    async function getBulkEmailJobStatus(jobId) {
      const formData = new FormData();
      formData.append('csrf_token', csrfToken);
      formData.append('job_id', String(jobId));
      formData.append('site_code', activeSiteCode);

      const response = await fetch(bulkEmailStatusUrl, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': csrfToken
        },
        body: formData
      });

      const payload = await parseJsonResponse(response, 'bulk_email_status_failed');
      if (!response.ok || !payload || payload.success !== true) {
        throw new Error(String((payload && payload.message) || 'bulk_email_status_failed'));
      }
      return payload;
    }

    function updateBulkEmailProgress(job) {
      const progressBar = document.getElementById('bulkEmailProgressBar');
      const totalNode = document.getElementById('bulkEmailProgressTotal');
      const processedNode = document.getElementById('bulkEmailProgressProcessed');
      const successNode = document.getElementById('bulkEmailProgressSuccess');
      const failedNode = document.getElementById('bulkEmailProgressFailed');
      const messageNode = document.getElementById('bulkEmailProgressMessage');
      const percent = Number(job && job.progress_percent ? job.progress_percent : 0);

      if (progressBar) {
        progressBar.style.width = percent + '%';
        progressBar.setAttribute('aria-valuenow', String(percent));
        progressBar.textContent = percent + '%';
      }
      if (totalNode) totalNode.textContent = String(job && job.total_count ? job.total_count : 0);
      if (processedNode) processedNode.textContent = String(job && job.processed_count ? job.processed_count : 0);
      if (successNode) successNode.textContent = String(job && job.success_count ? job.success_count : 0);
      if (failedNode) failedNode.textContent = String(job && job.failed_count ? job.failed_count : 0);
      if (messageNode) {
        messageNode.textContent = String((job && job.last_message) || <?= json_encode(t('bdr_distance_bulk_email_status_idle', 'Menunggu bulk email job dimulakan.')) ?>);
        let alertClass = 'alert-secondary';
        if (job && job.is_finished) {
          alertClass = Number(job.failed_count || 0) > 0 ? 'alert-warning' : 'alert-success';
        }
        messageNode.className = 'alert mb-0 ' + alertClass;
      }
    }

    function renderExclusionOptions(rowData) {
      const optionsEl = document.getElementById('sampleViewExclusionOptions');
      const statusEl = document.getElementById('sampleViewExclusionStatus');
      if (!optionsEl) return;
      const issues = Array.isArray(rowData && rowData.notification_issues) ? rowData.notification_issues : [];
      optionsEl.innerHTML = '';
      if (!issues.length) {
        if (statusEl) {
          statusEl.textContent = <?= json_encode(t('bdr_distance_notification_exclusion_none', 'Tiada isu untuk dikecualikan bagi rekod ini.')) ?>;
        }
        return;
      }

      issues.forEach(function(issue) {
        const type = String(issue.type || '').trim();
        if (!type) return;
        const label = String(issue.label || getIssueLabelFromType(type)).trim();
        const checked = issue.excluded === true;
        const item = document.createElement('label');
        item.className = 'form-check';
        item.innerHTML =
          '<input class="form-check-input js-exclusion-issue" type="checkbox" value="' + escapeHtml(type) + '"' + (checked ? ' checked' : '') + '>' +
          '<span class="form-check-label">' + escapeHtml(label) + '</span>';
        optionsEl.appendChild(item);
      });
      if (statusEl) {
        statusEl.textContent = <?= json_encode(t('bdr_distance_notification_exclusion_status_ready', 'Pilih isu yang perlu dikecualikan untuk alamat semasa ini.')) ?>;
      }
    }

    async function saveNotificationExclusion(rowData) {
      const selectedExcludedTypes = Array.from(document.querySelectorAll('.js-exclusion-issue:checked')).map(function(node) {
        return String(node.value || '').trim();
      }).filter(Boolean);
      const allIssueTypes = Array.isArray(rowData && rowData.notification_issues)
        ? rowData.notification_issues.map(function(issue) { return String(issue.type || '').trim(); }).filter(Boolean)
        : [];
      const response = await fetch(bulkEmailExclusionSaveUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({
          csrf_token: csrfToken,
          staff_no: String(rowData && rowData.staff_no ? rowData.staff_no : ''),
          site_code: activeSiteCode,
          address_hash: String(rowData && rowData.notification_address_hash ? rowData.notification_address_hash : ''),
          excluded_issue_types: selectedExcludedTypes,
          all_issue_types: allIssueTypes,
          reason: String((document.getElementById('sampleViewExclusionReason') || {}).value || ''),
          notes: String((document.getElementById('sampleViewExclusionNotes') || {}).value || '')
        })
      });

      const payload = await parseJsonResponse(response, 'bulk_email_exclusion_save_failed');
      if (!response.ok || !payload || payload.success !== true) {
        throw new Error(String((payload && payload.message) || 'bulk_email_exclusion_save_failed'));
      }
      return payload;
    }

    async function processBulkEmailJob(jobId) {
      const formData = new FormData();
      formData.append('csrf_token', csrfToken);
      formData.append('job_id', String(jobId));
      formData.append('site_code', activeSiteCode);

      const response = await fetch(bulkEmailProcessUrl, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': csrfToken
        },
        body: formData
      });

      const payload = await parseJsonResponse(response, 'bulk_email_process_failed');
      if (!response.ok || !payload || payload.success !== true) {
        throw new Error(String((payload && payload.message) || 'bulk_email_process_failed'));
      }

      updateBulkEmailProgress(payload.job || {});
      if (payload.job && payload.job.is_finished) {
        clearBulkEmailPoll();
        activeBulkEmailJobId = 0;
        if (window.Swal && typeof window.Swal.fire === 'function') {
          window.Swal.fire({
            icon: Number(payload.job.failed_count || 0) > 0 ? 'warning' : 'success',
            title: <?= json_encode(t('bdr_distance_bulk_email_completed_title', 'Bulk Email Selesai')) ?>,
            text: String((payload.job.last_message || <?= json_encode(t('bdr_distance_bulk_email_status_completed', 'Bulk email job telah selesai.')) ?>)),
            confirmButtonText: <?= json_encode(t('bdr_distance_btn_ok', 'OK')) ?>
          });
        }
        return;
      }

      bulkEmailPollTimer = window.setTimeout(function() {
        processBulkEmailJob(jobId).catch(function(error) {
          clearBulkEmailPoll();
          if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
              icon: 'error',
              title: <?= json_encode(t('bdr_distance_bulk_email_process_failed_title', 'Bulk Email Gagal Diproses')) ?>,
              text: String(error && error.message ? error.message : <?= json_encode(t('bdr_distance_bulk_email_process_failed', 'Pemprosesan bulk email gagal.')) ?>),
              confirmButtonText: <?= json_encode(t('bdr_distance_btn_ok', 'OK')) ?>
            });
          }
        });
      }, bulkEmailPollDelayMs);
    }

    $dept.off('change').on('change', function() {
      const selectedDepartment = this.value || '__ALL__';
      if (selectedDepartment === currentLoadedDepartment) {
        return;
      }
      if (selectedDepartment === '__ALL__') {
        navigateWithTableLoader(pageUrlWithSite('__ALL__', activeSiteCode));
        return;
      }
      setTableSwitchLoaderActive(true);
      loadRowsData(selectedDepartment);
    });

    document.querySelectorAll('.bdr-site-switch__link').forEach(function(link) {
      link.addEventListener('click', function(event) {
        const targetSiteCode = String(link.getAttribute('data-site-code') || '').trim();
        if (!targetSiteCode || targetSiteCode === activeSiteCode) {
          event.preventDefault();
          return;
        }
        event.preventDefault();
        const targetDepartment = targetSiteCode === 'upnm_kampus'
          ? String(currentDepartment || '')
          : '';
        loadRowsData(targetDepartment, targetSiteCode);
      });
    });

    $issue.val('all');
    $issue.off('change').on('change', function() {
      issueFilterValue = String(this.value || 'all');
      dt.draw();
    });

    const customOperatorEl = document.getElementById('distanceFilterOperator');
    const customValue1El = document.getElementById('distanceFilterValue1');
    const customValueMinEl = document.getElementById('distanceFilterValueMin');
    const customValueMaxEl = document.getElementById('distanceFilterValueMax');
    const customSingleWrapEl = document.getElementById('distanceFilterSingleWrap');
    const customBetweenWrapEl = document.getElementById('distanceFilterBetweenWrap');
    const customErrorEl = document.getElementById('distanceFilterError');
    const customApplyBtn = document.getElementById('distanceFilterApplyBtn');
    const customResetBtn = document.getElementById('distanceFilterResetBtn');
    const exportXlsBtn = document.getElementById('exportXlsBtn');

    function setDistanceFilterError(message) {
      if (!customErrorEl) return;
      if (!message) {
        customErrorEl.textContent = '';
        customErrorEl.classList.add('d-none');
        return;
      }
      customErrorEl.textContent = message;
      customErrorEl.classList.remove('d-none');
    }

    function toggleCustomOperatorInputs() {
      const isBetween = customOperatorEl && customOperatorEl.value === 'between';
      if (customSingleWrapEl) {
        customSingleWrapEl.classList.toggle('d-none', !!isBetween);
      }
      if (customBetweenWrapEl) {
        customBetweenWrapEl.classList.toggle('d-none', !isBetween);
      }
      setDistanceFilterError('');
    }

    function resetCustomFilterInputs() {
      if (customOperatorEl) customOperatorEl.value = '=';
      if (customValue1El) customValue1El.value = '';
      if (customValueMinEl) customValueMinEl.value = '';
      if (customValueMaxEl) customValueMaxEl.value = '';
      toggleCustomOperatorInputs();
    }

    function syncDistanceFilterDropdown() {
      const value = distanceFilterState.mode === 'custom' ? 'custom' : (distanceFilterState.preset || 'all');
      $distance.val(value);
    }

    function resetDistanceFilterState(redraw = true) {
      distanceFilterState.mode = 'all';
      distanceFilterState.preset = 'all';
      distanceFilterState.custom = null;
      syncDistanceFilterDropdown();
      resetCustomFilterInputs();
      if (redraw) {
        dt.draw();
      }
    }

    async function parseJsonResponse(response, fallbackMessage) {
      const text = await response.text();
      const normalizedFallback = String(fallbackMessage || 'request_failed');
      if (!text) {
        throw new Error(normalizedFallback);
      }

      try {
        return JSON.parse(text);
      } catch (error) {
        const compact = text.replace(/\s+/g, ' ').trim();
        const looksLikeHtml = /^<!doctype html/i.test(compact) || /^<html/i.test(compact) || compact.startsWith('<');
        if (looksLikeHtml) {
          throw new Error(<?= json_encode(t('bdr_distance_json_parse_html_error', 'Server returned HTML instead of JSON. Semak log PHP/server untuk ralat sebenar.')) ?>);
        }

        const snippet = compact.slice(0, 220);
        throw new Error(snippet || normalizedFallback);
      }
    }

    function parseDownloadFilename(disposition, fallback) {
      const source = String(disposition || '');
      const utf8Match = source.match(/filename\*=UTF-8''([^;]+)/i);
      if (utf8Match && utf8Match[1]) {
        return decodeURIComponent(utf8Match[1]).replace(/[\\/:*?"<>|]+/g, '_');
      }
      const basicMatch = source.match(/filename="?([^";]+)"?/i);
      if (basicMatch && basicMatch[1]) {
        return basicMatch[1].replace(/[\\/:*?"<>|]+/g, '_');
      }
      return fallback;
    }

    function exportCurrentTableToXls() {
      const exportedRowCount = dt.rows({ search: 'applied' }).count();
      if (!exportedRowCount) {
        window.alert(<?= json_encode(t('bdr_distance_export_empty', 'Tiada data untuk dieksport.')) ?>);
        return;
      }

      const originalHtml = exportXlsBtn ? exportXlsBtn.innerHTML : '';
      if (exportXlsBtn) {
        exportXlsBtn.disabled = true;
        exportXlsBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>';
      }

      try {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = exportXlsUrl;
        form.style.display = 'none';

        const fields = {
          site_code: activeSiteCode,
          department: currentLoadedDepartment || '',
          search_query: dt.search() || ''
        };

        Object.keys(fields).forEach(function(name) {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = name;
          input.value = fields[name];
          form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
        form.remove();
      } catch (error) {
        window.alert(<?= json_encode(t('bdr_distance_export_failed', 'Export XLS gagal. Sila cuba lagi.')) ?>);
      }

      window.setTimeout(function() {
        if (exportXlsBtn) {
          exportXlsBtn.disabled = false;
          exportXlsBtn.innerHTML = originalHtml;
        }
      }, 1200);
    }

    function applyPresetDistanceFilter(preset) {
      distanceFilterState.mode = preset === 'all' ? 'all' : 'preset';
      distanceFilterState.preset = preset;
      distanceFilterState.custom = null;
      resetCustomFilterInputs();
      syncDistanceFilterDropdown();
      dt.draw();
    }

    function evaluateDistanceFilter(kmValue) {
      if (distanceFilterState.mode === 'preset') {
        if (distanceFilterState.preset === 'le8') {
          return kmValue <= 8;
        }
        if (distanceFilterState.preset === 'gt8') {
          return kmValue > 8;
        }
        return true;
      }

      if (distanceFilterState.mode === 'custom' && distanceFilterState.custom) {
        const custom = distanceFilterState.custom;
        const value = Number(custom.value);
        const min = Number(custom.min);
        const max = Number(custom.max);
        switch (custom.operator) {
          case '=': return kmValue === value;
          case '!=': return kmValue !== value;
          case '>': return kmValue > value;
          case '<': return kmValue < value;
          case '>=': return kmValue >= value;
          case '<=': return kmValue <= value;
          case 'between': return kmValue >= min && kmValue <= max;
          default: return true;
        }
      }

      return true;
    }

    $distance.off('change').on('change', function() {
      const selectedValue = this.value || 'all';
      if (selectedValue === 'custom') {
        if (distanceFilterModal) {
          toggleCustomOperatorInputs();
          distanceFilterModal.show();
        }
        return;
      }
      applyPresetDistanceFilter(selectedValue);
    });

    if (customOperatorEl) {
      customOperatorEl.addEventListener('change', toggleCustomOperatorInputs);
    }

    if (customApplyBtn) {
      customApplyBtn.addEventListener('click', function() {
        const operator = customOperatorEl ? customOperatorEl.value : '=';
        if (operator === 'between') {
          const min = customValueMinEl ? customValueMinEl.value.trim() : '';
          const max = customValueMaxEl ? customValueMaxEl.value.trim() : '';
          if (min === '' || max === '' || Number.isNaN(Number(min)) || Number.isNaN(Number(max))) {
            setDistanceFilterError(<?= json_encode(t('bdr_distance_filter_validation_between', 'Sila isi nilai minimum dan maksimum yang sah.')) ?>);
            return;
          }
          if (Number(min) > Number(max)) {
            setDistanceFilterError(<?= json_encode(t('bdr_distance_filter_validation_range', 'Nilai minimum tidak boleh melebihi maksimum.')) ?>);
            return;
          }
          distanceFilterState.mode = 'custom';
          distanceFilterState.preset = 'custom';
          distanceFilterState.custom = { operator: 'between', min: min, max: max };
        } else {
          const value = customValue1El ? customValue1El.value.trim() : '';
          if (value === '' || Number.isNaN(Number(value))) {
            setDistanceFilterError(<?= json_encode(t('bdr_distance_filter_validation_value', 'Sila isi satu nilai nombor yang sah.')) ?>);
            return;
          }
          distanceFilterState.mode = 'custom';
          distanceFilterState.preset = 'custom';
          distanceFilterState.custom = { operator: operator, value: value };
        }

        setDistanceFilterError('');
        syncDistanceFilterDropdown();
        dt.draw();
        if (distanceFilterModal) {
          distanceFilterModal.hide();
        }
      });
    }

    if (customResetBtn) {
      customResetBtn.addEventListener('click', function() {
        resetDistanceFilterState(true);
        if (distanceFilterModal) {
          distanceFilterModal.hide();
        }
      });
    }

    if (distanceFilterModalEl) {
      distanceFilterModalEl.addEventListener('hidden.bs.modal', function() {
        setDistanceFilterError('');
        syncDistanceFilterDropdown();
      });
    }

    function initDepartmentSelect2() {
      if (!(window.jQuery && jQuery.fn && typeof jQuery.fn.select2 === 'function')) {
        return false;
      }

      if ($dept.data('select2')) {
        $dept.select2('destroy');
      }

      $dept.select2({
        width: 'style',
        placeholder: departmentPlaceholder,
        allowClear: true,
        minimumResultsForSearch: 0
      });

      applyDepartmentFilterWidth($dept, departmentFilterWidth);
      return true;
    }

    if (!initDepartmentSelect2()) {
      const script = document.createElement('script');
      script.src = '<?= base_url('assets/vendor/select2/js/select2.full.min.js') ?>?v=<?= h($version) ?>';
      script.onload = function() {
        initDepartmentSelect2();
      };
      document.head.appendChild(script);
    } else {
      applyDepartmentFilterWidth($dept, departmentFilterWidth);
    }
    applyDepartmentFilterWidth($issue, issueFilterWidth);
    applyDepartmentFilterWidth($distance, distanceFilterWidth);
    resetCustomFilterInputs();
    syncDistanceFilterDropdown();

    function resolveDefaultDepartmentValue() {
      const target = String(selectedDepartment || '').trim().toLowerCase();
      if (!target) return '';
      const normalize = function(text) {
        return String(text || '').toLowerCase().replace(/[^a-z0-9]+/g, '');
      };
      const normalizedTarget = normalize(target);

      let matched = '';
      $dept.find('option').each(function() {
        const value = String(this.value || '').trim();
        if (!value || value === '__ALL__') return;
        const normalizedValue = normalize(value);
        if (value.toLowerCase() === target || normalizedValue === normalizedTarget) {
          matched = value;
          return false;
        }
        if (!matched && (value.toLowerCase().includes(target) || target.includes(value.toLowerCase()) || (normalizedValue && normalizedTarget && (normalizedValue.includes(normalizedTarget) || normalizedTarget.includes(normalizedValue))))) {
          matched = value;
        }
      });

      return matched;
    }

    const defaultDepartmentValue = resolveDefaultDepartmentValue();
    if (defaultDepartmentValue) {
      $dept.val(defaultDepartmentValue);
      departmentFilterValue = '';
      currentLoadedDepartment = defaultDepartmentValue;
    } else {
      $dept.val('__ALL__');
      departmentFilterValue = '';
      currentLoadedDepartment = '__ALL__';
    }

    if ($dept.data('select2')) {
      $dept.trigger('change.select2');
    }

    function buildRowHtml(row, index) {
      const name = String(row.name || '');
      const staffNo = String(row.staff_no || '');
      const rowSiteCode = String(row.site_code || activeSiteCode);
      const staffEmail = String(row.email || '');
      const department = String(row.department || '');
      const mailingAddress = String(row.mailing_address || '');
      const addressStatus = String(row.address_status || 'VALID');
      const mapUrl = String(row.map_url || '');
      const distanceLabel = String(row.distance_label || '');
      const distanceSource = String(row.distance_source || '');
      const directionUrl = String(row.direction_url || '');
      const distanceAttempted = !!row.distance_attempted;
      const matchQuality = String(row.match_quality || '');
      const debugReason = String(row.debug_reason || '');
      const isAddressChanged = !!row.is_address_changed || debugReason === 'address_changed';
      const notificationIssues = Array.isArray(row.notification_issues) ? row.notification_issues : [];
      const activeNotificationIssues = Array.isArray(row.active_notification_issues) && row.active_notification_issues.length
        ? row.active_notification_issues
        : notificationIssues.filter(function(issue) { return issue && issue.excluded !== true; });
      const excludedNotificationIssues = Array.isArray(row.excluded_notification_issues) && row.excluded_notification_issues.length
        ? row.excluded_notification_issues
        : notificationIssues.filter(function(issue) { return issue && issue.excluded === true; }).map(function(issue) { return String(issue.type || '').trim(); }).filter(Boolean);
      const confirmedNotificationIssues = Array.isArray(row.confirmed_notification_issues) && row.confirmed_notification_issues.length
        ? row.confirmed_notification_issues
        : notificationIssues.filter(function(issue) {
            return issue
              && issue.excluded === true
              && String(issue.exclusion_source || '').trim().toUpperCase() === 'SELF_CONFIRM';
          }).map(function(issue) { return String(issue.type || '').trim(); }).filter(Boolean);
      const showEmailReminder = !!row.show_email_reminder;
      const isReviewLocation = !!row.is_review_location
        || matchQuality.toUpperCase() === 'LOW'
        || debugReason === 'low_match_quality'
        || distanceLabel === reviewLocationLabel;
      const issueType = String(row.issue_type || getIssueTypeFromState(addressStatus, isAddressChanged, isReviewLocation));
      const isIssueConfirmed = isIssueSelfConfirmed(row, issueType);
      let rowClass = '';
      let addressTooltip = '';
      let distanceTooltip = '';
      let distanceInitialText = '-';
      let distanceCellClass = '';

      if (addressStatus === 'EMPTY') {
        rowClass = 'table-danger-soft';
        addressTooltip = <?= json_encode(t('bdr_distance_status_no_address', 'Tiada Alamat')) ?>;
        distanceTooltip = <?= json_encode(t('bdr_distance_msg_no_address', 'Jarak tidak dapat dikira kerana tiada alamat')) ?>;
        distanceInitialText = <?= json_encode(t('bdr_distance_status_no_address', 'Tiada Alamat')) ?>;
        distanceCellClass = 'distance-no-address';
      } else if (addressStatus === 'INVALID') {
        rowClass = 'table-warning-soft';
        addressTooltip = <?= json_encode(t('bdr_distance_status_incomplete', 'Alamat tidak lengkap')) ?>;
        distanceTooltip = <?= json_encode(t('bdr_distance_msg_invalid', 'Jarak berdasarkan alamat yang mungkin tidak lengkap')) ?>;
        distanceInitialText = <?= json_encode(t('bdr_distance_status_incomplete', 'Alamat tidak lengkap')) ?>;
        distanceCellClass = 'distance-failed';
      } else if (isAddressChanged) {
        rowClass = 'table-warning-soft';
        distanceTooltip = <?= json_encode(t('bdr_distance_msg_address_changed', 'Alamat telah berubah. Sila kira semula.')) ?>;
        distanceInitialText = <?= json_encode(t('bdr_distance_not_calculated', 'Belum Dikira')) ?>;
        distanceCellClass = 'distance-pending';
      } else {
        distanceTooltip = distanceAttempted
          ? <?= json_encode(t('bdr_distance_msg_failed_cached', 'Kiraan terdahulu gagal. Klik butang Kira untuk cuba semula')) ?>
          : <?= json_encode(t('bdr_distance_msg_checking_cache', 'Sedang menyemak cache jarak')) ?>;
        distanceInitialText = distanceAttempted
          ? <?= json_encode(t('bdr_distance_status_failed', 'Gagal Kira')) ?>
          : <?= json_encode(t('bdr_distance_not_calculated', 'Belum Dikira')) ?>;
        distanceCellClass = distanceAttempted ? '' : 'distance-pending';
      }

      const rawDistanceKm = (row && Object.prototype.hasOwnProperty.call(row, 'distance_km')) ? row.distance_km : '';
      const distanceKmValue = Number.isFinite(parseFloat(String(rawDistanceKm))) ? parseFloat(String(rawDistanceKm)) : NaN;
      if (Number.isFinite(distanceKmValue)) {
        if (distanceKmValue > 8) {
          distanceCellClass = 'distance-far';
        } else if (distanceKmValue === 8) {
          distanceCellClass = 'distance-equal';
        } else if (distanceKmValue < 8) {
          distanceCellClass = 'distance-near';
          rowClass = String((rowClass + ' table-distance-near-soft').trim());
        }
        distanceInitialText = distanceLabel || (distanceKmValue.toFixed(2) + ' KM');
        distanceTooltip = distanceSource === 'route' ? routeDistanceTitle : straightDistanceTitle;
      } else if (distanceLabel === <?= json_encode(t('bdr_distance_status_failed', 'Gagal Kira')) ?>) {
        distanceCellClass = 'distance-failed';
        distanceInitialText = distanceLabel;
        distanceTooltip = <?= json_encode(t('bdr_distance_msg_failed_cached', 'Kiraan terdahulu gagal. Klik butang Kira untuk cuba semula')) ?>;
      }
      if (isReviewLocation) {
        rowClass = 'table-danger-soft';
        distanceCellClass = 'distance-failed';
        distanceInitialText = reviewLocationLabel;
        distanceTooltip = <?= json_encode(t('bdr_distance_msg_low_match', 'Lokasi geocode meragukan. Sila semak alamat dan koordinat.')) ?>;
      }

      const modalRow = {
        id: Number(row.id || (index + 1)),
        name: name,
        staff_no: staffNo,
        email: staffEmail,
        department: department,
        site_code: rowSiteCode,
        office_label: String(row.office_label || activeOfficeLabel),
        office_address: String(row.office_address || activeOfficeAddress),
        mailing_address: mailingAddress,
        address_status: addressStatus,
        distance_label: distanceLabel,
        distance_source: String(row.distance_source || ''),
        route_provider: String(row.route_provider || ''),
        cache_origin: String(row.cache_origin || 'none'),
        matched_query: String(row.matched_query || ''),
        provider_display_name: String(row.provider_display_name || row.matched_query || ''),
        home_coords_label: String(row.home_coords_label || ''),
        match_quality: String(row.match_quality || ''),
        debug_reason: String(row.debug_reason || ''),
        details_hydrated: !!row.details_hydrated,
        is_review_location: !!row.is_review_location,
        is_address_changed: !!row.is_address_changed,
        show_email_reminder: !!row.show_email_reminder,
        issue_type: String(row.issue_type || ''),
        issue_label: String(row.issue_label || ''),
        notification_address_hash: String(row.notification_address_hash || ''),
        selected_state: String(row.selected_state || ''),
        detected_state: String(row.detected_state || ''),
        notification_issues: notificationIssues
      };
      const rowJson = escapeHtml(JSON.stringify(modalRow));
      const addressInputs = row.address_inputs && typeof row.address_inputs === 'object' ? row.address_inputs : {};

      const addressHtml = mailingAddress
        ? '<div class="alamat-cell-wrap" data-bs-toggle="tooltip" data-bs-custom-class="template-tooltip" title="' + escapeHtml(addressTooltip ? (addressTooltip + ' | ' + mailingAddress) : mailingAddress) + '">'
          + '<div class="alamat-cell-copy">'
          + '<span class="alamat-text truncate-1line">' + escapeHtml(mailingAddress) + '</span>'
          + '</div>'
          + '<a href="' + escapeHtml(mapUrl) + '" target="_blank" rel="noopener noreferrer" class="alamat-map-link" aria-label="' + escapeHtml(<?= json_encode(t('bdr_distance_open_map', 'Buka peta')) ?>) + '"><i class="ri-map-pin-line"></i></a>'
          + '</div>'
        : '<span class="address-empty-badge truncate-1line" data-bs-toggle="tooltip" data-bs-custom-class="template-tooltip" title="' + escapeHtml(addressTooltip || <?= json_encode(t('bdr_distance_status_no_address', 'Tiada Alamat')) ?>) + '">' + escapeHtml(<?= json_encode(t('bdr_distance_status_no_address', 'Tiada Alamat')) ?>) + '</span>';

      return '<tr'
        + ' data-row-index="' + escapeHtml(String(index)) + '"'
        + ' data-site-code="' + escapeHtml(rowSiteCode) + '"'
        + ' data-department="' + escapeHtml(department) + '"'
        + ' data-staf-id="' + escapeHtml(staffNo) + '"'
        + ' data-email="' + escapeHtml(staffEmail) + '"'
        + ' data-address="' + escapeHtml(mailingAddress) + '"'
        + ' data-address-status="' + escapeHtml(addressStatus) + '"'
        + ' data-distance-attempted="' + escapeHtml(distanceAttempted ? '1' : '0') + '"'
        + ' data-match-quality="' + escapeHtml(matchQuality) + '"'
        + ' data-debug-reason="' + escapeHtml(debugReason) + '"'
        + ' data-review-location="' + escapeHtml(isReviewLocation ? '1' : '0') + '"'
        + ' data-address-changed="' + escapeHtml(isAddressChanged ? '1' : '0') + '"'
        + ' data-show-email-reminder="' + escapeHtml(showEmailReminder ? '1' : '0') + '"'
        + ' data-issue-type="' + escapeHtml(issueType) + '"'
        + ' data-issue-confirmed="' + escapeHtml(isIssueConfirmed ? '1' : '0') + '"'
        + ' data-address-hash="' + escapeHtml(String(row.notification_address_hash || '')) + '"'
        + ' data-detected-state="' + escapeHtml(String(row.detected_state || '')) + '"'
        + ' data-alamat1="' + escapeHtml(String(addressInputs.alamat1 || '')) + '"'
        + ' data-alamat2="' + escapeHtml(String(addressInputs.alamat2 || '')) + '"'
        + ' data-alamat3="' + escapeHtml(String(addressInputs.alamat3 || '')) + '"'
        + ' data-poskod="' + escapeHtml(String(addressInputs.poskod || '')) + '"'
        + ' data-negeri="' + escapeHtml(String(addressInputs.negeri || '')) + '"'
        + ' data-negara="' + escapeHtml(String(addressInputs.negara || '')) + '"'
        + ' data-route-points="[]"'
        + ' class="' + escapeHtml(rowClass) + '">'
        + '<td class="col-bil"></td>'
        + '<td class="col-nama"><span class="truncate-1line" data-bs-toggle="tooltip" data-bs-custom-class="template-tooltip" title="' + escapeHtml(name) + '">' + escapeHtml(name || '-') + '</span></td>'
        + '<td class="col-staf"><span class="truncate-1line" data-bs-toggle="tooltip" data-bs-custom-class="template-tooltip" title="' + escapeHtml(staffNo) + '">' + escapeHtml(staffNo || '-') + '</span></td>'
        + '<td class="col-alamat">' + addressHtml + '</td>'
        + '<td class="col-status">' + renderIssueStatusHtml(issueType, isIssueConfirmed) + '</td>'
        + '<td class="col-jarak"><span class="js-distance-cell ' + escapeHtml(distanceCellClass + ' truncate-1line') + '" data-distance-km="' + escapeHtml(Number.isFinite(distanceKmValue) ? String(distanceKmValue) : '') + '" data-direction-url="' + escapeHtml(directionUrl) + '" data-bs-toggle="tooltip" data-bs-custom-class="template-tooltip" title="' + escapeHtml(distanceTooltip) + '">' + escapeHtml(distanceInitialText) + '</span></td>'
        + '<td class="col-actions">'
        + '<button type="button" class="btn btn-outline-success btn-sm js-calc-row" title="' + escapeHtml(<?= json_encode(t('bdr_distance_btn_calculate', 'Kira Jarak')) ?>) + '"><i class="ri-route-line"></i></button>'
        + '<button type="button" class="btn btn-outline-primary btn-sm js-view-row" data-row="' + rowJson + '" title="' + escapeHtml(<?= json_encode(t('bdr_distance_btn_view', 'Lihat')) ?>) + '"><i class="ri-eye-line"></i></button>'
        + '<button type="button" class="btn btn-outline-danger btn-sm js-email-reminder" style="' + (showEmailReminder ? '' : 'display:none;') + '" title="' + escapeHtml(<?= json_encode(t('bdr_distance_btn_email_reminder', 'Email Reminder')) ?>) + '"><i class="ri-mail-send-line"></i></button>'
        + '</td></tr>';
    }

    function applyRowsPayload(payload) {
      const items = Array.isArray(payload.items) ? payload.items : [];
      const rowNodes = items.map(function(item, index) {
        const wrapper = document.createElement('tbody');
        wrapper.innerHTML = buildRowHtml(item, index);
        return wrapper.firstElementChild;
      }).filter(Boolean);
      dt.clear();
      if (rowNodes.length) {
        rowNodes.forEach(function(rowNode) {
          dt.row.add(rowNode);
        });
      }
      dt.draw(false);

      const options = Array.isArray(payload.department_options) ? payload.department_options : [];
      $dept.find('option:not([value="__ALL__"])').remove();
      options.forEach(function(name) {
        if (!name) return;
        $dept.append(new Option(String(name), String(name)));
      });

      const targetDepartment = Object.prototype.hasOwnProperty.call(payload || {}, 'current_department')
        ? String(payload.current_department || '')
        : String(currentDepartment || '');
      const normalizedTarget = targetDepartment.trim().toLowerCase();
      let matchedValue = '';
      $dept.find('option').each(function() {
        const value = String(this.value || '').trim();
        if (value && value.toLowerCase() === normalizedTarget) {
          matchedValue = value;
          return false;
        }
      });
      departmentFilterValue = '';
      currentLoadedDepartment = matchedValue || '__ALL__';
      $dept.val(currentLoadedDepartment);
      if ($dept.data('select2')) {
        $dept.trigger('change.select2');
      }
      dt.draw();

      const statsBtn = document.getElementById('openStatsSummary');
      if (statsBtn) {
        statsBtn.innerHTML = '<i class="ri-bar-chart-box-line me-1"></i>' + escapeHtml(String(payload.total || items.length)) + ' ' + escapeHtml(<?= json_encode(t('bdr_distance_total_records', 'rekod')) ?>);
      }
      const payloadSiteCode = String(payload.site_code || activeSiteCode || '').trim();
      if (payloadSiteCode) {
        activeSiteCode = payloadSiteCode;
      }
      activeOfficeLabel = String(payload.office_label || activeOfficeLabel || '');
      activeOfficeAddress = String(payload.office_address || activeOfficeAddress || '');
      refreshActiveSiteUrls();
      refreshDestinationTitles();
      document.querySelectorAll('.bdr-site-switch__link').forEach(function(link) {
        const linkSite = String(link.getAttribute('data-site-code') || '').trim();
        link.classList.toggle('is-active', linkSite === activeSiteCode);
        link.setAttribute('href', pageUrlWithSite('', linkSite || activeSiteCode));
      });
      const officeText = document.getElementById('bdrActiveOfficeText');
      if (officeText) {
        officeText.innerHTML = '<i class="ri-map-pin-2-line me-1"></i>' + escapeHtml(activeOfficeLabel) + ' · ' + escapeHtml(activeOfficeAddress);
      }
      rebuildTooltips(document);
      updateStatsSummary();
    }

    function loadRowsData(department, siteCode) {
      if (rowsLoading) {
        if (typeof siteCode === 'string' && siteCode !== activeSiteCode) {
          queuedSiteLoad = siteCode;
        }
        if (typeof department === 'string') {
          queuedDepartmentLoad = department;
        }
        return Promise.resolve();
      }
      const requestedDepartment = typeof department === 'string' ? department : '';
      const targetDepartment = requestedDepartment === '__ALL__' ? '' : requestedDepartment;
      const targetSiteCode = typeof siteCode === 'string' && siteCode !== '' ? siteCode : activeSiteCode;
      const nextUrl = pageUrlWithSite(targetDepartment || '', targetSiteCode);
      if (window.history && typeof window.history.replaceState === 'function') {
        window.history.replaceState({}, '', nextUrl);
      }
      rowsLoading = true;
      if (loaderTimer) {
        window.clearTimeout(loaderTimer);
      }
      loaderTimer = window.setTimeout(function() {
        if (rowsLoading) {
          setTableSwitchLoaderActive(true);
        }
      }, 180);
      const params = new URLSearchParams();
      params.set('rows_data', '1');
      params.set('site', targetSiteCode);
      if (typeof department === 'string' && department !== '') {
        params.set('department', department);
      }

      return fetch('bdr-distance.php?' + params.toString(), {
        method: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(function(response) {
        if (!response.ok) {
          throw new Error('rows_data_http_' + response.status);
        }
        return response.json();
      })
      .then(function(payload) {
        applyRowsPayload(payload || {});
      })
      .catch(function(err) {
        console.error('Failed loading bdr-distance rows_data', err);
        if (window.Swal && typeof window.Swal.fire === 'function') {
          window.Swal.fire({
            icon: 'error',
            title: <?= json_encode(t('bdr_distance_rows_data_failed_title', 'Ralat Memuat Data')) ?>,
            text: <?= json_encode(t('bdr_distance_rows_data_failed_text', 'Data jabatan tidak dapat dimuatkan buat masa ini. Sila cuba semula.')) ?>,
            confirmButtonText: <?= json_encode(t('bdr_distance_btn_ok', 'OK')) ?>
          });
        }
      })
      .finally(function() {
        rowsLoading = false;
        if (loaderTimer) {
          window.clearTimeout(loaderTimer);
          loaderTimer = null;
        }
        setTableSwitchLoaderActive(false);
        if (queuedSiteLoad !== null && queuedSiteLoad !== activeSiteCode) {
          const nextSite = queuedSiteLoad;
          queuedSiteLoad = null;
          loadRowsData(nextSite === 'upnm_kampus' ? String(currentDepartment || '') : '', nextSite);
          return;
        }
        queuedSiteLoad = null;
        if (queuedDepartmentLoad !== null && queuedDepartmentLoad !== currentLoadedDepartment) {
          const nextDepartment = queuedDepartmentLoad;
          queuedDepartmentLoad = null;
          loadRowsData(nextDepartment);
          return;
        }
        queuedDepartmentLoad = null;
      });
    }

    function openViewModal(data, sourceRow, options) {
      if (!viewModal) return;
      const modalOptions = options || {};
      const activeTabId = modalOptions.preserveActiveTab && viewModalEl
        ? String(((viewModalEl.querySelector('.nav-link.active') || {}).id) || '')
        : '';
      document.getElementById('sampleViewName').textContent = data.name || '-';
      document.getElementById('sampleViewStaffNo').textContent = data.staff_no || '-';
      document.getElementById('sampleViewDepartment').textContent = data.department || '-';
      const activeRow = sourceRow || document.querySelector('tr[data-row-index="' + String(data.id - 1) + '"]');
      const activeDistanceCell = activeRow ? activeRow.querySelector('.js-distance-cell') : null;
      const rowAddressStatus = String(data.address_status || (activeRow ? activeRow.getAttribute('data-address-status') : '') || '');
      const modalDistanceEl = document.getElementById('sampleViewDistance');
      const modalDistanceText = activeDistanceCell ? activeDistanceCell.textContent.trim() || '-' : (data.distance_label || '-');
      modalDistanceEl.textContent = modalDistanceText;
      modalDistanceEl.className = '';
      const modalDistanceStateClass = resolveDistanceStateClass(modalDistanceText);
      if (modalDistanceStateClass) {
        modalDistanceEl.classList.add(modalDistanceStateClass);
      }
      const cacheOrigin = (activeDistanceCell ? activeDistanceCell.dataset.cacheOrigin : '') || (activeRow ? activeRow.dataset.cacheOrigin : '') || data.cache_origin || 'none';
      const badge = document.getElementById('sampleViewDataBadge');
      if (badge) {
        badge.textContent = cacheOriginMap[cacheOrigin] || cacheOriginMap.none;
        badge.className = 'distance-origin-badge distance-origin-badge--' + String(cacheOrigin || 'none').replace(/[^a-z0-9_-]/gi, '');
      }
      document.getElementById('sampleViewMatchedQuery').textContent = (activeDistanceCell ? activeDistanceCell.dataset.providerDisplayName : '') || (activeRow ? activeRow.dataset.providerDisplayName : '') || data.provider_display_name || data.matched_query || '-';
      document.getElementById('sampleViewHomeCoords').textContent = (activeDistanceCell ? activeDistanceCell.dataset.homeCoords : '') || (activeRow ? activeRow.dataset.homeCoords : '') || data.home_coords_label || (rowAddressStatus === 'VALID' ? <?= json_encode(t('bdr_distance_not_generated', 'Belum Dijana')) ?> : '-');
      document.getElementById('sampleViewMatchQuality').textContent = (activeDistanceCell ? activeDistanceCell.dataset.matchQuality : '') || (activeRow ? activeRow.dataset.matchQuality : '') || data.match_quality || (rowAddressStatus === 'VALID' ? <?= json_encode(t('bdr_distance_not_calculated', 'Belum Dikira')) ?> : '-');
      const routeProvider = (activeDistanceCell ? activeDistanceCell.dataset.routeProvider : '') || (activeRow ? activeRow.dataset.routeProvider : '') || data.route_provider || '';
      const distanceSource = (activeDistanceCell ? activeDistanceCell.dataset.distanceSource : '') || (activeRow ? activeRow.dataset.distanceSource : '') || data.distance_source || (rowAddressStatus === 'VALID' ? 'deferred_lookup' : '-');
      const debugReason = (activeDistanceCell ? activeDistanceCell.dataset.debugReason : '') || (activeRow ? activeRow.dataset.debugReason : '') || data.debug_reason || (rowAddressStatus === 'VALID' ? 'deferred_lookup' : '-');
      const distanceSourceLabel = labelizeDistanceMeta(distanceSource, distanceSourceMap);
      const routeProviderLabel = labelizeDistanceMeta(routeProvider, distanceSourceMap);
      document.getElementById('sampleViewDistanceSource').textContent = routeProvider ? (distanceSourceLabel + ' / ' + routeProviderLabel) : distanceSourceLabel;
      document.getElementById('sampleViewDebugReason').textContent = labelizeDistanceMeta(debugReason, debugReasonMap);
      const addressStatus = String(data.address_status || '');
      const addressText = String(data.mailing_address || '-');
      document.getElementById('sampleViewMailingAddress').textContent = addressStatus === 'INVALID'
        ? addressText + ' (<?= h(t('bdr_distance_status_incomplete', 'Alamat tidak lengkap')) ?>)'
        : (addressStatus === 'EMPTY' ? '- (<?= h(t('bdr_distance_status_no_address', 'Tiada Alamat')) ?>)' : addressText);
      let routePoints = [];
      try {
        routePoints = JSON.parse((activeDistanceCell ? activeDistanceCell.dataset.routePoints : '') || (activeRow ? activeRow.dataset.routePoints : '') || '[]');
      } catch (err) {
        routePoints = [];
      }
      const mergedRowData = Object.assign({}, data || {});
      if ((!Array.isArray(mergedRowData.notification_issues) || !mergedRowData.notification_issues.length) && activeRow) {
        mergedRowData.notification_issues = getRowNotificationIssues(activeRow);
      }
      if (!mergedRowData.notification_address_hash && activeRow) {
        mergedRowData.notification_address_hash = String(activeRow.getAttribute('data-address-hash') || '').trim();
      }
      if (!mergedRowData.detected_state && activeRow) {
        mergedRowData.detected_state = String(activeRow.getAttribute('data-detected-state') || '').trim();
      }
      viewModalEl.dataset.notificationRow = JSON.stringify(mergedRowData);
      const exclusionReasonEl = document.getElementById('sampleViewExclusionReason');
      const exclusionNotesEl = document.getElementById('sampleViewExclusionNotes');
      const exclusionStatusEl = document.getElementById('sampleViewExclusionStatus');
      if (exclusionReasonEl) exclusionReasonEl.value = '';
      if (exclusionNotesEl) exclusionNotesEl.value = '';
      if (sampleViewSaveExclusionBtn) sampleViewSaveExclusionBtn.disabled = false;
      renderExclusionOptions(mergedRowData);
      if (exclusionStatusEl && (!Array.isArray(mergedRowData.notification_issues) || !mergedRowData.notification_issues.length)) {
        exclusionStatusEl.textContent = <?= json_encode(t('bdr_distance_notification_exclusion_none', 'Tiada isu untuk dikecualikan bagi rekod ini.')) ?>;
      }
      viewModalEl.dataset.activeRowIndex = activeRow ? String(activeRow.getAttribute('data-row-index') || '') : '';
      renderRoutePreview(routePoints);
      if (window.bootstrap && sampleViewInfoTabEl) {
        const targetTabEl = modalOptions.preserveActiveTab && activeTabId
          ? document.getElementById(activeTabId)
          : sampleViewInfoTabEl;
        bootstrap.Tab.getOrCreateInstance(targetTabEl || sampleViewInfoTabEl).show();
      }
      if (!modalOptions.keepOpen || !viewModalEl.classList.contains('show')) {
        viewModal.show();
      }
    }

    function loadRoutePreviewForActiveModal() {
      if (!viewModalEl || !viewModalEl.classList.contains('show')) return;
      const rowIndex = String(viewModalEl.dataset.activeRowIndex || '').trim();
      if (rowIndex === '') return;

      const activeRow = document.querySelector('tr[data-row-index="' + rowIndex + '"]');
      if (!activeRow) return;

      const activeDistanceCell = activeRow.querySelector('.js-distance-cell');
      const rowAddressStatus = String(activeRow.getAttribute('data-address-status') || '').trim();
      const currentDistanceText = activeDistanceCell ? String(activeDistanceCell.textContent || '').trim() : '';
      let routePoints = [];
      try {
        routePoints = JSON.parse((activeDistanceCell ? activeDistanceCell.dataset.routePoints : '') || (activeRow ? activeRow.dataset.routePoints : '') || '[]');
      } catch (err) {
        routePoints = [];
      }

      if (routePoints.length >= 2) {
        renderRoutePreview(routePoints);
        return;
      }

      if (rowAddressStatus !== 'VALID' || !isDistanceCalculatedText(currentDistanceText) || activeRow.dataset.routePreviewInflight === '1') {
        renderRoutePreview([]);
        return;
      }

      if (sampleRoutePreviewEl) {
        sampleRoutePreviewEl.className = 'route-preview-empty';
        sampleRoutePreviewEl.textContent = <?= json_encode(t('bdr_distance_route_preview_generating', 'Sedang menjana visual laluan...')) ?>;
      }

      activeRow.dataset.routePreviewInflight = '1';
      fetchDistanceDetailForRow(activeRow)
        .then(function(detailPayload) {
          if (!viewModalEl.classList.contains('show') || String(viewModalEl.dataset.activeRowIndex || '') !== rowIndex) {
            return;
          }
          if (!detailPayload || !detailPayload.found) {
            renderRoutePreview([]);
            return;
          }

          const meta = buildDistanceTitle(detailPayload, rowAddressStatus);
          updateDistanceCell(activeDistanceCell, {
            distance_label: meta.distanceLabel || currentDistanceText,
            direction_url: String(detailPayload.direction_url || (activeDistanceCell ? activeDistanceCell.getAttribute('data-direction-url') : '') || ''),
            title: meta.title,
            matched_query: String(detailPayload.matched_query || ''),
            provider_display_name: String(detailPayload.provider_display_name || detailPayload.matched_query || ''),
            home_coords_label: String(detailPayload.home_coords_label || ''),
            match_quality: String(detailPayload.match_quality || ''),
            distance_source: String(detailPayload.distance_source || ''),
            route_provider: String(detailPayload.route_provider || ''),
            cache_origin: String(detailPayload.cache_origin || 'db'),
            debug_reason: String(detailPayload.debug_reason || ''),
            route_points: Array.isArray(detailPayload.route_points) ? detailPayload.route_points : [],
            distance_attempted: true
          });
          renderRoutePreview(Array.isArray(detailPayload.route_points) ? detailPayload.route_points : []);
        })
        .catch(function() {
          renderRoutePreview([]);
        })
        .finally(function() {
          activeRow.dataset.routePreviewInflight = '0';
        });
    }

    function fetchDistanceDetailForRow(row) {
      if (!row) return Promise.resolve(null);
      const formData = new FormData();
      formData.append('address', String(row.getAttribute('data-address') || ''));
      formData.append('address_status', String(row.getAttribute('data-address-status') || ''));
      formData.append('alamat1', String(row.getAttribute('data-alamat1') || ''));
      formData.append('alamat2', String(row.getAttribute('data-alamat2') || ''));
      formData.append('alamat3', String(row.getAttribute('data-alamat3') || ''));
      formData.append('poskod', String(row.getAttribute('data-poskod') || ''));
      formData.append('negeri', String(row.getAttribute('data-negeri') || ''));
      formData.append('negara', String(row.getAttribute('data-negara') || ''));
      formData.append('site_code', activeSiteCode);

      return fetch(distanceDetailUrl, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(function(response) {
        return response.json();
      })
      .catch(function() {
        return null;
      });
    }

    function updateStatsSummary() {
      const rows = dt.rows({ search: 'applied' }).nodes().toArray();
      const stats = {
        total: 0,
        calculated: 0,
        failed: 0,
        empty: 0,
        invalid: 0,
        pending: 0,
        near: 0,
        equal: 0,
        far: 0
      };

      rows.forEach(function(row) {
        stats.total += 1;
        const addressStatus = String(row.getAttribute('data-address-status') || '');
        const distanceAttempted = String(row.getAttribute('data-distance-attempted') || '') === '1';
        const isReviewLocation = String(row.getAttribute('data-review-location') || '') === '1';
        const cell = row.querySelector('.js-distance-cell');
        const text = String(cell ? cell.textContent.trim() : '');
        const km = parseFloat(String(cell ? (cell.dataset.distanceKm || '') : '').replace(/[^0-9.]/g, ''));

        if (addressStatus === 'EMPTY') {
          stats.empty += 1;
          return;
        }

        if (addressStatus === 'INVALID') {
          stats.invalid += 1;
          return;
        }

        if (text === <?= json_encode(t('bdr_distance_status_failed', 'Gagal Kira')) ?>) {
          stats.failed += 1;
          return;
        }

        if (isReviewLocation || (distanceAttempted && !Number.isFinite(km))) {
          stats.failed += 1;
          return;
        }

        if (Number.isFinite(km)) {
          stats.calculated += 1;
          if (km < 8) {
            stats.near += 1;
          } else if (km > 8) {
            stats.far += 1;
          } else {
            stats.equal += 1;
          }
          return;
        }

        stats.pending += 1;
      });

      document.getElementById('statsTotalRecords').textContent = String(stats.total);
      document.getElementById('statsCalculated').textContent = String(stats.calculated);
      document.getElementById('statsFailed').textContent = String(stats.failed);
      document.getElementById('statsEmptyAddress').textContent = String(stats.empty);
      document.getElementById('statsInvalidAddress').textContent = String(stats.invalid);
      document.getElementById('statsPending').textContent = String(stats.pending);
      document.getElementById('statsNear').textContent = String(stats.near);
      document.getElementById('statsEqual').textContent = String(stats.equal);
      document.getElementById('statsFar').textContent = String(stats.far);
    }

    jQuery(document).on('click', '.js-view-row', function(){
      openViewModal(parseRowPayload(this), this.closest('tr'));
    });

    jQuery(document).on('click', '#openStatsSummary', function(){
      updateStatsSummary();
      if (statsModal) {
        statsModal.show();
      }
    });

    if (exportXlsBtn) {
      exportXlsBtn.addEventListener('click', function() {
        exportCurrentTableToXls();
      });
    }

    if (window.bootstrap) {
      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
        new bootstrap.Tooltip(element, {
          container: 'body',
          boundary: document.body,
          placement: 'top',
          fallbackPlacements: ['top', 'bottom', 'left', 'right']
        });
      });
    }

    function updateDistanceCell(cell, payload) {
      if (!cell) return;
      const title = payload.title || cell.getAttribute('title') || '';
      const matchedQuery = String(payload.matched_query || '');
      const providerDisplayName = String(payload.provider_display_name || matchedQuery || '');
      const homeCoords = String(payload.home_coords_label || '');
      const matchQuality = String(payload.match_quality || '');
      const distanceSource = String(payload.distance_source || '');
      const routeProvider = String(payload.route_provider || '');
      const debugReason = String(payload.debug_reason || '');
      const cacheOrigin = String(payload.cache_origin || 'none');
      const routePoints = Array.isArray(payload.route_points) ? payload.route_points : [];
      const isReviewLocation = !!payload.is_review_location
        || String(payload.distance_label || '') === reviewLocationLabel
        || matchQuality === 'LOW'
        || debugReason === 'low_match_quality';
      const numericKm = payload.distance_label ? parseFloat(String(payload.distance_label).replace(/[^0-9.]/g, '')) : NaN;
      let distanceStateClass = '';
      if (Number.isFinite(numericKm)) {
        if (numericKm > 8) {
          distanceStateClass = 'distance-far';
        } else if (numericKm === 8) {
          distanceStateClass = 'distance-equal';
        } else if (numericKm < 8) {
          distanceStateClass = 'distance-near';
        }
      } else if (String(payload.distance_label || '') === <?= json_encode(t('bdr_distance_status_failed', 'Gagal Kira')) ?>) {
        distanceStateClass = 'distance-failed';
      } else if (String(payload.distance_label || '') === <?= json_encode(t('bdr_distance_status_calculating', 'Mengira...')) ?>) {
        distanceStateClass = 'distance-failed';
      } else if (String(payload.distance_label || '') === <?= json_encode(t('bdr_distance_status_incomplete', 'Alamat tidak lengkap')) ?>) {
        distanceStateClass = 'distance-failed';
      } else if (String(payload.distance_label || '') === <?= json_encode(t('bdr_distance_not_calculated', 'Belum Dikira')) ?>) {
        distanceStateClass = 'distance-failed';
      } else if (String(payload.distance_label || '') === reviewLocationLabel) {
        distanceStateClass = 'distance-failed';
      } else if (String(payload.distance_label || '') === <?= json_encode(t('bdr_distance_status_no_address', 'Tiada Alamat')) ?>) {
        distanceStateClass = 'distance-no-address';
      }
      if (payload.distance_label && payload.direction_url && Number.isFinite(numericKm)) {
        cell.outerHTML = '<a href="' + String(payload.direction_url).replace(/"/g, '&quot;') + '" target="_blank" rel="noopener noreferrer" class="distance-link truncate-1line js-distance-cell ' + distanceStateClass + '" data-bs-toggle="tooltip" data-bs-custom-class="template-tooltip" title="' + String(title).replace(/"/g, '&quot;') + '">' + String(payload.distance_label) + '</a>';
      } else {
        cell.textContent = payload.distance_label || '-';
        cell.classList.remove('distance-pending');
        cell.classList.remove('distance-near', 'distance-far', 'distance-failed');
        if (distanceStateClass) {
          cell.classList.add(distanceStateClass);
        }
        cell.setAttribute('title', title);
      }

      const targetCell = cell.closest('td');
      const target = targetCell ? targetCell.querySelector('.js-distance-cell') : null;
      const row = cell.closest('tr');
      if (target) {
        target.setAttribute('data-direction-url', String(payload.direction_url || target.getAttribute('data-direction-url') || ''));
        target.dataset.matchedQuery = matchedQuery;
        target.dataset.providerDisplayName = providerDisplayName;
        target.dataset.homeCoords = homeCoords;
        target.dataset.matchQuality = matchQuality;
        target.dataset.distanceSource = distanceSource;
        target.dataset.cacheOrigin = cacheOrigin;
        target.dataset.routeProvider = routeProvider;
        target.dataset.debugReason = debugReason;
        target.dataset.routePoints = JSON.stringify(routePoints);
        target.dataset.distanceKm = Number.isFinite(numericKm) ? String(numericKm) : '';
        target.classList.remove('distance-near', 'distance-far', 'distance-equal', 'distance-failed', 'distance-no-address');
        if (distanceStateClass) {
          target.classList.add(distanceStateClass);
        }
      }
      if (row) {
        if (Object.prototype.hasOwnProperty.call(payload, 'address_status')) {
          row.dataset.addressStatus = String(payload.address_status || '');
        }
        row.dataset.matchedQuery = matchedQuery;
        row.dataset.providerDisplayName = providerDisplayName;
        row.dataset.homeCoords = homeCoords;
        row.dataset.matchQuality = matchQuality;
        row.dataset.distanceSource = distanceSource;
        row.dataset.cacheOrigin = cacheOrigin;
        row.dataset.routeProvider = routeProvider;
        row.dataset.debugReason = debugReason;
        row.dataset.routePoints = JSON.stringify(routePoints);
        row.dataset.distanceAttempted = payload.distance_attempted === false ? '0' : '1';
        row.dataset.reviewLocation = isReviewLocation ? '1' : '0';
        row.dataset.addressChanged = payload.is_address_changed ? '1' : '0';
        if (Object.prototype.hasOwnProperty.call(payload, 'notification_address_hash')) {
          row.setAttribute('data-address-hash', String(payload.notification_address_hash || ''));
        }
        if (Object.prototype.hasOwnProperty.call(payload, 'detected_state')) {
          row.setAttribute('data-detected-state', String(payload.detected_state || ''));
        }
        row.dataset.showEmailReminder = Object.prototype.hasOwnProperty.call(payload, 'show_email_reminder')
          ? (payload.show_email_reminder ? '1' : '0')
          : ((
            row.dataset.addressStatus === 'EMPTY'
            || row.dataset.addressStatus === 'INVALID'
            || String(row.dataset.addressChanged || '') === '1'
            || isReviewLocation
          ) ? '1' : '0');
        const resolvedIssueType = String(payload.issue_type || '').trim() || getIssueTypeFromState(
          String(row.dataset.addressStatus || ''),
          String(row.dataset.addressChanged || '') === '1',
          isReviewLocation
        );
        row.dataset.issueType = resolvedIssueType;
        const confirmedIssueTypes = Array.isArray(payload.confirmed_notification_issues)
          ? payload.confirmed_notification_issues.map(function(type) { return String(type || '').trim(); })
          : [];
        row.dataset.issueConfirmed = resolvedIssueType !== 'none' && confirmedIssueTypes.indexOf(resolvedIssueType) !== -1 ? '1' : '0';
        row.classList.remove('table-distance-near-soft', 'table-danger-soft', 'table-warning-soft');
        if (row.dataset.reviewLocation === '1') {
          row.classList.add('table-danger-soft');
        } else if (row.dataset.addressChanged === '1') {
          row.classList.add('table-warning-soft');
        } else if (Number.isFinite(numericKm) && numericKm < 8) {
          row.classList.add('table-distance-near-soft');
        }
        const statusCell = row.querySelector('td.col-status');
        if (statusCell) {
          statusCell.innerHTML = renderIssueStatusHtml(String(row.dataset.issueType || 'none'), String(row.dataset.issueConfirmed || '') === '1');
          if (window.bootstrap) {
            statusCell.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
              new bootstrap.Tooltip(el, {
                container: 'body',
                boundary: document.body,
                trigger: 'hover focus'
              });
            });
          }
        }
        syncReviewRowState(row);
      }

      if (window.bootstrap) {
        if (target) {
          new bootstrap.Tooltip(target, {
            container: 'body',
            boundary: document.body,
            placement: 'top',
            fallbackPlacements: ['top', 'bottom', 'left', 'right']
          });
        }
      }
    }

    function lookupDistanceForRow(row, forceLookup) {
      if (!row) return Promise.resolve();
      if (row.dataset.distanceInflight === '1') return Promise.resolve();

      const cell = row.querySelector('.js-distance-cell');
      if (!cell) return Promise.resolve();

      const address = String(row.getAttribute('data-address') || '').trim();
      const addressStatus = String(row.getAttribute('data-address-status') || '').trim();
      if (!address) {
        updateDistanceCell(cell, {
          distance_label: <?= json_encode(t('bdr_distance_status_no_address', 'Tiada Alamat')) ?>,
          direction_url: '',
          title: <?= json_encode(t('bdr_distance_msg_no_address', 'Jarak tidak dapat dikira kerana tiada alamat')) ?>,
          matched_query: '',
          provider_display_name: '',
          home_coords_label: '',
          match_quality: 'NONE',
          distance_source: '',
          route_provider: '',
          debug_reason: 'address_empty',
          route_points: []
        });
        return Promise.resolve();
      }

      cell.textContent = <?= json_encode(t('bdr_distance_status_calculating', 'Mengira...')) ?>;
      cell.setAttribute('title', calculatingDistanceTitle);
      row.dataset.distanceInflight = '1';

      const formData = new FormData();
      formData.append('address', address);
      formData.append('address_status', addressStatus);
      formData.append('alamat1', String(row.getAttribute('data-alamat1') || ''));
      formData.append('alamat2', String(row.getAttribute('data-alamat2') || ''));
      formData.append('alamat3', String(row.getAttribute('data-alamat3') || ''));
      formData.append('poskod', String(row.getAttribute('data-poskod') || ''));
      formData.append('negeri', String(row.getAttribute('data-negeri') || ''));
      formData.append('negara', String(row.getAttribute('data-negara') || ''));
      formData.append('f_stafID', String(row.getAttribute('data-staf-id') || ''));
      formData.append('site_code', activeSiteCode);
      formData.append('force', forceLookup ? '1' : '0');

      return fetch(distanceLookupUrl, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(function(response) {
        return response.json();
      })
      .then(function(payload) {
        const matchQuality = String(payload.match_quality || '');
        const message = String(payload.message || '');
        let distanceLabel = String(payload.distance_label || '');
        let title = '';
        if (matchQuality === 'LOW' || message === 'low_match_quality') {
          distanceLabel = <?= json_encode(t('bdr_distance_status_review_location', 'Semak Lokasi')) ?>;
          title = <?= json_encode(t('bdr_distance_msg_low_match', 'Lokasi geocode meragukan. Sila semak alamat dan koordinat.')) ?>;
        } else if (!distanceLabel) {
          title = <?= json_encode(t('bdr_distance_msg_unknown', 'Jarak belum dapat ditentukan')) ?>;
          if (addressStatus === 'VALID') {
            distanceLabel = <?= json_encode(t('bdr_distance_status_failed', 'Gagal Kira')) ?>;
          }
        } else {
          title = String(payload.distance_source || '') === 'route'
            ? routeDistanceTitle
            : straightDistanceTitle;
        }

        updateDistanceCell(cell, {
          distance_label: distanceLabel,
          direction_url: String(payload.direction_url || cell.getAttribute('data-direction-url') || ''),
          title: title,
          matched_query: String(payload.matched_query || ''),
          provider_display_name: String(payload.provider_display_name || payload.matched_query || ''),
          home_coords_label: String(payload.home_coords_label || ''),
          match_quality: matchQuality,
          is_review_location: !!payload.is_review_location || matchQuality === 'LOW' || message === 'low_match_quality',
          distance_source: String(payload.distance_source || ''),
          route_provider: String(payload.route_provider || ''),
          cache_origin: String(payload.cache_origin || 'none'),
          debug_reason: String(payload.debug_reason || ''),
          route_points: Array.isArray(payload.route_points) ? payload.route_points : [],
          distance_attempted: true
        });
        applyLookupPayloadFallback(row, {
          distance_km: Object.prototype.hasOwnProperty.call(payload, 'distance_km') ? payload.distance_km : null,
          distance_label: distanceLabel,
          direction_url: String(payload.direction_url || cell.getAttribute('data-direction-url') || ''),
          matched_query: String(payload.matched_query || ''),
          provider_display_name: String(payload.provider_display_name || payload.matched_query || ''),
          home_coords_label: String(payload.home_coords_label || ''),
          match_quality: matchQuality,
          is_review_location: !!payload.is_review_location || matchQuality === 'LOW' || message === 'low_match_quality',
          distance_source: String(payload.distance_source || ''),
          route_provider: String(payload.route_provider || ''),
          cache_origin: String(payload.cache_origin || 'none'),
          debug_reason: String(payload.debug_reason || ''),
          route_points: Array.isArray(payload.route_points) ? payload.route_points : [],
          distance_attempted: true
        });
        return fetchCanonicalRowSnapshot(row).then(function(snapshot) {
          if (snapshot) {
            syncCanonicalRowState(row, snapshot);
          } else {
            refreshOpenViewModalForRow(row);
          }
        });
      })
      .then(function() {
        if (dt && typeof dt.draw === 'function') {
          dt.draw(false);
        }
        updateStatsSummary();
      })
      .catch(function() {
        updateDistanceCell(cell, {
          distance_label: <?= json_encode(t('bdr_distance_status_failed', 'Gagal Kira')) ?>,
          direction_url: String(cell.getAttribute('data-direction-url') || ''),
          title: <?= json_encode(t('bdr_distance_msg_lookup_failed', 'Lookup jarak gagal')) ?>,
          matched_query: '',
          provider_display_name: '',
          home_coords_label: '',
          match_quality: 'NONE',
          is_review_location: false,
          distance_source: '',
          route_provider: '',
          cache_origin: 'none',
          debug_reason: 'lookup_request_failed',
          route_points: [],
          distance_attempted: true
        });
      })
      .finally(function() {
        row.dataset.distanceInflight = '0';
      });
    }

    function renderRoutePreview(routePoints) {
      const container = document.getElementById('sampleRoutePreview');
      if (!container) return;

      if (!Array.isArray(routePoints) || routePoints.length < 2) {
        container.className = 'route-preview-empty';
        container.textContent = <?= json_encode(t('bdr_distance_route_preview_empty', 'Tiada visual laluan')) ?>;
        return;
      }

      const width = 760;
      const height = 280;
      const pad = 28;
      const lats = routePoints.map(function(p) { return Number(p.lat || 0); }).filter(Number.isFinite);
      const lons = routePoints.map(function(p) { return Number(p.lon || 0); }).filter(Number.isFinite);
      if (lats.length < 2 || lons.length < 2) {
        container.className = 'route-preview-empty';
        container.textContent = <?= json_encode(t('bdr_distance_route_preview_empty', 'Tiada visual laluan')) ?>;
        return;
      }
      const minLat = Math.min.apply(null, lats);
      const maxLat = Math.max.apply(null, lats);
      const minLon = Math.min.apply(null, lons);
      const maxLon = Math.max.apply(null, lons);
      const clampLat = function(lat) {
        return Math.max(-85.05112878, Math.min(85.05112878, Number(lat || 0)));
      };
      const worldSize = function(zoom) {
        return 256 * Math.pow(2, zoom);
      };
      const lonToPixel = function(lon, zoom) {
        return ((Number(lon || 0) + 180) / 360) * worldSize(zoom);
      };
      const latToPixel = function(lat, zoom) {
        const clamped = clampLat(lat);
        const sinLat = Math.sin(clamped * Math.PI / 180);
        return (0.5 - Math.log((1 + sinLat) / (1 - sinLat)) / (4 * Math.PI)) * worldSize(zoom);
      };
      let zoom = 18;
      for (let candidateZoom = 18; candidateZoom >= 5; candidateZoom--) {
        const left = lonToPixel(minLon, candidateZoom);
        const right = lonToPixel(maxLon, candidateZoom);
        const top = latToPixel(maxLat, candidateZoom);
        const bottom = latToPixel(minLat, candidateZoom);
        if ((right - left) <= (width - pad * 2) && (bottom - top) <= (height - pad * 2)) {
          zoom = candidateZoom;
          break;
        }
      }

      const centerLon = (minLon + maxLon) / 2;
      const centerLat = (minLat + maxLat) / 2;
      const centerPx = {
        x: lonToPixel(centerLon, zoom),
        y: latToPixel(centerLat, zoom)
      };
      const viewportOrigin = {
        x: centerPx.x - (width / 2),
        y: centerPx.y - (height / 2)
      };

      const project = function(point) {
        return {
          x: lonToPixel(Number(point.lon || 0), zoom) - viewportOrigin.x,
          y: latToPixel(Number(point.lat || 0), zoom) - viewportOrigin.y
        };
      };
      const svgPoints = routePoints.map(function(point) {
        const projected = project(point);
        return projected.x.toFixed(2) + ',' + projected.y.toFixed(2);
      }).join(' ');
      const start = project(routePoints[0]);
      const end = project(routePoints[routePoints.length - 1]);
      const tileStartX = Math.floor(viewportOrigin.x / 256);
      const tileEndX = Math.floor((viewportOrigin.x + width) / 256);
      const tileStartY = Math.floor(viewportOrigin.y / 256);
      const tileEndY = Math.floor((viewportOrigin.y + height) / 256);
      const tileLimit = Math.pow(2, zoom);
      let tileHtml = '';
      for (let tileX = tileStartX; tileX <= tileEndX; tileX++) {
        for (let tileY = tileStartY; tileY <= tileEndY; tileY++) {
          if (tileY < 0 || tileY >= tileLimit) {
            continue;
          }
          const wrappedTileX = ((tileX % tileLimit) + tileLimit) % tileLimit;
          const left = Math.round((tileX * 256) - viewportOrigin.x);
          const top = Math.round((tileY * 256) - viewportOrigin.y);
          const src = 'https://tile.openstreetmap.org/' + zoom + '/' + wrappedTileX + '/' + tileY + '.png';
          tileHtml += '<img class="route-preview-tile" src="' + escapeHtml(src) + '" alt="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" style="left:' + left + 'px;top:' + top + 'px;">';
        }
      }

      container.className = 'route-preview route-preview--map';
      container.innerHTML =
        '<div class="route-preview-map" style="width:' + width + 'px;height:' + height + 'px;">' +
          '<div class="route-preview-tiles">' + tileHtml + '</div>' +
          '<svg viewBox="0 0 ' + width + ' ' + height + '" class="route-preview-svg" role="img" aria-label="<?= h(t('bdr_distance_route_preview_aria', 'Visual laluan provider')) ?>">' +
            '<polyline points="' + svgPoints + '" fill="none" stroke="rgba(255,255,255,0.95)" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"></polyline>' +
            '<polyline points="' + svgPoints + '" fill="none" stroke="#0f766e" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></polyline>' +
            '<circle cx="' + start.x.toFixed(2) + '" cy="' + start.y.toFixed(2) + '" r="8" fill="#2563eb" stroke="#ffffff" stroke-width="3"></circle>' +
            '<circle cx="' + end.x.toFixed(2) + '" cy="' + end.y.toFixed(2) + '" r="8" fill="#dc2626" stroke="#ffffff" stroke-width="3"></circle>' +
            '<text x="' + (start.x + 12).toFixed(2) + '" y="' + (start.y - 10).toFixed(2) + '" class="route-preview-label"><?= h(t('bdr_distance_route_label_home', 'RUMAH')) ?></text>' +
            '<text x="' + (end.x + 12).toFixed(2) + '" y="' + (end.y - 10).toFixed(2) + '" class="route-preview-label">' + escapeHtml(activeOfficeLabel) + '</text>' +
          '</svg>' +
          '<div class="route-preview-attribution">&copy; OpenStreetMap contributors</div>' +
        '</div>';
    }

    if (sampleViewRouteTabEl) {
      sampleViewRouteTabEl.addEventListener('shown.bs.tab', function() {
        loadRoutePreviewForActiveModal();
      });
    }

    if (viewModalEl) {
      viewModalEl.addEventListener('hidden.bs.modal', function() {
        viewModalEl.dataset.activeRowIndex = '';
        if (sampleRoutePreviewEl) {
          sampleRoutePreviewEl.className = 'route-preview-empty';
          sampleRoutePreviewEl.textContent = <?= json_encode(t('bdr_distance_route_preview_empty', 'Tiada visual laluan')) ?>;
        }
      });
    }

    jQuery(document).on('click', '.js-calc-row', function() {
      const row = this.closest('tr');
      lookupDistanceForRow(row, true);
    });

    jQuery(document).on('click', '.js-email-reminder', function() {
      openEmailReminderModal(this.closest('tr'));
    });

    const emailReminderSendBtn = document.getElementById('btnEmailReminderSend');

    if (emailReminderSendBtn) {
      emailReminderSendBtn.addEventListener('click', async function() {
        const reminderRecipientName = String(emailReminderModalEl ? (emailReminderModalEl.dataset.staffName || '') : '').trim();
        const reminderRecipientEmailField = document.getElementById('emailReminderRecipientEmail');
        const reminderRecipientEmail = String(reminderRecipientEmailField ? reminderRecipientEmailField.value : '').trim();
        const reminderRecipientLabel = reminderRecipientName && reminderRecipientEmail
          ? (reminderRecipientName + ' / ' + reminderRecipientEmail)
          : (reminderRecipientName || reminderRecipientEmail || '-');

        if (window.Swal && typeof window.Swal.fire === 'function') {
          const confirmation = await window.Swal.fire({
            icon: 'question',
            title: <?= json_encode(t('bdr_distance_email_confirm_title', 'Hantar Email Reminder?')) ?>,
            text: <?= json_encode(t('bdr_distance_email_confirm_text', 'Anda pasti mahu hantar reminder kepada')) ?> + ' ' + reminderRecipientLabel + '?',
            showCancelButton: true,
            confirmButtonText: <?= json_encode(t('bdr_distance_email_confirm_yes', 'Ya, Hantar')) ?>,
            cancelButtonText: <?= json_encode(t('bdr_distance_email_confirm_no', 'Batal')) ?>,
          });
          if (!confirmation || confirmation.isConfirmed !== true) {
            return;
          }
        } else if (!window.confirm(<?= json_encode(t('bdr_distance_email_confirm_text', 'Anda pasti mahu hantar reminder kepada')) ?> + ' ' + reminderRecipientLabel + '?')) {
          return;
        }

        try {
          setReminderButtonLoading(emailReminderSendBtn, true, <?= json_encode(t('bdr_distance_email_sending_label', 'Menghantar...')) ?>);
          const payload = await requestEmailReminder(emailReminderSendUrl);
          if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
              icon: 'success',
              title: <?= json_encode(t('bdr_distance_email_send_success_title', 'Emel Berjaya Dihantar')) ?>,
              text: String(payload.message || <?= json_encode(t('bdr_distance_email_send_success', 'Emel reminder berjaya dihantar.')) ?>),
              confirmButtonText: <?= json_encode(t('bdr_distance_btn_ok', 'OK')) ?>
            });
          }
        } catch (error) {
          if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
              icon: 'error',
              title: <?= json_encode(t('bdr_distance_email_send_failed_title', 'Emel Gagal Dihantar')) ?>,
              text: String(error && error.message ? error.message : <?= json_encode(t('bdr_distance_email_send_failed', 'Emel reminder gagal dihantar.')) ?>),
              confirmButtonText: <?= json_encode(t('bdr_distance_btn_ok', 'OK')) ?>
            });
          } else {
            window.alert(String(error && error.message ? error.message : 'Send failed'));
          }
        } finally {
          setReminderButtonLoading(emailReminderSendBtn, false, '');
        }
      });
    }

    if (openBulkEmailModalBtn) {
      openBulkEmailModalBtn.addEventListener('click', function() {
        const summary = fillBulkEmailSummary();
        if (summary.issue_count <= 0) {
          if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
              icon: 'info',
              title: <?= json_encode(t('bdr_distance_bulk_email_no_issue_title', 'Tiada Rekod Bermasalah')) ?>,
              text: <?= json_encode(t('bdr_distance_bulk_email_no_issue_text', 'Tiada rekod bermasalah dalam paparan semasa untuk dimasukkan ke dalam bulk email.')) ?>,
              confirmButtonText: <?= json_encode(t('bdr_distance_btn_ok', 'OK')) ?>
            });
          }
          return;
        }
        if (bulkEmailModal) {
          bulkEmailModal.show();
        }
      });
    }

    if (bulkEmailStartBtn) {
      bulkEmailStartBtn.addEventListener('click', async function() {
        const summary = fillBulkEmailSummary();
        if (summary.eligible_count <= 0) {
          return;
        }

        try {
          setReminderButtonLoading(bulkEmailStartBtn, true, <?= json_encode(t('bdr_distance_bulk_email_starting', 'Memulakan...')) ?>);
          const started = await startBulkEmailJob();
          activeBulkEmailJobId = Number(started && started.job && started.job.id ? started.job.id : 0);
          if (activeBulkEmailJobId <= 0) {
            throw new Error('bulk_email_job_missing');
          }

          if (bulkEmailModal) {
            bulkEmailModal.hide();
          }
          if (bulkEmailProgressModal) {
            bulkEmailProgressModal.show();
          }

          const statusPayload = await getBulkEmailJobStatus(activeBulkEmailJobId);
          updateBulkEmailProgress(statusPayload.job || {});
          clearBulkEmailPoll();
          await processBulkEmailJob(activeBulkEmailJobId);
        } catch (error) {
          if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
              icon: 'error',
              title: <?= json_encode(t('bdr_distance_bulk_email_start_failed_title', 'Bulk Email Gagal Dimulakan')) ?>,
              text: String(error && error.message ? error.message : <?= json_encode(t('bdr_distance_bulk_email_start_failed', 'Bulk email job gagal dimulakan.')) ?>),
              confirmButtonText: <?= json_encode(t('bdr_distance_btn_ok', 'OK')) ?>
            });
          }
        } finally {
          setReminderButtonLoading(bulkEmailStartBtn, false, '');
        }
      });
    }

    jQuery(document).on('change', '.js-bulk-issue-filter', function() {
      syncBulkEmailIssueSelection();
    });

    if (sampleViewSaveExclusionBtn) {
      sampleViewSaveExclusionBtn.addEventListener('click', async function() {
        let rowData = {};
        try {
          rowData = JSON.parse(String(viewModalEl && viewModalEl.dataset.notificationRow ? viewModalEl.dataset.notificationRow : '{}'));
        } catch (err) {
          rowData = {};
        }

        if (!rowData || !rowData.staff_no || !rowData.notification_address_hash) {
          const statusEl = document.getElementById('sampleViewExclusionStatus');
          if (statusEl) {
            statusEl.textContent = <?= json_encode(t('bdr_distance_notification_exclusion_invalid', 'Maklumat rekod tidak lengkap untuk simpan exclude.')) ?>;
          }
          return;
        }

        const statusEl = document.getElementById('sampleViewExclusionStatus');
        try {
          setReminderButtonLoading(sampleViewSaveExclusionBtn, true, <?= json_encode(t('bdr_distance_notification_exclusion_saving', 'Menyimpan...')) ?>);
          if (statusEl) {
            statusEl.textContent = <?= json_encode(t('bdr_distance_notification_exclusion_saving', 'Menyimpan...')) ?>;
          }
          await saveNotificationExclusion(rowData);
          const targetRow = findRowByStaffNo(String(rowData.staff_no || ''));
          const snapshot = targetRow ? await fetchCanonicalRowSnapshot(targetRow) : null;
          if (targetRow && snapshot) {
            syncCanonicalRowState(targetRow, snapshot);
          } else {
            loadRowsData(currentLoadedDepartment || '__ALL__');
          }
          if (statusEl) {
            statusEl.textContent = <?= json_encode(t('bdr_distance_notification_exclusion_saved', 'Tetapan exclude berjaya disimpan.')) ?>;
          }
        } catch (error) {
          if (statusEl) {
            statusEl.textContent = String(error && error.message ? error.message : <?= json_encode(t('bdr_distance_notification_exclusion_failed', 'Tetapan exclude gagal disimpan.')) ?>);
          }
        } finally {
          setReminderButtonLoading(sampleViewSaveExclusionBtn, false, '');
        }
      });
    }

    function collectFilteredRows() {
      return dt.rows({ search: 'applied' }).nodes().toArray();
    }

    function buildDistanceTitle(payload, addressStatus) {
      const matchQuality = String(payload.match_quality || '');
      const message = String(payload.message || '');
      const distanceLabel = String(payload.distance_label || '');
      if (matchQuality === 'LOW' || message === 'low_match_quality') {
        return {
          distanceLabel: <?= json_encode(t('bdr_distance_status_review_location', 'Semak Lokasi')) ?>,
          title: <?= json_encode(t('bdr_distance_msg_low_match', 'Lokasi geocode meragukan. Sila semak alamat dan koordinat.')) ?>
        };
      }
      if (!distanceLabel) {
        return {
          distanceLabel: addressStatus === 'VALID'
            ? <?= json_encode(t('bdr_distance_status_failed', 'Gagal Kira')) ?>
            : '',
          title: <?= json_encode(t('bdr_distance_msg_unknown', 'Jarak belum dapat ditentukan')) ?>
        };
      }
      return {
        distanceLabel: distanceLabel,
        title: String(payload.distance_source || '') === 'route'
          ? routeDistanceTitle
          : straightDistanceTitle
      };
    }

    window.addEventListener('resize', function() {
      applyDepartmentFilterWidth(jQuery('#dtDepartmentFilter'), departmentFilterWidth);
      applyDepartmentFilterWidth(jQuery('#dtIssueFilter'), issueFilterWidth);
    });

    dt.on('draw', function() {
      dt.rows({ page: 'current' }).nodes().toArray().forEach(function(row) {
        syncReviewRowState(row);
      });
      updateStatsSummary();
    });
    dt.rows({ page: 'current' }).nodes().toArray().forEach(function(row) {
      syncReviewRowState(row);
    });
    updateStatsSummary();
  });
})();
</script>
</body>
</html>
