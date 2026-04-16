<?php
declare(strict_types=1);

$NEED_DATATABLES = true;

require_once __DIR__ . '/../includes/init.php';
require_login();

require_once __DIR__ . '/../controllers/BdrDailyReportController.php';

$controllerClass = 'BdrDailyReportController';
$controller = new $controllerClass();
$controller->handleRequest();
$lang = $controller->lang ?? ($_SESSION['lang'] ?? 'ms');
$rows = $controller->rows ?? [];
$pageAlert = $controller->pageAlert ?? 'info';
$pageMessage = $controller->pageMessage ?? '';
$tableEmptyMessage = $controller->tableEmptyMessage ?? 'Tiada rekod BDR diluluskan ditemui.';
$reportStorageReady = !empty($controller->reportStorageReady);
$csrfToken = (string)($_SESSION['csrf_token'] ?? '');

if (!function_exists('h')) {
    function h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('t')) {
    function t(string $key, string $fallback): string
    {
        $value = __($key);
        return ($value === $key || $value === null || $value === '') ? $fallback : (string)$value;
    }
}

$PAGE_TITLE = t('bdr_daily_report_page_title', 'Laporan Harian BDR');
$version = (string)($_ENV['APP_ASSET_VER'] ?? date('ymdHis'));
$pageUrl = function_exists('base_url') ? (string)base_url('pages/bdr-daily-report.php') : 'bdr-daily-report.php';
$printPageUrl = function_exists('base_url') ? (string)base_url('pages/bdr-daily-report-print.php') : 'bdr-daily-report-print.php';
$initialPageMessage = trim((string)$pageMessage);
$initialPageAlert = trim((string)$pageAlert) !== '' ? trim((string)$pageAlert) : 'info';
$currentMonthValue = date('m');
$currentYearValue = date('Y');
$monthLabels = [
  '01' => t('bdr_daily_report_month_01', 'Januari'),
  '02' => t('bdr_daily_report_month_02', 'Februari'),
  '03' => t('bdr_daily_report_month_03', 'Mac'),
  '04' => t('bdr_daily_report_month_04', 'April'),
  '05' => t('bdr_daily_report_month_05', 'Mei'),
  '06' => t('bdr_daily_report_month_06', 'Jun'),
  '07' => t('bdr_daily_report_month_07', 'Julai'),
  '08' => t('bdr_daily_report_month_08', 'Ogos'),
  '09' => t('bdr_daily_report_month_09', 'September'),
  '10' => t('bdr_daily_report_month_10', 'Oktober'),
  '11' => t('bdr_daily_report_month_11', 'November'),
  '12' => t('bdr_daily_report_month_12', 'Disember'),
];
$taskStatusOptions = [
  'SELESAI' => t('bdr_daily_report_status_selesai', 'Selesai'),
  'TIDAK_SELESAI' => t('bdr_daily_report_status_tidak_selesai', 'Tidak Selesai'),
  'DALAM_PROSES' => t('bdr_daily_report_status_dalam_proses', 'Dalam Proses'),
  'DITANGGUHKAN' => t('bdr_daily_report_status_ditangguhkan', 'Ditangguhkan'),
  'MENUNGGU_MAKLUM_BALAS' => t('bdr_daily_report_status_menunggu_maklum_balas', 'Menunggu Maklum Balas'),
];

if (is_array($rows) && $rows !== []) {
  usort($rows, static function ($left, $right): int {
    $leftDate = trim((string)($left['date_sort'] ?? ''));
    $rightDate = trim((string)($right['date_sort'] ?? ''));

    if ($leftDate === $rightDate) {
      return strcmp(
        trim((string)($right['reference_no'] ?? '')),
        trim((string)($left['reference_no'] ?? ''))
      );
    }

    return strcmp($rightDate, $leftDate);
  });
}

$jsTranslations = [
  'datatable' => [
    'lengthMenu' => t('bdr_daily_report_datatable_length', 'Papar _MENU_ rekod'),
    'info' => t('bdr_daily_report_datatable_info', 'Memaparkan _START_ hingga _END_ daripada _TOTAL_ rekod'),
    'infoEmpty' => t('bdr_daily_report_datatable_info_empty', 'Memaparkan 0 hingga 0 daripada 0 rekod'),
    'paginatePrevious' => t('bdr_daily_report_datatable_previous', 'Sebelum'),
    'paginateNext' => t('bdr_daily_report_datatable_next', 'Seterusnya'),
    'zeroRecords' => t('bdr_daily_report_datatable_zero_records', 'Tiada rekod sepadan ditemui'),
    'searchPlaceholder' => t('bdr_daily_report_datatable_search_placeholder', 'Cari rekod'),
  ],
  'entryTable' => [
    'info' => t('bdr_daily_report_task_datatable_info', 'Memaparkan _START_ hingga _END_ daripada _TOTAL_ tugasan'),
    'infoEmpty' => t('bdr_daily_report_task_datatable_info_empty', 'Memaparkan 0 hingga 0 daripada 0 tugasan'),
    'emptyTable' => t('bdr_daily_report_task_datatable_empty', 'Belum ada tugasan ditambah.'),
    'paginatePrevious' => t('bdr_daily_report_datatable_previous', 'Sebelum'),
    'paginateNext' => t('bdr_daily_report_datatable_next', 'Seterusnya'),
    'zeroRecords' => t('bdr_daily_report_task_datatable_zero_records', 'Tiada tugasan ditemui'),
  ],
  'swalTitles' => [
    'success' => t('bdr_daily_report_swal_success_title', 'Berjaya'),
    'warning' => t('bdr_daily_report_swal_warning_title', 'Perhatian'),
    'info' => t('bdr_daily_report_swal_info_title', 'Makluman'),
    'error' => t('bdr_daily_report_swal_error_title', 'Ralat'),
    'question' => t('bdr_daily_report_swal_question_title', 'Pengesahan'),
  ],
  'buttons' => [
    'close' => t('bdr_daily_report_btn_close', 'Tutup'),
    'cancel' => t('bdr_daily_report_btn_cancel', 'Batal'),
    'downloadReport' => t('bdr_daily_report_btn_download_report', 'Download Laporan Harian'),
    'saveDraft' => t('bdr_daily_report_btn_save_draft', 'Simpan Draf'),
    'save' => t('bdr_daily_report_btn_save', 'Simpan'),
    'previewForm' => t('bdr_daily_report_btn_preview_form', 'Preview Borang'),
    'resetItem' => t('bdr_daily_report_btn_reset_item', 'Kosongkan'),
    'addToTable' => t('bdr_daily_report_btn_add_to_table', 'Tambah ke Jadual'),
    'updateTable' => t('bdr_daily_report_btn_update_table', 'Kemaskini Jadual'),
    'editTask' => t('bdr_daily_report_btn_edit_task', 'Edit tugasan'),
    'deleteTask' => t('bdr_daily_report_btn_delete_task', 'Padam tugasan'),
  ],
  'entryActionFill' => t('bdr_daily_report_entry_action_fill', 'Isi laporan'),
  'entryActionUpdate' => t('bdr_daily_report_entry_action_update', 'Kemaskini laporan'),
  'modalDefaultTab' => t('bdr_daily_report_modal_default_tab', 'Maklumat'),
  'modalDefaultField' => t('bdr_daily_report_modal_default_field', 'Maklumat'),
  'requestFailed' => t('bdr_daily_report_request_failed', 'Permintaan tidak berjaya diproses.'),
  'entryLoadFailed' => t('bdr_daily_report_entry_load_failed', 'Laporan harian tidak dapat dimuatkan.'),
  'taskTitleRequired' => t('bdr_daily_report_task_title_required', 'Ketetapan tugas wajib diisi sebelum ditambah ke jadual.'),
  'invalidReportDate' => t('bdr_daily_report_invalid_report_date', 'Tarikh laporan tidak sah.'),
  'recordSaved' => t('bdr_daily_report_record_saved', 'Rekod berjaya disimpan.'),
  'recordSaveFailed' => t('bdr_daily_report_record_save_failed', 'Rekod tidak dapat disimpan.'),
  'confirmSaveDraft' => t('bdr_daily_report_confirm_save_draft', 'Simpan draf laporan harian ini sekarang?'),
  'confirmSaveDraftYes' => t('bdr_daily_report_confirm_save_draft_yes', 'Ya, simpan draf'),
  'confirmSave' => t('bdr_daily_report_confirm_save', 'Simpan laporan harian ini sekarang?'),
  'confirmSaveYes' => t('bdr_daily_report_confirm_save_yes', 'Ya, simpan'),
  'previewRequiresDraft' => t('bdr_daily_report_preview_requires_draft', 'Simpan draf terlebih dahulu sebelum preview borang.'),
  'downloadRequiresDraft' => t('bdr_daily_report_download_requires_draft', 'Simpan draf atau laporan terlebih dahulu sebelum muat turun laporan harian.'),
  'taskStatusLabels' => $taskStatusOptions,
];
$availableMonths = [];
$availableYears = [];
foreach ($rows as $row) {
  $dateSort = trim((string)($row['date_sort'] ?? ''));
  if (preg_match('/^(\d{4})-(\d{2})-\d{2}$/', $dateSort, $matches) !== 1) {
    continue;
  }

  $availableYears[$matches[1]] = $matches[1];
  $availableMonths[$matches[2]] = $monthLabels[$matches[2]] ?? $matches[2];
}

if (!isset($availableMonths[$currentMonthValue])) {
  $availableMonths[$currentMonthValue] = $monthLabels[$currentMonthValue] ?? $currentMonthValue;
}

if (!isset($availableYears[$currentYearValue])) {
  $availableYears[$currentYearValue] = $currentYearValue;
}

krsort($availableYears);
ksort($availableMonths);
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>">
<head>
  <?php include __DIR__ . '/../includes/head.php'; ?>
  <link href="<?= base_url('assets/css/datatables-standard.css') ?>?v=<?= h($version) ?>" rel="stylesheet">
  <link href="<?= base_url('assets/css/pages/bdr-daily-report.css') ?>?v=<?= h($version) ?>" rel="stylesheet">
  <script src="<?= base_url('assets/js/helpers/datatables-standard.js') ?>?v=<?= h($version) ?>"></script>
</head>
<body
  data-topbar-color="<?= h($_SESSION['theme.topbar'] ?? 'light') ?>"
  data-menu-color="<?= h($_SESSION['theme.menu'] ?? 'light') ?>"
  data-layout="vertical"
  data-sidebar-size="default"
  class="loading">

<div class="wrapper">
  <?php include __DIR__ . '/../includes/topbar.php'; ?>
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="content-page">
    <div class="content">
      <div class="container-fluid">

        <div class="row mb-3">
          <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center flex-wrap">
              <h4 class="page-title"><i class="ri-file-list-3-line me-1"></i> <?= h($PAGE_TITLE) ?></h4>
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
                    <h5 class="card-title mb-1"><?= h(t('bdr_daily_report_table_title', 'Senarai BDR Diluluskan')) ?></h5>
                    <p class="text-muted mb-0"><?= h(t('bdr_daily_report_table_subtitle', 'Hanya rekod BDR yang diluluskan oleh ketua jabatan dipaparkan. Klik Lihat untuk melihat butiran penuh')) ?></p>
                  </div>
                </div>

                <div class="report-table-filters" id="reportTableFilters" hidden>
                  <label class="report-filter-field" for="reportMonthFilter" aria-label="<?= h(t('bdr_daily_report_filter_month', 'Bulan')) ?>">
                    <select id="reportMonthFilter" class="form-select form-select-sm" aria-label="<?= h(t('bdr_daily_report_filter_month', 'Bulan')) ?>">
                      <option value=""><?= h(t('bdr_daily_report_filter_month', 'Bulan')) ?></option>
                      <?php foreach ($availableMonths as $monthValue => $monthLabel): ?>
                        <option value="<?= h($monthValue) ?>"<?= $monthValue === $currentMonthValue ? ' selected' : '' ?>><?= h($monthLabel) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </label>
                  <label class="report-filter-field" for="reportYearFilter" aria-label="<?= h(t('bdr_daily_report_filter_year', 'Tahun')) ?>">
                    <select id="reportYearFilter" class="form-select form-select-sm" aria-label="<?= h(t('bdr_daily_report_filter_year', 'Tahun')) ?>">
                      <option value=""><?= h(t('bdr_daily_report_filter_year', 'Tahun')) ?></option>
                      <?php foreach ($availableYears as $yearValue): ?>
                        <option value="<?= h($yearValue) ?>"<?= $yearValue === $currentYearValue ? ' selected' : '' ?>><?= h($yearValue) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </label>
                </div>

                <div class="table-responsive dt-standard">
                  <table class="table table-bordered align-middle w-100" id="userDT">
                    <thead>
                      <tr>
                        <th class="col-bil"><?= h(t('bdr_daily_report_col_no', 'No.')) ?></th>
                        <th class="col-date"><?= h(t('bdr_daily_report_col_date', 'Tarikh BDR')) ?></th>
                        <th class="col-ref"><?= h(t('bdr_daily_report_col_reference', 'No. Rujukan')) ?></th>
                        <th class="col-category"><?= h(t('bdr_daily_report_col_category', 'Kategori')) ?></th>
                        <th class="col-approval"><?= h(t('bdr_daily_report_col_approval', 'Status / Catatan Pengarah')) ?></th>
                        <th class="col-time-in"><?= h(t('bdr_daily_report_col_clock_in', 'Masa Masuk')) ?></th>
                        <th class="col-time-out"><?= h(t('bdr_daily_report_col_clock_out', 'Masa Keluar')) ?></th>
                        <th class="col-status"><?= h(t('bdr_daily_report_col_status', 'Status')) ?></th>
                        <th class="col-actions"><?= h(t('bdr_daily_report_col_actions', 'Tindakan')) ?></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($rows as $index => $row): ?>
                        <?php $rowJson = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
                        <tr data-row-index="<?= h((string)$index) ?>">
                          <td class="col-bil"></td>
                          <td class="col-date" data-order="<?= h((string)($row['date_sort'] ?? '')) ?>">
                            <span class="date-pill"><?= h((string)($row['date'] ?? '-')) ?></span>
                          </td>
                          <td class="col-ref">
                            <span class="truncate-1line" data-bs-toggle="tooltip" title="<?= h((string)($row['reference_no'] ?? '-')) ?>">
                              <?= h((string)($row['reference_no'] ?? '-')) ?>
                            </span>
                          </td>
                          <td class="col-category">
                            <span class="truncate-1line" data-bs-toggle="tooltip" title="<?= h((string)($row['category'] ?? '-')) ?>">
                              <?= h((string)($row['category'] ?? '-')) ?>
                            </span>
                          </td>
                          <td class="col-approval">
                            <?php
                              $approvalFlag = trim((string)($row['approval_flag'] ?? ''));
                              $approvalComment = trim((string)($row['approval_comment'] ?? ''));
                              $approvalTooltip = trim((string)($row['approval_director_name'] ?? ''));
                              $approvalStatusLabel = match ($approvalFlag) {
                                '1' => t('bdr_daily_report_approval_status_passed', 'Lulus'),
                                '0' => t('bdr_daily_report_approval_status_rejected', 'Tidak Lulus'),
                                default => ($approvalFlag !== '' && $approvalFlag !== '-')
                                  ? $approvalFlag
                                  : t('bdr_daily_report_approval_status_unknown', '-'),
                              };
                              $approvalInlineText = $approvalStatusLabel;
                              if ($approvalComment !== '') {
                                $approvalInlineText .= ' / ' . $approvalComment;
                              }
                              $hasApprovalDetail = $approvalInlineText !== '-';
                            ?>
                            <?php if ($hasApprovalDetail): ?>
                              <div class="approval-status-inline">
                                <span class="approval-status-text<?= $approvalFlag === '1' ? ' is-approved' : ($approvalFlag === '0' ? ' is-rejected' : '') ?> truncate-1line" data-bs-toggle="tooltip" title="<?= h($approvalInlineText) ?>"><?= h($approvalInlineText) ?></span>
                                <?php if ($approvalTooltip !== ''): ?>
                                  <button
                                    type="button"
                                    class="approval-status-info"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    data-bs-custom-class="approval-info-tooltip"
                                    title="<?= h($approvalTooltip) ?>"
                                    aria-label="<?= h(t('bdr_daily_report_approval_tooltip_label', 'Maklumat pengarah')) ?>">
                                    <i class="ri-information-2-fill"></i>
                                  </button>
                                <?php endif; ?>
                              </div>
                            <?php else: ?>
                              <span class="status-empty">-</span>
                            <?php endif; ?>
                          </td>
                          <td class="col-time-in">
                            <span class="time-value-chip truncate-1line" data-bs-toggle="tooltip" title="<?= h((string)($row['clock_in'] ?? '-')) ?>">
                              <i class="ri-login-circle-line time-value-chip__icon" aria-hidden="true"></i>
                              <span><?= h((string)($row['clock_in'] ?? '-')) ?></span>
                            </span>
                          </td>
                          <td class="col-time-out">
                            <span class="time-value-chip truncate-1line" data-bs-toggle="tooltip" title="<?= h((string)($row['clock_out'] ?? '-')) ?>">
                              <i class="ri-logout-circle-r-line time-value-chip__icon" aria-hidden="true"></i>
                              <span><?= h((string)($row['clock_out'] ?? '-')) ?></span>
                            </span>
                          </td>
                          <td class="col-status">
                            <?php if (!empty($row['report_badge'])): ?>
                              <span class="report-entry-badge <?= h((string)($row['report_badge_class'] ?? 'is-draft')) ?>" data-entry-status-badge>
                                <?= h((string)$row['report_badge']) ?>
                              </span>
                            <?php else: ?>
                              <span class="status-empty">-</span>
                            <?php endif; ?>
                          </td>
                          <td class="col-actions">
                            <div class="action-btn-group">
                              <button type="button" class="btn btn-outline-primary btn-sm action-btn js-view-row" data-row='<?= h((string)$rowJson) ?>' title="<?= h(t('bdr_daily_report_btn_view', 'Lihat')) ?>">
                                <i class="ri-eye-line"></i>
                              </button>
                              <button type="button" class="btn btn-outline-success btn-sm action-btn js-edit-report-row" data-row='<?= h((string)$rowJson) ?>' title="<?= h((string)($row['report_action_label'] ?? t('bdr_daily_report_entry_action_fill', 'Isi laporan'))) ?>">
                                <i class="ri-file-edit-line"></i>
                              </button>
                              <button
                                type="button"
                                class="btn btn-outline-secondary btn-sm action-btn js-download-report-row"
                                data-row='<?= h((string)$rowJson) ?>'
                                title="<?= h(t('bdr_daily_report_btn_download_report', 'Download Laporan Harian')) ?>"
                                <?= empty($row['entry_id']) ? 'disabled' : '' ?>>
                                <i class="ri-download-2-line"></i>
                              </button>
                            </div>
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
<div class="modal fade report-modal" id="bdrDailyReportViewModal" tabindex="-1" aria-hidden="true" aria-labelledby="bdrDailyReportViewModalTitle">
  <div class="modal-dialog modal-dialog-centered report-entry-modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title" id="bdrDailyReportViewModalTitle">
            <i class="ri-file-search-line"></i> <?= h(t('bdr_daily_report_modal_view_title', 'Butiran Rekod BDR')) ?>
          </h5>
          <div class="report-modal-subtitle"><?= h(t('bdr_daily_report_modal_view_subtitle', 'Paparan terperinci rekod BDR yang telah diluluskan untuk semakan staf.')) ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('bdr_daily_report_btn_close', 'Tutup')) ?>"></button>
      </div>
      <div class="modal-body">
        <div class="report-modal-shell report-view-shell">
          <div class="report-modal-intro">
            <div class="report-modal-intro-icon"><i class="ri-survey-line"></i></div>
            <div>
              <div class="report-modal-intro-title"><?= h(t('bdr_daily_report_modal_view_intro_title', 'Ringkasan Rekod')) ?></div>
              <p class="report-modal-intro-text"><?= h(t('bdr_daily_report_modal_view_intro_text', 'Semak maklumat permohonan, pemohon, dan kelulusan dalam susun atur yang lebih jelas.')) ?></p>
            </div>
          </div>
          <div class="report-view-tabs" id="reportViewTabs"></div>
          <div class="report-view-tab-panels" id="reportViewTabPanels"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="ri-close-line me-1"></i><?= h(t('bdr_daily_report_btn_close', 'Tutup')) ?></button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade report-modal" id="bdrDailyReportEntryModal" tabindex="-1" aria-hidden="true" aria-labelledby="bdrDailyReportEntryModalTitle">
  <div class="modal-dialog modal-dialog-centered report-entry-modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title" id="bdrDailyReportEntryModalTitle">
            <i class="ri-file-edit-line"></i> <?= h(t('bdr_daily_report_modal_entry_title', 'Laporan Harian BDR')) ?>
          </h5>
          <div class="report-modal-subtitle"><?= h(t('bdr_daily_report_modal_entry_subtitle', 'Kemaskini laporan harian mengikut tarikh BDR approved dengan susun atur kerja yang lebih teratur.')) ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(t('bdr_daily_report_btn_close', 'Tutup')) ?>"></button>
      </div>
      <div class="modal-body">
        <div class="report-modal-shell report-entry-shell">
          <input type="hidden" id="reportEntryId" value="">
          <input type="hidden" id="reportEntryDate" value="">

          <div class="report-entry-tabs" id="reportEntryTabs">
            <button type="button" class="report-tab-btn is-active" data-entry-tab-target="info"><i class="ri-information-line me-1"></i><?= h(t('bdr_daily_report_tab_info', 'Info')) ?></button>
            <button type="button" class="report-tab-btn" data-entry-tab-target="entry"><i class="ri-edit-box-line me-1"></i><?= h(t('bdr_daily_report_tab_entry', 'Data Entry')) ?></button>
          </div>

          <div class="report-entry-tab-panels">
            <div class="report-tab-panel is-active" data-entry-tab-panel="info">
              <div class="form-section report-modal-section">
                <div class="form-section-title">
                  <i class="ri-profile-line"></i><?= h(t('bdr_daily_report_section_record_info', 'Maklumat Rekod BDR')) ?>
                </div>
                <div class="report-entry-summary info-card" id="reportEntrySummary">
                  <div class="report-entry-summary-item info-item">
                    <i class="ri-calendar-event-line info-icon"></i>
                    <div class="info-content">
                      <span class="report-entry-summary-label info-label"><?= h(t('bdr_daily_report_summary_date', 'Tarikh BDR')) ?></span>
                      <span class="report-entry-summary-value info-value" id="reportEntrySummaryDate">-</span>
                    </div>
                  </div>
                  <div class="report-entry-summary-item info-item">
                    <i class="ri-user-3-line info-icon"></i>
                    <div class="info-content">
                      <span class="report-entry-summary-label info-label"><?= h(t('bdr_daily_report_summary_staff_name', 'Nama Staf')) ?></span>
                      <span class="report-entry-summary-value info-value" id="reportEntrySummaryStaffName">-</span>
                    </div>
                  </div>
                  <div class="report-entry-summary-item info-item">
                    <i class="ri-id-card-line info-icon"></i>
                    <div class="info-content">
                      <span class="report-entry-summary-label info-label"><?= h(t('bdr_daily_report_summary_staff_id', 'No. Staf / Login ID')) ?></span>
                      <span class="report-entry-summary-value info-value" id="reportEntrySummaryStaffId">-</span>
                    </div>
                  </div>
                  <div class="report-entry-summary-item info-item">
                    <i class="ri-file-list-2-line info-icon"></i>
                    <div class="info-content">
                      <span class="report-entry-summary-label info-label"><?= h(t('bdr_daily_report_summary_reference', 'No. Rujukan')) ?></span>
                      <span class="report-entry-summary-value info-value" id="reportEntrySummaryReference">-</span>
                    </div>
                  </div>
                  <div class="report-entry-summary-item info-item">
                    <i class="ri-bookmark-3-line info-icon"></i>
                    <div class="info-content">
                      <span class="report-entry-summary-label info-label"><?= h(t('bdr_daily_report_summary_category', 'Kategori')) ?></span>
                      <span class="report-entry-summary-value info-value" id="reportEntrySummaryCategory">-</span>
                    </div>
                  </div>
                  <div class="report-entry-summary-item info-item">
                    <i class="ri-user-star-line info-icon"></i>
                    <div class="info-content">
                      <span class="report-entry-summary-label info-label"><?= h(t('bdr_daily_report_summary_approval', 'Kelulusan')) ?></span>
                      <span class="report-entry-summary-value info-value" id="reportEntrySummaryApproval">-</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="report-tab-panel" data-entry-tab-panel="entry">
              <div class="report-entry-workspace">
                <div class="report-entry-panel report-entry-panel-form">
                  <div class="form-section report-modal-section report-entry-editor-section">
                    <div class="form-section-title">
                      <i class="ri-quill-pen-line"></i><?= h(t('bdr_daily_report_section_task_editor', 'Editor Tugasan Harian')) ?>
                    </div>
                    <div class="report-entry-toolbar">
                      <div>
                        <h6 class="mb-1"><?= h(t('bdr_daily_report_task_editor_title', 'Data Tugasan Harian')) ?></h6>
                        <p class="text-muted mb-0 small"><?= h(t('bdr_daily_report_task_editor_subtitle', 'Isi tugasan dan klik tambah untuk masukkan ke dalam jadual.')) ?></p>
                      </div>
                    </div>

                    <div class="report-entry-form-shell">
                      <div class="report-entry-item-grid">
                        <div class="report-entry-field report-entry-field-full">
                          <label class="form-label" for="reportEntryTaskTitle"><i class="ri-task-line me-1"></i><?= h(t('bdr_daily_report_field_task_title', 'Ketetapan Tugas')) ?></label>
                          <textarea class="form-control" id="reportEntryTaskTitle" rows="3" placeholder="<?= h(t('bdr_daily_report_field_task_title_placeholder', 'Nyatakan tugasan yang dilaksanakan')) ?>"></textarea>
                        </div>
                        <div class="report-entry-field report-entry-field-full">
                          <label class="form-label" for="reportEntryTaskComment"><i class="ri-chat-1-line me-1"></i><?= h(t('bdr_daily_report_field_task_comment', 'Ulasan dinyatakan oleh staf')) ?></label>
                          <textarea class="form-control" id="reportEntryTaskComment" rows="4" placeholder="<?= h(t('bdr_daily_report_field_task_comment_placeholder', 'Ulasan atau hasil tugasan')) ?>"></textarea>
                        </div>
                        <div class="report-entry-field report-entry-field-full">
                          <label class="form-label" for="reportEntryTaskStatus"><i class="ri-loader-4-line me-1"></i><?= h(t('bdr_daily_report_field_task_status', 'Status')) ?></label>
                          <select class="form-select" id="reportEntryTaskStatus">
                            <?php foreach ($taskStatusOptions as $statusValue => $statusLabel): ?>
                              <option value="<?= h($statusValue) ?>"><?= h($statusLabel) ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                      </div>

                      <div class="report-entry-form-actions">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="reportEntryResetItemBtn"><i class="ri-eraser-line me-1"></i><?= h(t('bdr_daily_report_btn_reset_item', 'Kosongkan')) ?></button>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="reportEntryAddItemBtn">
                          <i class="ri-add-line me-1"></i><span id="reportEntryAddItemBtnLabel"><?= h(t('bdr_daily_report_btn_add_to_table', 'Tambah ke Jadual')) ?></span>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="report-entry-panel report-entry-panel-table">
                  <div class="form-section report-modal-section report-entry-list-section">
                    <div class="form-section-title">
                      <i class="ri-table-2"></i><?= h(t('bdr_daily_report_section_task_list', 'Senarai Tugasan')) ?>
                    </div>
                    <div class="report-entry-table-header">
                      <div>
                        <h6 class="mb-1"><?= h(t('bdr_daily_report_task_table_title', 'Jadual Tugasan Harian')) ?></h6>
                        <p class="text-muted mb-0 small"><?= h(t('bdr_daily_report_task_table_subtitle', 'Semak, edit, atau padam tugasan yang telah dimasukkan sebelum simpan laporan.')) ?></p>
                      </div>
                    </div>
                    <div class="report-entry-table-wrap dt-standard">
                      <table class="table table-bordered align-middle report-entry-table w-100 mb-0" id="reportEntryTable">
                        <thead>
                          <tr>
                            <th style="width: 7%;"><?= h(t('bdr_daily_report_task_table_col_no', 'Bil.')) ?></th>
                            <th style="width: 33%;"><?= h(t('bdr_daily_report_task_table_col_task', 'Ketetapan Tugas')) ?></th>
                            <th style="width: 30%;"><?= h(t('bdr_daily_report_task_table_col_comment', 'Ulasan Staf')) ?></th>
                            <th style="width: 15%;"><?= h(t('bdr_daily_report_task_table_col_execution', 'Status')) ?></th>
                            <th style="width: 15%;"><?= h(t('bdr_daily_report_task_table_col_action', 'Tindakan')) ?></th>
                          </tr>
                        </thead>
                        <tbody id="reportEntryItems"></tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer report-entry-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal"><i class="ri-close-line me-1"></i><?= h(t('bdr_daily_report_btn_close', 'Tutup')) ?></button>
        <button type="button" class="btn btn-outline-secondary" id="reportEntryPreviewBtn"><i class="ri-eye-line me-1"></i><?= h(t('bdr_daily_report_btn_preview_form', 'Preview Borang')) ?></button>
        <button type="button" class="btn btn-outline-primary" id="reportEntrySaveBtn"><i class="ri-save-3-line me-1"></i><?= h(t('bdr_daily_report_btn_save_draft', 'Simpan Draf')) ?></button>
        <button type="button" class="btn btn-success" id="reportEntrySubmitBtn"><i class="ri-save-3-line me-1"></i><?= h(t('bdr_daily_report_btn_save', 'Simpan')) ?></button>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  document.addEventListener('DOMContentLoaded', function(){
    if (!window.jQuery || !jQuery.fn || !jQuery.fn.DataTable) {
      return;
    }

    const viewModalEl = document.getElementById('bdrDailyReportViewModal');
    const viewModal = (window.bootstrap && viewModalEl) ? new bootstrap.Modal(viewModalEl) : null;
    const entryModalEl = document.getElementById('bdrDailyReportEntryModal');
    const entryModal = (window.bootstrap && entryModalEl) ? new bootstrap.Modal(entryModalEl) : null;
    const filterToolbar = document.getElementById('reportTableFilters');
    const monthFilter = document.getElementById('reportMonthFilter');
    const yearFilter = document.getElementById('reportYearFilter');
    const csrfToken = <?= json_encode($csrfToken) ?>;
    const initialPageMessage = <?= json_encode($initialPageMessage) ?>;
    const initialPageAlert = <?= json_encode($initialPageAlert) ?>;
    const i18n = <?= json_encode($jsTranslations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const reportStorageReady = <?= $reportStorageReady ? 'true' : 'false' ?>;
    const entryItemsEl = document.getElementById('reportEntryItems');
    const entryTableEl = document.getElementById('reportEntryTable');
    const entryIdInput = document.getElementById('reportEntryId');
    const entryDateInput = document.getElementById('reportEntryDate');
    const entryAddItemBtn = document.getElementById('reportEntryAddItemBtn');
    const entryAddItemBtnLabel = document.getElementById('reportEntryAddItemBtnLabel');
    const entryResetItemBtn = document.getElementById('reportEntryResetItemBtn');
    const entrySaveBtn = document.getElementById('reportEntrySaveBtn');
    const entrySubmitBtn = document.getElementById('reportEntrySubmitBtn');
    const entryPreviewBtn = document.getElementById('reportEntryPreviewBtn');
    const entryTaskTitleInput = document.getElementById('reportEntryTaskTitle');
    const entryTaskStatusInput = document.getElementById('reportEntryTaskStatus');
    const entryTaskCommentInput = document.getElementById('reportEntryTaskComment');
    const entrySummaryDate = document.getElementById('reportEntrySummaryDate');
    const entrySummaryStaffName = document.getElementById('reportEntrySummaryStaffName');
    const entrySummaryStaffId = document.getElementById('reportEntrySummaryStaffId');
    const entrySummaryReference = document.getElementById('reportEntrySummaryReference');
    const entrySummaryCategory = document.getElementById('reportEntrySummaryCategory');
    const entrySummaryApproval = document.getElementById('reportEntrySummaryApproval');
    const pageUrl = <?= json_encode($pageUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const printPageUrl = <?= json_encode($printPageUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let activeEntryTriggerRow = null;
    let draftEntryItems = [];
    let editingEntryItemIndex = -1;
    let entryTableInstance = null;

    jQuery.fn.dataTable.ext.search.push(function(settings, data) {
      if (!settings || settings.nTable.getAttribute('id') !== 'userDT') {
        return true;
      }

      const dateText = String(data[1] || '').trim();
      if (dateText === '') {
        return true;
      }

      const dateParts = dateText.split('/');
      if (dateParts.length !== 3) {
        return true;
      }

      const rowMonth = dateParts[1] || '';
      const rowYear = dateParts[2] || '';
      const selectedMonth = monthFilter ? String(monthFilter.value || '') : '';
      const selectedYear = yearFilter ? String(yearFilter.value || '') : '';

      if (selectedMonth !== '' && rowMonth !== selectedMonth) {
        return false;
      }

      if (selectedYear !== '' && rowYear !== selectedYear) {
        return false;
      }

      return true;
    });

    const dataTable = jQuery('#userDT').DataTable({
      pageLength: 10,
      lengthChange: true,
      lengthMenu: [10, 25, 50, 100, 200],
      ordering: true,
      order: [[1, 'desc']],
      autoWidth: false,
      scrollX: false,
      dom:
        '<"row mb-2"<"col-sm-12 col-md-6 dt-top-left"l><"col-sm-12 col-md-6 d-flex justify-content-md-end dt-top-right"f>>' +
        't' +
        '<"dt-bottom-row mt-2 d-flex justify-content-between align-items-center"<"dt-info-left"i><"dt-paging-right d-flex justify-content-end"p>>',
      language: {
        lengthMenu: i18n.datatable.lengthMenu,
        search: '',
        info: i18n.datatable.info,
        infoEmpty: i18n.datatable.infoEmpty,
        emptyTable: <?= json_encode($tableEmptyMessage) ?>,
        paginate: {
          previous: i18n.datatable.paginatePrevious,
          next: i18n.datatable.paginateNext
        },
        zeroRecords: i18n.datatable.zeroRecords
      },
      columnDefs: [
        { targets: 0, orderable: false, searchable: false, width: 56 },
        { targets: 8, orderable: false, searchable: false, width: 96 }
      ],
      rowCallback: function(row, data, displayIndex){
        const api = this.api();
        const info = api.page.info();
        jQuery('td:eq(0)', row).text(info.start + displayIndex + 1);
      },
      initComplete: function() {
        const wrapper = document.querySelector('#userDT_wrapper .dt-top-right');
        if (wrapper && filterToolbar) {
          filterToolbar.hidden = false;
          wrapper.insertBefore(filterToolbar, wrapper.firstChild);
        }

        if (window.DataTableStandard && typeof window.DataTableStandard.decorate === 'function') {
          window.DataTableStandard.decorate('#userDT', {
            searchPlaceholder: i18n.datatable.searchPlaceholder
          });
        }
      }
    });

    [monthFilter, yearFilter].forEach(function(filterElement) {
      if (!filterElement) {
        return;
      }

      filterElement.addEventListener('change', function() {
        dataTable.draw();
      });
    });

    function parseRowPayload(button) {
      try {
        return JSON.parse(button.getAttribute('data-row') || '{}');
      } catch (error) {
        return {};
      }
    }

    function mapAlertTypeToSwal(type) {
      const normalized = String(type || 'info').trim().toLowerCase();
      if (normalized === 'success') {
        return { icon: 'success', title: i18n.swalTitles.success };
      }
      if (normalized === 'warning') {
        return { icon: 'warning', title: i18n.swalTitles.warning };
      }
      if (normalized === 'info') {
        return { icon: 'info', title: i18n.swalTitles.info };
      }

      return { icon: 'error', title: i18n.swalTitles.error };
    }

    async function showSwalMessage(message, type) {
      const text = String(message || '').trim();
      if (text === '') {
        return;
      }

      const config = mapAlertTypeToSwal(type);
      if (window.Swal && typeof window.Swal.fire === 'function') {
        await window.Swal.fire({
          icon: config.icon,
          title: config.title,
          text: text,
          zIndex: 20000,
          confirmButtonText: i18n.buttons.close,
          allowOutsideClick: false,
          allowEscapeKey: true,
          timer: undefined,
          timerProgressBar: false
        });
        return;
      }

      window.alert(text);
    }

    async function confirmSwalMessage(message, confirmText) {
      const text = String(message || '').trim();
      const okText = String(confirmText || i18n.buttons.save);

      if (!(window.Swal && typeof window.Swal.fire === 'function')) {
        return window.confirm(text);
      }

      const result = await window.Swal.fire({
        icon: 'question',
        title: i18n.swalTitles.question,
        text: text,
        zIndex: 20000,
        showCancelButton: true,
        confirmButtonText: okText,
        cancelButtonText: i18n.buttons.cancel,
        allowOutsideClick: false,
        allowEscapeKey: true
      });

      return !!result.isConfirmed;
    }

    function setSummaryText(element, value) {
      if (!element) {
        return;
      }

      const text = String(value || '').trim();
      element.textContent = text !== '' ? text : '-';
    }

    function resetEntryModal(row) {
      const payload = row || {};
      if (entryIdInput) {
        entryIdInput.value = payload.entry_id ? String(payload.entry_id) : '';
      }
      if (entryDateInput) {
        entryDateInput.value = String(payload.date_sort || '');
      }

      setSummaryText(entrySummaryDate, payload.date || '-');
      setSummaryText(entrySummaryStaffName, payload.source_staff_name || '-');
      setSummaryText(entrySummaryStaffId, payload.source_staff_id || '-');
      setSummaryText(entrySummaryReference, payload.reference_no || '-');
      setSummaryText(entrySummaryCategory, payload.category || '-');
      setSummaryText(entrySummaryApproval, payload.approval_name || '-');
      draftEntryItems = [];
      resetEntryForm();
      renderEntryItems();
      activateEntryTab('info');
    }

    function resetEntryForm() {
      editingEntryItemIndex = -1;
      if (entryTaskTitleInput) {
        entryTaskTitleInput.value = '';
      }
      if (entryTaskStatusInput) {
        entryTaskStatusInput.value = 'SELESAI';
      }
      if (entryTaskCommentInput) {
        entryTaskCommentInput.value = '';
      }
      if (entryAddItemBtnLabel) {
        entryAddItemBtnLabel.textContent = i18n.buttons.addToTable;
      }
    }

    function escapeHtml(value) {
      return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function getTaskExecutionStatusLabel(status) {
      const normalized = String(status || 'SELESAI').trim().toUpperCase();
      const labels = i18n.taskStatusLabels || {};

      return labels[normalized] || labels.SELESAI || 'Selesai';
    }

    function renderEntryItems() {
      if (!entryItemsEl) {
        return;
      }

      if (entryTableInstance) {
        entryTableInstance.destroy();
        entryTableInstance = null;
      }

      entryItemsEl.innerHTML = '';
      draftEntryItems.forEach(function(item, index) {
        const row = document.createElement('tr');
        row.innerHTML = '' +
          '<td>' + String(index + 1) + '</td>' +
          '<td>' + escapeHtml(item.task_title || '-') + '</td>' +
          '<td>' + escapeHtml(item.staff_comment || '-') + '</td>' +
          '<td>' + escapeHtml(getTaskExecutionStatusLabel(item.task_execution_status)) + '</td>' +
          '<td>' +
            '<div class="report-entry-table-actions">' +
              '<button type="button" class="btn btn-outline-primary btn-sm report-entry-icon-btn report-entry-edit-item" data-entry-item-index="' + String(index) + '" title="' + escapeHtml(i18n.buttons.editTask) + '" aria-label="' + escapeHtml(i18n.buttons.editTask) + '">' +
                '<i class="ri-pencil-line"></i>' +
              '</button>' +
              '<button type="button" class="btn btn-outline-danger btn-sm report-entry-icon-btn report-entry-remove-item" data-entry-item-index="' + String(index) + '" title="' + escapeHtml(i18n.buttons.deleteTask) + '" aria-label="' + escapeHtml(i18n.buttons.deleteTask) + '">' +
                '<i class="ri-delete-bin-line"></i>' +
              '</button>' +
            '</div>' +
          '</td>';
        entryItemsEl.appendChild(row);
      });

      if (!entryTableEl) {
        return;
      }

      entryTableInstance = jQuery(entryTableEl).DataTable({
        paging: true,
        pageLength: 5,
        lengthChange: false,
        searching: false,
        ordering: false,
        info: true,
        autoWidth: false,
        scrollX: false,
        dom: 't<"dt-bottom-row mt-2 d-flex justify-content-between align-items-center"<"dt-info-left"i><"dt-paging-right d-flex justify-content-end"p>>',
        language: {
          info: i18n.entryTable.info,
          infoEmpty: i18n.entryTable.infoEmpty,
          emptyTable: i18n.entryTable.emptyTable,
          paginate: {
            previous: i18n.entryTable.paginatePrevious,
            next: i18n.entryTable.paginateNext
          },
          zeroRecords: i18n.entryTable.zeroRecords
        },
        columnDefs: [
          { targets: [0], className: 'text-center' }
        ]
      });
    }

    function collectEntryItems() {
      return draftEntryItems.map(function(item, index) {
        return {
          sort_order: index + 1,
          task_title: String(item.task_title || '').trim(),
          task_execution_status: String(item.task_execution_status || 'SELESAI').trim(),
          staff_comment: String(item.staff_comment || '').trim()
        };
      });
    }

    function activateEntryTab(targetKey) {
      document.querySelectorAll('[data-entry-tab-target]').forEach(function(button) {
        button.classList.toggle('is-active', button.getAttribute('data-entry-tab-target') === targetKey);
      });

      document.querySelectorAll('[data-entry-tab-panel]').forEach(function(panel) {
        panel.classList.toggle('is-active', panel.getAttribute('data-entry-tab-panel') === targetKey);
      });
    }

    function setEntryLoadingState(isLoading) {
      [entryAddItemBtn, entrySaveBtn, entrySubmitBtn, entryPreviewBtn].forEach(function(button) {
        if (!button) {
          return;
        }

        button.disabled = !!isLoading;
      });

      if (entryModalEl) {
        entryModalEl.classList.toggle('is-loading', !!isLoading);
      }
    }

    async function requestEntry(action, extraData) {
      const formData = new FormData();
      formData.append('csrf_token', csrfToken);
      Object.keys(extraData || {}).forEach(function(key) {
        formData.append(key, String(extraData[key] == null ? '' : extraData[key]));
      });

      const response = await fetch(pageUrl + '?action=' + encodeURIComponent(action), {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': csrfToken
        },
        body: formData
      });

      const payload = await response.json().catch(function() { return null; });
      if (!response.ok || !payload || payload.success !== true) {
        throw new Error(String((payload && payload.message) || i18n.requestFailed));
      }

      return payload;
    }

    function updateRowEntryState(rowElement, entryPayload, badge, badgeClass) {
      if (!rowElement || !entryPayload) {
        return;
      }

      const statusCell = rowElement.querySelector('.col-status');
      if (statusCell) {
        statusCell.innerHTML = badge
          ? '<span class="report-entry-badge ' + String(badgeClass || 'is-draft') + '" data-entry-status-badge>' + String(badge) + '</span>'
          : '<span class="status-empty">-</span>';
      }

      rowElement.querySelectorAll('[data-row]').forEach(function(element) {
        const current = parseRowPayload(element);
        current.entry_exists = true;
        current.entry_id = entryPayload.entry_id;
        current.entry_status = entryPayload.status;
        current.report_badge = badge || null;
        current.report_badge_class = badgeClass || 'is-draft';
        current.report_action_label = entryPayload.status === 'submitted' ? i18n.entryActionUpdate : i18n.entryActionFill;
        element.setAttribute('data-row', JSON.stringify(current));
        if (element.classList.contains('js-edit-report-row')) {
          element.setAttribute('title', current.report_action_label);
        }
        if (element.classList.contains('js-download-report-row')) {
          element.disabled = !current.entry_id;
          element.setAttribute('title', i18n.buttons.downloadReport);
        }
      });
    }

    function populateEntryModalFromResponse(data) {
      const entry = data && data.entry ? data.entry : {};
      if (entryIdInput) {
        entryIdInput.value = entry.entry_id ? String(entry.entry_id) : '';
      }
      if (entryDateInput && entry.report_date) {
        entryDateInput.value = String(entry.report_date);
      }

      setSummaryText(entrySummaryDate, entry.report_date_label || entry.report_date || '-');
      setSummaryText(entrySummaryStaffName, entry.staff_name || '-');
      setSummaryText(entrySummaryStaffId, entry.staf_id || entry.login_id || '-');
      setSummaryText(entrySummaryReference, entry.reference_summary || '-');
      setSummaryText(entrySummaryCategory, entry.category_summary || '-');
      setSummaryText(entrySummaryApproval, entry.approval_name || '-');
      draftEntryItems = data && Array.isArray(data.items) ? data.items.map(function(item) {
        return {
          task_title: String(item.task_title || ''),
          task_execution_status: String(item.task_execution_status || 'SELESAI'),
          staff_comment: String(item.staff_comment || '')
        };
      }) : [];
      resetEntryForm();
      renderEntryItems();
      if (entryPreviewBtn) {
        entryPreviewBtn.disabled = !entry.entry_id;
      }
    }

    function renderModalTabs(tabs) {
      const tabsContainer = document.getElementById('reportViewTabs');
      const panelsContainer = document.getElementById('reportViewTabPanels');
      if (!tabsContainer || !panelsContainer) {
        return;
      }

      tabsContainer.innerHTML = '';
      panelsContainer.innerHTML = '';

      const safeTabs = Array.isArray(tabs) && tabs.length ? tabs : [{ key: 'maklumat', title: i18n.modalDefaultTab, fields: [{ label: i18n.modalDefaultField, value: '-' }] }];

      safeTabs.forEach(function(tab, index) {
        const tabKey = tab && tab.key ? String(tab.key) : 'tab-' + index;
        const tabTitle = tab && tab.title ? String(tab.title) : i18n.modalDefaultTab;
        const fields = Array.isArray(tab && tab.fields) && tab.fields.length ? tab.fields : [{ label: i18n.modalDefaultField, value: '-' }];

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'report-tab-btn' + (index === 0 ? ' is-active' : '');
        button.setAttribute('data-tab-target', tabKey);
        button.textContent = tabTitle;
        tabsContainer.appendChild(button);

        const panel = document.createElement('div');
        panel.className = 'report-tab-panel' + (index === 0 ? ' is-active' : '');
        panel.setAttribute('data-tab-panel', tabKey);

        const section = document.createElement('div');
        section.className = 'form-section report-modal-section';

        const sectionTitle = document.createElement('div');
        sectionTitle.className = 'form-section-title';
        sectionTitle.innerHTML = '<i class="ri-layout-grid-line"></i>' + escapeHtml(tabTitle);

        const grid = document.createElement('div');
        grid.className = 'report-view-grid info-card';

        fields.forEach(function(field) {
          const item = document.createElement('div');
          const label = document.createElement('div');
          const value = document.createElement('div');

          if (field && field.full_width) {
            item.classList.add('report-view-item-full');
          }

          label.className = 'report-view-label';
          value.className = 'report-view-value';
          label.textContent = field && field.label ? String(field.label) : '-';
          value.textContent = field && field.value && String(field.value).trim() !== '' ? String(field.value) : '-';

          item.appendChild(label);
          item.appendChild(value);
          grid.appendChild(item);
        });

        section.appendChild(sectionTitle);
        section.appendChild(grid);
        panel.appendChild(section);
        panelsContainer.appendChild(panel);
      });
    }

    function activateModalTab(targetKey) {
      document.querySelectorAll('[data-tab-target]').forEach(function(button) {
        button.classList.toggle('is-active', button.getAttribute('data-tab-target') === targetKey);
      });

      document.querySelectorAll('#reportViewTabPanels [data-tab-panel]').forEach(function(panel) {
        panel.classList.toggle('is-active', panel.getAttribute('data-tab-panel') === targetKey);
      });
    }

    document.addEventListener('click', function(event) {
      const button = event.target.closest('.report-tab-btn');
      if (!button) {
        return;
      }

      if (button.hasAttribute('data-entry-tab-target')) {
        activateEntryTab(button.getAttribute('data-entry-tab-target') || 'info');
        return;
      }

      activateModalTab(button.getAttribute('data-tab-target') || '');
    });

    jQuery(document).on('click', '.js-view-row', function(){
      if (!viewModal) {
        return;
      }

      const row = parseRowPayload(this);
      renderModalTabs(row.modal_tabs || []);
      viewModal.show();
    });

    jQuery(document).on('click', '.js-edit-report-row', async function(){
      if (!entryModal) {
        return;
      }

      activeEntryTriggerRow = this.closest('tr');
      const row = parseRowPayload(this);
      resetEntryModal(row);
      if (entryPreviewBtn) {
        entryPreviewBtn.disabled = true;
      }
      entryModal.show();
      setEntryLoadingState(true);

      try {
        const payload = await requestEntry('entry-load', {
          report_date: row.date_sort || ''
        });
        populateEntryModalFromResponse(payload.data || {});
      } catch (error) {
        await showSwalMessage(error && error.message ? error.message : i18n.entryLoadFailed, 'error');
      } finally {
        setEntryLoadingState(false);
      }
    });

    jQuery(document).on('click', '.js-download-report-row', async function(){
      const row = parseRowPayload(this);
      const entryId = row && row.entry_id ? String(row.entry_id) : '';
      if (!entryId) {
        await showSwalMessage(i18n.downloadRequiresDraft || i18n.previewRequiresDraft, 'warning');
        return;
      }

      window.open(printPageUrl + '?entry_id=' + encodeURIComponent(entryId), '_blank', 'noopener');
    });

    if (entryAddItemBtn) {
      entryAddItemBtn.addEventListener('click', async function() {
        const taskTitle = entryTaskTitleInput ? String(entryTaskTitleInput.value || '').trim() : '';
        const taskStatus = entryTaskStatusInput ? String(entryTaskStatusInput.value || 'SELESAI').trim() : 'SELESAI';
        const taskComment = entryTaskCommentInput ? String(entryTaskCommentInput.value || '').trim() : '';

        if (taskTitle === '') {
          await showSwalMessage(i18n.taskTitleRequired, 'warning');
          activateEntryTab('entry');
          if (entryTaskTitleInput) {
            entryTaskTitleInput.focus();
          }
          return;
        }

        const itemPayload = {
          task_title: taskTitle,
          task_execution_status: taskStatus,
          staff_comment: taskComment
        };

        if (editingEntryItemIndex >= 0 && draftEntryItems[editingEntryItemIndex]) {
          draftEntryItems[editingEntryItemIndex] = itemPayload;
        } else {
          draftEntryItems.push(itemPayload);
        }

        resetEntryForm();
        renderEntryItems();
      });
    }

    if (entryResetItemBtn) {
      entryResetItemBtn.addEventListener('click', function() {
        resetEntryForm();
      });
    }

    document.addEventListener('click', function(event) {
      const editButton = event.target.closest('.report-entry-edit-item');
      if (editButton) {
        const index = Number(editButton.getAttribute('data-entry-item-index') || -1);
        const item = index >= 0 ? draftEntryItems[index] : null;
        if (!item) {
          return;
        }

        editingEntryItemIndex = index;
        if (entryTaskTitleInput) {
          entryTaskTitleInput.value = String(item.task_title || '');
        }
        if (entryTaskStatusInput) {
          entryTaskStatusInput.value = String(item.task_execution_status || 'SELESAI');
        }
        if (entryTaskCommentInput) {
          entryTaskCommentInput.value = String(item.staff_comment || '');
        }
        if (entryAddItemBtnLabel) {
          entryAddItemBtnLabel.textContent = i18n.buttons.updateTable;
        }
        activateEntryTab('entry');
        if (entryTaskTitleInput) {
          entryTaskTitleInput.focus();
        }
        return;
      }

      const removeButton = event.target.closest('.report-entry-remove-item');
      if (!removeButton) {
        return;
      }

      const index = Number(removeButton.getAttribute('data-entry-item-index') || -1);
      if (index < 0 || !draftEntryItems[index]) {
        return;
      }

      draftEntryItems.splice(index, 1);
      if (editingEntryItemIndex === index) {
        resetEntryForm();
      } else if (editingEntryItemIndex > index) {
        editingEntryItemIndex -= 1;
      }
      renderEntryItems();
    });

    async function persistEntry(submit) {
      const reportDate = entryDateInput ? String(entryDateInput.value || '') : '';
      if (!reportDate) {
        await showSwalMessage(i18n.invalidReportDate, 'error');
        return;
      }

      const items = collectEntryItems();
      setEntryLoadingState(true);

      try {
        const payload = await requestEntry(submit ? 'entry-submit' : 'entry-save-draft', {
          report_date: reportDate,
          entry_id: entryIdInput ? String(entryIdInput.value || '') : '',
          items_json: JSON.stringify(items)
        });

        const data = payload.data || {};
        if (data.entry) {
          if (entryIdInput) {
            entryIdInput.value = data.entry.entry_id ? String(data.entry.entry_id) : '';
          }
          if (entryPreviewBtn) {
            entryPreviewBtn.disabled = !data.entry.entry_id;
          }
          if (activeEntryTriggerRow) {
            updateRowEntryState(activeEntryTriggerRow, data.entry, data.badge, data.badge_class);
          }
        }

        await showSwalMessage(payload.message || i18n.recordSaved, 'success');
      } catch (error) {
        await showSwalMessage(error && error.message ? error.message : i18n.recordSaveFailed, 'error');
      } finally {
        setEntryLoadingState(false);
      }
    }

    if (entrySaveBtn) {
      entrySaveBtn.addEventListener('click', async function() {
        const confirmed = await confirmSwalMessage(i18n.confirmSaveDraft, i18n.confirmSaveDraftYes);
        if (!confirmed) {
          return;
        }

        persistEntry(false);
      });
    }

    if (entrySubmitBtn) {
      entrySubmitBtn.addEventListener('click', async function() {
        const confirmed = await confirmSwalMessage(i18n.confirmSave, i18n.confirmSaveYes);
        if (!confirmed) {
          return;
        }

        persistEntry(true);
      });
    }

    if (entryPreviewBtn) {
      entryPreviewBtn.addEventListener('click', async function() {
        const entryId = entryIdInput ? String(entryIdInput.value || '') : '';
        if (!entryId) {
          await showSwalMessage(i18n.previewRequiresDraft, 'warning');
          return;
        }

        window.open(printPageUrl + '?entry_id=' + encodeURIComponent(entryId), '_blank', 'noopener');
      });
    }

    if (initialPageMessage !== '') {
      showSwalMessage(initialPageMessage, initialPageAlert);
    }

    if (window.bootstrap) {
      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
        new bootstrap.Tooltip(element);
      });
    }
  });
})();
</script>
</body>
</html>
