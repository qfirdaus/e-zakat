<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_login();
require_once __DIR__ . '/../ajax/_helpers.php';
require_once __DIR__ . '/../includes/functions-db.php';

$pdoPerm = Database::getInstance('mysql')->getConnection();
ensurePageGroupManagePermission($pdoPerm);

$PAGE_TITLE = (string)(__('studentLookup_page_title') ?? 'Carian Pelajar');
$lang = $_SESSION['lang'] ?? 'ms';
$version = (string)($_ENV['APP_ASSET_VER'] ?? date('ymdHis'));

$environment = function_exists('get_sybase_environment') ? get_sybase_environment() : 'production';
$operationalMode = function_exists('get_sybase_operational_mode') ? get_sybase_operational_mode() : 'staff_only';
$studentEnabled = function_exists('is_student_mode_enabled') ? is_student_mode_enabled() : false;
$studentKey = function_exists('get_sybase_student_key') ? get_sybase_student_key() : '-';
$defaultHome = (string)(app_config('site.default_home', 'pages/dashboard.php') ?? 'pages/dashboard.php');
$defaultHomeHref = ltrim($defaultHome, '/');
$operationalModeLabel = $operationalMode === 'staff_student'
    ? (string)(__('config_tab_db_mode_staff_student') ?? 'Staff + Student')
    : (string)(__('config_tab_db_mode_staff_only') ?? 'Staff Only');

if (!$studentEnabled) {
    header('Location: ' . base_url($defaultHomeHref));
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = (string)$_SESSION['csrf_token'];
$search = trim((string)($_GET['q'] ?? ''));
$queryInfo = $search !== ''
    ? (string)(__('studentLookup_query_info_search') ?? 'Gunakan halaman ini untuk mencari data pelajar aktif berdasarkan matrik, nama, atau fakulti melalui domain Sybase Pelajar.')
    : (string)(__('studentLookup_query_info_default') ?? 'Halaman ini digunakan untuk menyemak data pelajar aktif melalui domain Sybase Pelajar apabila mode Staf + Pelajar diaktifkan.');

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>" data-bs-theme="<?= h($_SESSION['theme.layout'] ?? 'light') ?>">
<head>
  <?php include __DIR__ . '/../includes/head.php'; ?>
  <meta name="csrf-token" content="<?= h($csrf) ?>">
  <link href="<?= base_url('assets/css/datatables-standard.css') ?>?v=<?= h($version) ?>" rel="stylesheet">
  <script src="<?= base_url('assets/js/helpers/datatables-standard.js') ?>?v=<?= h($version) ?>"></script>
  <style>
    .student-lookup-card{
      border:1px solid rgba(15,23,42,.08);
      border-radius:1rem;
      box-shadow:0 10px 30px rgba(15,23,42,.06);
      overflow:hidden;
    }
    .student-lookup-card .card-header{
      background:linear-gradient(135deg, rgba(37,99,235,.1), rgba(14,165,233,.08));
      border-bottom:1px solid rgba(15,23,42,.08);
    }
    .student-summary{
      border:1px solid rgba(15,23,42,.08);
      border-radius:.9rem;
      padding:1rem 1.1rem;
      background:#fff;
      height:100%;
    }
    .student-summary-label{
      display:block;
      font-size:.75rem;
      text-transform:uppercase;
      letter-spacing:.06em;
      color:#64748b;
      margin-bottom:.35rem;
      font-weight:700;
    }
    .student-summary-value{
      font-size:1rem;
      font-weight:700;
      color:#0f172a;
      word-break:break-word;
    }
    .student-query-box{
      border:1px dashed rgba(37,99,235,.25);
      border-radius:.9rem;
      background:rgba(239,246,255,.75);
      padding:1rem 1.1rem;
      font-size:.92rem;
      color:#1e3a8a;
    }
    .student-table{
      border-radius:8px;
      overflow:hidden;
      box-shadow:none;
      border:1px solid rgba(148,163,184,.14);
      background:rgba(255,255,255,.96);
      border-collapse:separate !important;
      border-spacing:0 !important;
    }
    .student-table thead th{
      background:linear-gradient(180deg, rgba(248,250,252,.96), rgba(241,245,249,.92));
      border-bottom:1px solid rgba(148,163,184,.16);
      padding:.9rem .85rem;
      font-size:.8rem;
      font-weight:700;
      text-transform:uppercase;
      letter-spacing:.06em;
      color:#64748b;
    }
    .student-table th.col-bil,
    .student-table td.col-bil{
      text-align:center;
    }
    .student-table th.col-left,
    .student-table td.col-left{
      text-align:left;
    }
    .student-table tbody td{
      padding:.9rem .85rem;
      vertical-align:middle;
      border-top-color:rgba(226,232,240,.9);
    }
    .student-table tbody tr:hover{
      background:rgba(241,245,249,.88);
      box-shadow: inset 0 0 0 999px rgba(241,245,249,.3);
    }
    .student-table .student-table-empty{
      text-align:center;
      color:#64748b;
      padding:1.4rem 1rem !important;
      font-size:.92rem;
      background:linear-gradient(180deg, rgba(248,250,252,.88), rgba(255,255,255,.98));
    }
    .student-search-card{
      border:1px solid rgba(15,23,42,.08);
      border-radius:.95rem;
      background:#fff;
      padding:1rem 1.1rem;
      box-shadow:0 2px 8px rgba(15,23,42,.04);
    }
    .swal2-student-popup{
      border-radius:1rem !important;
      box-shadow:0 18px 48px rgba(15,23,42,.18) !important;
    }
    .swal2-student-title{
      font-size:1.35rem !important;
      font-weight:700 !important;
      color:#0f172a !important;
    }
    .swal2-student-confirm{
      border-radius:.75rem !important;
      padding:.72rem 1.85rem !important;
      font-weight:600 !important;
      box-shadow:0 8px 18px rgba(37,99,235,.22) !important;
    }
    .student-table .dataTables_wrapper .row:first-child{
      align-items:center;
      margin-bottom:.75rem;
    }
    .student-table #studentLookupTable_wrapper .row{
      margin-left:0 !important;
      margin-right:0 !important;
    }
    .student-table #studentLookupTable_wrapper .row > [class*="col-"]{
      padding-left:0 !important;
      padding-right:0 !important;
    }
    .student-table .dt-top-left{
      padding-left:1.55rem !important;
      position:relative !important;
      top:7px !important;
    }
    .student-table .dt-top-left .dataTables_length,
    .student-table .dt-top-left .dataTables_length label{
      margin-left:0 !important;
      padding-left:0 !important;
      white-space:nowrap !important;
    }
    .student-table #studentLookupTable_length{
      margin-left:.45rem !important;
    }
    .student-table .dt-bottom-row > .dt-info-left{
      padding-left:1.15rem !important;
      margin-left:0 !important;
    }
    .student-table .dt-bottom-row > .dt-info-left .dataTables_info{
      padding-left:0 !important;
      margin-left:0 !important;
      white-space:nowrap !important;
    }
    .student-table #studentLookupTable_wrapper .dt-bottom-row{
      display:flex !important;
      align-items:center !important;
      justify-content:space-between !important;
      flex-wrap:nowrap !important;
      gap:.75rem !important;
    }
    .student-table #studentLookupTable_wrapper .dt-bottom-row > .dt-info-left{
      flex:0 1 auto !important;
      min-width:0 !important;
      overflow:hidden !important;
    }
    .student-table #studentLookupTable_wrapper .dt-bottom-row > .dt-paging-right{
      flex:0 0 auto !important;
      margin-left:auto !important;
      display:flex !important;
      justify-content:flex-end !important;
      align-items:center !important;
      position:relative !important;
      top:-7px !important;
    }
    .student-table #studentLookupTable_wrapper .dataTables_paginate{
      margin-top:0 !important;
      margin-left:auto !important;
      white-space:nowrap !important;
      display:flex !important;
      align-items:center !important;
      justify-content:flex-end !important;
    }
    .student-table .dataTables_wrapper .dataTables_filter input{
      min-width:240px;
      border:1px solid rgba(148,163,184,.24) !important;
      border-radius:8px !important;
      background:rgba(255,255,255,.98) !important;
      box-shadow:0 10px 24px rgba(15,23,42,.05);
    }
    .student-table #studentLookupTable_wrapper .dt-top-right,
    .student-table #studentLookupTable_wrapper .dt-top-right .dataTables_filter,
    .student-table #studentLookupTable_wrapper .dt-top-right .dataTables_filter label{
      display:flex !important;
      align-items:center !important;
      position:relative !important;
      top:3px !important;
    }
    html[data-bs-theme="dark"] .student-lookup-card{
      border-color:rgba(148,163,184,.18);
      box-shadow:none;
    }
    html[data-bs-theme="dark"] .student-lookup-card .card-header{
      background:linear-gradient(135deg, rgba(30,64,175,.35), rgba(14,165,233,.2));
      border-bottom-color:rgba(148,163,184,.18);
    }
    html[data-bs-theme="dark"] .student-summary{
      background:#111827;
      border-color:rgba(148,163,184,.18);
    }
    html[data-bs-theme="dark"] .student-summary-label{
      color:#94a3b8;
    }
    html[data-bs-theme="dark"] .student-summary-value{
      color:#e5e7eb;
    }
    html[data-bs-theme="dark"] .student-query-box{
      background:rgba(15,23,42,.8);
      border-color:rgba(96,165,250,.25);
      color:#bfdbfe;
    }
    html[data-bs-theme="dark"] .student-table thead th{
      background:linear-gradient(180deg, rgba(30,41,59,.92), rgba(15,23,42,.95));
      color:#cbd5e1;
      border-bottom-color:rgba(255,255,255,.08);
    }
    html[data-bs-theme="dark"] .student-table tbody td{
      border-top-color:rgba(51,65,85,.95);
    }
    html[data-bs-theme="dark"] .student-table tbody tr:hover{
      background:rgba(30,41,59,.76);
      box-shadow: inset 0 0 0 999px rgba(30,41,59,.18);
    }
    html[data-bs-theme="dark"] .student-table .student-table-empty{
      color:#94a3b8;
      background:linear-gradient(180deg, rgba(15,23,42,.94), rgba(2,6,23,.92));
    }
    html[data-bs-theme="dark"] .student-table{
      background:rgba(15,23,42,.92);
      border-color:rgba(148,163,184,.22);
    }
    html[data-bs-theme="dark"] .student-search-card{
      background:#111827;
      border-color:rgba(148,163,184,.18);
      box-shadow:none;
    }
  </style>
</head>
<body
  data-topbar-color="<?= h($_SESSION['theme.topbar'] ?? 'light') ?>"
  data-menu-color="<?= h($_SESSION['theme.menu'] ?? 'light') ?>"
  data-layout="vertical"
  data-sidebar-size="default">

<div class="wrapper">
  <?php include __DIR__ . '/../includes/topbar.php'; ?>
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="content-page">
    <div class="content">
      <div class="container-fluid">
        <div class="row mb-3">
          <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center flex-wrap">
              <h4 class="page-title"><i class="ri-graduation-cap-line me-1"></i> <?= h(__('studentLookup_page_title') ?? 'Carian Pelajar') ?></h4>
              <div class="page-title-right">
                <ol class="breadcrumb m-0">
                  <li class="breadcrumb-item"><a href="<?= h($defaultHomeHref) ?>"><i class="ri-home-4-line align-middle me-1"></i><?= h(__('breadcrumb_home') ?? 'Home') ?></a></li>
                  <li class="breadcrumb-item active"><?= h(__('studentLookup_page_title') ?? 'Carian Pelajar') ?></li>
                </ol>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-12">
            <div class="card student-lookup-card">
              <div class="card-header">
                <div class="d-flex align-items-center">
                  <div class="me-3 rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                    <i class="ri-search-eye-line fs-4"></i>
                  </div>
                  <div>
                    <h5 class="mb-1 fw-semibold"><?= h(__('studentLookup_header_title') ?? 'Carian Data Pelajar') ?></h5>
                    <div class="text-muted small"><?= h(__('studentLookup_header_subtitle') ?? 'Semak data pelajar aktif daripada view v210 melalui domain Sybase Pelajar.') ?></div>
                  </div>
                </div>
              </div>
              <div class="card-body">
                <div class="row g-3 mb-3">
                  <div class="col-lg-4">
                    <div class="student-summary">
                      <span class="student-summary-label"><?= h(__('studentLookup_environment') ?? 'Environment') ?></span>
                      <span class="student-summary-value"><?= h(ucfirst($environment)) ?></span>
                    </div>
                  </div>
                  <div class="col-lg-4">
                    <div class="student-summary">
                      <span class="student-summary-label"><?= h(__('studentLookup_mode') ?? 'Operational Mode') ?></span>
                      <span class="student-summary-value"><?= h($operationalModeLabel) ?></span>
                    </div>
                  </div>
                  <div class="col-lg-4">
                    <div class="student-summary">
                      <span class="student-summary-label"><?= h(__('studentLookup_runtime_key') ?? 'Student Runtime Key') ?></span>
                      <span class="student-summary-value"><?= h($studentKey) ?></span>
                    </div>
                  </div>
                </div>

                <div class="student-search-card mb-3">
                  <form method="get" class="row g-3 align-items-end">
                    <div class="col-lg-10">
                      <label class="form-label fw-semibold">
                        <i class="ri-search-line me-1 text-muted"></i><?= h(__('studentLookup_search_label') ?? 'Carian Pelajar') ?>
                      </label>
                      <input
                        type="text"
                        name="q"
                        class="form-control"
                        value="<?= h($search) ?>"
                        placeholder="<?= h(__('studentLookup_search_placeholder') ?? 'Cari matrik, nama, atau fakulti') ?>"
                        maxlength="100">
                    </div>
                    <div class="col-lg-2">
                      <label class="form-label fw-semibold d-block opacity-0">.</label>
                      <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                          <i class="ri-search-line me-1"></i><?= h(__('studentLookup_search_button') ?? 'Cari') ?>
                        </button>
                        <?php if ($search !== ''): ?>
                          <a href="carian-pelajar.php" class="btn btn-outline-secondary">
                            <i class="ri-refresh-line"></i>
                          </a>
                        <?php endif; ?>
                      </div>
                    </div>
                  </form>
                </div>

                <div class="student-query-box mb-3" id="studentQueryInfo"><?= h($queryInfo) ?></div>

                <?php if (!$studentEnabled): ?>
                  <div class="alert alert-warning mb-0">
                    <i class="ri-alert-line me-2"></i><?= h(__('studentLookup_mode_disabled') ?? 'Carian pelajar hanya tersedia apabila mode Staf + Pelajar diaktifkan.') ?>
                  </div>
                <?php else: ?>
                  <div class="table-responsive student-table dt-standard">
                    <table class="table table-hover align-middle mb-0" id="studentLookupTable">
                      <thead>
                        <tr>
                          <th class="col-bil" style="width:5%;">#</th>
                          <th class="col-left" style="width:10%;"><?= h(__('studentLookup_col_matrik') ?? 'Matrik') ?></th>
                          <th class="col-left" style="width:50%;"><?= h(__('studentLookup_col_nama') ?? 'Nama') ?></th>
                          <th class="col-left" style="width:35%;"><?= h(__('studentLookup_col_fakulti') ?? 'Fakulti') ?></th>
                        </tr>
                      </thead>
                      <tbody id="studentLookupResults">
                        <tr>
                          <td colspan="4" class="student-table-empty"><?= h(__('studentLookup_empty_table') ?? 'Tiada rekod pelajar untuk dipaparkan.') ?></td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                <?php endif; ?>
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
<?php if ($studentEnabled): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('.student-search-card form');
  const input = form ? form.querySelector('input[name="q"]') : null;
  const resultsBody = document.getElementById('studentLookupResults');
  const table = document.getElementById('studentLookupTable');
  const queryBox = document.getElementById('studentQueryInfo');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const endpoint = <?= json_encode(base_url('ajax/user-search-pelajar.php'), JSON_UNESCAPED_UNICODE) ?>;
  const resetHref = 'carian-pelajar.php';
  const T = {
    emptyTable: <?= json_encode(__('studentLookup_empty_table') ?? 'Tiada rekod pelajar untuk dipaparkan.', JSON_UNESCAPED_UNICODE) ?>,
    noSearchResults: <?= json_encode(__('studentLookup_no_search_results') ?? 'Tiada padanan pelajar ditemui.', JSON_UNESCAPED_UNICODE) ?>,
    loading: <?= json_encode(__('studentLookup_loading'), JSON_UNESCAPED_UNICODE) ?>,
    successSearch: <?= json_encode(__('studentLookup_success_search'), JSON_UNESCAPED_UNICODE) ?>,
    errorPrefix: <?= json_encode(__('studentLookup_error_prefix'), JSON_UNESCAPED_UNICODE) ?>,
    systemError: <?= json_encode(__('studentSearch_system_error'), JSON_UNESCAPED_UNICODE) ?>,
    alertTitleSuccess: <?= json_encode(__('manual_alert_success_title'), JSON_UNESCAPED_UNICODE) ?>,
    alertTitleError: <?= json_encode(__('manual_alert_error_title'), JSON_UNESCAPED_UNICODE) ?>,
    queryInfoDefault: <?= json_encode(__('studentLookup_query_info_default'), JSON_UNESCAPED_UNICODE) ?>,
    queryInfoSearch: <?= json_encode(__('studentLookup_query_info_search'), JSON_UNESCAPED_UNICODE) ?>
  };
  let dataTable = null;

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function buildQueryPreview(term) {
    return term ? T.queryInfoSearch : T.queryInfoDefault;
  }

  async function showResultAlert(kind, message) {
    if (!window.Swal || !message) return;
    await Swal.fire({
      icon: kind === 'error' ? 'error' : 'success',
      title: kind === 'error' ? T.alertTitleError : T.alertTitleSuccess,
      text: message,
      confirmButtonColor: kind === 'error' ? '#dc3545' : '#2563eb',
      customClass: {
        popup: 'swal2-student-popup',
        title: 'swal2-student-title',
        confirmButton: 'swal2-student-confirm'
      }
    });
  }

  function destroyDataTable() {
    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable && dataTable) {
      dataTable.destroy();
      dataTable = null;
    }
  }

  function initDataTable() {
    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.DataTable || !table) return;
    destroyDataTable();
    dataTable = window.jQuery(table).DataTable({
      pageLength: 10,
      lengthChange: true,
      lengthMenu: [10, 25, 50, 100, 200],
      ordering: true,
      order: [[2, 'asc']],
      searching: true,
      info: true,
      responsive: true,
      autoWidth: false,
      dom: '<"row mb-2"<"col-sm-12 col-md-6 dt-top-left"l><"col-sm-12 col-md-6 d-flex justify-content-md-end dt-top-right"f>>' +
        't' +
        '<"dt-bottom-row mt-2 d-flex justify-content-between align-items-center"<"dt-info-left"i><"dt-paging-right d-flex justify-content-end"p>>',
      language: {
        lengthMenu: <?= json_encode(__('manual_dt_length_menu'), JSON_UNESCAPED_UNICODE) ?>,
        info: <?= json_encode(__('manual_dt_info'), JSON_UNESCAPED_UNICODE) ?>,
        infoEmpty: <?= json_encode(__('manual_dt_info_empty'), JSON_UNESCAPED_UNICODE) ?>,
        emptyTable: <?= json_encode(__('studentLookup_empty_table') ?? 'Tiada rekod pelajar untuk dipaparkan.', JSON_UNESCAPED_UNICODE) ?>,
        zeroRecords: <?= json_encode(__('manual_dt_zero_records'), JSON_UNESCAPED_UNICODE) ?>,
        search: <?= json_encode(__('manual_dt_search_label'), JSON_UNESCAPED_UNICODE) ?>,
        paginate: {
          previous: <?= json_encode(__('manual_dt_paginate_prev'), JSON_UNESCAPED_UNICODE) ?>,
          next: <?= json_encode(__('manual_dt_paginate_next'), JSON_UNESCAPED_UNICODE) ?>
        }
      },
      columnDefs: [
        { targets: 0, orderable: false, searchable: false, className: 'col-bil text-center', width: '5%' }
      ],
      rowCallback: function(row, data, displayIndex) {
        const api = this.api();
        const info = api.page.info();
        window.jQuery('td:eq(0)', row).text(info.start + displayIndex + 1).addClass('col-bil text-center');
      }
    });
    if (window.DataTableStandard && typeof window.DataTableStandard.decorate === 'function') {
      window.DataTableStandard.decorate('#studentLookupTable', {
        searchPlaceholder: <?= json_encode(__('studentLookup_search_button') ?? 'Cari', JSON_UNESCAPED_UNICODE) ?>
      });
    }
  }

  function renderRows(rows, term) {
    if (!resultsBody) return;
    destroyDataTable();
    if (!Array.isArray(rows) || rows.length === 0) {
      resultsBody.innerHTML = `
        <tr>
          <td colspan="4" class="student-table-empty">${escapeHtml(term ? T.noSearchResults : T.emptyTable)}</td>
        </tr>
      `;
      return;
    }

    resultsBody.innerHTML = rows.map((row, idx) => `
      <tr>
        <td class="col-bil">${idx + 1}</td>
        <td class="col-left">${escapeHtml(row.matrik)}</td>
        <td class="col-left">${escapeHtml(row.nama)}</td>
        <td class="col-left">${escapeHtml(row.fakulti)}</td>
      </tr>
    `).join('');
    initDataTable();
  }

  async function loadStudents(term = '') {
    if (queryBox) {
      queryBox.textContent = buildQueryPreview(term);
    }

    if (!term || term.length < 2) {
      renderRows([], '');
      return;
    }

    if (resultsBody) {
      resultsBody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-4">${escapeHtml(T.loading)}</td></tr>`;
    }

    const body = new URLSearchParams();
    body.set('csrf_token', csrf);
    body.set('q', term);
    body.set('page', '1');
    body.set('all', '1');

    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          'Accept': 'application/json',
          'X-CSRF-Token': csrf
        },
        body: body.toString()
      });

      const json = await response.json();
      if (!response.ok || json.error) {
        throw new Error(json.message || T.systemError);
      }

      const rows = Array.isArray(json.results) ? json.results : [];
      renderRows(rows, term);
      if (term) {
        const message = T.successSearch.replace('%1$d', rows.length).replace('%2$s', term);
        await showResultAlert('success', message);
      }
    } catch (error) {
      destroyDataTable();
      resultsBody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-4">${escapeHtml(error?.message || T.systemError)}</td></tr>`;
      const message = `${T.errorPrefix} ${error?.message || T.systemError}`;
      await showResultAlert('error', message);
    }
  }

  if (form && input) {
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      const term = input.value.trim();
      const url = new URL(window.location.href);
      if (term) {
        url.searchParams.set('q', term);
      } else {
        url.searchParams.delete('q');
      }
      window.history.replaceState({}, '', url.toString());
      loadStudents(term);
    });

    const resetButton = form.querySelector('a.btn-outline-secondary');
    if (resetButton) {
      resetButton.addEventListener('click', (event) => {
        event.preventDefault();
        input.value = '';
        window.history.replaceState({}, '', resetHref);
        loadStudents('');
      });
    }
  }

  renderRows([], '');
  if (<?= json_encode($search !== '', JSON_UNESCAPED_UNICODE) ?>) {
    loadStudents(<?= json_encode($search, JSON_UNESCAPED_UNICODE) ?>);
  }
});
</script>
<?php endif; ?>
</body>
</html>
