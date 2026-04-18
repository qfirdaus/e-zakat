<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../ajax/_helpers.php';
require_login();

$tcpdfCandidates = [
    __DIR__ . '/../tcpdf/tcpdf.php',
    dirname(__DIR__, 2) . '/app/tcpdf/tcpdf.php',
];

$tcpdfLoaded = false;
foreach ($tcpdfCandidates as $candidate) {
    if (is_file($candidate)) {
        require_once $candidate;
        $tcpdfLoaded = class_exists('TCPDF');
        if ($tcpdfLoaded) {
            break;
        }
    }
}

if (!$tcpdfLoaded) {
    http_response_code(500);
  exit((string) __('email_pdf_library_missing'));
}

$pdo = Database::getInstance('mysql')->getConnection();
$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
  exit((string) __('email_error_invalid_id'));
}

$stmt = $pdo->prepare(
    "
    SELECT *
    FROM v_permohonan_email
    WHERE f_permohonanID = :id
    LIMIT 1
    "
);
$stmt->execute([':id' => $id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    http_response_code(404);
  exit((string) __('email_pdf_not_found'));
}

$currentStafID = trim((string) ($_SESSION['f_stafID'] ?? ''));
$isOwner = $currentStafID !== '' && $currentStafID === trim((string) ($data['f_stafID'] ?? ''));
if (!$isOwner && !hasGroupManagePermission($pdo)) {
    http_response_code(403);
  exit((string) __('email_pdf_forbidden'));
}

$logoCandidates = [
    realpath(__DIR__ . '/../assets/img/logo_upnm.png') ?: '',
    realpath(dirname(__DIR__, 2) . '/app/assets/img/logo_upnm.png') ?: '',
];
$logoPath = '';
foreach ($logoCandidates as $candidate) {
    if ($candidate !== '' && is_file($candidate)) {
        $logoPath = $candidate;
        break;
    }
}

$pdf = new TCPDF('P', 'mm', 'A4');
$pdf->SetCreator('e-Facility');
$pdf->SetAuthor('UPNM');
$pdf->SetTitle((string) __('email_pdf_title'));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

$safe = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');

$logoHtml = $logoPath !== ''
    ? '<img src="' . str_replace('\\', '/', $logoPath) . '" height="60">'
    : '';

$html = '
<table width="100%" border="0">
  <tr>
    <td width="15%">' . $logoHtml . '</td>
    <td width="85%" align="center">
      <h3>UNIVERSITI PERTAHANAN NASIONAL MALAYSIA</h3>
      <h4>' . $safe(__('email_pdf_header_official')) . '</h4>
    </td>
  </tr>
</table>
<br>
<table border="1" cellpadding="6">
  <tr><td width="35%">' . $safe(__('email_pdf_field_application_no')) . '</td><td>' . $safe($data['f_no_permohonan'] ?? '') . '</td></tr>
  <tr><td>' . $safe(__('email_pdf_field_applicant_name')) . '</td><td>' . $safe($data['f_nama'] ?? '') . '</td></tr>
  <tr><td>' . $safe(__('email_pdf_field_staff_id')) . '</td><td>' . $safe($data['f_stafID'] ?? '') . '</td></tr>
  <tr><td>' . $safe(__('email_pdf_field_requested_email')) . '</td><td>' . $safe($data['f_email_dipohon'] ?? '') . '</td></tr>
  <tr><td>' . $safe(__('email_pdf_field_purpose')) . '</td><td>' . nl2br($safe($data['f_tujuan'] ?? '')) . '</td></tr>
  <tr><td>' . $safe(__('email_pdf_field_application_date')) . '</td><td>' . $safe($data['f_tarikh_hantar'] ?? '') . '</td></tr>
</table>
<br><br>
<table border="1" cellpadding="8">
  <tr>
    <td width="50%" height="70">
      ' . $safe(__('email_pdf_prepared_by')) . '
      <br><br><br><br>
      _________________________
      <br>
      ' . $safe(__('email_pdf_applicant')) . '
    </td>
    <td width="50%">
      ' . $safe(__('email_pdf_reviewed_by')) . '
      <br><br><br><br>
      _________________________
      <br>
      ' . $safe(__('email_pdf_ict_section')) . '
    </td>
  </tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('permohonan_email_' . ($data['f_no_permohonan'] ?? $id) . '.pdf', 'I');