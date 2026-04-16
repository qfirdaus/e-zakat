<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_login();

require_once __DIR__ . '/../controllers/BdrDailyReportController.php';

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

if (!function_exists('format_print_date')) {
  function format_print_date($value): string
  {
    $text = trim((string)$value);
    if ($text === '') {
      return '-';
    }

    try {
      return (new DateTimeImmutable($text))->format('d/m/Y');
    } catch (Throwable $exception) {
      return $text;
    }
  }
}

$controller = new BdrDailyReportController();
$lang = $_SESSION['lang'] ?? 'ms';
$entryId = isset($_GET['entry_id']) && is_numeric($_GET['entry_id']) ? (int)$_GET['entry_id'] : 0;
$printData = $entryId > 0 ? $controller->getPrintableEntryById($entryId) : null;

if (!$printData) {
    http_response_code(404);
}

$entry = $printData['entry'] ?? [];
$items = $printData['items'] ?? [];
$sources = $printData['sources'] ?? [];
$officialPrintTitle = 'BORANG KETETAPAN DAN PENGESAHAN TUGAS SEWAKTU MELAKSANAKAN TUGAS BEKERJA DARI RUMAH (BDR)';
$reportDateLabel = trim((string)($entry['report_date_label'] ?? '')) !== ''
    ? (string)$entry['report_date_label']
    : (string)($entry['report_date'] ?? '-');
$entryStatusLabel = ($entry['status'] ?? 'draft') === 'submitted'
  ? t('bdr_daily_report_badge_saved', 'Disimpan')
  : t('bdr_daily_report_badge_draft', 'Draf');
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($officialPrintTitle) ?></title>
  <style>
    :root {
      --ink: #0f172a;
      --muted: #475569;
      --line: #cbd5e1;
      --line-soft: #e2e8f0;
      --panel: #ffffff;
      --panel-soft: #f8fafc;
      --accent: #1d4ed8;
      --accent-soft: #dbeafe;
      --success-soft: #dcfce7;
      --warning-soft: #fef3c7;
    }

    body {
      margin: 0;
      background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
      color: var(--ink);
      font-family: "Segoe UI", Arial, sans-serif;
      font-size: 14px;
      line-height: 1.5;
    }

    .print-shell {
      max-width: 1080px;
      margin: 0 auto;
      padding: 28px;
    }

    .print-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 18px;
    }

    .print-toolbar .btn {
      border: 1px solid var(--line);
      background: rgba(255, 255, 255, 0.96);
      color: var(--ink);
      border-radius: 10px;
      padding: 10px 15px;
      font-weight: 600;
      cursor: pointer;
      box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    }

    .print-toolbar-title {
      font-size: 0.9rem;
      font-weight: 700;
      color: var(--muted);
      letter-spacing: 0.04em;
      text-transform: uppercase;
    }

    .print-card {
      background: var(--panel);
      border: 1px solid var(--line);
      border-radius: 20px;
      box-shadow: 0 24px 56px rgba(15, 23, 42, 0.12);
      overflow: hidden;
    }

    .print-card-inner {
      padding: 28px 30px 26px;
    }

    .print-hero {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 18px;
      padding: 24px 30px;
      background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
      color: #ffffff;
      border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    }

    .print-hero-eyebrow {
      margin: 0 0 8px;
      font-size: 0.76rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: rgba(255, 255, 255, 0.74);
      font-weight: 700;
    }

    .print-hero h1 {
      margin: 0;
      font-size: 1.2rem;
      line-height: 1.45;
      text-transform: uppercase;
      letter-spacing: 0.03em;
    }

    .print-hero p {
      margin: 10px 0 0;
      color: rgba(255, 255, 255, 0.84);
      max-width: 760px;
    }

    .print-status-pill {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 118px;
      padding: 0.55rem 0.9rem;
      border-radius: 999px;
      font-size: 0.78rem;
      font-weight: 700;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      border: 1px solid rgba(255, 255, 255, 0.18);
      background: rgba(255, 255, 255, 0.12);
    }

    .print-section {
      margin-top: 22px;
    }

    .print-section:first-child {
      margin-top: 0;
    }

    .print-section-tasks {
      margin-top: 22px;
    }

    .print-section-title {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin: 0 0 12px;
      padding-bottom: 8px;
      border-bottom: 2px solid var(--line-soft);
    }

    .print-section-title strong {
      font-size: 0.85rem;
      font-weight: 800;
      color: var(--muted);
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .print-section-subtitle {
      font-size: 0.8rem;
      color: #64748b;
    }

    .print-note {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      padding: 14px 16px;
      border: 1px solid var(--line-soft);
      border-radius: 14px;
      background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
      color: var(--muted);
      margin-bottom: 18px;
    }

    .print-note-badge {
      flex: 0 0 auto;
      width: 34px;
      height: 34px;
      border-radius: 10px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: var(--accent-soft);
      color: var(--accent);
      font-weight: 800;
    }

    .meta-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 14px 18px;
      margin-bottom: 0;
    }

    .meta-item {
      border: 1px solid var(--line-soft);
      border-radius: 14px;
      padding: 13px 14px;
      background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
    }

    .meta-item.full {
      grid-column: 1 / -1;
    }

    .meta-label {
      display: block;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #64748b;
      margin-bottom: 6px;
      font-weight: 800;
    }

    .meta-value {
      display: block;
      font-weight: 700;
      white-space: pre-line;
      color: var(--ink);
      line-height: 1.5;
    }

    .meta-status .meta-value {
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .status-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: #f59e0b;
      box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.16);
      flex: 0 0 auto;
    }

    .status-dot.is-submitted {
      background: #16a34a;
      box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.14);
    }

    .table-shell {
      border: 1px solid var(--line);
      border-radius: 16px;
      overflow: hidden;
      background: #ffffff;
      box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06);
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th,
    td {
      border: 1px solid var(--line-soft);
      padding: 12px 13px;
      vertical-align: top;
    }

    thead th {
      background: linear-gradient(180deg, #eff6ff 0%, #e2e8f0 100%);
      text-align: left;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--muted);
      font-weight: 800;
    }

    tbody td {
      background: #ffffff;
      color: var(--ink);
    }

    tbody tr:nth-child(even) td {
      background: #fcfdff;
    }

    .col-bil {
      width: 7%;
      text-align: center;
      font-weight: 700;
    }

    .task-status-chip {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0.35rem 0.65rem;
      border-radius: 999px;
      font-size: 0.75rem;
      font-weight: 700;
      border: 1px solid transparent;
      background: var(--accent-soft);
      color: var(--accent);
      white-space: nowrap;
    }

    .task-status-chip.is-selesai {
      background: var(--success-soft);
      color: #166534;
      border-color: rgba(22, 101, 52, 0.12);
    }

    .task-status-chip.is-tidak-selesai {
      background: #fee2e2;
      color: #991b1b;
      border-color: rgba(153, 27, 27, 0.12);
    }

    .task-status-chip.is-dalam-proses {
      background: #e0f2fe;
      color: #075985;
      border-color: rgba(7, 89, 133, 0.12);
    }

    .task-status-chip.is-ditangguhkan {
      background: var(--warning-soft);
      color: #92400e;
      border-color: rgba(146, 64, 14, 0.12);
    }

    .task-status-chip.is-menunggu-maklum-balas {
      background: #ede9fe;
      color: #6d28d9;
      border-color: rgba(109, 40, 217, 0.12);
    }

    .empty-state {
      padding: 30px 24px;
      text-align: center;
      border: 1px dashed var(--line);
      border-radius: 16px;
      color: #64748b;
      background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }

    .source-list {
      display: grid;
      gap: 10px;
    }

    .source-item {
      border: 1px solid var(--line-soft);
      border-radius: 12px;
      padding: 12px 14px;
      background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }

    .source-item strong {
      display: block;
      margin-bottom: 4px;
      font-size: 0.82rem;
      color: var(--ink);
    }

    .source-item span {
      display: block;
      color: var(--muted);
      font-size: 0.85rem;
    }

    .print-footer {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      gap: 18px;
      margin-top: 26px;
      padding-top: 16px;
      border-top: 1px dashed var(--line);
      color: #64748b;
      font-size: 0.82rem;
    }

    .print-footer strong {
      display: block;
      color: var(--muted);
      margin-bottom: 3px;
    }

    .print-signature {
      min-width: 220px;
      text-align: right;
    }

    .print-signature-line {
      margin-top: 34px;
      border-top: 1px solid #94a3b8;
      padding-top: 8px;
    }

    .print-title {
      text-align: center;
      margin-bottom: 18px;
    }

    @media print {
      body {
        background: #ffffff;
      }

      .print-shell {
        max-width: none;
        padding: 0;
      }

      .print-toolbar {
        display: none;
      }

      .print-card {
        border: none;
        box-shadow: none;
        border-radius: 0;
      }

      .print-card-inner {
        padding: 18px 0 0;
      }

      .print-hero {
        padding: 0 0 14px;
        background: transparent;
        color: var(--ink);
        border-bottom: 2px solid var(--ink);
      }

      .print-hero-eyebrow,
      .print-hero p {
        color: var(--muted);
      }

      .print-hero-eyebrow,
      .print-hero p,
      .print-status-pill,
      .print-note {
        display: none;
      }

      .print-section-tasks {
        break-before: page;
        page-break-before: always;
        margin-top: 0;
      }

      .print-status-pill {
        color: var(--ink);
        background: transparent;
        border-color: var(--line);
      }

      .table-shell,
      .meta-item,
      .source-item,
      .print-note {
        box-shadow: none;
      }
    }

    @media (max-width: 767.98px) {
      .print-shell {
        padding: 16px;
      }

      .print-toolbar,
      .print-hero,
      .print-footer {
        flex-direction: column;
        align-items: stretch;
      }

      .print-card-inner {
        padding: 20px 18px;
      }

      .print-hero {
        padding: 20px 18px;
      }

      .meta-grid {
        grid-template-columns: 1fr;
      }

      .print-signature {
        min-width: 0;
        text-align: left;
      }
    }
  </style>
</head>
<body>
  <div class="print-shell">
    <div class="print-toolbar">
      <div class="print-toolbar-title"><?= h(t('bdr_daily_report_print_toolbar_title', 'Laporan Harian BDR')) ?></div>
      <div>
        <button type="button" class="btn" onclick="window.print()"><?= h(t('bdr_daily_report_print_button', 'Cetak / Simpan PDF')) ?></button>
      </div>
    </div>

    <div class="print-card">
      <?php if (!$printData): ?>
        <div class="empty-state"><?= h(t('bdr_daily_report_print_not_found', 'Rekod laporan harian tidak ditemui atau tidak boleh diakses.')) ?></div>
      <?php else: ?>
        <div class="print-hero">
          <div>
            <div class="print-hero-eyebrow"><?= h(t('bdr_daily_report_print_eyebrow', 'Dokumen Rasmi')) ?></div>
            <h1><?= h($officialPrintTitle) ?></h1>
          </div>
        </div>

        <div class="print-card-inner">
          <section class="print-section">
            <div class="print-section-title">
              <strong><?= h(t('bdr_daily_report_print_section_report_info', 'Maklumat Laporan')) ?></strong>
              <span class="print-section-subtitle"><?= h(t('bdr_daily_report_print_section_report_info_subtitle', 'Ringkasan staf dan rekod rujukan')) ?></span>
            </div>

            <div class="meta-grid">
              <div class="meta-item">
                <span class="meta-label"><?= h(t('bdr_daily_report_print_meta_name', 'Nama')) ?></span>
                <span class="meta-value"><?= h($entry['staff_name'] ?? '-') ?></span>
              </div>
              <div class="meta-item">
                <span class="meta-label"><?= h(t('bdr_daily_report_print_meta_staff_id', 'No. Staf')) ?></span>
                <span class="meta-value"><?= h($entry['staf_id'] ?? '-') ?></span>
              </div>
              <div class="meta-item">
                <span class="meta-label"><?= h(t('bdr_daily_report_print_meta_date', 'Tarikh')) ?></span>
                <span class="meta-value"><?= h($reportDateLabel) ?></span>
              </div>
              <div class="meta-item meta-status">
                <span class="meta-label"><?= h(t('bdr_daily_report_print_meta_status', 'Status Laporan')) ?></span>
                <span class="meta-value"><?= h($entryStatusLabel) ?></span>
              </div>
              <div class="meta-item full">
                <span class="meta-label"><?= h(t('bdr_daily_report_print_meta_reference', 'No. Rujukan')) ?></span>
                <span class="meta-value"><?= h($entry['reference_summary'] ?? '-') ?></span>
              </div>
              <div class="meta-item">
                <span class="meta-label"><?= h(t('bdr_daily_report_print_meta_category', 'Kategori')) ?></span>
                <span class="meta-value"><?= h($entry['category_summary'] ?? '-') ?></span>
              </div>
              <div class="meta-item">
                <span class="meta-label"><?= h(t('bdr_daily_report_print_meta_approval', 'Kelulusan')) ?></span>
                <span class="meta-value"><?= h($entry['approval_name'] ?? '-') ?></span>
              </div>
            </div>
          </section>

          <section class="print-section print-section-tasks">
            <div class="print-section-title">
              <strong><?= h(t('bdr_daily_report_print_section_tasks', 'Butiran Tugasan')) ?></strong>
              <span class="print-section-subtitle"><?= h(t('bdr_daily_report_print_section_tasks_subtitle', 'Senarai tugasan harian yang direkodkan')) ?></span>
            </div>

            <div class="table-shell">
              <table>
                <thead>
                  <tr>
                    <th class="col-bil">Bil.</th>
                    <th style="width: 38%;">Ketetapan Tugas</th>
                    <th style="width: 37%;">Ulasan</th>
                    <th style="width: 18%;"><?= h(t('bdr_daily_report_task_table_col_execution', 'Status')) ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($items === []): ?>
                    <tr>
                      <td colspan="4"><?= h(t('bdr_daily_report_print_no_tasks', 'Tiada tugasan direkodkan.')) ?></td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($items as $index => $item): ?>
                      <?php
                        $taskStatusLabel = BdrDailyReportController::getTaskExecutionStatusLabel((string)($item['task_execution_status'] ?? 'SELESAI'));
                        $taskStatusClass = strtolower(trim((string)($item['task_execution_status'] ?? 'SELESAI')));
                        $taskStatusClass = str_replace('_', '-', preg_replace('/\s+/', '-', $taskStatusClass));
                      ?>
                      <tr>
                        <td class="col-bil"><?= h((string)($item['sort_order'] ?? ($index + 1))) ?></td>
                        <td><?= h($item['task_title'] ?? '-') ?></td>
                        <td><?= h($item['staff_comment'] ?? '-') ?></td>
                        <td>
                          <span class="task-status-chip is-<?= h($taskStatusClass) ?>">
                            <?= h($taskStatusLabel) ?>
                          </span>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </section>

          <div class="print-footer">
            <div>
              <strong><?= h(t('bdr_daily_report_print_printed_at', 'Tarikh Cetakan')) ?></strong>
              <div><?= h(date('d/m/Y H:i')) ?></div>
            </div>
            <div class="print-signature">
              <strong><?= h(t('bdr_daily_report_print_staff_confirmation', 'Pengesahan Staf')) ?></strong>
              <div class="print-signature-line"><?= h(t('bdr_daily_report_print_signature_hint', 'Tandatangan / Cop')) ?></div>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>