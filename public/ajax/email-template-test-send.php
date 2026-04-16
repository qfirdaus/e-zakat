<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_login();
require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/EmailPlaceholder.php';
require_once __DIR__ . '/../classes/EmailTemplateRenderService.php';
require_once __DIR__ . '/../classes/Mailer.php';

header('Content-Type: application/json; charset=utf-8');

function normalizeEmailTemplateJsonInput(string $value): string
{
    $normalized = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
    $normalized = str_replace("\xC2\xA0", ' ', $normalized);
    $normalized = str_replace(["\u{2018}", "\u{2019}"], "'", $normalized);
    $normalized = str_replace(["\u{201C}", "\u{201D}"], '"', $normalized);
    $normalized = preg_replace('/,\s*([}\]])/', '$1', $normalized) ?? $normalized;

    return trim($normalized);
}

/**
 * @return array<string,mixed>
 */
function emailTemplateTestSendPayload(): array
{
    $sampleVariables = [];
    $sampleInput = normalizeEmailTemplateJsonInput((string)($_POST['sample_variables'] ?? '{}'));
    if ($sampleInput !== '') {
        $decoded = json_decode($sampleInput, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw new InvalidArgumentException((string)(__('emailTemplate_error_sample_json_invalid') ?: 'Sample variables mesti dalam format JSON yang sah.'));
        }
        $sampleVariables = $decoded;
    }

    return [
        'test_email' => trim((string)($_POST['test_email'] ?? '')),
        'subject_template' => trim((string)($_POST['subject_template'] ?? '')),
        'body_html' => trim((string)($_POST['body_html'] ?? '')),
        'body_text' => trim((string)($_POST['body_text'] ?? '')),
        'sample_variables' => $sampleVariables,
    ];
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        jsonErrorResponse('Method not allowed', 405);
    }

    if (!isValidCsrfToken()) {
        jsonErrorResponse((string)(__('userGroup_csrf_invalid') ?: 'CSRF token tidak sah.'), 403);
    }

    $pdo = Database::pdoMysql();
    ensureAjaxGroupManagePermission($pdo);

    $payload = emailTemplateTestSendPayload();
    if ($payload['test_email'] === '' || !filter_var($payload['test_email'], FILTER_VALIDATE_EMAIL)) {
        jsonErrorResponse((string)(__('emailTemplate_error_test_email_invalid') ?: 'Alamat emel ujian tidak sah.'), 422);
    }

    if ($payload['subject_template'] === '' || $payload['body_html'] === '') {
        jsonErrorResponse((string)(__('emailTemplate_error_preview_required') ?: 'Subjek dan kandungan HTML diperlukan untuk preview.'), 422);
    }

    $placeholderModel = new EmailPlaceholder($pdo);
    $renderService = new EmailTemplateRenderService($placeholderModel);
    $rendered = $renderService->renderTemplate([
        'subject_template' => $payload['subject_template'],
        'body_html' => $payload['body_html'],
        'body_text' => $payload['body_text'],
    ], (array)$payload['sample_variables'], [
        'recipient_email' => $payload['test_email'],
    ]);

    $mailer = Mailer::fromConfig($pdo);
    $sent = $mailer->send(
        $payload['test_email'],
        (string)($rendered['subject'] ?? ''),
        (string)($rendered['html'] ?? ''),
        (string)($rendered['text'] ?? '')
    );

    if (!$sent) {
        jsonErrorResponse($mailer->getLastError() ?: (string)(__('emailTemplate_error_test_send_failed') ?: 'Emel ujian tidak berjaya dihantar.'), 500);
    }

    jsonSuccessResponse([
        'message' => (string)(__('emailTemplate_test_send_success') ?: 'Emel ujian berjaya dihantar.'),
    ]);
} catch (InvalidArgumentException $e) {
    jsonErrorResponse($e->getMessage(), 422);
} catch (Throwable $e) {
    error_log('[email-template-test-send] ' . $e->getMessage());
    jsonErrorResponse((string)(__('emailTemplate_error_test_send_failed') ?: 'Emel ujian tidak berjaya dihantar.'), 500);
}
