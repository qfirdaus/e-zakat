<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/Mailer.php';
require_once __DIR__ . '/../classes/EmailTemplateDeliveryService.php';

header('Content-Type: application/json; charset=utf-8');

try {
  /* 1) Method guard */
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'success'=>false,'message'=>'Method not allowed']); exit;
  }

  /* 2) CSRF */
  $csrf_session = $_SESSION['csrf_token'] ?? '';
  $csrf_form    = $_POST['csrf_token']   ?? '';
  if (!$csrf_session || !$csrf_form || !hash_equals($csrf_session, $csrf_form)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'success'=>false,'message'=>'CSRF token tidak sah']); exit;
  }

  /* 3) Ambil & normalkan input (sokong nama field lama & baru) */
  $role       = trim((string)($_POST['role'] ?? $_POST['peranan'] ?? ''));
  $email      = trim((string)($_POST['email'] ?? ''));
  $targetNama = trim((string)($_POST['nama'] ?? $_POST['target_nama'] ?? '')); // alias
  $stafNama   = trim((string)($_POST['staf_nama'] ?? $_POST['staff_nama'] ?? $_POST['nama_staf'] ?? ''));
  // sokong pelbagai kunci untuk no. staf
  $stafNopek  = trim((string)(
                    $_POST['staf_nopek'] ??
                    $_POST['nopekerja'] ??
                    $_POST['nopek']     ??
                    $_POST['nostaf']    ?? ''
                  ));
  $tahun      = trim((string)($_POST['tahun'] ?? date('Y')));

  // ID tambahan (tak wajib, untuk logging/masa depan)
  $penilaiID  = trim((string)($_POST['penilaiID'] ?? ''));
  $stafID     = trim((string)($_POST['stafID']    ?? ''));

  /* 4) Validasi minima */
  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'success'=>false,'message'=>'Alamat emel tidak sah']); exit;
  }
  if ($tahun === '') {
    http_response_code(400);
    echo json_encode(['ok'=>false,'success'=>false,'message'=>'Tahun tidak diberikan']); exit;
  }

  $pdoMysql = Database::pdoMysql();          // guna helper DB projek
  $ok = false;
  $deliveryError = '';

  try {
    $deliveryService = new EmailTemplateDeliveryService($pdoMysql);
    $deliveryService->sendDefault('staff', 'reminder', $email, [
      'recipient_name' => $targetNama ?: 'Tuan/Puan',
      'recipient_email' => $email,
      'workflow_role' => $role,
      'staff_name' => $stafNama ?: '-',
      'staff_nopek' => $stafNopek ?: '-',
      'year' => $tahun,
      'action_link' => (string)app_config('mail.default_action_url', '#'),
      'target_name' => $targetNama ?: 'Tuan/Puan',
    ], [], [
      'recipient_name' => $targetNama ?: 'Tuan/Puan',
      'recipient_email' => $email,
      'recipient_role' => 'staff',
      'footer_note' => (string)app_config_localized('mail.footer_note', 'Emel ini dijana secara automatik. Sila jangan balas emel ini.'),
    ]);
    $ok = true;
  } catch (Throwable $templateDeliveryError) {
    $deliveryError = $templateDeliveryError->getMessage();
    error_log('[send_reminder] template delivery fallback: ' . $deliveryError);
  }

  if (!$ok) {
    /* 5) Subjek & badan emel - fallback guna template professional sedia ada */
    $subRole = $role !== '' ? " ({$role})" : '';
    $subject = "Peringatan Penilaian SKT {$tahun}{$subRole}";

    $actionUrl = (string)app_config('mail.default_action_url', '#');
    $systemName = (string)app_config('mail.system_name', app_config('system.name', 'Base System'));
    $footerNote = (string)app_config_localized('mail.footer_note', 'Emel ini dijana secara automatik. Sila jangan balas emel ini.');

    [$bodyHtml, $bodyTxt] = Mailer::render('reminder', [
      'targetNama'  => $targetNama ?: 'Tuan/Puan',
      'stafNama'    => $stafNama ?: '-',
      'stafNopek'   => $stafNopek ?: '-',
      'tahun'       => $tahun,
      'jabatanNama' => '',
      'deadline'    => '',
      'actionUrl'   => $actionUrl,
      'systemName'  => $systemName,
      'footerNote'  => $footerNote
    ]);

    $mailer = Mailer::fromConfig($pdoMysql);
    $ok = $mailer->send($email, $subject, $bodyHtml, $bodyTxt);

    if (!$ok && $deliveryError !== '') {
      error_log('[send_reminder] fallback mailer also failed after template delivery error: ' . $deliveryError);
    }
  }

  if (!$ok) {
    http_response_code(500);
    $err = isset($mailer) && method_exists($mailer, 'getLastError') ? $mailer->getLastError() : ($deliveryError ?: 'Gagal menghantar emel');
    echo json_encode(['ok'=>false,'success'=>false,'message'=>$err]); exit;
  }

  /* 7) Response berjaya */
  echo json_encode([
    'ok'      => true,
    'success' => true,
    'message' => 'Emel berjaya dihantar',
  ]);
  exit;

} catch (Throwable $e) {
  error_log('[send_reminder] '.$e->getMessage());
  http_response_code(500);
  echo json_encode(['ok'=>false,'success'=>false,'message'=>$e->getMessage()]);
  exit;
}
