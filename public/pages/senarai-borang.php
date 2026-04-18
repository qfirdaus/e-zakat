<?php
// pages/senarai-borang.php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_login();

require_once __DIR__ . '/../controllers/BorangListController.php';
require_once __DIR__ . '/../classes/Database.php';

$controller = new BorangListController();

$lang            = $controller->lang ?? ($_SESSION['language'] ?? 'ms');
$profile         = $controller->profile ?? [];
$senaraiBorang   = $controller->senaraiBorang ?? [];
$senaraiKategori = $controller->senaraiKategori ?? [];
$version         = (string)($_ENV['APP_ASSET_VER'] ?? date('ymdHis'));

try {
    $pdo = Database::getInstance('mysql')->getConnection();
} catch (Throwable $e) {
    $pdo = null;
}

$canManageBorang = false;
try {
    require_once __DIR__ . '/../ajax/_helpers.php';
    if ($pdo instanceof PDO) {
        $canManageBorang = hasGroupManagePermission($pdo);
    }
} catch (Throwable $e) {
    $canManageBorang = false;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_token'];

function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="<?= h((string)$lang) ?>" data-bs-theme="<?= h($_SESSION['theme.layout'] ?? 'light') ?>">
<head>
  <?php include __DIR__ . '/../includes/head.php'; ?>
  <meta name="csrf-token" content="<?= h($csrf) ?>">
  <link href="<?= base_url('assets/css/datatables-standard.css') ?>?v=<?= h($version) ?>" rel="stylesheet">
  <style>
    #borangDT { table-layout: fixed; width: 100%; }
    .form-access-table { width: 100%; }
    .form-access-table th, .form-access-table td { vertical-align: middle; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .form-access-table th.col-bil, .form-access-table td.col-bil { width: 5%; text-align: center; }
    .form-access-table th.col-nama, .form-access-table td.col-nama { width: 28%; text-align: left; }
    .form-access-table th.col-kategori, .form-access-table td.col-kategori { width: 22%; text-align: left; }
    .form-access-table th.col-path, .form-access-table td.col-path { width: 20%; text-align: left; }
    .form-access-table th.col-status, .form-access-table td.col-status { width: 10%; text-align: center; }
    .form-access-table th.col-actions, .form-access-table td.col-actions { width: 15%; text-align: center; }
    .truncate-1line { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .icon-btn { padding: .25rem .5rem; line-height: 1; }
    .form-access-table .cell-inline {
      display: inline-flex;
      align-items: center;
      gap: .28rem;
      max-width: 100%;
      min-height: 1.4rem;
    }
    .form-access-table .group-chip,
    .form-access-table .access-chip {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: .12rem .34rem;
      min-height: 1rem;
      font-size: .64rem;
      line-height: 1;
      font-weight: 600;
      border-radius: 999px;
      border: 1px solid transparent;
      vertical-align: middle;
    }
    .form-access-table .group-chip {
      max-width: 100%;
      background: rgba(59, 130, 246, .10);
      color: #1d4ed8;
      border-color: rgba(59, 130, 246, .16);
    }
    .form-access-table .access-chip {
      min-width: 4.1rem;
      background: rgba(15, 23, 42, .04);
      color: #334155;
      border-color: rgba(148, 163, 184, .22);
    }
    .form-access-table .access-chip.is-allowed {
      background: rgba(16, 185, 129, .12);
      color: #0f766e;
      border-color: rgba(20, 184, 166, .16);
    }
    .form-access-table .access-chip.is-blocked {
      background: rgba(239, 68, 68, .10);
      color: #b91c1c;
      border-color: rgba(239, 68, 68, .14);
    }
    .form-access-table td.col-actions .btn,
    .form-access-table td.col-actions a.btn {
      box-shadow: none !important;
      width: 1.75rem;
      height: 1.75rem;
      padding: 0;
      line-height: 1;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    .form-access-table td.col-actions .btn + .btn,
    .form-access-table td.col-actions .btn + a.btn,
    .form-access-table td.col-actions a.btn + .btn,
    .form-access-table td.col-actions a.btn + a.btn {
      margin-left: .25rem !important;
    }
    .dt-bottom-row { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .5rem; }
    .dt-bottom-row .dataTables_info { margin: .25rem 0; white-space: nowrap; line-height: 1.5; }
    .dt-bottom-row .dataTables_paginate { margin-left: auto; }
    .dataTables_length,
    #borangDT_wrapper .dataTables_length {
      white-space: nowrap !important;
      line-height: 1.4;
      display: inline-block;
    }
    .dataTables_length label,
    #borangDT_wrapper .dataTables_length label {
      white-space: nowrap !important;
      display: inline-flex !important;
      align-items: center;
      gap: .4rem;
      margin-bottom: 0;
      flex-wrap: nowrap !important;
      font-size: .875rem !important;
    }
    .dataTables_length select,
    #borangDT_wrapper .dataTables_length select {
      display: inline-block !important;
      margin: 0 .4rem !important;
      flex-shrink: 0 !important;
      height: 36px !important;
      min-height: 36px !important;
      padding: .5rem .75rem !important;
      font-size: .875rem !important;
      line-height: 1.4 !important;
      border: 2px solid #e9ecef !important;
      border-radius: .5rem !important;
      min-width: 70px !important;
      transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out !important;
    }
    .dataTables_length select:hover,
    #borangDT_wrapper .dataTables_length select:hover { border-color: #ced4da !important; }
    .dataTables_length select:focus,
    #borangDT_wrapper .dataTables_length select:focus {
      border-color: #86b7fe !important;
      box-shadow: 0 0 0 .25rem rgba(13, 110, 253, .25) !important;
      outline: none !important;
    }
    .dataTables_length label > * { white-space: nowrap !important; display: inline !important; }
    .dt-top-left { white-space: nowrap !important; flex-wrap: nowrap !important; }
    .dt-top-left .dataTables_length { white-space: nowrap !important; }
    .dt-top-right button,
    #borangDT_wrapper .dt-top-right button {
      height: 36px !important;
      min-height: 36px !important;
      border: 2px solid #e9ecef !important;
      border-radius: .5rem !important;
      padding: .5rem .75rem !important;
      font-size: .875rem !important;
      line-height: 1.4 !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out !important;
      margin-left: 0 !important;
      margin-right: 0 !important;
      white-space: nowrap !important;
    }
    .dt-top-right button:hover,
    #borangDT_wrapper .dt-top-right button:hover { border-color: #ced4da !important; }
    .dt-top-right button:focus,
    #borangDT_wrapper .dt-top-right button:focus {
      border-color: #86b7fe !important;
      box-shadow: 0 0 0 .25rem rgba(13, 110, 253, .25) !important;
    }
    .dt-top-right { gap: .5rem !important; }
    .dt-top-right button + button { margin-left: 0 !important; }
    #borangDT_wrapper .dt-top-right,
    #borangDT_wrapper .dt-top-left {
      align-items: flex-start !important;
    }
    #borangDT_wrapper .dt-top-right > * {
      position: relative !important;
      top: 0 !important;
    }
    .modal,
    .modal-dialog,
    .modal-dialog-centered,
    .modal-content,
    .modal-content::before,
    .modal-content::after {
      box-shadow: none !important;
      outline: 0 !important;
      filter: none !important;
    }
    .modal-dialog { border: 0 !important; background: transparent !important; }
    .modal.fade { transition: none !important; }
    .modal.fade .modal-dialog { transition: none !important; transform: none !important; }
    .modal.show .modal-dialog { transform: none !important; }
    .modal-content {
      border: none;
      border-radius: 12px;
      box-shadow: none !important;
      outline: 0 !important;
      filter: none !important;
      overflow: hidden;
    }
    .modal-footer {
      border-top: 1px solid rgba(0, 0, 0, .08);
      border-radius: 0 0 12px 12px;
    }
    .modal-footer .btn { border-radius: 8px; }
    #editBorangModal .modal-dialog { max-width: 1220px; }
    #editBorangModal .modal-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: #fff;
      border-bottom: none;
      padding: 1.25rem 1.75rem;
    }
    #editBorangModal .modal-header .modal-title {
      color: #fff;
      font-weight: 600;
      font-size: 1.15rem;
      letter-spacing: .3px;
    }
    #editBorangModal .modal-header .btn-close,
    #addBorangModal .modal-header .btn-close,
    #formModal .modal-header .btn-close { filter: invert(1); opacity: .9; }
    #editBorangModal .modal-body,
    #addBorangModal .modal-body { padding: 1.25rem 1.5rem; }
    #editBorangModal .modal-body {
      max-height: min(80vh, 820px);
      overflow-y: auto;
      overflow-x: hidden;
      padding: 1.1rem 1.35rem;
    }
    #editBorangModal .modal-footer,
    #addBorangModal .modal-footer { padding: 1rem 1.75rem; background-color: #f8f9fa; }
    #editBorangModal .edit-modal-shell {
      display: grid;
      grid-template-columns: minmax(340px, 400px) minmax(0, 1fr);
      gap: 1rem;
      align-items: start;
    }
    #editBorangModal .edit-summary-card {
      position: sticky;
      top: 0;
    }
    #editBorangModal .edit-form-card {
      border: 2px solid #e9ecef;
      border-radius: .85rem;
      background: #fff;
      padding: 1rem 1rem .9rem;
      box-shadow: 0 2px 10px rgba(15, 23, 42, .05);
    }
    #editBorangModal .edit-form-card .row.g-3 {
      --bs-gutter-x: .85rem;
      --bs-gutter-y: .85rem;
    }
    #editBorangModal .form-label {
      margin-bottom: .45rem;
      font-size: .9rem;
    }
    #editBorangModal .form-section {
      margin-bottom: .75rem;
      padding-bottom: .75rem;
    }
    #editBorangModal .form-section-title {
      margin-bottom: .85rem;
    }
    #editBorangModal .icon-picker {
      max-height: none;
    }
    #editBorangModal .info-card {
      padding: .8rem;
    }
    #editBorangModal .info-item {
      margin-bottom: .55rem;
      padding: .55rem;
    }
    @media (max-width: 991.98px) {
      #editBorangModal .modal-dialog { max-width: 94vw; }
      #editBorangModal .edit-modal-shell {
        grid-template-columns: 1fr;
      }
      #editBorangModal .edit-summary-card {
        position: static;
      }
    }
    #editBorangModal .info-card,
    #addBorangModal .info-card {
      background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
      border: 2px solid #e9ecef;
      border-radius: .75rem;
      padding: .9rem;
      margin-bottom: 0;
      box-shadow: 0 2px 4px rgba(0, 0, 0, .05);
    }
    #editBorangModal .info-item,
    #addBorangModal .info-item {
      display: flex;
      align-items: flex-start;
      margin-bottom: .75rem;
      padding: .625rem;
      background-color: rgba(255,255,255,.7);
      border-radius: .5rem;
      transition: all .2s ease;
    }
    #editBorangModal .info-item:last-child,
    #addBorangModal .info-item:last-child { margin-bottom: 0; }
    #editBorangModal .info-item:hover,
    #addBorangModal .info-item:hover {
      background-color: rgba(255,255,255,.9);
      transform: translateX(2px);
    }
    #editBorangModal .info-icon,
    #addBorangModal .info-icon {
      color: #667eea;
      font-size: 1.2rem;
      margin-right: .875rem;
      margin-top: .125rem;
      flex-shrink: 0;
    }
    #editBorangModal .info-content,
    #addBorangModal .info-content { flex: 1; }
    #editBorangModal .info-label,
    #addBorangModal .info-label {
      font-size: .75rem;
      color: #6c757d;
      text-transform: uppercase;
      letter-spacing: .5px;
      font-weight: 700;
      margin-bottom: .25rem;
    }
    #editBorangModal .info-value,
    #addBorangModal .info-value {
      font-size: .95rem;
      color: #212529;
      font-weight: 600;
      line-height: 1.4;
    }
    #editBorangModal .info-value {
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    #editBorangModal .form-label,
    #addBorangModal .form-label {
      font-weight: 600;
      color: #495057;
      margin-bottom: .75rem;
      font-size: .95rem;
      display: flex;
      align-items: center;
    }
    #editBorangModal .form-label i,
    #addBorangModal .form-label i {
      margin-right: .5rem;
      font-size: 1.1rem;
    }
    #editBorangModal .form-select,
    #editBorangModal .form-control,
    #addBorangModal .form-select,
    #addBorangModal .form-control,
    .form-select,
    .form-control {
      min-height: 40px;
      border: 2px solid #e9ecef;
      border-radius: .5rem;
      padding: .5rem .75rem;
      font-size: .9rem;
      transition: all .2s ease;
    }
    #editBorangModal .form-select:focus,
    #editBorangModal .form-control:focus,
    #addBorangModal .form-select:focus,
    #addBorangModal .form-control:focus,
    .form-select:focus,
    .form-control:focus {
      border-color: #86b7fe;
      box-shadow: 0 0 0 .25rem rgba(13, 110, 253, .25);
    }
    #editBorangModal .form-section,
    #addBorangModal .form-section {
      margin-bottom: 1rem;
      padding-bottom: .9rem;
      border-bottom: 2px solid #e9ecef;
    }
    #editBorangModal .form-section:last-child,
    #addBorangModal .form-section:last-child {
      border-bottom: none;
      margin-bottom: 0;
      padding-bottom: 0;
    }
    #editBorangModal .form-section-title,
    #addBorangModal .form-section-title {
      font-size: .8rem;
      font-weight: 700;
      color: #495057;
      text-transform: uppercase;
      letter-spacing: .9px;
      margin-bottom: 1rem;
      padding-bottom: .5rem;
      border-bottom: 2px solid #667eea;
      display: flex;
      align-items: center;
    }
    #addBorangModal .form-section-title { border-bottom-color: #28a745; }
    #editBorangModal .form-section-title i,
    #addBorangModal .form-section-title i {
      margin-right: .4rem;
      font-size: .95rem;
    }
    #addBorangModal .form-section-title i { color: #28a745; }
    #editBorangModal .field-invalid,
    #addBorangModal .field-invalid {
      animation: fieldBlink .5s ease-in-out 3;
      border-color: #dc3545 !important;
      box-shadow: 0 0 0 .2rem rgba(220, 53, 69, .25) !important;
    }
    @keyframes fieldBlink {
      0%, 100% {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 .2rem rgba(220, 53, 69, .25) !important;
      }
      50% {
        border-color: #ff6b7a !important;
        box-shadow: 0 0 0 .3rem rgba(220, 53, 69, .4) !important;
      }
    }
    .form-access-table {
      border-radius: .75rem;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(15, 23, 42, .05);
    }
    .form-access-table thead th {
      background: linear-gradient(180deg, #f8fbff 0%, #eef4fb 100%);
      color: #475569;
      font-size: .82rem;
      font-weight: 700;
      letter-spacing: .02em;
      text-transform: uppercase;
      padding: .82rem .78rem;
      border-bottom-color: rgba(148, 163, 184, .18);
    }
    .form-access-table tbody tr {
      height: 3rem;
      background: #fff;
      transition: background-color .18s ease;
    }
    .form-access-table tbody tr:hover,
    .form-access-table.table > tbody > tr:hover > *,
    .form-access-table > tbody > tr:hover > td {
      background: rgba(59, 130, 246, .045) !important;
    }
    .form-access-table tbody td {
      padding: .5rem .72rem;
      font-size: .84rem;
      line-height: 1.22;
      border-color: rgba(226, 232, 240, .78);
      background: transparent !important;
    }
    .form-access-table td.col-kategori,
    .form-access-table td.col-status,
    .form-access-table td.col-actions {
      white-space: nowrap;
    }
    [data-bs-theme="dark"] .form-access-table thead th {
      background: rgba(30, 41, 59, .92);
      color: rgba(226, 232, 240, .94);
    }
    [data-bs-theme="dark"] .form-access-table tbody td {
      border-color: rgba(51, 65, 85, .72);
    }
    [data-bs-theme="dark"] .form-access-table tbody tr:hover,
    [data-bs-theme="dark"] .form-access-table.table > tbody > tr:hover > *,
    [data-bs-theme="dark"] .form-access-table > tbody > tr:hover > td {
      background: rgba(148, 163, 184, .1) !important;
    }
    [data-bs-theme="dark"] .form-access-table .group-chip {
      background: rgba(59, 130, 246, .18);
      color: #bfdbfe;
      border-color: rgba(96, 165, 250, .22);
    }
    [data-bs-theme="dark"] .form-access-table .access-chip {
      background: rgba(148, 163, 184, .10);
      color: #e2e8f0;
      border-color: rgba(148, 163, 184, .18);
    }
    [data-bs-theme="dark"] .form-access-table .access-chip.is-allowed {
      background: rgba(16, 185, 129, .18);
      color: #99f6e4;
      border-color: rgba(45, 212, 191, .18);
    }
    [data-bs-theme="dark"] .form-access-table .access-chip.is-blocked {
      background: rgba(239, 68, 68, .18);
      color: #fecaca;
      border-color: rgba(248, 113, 113, .20);
    }
    #borangDT tbody tr.row-updated-highlight {
      border-left: 4px solid #28a745 !important;
      box-shadow: 0 0 0 2px rgba(40, 167, 69, .30) !important;
      animation: highlightPulse 1.2s ease-in-out infinite;
    }
    #borangDT tbody tr.row-updated-highlight:hover {
      box-shadow: 0 0 0 2px rgba(40, 167, 69, .40) !important;
    }
    @keyframes highlightPulse {
      0%, 100% { box-shadow: 0 0 0 2px rgba(40, 167, 69, .28); }
      50% { box-shadow: 0 0 0 3px rgba(40, 167, 69, .48); }
    }
    #borangDT_wrapper .row.mb-2 { align-items: center; }
    #borangDT_wrapper .dataTables_filter { text-align: right; }
    #borangDT_wrapper .dataTables_filter label {
      margin: 0 !important;
      font-size: .875rem !important;
      display: inline-flex !important;
      align-items: center !important;
      gap: .5rem !important;
    }
    #borangDT_wrapper .dataTables_filter input {
      display: inline-block !important;
      width: 220px !important;
      max-width: 100% !important;
      height: 36px !important;
      min-height: 36px !important;
      padding: .5rem .75rem !important;
      font-size: .875rem !important;
      line-height: 1.4 !important;
      border: 2px solid #e9ecef !important;
      border-radius: .5rem !important;
      transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out !important;
    }
    #borangDT_wrapper .dataTables_filter input:focus {
      border-color: #86b7fe !important;
      box-shadow: 0 0 0 .25rem rgba(13, 110, 253, .25) !important;
      outline: none !important;
    }
    #addBorangModal .modal-dialog { max-width: 1180px; }
    #addBorangModal .modal-header {
      background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
      color: #fff;
      border-bottom: none;
      padding: 1rem 1.35rem;
    }
    #addBorangModal .modal-header .modal-title {
      color: #fff;
      font-weight: 600;
      font-size: 1.05rem;
      letter-spacing: .2px;
    }
    #addBorangModal .modal-body {
      max-height: min(80vh, 820px);
      overflow-y: auto;
      overflow-x: hidden;
      padding: 1.1rem 1.35rem;
    }
    #addBorangModal .add-modal-shell {
      display: grid;
      grid-template-columns: minmax(320px, 380px) minmax(0, 1fr);
      gap: 1rem;
      align-items: start;
    }
    #addBorangModal .add-summary-card {
      position: sticky;
      top: 0;
    }
    #addBorangModal .add-form-card {
      border: 2px solid #e9ecef;
      border-radius: .85rem;
      background: #fff;
      padding: 1rem 1rem .9rem;
      box-shadow: 0 2px 10px rgba(15, 23, 42, .05);
    }
    #addBorangModal .add-form-card .row.g-3 {
      --bs-gutter-x: .85rem;
      --bs-gutter-y: .85rem;
    }
    #addBorangModal .form-label {
      margin-bottom: .45rem;
      font-size: .9rem;
    }
    #addBorangModal .form-section {
      margin-bottom: .75rem;
      padding-bottom: .75rem;
    }
    #addBorangModal .form-section-title {
      margin-bottom: .85rem;
    }
    #addBorangModal .icon-picker {
      max-height: none;
    }
    #addBorangModal .info-card {
      padding: .8rem;
    }
    #addBorangModal .info-item {
      margin-bottom: .55rem;
      padding: .55rem;
    }
    @media (max-width: 991.98px) {
      #addBorangModal .modal-dialog { max-width: 94vw; }
      #addBorangModal .add-modal-shell {
        grid-template-columns: 1fr;
      }
      #addBorangModal .add-summary-card {
        position: static;
      }
    }
    #editBorangModal #eb_error,
    #addBorangModal #ab_error {
      margin-top: 1rem;
      border-radius: .5rem;
      border-left: 4px solid #dc3545;
    }
    .icon-picker {
      border: 1px solid rgba(0,0,0,.1);
      border-radius: 10px;
      padding: 10px;
      max-height: none;
      overflow: visible;
      background: #fff;
    }
    [data-bs-theme="dark"] .icon-picker { background: #0f172a; }
    .icon-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(40px,1fr));
      gap: 8px;
    }
    .icon-item {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 40px;
      border-radius: 8px;
      cursor: pointer;
      transition: all .2s ease;
      border: 1px solid transparent;
    }
    .icon-item:hover {
      background: rgba(99,102,241,.1);
      border-color: #6366f1;
    }
    .icon-item.active {
      background: #6366f1;
      color: #fff;
    }
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
      font-size: .95rem;
      font-weight: 500;
    }
    .btn-loading {
      position: relative;
      pointer-events: none;
      opacity: .7;
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
      animation: spin .6s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .btn-loading .btn-text { opacity: 0; }
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

        <div class="row mb-3">
          <div class="col-12">
            <div class="page-title-box d-flex justify-content-between align-items-center flex-wrap">
              <h4 class="page-title"><?= __('formList_page_title') ?></h4>
              <div class="page-title-right">
                <ol class="breadcrumb m-0">
                  <li class="breadcrumb-item">
                    <a href="dashboard.php">
                      <i class="ri-home-4-line align-middle me-1"></i> <?= __('formList_breadcrumb_home') ?>
                    </a>
                  </li>
                  <li class="breadcrumb-item active"><?= __('formList_page_title') ?></li>
                </ol>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-body">

                <div class="table-responsive dt-standard">
                <table class="table table-bordered align-middle form-access-table" id="borangDT">
                  <thead>
                    <tr>
                      <th class="col-bil"><?= __('userList_col_no') ?></th>
                      <th class="col-nama"><?= __('formList_col_name') ?></th>
                      <th class="col-kategori"><?= __('formList_col_category') ?></th>
                      <th class="col-path"><?= __('formList_col_path') ?></th>
                      <th class="col-status"><?= __('formList_col_status') ?></th>
                      <th class="col-actions"><?= __('formList_col_action') ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($senaraiBorang)): ?>
                      <?php foreach ($senaraiBorang as $b): ?>
                        <?php
                          $borangID      = (int)($b['f_borangID'] ?? 0);
                          $namaMs        = (string)($b['f_nama_ms'] ?? '');
                          $namaEn        = (string)($b['f_nama_en'] ?? '');
                          $displayName   = $lang === 'en' && $namaEn !== '' ? $namaEn : $namaMs;
                          $kategoriID    = (int)($b['f_kategoriID'] ?? ($b['kategoriID'] ?? 0));
                          $kategoriName  = (string)($b['f_groupName'] ?? ($b['kategori'] ?? ''));
                          $path          = (string)($b['f_path'] ?? '');
                          $icon          = (string)($b['f_icon'] ?? 'ri-file-line');
                          $flag          = (int)($b['f_flag'] ?? 0);
                        ?>
                        <tr
                          data-borang-id="<?= h((string)$borangID) ?>"
                          data-nama-ms="<?= h($namaMs) ?>"
                          data-nama-en="<?= h($namaEn) ?>"
                          data-kategori-id="<?= h((string)$kategoriID) ?>"
                          data-kategori-name="<?= h($kategoriName) ?>"
                          data-path="<?= h($path) ?>"
                          data-icon="<?= h($icon) ?>"
                          data-flag="<?= h((string)$flag) ?>">
                          <td class="col-bil"></td>
                          <td class="col-nama">
                            <span class="truncate-1line">
                              <i class="<?= h($icon) ?> me-2"></i><?= h($displayName) ?>
                            </span>
                          </td>
                          <td class="col-kategori">
                            <span class="cell-inline">
                              <span class="group-chip truncate-1line" title="<?= h($kategoriName) ?>"><?= h($kategoriName) ?></span>
                            </span>
                          </td>
                          <td class="col-path"><span class="truncate-1line"><?= h($path) ?></span></td>
                          <td class="col-status">
                            <?php if ($flag === 1): ?>
                              <span class="access-chip is-allowed"><?= __('formList_status_active') ?></span>
                            <?php else: ?>
                              <span class="access-chip is-blocked"><?= __('formList_status_inactive') ?></span>
                            <?php endif; ?>
                          </td>
                          <td class="col-actions">
                            <?php if ($canManageBorang): ?>
                              <button type="button"
                                class="btn btn-outline-primary btn-sm icon-btn btn-edit-borang"
                                title="<?= h(__('formList_modal_edit_title')) ?>"
                                data-borang-id="<?= h((string)$borangID) ?>">
                                <i class="ri-pencil-line"></i>
                              </button>
                            <?php endif; ?>
                            <button type="button"
                              class="btn btn-outline-info btn-sm icon-btn btn-open-form"
                              title="<?= h(__('formList_preview')) ?>"
                              data-path="<?= h($path) ?>"
                              data-title="<?= h($displayName) ?>">
                              <i class="ri-eye-line"></i>
                            </button>
                            <a href="pdf_permohonan_email.php?id=<?= h((string)$borangID) ?>"
                              target="_blank"
                              class="btn btn-outline-danger btn-sm icon-btn"
                              title="<?= h(__('formList_action_pdf')) ?>">
                              <i class="ri-file-pdf-line"></i>
                            </a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr><td colspan="6" class="text-center text-muted"><?= __('formList_no_records') ?></td></tr>
                    <?php endif; ?>
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
<script src="<?= base_url('assets/vendor/datatables.net/js/jquery.dataTables.min.js') ?>?v=<?= h($version) ?>"></script>
<script src="<?= base_url('assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js') ?>?v=<?= h($version) ?>"></script>

<div class="modal fade" id="editBorangModal" tabindex="-1" aria-hidden="true" aria-labelledby="editBorangModalTitle">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editBorangModalTitle">
          <i class="ri-file-edit-line me-2"></i> <?= __('formList_modal_edit_title') ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(__('formList_btn_close')) ?>"></button>
      </div>
      <div class="modal-body">
        <form id="editBorangForm" autocomplete="off">
          <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
          <input type="hidden" name="borangID" id="eb_borangID" value="">
          <div class="edit-modal-shell">
            <div class="edit-summary-card">
              <div class="form-section">
                <div class="form-section-title">
                  <i class="ri-file-list-3-line me-1"></i> <?= __('formList_page_title') ?>
                </div>

                <div class="info-card">
                  <div class="info-item">
                    <i class="ri-file-line info-icon"></i>
                    <div class="info-content">
                      <div class="info-label"><?= __('formList_col_name') ?></div>
                      <div class="info-value" id="eb_infoName"><?= __('userList_empty_value') ?></div>
                    </div>
                  </div>
                  <div class="info-item">
                    <i class="ri-route-line info-icon"></i>
                    <div class="info-content">
                      <div class="info-label"><?= __('formList_col_path') ?></div>
                      <div class="info-value" id="eb_infoPath"><?= __('userList_empty_value') ?></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="edit-form-card">
              <div class="form-section">
                <div class="form-section-title">
                  <i class="ri-settings-3-line me-1"></i> <?= __('userList_modal_section_settings') ?>
                </div>

                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label" for="eb_kategoriID">
                      <i class="ri-folders-line"></i> <?= __('formList_modal_label_section') ?>
                    </label>
                    <select class="form-select" id="eb_kategoriID" name="kategoriID" required>
                      <option value=""><?= __('formList_select_option') ?></option>
                      <?php foreach ($senaraiKategori as $kategori): ?>
                        <option value="<?= h((string)($kategori['f_groupID'] ?? '')) ?>"><?= h((string)($kategori['f_groupName'] ?? '')) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="eb_path">
                      <i class="ri-route-line"></i> <?= __('formList_modal_label_path') ?>
                    </label>
                    <input type="text" class="form-control" id="eb_path" name="path" placeholder="<?= h(__('formList_placeholder_path')) ?>" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="eb_nama_ms">
                      <i class="ri-translate-2"></i> <?= __('formList_modal_label_name_ms') ?>
                    </label>
                    <input type="text" class="form-control" id="eb_nama_ms" name="nama_ms" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="eb_nama_en">
                      <i class="ri-translate"></i> <?= __('formList_modal_label_name_en') ?>
                    </label>
                    <input type="text" class="form-control" id="eb_nama_en" name="nama_en">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="eb_icon">
                      <i class="ri-image-line"></i> <?= __('formList_modal_label_icon') ?>
                    </label>
                    <input type="text" class="form-control mb-2" id="eb_icon" name="icon" placeholder="<?= h(__('formList_placeholder_icon')) ?>">
                    <div class="icon-picker">
                      <div class="icon-grid" id="eb_iconGrid"></div>
                    </div>
                    <div class="mt-2">
                      <?= __('formList_preview') ?>:
                      <i id="eb_iconPreview" class="fs-4 text-primary"></i>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">
                      <i class="ri-shield-check-line"></i> <?= __('formList_modal_label_status') ?>
                    </label>
                    <select class="form-select" id="eb_flag" name="flag" required>
                      <option value="1"><?= __('formList_status_active') ?></option>
                      <option value="0"><?= __('formList_status_inactive') ?></option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
        <div id="eb_error" class="alert alert-danger d-none mt-3"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="ri-close-line me-1"></i> <?= __('formList_btn_close') ?>
        </button>
        <button type="button" class="btn btn-primary" id="eb_saveBtn">
          <i class="ri-save-3-line me-1"></i> <?= __('formList_btn_update') ?>
        </button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="addBorangModal" tabindex="-1" aria-hidden="true" aria-labelledby="addBorangModalTitle">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addBorangModalTitle">
          <i class="ri-file-add-line me-2"></i> <?= __('formList_modal_add_title') ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(__('formList_btn_close')) ?>"></button>
      </div>
      <div class="modal-body">
        <form id="addBorangForm" autocomplete="off">
          <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
          <div class="add-modal-shell">
            <div class="add-summary-card">
              <div class="form-section">
                <div class="form-section-title">
                  <i class="ri-information-line me-1"></i> <?= __('formList_page_title') ?>
                </div>

                <div class="info-card">
                  <div class="info-item">
                    <i class="ri-translate-2 info-icon"></i>
                    <div class="info-content">
                      <div class="info-label"><?= __('formList_modal_label_name_ms') ?></div>
                      <div class="info-value" id="ab_infoName"><?= __('formList_modal_add_title') ?></div>
                    </div>
                  </div>
                  <div class="info-item">
                    <i class="ri-route-line info-icon"></i>
                    <div class="info-content">
                      <div class="info-label"><?= __('formList_modal_label_path') ?></div>
                      <div class="info-value" id="ab_infoPath"><?= __('userList_empty_value') ?></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="add-form-card">
              <div class="form-section">
                <div class="form-section-title">
                  <i class="ri-settings-3-line me-1"></i> <?= __('userList_modal_section_settings') ?>
                </div>

                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label" for="ab_kategoriID">
                      <i class="ri-folders-line"></i> <?= __('formList_modal_label_section') ?> <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="ab_kategoriID" name="kategoriID" required>
                      <option value=""><?= __('formList_select_option') ?></option>
                      <?php foreach ($senaraiKategori as $kategori): ?>
                        <option value="<?= h((string)($kategori['f_groupID'] ?? '')) ?>"><?= h((string)($kategori['f_groupName'] ?? '')) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="ab_path">
                      <i class="ri-route-line"></i> <?= __('formList_modal_label_path') ?> <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="ab_path" name="path" placeholder="<?= h(__('formList_placeholder_path')) ?>" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="ab_nama_ms">
                      <i class="ri-translate-2"></i> <?= __('formList_modal_label_name_ms') ?> <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="ab_nama_ms" name="nama_ms" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="ab_nama_en">
                      <i class="ri-translate"></i> <?= __('formList_modal_label_name_en') ?>
                    </label>
                    <input type="text" class="form-control" id="ab_nama_en" name="nama_en">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="ab_icon">
                      <i class="ri-image-line"></i> <?= __('formList_modal_label_icon') ?>
                    </label>
                    <input type="text" class="form-control mb-2" id="ab_icon" name="icon" placeholder="<?= h(__('formList_placeholder_icon')) ?>">
                    <div class="icon-picker">
                      <div class="icon-grid" id="ab_iconGrid"></div>
                    </div>
                    <div class="mt-2">
                      <?= __('formList_preview') ?>:
                      <i id="ab_iconPreview" class="fs-4 text-primary"></i>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="ab_flag">
                      <i class="ri-shield-check-line"></i> <?= __('formList_modal_label_status') ?>
                    </label>
                    <select class="form-select" id="ab_flag" name="flag" required>
                      <option value="1"><?= __('formList_status_active') ?></option>
                      <option value="0"><?= __('formList_status_inactive') ?></option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
        <div id="ab_error" class="alert alert-danger d-none mt-3"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="ri-close-line me-1"></i> <?= __('formList_btn_close') ?>
        </button>
        <button type="button" class="btn btn-success" id="ab_saveBtn">
          <i class="ri-save-3-line me-1"></i> <?= __('formList_btn_save') ?>
        </button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="formModal" tabindex="-1" aria-hidden="true" aria-labelledby="formModalTitle">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:#fff;">
        <h5 class="modal-title" id="formModalTitle"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= h(__('formList_btn_close')) ?>"></button>
      </div>
      <div class="modal-body" id="formModalContent">
        <div class="modal-loading">
          <div class="spinner-border"></div>
          <span><?= __('formList_loading') ?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const $ = window.jQuery || window.$;
  const hasBootstrap = () => !!(window.bootstrap && bootstrap.Modal);
  const hasJQuery = () => !!($ && $.fn);
  const hasDT = () => !!($ && $.fn && ($.fn.DataTable || $.fn.dataTable));

  const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const canManageBorang = <?= $canManageBorang ? 'true' : 'false' ?>;
  const currentLangIsEn = <?= $lang === 'en' ? 'true' : 'false' ?>;

  const T = {
    noRecords: <?= json_encode(__('formList_no_records')) ?>,
    searchPlaceholder: <?= json_encode(__('formList_dt_search_placeholder')) ?>,
    lengthMenu: <?= json_encode(__('formList_dt_length_menu')) ?>,
    info: <?= json_encode(__('formList_dt_info')) ?>,
    infoEmpty: <?= json_encode(__('formList_dt_info_empty')) ?>,
    paginatePrev: <?= json_encode(__('formList_dt_paginate_prev')) ?>,
    paginateNext: <?= json_encode(__('formList_dt_paginate_next')) ?>,
    addTitle: <?= json_encode(__('formList_modal_add_title')) ?>,
    editTitle: <?= json_encode(__('formList_modal_edit_title')) ?>,
    btnAdd: <?= json_encode(__('formList_btn_add')) ?>,
    btnSave: <?= json_encode(__('formList_btn_save')) ?>,
    btnUpdate: <?= json_encode(__('formList_btn_update')) ?>,
    btnSaving: <?= json_encode(__('userList_btn_saving')) ?>,
    successTitle: <?= json_encode(__('formList_success_title')) ?>,
    errorTitle: <?= json_encode(__('formList_error_title')) ?>,
    okText: <?= json_encode(__('userList_btn_ok')) ?>,
    genericError: <?= json_encode(__('formList_error_generic')) ?>,
    invalidResponse: <?= json_encode(__('formList_error_invalid_response')) ?>,
    fetchError: <?= json_encode(__('formList_error_fetch_data')) ?>,
    processingTitle: <?= json_encode(__('formList_processing_title')) ?>,
    processingText: <?= json_encode(__('formList_processing_text')) ?>,
    draftTitle: <?= json_encode(__('formList_draft_title')) ?>,
    draftText: <?= json_encode(__('formList_draft_text')) ?>,
    draftContinue: <?= json_encode(__('formList_draft_continue')) ?>,
    draftNew: <?= json_encode(__('formList_draft_new')) ?>,
    submitSuccessText: <?= json_encode(__('formList_submit_success_text')) ?>,
    systemErrorTitle: <?= json_encode(__('formList_system_error_title')) ?>,
    emptyValue: <?= json_encode(__('userList_empty_value')) ?>
  };

  const CONFIG = {
    RATE_LIMIT_DELAY: 1000,
    HIGHLIGHT_DURATION: 12000,
    DT_RETRY_DELAY: 150,
    DT_MAX_RETRIES: 80
  };

  const icons = [
    'ri-file-line',
    'ri-file-text-line',
    'ri-file-paper-2-line',
    'ri-file-list-line',
    'ri-file-copy-line',
    'ri-file-edit-line',
    'ri-file-search-line',
    'ri-file-add-line',
    'ri-file-warning-line',
    'ri-checkbox-circle-line',
    'ri-draft-line',
    'ri-folder-line',
    'ri-folder-open-line',
    'ri-clipboard-line',
    'ri-article-line'
  ];

  const rateLimitTracker = new Map();
  let dtInitRetries = 0;
  let dtReady = false;
  let pageStarted = false;

  function sanitizeError(error) {
    if (!error) return T.genericError;
    const msg = error.message || error.toString() || T.genericError;
    return msg
      .replace(/in \/.*?\.php:\d+/g, '')
      .replace(/SQLSTATE\[.*?\]/g, '')
      .replace(/PDOException:/g, '')
      .replace(/Exception:/g, '')
      .substring(0, 200);
  }

  function showActionError(title, message) {
    if (window.Swal && typeof Swal.fire === 'function') {
      Swal.fire(title || T.errorTitle, message || T.genericError, 'error');
      return;
    }
    window.alert(message || T.genericError);
  }

  async function parseJsonResponse(response) {
    const text = await response.text();
    try {
      return JSON.parse(text);
    } catch (error) {
      throw new Error(T.invalidResponse);
    }
  }

  function checkRateLimit(key, delay = CONFIG.RATE_LIMIT_DELAY) {
    const now = Date.now();
    const lastCall = rateLimitTracker.get(key) || 0;
    if ((now - lastCall) < delay) return false;
    rateLimitTracker.set(key, now);
    return true;
  }

  function createRateLimitedHandler(fn, delay) {
    return async function (...args) {
      if (!checkRateLimit(fn.name || 'handler', delay)) return;
      return fn.apply(this, args);
    };
  }

  function validateField(fieldElement, isValid) {
    if (!fieldElement) return;
    fieldElement.classList.remove('field-invalid');
    if (!isValid) {
      fieldElement.classList.add('field-invalid');
      setTimeout(() => {
        fieldElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }, 100);
      setTimeout(() => {
        fieldElement.classList.remove('field-invalid');
      }, 1500);
    }
  }

  function showInlineError(el, msg) {
    if (!el) return;
    el.textContent = msg || T.genericError;
    el.classList.remove('d-none');
  }

  function hideInlineError(el) {
    if (!el) return;
    el.classList.add('d-none');
  }

  function updateIconPreview(input, preview) {
    if (!preview) return;
    preview.className = ((input?.value || 'ri-file-line') + ' fs-4 text-primary').trim();
  }

  function buildIconPicker(gridId, inputId, previewId) {
    const grid = document.getElementById(gridId);
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    if (!grid || !input || !preview) return;

    grid.innerHTML = '';

    icons.forEach((icon) => {
      const div = document.createElement('div');
      div.className = 'icon-item';
      div.dataset.icon = icon;
      div.innerHTML = `<i class="${icon} fs-5"></i>`;
      div.addEventListener('click', function () {
        grid.querySelectorAll('.icon-item').forEach((item) => item.classList.remove('active'));
        this.classList.add('active');
        input.value = icon;
        updateIconPreview(input, preview);
      });
      grid.appendChild(div);
    });

    input.addEventListener('input', function () {
      const val = this.value.trim();
      grid.querySelectorAll('.icon-item').forEach((item) => {
        item.classList.toggle('active', item.dataset.icon === val);
      });
      updateIconPreview(input, preview);
    });

    updateIconPreview(input, preview);
  }

  function highlightRow(borangID) {
    if (!borangID) return;
    const targetRow = document.querySelector(`#borangDT tbody tr[data-borang-id="${CSS.escape(String(borangID))}"]`);
    if (!targetRow) return;
    targetRow.classList.add('row-updated-highlight');
    setTimeout(() => {
      targetRow.classList.remove('row-updated-highlight');
    }, CONFIG.HIGHLIGHT_DURATION);
  }

  function getDisplayName(data) {
    return (currentLangIsEn && data.nama_en) ? data.nama_en : (data.nama_ms || T.emptyValue);
  }

  function updateInfoCard(prefix, data) {
    const infoName = document.getElementById(`${prefix}_infoName`);
    const infoPath = document.getElementById(`${prefix}_infoPath`);
    if (infoName) infoName.textContent = getDisplayName(data);
    if (infoPath) infoPath.textContent = data.path || T.emptyValue;
  }

  function updateEditSummaryCard() {
    const namaMs = document.getElementById('eb_nama_ms')?.value?.trim() || '';
    const namaEn = document.getElementById('eb_nama_en')?.value?.trim() || '';
    const path = document.getElementById('eb_path')?.value?.trim() || '';
    const activeName = currentLangIsEn && namaEn ? namaEn : (namaMs || namaEn || T.emptyValue);
    const infoName = document.getElementById('eb_infoName');
    const infoPath = document.getElementById('eb_infoPath');

    if (infoName) {
      infoName.textContent = activeName;
    }
    if (infoPath) {
      infoPath.textContent = path || T.emptyValue;
    }
  }

  function updateAddSummaryCard() {
    const namaMs = document.getElementById('ab_nama_ms')?.value?.trim() || '';
    const namaEn = document.getElementById('ab_nama_en')?.value?.trim() || '';
    const path = document.getElementById('ab_path')?.value?.trim() || '';
    const infoName = document.getElementById('ab_infoName');
    const infoPath = document.getElementById('ab_infoPath');

    if (infoName) {
      infoName.textContent = namaMs || namaEn || T.addTitle;
    }
    if (infoPath) {
      infoPath.textContent = path || T.emptyValue;
    }
  }

  function fillEditForm(data) {
    document.getElementById('eb_borangID').value = data.id || '';
    document.getElementById('eb_nama_ms').value = data.nama_ms || '';
    document.getElementById('eb_nama_en').value = data.nama_en || '';
    document.getElementById('eb_kategoriID').value = data.kategoriID || '';
    document.getElementById('eb_path').value = data.path || '';
    document.getElementById('eb_icon').value = data.icon || 'ri-file-line';
    document.getElementById('eb_flag').value = String(data.flag ?? 1);
    updateInfoCard('eb', data);
    updateEditSummaryCard();
    updateIconPreview(document.getElementById('eb_icon'), document.getElementById('eb_iconPreview'));
  }

  function resetAddForm() {
    const form = document.getElementById('addBorangForm');
    form?.reset();
    document.getElementById('ab_flag').value = '1';
    document.getElementById('ab_icon').value = 'ri-file-line';
    updateIconPreview(document.getElementById('ab_icon'), document.getElementById('ab_iconPreview'));
    hideInlineError(document.getElementById('ab_error'));
    updateAddSummaryCard();
  }

  function resetEditForm() {
    const form = document.getElementById('editBorangForm');
    form?.reset();
    document.getElementById('eb_borangID').value = '';
    document.getElementById('eb_icon').value = 'ri-file-line';
    document.getElementById('eb_flag').value = '1';
    document.getElementById('eb_infoName').textContent = T.emptyValue;
    document.getElementById('eb_infoPath').textContent = T.emptyValue;
    updateEditSummaryCard();
    updateIconPreview(document.getElementById('eb_icon'), document.getElementById('eb_iconPreview'));
    hideInlineError(document.getElementById('eb_error'));
  }

  function initDataTable() {
    if (dtReady) {
      return;
    }

    const tableEl = document.getElementById('borangDT');
    if (!tableEl || !hasJQuery() || !hasDT()) {
      if (dtInitRetries < CONFIG.DT_MAX_RETRIES) {
        dtInitRetries++;
        window.setTimeout(initDataTable, CONFIG.DT_RETRY_DELAY);
      }
      return;
    }

    if ($.fn.DataTable.isDataTable('#borangDT')) {
      $('#borangDT').DataTable().destroy();
    }

    $('#borangDT').DataTable({
      pageLength: 10,
      lengthChange: true,
      lengthMenu: [10, 25, 50, 100, 200],
      ordering: true,
      order: [[1, 'asc']],
      autoWidth: false,
      columnDefs: [
        { targets: [0, 5], orderable: false }
      ],
      language: {
        search: '',
        lengthMenu: T.lengthMenu,
        info: T.info,
        infoEmpty: T.infoEmpty,
        zeroRecords: T.noRecords,
        paginate: {
          previous: T.paginatePrev,
          next: T.paginateNext
        }
      },
      dom:
        '<"row mb-2"<"col-sm-12 col-md-6 dt-top-left"l><"col-sm-12 col-md-6 d-flex justify-content-md-end dt-top-right"f>>' +
        't' +
        '<"dt-bottom-row mt-2 d-flex justify-content-between align-items-center"<"dt-info-left"i><"dt-paging-right d-flex justify-content-end"p>>',
      drawCallback: function () {
        const api = this.api();
        const pageInfo = api.page.info();
        let rowIndex = 0;
        api.rows({ page: 'current' }).every(function () {
          $(this.node()).find('td:first').text(pageInfo.start + rowIndex + 1);
          rowIndex++;
        });
      },
      initComplete: function () {
        $('#borangDT_length select').addClass('form-select w-auto');
        $('#borangDT_length label').addClass('mb-0');
        const $topLeft = $('#borangDT_wrapper .dt-top-left').addClass('d-flex align-items-center gap-2 flex-nowrap');
        const $topRight = $('#borangDT_wrapper .dt-top-right').addClass('align-items-center gap-2 flex-nowrap');
        const $filter = $('#borangDT_filter');
        const $input = $filter.find('input');

        $filter.find('label').contents().filter(function () {
          return this.nodeType === 3;
        }).remove();
        $input.attr('placeholder', T.searchPlaceholder);

        if (canManageBorang && !document.getElementById('btnAddBorang')) {
          const $addBtn = $('<button type="button" id="btnAddBorang" class="btn btn-success">' +
            '<i class="ri-file-add-line me-1"></i> ' + T.btnAdd +
          '</button>');
          if ($topRight.length) {
            $topRight.append($addBtn);
          } else if ($filter.length) {
            $filter.append($addBtn);
          }
        }

        $topLeft.addClass('d-flex align-items-center gap-2 flex-nowrap');
        $topRight.addClass('d-flex justify-content-md-end');
      }
    });

    dtReady = true;
  }

  async function submitBorangForm(formEl, errorEl, saveBtn, successButtonColor) {
    hideInlineError(errorEl);

    const requiredFields = formEl.querySelectorAll('[required]');
    let isValid = true;
    requiredFields.forEach((field) => {
      if (!field.value || String(field.value).trim() === '') {
        validateField(field, false);
        isValid = false;
      }
    });

    if (!isValid) return null;

    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = `<i class="ri-loader-4-line ri-spin me-1"></i> ${T.btnSaving}`;

    try {
      const formData = new FormData(formEl);
      if (!formData.get('csrf_token')) {
        formData.append('csrf_token', CSRF);
      }

      const response = await fetch('<?= base_url('ajax/borang-save.php') ?>', {
        method: 'POST',
        body: formData
      });

      const text = await response.text();
      let payload = null;
      try {
        payload = JSON.parse(text);
      } catch (e) {
        throw new Error(T.invalidResponse);
      }

      if (!response.ok || !payload || !payload.success) {
        throw new Error((payload && payload.message) || T.genericError);
      }

      await Swal.fire({
        icon: 'success',
        title: T.successTitle,
        text: payload.message || T.successTitle,
        confirmButtonText: T.okText,
        confirmButtonColor: successButtonColor || '#28a745',
        timer: 1800,
        timerProgressBar: true
      });

      return payload;
    } catch (error) {
      showInlineError(errorEl, sanitizeError(error));
      return null;
    } finally {
      saveBtn.disabled = false;
      saveBtn.innerHTML = originalText;
    }
  }

  async function openEditModal(borangID) {
    const modalEl = document.getElementById('editBorangModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    hideInlineError(document.getElementById('eb_error'));

    try {
      const response = await fetch(`<?= base_url('ajax/borang-edit.php') ?>?id=${encodeURIComponent(borangID)}`, {
        headers: { 'Accept': 'application/json' }
      });
      const data = await parseJsonResponse(response);

      if (!response.ok || !data || !data.success || !data.data) {
        throw new Error((data && data.message) || T.fetchError);
      }

      fillEditForm(data.data);
      modal.show();
    } catch (error) {
      showActionError(T.errorTitle, sanitizeError(error));
    }
  }

  function openForm(path, title, draftId) {
    const titleEl = document.getElementById('formModalTitle');
    if (titleEl) {
      titleEl.textContent = '';
      const iconEl = document.createElement('i');
      iconEl.className = 'ri-file-text-line me-2';
      titleEl.appendChild(iconEl);
      titleEl.appendChild(document.createTextNode(String(title || '')));
    }

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('formModal'));
    modal.show();

    loadModalContent(path)
      .then(({ html }) => {
        if (!html) {
          throw new Error(T.fetchError);
        }

        document.getElementById('formModalContent').innerHTML = html;

        if (!draftId) {
          return null;
        }

        return fetch("<?= base_url('ajax/email-get-draft.php') ?>?id=" + encodeURIComponent(draftId), {
          headers: { 'Accept': 'application/json' }
        })
          .then((res) => parseJsonResponse(res).then((payload) => ({ response: res, payload })));
      })
      .then((result) => {
        if (!result) {
          return;
        }

        const { response, payload } = result;
        if (!response.ok || !payload || !payload.success) {
          return;
        }

        const d = payload.data || {};
        const draftField = document.querySelector('#draft_id');
        if (draftField) draftField.value = d.f_permohonanID ?? '';

        if (d.f_taraf_jawatan) {
          document.querySelectorAll("input[name='taraf_jawatan']").forEach((radio) => {
            if (String(radio.value).toLowerCase() === String(d.f_taraf_jawatan).toLowerCase()) {
              radio.checked = true;
            }
          });
        }

        const pejabat = document.querySelector("[name='no_tel_pejabat']");
        const bimbit = document.querySelector("[name='no_tel_bimbit']");
        const altEmail = document.querySelector("[name='alternative_email']");
        const emailDipohon = document.querySelector("[name='email_dipohon']");
        const tujuan = document.querySelector("[name='tujuan']");

        if (pejabat) pejabat.value = d.f_tel_pejabat ?? d.f_no_tel_pejabat ?? '';
        if (bimbit) bimbit.value = d.f_tel_bimbit ?? d.f_no_tel_bimbit ?? '';
        if (altEmail) altEmail.value = d.f_email_alternatif ?? d.f_alternative_email ?? '';
        if (emailDipohon) emailDipohon.value = d.f_email_dipohon ?? '';
        if (tujuan) tujuan.value = d.f_tujuan ?? '';
      })
      .catch((error) => {
        document.getElementById('formModalContent').innerHTML = `<div class="alert alert-danger mb-0">${sanitizeError(error)}</div>`;
      });
  }

  async function loadModalContent(path) {
    const rawPath = String(path || '').trim();
    if (!rawPath) {
      throw new Error(T.fetchError);
    }

    const safePath = rawPath.replace(/^\/+/, '').replace(/\\/g, '/');
    const baseName = safePath.replace(/^.*\//, '').replace(/\.php$/i, '');
    const normalizedName = baseName.replace(/-/g, '_');
    const candidates = [];

    const pushCandidate = (fileName) => {
      if (!fileName || candidates.includes(fileName)) return;
      candidates.push(fileName);
    };

    pushCandidate(safePath);
    pushCandidate(`${baseName}.php`);
    pushCandidate(`${normalizedName}.php`);
    pushCandidate(`md_${baseName}.php`);
    pushCandidate(`md_${normalizedName}.php`);

    for (const candidate of candidates) {
      const url = "<?= base_url('pages/modal/') ?>" + candidate;
      try {
        const response = await fetch(url, {
          method: 'GET',
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!response.ok) {
          continue;
        }

        const html = await response.text();
        if (html && html.trim() !== '') {
          return { url, html };
        }
      } catch (error) {
      }
    }

    throw new Error(T.fetchError);
  }

  function startPage() {
    if (pageStarted || !hasBootstrap() || !hasJQuery()) {
      return;
    }
    pageStarted = true;

    buildIconPicker('ab_iconGrid', 'ab_icon', 'ab_iconPreview');
    buildIconPicker('eb_iconGrid', 'eb_icon', 'eb_iconPreview');
    initDataTable();
    resetAddForm();
    resetEditForm();

    const addModalEl = document.getElementById('addBorangModal');
    const editModalEl = document.getElementById('editBorangModal');
    const addModal = bootstrap.Modal.getOrCreateInstance(addModalEl);
    const editModal = bootstrap.Modal.getOrCreateInstance(editModalEl);

    addModalEl.addEventListener('hidden.bs.modal', resetAddForm);
    editModalEl.addEventListener('hidden.bs.modal', resetEditForm);

    document.getElementById('ab_nama_ms')?.addEventListener('input', updateAddSummaryCard);
    document.getElementById('ab_nama_en')?.addEventListener('input', updateAddSummaryCard);
    document.getElementById('ab_path')?.addEventListener('input', updateAddSummaryCard);
    document.getElementById('eb_nama_ms')?.addEventListener('input', updateEditSummaryCard);
    document.getElementById('eb_nama_en')?.addEventListener('input', updateEditSummaryCard);
    document.getElementById('eb_path')?.addEventListener('input', updateEditSummaryCard);

    document.addEventListener('click', function (e) {
      if (e.target.closest('#btnAddBorang')) {
        resetAddForm();
        addModal.show();
        return;
      }

      const editBtn = e.target.closest('.btn-edit-borang');
      if (editBtn) {
        const borangID = editBtn.getAttribute('data-borang-id') || '';
        if (borangID) openEditModal(borangID);
        return;
      }

      const openBtn = e.target.closest('.btn-open-form');
      if (openBtn) {
        const path = openBtn.dataset.path || '';
        const title = openBtn.dataset.title || '';

        fetch("<?= base_url('ajax/email-check-draft.php') ?>", {
          headers: { 'Accept': 'application/json' }
        })
          .then((res) => parseJsonResponse(res).then((payload) => ({ response: res, payload })))
          .then(({ response, payload }) => {
            const draftId = response.ok && payload && payload.success ? (payload.draft_id || null) : null;

            if (!draftId) {
              openForm(path, title, null);
              return;
            }

            if (!(window.Swal && typeof Swal.fire === 'function')) {
              openForm(path, title, draftId);
              return;
            }

            Swal.fire({
              title: T.draftTitle,
              text: T.draftText,
              icon: 'question',
              showCancelButton: true,
              confirmButtonText: T.draftContinue,
              cancelButtonText: T.draftNew
            }).then((result) => {
              if (result.isConfirmed) {
                openForm(path, title, draftId);
              } else {
                openForm(path, title, null);
              }
            });
          })
          .catch(() => {
            openForm(path, title, null);
          });
      }
    });

    document.getElementById('ab_saveBtn')?.addEventListener('click', createRateLimitedHandler(async function () {
      const payload = await submitBorangForm(
        document.getElementById('addBorangForm'),
        document.getElementById('ab_error'),
        this,
        '#28a745'
      );

      if (payload) {
        addModal.hide();
        window.location.reload();
      }
    }, CONFIG.RATE_LIMIT_DELAY));

    document.getElementById('eb_saveBtn')?.addEventListener('click', createRateLimitedHandler(async function () {
      const formEl = document.getElementById('editBorangForm');
      const payload = await submitBorangForm(
        formEl,
        document.getElementById('eb_error'),
        this,
        '#0d6efd'
      );

      if (payload) {
        const editedID = formEl.querySelector('[name="borangID"]')?.value || '';
        editModal.hide();
        highlightRow(editedID);
        window.location.reload();
      }
    }, CONFIG.RATE_LIMIT_DELAY));
  }

  function startWhenReady() {
    if (pageStarted) {
      return;
    }

    if (hasBootstrap() && hasJQuery()) {
      startPage();
      return;
    }

    window.setTimeout(startWhenReady, CONFIG.DT_RETRY_DELAY);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startWhenReady);
  } else {
    startWhenReady();
  }

  window.addEventListener('load', function () {
    startWhenReady();
    dtInitRetries = 0;
    dtReady = false;
    initDataTable();
  });

  const dtWatchdog = window.setInterval(() => {
    if (document.getElementById('borangDT_wrapper')) {
      window.clearInterval(dtWatchdog);
      return;
    }
    if (hasDT() && document.getElementById('borangDT')) {
      dtInitRetries = 0;
      dtReady = false;
      initDataTable();
    }
  }, 1000);

  document.addEventListener('click', function (e) {
    if (e.target.closest('#btnNext1')) {
      const step1 = document.querySelector('#tabPemohon');
      const inputs = step1 ? step1.querySelectorAll('input, textarea, select') : [];

      for (const input of inputs) {
        if (!input.checkValidity()) {
          input.reportValidity();
          return;
        }
      }

      const form = document.getElementById('formPermohonanEmel');
      if (!form) return;
      const formData = new FormData(form);
      if (!formData.get('csrf_token') && CSRF) {
        formData.append('csrf_token', CSRF);
      }
      const draftID = document.getElementById('draft_id')?.value || '';
      const url = draftID
        ? "<?= base_url('ajax/email-update-draft.php') ?>"
        : "<?= base_url('ajax/email-create-draft.php') ?>";

      fetch(url, { method: 'POST', body: formData })
        .then((res) => res.json())
        .then((data) => {
          if (!data.success) {
            alert(data.message);
            return;
          }

          if (data.draft_id && document.getElementById('draft_id')) {
            document.getElementById('draft_id').value = data.draft_id;
          }

          const tab = new bootstrap.Tab(
            document.querySelector('#emailTabs button[data-bs-target="#tabEmail"]')
          );
          tab.show();
        });
    }

    if (e.target.closest('#btnNext2')) {
      const step2 = document.querySelector('#tabEmail');
      const inputs = step2 ? step2.querySelectorAll('input, textarea, select') : [];

      for (const input of inputs) {
        if (!input.checkValidity()) {
          input.reportValidity();
          return;
        }
      }

      const form = document.getElementById('formPermohonanEmel');
      if (!form) return;
      const formData = new FormData(form);
      if (!formData.get('csrf_token') && CSRF) {
        formData.append('csrf_token', CSRF);
      }

      fetch("<?= base_url('ajax/email-update-draft.php') ?>", {
        method: 'POST',
        body: formData
      })
        .then((res) => res.json())
        .then((data) => {
          if (!data.success) {
            Swal.fire(T.errorTitle, data.message, 'error');
            return;
          }

          const tab = new bootstrap.Tab(
            document.querySelector('#emailTabs button[data-bs-target="#tabConfirm"]')
          );
          tab.show();
        });
    }

    if (e.target.closest('#btnPrev2')) {
      const tab = new bootstrap.Tab(
        document.querySelector('#emailTabs button[data-bs-target="#tabPemohon"]')
      );
      tab.show();
    }

    if (e.target.closest('#btnPrev3')) {
      const tab = new bootstrap.Tab(
        document.querySelector('#emailTabs button[data-bs-target="#tabEmail"]')
      );
      tab.show();
    }
  });

  document.addEventListener('submit', function (e) {
    if (!e.target.matches('#formPermohonanEmel')) return;

    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    if (!formData.get('csrf_token') && CSRF) {
      formData.append('csrf_token', CSRF);
    }

    Swal.fire({
      title: T.processingTitle,
      text: T.processingText,
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    fetch("<?= base_url('ajax/email-submit.php') ?>", {
      method: 'POST',
      body: formData
    })
      .then((res) => res.json())
      .then((data) => {
        if (!data.success) {
          Swal.fire({
            icon: 'error',
            title: T.errorTitle,
            text: data.message
          });
          return;
        }

        Swal.fire({
          icon: 'success',
          title: T.successTitle,
          text: T.submitSuccessText
        }).then(() => {
          location.reload();
        });
      })
      .catch((err) => {
        Swal.fire({
          icon: 'error',
          title: T.systemErrorTitle,
          text: err.message
        });
      });
  });
})();
</script>

</body>
</html>
