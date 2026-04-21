<?php
// pages/senarai-borang.php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_login();

require_once __DIR__ . '/../controllers/ListPermohonanController.php';

$controller = new ListPermohonanController();

$lang                = $controller->lang ?? 'ms';
$profile             = $controller->profile ?? [];
$senaraiPermohonan   = $controller->senaraiPermohonan ?? [];
$version             = (string)($_ENV['APP_ASSET_VER'] ?? date('ymdHis'));

// UI permission guard (use existing backend helper)
$canManageGroups = false;
try {
  require_once __DIR__ . '/../ajax/_helpers.php';
  $pdoPerm = Database::getInstance('mysql')->getConnection();
  $canManageGroups = hasGroupManagePermission($pdoPerm);
} catch (Throwable $e) {
  $canManageGroups = false;
}
$permDisabledAttr = $canManageGroups ? '' : 'disabled aria-disabled="true"';
$permDisabledClass = $canManageGroups ? '' : ' disabled';

// helper escape
if (!function_exists('h')) {
  function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>" data-bs-theme="<?= h($_SESSION['theme.layout'] ?? 'light') ?>">
<head>
  <?php include __DIR__ . '/../includes/head.php'; ?>

  <meta name="csrf-token" content="<?= h($csrf) ?>">
  <!-- ✅ Standard DataTables CSS (shared) -->
  <link href="<?= base_url('assets/css/datatables-standard.css') ?>?v=<?= h($version ?? date('ymdHis')) ?>" rel="stylesheet">
  <style>
    .icon-btn { line-height:1; }

    /* Kolum jadual kumpulan - use percentages to match requested layout */
    table.table th.th-nowrap { white-space: nowrap; }
    table.table th.th-color, table.table td.td-color { width: 4%; min-width: 46px; max-width: 56px; }
    table.table th.th-mod,  table.table td.td-mod  { width: 10%; }
    table.table th.th-menu, table.table td.td-menu { width: 10%; }
    table.table th.th-grp,  table.table td.td-grp  { width: 10%; }
    .group-color-cell { display: flex; justify-content: flex-end; }
    .group-color-bar {
      display: inline-block;
      width: 40px;
      height: 14px;
      border-radius: 999px;
      border: 1px solid rgba(15, 23, 42, 0.22);
      box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.25);
    }
    html[data-bs-theme="dark"] .group-color-bar { border-color: rgba(148, 163, 184, 0.55); }

    /* Reorder view */
    .modul-badge { font-size:.75rem; }
    .menu-path { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; font-size:.775rem; opacity:.8; }
    .menu-row { display:grid; grid-template-columns: 1fr auto; gap:.75rem; padding:.6rem 0; border-bottom:1px dashed var(--bs-border-color); }
    .menu-row:last-child { border-bottom:0; }
    .reorder-group .btn { padding:.25rem .55rem; }
    .menu-row.saving { opacity:.6; pointer-events:none; }

    /* DataTable: top & bottom bars */
    .dt-topbar{
      display:flex; align-items:center; justify-content:space-between;
      gap:.75rem; border-bottom:1px solid var(--bs-border-color); padding-bottom:.5rem; margin-bottom:.75rem;
    }
    .dt-topbar .left, .dt-topbar .right{ display:flex; align-items:center; gap:.5rem; }
    .dt-topbar .right .form-control{ width:260px; max-width:100%; }
    .dt-bottom-row { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.5rem; }
    @media (max-width:575.98px){ .dt-topbar{ flex-wrap:wrap; } .dt-topbar .right{ margin-left:auto; } }

    /* Fix DataTables layout untuk permohonanTable - prevent horizontal scroll */
    #permohonanTable_wrapper {
      overflow-x: hidden;
      width: 100%;
      max-width: 100%;
    }
    
    /* ✅ Table styling sama seperti senarai-pengguna.php */
    #permohonanTable {

      border-radius: 0.75rem;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
      table-layout: fixed;
    }
    #permohonanTable thead {
      background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
      color: #ffffff;
    }
    #permohonanTable thead th {
      font-weight: 700;
      font-size: 0.875rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      padding: 1rem 0.75rem;
      border: none;
      color: #ffffff;
    }
    #permohonanTable tbody tr {
      transition: all 0.2s ease;
    }
    #permohonanTable tbody tr:hover {
      background: #f8fafc !important;
      transform: scale(1.01);
      box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
    }
    #permohonanTable tbody td {
      padding: 0.875rem 0.75rem;
      border-color: #f1f5f9;
      vertical-align: middle;
    }
    /* Dark theme support */
    html[data-bs-theme="dark"] #permohonanTable thead {
      background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
    }
    html[data-bs-theme="dark"] #permohonanTable tbody tr:hover {
      background: #334155 !important;
    }
    /* Top Controls */
    .dt-top-right {
      gap: 0.5rem;
    }

    /* Custom Search */
    .dt-custom-search {
      height: 36px !important;
      border: 2px solid #e9ecef !important;
      border-radius: 0.5rem !important;
      padding: 0.5rem 0.75rem !important;
      font-size: 0.875rem !important;
      transition: all .15s ease;
    }

    .dt-custom-search:focus {
      border-color: #86b7fe !important;
      box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25) !important;
    }

    /* Add Button */
    .btn-add-form {
      height: 36px;
      background: linear-gradient(135deg, #10b981 0%, #14b8a6 100%);
      color: #fff;
      border: none;
      border-radius: 0.5rem;
      padding: 0 16px;
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      transition: all .2s ease;
    }

    .btn-add-form:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 14px rgba(16,185,129,.35);
      color: #fff;
    }
    /* Remove stripline effect */
    #permohonanTable tbody tr,
    #permohonanTable tbody tr:nth-of-type(odd),
    #permohonanTable tbody tr:nth-of-type(even) {
      background-color: transparent !important;
    }
    #permohonanTable_wrapper .dataTables_length {
      display: flex;
      align-items: center;
      white-space: nowrap;
      overflow: hidden;
      flex-wrap: nowrap;
      max-width: 100%;
    }
    #permohonanTable_wrapper .dataTables_length label {
      display: flex;
      align-items: center;
      white-space: nowrap;
      margin-bottom: 0;
      flex-wrap: nowrap;
      gap: 0.5rem;
      max-width: 100%;
      overflow: hidden;
    }
    #permohonanTable_wrapper .dataTables_length select {
      margin: 0;
      width: auto;
      min-width: 70px;
      max-width: 100px;
      display: inline-block;
    }
    #permohonanTable_wrapper .dataTables_filter {
      text-align: right;
      margin-left: auto;
      max-width: 100%;
      overflow: hidden;
    }
    #permohonanTable_wrapper .dataTables_filter label {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      margin-bottom: 0;
      width: 100%;
      white-space: nowrap;
      max-width: 100%;
      overflow: hidden;
    }
    #permohonanTable_wrapper .dataTables_filter input {
      margin-left: 0.5rem;
      width: auto;
      min-width: 150px;
      max-width: 250px;
    }
    #permohonanTable_wrapper .dataTables_info {
      display: flex;
      align-items: center;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 100%;
    }
    #permohonanTable_wrapper .dataTables_paginate {
      text-align: right;
      margin-left: auto;
      max-width: 100%;
      overflow: hidden;
    }
    #permohonanTable_wrapper .dataTables_paginate .pagination {
      justify-content: flex-end;
      margin-bottom: 0;
      flex-wrap: nowrap;
    }
    #permohonanTable_wrapper .row {
      margin-left: -0.75rem;
      margin-right: -0.75rem;
      max-width: 100%;
      overflow-x: hidden;
    }
    #permohonanTable_wrapper .row > [class*="col-"] {
      padding-left: 0.75rem;
      padding-right: 0.75rem;
      max-width: 100%;
      overflow: hidden;
    }
    /* Fix bottom row layout */
    #permohonanTable_wrapper .row.mt-3 {
      display: flex;
      align-items: center;
      justify-content: space-between;
      max-width: 100%;
      overflow-x: hidden;
    }
    #permohonanTable_wrapper .row.mt-3 > [class*="col-md-5"] {
      display: flex;
      align-items: center;
      max-width: 100%;
      overflow: hidden;
    }
    #permohonanTable_wrapper .row.mt-3 > [class*="col-md-7"] {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      max-width: 100%;
      overflow: hidden;
    }
    /* Ensure table container doesn't overflow */
    .table-responsive {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    @media (max-width: 991.98px) {
      .table-responsive {
        overflow-x: auto;
      }
    }
    @media (max-width: 767.98px) {
      #permohonanTable_wrapper .dataTables_length,
      #permohonanTable_wrapper .dataTables_filter {
        margin-bottom: 0.75rem;
      }
      #permohonanTable_wrapper .dataTables_filter {
        text-align: left;
        margin-left: 0;
      }
      #permohonanTable_wrapper .dataTables_filter label {
        justify-content: flex-start;
      }
      #permohonanTable_wrapper .dataTables_info,
      #permohonanTable_wrapper .dataTables_paginate {
        text-align: center;
        margin-top: 0.5rem;
      }
      #permohonanTable_wrapper .row.mt-3 {
        flex-direction: column;
        align-items: stretch;
      }
      #permohonanTable_wrapper .row.mt-3 > [class*="col-md-5"],
      #permohonanTable_wrapper .row.mt-3 > [class*="col-md-7"] {
        justify-content: center;
        margin-top: 0.5rem;
      }
    }

    /* Buang class lama yang tak digunakan */
    #menuDT_wrapper .dt-top-right, #grpCnt .dt-top-right { display:none!important; }

    /* Akses Kumpulan – kolum “Menu”: kecil & 1 baris */
    #groupPermsDT th.col-menu, #groupPermsDT td.col-menu {
      width: 140px; white-space: nowrap;
    }

    /* Akses Menu – status & actions kekal sebaris */
    #menuDT th.col-status, #menuDT td.col-status { width: 200px; white-space: nowrap; }
    #menuDT th.col-actions, #menuDT td.col-actions { width: 170px; white-space: nowrap; }

    /* Multi-modal stacking */
    .modal-backdrop.show + .modal-backdrop.show { z-index: 1065; }
    .modal.show { z-index: 1055; }
    .modal.show + .modal.show { z-index: 1070; }

    /* Professional Modal Styling */
    .modal-content {
      border: none;
      border-radius: 12px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }

    .modal-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 1.5rem 1.75rem;
      border-bottom: none;
      position: relative;
    }

    .modal-header.bg-body-tertiary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }

    .modal-header .modal-title {
      color: white;
      font-weight: 600;
      font-size: 1.25rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .modal-header .modal-title i {
      font-size: 1.5rem;
      opacity: 0.95;
    }

    .modal-header .btn-close {
      filter: brightness(0) invert(1);
      opacity: 0.9;
      transition: opacity 0.2s;
    }

    .modal-header .btn-close:hover {
      opacity: 1;
    }

    .modal-subtitle {
      padding: 0.75rem 1.75rem;
      background: rgba(102, 126, 234, 0.08);
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
      font-size: 0.875rem;
      color: #6c757d;
      font-weight: 500;
    }

    .modal-body {
      padding: 1.75rem;
      background: #fff;
    }

    .modal-footer {
      padding: 1.25rem 1.75rem;
      background: #f8f9fa;
      border-top: 1px solid rgba(0, 0, 0, 0.08);
      border-radius: 0 0 12px 12px;
    }

    .modal-footer .btn {
      padding: 0.5rem 1.5rem;
      font-weight: 500;
      border-radius: 8px;
      transition: all 0.2s ease;
    }

    .modal-footer .btn-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .modal-footer .btn-primary:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
    }

    /* Loading States */
    .modal-loading {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 3rem 2rem;
      text-align: center;
    }

    .modal-loading .spinner-border {
      width: 3rem;
      height: 3rem;
      border-width: 3px;
      color: #667eea;
      margin-bottom: 1rem;
    }

    .modal-loading span {
      color: #6c757d;
      font-size: 0.95rem;
      font-weight: 500;
    }

    /* Error States */
    .modal-error {
      padding: 1rem 1.25rem;
      border-radius: 8px;
      background: #fee;
      border-left: 4px solid #dc3545;
      margin-bottom: 1rem;
    }

    /* Form Styling in Modals */
    .modal-body .form-label {
      font-weight: 600;
      color: #495057;
      margin-bottom: 0.5rem;
      font-size: 0.9rem;
    }

    .modal-body .form-control,
    .modal-body .form-select {
      border-radius: 8px;
      border: 1.5px solid #e0e0e0;
      padding: 0.625rem 0.875rem;
      transition: all 0.2s ease;
    }

    .modal-body .form-control:focus,
    .modal-body .form-select:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
    }

    /* Make module/menu multi-selects in the create modal taller for easier selection */
    #gc_moduls, #gc_menus {
      min-height: 200px;
      height: 200px;
      overflow: auto;
    }
    @media (max-width: 767.98px) {
      #gc_moduls, #gc_menus { min-height: 140px; height: 140px; }
    }

    /* Search Input in Modals */
    .modal-body input[type="search"] {
      border-radius: 10px;
      padding: 0.75rem 1rem;
      border: 2px solid #e9ecef;
      transition: all 0.2s ease;
    }

    .modal-body input[type="search"]:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
    }

    /* Themed Modal Accent */
    .modal-themed .modal-content {
      position: relative;
    }

    .modal-themed .modal-content::before {
      content: "";
      position: absolute;
      inset: 0 0 auto 0;
      height: 4px;
      background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
      border-top-left-radius: 12px;
      border-top-right-radius: 12px;
    }

    /* Edit Modal Specific */
    #menuEditModal .modal-header {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    #menuEditModal .modal-dialog {
      max-width: 900px;
    }

    #menuEditModal .modal-footer .btn-primary {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      box-shadow: 0 4px 12px rgba(245, 87, 108, 0.3);
    }

    #menuEditModal .modal-footer .btn-primary:hover {
      box-shadow: 0 6px 16px rgba(245, 87, 108, 0.4);
    }

    /* Accordion in Modals */
    .modal-body .accordion {
      border-radius: 8px;
      overflow: hidden;
    }

    .modal-body .accordion-item {
      border: 1px solid #e9ecef;
      margin-bottom: 0.5rem;
      border-radius: 8px;
    }

    .modal-body .accordion-button {
      background: #f8f9fa;
      font-weight: 600;
      padding: 1rem 1.25rem;
      border-radius: 8px;
    }

    .modal-body .accordion-button:not(.collapsed) {
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
      color: #667eea;
    }

    /* Table in Modals */
    .modal-body .table {
      border-radius: 8px;
      overflow: hidden;
    }

    .modal-body .table thead {
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    }

    /* Badge Styling */
    .modal-body .badge {
      padding: 0.4rem 0.75rem;
      border-radius: 6px;
      font-weight: 500;
    }

    /* Input Group Styling */
    .modal-body .input-group-text {
      border-radius: 8px 0 0 8px;
      background: #f8f9fa;
      border-color: #e0e0e0;
    }

    .modal-body .input-group .form-control {
      border-left: none;
      border-radius: 0 8px 8px 0;
    }

    /* Button Group in Forms */
    .modal-body .btn-group .btn {
      border-radius: 6px;
      font-weight: 500;
      transition: all 0.2s ease;
    }

    .modal-body .btn-group .btn:hover {
      transform: translateY(-1px);
    }

    /* List Group in Modals */
    .modal-body .list-group-item {
      border-radius: 8px;
      margin-bottom: 0.5rem;
      border: 1.5px solid #e9ecef;
      transition: all 0.2s ease;
    }

    .modal-body .list-group-item:hover {
      border-color: #667eea;
      background: rgba(102, 126, 234, 0.05);
      transform: translateX(4px);
    }

    /* Smooth Transitions */
    .modal.fade .modal-dialog {
      transition: transform 0.3s ease-out, opacity 0.3s ease-out;
    }

    .modal.show .modal-dialog {
      transform: none;
    }

    /* Content Animation */
    .modal-body > div:not(.d-none) {
      animation: fadeInUp 0.3s ease-out;
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    #groupPermsDT { table-layout: fixed; }
    #groupPermsDT th.col-check, #groupPermsDT td.col-check { width: 60px; }
    #groupPermsDT th.col-menu,  #groupPermsDT td.col-menu  { width: 140px; white-space: nowrap; }

    /* Loading indicators */
    .btn-loading {
      position: relative;
      pointer-events: none;
      opacity: 0.7;
    }
    .btn-loading::after {
      content: "";
      position: absolute;
      width: 16px;
      height: 16px;
      top: 50%;
      left: 50%;
      margin-left: -8px;
      margin-top: -8px;
      border: 2px solid transparent;
      border-top-color: currentColor;
      border-radius: 50%;
      animation: spin 0.6s linear infinite;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    .btn-loading .btn-text {
      opacity: 0;
    }

    /* Undo notification */
    .undo-notification {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 9999;
      min-width: 300px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    /* Controls & buttons: align with senarai-pengguna.php styles */
    .dt-bottom-row { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.5rem; }
    .dataTables_length, #permohonanTable_wrapper .dataTables_length {
      white-space: nowrap !important;
      line-height: 1.4;
      display: inline-block;
    }
    .dataTables_length label, #permohonanTable_wrapper .dataTables_length label {
      white-space: nowrap !important;
      display: inline-flex !important;
      align-items: center;
      gap: 0.4rem;
      margin-bottom: 0;
      flex-wrap: nowrap !important;
      font-size: 0.875rem !important;
    }
    .dataTables_length select, #permohonanTable_wrapper .dataTables_length select {
      display: inline-block !important;
      margin: 0 0.4rem !important;
      flex-shrink: 0 !important;
      height: 36px !important;
      min-height: 36px !important;
      padding: 0.5rem 0.75rem !important;
      font-size: 0.875rem !important;
      line-height: 1.4 !important;
      border: 2px solid #e9ecef !important;
      border-radius: 0.5rem !important;
      min-width: 70px !important;
      transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
    }
    .dataTables_length select:hover, #permohonanTable_wrapper .dataTables_length select:hover { border-color: #ced4da !important; }
    .dataTables_length select:focus, #permohonanTable_wrapper .dataTables_length select:focus {
      border-color: #86b7fe !important;
      box-shadow: 0 0 0 0.25rem rgba(13,110,253,0.25) !important;
      outline: none !important;
    }
    .dt-top-left { white-space: nowrap !important; flex-wrap: nowrap !important; }
    /* Buttons in top-right: sizing, borders and gap consistent with senarai-pengguna */
    .dt-top-right button, #permohonanTable_wrapper .dt-top-right button {
      height: 36px !important;
      min-height: 36px !important;
      border: 2px solid #e9ecef !important;
      border-radius: 0.5rem !important;
      padding: 0.5rem 0.75rem !important;
      font-size: 0.875rem !important;
      line-height: 1.4 !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
      margin-left: 0 !important;
      margin-right: 0 !important;
      white-space: nowrap !important;
    }
    .dt-top-right button:hover, #permohonanTable_wrapper .dt-top-right button:hover { border-color: #ced4da !important; }
    .dt-top-right button:focus, #permohonanTable_wrapper .dt-top-right button:focus {
      border-color: #86b7fe !important;
      box-shadow: 0 0 0 0.25rem rgba(13,110,253,0.25) !important;
    }
    .dt-top-right { gap: 0.5rem !important; }
    .dt-top-right button + button { margin-left: 0 !important; }

    .icon-picker{
  border:1px solid rgba(0,0,0,.1);
  border-radius:10px;
  padding:10px;
  max-height:180px;
  overflow-y:auto;
  background:#fff;
}

[data-bs-theme="dark"] .icon-picker{
  background:#0f172a;
}

.icon-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill, minmax(40px,1fr));
  gap:8px;
}

.icon-item{
  display:flex;
  align-items:center;
  justify-content:center;
  height:40px;
  border-radius:8px;
  cursor:pointer;
  transition:all .2s ease;
  border:1px solid transparent;
}

.icon-item:hover{
  background:rgba(99,102,241,.1);
  border-color:#6366f1;
}

.icon-item.active{
  background:#6366f1;
  color:#fff;
}

</style>
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

        <!-- Tajuk + breadcrumb -->
        <div class="row mb-3">
          <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center flex-wrap">
              <h4 class="page-title"><?= __('permohonan_list_intro') ?></h4>
              <div class="page-title-right">
                  <ol class="breadcrumb m-0">
                  <li class="breadcrumb-item">
                    <a href="dashboard.php">
                      <i class="ri-home-4-line align-middle me-1"></i> <?= __('breadcrumb_home') ?? 'Home' ?>
                    </a>
                  </li>
                  <li class="breadcrumb-item active"><?= __('permohonan_list_intro') ?></li>
                </ol>
              </div>
            </div>
          </div>
        </div>

      
        <!-- Jadual Borang -->
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-body">
                <p class="text-muted mb-3"><?= __('permohonan_list_intro') ?></p>

                <div class="table-responsive">
                  <table class="table table-bordered align-middle" id="permohonanTable">
                    <thead>
                       <tr>
                        <th style="width:5%">#</th>
                        <th><?= __('permohonan_col_no') ?></th>
                        <th><?= __('permohonan_col_jenis') ?></th>
                        <th><?= __('permohonan_col_servis') ?></th>
                        <th><?= __('permohonan_col_status') ?></th>
                        <th><?= __('permohonan_col_tarikh') ?></th>
                        <th class="text-center"><?= __('permohonan_col_pdf') ?></th>
                      </tr>
                    </thead>
                  <tbody>
                    <?php if (!empty($senaraiPermohonan)): ?>
                     <?php foreach ($senaraiPermohonan as $i => $p): ?>

                      <?php
                      $id     = (int)$p['f_permohonanID'];
                      $no     = $p['f_no_permohonan'];
                      $jenis  = $p['jenis'];
                      $servis = $p['perkhidmatan'];
                      $status = $p['f_status'];
                      $tarikh = $p['f_created_at'] 
                          ? date('d/m/Y', strtotime($p['f_created_at'])) 
                          : '-';
                      ?>

                      <tr>

                      <td><?= $i+1 ?></td>

                      <td><?= h($no) ?></td>

                      <td>
                      <?php if ($jenis == 'EMAIL'): ?>
                      <span class="badge bg-primary"><?= __('permohonan_type_email') ?></span>
                      <?php endif; ?>
                      </td>

                      <td><?= h($servis) ?></td>

                      <td>

                      <?php if ($status == 'SUBMITTED'): ?>
                      <span class="badge bg-warning"><?= __('permohonan_status_submitted') ?></span>

                      <?php elseif ($status == 'APPROVED'): ?>
                      <span class="badge bg-success"><?= __('permohonan_status_approved') ?></span>

                      <?php elseif ($status == 'PROCESSING'): ?>
                      <span class="badge bg-secondary"><?= __('permohonan_status_processing') ?></span>

                      <?php elseif ($status == 'REJECTED'): ?>
                      <span class="badge bg-danger"><?= __('permohonan_status_rejected') ?></span>

                      <?php endif; ?>

                      </td>

                      <td><?= $tarikh ?></td>

                      <td class="text-center">
                      <a href="pdf-permohonan.php?jenis=<?= $jenis ?>&id=<?= $id ?>" 
                      target="_blank"
                      class="btn btn-sm btn-outline-danger">
                      <i class="ri-file-pdf-line"></i>
                      </a>
                      
                      </td>

                      </tr>

                      <?php endforeach; ?>

                    <?php else: ?>

                      <tr>
                        <td colspan="8" class="text-center text-muted">
                          <?= __('permohonan_no_record') ?>
                        </td>
                      </tr>

                    <?php endif; ?>

                    </tbody>

                  </table>

                </div>

              </div>
            </div>
          </div>
        </div>

      </div><!-- /.container-fluid -->
    </div><!-- /.content -->
    <?php include __DIR__ . '/../includes/footer.php'; ?>
  </div><!-- /.content-page -->
</div><!-- /.wrapper -->

<?php include __DIR__ . '/../includes/script.php'; ?>
<!-- Extracted JavaScript Modules -->

 

<script>
document.addEventListener('DOMContentLoaded', function () {

  if (!window.jQuery || !jQuery.fn || !jQuery.fn.DataTable) {
    console.warn('DataTables not loaded');
    return;
  }

  /* =====================================================
     TRANSLATION
  ===================================================== */
  const T = {
    no_records: <?= json_encode(__('borang_no_records')) ?>,
    search_placeholder: <?= json_encode(__('borang_dt_search_label')) ?>,
    dt_length_menu: <?= json_encode(__('borang_dt_length_menu')) ?>,
    dt_info: <?= json_encode(__('borang_dt_info')) ?>,
    dt_info_empty: <?= json_encode(__('borang_dt_info_empty')) ?>,
    dt_paginate_prev: <?= json_encode(__('borang_dt_paginate_prev')) ?>,
    dt_paginate_next: <?= json_encode(__('borang_dt_paginate_next')) ?>,
    add_title: <?= json_encode(__('borang_modal_add_title')) ?>,
    edit_title: <?= json_encode(__('borang_modal_edit_title')) ?>,
    btn_save: <?= json_encode(__('borang_modal_btn_save')) ?>,
    btn_update: <?= json_encode(__('btn_update')) ?>,
    success_title: "Berjaya",
    error_title: "Ralat"
  };

  /* =====================================================
     DATATABLE
  ===================================================== */
  const table = jQuery('#permohonanTable').DataTable({
    pageLength: 10,
    ordering: true,
    order: [[1, 'asc']],
    columnDefs: [
      { targets: [0, 5], orderable: false }
    ],
    language: {
      search: "",
      searchPlaceholder: T.search_placeholder,
      lengthMenu: T.dt_length_menu,
      info: T.dt_info,
      infoEmpty: T.dt_info_empty,
      zeroRecords: T.no_records,
      paginate: {
        previous: T.dt_paginate_prev,
        next: T.dt_paginate_next
      }
    },
    dom:
      '<"row mb-3 align-items-center"<"col-md-6 dt-top-left"l><"col-md-6 dt-top-right d-flex justify-content-end align-items-center gap-2">>' +
      't' +
      '<"row mt-3"<"col-md-6"i><"col-md-6 text-end"p>>',

    drawCallback: function () {
      const api = this.api();
      const pageInfo = api.page.info();
      let rowIndex = 0;

      api.rows({ page: 'current' }).every(function () {
        jQuery(this.node()).find('td:first')
          .text(pageInfo.start + rowIndex + 1);
        rowIndex++;
      });
    },

    initComplete: function () {
      const wrapper = jQuery('#permohonanTable_wrapper');
      const right = wrapper.find('.dt-top-right');
      const api = this.api();

      const searchInput = `
        <input type="search"
          class="form-control dt-custom-search"
          placeholder="${T.search_placeholder}"
          style="width:260px;">
      `;

      right.append(searchInput) ;

      wrapper.find('.dt-custom-search').on('keyup', function () {
        api.search(this.value).draw();
      });
    }
  });

});
</script>


</body>
</html>
