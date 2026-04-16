<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_login();
require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/EmailPlaceholder.php';
require_once __DIR__ . '/../classes/EmailTemplateRenderService.php';

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
function emailTemplatePreviewPayload(): array
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

    $payload = emailTemplatePreviewPayload();
    if ($payload['subject_template'] === '' || $payload['body_html'] === '') {
        jsonErrorResponse((string)(__('emailTemplate_error_preview_required') ?: 'Subjek dan kandungan HTML diperlukan untuk preview.'), 422);
    }

    $placeholderModel = new EmailPlaceholder($pdo);
    $renderService = new EmailTemplateRenderService($placeholderModel);
    $rendered = $renderService->renderTemplate([
        'subject_template' => $payload['subject_template'],
        'body_html' => $payload['body_html'],
        'body_text' => $payload['body_text'],
    ], (array)$payload['sample_variables']);

    jsonSuccessResponse([
        'message' => (string)(__('emailTemplate_preview_success') ?: 'Preview template berjaya dijana.'),
        'preview' => [
            'subject' => (string)($rendered['subject'] ?? ''),
            'html' => (string)($rendered['html'] ?? ''),
            'text' => (string)($rendered['text'] ?? ''),
            'raw_html' => (string)($rendered['raw_html'] ?? ''),
            'used_placeholders' => array_values((array)($rendered['used_placeholders'] ?? [])),
            'invalid_placeholders' => array_values((array)($rendered['invalid_placeholders'] ?? [])),
            'missing_placeholders' => array_values((array)($rendered['missing_placeholders'] ?? [])),
        ],
    ]);
} catch (InvalidArgumentException $e) {
    jsonErrorResponse($e->getMessage(), 422);
} catch (Throwable $e) {
    error_log('[email-template-preview] ' . $e->getMessage());
    jsonErrorResponse((string)(__('emailTemplate_error_preview_failed') ?: 'Ralat sistem semasa menjana preview template emel.'), 500);
}
