<?php
declare(strict_types=1);

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/EmailTemplate.php';
require_once __DIR__ . '/../classes/EmailPlaceholder.php';
require_once __DIR__ . '/../classes/EmailTemplateRenderService.php';
require_once __DIR__ . '/../classes/Mailer.php';
require_once __DIR__ . '/../includes/functions-db.php';

class BdrDistanceController
{
    protected const BULK_EMAIL_JOB_TABLE = 'tbl_bdr_email_bulk_job';
    protected const BULK_EMAIL_ITEM_TABLE = 'tbl_bdr_email_bulk_job_item';
    protected const BULK_EMAIL_EXCLUSION_TABLE = 'tbl_bdr_email_notification_exclusion';
    protected const STAFF_SITE_TABLE = 'tbl_bdr_staff_site';
    protected const BULK_EMAIL_BATCH_SIZE = 2;
    protected const BULK_EMAIL_DELAY_US = 1200000;
    protected const NOTIFY_TOO_CLOSE_KM = 1.0;
    protected const NOTIFY_TOO_FAR_KM = 100.0;
    protected const SELF_CONFIRM_TOKEN_TTL_DAYS = 30;
    public string $siteCode = 'upnm_kampus';
    public string $officeLabel = 'Kampus UPNM';
    public string $officeAddress = '';
    public array $officeCoords = [];
    public array $siteOptions = [];
    public string $lang = 'ms';
    public array $profile = [];
    public array $rows = [];
    public array $departmentOptions = [];
    public string $currentDepartment = '';
    public string $selectedDepartmentValue = '';
    protected string $selectedDepartment = '';
    protected PDO $pdoMysql;
    protected ?array $lastApiLookupFailure = null;

    protected function debugLogEnabled(string $envKey): bool
    {
        $value = $_ENV[$envKey] ?? getenv($envKey);
        if (is_string($value) && trim($value) !== '') {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }

    protected function appendDebugLog(string $filename, array $context): void
    {
        $dir = realpath(__DIR__ . '/../log') ?: (__DIR__ . '/../log');
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $context['timestamp'] = $context['timestamp'] ?? date('c');
        @file_put_contents(
            rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename,
            json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    protected function debugDistanceHydration(array $context): void
    {
        if (!$this->debugLogEnabled('BDR_DEBUG_LOGS')) {
            return;
        }

        $line = '[' . date('Y-m-d H:i:s') . '] ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        $dir = realpath(__DIR__ . '/../log') ?: (__DIR__ . '/../log');
        @file_put_contents(rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'bdr-distance-hydration.log', $line, FILE_APPEND | LOCK_EX);
    }

    protected function debugDistanceHydrationRowSnapshot(array $context): void
    {
        if (!$this->debugLogEnabled('BDR_DEBUG_LOGS')) {
            return;
        }

        $this->appendDebugLog('bdr-distance-hydration-row.log', $context);
    }

    protected function debugApiLookup(array $context): void
    {
        if (!$this->debugLogEnabled('BDR_DEBUG_LOGS')) {
            return;
        }

        $this->appendDebugLog('bdr-distance-api-debug.log', $context);
    }

    protected function debugStaffSiteSync(array $context): void
    {
        if (!$this->debugLogEnabled('BDR_DEBUG_LOGS')) {
            return;
        }

        $context['site_code'] = $context['site_code'] ?? $this->siteCode;
        $this->appendDebugLog('bdr-staff-site-sync.log', $context);
    }

    public function __construct(bool $skipRowLoad = false)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->lang = $_SESSION['lang'] ?? 'ms';
        $this->siteOptions = distance_site_registry();
        $this->setActiveSiteContext($this->resolveSiteCode());
        $this->pdoMysql = Database::getInstance('mysql')->getConnection();
        $this->profile = $this->loadProfile();
        $this->currentDepartment = $this->resolveCurrentDepartment();
        $this->selectedDepartment = $this->resolveSelectedDepartment();
        $this->selectedDepartmentValue = $this->selectedDepartment;
        $this->applyUserTheme();
        if (!$skipRowLoad) {
            $this->loadRows();
        }
    }

    protected function resolveSiteCode(): string
    {
        $raw = trim((string)($_REQUEST['site'] ?? $_SESSION['bdr_distance_site'] ?? ''));
        $siteCode = distance_normalize_site_code($raw);
        $_SESSION['bdr_distance_site'] = $siteCode;

        return $siteCode;
    }

    protected function applySiteContext(string $siteCode, bool $persistToSession = true): void
    {
        $this->siteCode = distance_normalize_site_code($siteCode);
        $this->officeLabel = distance_office_label($this->siteCode);
        $this->officeAddress = distance_office_address($this->siteCode);
        $this->officeCoords = distance_office_coords($this->siteCode);
        if ($persistToSession) {
            $_SESSION['bdr_distance_site'] = $this->siteCode;
        }
    }

    protected function setActiveSiteContext(string $siteCode): void
    {
        $this->applySiteContext($siteCode, true);
    }

    protected function loadProfile(): array
    {
        $userModel = new User($this->pdoMysql);
        $fStafID = $_SESSION['f_stafID'] ?? null;

        return $fStafID ? ($userModel->getProfile((string)$fStafID) ?: []) : [];
    }

    protected function applyUserTheme(): void
    {
        $settingJson = $this->profile['f_themeSetting'] ?? '{}';
        $themeSetting = json_decode((string)$settingJson, true);
        if (!is_array($themeSetting)) {
            $themeSetting = [];
        }

        $_SESSION['theme.menu'] = $themeSetting['sidebarColor'] ?? ($_SESSION['theme.menu'] ?? 'light');
        $_SESSION['theme.topbar'] = $themeSetting['topbarColor'] ?? ($_SESSION['theme.topbar'] ?? 'light');
        $_SESSION['theme.layout'] = $themeSetting['layoutMode'] ?? ($_SESSION['theme.layout'] ?? 'light');
    }

    protected function getQueryString(string $key, string $default = ''): string
    {
        return trim((string)($_GET[$key] ?? $default));
    }

    protected function getQueryInt(string $key, int $default = 0): int
    {
        $value = $_GET[$key] ?? $default;
        return is_numeric($value) ? (int)$value : $default;
    }

    protected function isAjaxRequest(): bool
    {
        $requestedWith = strtolower(trim((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')));
        $accept = strtolower(trim((string)($_SERVER['HTTP_ACCEPT'] ?? '')));

        return $requestedWith === 'xmlhttprequest' || str_contains($accept, 'application/json');
    }

    protected function jsonSuccess(array $payload = [], int $status = 200): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=UTF-8');
        }

        echo json_encode(array_merge(['success' => true], $payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function jsonError(string $message, int $status = 400, array $extra = []): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=UTF-8');
        }

        echo json_encode(array_merge([
            'success' => false,
            'message' => $message,
        ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function debugBulkEmail(string $event, array $context = []): void
    {
        if (!$this->debugLogEnabled('BDR_DEBUG_LOGS')) {
            return;
        }

        $payload = array_merge([
            'event' => $event,
            'timestamp' => date('c'),
            'method' => (string)($_SERVER['REQUEST_METHOD'] ?? ''),
            'uri' => (string)($_SERVER['REQUEST_URI'] ?? ''),
            'remote_addr' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            'content_type' => (string)($_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '')),
            'accept' => (string)($_SERVER['HTTP_ACCEPT'] ?? ''),
            'x_requested_with' => (string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''),
            'login_id' => (string)($_SESSION['f_loginID'] ?? ''),
            'staff_id' => (string)($_SESSION['f_stafID'] ?? ''),
        ], $context);

        $this->appendDebugLog('bdr-bulk-email-debug.log', $payload);
    }

    protected function debugEmailReminder(string $event, array $context = []): void
    {
        if (!$this->debugLogEnabled('BDR_DEBUG_LOGS')) {
            return;
        }

        $payload = array_merge([
            'event' => $event,
            'timestamp' => date('c'),
            'method' => (string)($_SERVER['REQUEST_METHOD'] ?? ''),
            'uri' => (string)($_SERVER['REQUEST_URI'] ?? ''),
            'remote_addr' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            'login_id' => (string)($_SESSION['f_loginID'] ?? ''),
            'staff_id' => (string)($_SESSION['f_stafID'] ?? ''),
        ], $context);

        $this->appendDebugLog('bdr-email-reminder-debug.log', $payload);
    }

    public function setLang(string $lang): void
    {
        $this->lang = trim($lang) !== '' ? trim($lang) : 'ms';
        $_SESSION['lang'] = $this->lang;
    }

    public function handleDistanceLookup(): void
    {
        $address = trim((string)($_POST['address'] ?? ''));
        $status = strtoupper(trim((string)($_POST['address_status'] ?? '')));
        $force = trim((string)($_POST['force'] ?? '')) === '1';
        $addressData = [
            'alamat1' => trim((string)($_POST['alamat1'] ?? '')),
            'alamat2' => trim((string)($_POST['alamat2'] ?? '')),
            'alamat3' => trim((string)($_POST['alamat3'] ?? '')),
            'poskod' => trim((string)($_POST['poskod'] ?? '')),
            'negeri' => trim((string)($_POST['negeri'] ?? '')),
            'negara' => trim((string)($_POST['negara'] ?? '')),
        ];
        $staffMeta = [
            'f_stafID' => trim((string)($_POST['f_stafID'] ?? $_POST['staff_no'] ?? '')),
            'site_code' => $this->siteCode,
        ];
        $hasStructuredAddress = implode('', $addressData) !== '';
        $lookupInput = $hasStructuredAddress ? $addressData : $address;
        $comparisonAddress = $this->resolveAddressForMatchComparison($address, $addressData);

        if ($address === '') {
            $this->jsonSuccess([
                'distance_km' => null,
                'distance_label' => '',
                'distance_source' => '',
                'route_provider' => '',
                'route_points' => [],
                'direction_url' => '',
                'matched_query' => '',
                'home_coords_label' => '',
                'office_coords_label' => $this->formatCoordsLabel($this->officeCoords),
                'match_quality' => 'NONE',
                'cache_origin' => 'none',
                'debug_reason' => 'address_empty',
                'message' => 'empty_address',
            ]);
        }

        if ($status === 'EMPTY') {
            $this->jsonSuccess([
                'distance_km' => null,
                'distance_label' => '',
                'distance_source' => '',
                'route_provider' => '',
                'route_points' => [],
                'direction_url' => generate_direction_url($address, $this->officeAddress),
                'matched_query' => '',
                'home_coords_label' => '',
                'office_coords_label' => $this->formatCoordsLabel($this->officeCoords),
                'match_quality' => 'NONE',
                'cache_origin' => 'none',
                'debug_reason' => 'address_status_empty',
                'message' => 'empty_address',
            ]);
        }

        if ($status === 'INVALID') {
            $this->jsonSuccess([
                'distance_km' => null,
                'distance_label' => '',
                'distance_source' => '',
                'route_provider' => '',
                'route_points' => [],
                'direction_url' => generate_direction_url($address, $this->officeAddress),
                'matched_query' => '',
                'home_coords_label' => '',
                'office_coords_label' => $this->formatCoordsLabel($this->officeCoords),
                'match_quality' => 'LOW',
                'cache_origin' => 'none',
                'debug_reason' => 'address_status_invalid',
                'message' => 'invalid_address',
            ]);
        }

        $distanceData = distance_lookup_cached_result($lookupInput, $this->officeAddress, $force, $staffMeta);
        $distanceKm = is_numeric($distanceData['km'] ?? null) ? (float)$distanceData['km'] : null;
        $homeCoords = is_array($distanceData['home_coords'] ?? null) ? $distanceData['home_coords'] : null;
        $officeCoords = is_array($distanceData['office_coords'] ?? null) ? $distanceData['office_coords'] : $this->officeCoords;
        $matchedQuery = trim((string)($homeCoords['matched_query'] ?? $address));
        $qualityTarget = trim((string)($homeCoords['display_name'] ?? $matchedQuery));
        $matchQuality = $this->assessMatchQuality($comparisonAddress, $qualityTarget);
        $distanceLabel = format_distance_km($distanceKm);
        $distanceSource = (string)($distanceData['source'] ?? '');
        $routeProvider = (string)($distanceData['route_provider'] ?? '');
        $routePoints = is_array($distanceData['route_points'] ?? null) ? $distanceData['route_points'] : [];
        $debugReason = '';

        if (!is_array($homeCoords)) {
            $debugReason = 'geocode_failed';
        } elseif ($distanceKm === null) {
            $debugReason = 'route_failed';
        } elseif ($matchQuality === 'LOW') {
            $debugReason = 'low_match_quality';
        } else {
            $debugReason = $this->resolveMatchDebugReason($comparisonAddress, $qualityTarget, $matchQuality);
        }

        if ($matchQuality === 'LOW') {
            $distanceKm = null;
            $distanceLabel = $this->tr('bdr_distance_status_review_location', 'Semak Lokasi');
            $distanceSource = '';
            $routeProvider = '';
            $routePoints = [];
        }

        $this->jsonSuccess([
            'distance_km' => $distanceKm,
            'distance_label' => $distanceLabel,
            'distance_source' => $distanceSource,
            'route_provider' => $routeProvider,
            'route_points' => $routePoints,
            'direction_url' => generate_direction_url($address, $this->officeAddress),
            'matched_query' => $matchedQuery,
            'provider_display_name' => $qualityTarget,
            'home_coords_label' => $this->formatCoordsLabel($homeCoords),
            'office_coords_label' => $this->formatCoordsLabel($officeCoords),
            'match_quality' => $matchQuality,
            'is_review_location' => $matchQuality === 'LOW',
            'cache_origin' => (string)($distanceData['cache_origin'] ?? 'none'),
            'debug_reason' => $debugReason,
            'message' => $matchQuality === 'LOW'
                ? 'low_match_quality'
                : ($distanceKm === null ? 'lookup_unavailable' : 'ok'),
        ]);
    }

    public function handleEmailReminderSend(): void
    {
        $payload = $this->readEmailReminderPayload();
        $this->debugEmailReminder('send_enter', [
            'recipient_email' => (string)($payload['recipient_email'] ?? ''),
            'staff_no' => (string)($payload['variables']['staff_no'] ?? ''),
        ]);
        if ($payload['recipient_email'] === '' || !filter_var($payload['recipient_email'], FILTER_VALIDATE_EMAIL)) {
            $this->jsonError($this->tr('bdr_distance_email_invalid_email', 'Alamat emel staf tidak sah.'), 422);
        }

        try {
            $delivery = $this->resolveReminderDeliveryRecipient($payload['recipient_email']);
            $this->debugEmailReminder('delivery_resolved', [
                'recipient_email' => (string)($payload['recipient_email'] ?? ''),
                'delivery_email' => $delivery['delivery_email'],
                'delivery_mode' => $delivery['mode'],
                'test_recipient' => $delivery['test_recipient'],
            ]);
            if ($delivery['delivery_email'] === '' || !filter_var($delivery['delivery_email'], FILTER_VALIDATE_EMAIL)) {
                $this->jsonError($this->tr('bdr_distance_email_invalid_email', 'Alamat emel staf tidak sah.'), 422);
            }

            $template = $this->resolveEmailReminderTemplate();
            $placeholderModel = new EmailPlaceholder($this->pdoMysql);
            $renderService = new EmailTemplateRenderService($placeholderModel);
            $rendered = $renderService->renderTemplate($template, $payload['variables'], $payload['context']);

            $mailer = Mailer::fromConfig($this->pdoMysql);
            $sent = $mailer->send(
                $delivery['delivery_email'],
                (string)($rendered['subject'] ?? ''),
                (string)($rendered['html'] ?? ''),
                (string)($rendered['text'] ?? '')
            );

            if (!$sent) {
                throw new RuntimeException($mailer->getLastError() ?: 'Email could not be sent.');
            }

            $this->debugEmailReminder('send_success', [
                'recipient_email' => (string)($payload['recipient_email'] ?? ''),
                'delivery_email' => $delivery['delivery_email'],
                'delivery_mode' => $delivery['mode'],
            ]);

            $this->jsonSuccess([
                'message' => $this->tr('bdr_distance_email_send_success', 'Emel reminder berjaya dihantar.'),
                'sent_to' => $delivery['delivery_email'],
                'delivery_mode' => $delivery['mode'],
                'original_recipient' => $payload['recipient_email'],
            ]);
        } catch (Throwable $e) {
            $this->debugEmailReminder('send_exception', [
                'recipient_email' => (string)($payload['recipient_email'] ?? ''),
                'message' => $e->getMessage(),
                'class' => get_class($e),
            ]);
            error_log('[BdrDistanceController][email_reminder_send] ' . $e->getMessage());
            $this->jsonError($this->tr('bdr_distance_email_send_failed', 'Emel reminder gagal dihantar.'), 500);
        }
    }

    public function handleBulkEmailStart(): void
    {
        $this->assertValidCsrf();
        $this->debugBulkEmail('start_enter');

        try {
            $raw = file_get_contents('php://input');
            $decoded = is_string($raw) && trim($raw) !== '' ? json_decode($raw, true) : null;
            $department = $this->normalizeBulkEmailDepartment(
                is_array($decoded) ? (string)($decoded['department'] ?? '') : (string)($_POST['department'] ?? '')
            );
            $selectedIssueTypes = $this->normalizeBulkEmailIssueTypes(
                is_array($decoded) ? ($decoded['issue_types'] ?? []) : ($_POST['issue_types'] ?? [])
            );
            $this->debugBulkEmail('start_payload_parsed', [
                'department' => $department,
                'issue_types' => $selectedIssueTypes,
                'raw_length' => is_string($raw) ? strlen($raw) : 0,
                'json_valid' => is_array($decoded),
            ]);

            $this->selectedDepartment = $department;
            $this->selectedDepartmentValue = $department;
            $this->loadRows();

            $this->ensureBulkEmailTables();
            $entries = $this->buildBulkEmailEntriesFromRows($this->rows, $selectedIssueTypes);
            $this->debugBulkEmail('start_entries_loaded', [
                'entry_count' => count($entries),
                'rows_count' => count($this->rows),
            ]);
            if ($entries === []) {
                $this->jsonError($this->tr('bdr_distance_bulk_email_empty', 'Tiada rekod layak untuk dihantar.'), 422);
            }

            $userId = trim((string)($_SESSION['f_stafID'] ?? $_SESSION['f_staffID'] ?? $_SESSION['user_id'] ?? ''));
            $userName = trim((string)($_SESSION['f_name'] ?? $_SESSION['name'] ?? $_SESSION['nama'] ?? ''));
            $delivery = $this->resolveReminderDeliveryRecipient('');
            $jobToken = bin2hex(random_bytes(16));
            $now = date('Y-m-d H:i:s');

            $this->pdoMysql->beginTransaction();

            $stmtJob = $this->pdoMysql->prepare(
                'INSERT INTO ' . self::BULK_EMAIL_JOB_TABLE . '
                (f_jobToken, f_status, f_siteCode, f_scopeDepartment, f_totalCount, f_processedCount, f_successCount, f_failedCount, f_skippedCount, f_deliveryMode, f_testRecipient, f_createdBy, f_createdName, f_lastMessage, f_createdAt, f_updatedAt)
                VALUES
                (:job_token, :status, :site_code, :scope_department, :total_count, 0, 0, 0, 0, :delivery_mode, :test_recipient, :created_by, :created_name, :last_message, :created_at, :updated_at)'
            );
            $stmtJob->execute([
                ':job_token' => $jobToken,
                ':status' => 'QUEUED',
                ':site_code' => $this->siteCode,
                ':scope_department' => $department,
                ':total_count' => count($entries),
                ':delivery_mode' => $delivery['mode'],
                ':test_recipient' => $delivery['test_recipient'],
                ':created_by' => $userId,
                ':created_name' => $userName,
                ':last_message' => $this->tr('bdr_distance_bulk_email_status_queued', 'Bulk email job queued.'),
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
            $jobId = (int)$this->pdoMysql->lastInsertId();

            $stmtItem = $this->pdoMysql->prepare(
                'INSERT INTO ' . self::BULK_EMAIL_ITEM_TABLE . '
                (f_jobId, f_siteCode, f_staffNo, f_staffName, f_recipientEmail, f_deliveryEmail, f_department, f_mailingAddress, f_distanceLabel, f_issueLabel, f_issueReason, f_selfConfirmHtml, f_selfConfirmText, f_status, f_attemptCount, f_createdAt, f_updatedAt)
                VALUES
                (:job_id, :site_code, :staff_no, :staff_name, :recipient_email, :delivery_email, :department, :mailing_address, :distance_label, :issue_label, :issue_reason, :self_confirm_html, :self_confirm_text, :status, 0, :created_at, :updated_at)'
            );

            foreach ($entries as $entry) {
                $itemDelivery = $this->resolveReminderDeliveryRecipient((string)$entry['recipient_email']);
                $stmtItem->execute([
                    ':job_id' => $jobId,
                    ':site_code' => (string)($entry['site_code'] ?? $this->siteCode),
                    ':staff_no' => (string)$entry['staff_no'],
                    ':staff_name' => (string)$entry['staff_name'],
                    ':recipient_email' => (string)$entry['recipient_email'],
                    ':delivery_email' => (string)$itemDelivery['delivery_email'],
                    ':department' => (string)$entry['department'],
                    ':mailing_address' => (string)$entry['mailing_address'],
                    ':distance_label' => (string)$entry['distance_label'],
                    ':issue_label' => (string)$entry['issue_label'],
                    ':issue_reason' => (string)$entry['issue_reason'],
                    ':self_confirm_html' => (string)($entry['self_confirm_html'] ?? ''),
                    ':self_confirm_text' => (string)($entry['self_confirm_text'] ?? ''),
                    ':status' => 'PENDING',
                    ':created_at' => $now,
                    ':updated_at' => $now,
                ]);
            }

            $this->pdoMysql->commit();
            $job = $this->getBulkEmailJobById($jobId);
            if (!is_array($job)) {
                throw new RuntimeException('Bulk email job record not found after creation.');
            }

            $this->jsonSuccess([
                'message' => $this->tr('bdr_distance_bulk_email_start_success', 'Bulk email job berjaya diwujudkan.'),
                'job' => $this->buildBulkEmailJobSummary($job),
            ]);
        } catch (Throwable $e) {
            $this->debugBulkEmail('start_exception', [
                'message' => $e->getMessage(),
                'class' => get_class($e),
            ]);
            if ($this->pdoMysql->inTransaction()) {
                $this->pdoMysql->rollBack();
            }
            error_log('[BdrDistanceController][bulk_email_start] ' . $e->getMessage());
            $this->jsonError($this->tr('bdr_distance_bulk_email_start_failed', 'Bulk email job gagal dimulakan.'), 500);
        }
    }

    public function handleBulkEmailStatus(): void
    {
        $this->assertValidCsrf();
        $this->debugBulkEmail('status_enter', [
            'job_id' => (int)($_POST['job_id'] ?? 0),
        ]);
        try {
            $this->ensureBulkEmailTables();
            $jobId = (int)($_POST['job_id'] ?? 0);
            if ($jobId <= 0) {
                $this->jsonError($this->tr('bdr_distance_bulk_email_invalid_job', 'Rujukan bulk email job tidak sah.'), 422);
            }

            $job = $this->getBulkEmailJobById($jobId);
            if (!is_array($job)) {
                $this->jsonError($this->tr('bdr_distance_bulk_email_job_not_found', 'Bulk email job tidak ditemui.'), 404);
            }

            $this->jsonSuccess([
                'job' => $this->buildBulkEmailJobSummary($job),
            ]);
        } catch (Throwable $e) {
            $this->debugBulkEmail('status_exception', [
                'message' => $e->getMessage(),
                'class' => get_class($e),
            ]);
            error_log('[BdrDistanceController][bulk_email_status] ' . $e->getMessage());
            $this->jsonError($this->tr('bdr_distance_bulk_email_status_failed', 'Status bulk email tidak dapat diperoleh.'), 500);
        }
    }

    public function handleBulkEmailProcess(): void
    {
        $this->assertValidCsrf();
        $this->debugBulkEmail('process_enter', [
            'job_id' => (int)($_POST['job_id'] ?? 0),
        ]);

        try {
            $this->ensureBulkEmailTables();
            $jobId = (int)($_POST['job_id'] ?? 0);
            if ($jobId <= 0) {
                $this->jsonError($this->tr('bdr_distance_bulk_email_invalid_job', 'Rujukan bulk email job tidak sah.'), 422);
            }

            $job = $this->getBulkEmailJobById($jobId);
            if (!is_array($job)) {
                $this->jsonError($this->tr('bdr_distance_bulk_email_job_not_found', 'Bulk email job tidak ditemui.'), 404);
            }

            $processedInBatch = $this->processBulkEmailJobBatch($jobId);
            $job = $this->getBulkEmailJobById($jobId);
            if (!is_array($job)) {
                throw new RuntimeException('Bulk email job record missing after processing.');
            }

            $this->jsonSuccess([
                'message' => $this->tr('bdr_distance_bulk_email_process_success', 'Bulk email batch telah diproses.'),
                'processed_in_batch' => $processedInBatch,
                'job' => $this->buildBulkEmailJobSummary($job),
            ]);
        } catch (Throwable $e) {
            $this->debugBulkEmail('process_exception', [
                'message' => $e->getMessage(),
                'class' => get_class($e),
            ]);
            error_log('[BdrDistanceController][bulk_email_process] ' . $e->getMessage());
            $this->jsonError($this->tr('bdr_distance_bulk_email_process_failed', 'Pemprosesan bulk email gagal.'), 500);
        }
    }

    public function handleBulkEmailExclusionSave(): void
    {
        $this->assertValidCsrf();

        try {
            $this->ensureBulkEmailTables();
            $raw = file_get_contents('php://input');
            $decoded = is_string($raw) && trim($raw) !== '' ? json_decode($raw, true) : null;
            $payload = is_array($decoded) ? $decoded : $_POST;

            $staffNo = trim((string)($payload['staff_no'] ?? ''));
            $addressHash = trim((string)($payload['address_hash'] ?? ''));
            $selectedExcludedTypes = $this->normalizeBulkEmailIssueTypes($payload['excluded_issue_types'] ?? []);
            $allIssueTypes = $this->normalizeBulkEmailIssueTypes($payload['all_issue_types'] ?? []);
            $reason = trim((string)($payload['reason'] ?? ''));
            $notes = trim((string)($payload['notes'] ?? ''));

            if ($staffNo === '' || $addressHash === '' || $allIssueTypes === []) {
                $this->jsonError($this->tr('bdr_distance_bulk_email_invalid_exclusion', 'Maklumat pengecualian notifikasi tidak sah.'), 422);
            }

            $userId = trim((string)($_SESSION['f_stafID'] ?? $_SESSION['f_staffID'] ?? $_SESSION['user_id'] ?? ''));
            $userName = trim((string)($_SESSION['f_name'] ?? $_SESSION['name'] ?? $_SESSION['nama'] ?? ''));
            $now = date('Y-m-d H:i:s');
            $stmt = $this->pdoMysql->prepare(
                'INSERT INTO ' . self::BULK_EMAIL_EXCLUSION_TABLE . '
                (f_staffNo, f_siteCode, f_addressHash, f_issueType, f_reason, f_notes, f_isActive, f_source, f_confirmToken, f_tokenExpiresAt, f_confirmedAt, f_confirmedIp, f_confirmedUserAgent, f_confirmedByStaffNo, f_createdBy, f_createdName, f_createdAt, f_updatedAt)
                VALUES
                (:staff_no, :site_code, :address_hash, :issue_type, :reason, :notes, :is_active, :source, NULL, NULL, NULL, NULL, NULL, NULL, :created_by, :created_name, :created_at, :updated_at)
                ON DUPLICATE KEY UPDATE
                    f_reason = VALUES(f_reason),
                    f_notes = VALUES(f_notes),
                    f_isActive = VALUES(f_isActive),
                    f_source = VALUES(f_source),
                    f_confirmToken = VALUES(f_confirmToken),
                    f_tokenExpiresAt = VALUES(f_tokenExpiresAt),
                    f_confirmedAt = VALUES(f_confirmedAt),
                    f_confirmedIp = VALUES(f_confirmedIp),
                    f_confirmedUserAgent = VALUES(f_confirmedUserAgent),
                    f_confirmedByStaffNo = VALUES(f_confirmedByStaffNo),
                    f_updatedAt = VALUES(f_updatedAt)'
            );

            foreach ($allIssueTypes as $issueType) {
                $stmt->execute([
                    ':staff_no' => $staffNo,
                    ':site_code' => $this->siteCode,
                    ':address_hash' => $addressHash,
                    ':issue_type' => $issueType,
                    ':reason' => $reason !== '' ? $reason : null,
                    ':notes' => $notes !== '' ? $notes : null,
                    ':is_active' => in_array($issueType, $selectedExcludedTypes, true) ? 1 : 0,
                    ':source' => 'ADMIN',
                    ':created_by' => $userId !== '' ? $userId : null,
                    ':created_name' => $userName !== '' ? $userName : null,
                    ':created_at' => $now,
                    ':updated_at' => $now,
                ]);
            }

            $this->jsonSuccess([
                'message' => $this->tr('bdr_distance_bulk_email_exclusion_saved', 'Pengecualian notifikasi berjaya dikemas kini.'),
                'excluded_issue_types' => $selectedExcludedTypes,
            ]);
        } catch (Throwable $e) {
            error_log('[BdrDistanceController][bulk_email_exclusion_save] ' . $e->getMessage());
            $this->jsonError($this->tr('bdr_distance_bulk_email_exclusion_failed', 'Pengecualian notifikasi gagal disimpan.'), 500);
        }
    }

    public function handleDistancePrefetch(): void
    {
        $entries = $this->readDistancePrefetchEntries();
        if ($entries === []) {
            $this->jsonSuccess([
                'items' => [],
            ]);
        }

        $items = [];
        foreach ($entries as $entry) {
            $address = trim((string)($entry['address'] ?? ''));
            $addressStatus = strtoupper(trim((string)($entry['address_status'] ?? '')));
            $rowKey = trim((string)($entry['row_key'] ?? ''));
            $addressData = [
                'alamat1' => trim((string)($entry['alamat1'] ?? '')),
                'alamat2' => trim((string)($entry['alamat2'] ?? '')),
                'alamat3' => trim((string)($entry['alamat3'] ?? '')),
                'poskod' => trim((string)($entry['poskod'] ?? '')),
                'negeri' => trim((string)($entry['negeri'] ?? '')),
                'negara' => trim((string)($entry['negara'] ?? '')),
            ];
            $hasStructuredAddress = implode('', $addressData) !== '';
            $lookupInput = $hasStructuredAddress ? $addressData : $address;
            $comparisonAddress = $this->resolveAddressForMatchComparison($address, $addressData);

            if ($rowKey === '' || $address === '' || $addressStatus !== 'VALID') {
                continue;
            }

            $cached = distance_result_cache_get($lookupInput, $this->officeAddress, $this->siteCode);
            if (!is_array($cached)) {
                continue;
            }

            $distanceKm = is_numeric($cached['km'] ?? null) ? (float)$cached['km'] : null;
            $homeCoords = is_array($cached['home_coords'] ?? null) ? $cached['home_coords'] : null;
            $officeCoords = is_array($cached['office_coords'] ?? null) ? $cached['office_coords'] : $this->officeCoords;
            $matchedQuery = trim((string)($homeCoords['matched_query'] ?? $address));
            $qualityTarget = trim((string)($homeCoords['display_name'] ?? $matchedQuery));
            $matchQuality = $this->assessMatchQuality($comparisonAddress, $qualityTarget);
            $distanceSource = (string)($cached['source'] ?? '');
            $routeProvider = (string)($cached['route_provider'] ?? '');
            $routePoints = is_array($cached['route_points'] ?? null) ? $cached['route_points'] : [];
            $distanceLabel = format_distance_km($distanceKm);
            $debugReason = $this->resolveMatchDebugReason($comparisonAddress, $qualityTarget, $matchQuality, 'loaded_from_cache');

            if ($matchQuality === 'LOW') {
                $distanceKm = null;
                $distanceLabel = $this->tr('bdr_distance_status_review_location', 'Semak Lokasi');
                $distanceSource = '';
                $routeProvider = '';
                $routePoints = [];
                $debugReason = 'low_match_quality';
            } elseif ($distanceKm === null) {
                $debugReason = 'cached_failed_lookup';
            }

            $items[] = [
                'row_key' => $rowKey,
                'distance_km' => $distanceKm,
                'distance_label' => $distanceLabel,
                'distance_source' => $distanceSource,
                'route_provider' => $routeProvider,
                'route_points' => $routePoints,
                'direction_url' => generate_direction_url($address, $this->officeAddress),
                'matched_query' => $matchedQuery,
                'provider_display_name' => $qualityTarget,
                'home_coords_label' => $this->formatCoordsLabel($homeCoords),
                'office_coords_label' => $this->formatCoordsLabel($officeCoords),
                'match_quality' => $matchQuality,
                'is_review_location' => $matchQuality === 'LOW',
                'cache_origin' => 'db',
                'debug_reason' => $debugReason,
                'message' => $matchQuality === 'LOW'
                    ? 'low_match_quality'
                    : ($distanceKm === null ? 'lookup_unavailable' : 'ok'),
            ];
        }

        $this->jsonSuccess([
            'items' => $items,
        ]);
    }

    public function handleDistanceDetail(): void
    {
        $address = trim((string)($_POST['address'] ?? ''));
        $status = strtoupper(trim((string)($_POST['address_status'] ?? '')));
        $addressData = [
            'alamat1' => trim((string)($_POST['alamat1'] ?? '')),
            'alamat2' => trim((string)($_POST['alamat2'] ?? '')),
            'alamat3' => trim((string)($_POST['alamat3'] ?? '')),
            'poskod' => trim((string)($_POST['poskod'] ?? '')),
            'negeri' => trim((string)($_POST['negeri'] ?? '')),
            'negara' => trim((string)($_POST['negara'] ?? '')),
        ];
        $hasStructuredAddress = implode('', $addressData) !== '';
        $lookupInput = $hasStructuredAddress ? $addressData : $address;
        $comparisonAddress = $this->resolveAddressForMatchComparison($address, $addressData);

        if ($address === '' || $status !== 'VALID') {
            $this->jsonSuccess([
                'found' => false,
            ]);
        }

        $cached = distance_result_cache_get($lookupInput, $this->officeAddress, $this->siteCode);
        if (!is_array($cached)) {
            $this->jsonSuccess([
                'found' => false,
            ]);
        }

        $distanceKm = is_numeric($cached['km'] ?? null) ? (float)$cached['km'] : null;
        $homeCoords = is_array($cached['home_coords'] ?? null) ? $cached['home_coords'] : null;
        $officeCoords = is_array($cached['office_coords'] ?? null) ? $cached['office_coords'] : $this->officeCoords;
        $matchedQuery = trim((string)($homeCoords['matched_query'] ?? $address));
        $qualityTarget = trim((string)($homeCoords['display_name'] ?? $matchedQuery));
        $matchQuality = $this->assessMatchQuality($comparisonAddress, $qualityTarget);
        $distanceSource = (string)($cached['source'] ?? '');
        $routeProvider = (string)($cached['route_provider'] ?? '');
        $routePoints = is_array($cached['route_points'] ?? null) ? $cached['route_points'] : [];
        $distanceLabel = format_distance_km($distanceKm);
        $debugReason = $distanceKm === null
            ? 'cached_failed_lookup'
            : $this->resolveMatchDebugReason($comparisonAddress, $qualityTarget, $matchQuality, 'loaded_from_cache');

        if ($matchQuality === 'LOW') {
            $distanceKm = null;
            $distanceLabel = $this->tr('bdr_distance_status_review_location', 'Semak Lokasi');
            $distanceSource = '';
            $routeProvider = '';
            $routePoints = [];
            $debugReason = 'low_match_quality';
        }

        $this->jsonSuccess([
            'found' => true,
            'distance_km' => $distanceKm,
            'distance_label' => $distanceLabel,
            'distance_source' => $distanceSource,
            'route_provider' => $routeProvider,
            'route_points' => $routePoints,
            'direction_url' => generate_direction_url($address, $this->officeAddress),
            'matched_query' => $matchedQuery,
            'provider_display_name' => $qualityTarget,
            'home_coords_label' => $this->formatCoordsLabel($homeCoords),
            'office_coords_label' => $this->formatCoordsLabel($officeCoords),
            'match_quality' => $matchQuality,
            'is_review_location' => $matchQuality === 'LOW',
            'cache_origin' => 'db',
            'debug_reason' => $debugReason,
            'message' => $matchQuality === 'LOW'
                ? 'low_match_quality'
                : ($distanceKm === null ? 'lookup_unavailable' : 'ok'),
        ]);
    }

    public function handleRowsData(): void
    {
        $this->loadRows();

        $items = array_map(function (array $row): array {
            return $this->serializeRowForTable($row, false);
        }, $this->rows);

        $this->jsonSuccess([
            'items' => $items,
            'department_options' => $this->departmentOptions,
            'current_department' => $this->selectedDepartment,
            'site_code' => $this->siteCode,
            'site_options' => $this->siteOptions,
            'office_label' => $this->officeLabel,
            'office_address' => $this->officeAddress,
            'total' => count($items),
        ]);
    }

    public function handleRowSnapshot(): void
    {
        $staffNo = trim((string)($_POST['staff_no'] ?? $_POST['f_stafID'] ?? $_POST['staff_id'] ?? ''));
        if ($staffNo === '') {
            $this->jsonError($this->tr('bdr_distance_snapshot_staff_required', 'No. staf diperlukan untuk refresh rekod.'), 422);
        }

        $row = $this->loadHydratedCurrentRowByStaffNo($staffNo);
        if (!is_array($row)) {
            $this->jsonError($this->tr('bdr_distance_snapshot_row_not_found', 'Rekod staf tidak ditemui.'), 404);
        }

        $this->jsonSuccess([
            'item' => $this->serializeRowForTable($row, true),
            'site_code' => $this->siteCode,
            'office_label' => $this->officeLabel,
            'office_address' => $this->officeAddress,
            'generated_at' => date(DATE_ATOM),
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    protected function serializeRowForTable(array $row, bool $includeRoutePoints = false): array
    {
        $notificationIssues = is_array($row['notification_issues'] ?? null) ? $row['notification_issues'] : [];
        $serializedNotificationIssues = $includeRoutePoints
            ? $notificationIssues
            : array_values(array_map(static function (array $issue): array {
                return [
                    'type' => (string)($issue['type'] ?? ''),
                    'label' => (string)($issue['label'] ?? ''),
                    'excluded' => !empty($issue['excluded']),
                    'exclusion_source' => (string)($issue['exclusion_source'] ?? ''),
                ];
            }, $notificationIssues));

        return [
            'id' => (int)($row['id'] ?? 0),
            'name' => (string)($row['name'] ?? ''),
            'staff_no' => (string)($row['staff_no'] ?? ''),
            'email' => (string)($row['email'] ?? ''),
            'department' => (string)($row['department'] ?? ''),
            'department_code' => (string)($row['department_code'] ?? ''),
            'position' => (string)($row['position'] ?? ''),
            'site_code' => (string)($row['site_code'] ?? $this->siteCode),
            'office_label' => (string)($row['office_label'] ?? $this->officeLabel),
            'office_address' => (string)($row['office_address'] ?? $this->officeAddress),
            'address_status' => (string)($row['address_status'] ?? 'VALID'),
            'mailing_address' => (string)($row['mailing_address'] ?? ''),
            'map_url' => (string)($row['map_url'] ?? ''),
            'distance_km' => isset($row['distance_km']) && is_numeric($row['distance_km']) ? (float)$row['distance_km'] : null,
            'distance_label' => (string)($row['distance_label'] ?? ''),
            'distance_source' => (string)($row['distance_source'] ?? ''),
            'route_provider' => (string)($row['route_provider'] ?? ''),
            'route_points' => $includeRoutePoints && is_array($row['route_points'] ?? null) ? $row['route_points'] : [],
            'cache_origin' => (string)($row['cache_origin'] ?? 'none'),
            'details_hydrated' => $includeRoutePoints,
            'matched_query' => (string)($row['matched_query'] ?? ''),
            'provider_display_name' => (string)($row['provider_display_name'] ?? ($row['matched_query'] ?? '')),
            'home_coords_label' => (string)($row['home_coords_label'] ?? ''),
            'distance_attempted' => !empty($row['distance_attempted']),
            'direction_url' => (string)($row['direction_url'] ?? ''),
            'match_quality' => (string)($row['match_quality'] ?? ''),
            'debug_reason' => (string)($row['debug_reason'] ?? ''),
            'is_review_location' => !empty($row['is_review_location']),
            'is_address_changed' => !empty($row['is_address_changed']),
            'address_inputs' => is_array($row['address_inputs'] ?? null) ? $row['address_inputs'] : [],
            'show_email_reminder' => !empty($row['show_email_reminder']),
            'issue_type' => (string)($row['issue_type'] ?? 'none'),
            'issue_label' => (string)($row['issue_label'] ?? ''),
            'notification_address_hash' => (string)($row['notification_address_hash'] ?? ''),
            'selected_state' => (string)($row['selected_state'] ?? ''),
            'detected_state' => (string)($row['detected_state'] ?? ''),
            'notification_issues' => $serializedNotificationIssues,
            'active_notification_issues' => $includeRoutePoints && is_array($row['active_notification_issues'] ?? null) ? $row['active_notification_issues'] : [],
            'excluded_notification_issues' => $includeRoutePoints && is_array($row['excluded_notification_issues'] ?? null) ? $row['excluded_notification_issues'] : [],
            'confirmed_notification_issues' => $includeRoutePoints && is_array($row['confirmed_notification_issues'] ?? null) ? array_values(array_map('strval', $row['confirmed_notification_issues'])) : [],
        ];
    }

    public function handleApiSingleRecord(): void
    {
        $apiSiteCode = $this->resolveApiSiteCodeFromRequest();
        if ($apiSiteCode === null) {
            $this->jsonError('Parameter site tidak sah.', 422, [
                'valid_sites' => array_keys(distance_site_registry()),
            ]);
        }
        $this->setActiveSiteContext($apiSiteCode);

        $staffNo = trim((string)($_GET['staff_no'] ?? $_GET['staff_id'] ?? ''));
        if ($staffNo === '') {
            $this->jsonError('Parameter staff_no diperlukan.', 422);
        }

        $row = $this->loadSingleRowByStaffNo($staffNo);
        if (!is_array($row)) {
            if (($this->lastApiLookupFailure['type'] ?? '') === 'backend_unavailable') {
                $this->jsonError('Perkhidmatan data staf tidak tersedia buat sementara waktu.', 503);
            }
            $this->jsonError('Rekod staf tidak ditemui.', 404);
        }

        $issueType = $this->buildIssueTypeFromRow($row);
        $issueLabel = $this->buildIssueLabelFromType($issueType);
        $distanceState = $this->buildDistanceStateFromRow($row);
        $cacheDetail = $this->loadApiCacheDetailForRow($row);

        $this->jsonSuccess([
            'data' => [
                'staff_no' => (string)($row['staff_no'] ?? ''),
                'name' => (string)($row['name'] ?? ''),
                'department' => (string)($row['department'] ?? ''),
                'department_code' => (string)($row['department_code'] ?? ''),
                'position' => (string)($row['position'] ?? ''),
                'email' => (string)($row['email'] ?? ''),
                'site' => [
                    'site_code' => (string)($row['site_code'] ?? $this->siteCode),
                    'office_label' => (string)($row['office_label'] ?? $this->officeLabel),
                    'office_address' => (string)($row['office_address'] ?? $this->officeAddress),
                    'office_coords' => [
                        'lat' => (float)($this->officeCoords['lat'] ?? 0),
                        'lon' => (float)($this->officeCoords['lon'] ?? 0),
                    ],
                ],
                'address' => [
                    'alamat1' => (string)(($row['address_inputs']['alamat1'] ?? '') ?: ''),
                    'alamat2' => (string)(($row['address_inputs']['alamat2'] ?? '') ?: ''),
                    'alamat3' => (string)(($row['address_inputs']['alamat3'] ?? '') ?: ''),
                    'poskod' => (string)(($row['address_inputs']['poskod'] ?? '') ?: ''),
                    'negeri' => (string)(($row['address_inputs']['negeri'] ?? '') ?: ''),
                    'negara' => (string)(($row['address_inputs']['negara'] ?? '') ?: ''),
                    'standardized_address' => (string)($row['mailing_address'] ?? ''),
                    'status' => strtoupper(trim((string)($row['address_status'] ?? 'VALID'))),
                ],
                'distance' => [
                    'state' => $distanceState,
                    'km' => isset($row['distance_km']) && is_numeric($row['distance_km']) ? (float)$row['distance_km'] : null,
                    'label' => (string)($row['distance_label'] ?? ''),
                    'source' => (string)($cacheDetail['distance_source'] ?? $row['distance_source'] ?? ''),
                    'route_provider' => (string)($cacheDetail['route_provider'] ?? $row['route_provider'] ?? ''),
                    'direction_url' => (string)($cacheDetail['direction_url'] ?? $row['direction_url'] ?? ''),
                    'route_points' => is_array($cacheDetail['route_points'] ?? null) ? $cacheDetail['route_points'] : [],
                ],
                'matching' => [
                    'match_quality' => (string)($cacheDetail['match_quality'] ?? $row['match_quality'] ?? ''),
                    'matched_query' => (string)($cacheDetail['matched_query'] ?? $row['matched_query'] ?? ''),
                    'provider_display_name' => (string)($cacheDetail['provider_display_name'] ?? $row['provider_display_name'] ?? ''),
                    'home_coords_label' => (string)($cacheDetail['home_coords_label'] ?? $row['home_coords_label'] ?? ''),
                    'office_coords_label' => (string)($cacheDetail['office_coords_label'] ?? $row['office_coords_label'] ?? $this->formatCoordsLabel($this->officeCoords)),
                ],
                'issue' => [
                    'issue_type' => $issueType,
                    'issue_label' => $issueLabel,
                    'is_review_location' => !empty($row['is_review_location']),
                    'is_address_changed' => !empty($row['is_address_changed']),
                    'debug_reason' => (string)($cacheDetail['debug_reason'] ?? $row['debug_reason'] ?? ''),
                ],
                'cache' => [
                    'cache_origin' => (string)($cacheDetail['cache_origin'] ?? $row['cache_origin'] ?? 'none'),
                    'distance_attempted' => !empty($row['distance_attempted']),
                ],
                'generated_at' => date(DATE_ATOM),
            ],
        ]);
    }

    public function handleApiNearbyStaffRecords(): void
    {
        $thresholdKm = 8.0;
        $records = [];
        $cacheMap = [];
        $seenStaffIds = [];
        $sites = array_keys(distance_site_registry());

        foreach ($sites as $siteCode) {
            $sitePayload = $this->loadNearbyApiRowsForSite((string)$siteCode);
            $rows = is_array($sitePayload['rows'] ?? null) ? $sitePayload['rows'] : [];
            $cacheMap = is_array($sitePayload['cache_map'] ?? null) ? $sitePayload['cache_map'] : [];

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $distanceKm = isset($row['distance_km']) && is_numeric($row['distance_km'])
                    ? (float)$row['distance_km']
                    : null;
                if ($distanceKm === null || $distanceKm > $thresholdKm) {
                    continue;
                }

                $staffId = trim((string)($row['staff_no'] ?? ''));
                $name = trim((string)($row['name'] ?? ''));
                $address = trim((string)($row['mailing_address'] ?? ''));
                $cached = $this->resolveDistanceCacheRecordForRow($row, $cacheMap);
                $coords = $this->extractHomeCoordinatesFromCache($cached);

                if ($staffId === '' || $name === '' || $address === '') {
                    continue;
                }

                if ($coords['lat'] === null || $coords['lon'] === null) {
                    continue;
                }

                if (isset($seenStaffIds[$staffId])) {
                    $this->debugApiLookup([
                        'event' => 'nearby_duplicate_staff_id',
                        'staff_id' => $staffId,
                        'existing_site' => (string)$seenStaffIds[$staffId],
                        'duplicate_site' => (string)$siteCode,
                    ]);
                    continue;
                }

                $seenStaffIds[$staffId] = (string)$siteCode;

                $records[] = [
                    'staff_id' => $staffId,
                    'site' => (string)$siteCode,
                    'name' => $name,
                    'distance_km' => round($distanceKm, 2),
                    'address' => $address,
                    'longitude' => $coords['lon'],
                    'latitude' => $coords['lat'],
                ];
            }
        }

        usort($records, static function (array $left, array $right): int {
            $distanceCompare = ($left['distance_km'] <=> $right['distance_km']);
            if ($distanceCompare !== 0) {
                return $distanceCompare;
            }

            return strcmp((string)($left['staff_id'] ?? ''), (string)($right['staff_id'] ?? ''));
        });

        $this->jsonSuccess([
            'meta' => [
                'sites' => $sites,
                'threshold_km' => $thresholdKm,
                'total' => count($records),
                'generated_at' => date(DATE_ATOM),
            ],
            'data' => $records,
        ]);
    }

    protected function loadNearbyApiRowsForSite(string $siteCode): array
    {
        $originalSiteCode = $this->siteCode;
        $originalOfficeLabel = $this->officeLabel;
        $originalOfficeAddress = $this->officeAddress;
        $originalOfficeCoords = $this->officeCoords;
        $originalRows = $this->rows;
        $originalDepartmentOptions = $this->departmentOptions;

        $this->applySiteContext($siteCode, false);
        $this->loadRows(false);
        $rows = is_array($this->rows) ? $this->rows : [];
    $cacheMap = $this->loadDistanceCacheMap($rows);

        $this->siteCode = $originalSiteCode;
        $this->officeLabel = $originalOfficeLabel;
        $this->officeAddress = $originalOfficeAddress;
        $this->officeCoords = $originalOfficeCoords;
        $this->rows = $originalRows;
        $this->departmentOptions = $originalDepartmentOptions;

        return [
            'rows' => $rows,
            'cache_map' => $cacheMap,
        ];
    }

    protected function resolveApiSiteCodeFromRequest(): ?string
    {
        if (!array_key_exists('site', $_GET)) {
            return distance_default_site_code();
        }

        $raw = strtolower(trim((string)($_GET['site'] ?? '')));
        if ($raw === '') {
            return null;
        }

        return isset(distance_site_registry()[$raw]) ? $raw : null;
    }

    protected function resolveDistanceCacheRecordForRow(array $row, array $cacheMap): ?array
    {
        $staffNo = trim((string)($row['staff_no'] ?? ''));
        if ($staffNo !== '' && isset($cacheMap['staff:' . $staffNo]) && is_array($cacheMap['staff:' . $staffNo])) {
            return $cacheMap['staff:' . $staffNo];
        }

        $mailingAddress = trim((string)($row['mailing_address'] ?? ''));
        $addressInputs = is_array($row['address_inputs'] ?? null) ? $row['address_inputs'] : [];
        $currentAddressForKey = $mailingAddress;
        if ($addressInputs !== []) {
            $currentAddressForKey = function_exists('format_alamat_standard')
                ? format_alamat_standard($addressInputs)
                : $mailingAddress;
        }

        if ($currentAddressForKey !== '') {
            $addressKey = 'address:' . md5(distance_result_cache_key($currentAddressForKey, $this->officeAddress));
            if (isset($cacheMap[$addressKey]) && is_array($cacheMap[$addressKey])) {
                return $cacheMap[$addressKey];
            }

            $addressHash = 'hash:' . hash('sha256', trim((string)$currentAddressForKey));
            if (isset($cacheMap[$addressHash]) && is_array($cacheMap[$addressHash])) {
                return $cacheMap[$addressHash];
            }
        }

        if ($mailingAddress !== '') {
            $mailingHash = 'hash:' . hash('sha256', $mailingAddress);
            if (isset($cacheMap[$mailingHash]) && is_array($cacheMap[$mailingHash])) {
                return $cacheMap[$mailingHash];
            }
        }

        return null;
    }

    protected function extractHomeCoordinatesFromCache(?array $cached): array
    {
        if (!is_array($cached)) {
            return ['lat' => null, 'lon' => null];
        }

        $resultJson = trim((string)($cached['result_json'] ?? ''));
        if ($resultJson === '') {
            return ['lat' => null, 'lon' => null];
        }

        $decoded = json_decode($resultJson, true);
        if (!is_array($decoded)) {
            return ['lat' => null, 'lon' => null];
        }

        $homeCoords = is_array($decoded['home_coords'] ?? null) ? $decoded['home_coords'] : [];
        $lat = isset($homeCoords['lat']) && is_numeric($homeCoords['lat']) ? (float)$homeCoords['lat'] : null;
        $lon = isset($homeCoords['lon']) && is_numeric($homeCoords['lon']) ? (float)$homeCoords['lon'] : null;

        return [
            'lat' => $lat,
            'lon' => $lon,
        ];
    }

    public function handleExportXls(): void
    {
        $payload = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        $requestedSiteCode = trim((string)($payload['site_code'] ?? ''));
        if ($requestedSiteCode !== '') {
            $this->setActiveSiteContext(distance_normalize_site_code($requestedSiteCode));
        }

        $department = trim((string)($payload['department'] ?? ''));
        if ($department === '__ALL__' || strtolower($department) === 'all') {
            $department = '';
        }

        $this->selectedDepartment = $department;
        $this->selectedDepartmentValue = $department;
        $this->loadRows();

        $rows = $this->rows;
        $searchQuery = trim((string)($payload['search_query'] ?? ''));
        if ($searchQuery !== '') {
            $rows = $this->filterExportRowsBySearchQuery($rows, $searchQuery);
        }

        if (!is_array($rows) || $rows === []) {
            if (!headers_sent()) {
                http_response_code(400);
                header('Content-Type: application/json; charset=UTF-8');
            }
            echo json_encode([
                'success' => false,
                'message' => 'empty_export_rows',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $filenameSuffix = $department !== '' ? $department : 'semua-jabatan';
        $filename = 'bdr-distance-'
            . $this->sanitizeExportFilenamePart($this->siteCode)
            . '-'
            . $this->sanitizeExportFilenamePart($filenameSuffix)
            . '-'
            . date('Ymd-His')
            . '.xls';

        if (!headers_sent()) {
            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
            header('Pragma: public');
        }

        echo "\xEF\xBB\xBF";
        echo '<html><head><meta charset="UTF-8"></head><body>';
        echo '<table border="1">';
        echo '<thead><tr>';
        echo '<th>' . $this->e($this->tr('bdr_distance_col_no', 'No.')) . '</th>';
        echo '<th>' . $this->e($this->tr('bdr_distance_col_name', 'Nama')) . '</th>';
        echo '<th>' . $this->e($this->tr('bdr_distance_col_staff_no', 'No. Staf')) . '</th>';
        echo '<th>' . $this->e($this->tr('bdr_distance_col_department', 'Jabatan')) . '</th>';
        echo '<th>' . $this->e($this->tr('bdr_distance_col_mailing_address', 'Alamat (Map)')) . '</th>';
        echo '<th>' . $this->e($this->tr('bdr_distance_col_distance', 'Jarak (KM)')) . '</th>';
        echo '<th>' . $this->e($this->tr('bdr_distance_col_actions', 'Tindakan')) . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            echo '<tr>';
            echo '<td>' . $this->e((string)($row['no'] ?? '')) . '</td>';
            echo '<td>' . $this->e((string)($row['name'] ?? '')) . '</td>';
            echo '<td>' . $this->e((string)($row['staff_no'] ?? '')) . '</td>';
            echo '<td>' . $this->e((string)($row['department'] ?? '')) . '</td>';
            echo '<td>' . $this->e((string)($row['mailing_address'] ?? '')) . '</td>';
            echo '<td>' . $this->e((string)($row['distance_label'] ?? '')) . '</td>';
            echo '<td>' . $this->e((string)($row['actions'] ?? '-')) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table></body></html>';
        exit;
    }

    protected function filterExportRowsBySearchQuery(array $rows, string $searchQuery): array
    {
        $needle = strtolower(trim($searchQuery));
        if ($needle === '') {
            return $rows;
        }

        return array_values(array_filter($rows, static function (array $row) use ($needle): bool {
            $haystacks = [
                (string)($row['name'] ?? ''),
                (string)($row['staff_no'] ?? ''),
                (string)($row['department'] ?? ''),
                (string)($row['mailing_address'] ?? ''),
                (string)($row['distance_label'] ?? ''),
            ];

            foreach ($haystacks as $haystack) {
                if (str_contains(strtolower($haystack), $needle)) {
                    return true;
                }
            }

            return false;
        }));
    }

    protected function loadRows(bool $includeNotificationMeta = true): void
    {
        try {
            [$sql, $params] = $this->buildSybaseRowsQuery();
            if ($sql === '') {
                $this->departmentOptions = [];
                $this->rows = [];
                return;
            }

            $pdoSybase = Database::pdoSybaseStaff();
            $stmt = $pdoSybase->prepare($sql);
            $stmt->execute($params);
            $sourceRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $allRows = [];
            $activeStaffNos = [];
            $staffSnapshotRows = [];

            foreach ($sourceRows as $index => $row) {
                if (!is_array($row)) {
                    continue;
                }

                $builtRow = $this->buildSourceRowFromSybaseRecord($row, $index);
                if (!is_array($builtRow)) {
                    continue;
                }

                $name = (string)($builtRow['name'] ?? '');
                $staffNo = (string)($builtRow['staff_no'] ?? '');
                $department = (string)($builtRow['department'] ?? '');
                $email = (string)($builtRow['email'] ?? '');
                $departmentCode = (string)($builtRow['department_code'] ?? '');
                $position = (string)($builtRow['position'] ?? '');

                if ($staffNo !== '') {
                    $activeStaffNos[] = $staffNo;
                    $staffSnapshotRows[] = [
                        'staff_no' => $staffNo,
                        'staff_name' => $name,
                        'email' => $email,
                        'department' => $department,
                        'department_code' => $departmentCode,
                        'position' => $position,
                    ];
                }

                $allRows[] = $builtRow;
            }

            $activeStaffNos = array_values(array_unique(array_filter($activeStaffNos)));
            $this->syncStaffSiteSnapshot($staffSnapshotRows, $activeStaffNos, false);
            $siteMap = $this->loadStaffSiteMap($activeStaffNos);
            $allRows = $this->filterRowsByActiveSite($allRows, $siteMap);
            $allRows = array_values(array_map(function (array $row): array {
                $row['site_code'] = $this->siteCode;
                $row['office_label'] = $this->officeLabel;
                $row['office_address'] = $this->officeAddress;
                return $row;
            }, $allRows));
            $this->departmentOptions = $this->loadDepartmentOptionsForActiveSite();
            if ($this->departmentOptions === []) {
                $this->departmentOptions = $this->extractDepartmentOptions($allRows);
            }

            $filteredRows = $this->filterRowsByDepartment($allRows, $this->selectedDepartment);

            $rows = array_values(array_map(function (array $row, int $index): array {
                $row['id'] = $index + 1;
                return $row;
            }, $filteredRows, array_keys($filteredRows)));

            $rows = $this->hydrateRowsFromDistanceCache($rows);
            if ($includeNotificationMeta) {
                $rows = $this->enrichRowsWithNotificationMeta($rows);
            }
            $this->rows = $rows;
        } catch (Throwable $e) {
            error_log('[BdrDistanceController] Failed loading active staff from Sybase: ' . $e->getMessage());
            $this->departmentOptions = [];
            $this->rows = [];
        }
    }

    protected function loadHydratedCurrentRowByStaffNo(string $staffNo): ?array
    {
        $staffNo = trim($staffNo);
        if ($staffNo === '') {
            return null;
        }

        try {
            [$sql, $params] = $this->buildSybaseRowsQuery($staffNo);
            if ($sql === '') {
                return null;
            }

            $pdoSybase = Database::pdoSybaseStaff();
            $stmt = $pdoSybase->prepare($sql);
            $stmt->execute($params);
            $sourceRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($sourceRow)) {
                return null;
            }

            $row = $this->buildSourceRowFromSybaseRecord($sourceRow, 0);
            if (!is_array($row)) {
                return null;
            }

            $row['site_code'] = $this->siteCode;
            $row['office_label'] = $this->officeLabel;
            $row['office_address'] = $this->officeAddress;

            $rows = $this->hydrateRowsFromDistanceCache([$row]);
            $rows = $this->enrichRowsWithNotificationMeta($rows);
            if (!is_array($rows[0] ?? null)) {
                return null;
            }

            $rows[0]['id'] = 1;

            return $rows[0];
        } catch (Throwable $e) {
            error_log('[BdrDistanceController] Failed loading hydrated current staff row: ' . $e->getMessage());
            return null;
        }
    }

    protected function buildSourceRowFromSybaseRecord(array $row, int $index): ?array
    {
        $name = $this->pickValue($row, ['gelar_nama']);
        $staffNo = $this->pickValue($row, ['nopekerja']);
        $department = $this->pickValue($row, ['jabatansemasa']);
        $email = $this->pickValue($row, ['email']);
        $departmentCode = $this->pickValue($row, ['kdjbtnsemasa']);
        $position = $this->pickValue($row, ['jawatansemasa']);
        $addressData = $this->extractAddressData($row);
        $normalizedAddress = function_exists('alamat_parse_components')
            ? alamat_parse_components($addressData)
            : [];
        $mailingAddress = trim((string)($normalizedAddress['alamat_standard'] ?? format_alamat_standard($addressData)));
        $addressStatus = trim((string)($normalizedAddress['status'] ?? validate_alamat($addressData)));
        $mapUrl = generate_map_url($mailingAddress);
        $directionUrl = generate_direction_url($mailingAddress, $this->officeAddress);
        $searchCandidates = function_exists('build_distance_search_candidates')
            ? build_distance_search_candidates($addressData)
            : [];
        $defaultMatchedQuery = trim((string)($searchCandidates[0] ?? $mailingAddress));
        $rowMatchQuality = '';
        $rowDebugReason = '';
        $rowMatchedQuery = $defaultMatchedQuery;
        $rowHomeCoordsLabel = '';
        $rowDistanceSource = '';

        if ($addressStatus === 'EMPTY') {
            $rowMatchQuality = 'NONE';
            $rowDebugReason = 'address_empty';
            $rowDistanceSource = 'not_available';
        } elseif ($addressStatus === 'INVALID') {
            $rowMatchQuality = 'LOW';
            $rowDebugReason = 'address_status_invalid';
            $rowDistanceSource = 'address_only';
        } else {
            $rowMatchQuality = 'BELUM DIKIRA';
            $rowDebugReason = 'deferred_lookup';
            $rowDistanceSource = 'deferred_lookup';
        }

        if ($staffNo === '' && $name === '') {
            return null;
        }

        return [
            'id' => $index + 1,
            'name' => $name,
            'staff_no' => $staffNo,
            'department' => $department,
            'department_code' => $departmentCode,
            'position' => $position,
            'department_short' => $department,
            'email' => $email,
            'address_status' => $addressStatus,
            'mailing_address' => $mailingAddress,
            'map_url' => $mapUrl,
            'office_address' => $this->officeAddress,
            'distance_km' => null,
            'distance_label' => '',
            'distance_source' => $rowDistanceSource,
            'route_provider' => '',
            'route_points' => [],
            'matched_query' => $rowMatchedQuery,
            'provider_display_name' => $rowMatchedQuery,
            'home_coords_label' => $rowHomeCoordsLabel,
            'office_coords_label' => $this->formatCoordsLabel($this->officeCoords),
            'match_quality' => $rowMatchQuality,
            'cache_origin' => 'none',
            'debug_reason' => $rowDebugReason,
            'distance_attempted' => false,
            'is_review_location' => false,
            'is_address_changed' => false,
            'address_inputs' => $addressData,
            'direction_url' => $directionUrl,
        ];
    }

    /**
     * @return array{0:string,1:array<int,string>}
     */
    protected function buildSybaseRowsQuery(string $staffNo = ''): array
    {
        $where = [
            'CONVERT(INT, a.kodstatus) <> 9',
            'a.nopekerja IS NOT NULL',
            "LTRIM(RTRIM(a.nopekerja)) <> ''",
        ];
        $params = [];

        if ($this->siteCode === 'hat_mizan') {
            $hatStaffNos = $this->loadActiveStaffNosForSite($this->siteCode);
            if ($hatStaffNos === []) {
                return ['', []];
            }

            $where[] = 'LTRIM(RTRIM(a.nopekerja)) IN (' . implode(',', array_fill(0, count($hatStaffNos), '?')) . ')';
            $params = array_merge($params, $hatStaffNos);
        } elseif ($staffNo === '' && $this->selectedDepartment !== '') {
            $where[] = 'LTRIM(RTRIM(a.jabatansemasa)) = ?';
            $params[] = $this->selectedDepartment;
        }

        if ($staffNo !== '') {
            $where[] = 'LTRIM(RTRIM(a.nopekerja)) = ?';
            $params[] = $staffNo;
        }

        return [
            'SELECT a.*
             FROM v630staf_service_skim_all a
             WHERE ' . implode("\n               AND ", $where) . '
             ORDER BY a.gelar_nama ASC',
            $params,
        ];
    }

    protected function ensureStaffSiteTable(): void
    {
        $this->pdoMysql->exec(
            'CREATE TABLE IF NOT EXISTS ' . self::STAFF_SITE_TABLE . ' (
                f_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                f_staffNo VARCHAR(100) NOT NULL,
                f_staffName VARCHAR(255) DEFAULT NULL,
                f_email VARCHAR(255) DEFAULT NULL,
                f_department VARCHAR(255) DEFAULT NULL,
                f_departmentCode VARCHAR(100) DEFAULT NULL,
                f_position VARCHAR(255) DEFAULT NULL,
                f_siteCode VARCHAR(50) NOT NULL DEFAULT \'upnm_kampus\',
                f_source VARCHAR(50) NOT NULL DEFAULT \'SYBASE_SYNC\',
                f_notes TEXT DEFAULT NULL,
                f_isActive TINYINT(1) NOT NULL DEFAULT 1,
                f_lastSyncedAt DATETIME DEFAULT NULL,
                f_createdBy VARCHAR(100) DEFAULT NULL,
                f_createdAt DATETIME NOT NULL,
                f_updatedAt DATETIME NOT NULL,
                PRIMARY KEY (f_id),
                UNIQUE KEY uq_bdr_staff_site_staff (f_staffNo),
                KEY idx_bdr_staff_site_site (f_siteCode),
                KEY idx_bdr_staff_site_active (f_isActive),
                KEY idx_bdr_staff_site_department (f_department(100)),
                KEY idx_bdr_staff_site_email (f_email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /**
     * @param array<int,array<string,string>> $rows
     * @param array<int,string> $activeStaffNos
     */
    protected function syncStaffSiteSnapshot(array $rows, array $activeStaffNos, bool $markInactive = false): void
    {
        if ($rows === []) {
            $this->debugStaffSiteSync([
                'event' => 'sync_skipped_empty_rows',
                'active_staff_count' => count($activeStaffNos),
                'selected_department' => $this->selectedDepartment,
            ]);
            return;
        }

        try {
            $this->ensureStaffSiteTable();
            $sampleRows = array_slice(array_map(static function (array $row): array {
                return [
                    'staff_no' => trim((string)($row['staff_no'] ?? '')),
                    'department' => trim((string)($row['department'] ?? '')),
                    'department_code' => trim((string)($row['department_code'] ?? '')),
                    'position' => trim((string)($row['position'] ?? '')),
                ];
            }, $rows), 0, 5);
            $this->debugStaffSiteSync([
                'event' => 'sync_started',
                'row_count' => count($rows),
                'active_staff_count' => count($activeStaffNos),
                'selected_department' => $this->selectedDepartment,
                'mark_inactive' => $markInactive,
                'sample_rows' => $sampleRows,
            ]);

            $stmt = $this->pdoMysql->prepare(
                'INSERT INTO ' . self::STAFF_SITE_TABLE . '
                    (f_staffNo, f_staffName, f_email, f_department, f_departmentCode, f_position, f_siteCode, f_source, f_isActive, f_lastSyncedAt, f_createdAt, f_updatedAt)
                 VALUES
                    (:staff_no, :staff_name, :email, :department, :department_code, :position, :site_code, :source, 1, NOW(), NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    f_staffName = VALUES(f_staffName),
                    f_email = VALUES(f_email),
                    f_department = VALUES(f_department),
                    f_departmentCode = VALUES(f_departmentCode),
                    f_position = VALUES(f_position),
                    f_source = IF(f_source IS NULL OR f_source = \'\', VALUES(f_source), f_source),
                    f_isActive = 1,
                    f_lastSyncedAt = NOW(),
                    f_updatedAt = NOW()'
            );

            $this->pdoMysql->beginTransaction();
            $syncedCount = 0;
            foreach ($rows as $row) {
                $staffNo = trim((string)($row['staff_no'] ?? ''));
                if ($staffNo === '') {
                    continue;
                }
                $stmt->execute([
                    ':staff_no' => $staffNo,
                    ':staff_name' => trim((string)($row['staff_name'] ?? '')),
                    ':email' => trim((string)($row['email'] ?? '')),
                    ':department' => trim((string)($row['department'] ?? '')),
                    ':department_code' => trim((string)($row['department_code'] ?? '')),
                    ':position' => trim((string)($row['position'] ?? '')),
                    ':site_code' => distance_default_site_code(),
                    ':source' => 'SYBASE_SYNC',
                ]);
                $syncedCount++;
            }
            $this->pdoMysql->commit();

            if ($markInactive) {
                $this->markInactiveStaffSiteSnapshot($activeStaffNos);
            }

            $this->debugStaffSiteSync([
                'event' => 'sync_completed',
                'row_count' => count($rows),
                'synced_count' => $syncedCount,
                'active_staff_count' => count($activeStaffNos),
                'selected_department' => $this->selectedDepartment,
            ]);
        } catch (Throwable $e) {
            if ($this->pdoMysql->inTransaction()) {
                $this->pdoMysql->rollBack();
            }
            $this->debugStaffSiteSync([
                'event' => 'sync_exception',
                'row_count' => count($rows),
                'active_staff_count' => count($activeStaffNos),
                'selected_department' => $this->selectedDepartment,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            error_log('[BdrDistanceController] Failed syncing BDR staff site snapshot: ' . $e->getMessage());
        }
    }

    /**
     * @return array<int,string>
     */
    protected function loadActiveStaffNosForSite(string $siteCode): array
    {
        try {
            $this->ensureStaffSiteTable();
            $stmt = $this->pdoMysql->prepare(
                'SELECT f_staffNo
                 FROM ' . self::STAFF_SITE_TABLE . '
                 WHERE f_siteCode = :site_code
                   AND f_isActive = 1
                 ORDER BY f_staffNo ASC'
            );
            $stmt->execute([':site_code' => distance_normalize_site_code($siteCode)]);

            return array_values(array_filter(array_map(static function ($value): string {
                return trim((string)$value);
            }, $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [])));
        } catch (Throwable $e) {
            error_log('[BdrDistanceController] Failed loading BDR staff numbers for site: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @return array<int,string>
     */
    protected function loadDepartmentOptionsForActiveSite(): array
    {
        try {
            $this->ensureStaffSiteTable();
            $stmt = $this->pdoMysql->prepare(
                'SELECT DISTINCT f_department
                 FROM ' . self::STAFF_SITE_TABLE . '
                 WHERE f_siteCode = :site_code
                   AND f_isActive = 1
                   AND f_department IS NOT NULL
                   AND TRIM(f_department) <> \'\'
                 ORDER BY f_department ASC'
            );
            $stmt->execute([':site_code' => $this->siteCode]);

            return array_values(array_filter(array_map(static function ($value): string {
                return trim((string)$value);
            }, $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [])));
        } catch (Throwable $e) {
            error_log('[BdrDistanceController] Failed loading BDR department options for site: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @param array<int,string> $activeStaffNos
     */
    protected function markInactiveStaffSiteSnapshot(array $activeStaffNos): void
    {
        $activeStaffNos = array_values(array_unique(array_filter(array_map(static function ($value): string {
            return trim((string)$value);
        }, $activeStaffNos))));

        if ($activeStaffNos === []) {
            return;
        }

        try {
            $placeholders = implode(',', array_fill(0, count($activeStaffNos), '?'));
            $stmt = $this->pdoMysql->prepare(
                'UPDATE ' . self::STAFF_SITE_TABLE . '
                 SET f_isActive = 0, f_updatedAt = NOW()
                 WHERE f_staffNo NOT IN (' . $placeholders . ')'
            );
            $stmt->execute($activeStaffNos);
        } catch (Throwable $e) {
            error_log('[BdrDistanceController] Failed marking inactive BDR staff site snapshot: ' . $e->getMessage());
        }
    }

    /**
     * @param array<int,string> $staffNos
     * @return array<string,array{site_code:string,is_active:bool}>
     */
    protected function loadStaffSiteMap(array $staffNos): array
    {
        $staffNos = array_values(array_unique(array_filter(array_map(static function ($value): string {
            return trim((string)$value);
        }, $staffNos))));

        if ($staffNos === []) {
            return [];
        }

        try {
            $this->ensureStaffSiteTable();
            $placeholders = implode(',', array_fill(0, count($staffNos), '?'));
            $stmt = $this->pdoMysql->prepare(
                'SELECT f_staffNo, f_siteCode, f_isActive
                 FROM ' . self::STAFF_SITE_TABLE . '
                 WHERE f_staffNo IN (' . $placeholders . ')'
            );
            $stmt->execute($staffNos);

            $map = [];
            while ($record = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $staffNo = trim((string)($record['f_staffNo'] ?? ''));
                if ($staffNo === '') {
                    continue;
                }
                $map[$staffNo] = [
                    'site_code' => distance_normalize_site_code((string)($record['f_siteCode'] ?? '')),
                    'is_active' => ((int)($record['f_isActive'] ?? 0)) === 1,
                ];
            }

            return $map;
        } catch (Throwable $e) {
            error_log('[BdrDistanceController] Failed loading BDR staff site map: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @param array<string,array{site_code:string,is_active:bool}> $siteMap
     * @return array<int,array<string,mixed>>
     */
    protected function filterRowsByActiveSite(array $rows, array $siteMap): array
    {
        return array_values(array_filter($rows, function (array $row) use ($siteMap): bool {
            $staffNo = trim((string)($row['staff_no'] ?? ''));
            $siteInfo = $staffNo !== '' ? ($siteMap[$staffNo] ?? null) : null;
            $rowSiteCode = is_array($siteInfo) ? (string)($siteInfo['site_code'] ?? '') : distance_default_site_code();
            $isActive = !is_array($siteInfo) || !empty($siteInfo['is_active']);

            return $isActive && distance_normalize_site_code($rowSiteCode) === $this->siteCode;
        }));
    }

    protected function loadSingleRowByStaffNo(string $staffNo): ?array
    {
        $this->lastApiLookupFailure = null;
        $staffNo = trim($staffNo);
        if ($staffNo === '') {
            $this->debugApiLookup([
                'event' => 'lookup_empty_staff_no',
                'remote_addr' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            ]);
            return null;
        }

        try {
            $this->debugApiLookup([
                'event' => 'lookup_started',
                'staff_no' => $staffNo,
                'site_code' => $this->siteCode,
                'remote_addr' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            ]);

            if (!distance_result_table_ready()) {
                $this->lastApiLookupFailure = [
                    'type' => 'backend_unavailable',
                    'message' => 'Distance cache table is not ready.',
                ];
                $this->debugApiLookup([
                    'event' => 'lookup_cache_unavailable',
                    'staff_no' => $staffNo,
                    'site_code' => $this->siteCode,
                    'remote_addr' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
                ]);
                return null;
            }

            $stmt = $this->pdoMysql->prepare(
                'SELECT f_stafID, f_siteCode, f_homeAddress, f_homeAddressJson, f_distanceKm, f_source, f_routeProvider, f_matchQuality, f_resultJson, f_status, f_lastCalculatedAt
                 FROM ' . distance_result_table_name() . '
                 WHERE f_stafID = :staff_no
                   AND f_siteCode = :site_code
                 ORDER BY f_lastCalculatedAt DESC, f_cacheID DESC
                 LIMIT 1'
            );
            $stmt->execute([
                ':staff_no' => $staffNo,
                ':site_code' => $this->siteCode,
            ]);
            $cacheRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($cacheRow)) {
                $staffSiteRecord = $this->loadStaffSiteRecordForApi($staffNo);
                $row = is_array($staffSiteRecord) ? $this->buildApiRowFromStaffSiteRecord($staffSiteRecord) : null;
                if (is_array($row)) {
                    $this->debugApiLookup([
                        'event' => 'lookup_staff_snapshot_without_distance_cache',
                        'staff_no' => $staffNo,
                        'site_code' => $this->siteCode,
                        'staff_snapshot_found' => true,
                        'snapshot_department_code' => trim((string)($staffSiteRecord['f_departmentCode'] ?? '')),
                        'snapshot_department' => trim((string)($staffSiteRecord['f_department'] ?? '')),
                        'remote_addr' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
                    ]);
                    return $row;
                }

                $this->debugApiLookup([
                    'event' => 'lookup_not_found',
                    'staff_no' => $staffNo,
                    'site_code' => $this->siteCode,
                    'staff_snapshot_found' => false,
                    'remote_addr' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
                ]);
                return null;
            }

            $staffSiteRecord = $this->loadStaffSiteRecordForApi($staffNo);
            $row = $this->buildApiRowFromDistanceCacheRecord($cacheRow, $staffSiteRecord);
            if (!is_array($row)) {
                $this->debugApiLookup([
                    'event' => 'lookup_build_row_failed',
                    'staff_no' => $staffNo,
                    'site_code' => $this->siteCode,
                    'remote_addr' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
                ]);
                return null;
            }

            $this->debugApiLookup([
                'event' => 'lookup_found',
                'staff_no' => $staffNo,
                'matched_by' => 'mysql_cache',
                'matched_f_stafID' => trim((string)($cacheRow['f_stafID'] ?? '')),
                'site_code' => $this->siteCode,
                'staff_snapshot_found' => is_array($staffSiteRecord),
                'snapshot_department_code' => trim((string)($staffSiteRecord['f_departmentCode'] ?? '')),
                'snapshot_department' => trim((string)($staffSiteRecord['f_department'] ?? '')),
                'cache_status' => trim((string)($cacheRow['f_status'] ?? '')),
                'last_calculated_at' => trim((string)($cacheRow['f_lastCalculatedAt'] ?? '')),
                'remote_addr' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            ]);

            return $row;
        } catch (Throwable $e) {
            $this->lastApiLookupFailure = [
                'type' => 'backend_unavailable',
                'message' => $e->getMessage(),
            ];
            $this->debugApiLookup([
                'event' => 'lookup_exception',
                'staff_no' => $staffNo,
                'site_code' => $this->siteCode,
                'message' => $e->getMessage(),
                'remote_addr' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            ]);
            error_log('[BdrDistanceController] Failed loading single staff record for API: ' . $e->getMessage());
            return null;
        }
    }

    protected function loadStaffSiteRecordForApi(string $staffNo): ?array
    {
        $staffNo = trim($staffNo);
        if ($staffNo === '') {
            return null;
        }

        try {
            $stmt = $this->pdoMysql->prepare(
                'SELECT f_staffNo, f_staffName, f_email, f_department, f_departmentCode, f_position, f_siteCode, f_isActive, f_lastSyncedAt
                 FROM ' . self::STAFF_SITE_TABLE . '
                 WHERE f_staffNo = :staff_no
                   AND f_siteCode = :site_code
                   AND f_isActive = 1
                 LIMIT 1'
            );
            $stmt->execute([
                ':staff_no' => $staffNo,
                ':site_code' => $this->siteCode,
            ]);

            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            return is_array($record) ? $record : null;
        } catch (Throwable $e) {
            $this->debugApiLookup([
                'event' => 'lookup_staff_snapshot_exception',
                'staff_no' => $staffNo,
                'site_code' => $this->siteCode,
                'message' => $e->getMessage(),
                'remote_addr' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            ]);
            return null;
        }
    }

    protected function buildApiRowFromStaffSiteRecord(array $staffSiteRecord): ?array
    {
        $staffNo = trim((string)($staffSiteRecord['f_staffNo'] ?? ''));
        $snapshotSiteCode = distance_normalize_site_code((string)($staffSiteRecord['f_siteCode'] ?? $this->siteCode));
        if (
            $staffNo === ''
            || $snapshotSiteCode !== $this->siteCode
            || ((int)($staffSiteRecord['f_isActive'] ?? 0)) !== 1
        ) {
            return null;
        }

        return [
            'id' => 0,
            'name' => trim((string)($staffSiteRecord['f_staffName'] ?? '')),
            'staff_no' => $staffNo,
            'email' => trim((string)($staffSiteRecord['f_email'] ?? '')),
            'department' => trim((string)($staffSiteRecord['f_department'] ?? '')),
            'department_code' => trim((string)($staffSiteRecord['f_departmentCode'] ?? '')),
            'position' => trim((string)($staffSiteRecord['f_position'] ?? '')),
            'site_code' => $this->siteCode,
            'office_label' => $this->officeLabel,
            'office_address' => $this->officeAddress,
            'mailing_address' => '',
            'map_url' => '',
            'address_status' => 'UNKNOWN',
            'distance_km' => null,
            'distance_label' => '',
            'distance_source' => '',
            'route_provider' => '',
            'route_points' => [],
            'matched_query' => '',
            'provider_display_name' => '',
            'home_coords_label' => '',
            'office_coords_label' => $this->formatCoordsLabel($this->officeCoords),
            'match_quality' => '',
            'cache_origin' => 'none',
            'debug_reason' => 'staff_snapshot_without_distance_cache',
            'distance_attempted' => false,
            'is_review_location' => false,
            'is_address_changed' => false,
            'address_inputs' => [],
            'direction_url' => '',
        ];
    }

    protected function buildApiRowFromDistanceCacheRecord(array $cacheRow, ?array $staffSiteRecord = null): ?array
    {
        $staffNo = trim((string)($cacheRow['f_stafID'] ?? ''));
        if ($staffNo === '') {
            return null;
        }

        $address = trim((string)($cacheRow['f_homeAddress'] ?? ''));
        $addressInputs = [];
        $homeAddressJson = trim((string)($cacheRow['f_homeAddressJson'] ?? ''));
        if ($homeAddressJson !== '') {
            $decodedAddress = json_decode($homeAddressJson, true);
            if (is_array($decodedAddress)) {
                $addressInputs = [
                    'alamat1' => trim((string)($decodedAddress['alamat1'] ?? '')),
                    'alamat2' => trim((string)($decodedAddress['alamat2'] ?? '')),
                    'alamat3' => trim((string)($decodedAddress['alamat3'] ?? '')),
                    'poskod' => trim((string)($decodedAddress['poskod'] ?? '')),
                    'negeri' => trim((string)($decodedAddress['negeri'] ?? '')),
                    'negara' => trim((string)($decodedAddress['negara'] ?? '')),
                ];
            }
        }

        $resultJson = trim((string)($cacheRow['f_resultJson'] ?? ''));
        $result = [];
        if ($resultJson !== '') {
            $decodedResult = json_decode($resultJson, true);
            if (is_array($decodedResult)) {
                $result = $decodedResult;
            }
        }

        $snapshotStaffNo = trim((string)($staffSiteRecord['f_staffNo'] ?? ''));
        $snapshotSiteCode = distance_normalize_site_code((string)($staffSiteRecord['f_siteCode'] ?? $this->siteCode));
        $canUseSnapshot = $snapshotStaffNo !== ''
            && $snapshotStaffNo === $staffNo
            && $snapshotSiteCode === $this->siteCode
            && ((int)($staffSiteRecord['f_isActive'] ?? 0)) === 1;
        $staffName = $canUseSnapshot ? trim((string)($staffSiteRecord['f_staffName'] ?? '')) : '';
        $staffEmail = $canUseSnapshot ? trim((string)($staffSiteRecord['f_email'] ?? '')) : '';
        $department = $canUseSnapshot ? trim((string)($staffSiteRecord['f_department'] ?? '')) : '';
        $departmentCode = $canUseSnapshot ? trim((string)($staffSiteRecord['f_departmentCode'] ?? '')) : '';
        $position = $canUseSnapshot ? trim((string)($staffSiteRecord['f_position'] ?? '')) : '';

        $homeCoords = is_array($result['home_coords'] ?? null) ? $result['home_coords'] : [];
        $officeCoords = is_array($result['office_coords'] ?? null) ? $result['office_coords'] : $this->officeCoords;
        $matchedQuery = trim((string)($homeCoords['matched_query'] ?? $address));
        $providerDisplayName = trim((string)($homeCoords['display_name'] ?? $matchedQuery));
        $matchQuality = trim((string)($cacheRow['f_matchQuality'] ?? $result['match_quality'] ?? ''));
        $distanceKm = is_numeric($cacheRow['f_distanceKm'] ?? null) ? (float)$cacheRow['f_distanceKm'] : null;
        $distanceSource = trim((string)($cacheRow['f_source'] ?? $result['source'] ?? ''));
        $routeProvider = trim((string)($cacheRow['f_routeProvider'] ?? $result['route_provider'] ?? ''));
        $routePoints = is_array($result['route_points'] ?? null) ? $result['route_points'] : [];
        $status = strtoupper(trim((string)($cacheRow['f_status'] ?? '')));
        $distanceLabel = format_distance_km($distanceKm);
        $debugReason = $distanceKm === null ? 'cached_failed_lookup' : 'loaded_from_cache';
        $isReviewLocation = $matchQuality === 'LOW';
        if ($isReviewLocation) {
            $distanceKm = null;
            $distanceLabel = $this->tr('bdr_distance_status_review_location', 'Semak Lokasi');
            $distanceSource = '';
            $routeProvider = '';
            $routePoints = [];
            $debugReason = 'low_match_quality';
        } elseif ($distanceKm === null && $status !== 'SUCCESS') {
            $distanceLabel = '';
        }

        return [
            'id' => 0,
            'name' => $staffName,
            'staff_no' => $staffNo,
            'email' => $staffEmail,
            'department' => $department,
            'department_code' => $departmentCode,
            'position' => $position,
            'site_code' => distance_normalize_site_code((string)($cacheRow['f_siteCode'] ?? $this->siteCode)),
            'office_label' => $this->officeLabel,
            'office_address' => $this->officeAddress,
            'mailing_address' => $address,
            'map_url' => $address !== '' ? generate_direction_url($address, $this->officeAddress) : '',
            'address_status' => $address !== '' ? 'VALID' : 'EMPTY',
            'distance_km' => $distanceKm,
            'distance_label' => $distanceLabel,
            'distance_source' => $distanceSource,
            'route_provider' => $routeProvider,
            'route_points' => $routePoints,
            'matched_query' => $matchedQuery,
            'provider_display_name' => $providerDisplayName,
            'home_coords_label' => $this->formatCoordsLabel($homeCoords),
            'office_coords_label' => $this->formatCoordsLabel($officeCoords),
            'match_quality' => $matchQuality,
            'cache_origin' => 'db',
            'debug_reason' => $debugReason,
            'distance_attempted' => $status !== '',
            'is_review_location' => $isReviewLocation,
            'is_address_changed' => false,
            'address_inputs' => $addressInputs,
            'direction_url' => $address !== '' ? generate_direction_url($address, $this->officeAddress) : '',
        ];
    }

    protected function buildDistanceRowFromSourceRow(array $row, int $index = 1): ?array
    {
        $name = $this->pickValue($row, ['gelar_nama']);
        $staffNo = $this->pickValue($row, ['nopekerja']);
        $department = $this->pickValue($row, ['jabatansemasa']);
        $email = $this->pickValue($row, ['email']);
        $addressData = $this->extractAddressData($row);
        $normalizedAddress = function_exists('alamat_parse_components')
            ? alamat_parse_components($addressData)
            : [];
        $mailingAddress = trim((string)($normalizedAddress['alamat_standard'] ?? format_alamat_standard($addressData)));
        $addressStatus = trim((string)($normalizedAddress['status'] ?? validate_alamat($addressData)));
        $mapUrl = generate_map_url($mailingAddress);
        $directionUrl = generate_direction_url($mailingAddress, $this->officeAddress);
        $searchCandidates = function_exists('build_distance_search_candidates')
            ? build_distance_search_candidates($addressData)
            : [];
        $defaultMatchedQuery = trim((string)($searchCandidates[0] ?? $mailingAddress));
        $rowMatchQuality = '';
        $rowDebugReason = '';
        $rowMatchedQuery = $defaultMatchedQuery;
        $rowHomeCoordsLabel = '';
        $rowDistanceSource = '';

        if ($addressStatus === 'EMPTY') {
            $rowMatchQuality = 'NONE';
            $rowDebugReason = 'address_empty';
            $rowDistanceSource = 'not_available';
        } elseif ($addressStatus === 'INVALID') {
            $rowMatchQuality = 'LOW';
            $rowDebugReason = 'address_status_invalid';
            $rowDistanceSource = 'address_only';
        } else {
            $rowMatchQuality = 'BELUM DIKIRA';
            $rowDebugReason = 'deferred_lookup';
            $rowDistanceSource = 'deferred_lookup';
        }

        if ($staffNo === '' && $name === '') {
            return null;
        }

        return [
            'id' => $index,
            'name' => $name,
            'staff_no' => $staffNo,
            'department' => $department,
            'department_short' => $department,
            'email' => $email,
            'address_status' => $addressStatus,
            'mailing_address' => $mailingAddress,
            'map_url' => $mapUrl,
            'office_address' => $this->officeAddress,
            'distance_km' => null,
            'distance_label' => '',
            'distance_source' => $rowDistanceSource,
            'route_provider' => '',
            'route_points' => [],
            'matched_query' => $rowMatchedQuery,
            'provider_display_name' => $rowMatchedQuery,
            'home_coords_label' => $rowHomeCoordsLabel,
            'office_coords_label' => $this->formatCoordsLabel($this->officeCoords),
            'match_quality' => $rowMatchQuality,
            'cache_origin' => 'none',
            'debug_reason' => $rowDebugReason,
            'distance_attempted' => false,
            'is_review_location' => false,
            'is_address_changed' => false,
            'address_inputs' => $addressData,
            'direction_url' => $directionUrl,
        ];
    }

    protected function buildIssueTypeFromRow(array $row): string
    {
        $precomputed = trim((string)($row['issue_type'] ?? ''));
        if ($precomputed !== '' && $precomputed !== 'none') {
            return $precomputed;
        }
        $addressStatus = strtoupper(trim((string)($row['address_status'] ?? 'VALID')));
        if ($addressStatus === 'EMPTY') {
            return 'no_address';
        }
        if ($addressStatus === 'INVALID') {
            return 'incomplete_address';
        }
        if (!empty($row['is_address_changed'])) {
            return 'address_changed';
        }
        if (!empty($row['is_review_location'])) {
            return 'review_location';
        }
        return 'none';
    }

    protected function buildIssueLabelFromType(string $issueType): string
    {
        switch ($issueType) {
            case 'no_address':
                return $this->tr('bdr_distance_status_no_address', 'Tiada Alamat');
            case 'incomplete_address':
                return $this->tr('bdr_distance_status_incomplete', 'Alamat tidak lengkap');
            case 'address_changed':
                return $this->tr('bdr_distance_email_issue_address_changed', 'Alamat telah berubah');
            case 'review_location':
                return $this->tr('bdr_distance_status_review_location', 'Semak Lokasi');
            case 'too_close':
                return $this->tr('bdr_distance_issue_too_close', 'Jarak Terlalu Dekat');
            case 'too_far':
                return $this->tr('bdr_distance_issue_too_far', 'Jarak Terlalu Jauh');
            case 'state_mismatch':
                return $this->tr('bdr_distance_issue_state_mismatch', 'Negeri Tidak Sepadan');
            case 'outside_allowed_region':
                return $this->tr('bdr_distance_issue_outside_region', 'Luar Kawasan Semakan');
            default:
                return $this->tr('bdr_distance_status_no_issue', 'Tiada Isu');
        }
    }

    protected function buildDistanceStateFromRow(array $row): string
    {
        $addressStatus = strtoupper(trim((string)($row['address_status'] ?? 'VALID')));
        if ($addressStatus === 'EMPTY') {
            return 'no_address';
        }
        if ($addressStatus === 'INVALID') {
            return 'incomplete_address';
        }
        if (!empty($row['is_address_changed'])) {
            return 'pending_recalculation';
        }
        if (!empty($row['is_review_location'])) {
            return 'review_required';
        }
        if (isset($row['distance_km']) && is_numeric($row['distance_km'])) {
            return 'calculated';
        }
        if (!empty($row['distance_attempted'])) {
            return 'failed';
        }
        return 'not_yet_calculated';
    }

    protected function loadApiCacheDetailForRow(array $row): array
    {
        $addressStatus = strtoupper(trim((string)($row['address_status'] ?? 'VALID')));
        if ($addressStatus !== 'VALID' || !empty($row['is_address_changed'])) {
            return [];
        }

        $lookupInput = is_array($row['address_inputs'] ?? null) && $row['address_inputs'] !== []
            ? $row['address_inputs']
            : trim((string)($row['mailing_address'] ?? ''));

        if ($lookupInput === '' || $lookupInput === []) {
            return [];
        }

        $cached = distance_result_cache_get($lookupInput, $this->officeAddress, $this->siteCode);
        if (!is_array($cached)) {
            return [];
        }

        $address = trim((string)($row['mailing_address'] ?? ''));
        $comparisonAddress = $this->resolveAddressForMatchComparison(
            $address,
            is_array($row['address_inputs'] ?? null) ? $row['address_inputs'] : []
        );
        $distanceKm = is_numeric($cached['km'] ?? null) ? (float)$cached['km'] : null;
        $homeCoords = is_array($cached['home_coords'] ?? null) ? $cached['home_coords'] : null;
        $officeCoords = is_array($cached['office_coords'] ?? null) ? $cached['office_coords'] : $this->officeCoords;
        $matchedQuery = trim((string)($homeCoords['matched_query'] ?? $address));
        $qualityTarget = trim((string)($homeCoords['display_name'] ?? $matchedQuery));
        $matchQuality = $this->assessMatchQuality($comparisonAddress, $qualityTarget);
        $distanceSource = (string)($cached['source'] ?? '');
        $routeProvider = (string)($cached['route_provider'] ?? '');
        $routePoints = is_array($cached['route_points'] ?? null) ? $cached['route_points'] : [];
        $distanceLabel = format_distance_km($distanceKm);
        $debugReason = $distanceKm === null ? 'cached_failed_lookup' : 'loaded_from_cache';

        if ($matchQuality === 'LOW') {
            $distanceKm = null;
            $distanceLabel = $this->tr('bdr_distance_status_review_location', 'Semak Lokasi');
            $distanceSource = '';
            $routeProvider = '';
            $routePoints = [];
            $debugReason = 'low_match_quality';
        }

        return [
            'distance_km' => $distanceKm,
            'distance_label' => $distanceLabel,
            'distance_source' => $distanceSource,
            'route_provider' => $routeProvider,
            'route_points' => $routePoints,
            'direction_url' => generate_direction_url($address, $this->officeAddress),
            'matched_query' => $matchedQuery,
            'provider_display_name' => $qualityTarget,
            'home_coords_label' => $this->formatCoordsLabel($homeCoords),
            'office_coords_label' => $this->formatCoordsLabel($officeCoords),
            'match_quality' => $matchQuality,
            'cache_origin' => 'db',
            'debug_reason' => $debugReason,
        ];
    }

    protected function sanitizeExportFilenamePart(string $value): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9_-]+/u', '-', trim($value));
        $sanitized = trim((string)$sanitized, '-');
        return $sanitized !== '' ? strtolower($sanitized) : 'export';
    }

    protected function normalizeAddressComparisonKey(string $value): string
    {
        $value = strtoupper(trim($value));
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/[^A-Z0-9]+/', '', $value) ?? '';
        return trim($value);
    }

    protected function tr(string $key, string $fallback): string
    {
        $value = __($key);
        return ($value === $key || $value === null || $value === '') ? $fallback : (string)$value;
    }

    protected function destinationText(string $text): string
    {
        $officeLabel = trim($this->officeLabel) !== '' ? trim($this->officeLabel) : 'UPNM';
        if (str_contains($text, '{{office_label}}')) {
            return str_replace('{{office_label}}', $officeLabel, $text);
        }

        return str_replace('UPNM', $officeLabel, $text);
    }

    protected function trDestination(string $key, string $fallback): string
    {
        return $this->destinationText($this->tr($key, $fallback));
    }

    protected function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    protected function readDistancePrefetchEntries(): array
    {
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded) && isset($decoded['items']) && is_array($decoded['items'])) {
            return array_values(array_filter($decoded['items'], 'is_array'));
        }

        return [];
    }

    protected function hydrateRowsFromDistanceCache(array $rows): array
    {
        if ($rows === []) {
            return $rows;
        }

        $cacheMap = $this->loadDistanceCacheMap($rows);
        if ($cacheMap === []) {
            return $rows;
        }

        foreach ($rows as $index => $row) {
            $addressStatus = strtoupper(trim((string)($row['address_status'] ?? '')));
            $mailingAddress = trim((string)($row['mailing_address'] ?? ''));
            $staffNo = trim((string)($row['staff_no'] ?? ''));
            if ($addressStatus !== 'VALID' || ($mailingAddress === '' && $staffNo === '')) {
                continue;
            }

            $rowAddressInputs = is_array($row['address_inputs'] ?? null) ? $row['address_inputs'] : [];
            $currentAddressForHash = $mailingAddress;
            if ($rowAddressInputs !== []) {
                $currentAddressForHash = function_exists('format_alamat_standard')
                    ? format_alamat_standard($rowAddressInputs)
                    : $mailingAddress;
            }

            $addressCacheKey = $currentAddressForHash !== ''
                ? ('address:' . md5(distance_result_cache_key($currentAddressForHash, $this->officeAddress)))
                : '';
            $addressHashKey = $currentAddressForHash !== ''
                ? ('hash:' . hash('sha256', trim((string)$currentAddressForHash)))
                : '';
            $staffCacheKey = $staffNo !== '' ? ('staff:' . $staffNo) : '';

            $this->debugDistanceHydrationRowSnapshot([
                'phase' => 'row_scan',
                'staff_no' => $staffNo,
                'address_status' => $addressStatus,
                'mailing_address' => $mailingAddress,
                'address_inputs' => $rowAddressInputs,
                'current_address_for_hash' => $currentAddressForHash,
                'address_cache_key' => $addressCacheKey,
                'address_hash_key' => $addressHashKey,
                'staff_cache_key' => $staffCacheKey,
                'cache_map_has_address' => $addressCacheKey !== '' && isset($cacheMap[$addressCacheKey]),
                'cache_map_has_hash' => $addressHashKey !== '' && isset($cacheMap[$addressHashKey]),
                'cache_map_has_staff' => $staffCacheKey !== '' && isset($cacheMap[$staffCacheKey]),
            ]);

            $cached = null;
            $cacheLookupSource = 'none';
            if ($addressCacheKey !== '' && isset($cacheMap[$addressCacheKey]) && is_array($cacheMap[$addressCacheKey])) {
                $cached = $cacheMap[$addressCacheKey];
                $cacheLookupSource = 'address';
            } elseif ($addressHashKey !== '' && isset($cacheMap[$addressHashKey]) && is_array($cacheMap[$addressHashKey])) {
                $cached = $cacheMap[$addressHashKey];
                $cacheLookupSource = 'address_hash';
            } elseif ($staffCacheKey !== '' && isset($cacheMap[$staffCacheKey]) && is_array($cacheMap[$staffCacheKey])) {
                $cached = $cacheMap[$staffCacheKey];
                $cacheLookupSource = 'staff';
            }
            if (!is_array($cached)) {
                $this->debugDistanceHydrationRowSnapshot([
                    'phase' => 'row_cache_miss',
                    'staff_no' => $staffNo,
                    'address_status' => $addressStatus,
                    'mailing_address' => $mailingAddress,
                    'address_inputs' => $rowAddressInputs,
                    'current_address_for_hash' => $currentAddressForHash,
                    'address_cache_key' => $addressCacheKey,
                    'address_hash_key' => $addressHashKey,
                    'staff_cache_key' => $staffCacheKey,
                    'cache_map_has_address' => $addressCacheKey !== '' && isset($cacheMap[$addressCacheKey]),
                    'cache_map_has_hash' => $addressHashKey !== '' && isset($cacheMap[$addressHashKey]),
                    'cache_map_has_staff' => $staffCacheKey !== '' && isset($cacheMap[$staffCacheKey]),
                ]);
                $this->debugDistanceHydration([
                    'staff_no' => $staffNo,
                    'phase' => 'cache_miss',
                    'address_status' => $addressStatus,
                    'mailing_address' => $mailingAddress,
                    'current_address_for_key' => $currentAddressForHash,
                    'address_cache_key' => $addressCacheKey,
                    'address_hash_key' => $addressHashKey,
                    'staff_cache_key' => $staffCacheKey,
                ]);
                continue;
            }

            $currentHash = hash('sha256', trim((string)$currentAddressForHash));
            $cachedHash = trim((string)($cached['home_address_hash'] ?? ''));
            $normalizedCurrentAddress = trim((string)$currentAddressForHash);
            $cachedHomeAddress = trim((string)($cached['home_address'] ?? ''));
            $currentAddressKey = $this->normalizeAddressComparisonKey($normalizedCurrentAddress);
            $cachedAddressKey = $this->normalizeAddressComparisonKey($cachedHomeAddress);
            $sameNormalizedAddress = $normalizedCurrentAddress !== ''
                && $cachedHomeAddress !== ''
                && (
                    strcasecmp($normalizedCurrentAddress, $cachedHomeAddress) === 0
                    || ($currentAddressKey !== '' && $currentAddressKey === $cachedAddressKey)
                );
            if (
                $cacheLookupSource !== 'address'
                && $staffNo !== ''
                && $currentHash !== ''
                && $cachedHash !== ''
                && $currentHash !== $cachedHash
                && !$sameNormalizedAddress
            ) {
                $this->debugDistanceHydration([
                    'staff_no' => $staffNo,
                    'phase' => 'address_changed',
                    'lookup_source' => $cacheLookupSource,
                    'mailing_address' => $mailingAddress,
                    'current_address_for_hash' => $currentAddressForHash,
                    'current_hash' => $currentHash,
                    'cached_hash' => $cachedHash,
                    'normalized_current_address' => $normalizedCurrentAddress,
                    'cached_home_address' => $cachedHomeAddress,
                    'current_address_key' => $currentAddressKey,
                    'cached_address_key' => $cachedAddressKey,
                    'cached_distance_km' => $cached['distance_km'] ?? null,
                    'cached_status' => $cached['status'] ?? '',
                    'cached_match_quality' => $cached['match_quality'] ?? '',
                ]);
                $this->debugDistanceHydrationRowSnapshot([
                    'phase' => 'row_address_changed',
                    'staff_no' => $staffNo,
                    'lookup_source' => $cacheLookupSource,
                    'mailing_address' => $mailingAddress,
                    'address_inputs' => $rowAddressInputs,
                    'current_address_for_hash' => $currentAddressForHash,
                    'current_hash' => $currentHash,
                    'cached_hash' => $cachedHash,
                    'normalized_current_address' => $normalizedCurrentAddress,
                    'cached_home_address' => $cachedHomeAddress,
                    'current_address_key' => $currentAddressKey,
                    'cached_address_key' => $cachedAddressKey,
                    'cached_status' => $cached['status'] ?? '',
                    'cached_distance_km' => $cached['distance_km'] ?? null,
                ]);
                $rows[$index]['distance_km'] = null;
                $rows[$index]['distance_label'] = '';
                $rows[$index]['distance_source'] = 'deferred_lookup';
                $rows[$index]['route_provider'] = '';
                $rows[$index]['route_points'] = [];
                $rows[$index]['matched_query'] = trim((string)($rows[$index]['matched_query'] ?? ''));
                $rows[$index]['provider_display_name'] = trim((string)($rows[$index]['provider_display_name'] ?? $rows[$index]['matched_query'] ?? ''));
                $rows[$index]['home_coords_label'] = '';
                $rows[$index]['office_coords_label'] = $this->formatCoordsLabel($this->officeCoords);
                $rows[$index]['match_quality'] = 'BELUM DIKIRA';
                $rows[$index]['is_review_location'] = false;
                $rows[$index]['is_address_changed'] = true;
                $rows[$index]['cache_origin'] = 'none';
                $rows[$index]['debug_reason'] = 'address_changed';
                $rows[$index]['distance_attempted'] = false;
                continue;
            }

            $distanceKm = is_numeric($cached['distance_km'] ?? null) ? (float)$cached['distance_km'] : null;
            $matchQuality = $this->resolveCachedMatchQuality($row, $cached);
            $providerDisplayName = $this->resolveCachedProviderDisplayName($row, $cached);
            $distanceSource = (string)($cached['distance_source'] ?? '');
            $routeProvider = (string)($cached['route_provider'] ?? '');
            $routePoints = [];
            $debugReason = $distanceKm === null
                ? 'cached_failed_lookup'
                : $this->resolveMatchDebugReason($mailingAddress, $providerDisplayName, $matchQuality, 'loaded_from_cache');
            $distanceLabel = format_distance_km($distanceKm);

            if ($matchQuality === 'LOW') {
                $distanceKm = null;
                $distanceLabel = $this->tr('bdr_distance_status_review_location', 'Semak Lokasi');
                $distanceSource = '';
                $routeProvider = '';
                $routePoints = [];
                $debugReason = 'low_match_quality';
            } elseif ($distanceKm === null && strtoupper(trim((string)($cached['status'] ?? ''))) !== 'SUCCESS') {
                $distanceLabel = '';
            }

            $rows[$index]['distance_km'] = $distanceKm;
            $rows[$index]['distance_label'] = $distanceLabel;
            $rows[$index]['distance_source'] = $distanceSource;
            $rows[$index]['route_provider'] = $routeProvider;
            $rows[$index]['route_points'] = $routePoints;
            $rows[$index]['matched_query'] = trim((string)($rows[$index]['matched_query'] ?? ''));
            $rows[$index]['provider_display_name'] = $providerDisplayName;
            $rows[$index]['home_coords_label'] = '';
            $rows[$index]['office_coords_label'] = $this->formatCoordsLabel($this->officeCoords);
            $rows[$index]['match_quality'] = $matchQuality;
            $rows[$index]['is_review_location'] = $matchQuality === 'LOW';
            $rows[$index]['is_address_changed'] = false;
            $rows[$index]['cache_origin'] = 'db';
            $rows[$index]['debug_reason'] = $debugReason;
            $rows[$index]['distance_attempted'] = true;
            $this->debugDistanceHydrationRowSnapshot([
                'phase' => 'row_hydrated',
                'staff_no' => $staffNo,
                'lookup_source' => $cacheLookupSource,
                'mailing_address' => $mailingAddress,
                'address_inputs' => $rowAddressInputs,
                'current_address_for_hash' => $currentAddressForHash,
                'current_hash' => $currentHash,
                'cached_hash' => $cachedHash,
                'normalized_current_address' => $normalizedCurrentAddress,
                'cached_home_address' => $cachedHomeAddress,
                'current_address_key' => $currentAddressKey,
                'cached_address_key' => $cachedAddressKey,
                'distance_km' => $distanceKm,
                'distance_label' => $distanceLabel,
                'distance_source' => $distanceSource,
                'match_quality' => $matchQuality,
                'debug_reason' => $debugReason,
                'cache_origin' => 'db',
            ]);
            $this->debugDistanceHydration([
                'staff_no' => $staffNo,
                'phase' => 'hydrated',
                'lookup_source' => $cacheLookupSource,
                'mailing_address' => $mailingAddress,
                'current_address_for_hash' => $currentAddressForHash,
                'current_hash' => $currentHash,
                'cached_hash' => $cachedHash,
                'distance_km' => $distanceKm,
                'distance_label' => $distanceLabel,
                'distance_source' => $distanceSource,
                'match_quality' => $matchQuality,
                'debug_reason' => $debugReason,
            ]);
        }

        return $rows;
    }

    protected function loadDistanceCacheMap(array $rows): array
    {
        if ($rows === [] || !distance_result_table_ready()) {
            return [];
        }

        $staffNos = [];
        $addressKeys = [];
        $addressHashes = [];
        foreach ($rows as $row) {
            $addressStatus = strtoupper(trim((string)($row['address_status'] ?? '')));
            $mailingAddress = trim((string)($row['mailing_address'] ?? ''));
            $staffNo = trim((string)($row['staff_no'] ?? ''));
            $rowAddressInputs = is_array($row['address_inputs'] ?? null) ? $row['address_inputs'] : [];
            if ($addressStatus !== 'VALID') {
                continue;
            }

            $currentAddressForKey = $mailingAddress;
            if ($rowAddressInputs !== []) {
                $currentAddressForKey = function_exists('format_alamat_standard')
                    ? format_alamat_standard($rowAddressInputs)
                    : $mailingAddress;
            }

            if ($currentAddressForKey !== '') {
                $addressKeys[] = md5(distance_result_cache_key($currentAddressForKey, $this->officeAddress));
                $addressHashes[] = hash('sha256', trim((string)$currentAddressForKey));
            }

            if ($mailingAddress !== '') {
                $addressHashes[] = hash('sha256', trim((string)$mailingAddress));
            }

            if ($staffNo !== '') {
                $staffNos[] = $staffNo;
            }
        }

        $staffNos = array_values(array_unique(array_filter($staffNos)));
        $addressKeys = array_values(array_unique(array_filter($addressKeys)));
        $addressHashes = array_values(array_unique(array_filter($addressHashes)));
        if ($staffNos === [] && $addressKeys === [] && $addressHashes === []) {
            return [];
        }

        try {
            $cacheMap = [];

            if ($staffNos !== []) {
                $staffPlaceholders = implode(',', array_fill(0, count($staffNos), '?'));
                $stmt = $this->pdoMysql->prepare(
                    'SELECT f_stafID, f_homeAddressHash, f_distanceKm, f_source, f_routeProvider, f_matchQuality, f_status, f_resultJson
                     , f_homeAddress
                     FROM ' . distance_result_table_name() . '
                     WHERE f_stafID IN (' . $staffPlaceholders . ')
                       AND f_siteCode = ?
                     ORDER BY f_stafID ASC, f_lastCalculatedAt DESC, f_cacheID DESC'
                );
                $stmt->execute(array_merge($staffNos, [$this->siteCode]));

                while ($record = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $staffNo = trim((string)($record['f_stafID'] ?? ''));
                    if ($staffNo === '') {
                        continue;
                    }

                    if (isset($cacheMap['staff:' . $staffNo])) {
                        continue;
                    }

                    $cacheMap['staff:' . $staffNo] = [
                        'home_address_hash' => trim((string)($record['f_homeAddressHash'] ?? '')),
                        'home_address' => trim((string)($record['f_homeAddress'] ?? '')),
                        'distance_km' => isset($record['f_distanceKm']) && is_numeric($record['f_distanceKm']) ? (float)$record['f_distanceKm'] : null,
                        'distance_source' => trim((string)($record['f_source'] ?? '')),
                        'route_provider' => trim((string)($record['f_routeProvider'] ?? '')),
                        'match_quality' => trim((string)($record['f_matchQuality'] ?? '')),
                        'status' => trim((string)($record['f_status'] ?? '')),
                        'result_json' => (string)($record['f_resultJson'] ?? ''),
                    ];
                }
            }

            if ($addressHashes !== []) {
                $hashPlaceholders = implode(',', array_fill(0, count($addressHashes), '?'));
                $stmt = $this->pdoMysql->prepare(
                    'SELECT f_homeAddressHash, f_homeAddress, f_distanceKm, f_source, f_routeProvider, f_matchQuality, f_status, f_resultJson
                     FROM ' . distance_result_table_name() . '
                     WHERE f_homeAddressHash IN (' . $hashPlaceholders . ')
                       AND f_siteCode = ?
                     ORDER BY f_lastCalculatedAt DESC, f_cacheID DESC'
                );
                $stmt->execute(array_merge($addressHashes, [$this->siteCode]));

                while ($record = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $hash = trim((string)($record['f_homeAddressHash'] ?? ''));
                    if ($hash === '' || isset($cacheMap['hash:' . $hash])) {
                        continue;
                    }

                    $cacheMap['hash:' . $hash] = [
                        'home_address_hash' => $hash,
                        'home_address' => trim((string)($record['f_homeAddress'] ?? '')),
                        'distance_km' => isset($record['f_distanceKm']) && is_numeric($record['f_distanceKm']) ? (float)$record['f_distanceKm'] : null,
                        'distance_source' => trim((string)($record['f_source'] ?? '')),
                        'route_provider' => trim((string)($record['f_routeProvider'] ?? '')),
                        'match_quality' => trim((string)($record['f_matchQuality'] ?? '')),
                        'status' => trim((string)($record['f_status'] ?? '')),
                        'result_json' => (string)($record['f_resultJson'] ?? ''),
                    ];
                }
            }

            if ($addressKeys !== []) {
                $addressPlaceholders = implode(',', array_fill(0, count($addressKeys), '?'));
                $stmt = $this->pdoMysql->prepare(
                    'SELECT f_cacheKey, f_homeAddressHash, f_distanceKm, f_source, f_routeProvider, f_matchQuality, f_status, f_resultJson, f_homeAddress
                     FROM ' . distance_result_table_name() . '
                     WHERE f_cacheKey IN (' . $addressPlaceholders . ')
                       AND f_siteCode = ?
                     ORDER BY f_lastCalculatedAt DESC, f_cacheID DESC'
                );
                $stmt->execute(array_merge($addressKeys, [$this->siteCode]));

                while ($record = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $key = trim((string)($record['f_cacheKey'] ?? ''));
                    if ($key === '') {
                        continue;
                    }

                    $cacheMap['address:' . $key] = [
                        'home_address_hash' => trim((string)($record['f_homeAddressHash'] ?? '')),
                        'home_address' => trim((string)($record['f_homeAddress'] ?? '')),
                        'distance_km' => isset($record['f_distanceKm']) && is_numeric($record['f_distanceKm']) ? (float)$record['f_distanceKm'] : null,
                        'distance_source' => trim((string)($record['f_source'] ?? '')),
                        'route_provider' => trim((string)($record['f_routeProvider'] ?? '')),
                        'match_quality' => trim((string)($record['f_matchQuality'] ?? '')),
                        'status' => trim((string)($record['f_status'] ?? '')),
                        'result_json' => (string)($record['f_resultJson'] ?? ''),
                    ];
                }
            }

            return $cacheMap;
        } catch (Throwable $e) {
            error_log('[BdrDistanceController] Failed bulk loading distance cache: ' . $e->getMessage());
            return [];
        }
    }

    protected function resolveCachedMatchQuality(array $row, array $cached): string
    {
        $stored = trim((string)($cached['match_quality'] ?? ''));
        $inputAddress = trim((string)($row['mailing_address'] ?? ''));
        $resultJson = trim((string)($cached['result_json'] ?? ''));
        if ($inputAddress === '' || $resultJson === '') {
            return $stored;
        }

        $decoded = json_decode($resultJson, true);
        if (!is_array($decoded)) {
            return $stored;
        }

        $homeCoords = is_array($decoded['home_coords'] ?? null) ? $decoded['home_coords'] : [];
        $qualityTarget = trim((string)($homeCoords['display_name'] ?? $homeCoords['matched_query'] ?? ''));
        if ($qualityTarget === '') {
            return $stored;
        }

        return $this->assessMatchQuality($inputAddress, $qualityTarget);
    }

    protected function resolveCachedProviderDisplayName(array $row, array $cached): string
    {
        $inputAddress = trim((string)($row['mailing_address'] ?? ''));
        $resultJson = trim((string)($cached['result_json'] ?? ''));
        if ($resultJson === '') {
            return $inputAddress;
        }

        $decoded = json_decode($resultJson, true);
        if (!is_array($decoded)) {
            return $inputAddress;
        }

        $homeCoords = is_array($decoded['home_coords'] ?? null) ? $decoded['home_coords'] : [];
        $displayName = trim((string)($homeCoords['display_name'] ?? $homeCoords['matched_query'] ?? ''));

        return $displayName !== '' ? $displayName : $inputAddress;
    }

    protected function resolveCurrentDepartment(): string
    {
        $fromSybaseByCode = $this->resolveCurrentDepartmentFromSybaseByDepartmentCode();
        if ($fromSybaseByCode !== '') {
            return $fromSybaseByCode;
        }

        foreach ([
            'f_namajabatan',
            'namajabatan',
            'jabatan',
            'f_jabatan',
        ] as $key) {
            $value = trim((string)($this->profile[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        $sessionDepartment = trim((string)($_SESSION['f_namajabatan'] ?? ''));
        if ($sessionDepartment !== '') {
            return $sessionDepartment;
        }

        $fromSybase = $this->resolveCurrentDepartmentFromSybase();
        if ($fromSybase !== '') {
            return $fromSybase;
        }

        return '';
    }

    protected function resolveSelectedDepartment(): string
    {
        $isRowsDataRequest = isset($_GET['rows_data']) && (string)$_GET['rows_data'] === '1';

        if (array_key_exists('department', $_GET)) {
            $requested = trim((string)($_GET['department'] ?? ''));
            if (
                $requested === ''
                || strtoupper($requested) === '__ALL__'
                || strtolower($requested) === 'all'
            ) {
                return '';
            }

            return $requested;
        }

        if ($isRowsDataRequest) {
            return '';
        }

        if ($this->siteCode === 'hat_mizan') {
            return '';
        }

        return $this->currentDepartment;
    }

    protected function pickValue(array $row, array $aliases): string
    {
        foreach ($aliases as $alias) {
            foreach ($row as $key => $value) {
                if ($this->normalizeKey((string)$key) !== $this->normalizeKey($alias)) {
                    continue;
                }

                $text = trim((string)$value);
                if ($text !== '') {
                    return $text;
                }
            }
        }

        return '';
    }

    protected function extractAddressData(array $row): array
    {
        return [
            'alamat1' => $this->pickValue($row, ['alamat_surat1']),
            'alamat2' => $this->pickValue($row, ['alamat_surat2']),
            'alamat3' => $this->pickValue($row, ['alamat_surat3']),
            'poskod' => $this->pickValue($row, ['poskod_surat']),
            'negeri' => $this->pickValue($row, ['negeri_surat']),
            'negara' => $this->pickValue($row, ['negara_surat']),
        ];
    }

    /**
     * @return array{recipient_email:string,variables:array<string,mixed>,context:array<string,mixed>}
     */
    protected function readEmailReminderPayload(): array
    {
        return $this->buildEmailReminderPayloadFromData($_POST);
    }

    /**
     * @param array<string,mixed> $data
     * @return array{recipient_email:string,variables:array<string,mixed>,context:array<string,mixed>}
     */
    protected function buildEmailReminderPayloadFromData(array $data): array
    {
        $payloadSiteCode = trim((string)($data['site_code'] ?? $data['f_siteCode'] ?? ''));
        if ($payloadSiteCode !== '') {
            $this->setActiveSiteContext($payloadSiteCode);
        }

        $staffName = trim((string)($data['staff_name'] ?? ''));
        $staffNo = trim((string)($data['staff_no'] ?? $data['f_stafID'] ?? ''));
        $recipientEmail = trim((string)($data['recipient_email'] ?? ''));
        $department = trim((string)($data['department'] ?? ''));
        $mailingAddress = trim((string)($data['mailing_address'] ?? ''));
        $distanceLabel = trim((string)($data['distance_label'] ?? ''));
        $issueLabel = trim((string)($data['issue_label'] ?? ''));
        $issueReason = trim((string)($data['issue_reason'] ?? ''));
        $issueReasonLines = $this->extractIssueReasonLines($issueReason);
        $addressHash = trim((string)($data['address_hash'] ?? $data['notification_address_hash'] ?? ''));
        $issueTypes = $this->normalizeBulkEmailIssueTypes($data['notification_issue_types'] ?? []);
        $selfConfirmActionHtml = trim((string)($data['self_confirm_action_html'] ?? ''));
        $selfConfirmActionText = trim((string)($data['self_confirm_action_text'] ?? ''));
        $allIssueTypesSelfConfirmable = $issueTypes !== [] && count(array_filter($issueTypes, function (string $issueType): bool {
            return $this->isSelfConfirmableIssueType($issueType);
        })) === count($issueTypes);

        if (!$allIssueTypesSelfConfirmable) {
            $selfConfirmActionHtml = '';
            $selfConfirmActionText = '';
        }

        if (
            $allIssueTypesSelfConfirmable
            && ($selfConfirmActionHtml === '' && $selfConfirmActionText === '')
            && $staffNo !== ''
            && $addressHash !== ''
            && $issueTypes !== []
        ) {
            $selfConfirmAction = $this->buildSelfConfirmActionContent($staffNo, $addressHash, $issueTypes);
            $selfConfirmActionHtml = $selfConfirmAction['html'];
            $selfConfirmActionText = $selfConfirmAction['text'];
        }

        $actionLink = 'https://imap.upnm.edu.my';
        $imapActionHtml = '<p style="margin: 1.25rem 0 0.5rem;"><a href="' . htmlspecialchars($actionLink, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:0.72rem 1.15rem;border-radius:999px;background:#0f766e;color:#ffffff;text-decoration:none;font-weight:700;">' . htmlspecialchars($this->tr('bdr_distance_email_imap_button', 'Akses Sistem iMAP'), ENT_QUOTES, 'UTF-8') . '</a></p>';
        $imapActionText = $this->tr('bdr_distance_email_imap_button', 'Akses Sistem iMAP') . ': ' . $actionLink;
        $primaryActionHtml = $selfConfirmActionHtml !== '' ? $selfConfirmActionHtml : $imapActionHtml;
        $primaryActionText = $selfConfirmActionText !== '' ? $selfConfirmActionText : $imapActionText;
        $guidanceText = $selfConfirmActionHtml !== ''
            ? $this->tr('bdr_distance_email_guidance_self_confirm', 'Jika alamat di atas tidak tepat, sila kemas kini maklumat alamat anda di iMAP melalui portal OneID UPNM.')
            : $this->tr('bdr_distance_email_guidance_update_imap', 'Sila kemas kini atau semak semula maklumat alamat anda di iMAP bagi memastikan pengesahan jarak dapat dibuat dengan tepat.');
        $emailIntro = $this->buildEmailIntroText($issueTypes);
        $legacyIssueReason = $issueReason !== '' ? $issueReason : $this->formatIssueReasonText([]);
        if ($primaryActionText !== '') {
            $legacyIssueReason = trim($legacyIssueReason . "\n\n" . $primaryActionText);
        }

        $variables = [
            'recipient_name' => $staffName !== '' ? $staffName : $this->tr('bdr_distance_email_default_recipient', 'Tuan/Puan'),
            'recipient_email' => $recipientEmail,
            'staff_name' => $staffName,
            'staff_no' => $staffNo,
            'reference_no' => $staffNo !== '' ? $staffNo : '-',
            'status_label' => $issueLabel !== '' ? $issueLabel : $this->tr('bdr_distance_email_issue_default', 'Semakan alamat BDR diperlukan'),
            'email_intro' => $emailIntro,
            'issue_reason' => $legacyIssueReason,
            'issue_reason_text' => $this->formatIssueReasonText($issueReasonLines),
            'issue_reason_html' => $this->formatIssueReasonHtml($issueReasonLines),
            'self_confirm_action_html' => $selfConfirmActionHtml,
            'self_confirm_action_text' => $selfConfirmActionText,
            'primary_action_html' => $primaryActionHtml,
            'primary_action_text' => $primaryActionText,
            'guidance_text' => $guidanceText,
            'year' => date('Y'),
            'current_year' => date('Y'),
            'action_link' => $actionLink,
            'current_address' => $mailingAddress,
            'department_name' => $department,
            'distance_label' => $distanceLabel,
            'office_label' => $this->officeLabel,
            'site_code' => $this->siteCode,
            'office_address' => $this->officeAddress,
        ];

        $context = [
            'recipient_email' => $recipientEmail,
            'recipient_name' => $variables['recipient_name'],
            'recipient_department' => $department,
            'site_code' => $this->siteCode,
            'office_label' => $this->officeLabel,
            'footer_note' => $this->tr('bdr_distance_email_footer_note', 'Emel ini dijana secara automatik. Sila jangan balas emel ini.'),
        ];

        return [
            'recipient_email' => $recipientEmail,
            'variables' => $variables,
            'context' => $context,
        ];
    }

    /**
     * @param array<int,string> $issueTypes
     */
    protected function buildEmailIntroText(array $issueTypes): string
    {
        $issueTypes = array_values(array_unique(array_filter(array_map(static function (string $issueType): string {
            return trim($issueType);
        }, $issueTypes))));

        if (count($issueTypes) > 1) {
            return $this->tr('bdr_distance_email_intro_multiple', 'Emel ini adalah bagi memaklumkan bahawa alamat rumah semasa anda mempunyai beberapa perkara yang memerlukan semakan bagi tujuan rekod Bekerja Dari Rumah (BDR). Sila rujuk butiran isu yang disenaraikan di bawah dan ambil tindakan yang berkaitan.');
        }

        $issueType = $issueTypes[0] ?? '';
        switch ($issueType) {
            case 'review_location':
                return $this->tr('bdr_distance_email_intro_review_location', 'Emel ini adalah bagi memaklumkan bahawa alamat rumah semasa anda memerlukan semakan kerana sistem tidak dapat memadankan lokasi alamat tersebut dengan tahap keyakinan yang mencukupi bagi tujuan semakan rekod Bekerja Dari Rumah (BDR).');
            case 'address_changed':
                return $this->tr('bdr_distance_email_intro_address_changed', 'Emel ini adalah bagi memaklumkan bahawa alamat rumah semasa anda dikesan telah berubah berbanding rekod jarak yang pernah dikira sebelum ini. Sila semak dan pastikan alamat yang direkodkan adalah tepat bagi tujuan semakan rekod Bekerja Dari Rumah (BDR).');
            case 'state_mismatch':
                return $this->tr('bdr_distance_email_intro_state_mismatch', 'Emel ini adalah bagi memaklumkan bahawa negeri yang dipilih dalam rekod alamat anda tidak sepadan dengan negeri yang dikesan berdasarkan alamat rumah semasa yang direkodkan. Sila semak dan kemas kini maklumat negeri yang betul bagi tujuan semakan rekod Bekerja Dari Rumah (BDR).');
            case 'too_close':
                return $this->trDestination('bdr_distance_email_intro_too_close', 'Emel ini adalah bagi memaklumkan bahawa jarak alamat rumah semasa anda ke {{office_label}} dikesan terlalu dekat dan memerlukan semakan lanjut bagi memastikan alamat tersebut adalah alamat kediaman semasa yang sah untuk tujuan rekod Bekerja Dari Rumah (BDR).');
            case 'too_far':
                return $this->trDestination('bdr_distance_email_intro_too_far', 'Emel ini adalah bagi memaklumkan bahawa jarak alamat rumah semasa anda ke {{office_label}} dikesan melebihi julat semakan biasa. Sila sahkan bahawa alamat tersebut adalah kediaman semasa anda dan digunakan untuk berulang-alik bekerja bagi tujuan rekod Bekerja Dari Rumah (BDR).');
            case 'outside_allowed_region':
                return $this->tr('bdr_distance_email_intro_outside_region', 'Emel ini adalah bagi memaklumkan bahawa alamat rumah semasa anda dikesan berada di luar kawasan semakan biasa iaitu Selangor, Wilayah Persekutuan Kuala Lumpur, atau Putrajaya. Sila sahkan bahawa alamat tersebut adalah kediaman semasa anda dan digunakan untuk berulang-alik bekerja bagi tujuan rekod Bekerja Dari Rumah (BDR).');
            default:
                return $this->tr('bdr_distance_email_intro_default', 'Emel ini adalah bagi memaklumkan bahawa alamat rumah semasa anda memerlukan semakan bagi tujuan rekod Bekerja Dari Rumah (BDR). Sila rujuk butiran isu yang disenaraikan di bawah dan ambil tindakan yang berkaitan.');
        }
    }

    protected function assertValidCsrf(): void
    {
        $sessionToken = (string)($_SESSION['csrf_token'] ?? '');
        $requestToken = trim((string)($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')));
        if ($sessionToken === '' || $requestToken === '' || !hash_equals($sessionToken, $requestToken)) {
            $this->jsonError($this->tr('bdr_distance_bulk_email_invalid_csrf', 'Sesi anda telah tamat. Sila muat semula halaman dan cuba lagi.'), 403);
        }
    }

    protected function tableHasColumn(string $table, string $column): bool
    {
        try {
            $stmt = $this->pdoMysql->prepare(
                'SELECT COUNT(*)
                 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table
                   AND COLUMN_NAME = :column'
            );
            $stmt->execute([
                ':table' => $table,
                ':column' => $column,
            ]);

            return (int)$stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    protected function ensureBulkEmailTables(): void
    {
        $this->pdoMysql->exec(
            'CREATE TABLE IF NOT EXISTS ' . self::BULK_EMAIL_JOB_TABLE . ' (
                f_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                f_jobToken VARCHAR(64) NOT NULL,
                f_status VARCHAR(20) NOT NULL DEFAULT \'QUEUED\',
                f_siteCode VARCHAR(50) NOT NULL DEFAULT \'upnm_kampus\',
                f_scopeDepartment VARCHAR(255) DEFAULT NULL,
                f_totalCount INT UNSIGNED NOT NULL DEFAULT 0,
                f_processedCount INT UNSIGNED NOT NULL DEFAULT 0,
                f_successCount INT UNSIGNED NOT NULL DEFAULT 0,
                f_failedCount INT UNSIGNED NOT NULL DEFAULT 0,
                f_skippedCount INT UNSIGNED NOT NULL DEFAULT 0,
                f_deliveryMode VARCHAR(20) NOT NULL DEFAULT \'TEST\',
                f_testRecipient VARCHAR(255) DEFAULT NULL,
                f_createdBy VARCHAR(100) DEFAULT NULL,
                f_createdName VARCHAR(255) DEFAULT NULL,
                f_lastMessage TEXT DEFAULT NULL,
                f_startedAt DATETIME DEFAULT NULL,
                f_finishedAt DATETIME DEFAULT NULL,
                f_createdAt DATETIME NOT NULL,
                f_updatedAt DATETIME NOT NULL,
                PRIMARY KEY (f_id),
                UNIQUE KEY uq_bdr_bulk_job_token (f_jobToken),
                KEY idx_bdr_bulk_job_site (f_siteCode),
                KEY idx_bdr_bulk_job_status (f_status),
                KEY idx_bdr_bulk_job_created (f_createdAt)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $this->pdoMysql->exec(
            'CREATE TABLE IF NOT EXISTS ' . self::BULK_EMAIL_ITEM_TABLE . ' (
                f_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                f_jobId BIGINT UNSIGNED NOT NULL,
                f_siteCode VARCHAR(50) NOT NULL DEFAULT \'upnm_kampus\',
                f_staffNo VARCHAR(100) DEFAULT NULL,
                f_staffName VARCHAR(255) DEFAULT NULL,
                f_recipientEmail VARCHAR(255) DEFAULT NULL,
                f_deliveryEmail VARCHAR(255) DEFAULT NULL,
                f_department VARCHAR(255) DEFAULT NULL,
                f_mailingAddress TEXT DEFAULT NULL,
                f_distanceLabel VARCHAR(255) DEFAULT NULL,
                f_issueLabel VARCHAR(255) DEFAULT NULL,
                f_issueReason TEXT DEFAULT NULL,
                f_selfConfirmHtml TEXT DEFAULT NULL,
                f_selfConfirmText TEXT DEFAULT NULL,
                f_status VARCHAR(20) NOT NULL DEFAULT \'PENDING\',
                f_attemptCount INT UNSIGNED NOT NULL DEFAULT 0,
                f_lastError TEXT DEFAULT NULL,
                f_sentAt DATETIME DEFAULT NULL,
                f_createdAt DATETIME NOT NULL,
                f_updatedAt DATETIME NOT NULL,
                PRIMARY KEY (f_id),
                KEY idx_bdr_bulk_item_job (f_jobId),
                KEY idx_bdr_bulk_item_site (f_siteCode),
                KEY idx_bdr_bulk_item_status (f_status),
                CONSTRAINT fk_bdr_bulk_item_job FOREIGN KEY (f_jobId) REFERENCES ' . self::BULK_EMAIL_JOB_TABLE . ' (f_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $this->pdoMysql->exec(
            'CREATE TABLE IF NOT EXISTS ' . self::BULK_EMAIL_EXCLUSION_TABLE . ' (
                f_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                f_staffNo VARCHAR(100) NOT NULL,
                f_siteCode VARCHAR(50) NOT NULL DEFAULT \'upnm_kampus\',
                f_addressHash CHAR(64) NOT NULL,
                f_issueType VARCHAR(50) NOT NULL,
                f_reason VARCHAR(255) DEFAULT NULL,
                f_notes TEXT DEFAULT NULL,
                f_isActive TINYINT(1) NOT NULL DEFAULT 1,
                f_source VARCHAR(20) NOT NULL DEFAULT \'ADMIN\',
                f_confirmToken CHAR(64) DEFAULT NULL,
                f_tokenExpiresAt DATETIME DEFAULT NULL,
                f_confirmedAt DATETIME DEFAULT NULL,
                f_confirmedIp VARCHAR(64) DEFAULT NULL,
                f_confirmedUserAgent VARCHAR(255) DEFAULT NULL,
                f_confirmedByStaffNo VARCHAR(100) DEFAULT NULL,
                f_createdBy VARCHAR(100) DEFAULT NULL,
                f_createdName VARCHAR(255) DEFAULT NULL,
                f_createdAt DATETIME NOT NULL,
                f_updatedAt DATETIME NOT NULL,
                PRIMARY KEY (f_id),
                UNIQUE KEY uq_bdr_notification_exclusion (f_siteCode, f_staffNo, f_addressHash, f_issueType),
                KEY idx_bdr_notification_exclusion_site (f_siteCode),
                KEY idx_bdr_notification_exclusion_site_staff (f_siteCode, f_staffNo),
                KEY idx_bdr_notification_exclusion_active (f_isActive),
                KEY idx_bdr_notification_exclusion_source (f_source),
                KEY idx_bdr_notification_exclusion_staff (f_staffNo),
                KEY idx_bdr_notification_exclusion_hash (f_addressHash),
                KEY idx_bdr_notification_exclusion_token (f_confirmToken),
                KEY idx_bdr_notification_exclusion_token_expiry (f_tokenExpiresAt)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        if (!$this->tableHasColumn(self::BULK_EMAIL_ITEM_TABLE, 'f_selfConfirmHtml')) {
            $this->pdoMysql->exec('ALTER TABLE ' . self::BULK_EMAIL_ITEM_TABLE . ' ADD COLUMN f_selfConfirmHtml TEXT DEFAULT NULL AFTER f_issueReason');
        }
        if (!$this->tableHasColumn(self::BULK_EMAIL_ITEM_TABLE, 'f_selfConfirmText')) {
            $this->pdoMysql->exec('ALTER TABLE ' . self::BULK_EMAIL_ITEM_TABLE . ' ADD COLUMN f_selfConfirmText TEXT DEFAULT NULL AFTER f_selfConfirmHtml');
        }
        if (!$this->tableHasColumn(self::BULK_EMAIL_JOB_TABLE, 'f_siteCode')) {
            $this->pdoMysql->exec("ALTER TABLE " . self::BULK_EMAIL_JOB_TABLE . " ADD COLUMN f_siteCode VARCHAR(50) NOT NULL DEFAULT 'upnm_kampus' AFTER f_status");
        }
        if (!$this->tableHasColumn(self::BULK_EMAIL_ITEM_TABLE, 'f_siteCode')) {
            $this->pdoMysql->exec("ALTER TABLE " . self::BULK_EMAIL_ITEM_TABLE . " ADD COLUMN f_siteCode VARCHAR(50) NOT NULL DEFAULT 'upnm_kampus' AFTER f_jobId");
        }

        $exclusionColumns = [
            'f_siteCode' => "ALTER TABLE " . self::BULK_EMAIL_EXCLUSION_TABLE . " ADD COLUMN f_siteCode VARCHAR(50) NOT NULL DEFAULT 'upnm_kampus' AFTER f_staffNo",
            'f_source' => "ALTER TABLE " . self::BULK_EMAIL_EXCLUSION_TABLE . " ADD COLUMN f_source VARCHAR(20) NOT NULL DEFAULT 'ADMIN' AFTER f_isActive",
            'f_confirmToken' => "ALTER TABLE " . self::BULK_EMAIL_EXCLUSION_TABLE . " ADD COLUMN f_confirmToken CHAR(64) DEFAULT NULL AFTER f_source",
            'f_tokenExpiresAt' => "ALTER TABLE " . self::BULK_EMAIL_EXCLUSION_TABLE . " ADD COLUMN f_tokenExpiresAt DATETIME DEFAULT NULL AFTER f_confirmToken",
            'f_confirmedAt' => "ALTER TABLE " . self::BULK_EMAIL_EXCLUSION_TABLE . " ADD COLUMN f_confirmedAt DATETIME DEFAULT NULL AFTER f_tokenExpiresAt",
            'f_confirmedIp' => "ALTER TABLE " . self::BULK_EMAIL_EXCLUSION_TABLE . " ADD COLUMN f_confirmedIp VARCHAR(64) DEFAULT NULL AFTER f_confirmedAt",
            'f_confirmedUserAgent' => "ALTER TABLE " . self::BULK_EMAIL_EXCLUSION_TABLE . " ADD COLUMN f_confirmedUserAgent VARCHAR(255) DEFAULT NULL AFTER f_confirmedIp",
            'f_confirmedByStaffNo' => "ALTER TABLE " . self::BULK_EMAIL_EXCLUSION_TABLE . " ADD COLUMN f_confirmedByStaffNo VARCHAR(100) DEFAULT NULL AFTER f_confirmedUserAgent",
        ];
        foreach ($exclusionColumns as $column => $sql) {
            if (!$this->tableHasColumn(self::BULK_EMAIL_EXCLUSION_TABLE, $column)) {
                $this->pdoMysql->exec($sql);
            }
        }
    }

    /**
     * @return array<int,array<string,string>>
     */
    protected function readBulkEmailEntries(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = is_string($raw) && trim($raw) !== '' ? json_decode($raw, true) : null;
        $items = [];
        if (is_array($decoded) && isset($decoded['items']) && is_array($decoded['items'])) {
            $items = $decoded['items'];
        } elseif (isset($_POST['items']) && is_array($_POST['items'])) {
            $items = $_POST['items'];
        }

        $dedupe = [];
        $entries = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $staffNo = trim((string)($item['staff_no'] ?? ''));
            $recipientEmail = strtolower(trim((string)($item['recipient_email'] ?? '')));
            if ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $key = $staffNo !== '' ? ('staff:' . $staffNo) : ('email:' . $recipientEmail);
            if (isset($dedupe[$key])) {
                continue;
            }
            $dedupe[$key] = true;

            $entries[] = [
                'staff_name' => trim((string)($item['staff_name'] ?? '')),
                'staff_no' => $staffNo,
                'recipient_email' => $recipientEmail,
                'department' => trim((string)($item['department'] ?? '')),
                'mailing_address' => trim((string)($item['mailing_address'] ?? '')),
                'distance_label' => trim((string)($item['distance_label'] ?? '')),
                'issue_label' => trim((string)($item['issue_label'] ?? '')),
                'issue_reason' => trim((string)($item['issue_reason'] ?? '')),
            ];
        }

        return $entries;
    }

    protected function normalizeBulkEmailDepartment(string $department): string
    {
        $department = trim($department);
        if (
            $department === ''
            || strtoupper($department) === '__ALL__'
            || strtolower($department) === 'all'
        ) {
            return '';
        }

        return $department;
    }

    /**
     * @param mixed $issueTypes
     * @return array<int,string>
     */
    protected function normalizeBulkEmailIssueTypes($issueTypes): array
    {
        if (is_string($issueTypes)) {
            $trimmed = trim($issueTypes);
            if ($trimmed === '') {
                return [];
            }
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                $issueTypes = $decoded;
            } else {
                $issueTypes = preg_split('/\s*,\s*/', $trimmed) ?: [];
            }
        }

        if (!is_array($issueTypes)) {
            return [];
        }

        $normalized = [];
        foreach ($issueTypes as $issueType) {
            $issueType = trim((string)$issueType);
            if ($issueType === '') {
                continue;
            }
            $normalized[] = $issueType;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,string>>
     */
    protected function buildBulkEmailEntriesFromRows(array $rows, array $selectedIssueTypes = []): array
    {
        $dedupe = [];
        $entries = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $allIssues = is_array($row['notification_issues'] ?? null)
                ? $row['notification_issues']
                : $this->buildNotificationIssuesForRow($row);
            $activeIssues = array_values(array_filter($allIssues, static function (array $issue): bool {
                return empty($issue['excluded']);
            }));
            if ($selectedIssueTypes !== []) {
                $activeIssues = array_values(array_filter($activeIssues, static function (array $issue) use ($selectedIssueTypes): bool {
                    return in_array((string)($issue['type'] ?? ''), $selectedIssueTypes, true);
                }));
            }

            if ($activeIssues === []) {
                continue;
            }

            $recipientEmail = strtolower(trim((string)($row['email'] ?? '')));
            if ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $staffNo = trim((string)($row['staff_no'] ?? ''));
            $dedupeKey = $staffNo !== '' ? ('staff:' . $staffNo) : ('email:' . $recipientEmail);
            if (isset($dedupe[$dedupeKey])) {
                continue;
            }
            $dedupe[$dedupeKey] = true;

            $issueLabels = array_values(array_map(static function (array $issue): string {
                return trim((string)($issue['label'] ?? ''));
            }, $activeIssues));
            $issueReasons = array_values(array_map(static function (array $issue): string {
                return trim((string)($issue['reason'] ?? ''));
            }, $activeIssues));
            $selfConfirmAction = $this->buildSelfConfirmActionContent(
                $staffNo,
                trim((string)($row['notification_address_hash'] ?? '')),
                array_values(array_map(static function (array $issue): string {
                    return trim((string)($issue['type'] ?? ''));
                }, $activeIssues))
            );

            $entries[] = [
                'site_code' => $this->siteCode,
                'staff_name' => trim((string)($row['name'] ?? '')),
                'staff_no' => $staffNo,
                'recipient_email' => $recipientEmail,
                'department' => trim((string)($row['department'] ?? '')),
                'mailing_address' => trim((string)($row['mailing_address'] ?? '')),
                'distance_label' => trim((string)($row['distance_label'] ?? '')),
                'issue_label' => implode(', ', array_filter($issueLabels)),
                'issue_reason' => $this->buildBulkEmailIssueNarrative($row, $activeIssues, $issueReasons),
                'self_confirm_html' => $selfConfirmAction['html'],
                'self_confirm_text' => $selfConfirmAction['text'],
            ];
        }

        return $entries;
    }

    /**
     * @param array<int,array<string,mixed>> $issues
     * @param array<int,string> $issueReasons
     */
    protected function buildBulkEmailIssueNarrative(array $row, array $issues, array $issueReasons): string
    {
        return $this->formatIssueReasonText($this->buildIssueReasonLines($row, $issues, $issueReasons));
    }

    /**
     * @return array<int,string>
     */
    protected function selfConfirmableIssueTypes(): array
    {
        return ['outside_allowed_region', 'too_far', 'too_close'];
    }

    protected function isSelfConfirmableIssueType(string $issueType): bool
    {
        return in_array(trim($issueType), $this->selfConfirmableIssueTypes(), true);
    }

    /**
     * @param array<int,string> $issueTypes
     * @return array{html:string,text:string}
     */
    protected function buildSelfConfirmActionContent(string $staffNo, string $addressHash, array $issueTypes): array
    {
        if ($staffNo === '' || $addressHash === '') {
            return ['html' => '', 'text' => ''];
        }

        $confirmableIssueTypes = array_values(array_filter(array_unique($issueTypes), function (string $issueType): bool {
            return $this->isSelfConfirmableIssueType($issueType);
        }));
        if ($confirmableIssueTypes === []) {
            return ['html' => '', 'text' => ''];
        }

        $anchorIssueType = (string)($confirmableIssueTypes[0] ?? '');
        $token = $this->issueSelfConfirmToken($staffNo, $addressHash, $anchorIssueType);
        if ($token === '') {
            return ['html' => '', 'text' => ''];
        }

        foreach ($confirmableIssueTypes as $issueType) {
            if ($issueType === $anchorIssueType) {
                continue;
            }
            $this->issueSelfConfirmToken($staffNo, $addressHash, $issueType);
        }

        $note = $this->tr('bdr_distance_self_confirm_email_note', 'Jika alamat ini sememangnya kediaman semasa anda dan digunakan untuk berulang-alik bekerja, sila buat pengesahan melalui pautan berikut.');
        $issueLabels = array_values(array_filter(array_map(function (string $issueType): string {
            return trim($this->buildIssueLabelFromType($issueType));
        }, $confirmableIssueTypes)));
        $issueSummary = implode(', ', $issueLabels);
        $issueLineHtml = '';
        $issueLineText = '';
        if ($issueSummary !== '') {
            $issuePrefix = $this->tr('bdr_distance_self_confirm_issue_summary', 'Isu yang memerlukan pengesahan:');
            $issueLineHtml = '<p style="margin:0 0 0.55rem;"><strong>' . htmlspecialchars($issuePrefix, ENT_QUOTES, 'UTF-8') . '</strong> ' . htmlspecialchars($issueSummary, ENT_QUOTES, 'UTF-8') . '</p>';
            $issueLineText = $issuePrefix . ' ' . $issueSummary . "\n";
        }
        $url = base_url('pages/bdr-notification-confirm.php?token=' . rawurlencode($token));
        $buttonLabel = $this->tr('bdr_distance_self_confirm_email_button', 'Sahkan Alamat Ini');

        return [
            'html' => '<div style="margin:1rem 0 0;"><p style="margin:0 0 0.55rem;">' . htmlspecialchars($note, ENT_QUOTES, 'UTF-8') . '</p>' . $issueLineHtml . '<p style="margin:0.4rem 0 0;"><a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:0.7rem 1.05rem;border-radius:999px;background:#1d4ed8;color:#ffffff;text-decoration:none;font-weight:700;">' . htmlspecialchars($buttonLabel, ENT_QUOTES, 'UTF-8') . '</a></p></div>',
            'text' => $note . "\n" . $issueLineText . $buttonLabel . ': ' . $url,
        ];
    }

    protected function issueSelfConfirmToken(string $staffNo, string $addressHash, string $issueType): string
    {
        if ($staffNo === '' || $addressHash === '' || !$this->isSelfConfirmableIssueType($issueType)) {
            return '';
        }

        $this->ensureBulkEmailTables();

        try {
            $stmt = $this->pdoMysql->prepare(
                'SELECT * FROM ' . self::BULK_EMAIL_EXCLUSION_TABLE . '
                 WHERE f_staffNo = :staff_no
                   AND f_siteCode = :site_code
                   AND f_addressHash = :address_hash
                   AND f_issueType = :issue_type
                 LIMIT 1'
            );
            $stmt->execute([
                ':staff_no' => $staffNo,
                ':site_code' => $this->siteCode,
                ':address_hash' => $addressHash,
                ':issue_type' => $issueType,
            ]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

            $nowTs = time();
            $expiresAt = date('Y-m-d H:i:s', $nowTs + (self::SELF_CONFIRM_TOKEN_TTL_DAYS * 86400));
            if (is_array($existing)) {
                $isActive = (int)($existing['f_isActive'] ?? 0) === 1;
                if ($isActive) {
                    return '';
                }

                $existingToken = trim((string)($existing['f_confirmToken'] ?? ''));
                $existingExpiry = trim((string)($existing['f_tokenExpiresAt'] ?? ''));
                $existingExpiryTs = $existingExpiry !== '' ? strtotime($existingExpiry) : false;
                if ($existingToken !== '' && $existingExpiryTs !== false && $existingExpiryTs >= $nowTs) {
                    return $existingToken;
                }

                $token = bin2hex(random_bytes(32));
                $update = $this->pdoMysql->prepare(
                    'UPDATE ' . self::BULK_EMAIL_EXCLUSION_TABLE . '
                     SET f_source = :source,
                         f_confirmToken = :token,
                         f_tokenExpiresAt = :expires_at,
                         f_confirmedAt = NULL,
                         f_confirmedIp = NULL,
                         f_confirmedUserAgent = NULL,
                         f_confirmedByStaffNo = NULL,
                         f_updatedAt = :updated_at
                     WHERE f_id = :id'
                );
                $update->execute([
                    ':source' => 'SELF_CONFIRM',
                    ':token' => $token,
                    ':expires_at' => $expiresAt,
                    ':updated_at' => date('Y-m-d H:i:s'),
                    ':id' => (int)($existing['f_id'] ?? 0),
                ]);

                return $token;
            }

            $token = bin2hex(random_bytes(32));
            $now = date('Y-m-d H:i:s');
            $insert = $this->pdoMysql->prepare(
                'INSERT INTO ' . self::BULK_EMAIL_EXCLUSION_TABLE . '
                (f_staffNo, f_siteCode, f_addressHash, f_issueType, f_reason, f_notes, f_isActive, f_source, f_confirmToken, f_tokenExpiresAt, f_createdBy, f_createdName, f_createdAt, f_updatedAt)
                VALUES
                (:staff_no, :site_code, :address_hash, :issue_type, NULL, NULL, 0, :source, :token, :expires_at, :created_by, :created_name, :created_at, :updated_at)'
            );
            $insert->execute([
                ':staff_no' => $staffNo,
                ':site_code' => $this->siteCode,
                ':address_hash' => $addressHash,
                ':issue_type' => $issueType,
                ':source' => 'SELF_CONFIRM',
                ':token' => $token,
                ':expires_at' => $expiresAt,
                ':created_by' => 'BDR-EMAIL',
                ':created_name' => 'BDR Email',
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);

            return $token;
        } catch (Throwable $e) {
            error_log('[BdrDistanceController][self_confirm_token] ' . $e->getMessage());
            return '';
        }
    }

    /**
     * @param array<int,array<string,mixed>> $issues
     * @param array<int,string> $issueReasons
     * @return array<int,string>
     */
    protected function buildIssueReasonLines(array $row, array $issues = [], array $issueReasons = []): array
    {
        $lines = [];

        foreach (array_values(array_unique(array_filter(array_map(static function (string $reason): string {
            return trim($reason);
        }, $issueReasons)))) as $reason) {
            $lines[] = $reason;
        }

        $providerMatched = trim((string)($row['provider_display_name'] ?? $row['matched_query'] ?? ''));
        if ($providerMatched !== '') {
            $lines[] = $this->tr('bdr_distance_email_reason_provider_match', 'Alamat yang dipadankan oleh Google Maps:') . ' ' . $providerMatched;
        }

        $matchQuality = trim((string)($row['match_quality'] ?? ''));
        if ($matchQuality !== '') {
            $lines[] = $this->tr('bdr_distance_email_reason_match_quality', 'Kualiti padanan:') . ' ' . $matchQuality;
        }

        $distanceLabel = trim((string)($row['distance_label'] ?? ''));
        if ($distanceLabel !== '') {
            $lines[] = $this->tr('bdr_distance_email_reason_distance_label', 'Jarak semasa yang dipaparkan:') . ' ' . $distanceLabel;
        }

        $detectedState = trim((string)($row['detected_state'] ?? ''));
        if ($detectedState !== '') {
            $lines[] = $this->tr('bdr_distance_email_reason_detected_state', 'Negeri dikesan:') . ' ' . $detectedState;
        }

        $selectedState = trim((string)($row['selected_state'] ?? ''));
        $issueTypes = array_values(array_filter(array_map(static function (array $issue): string {
            return trim((string)($issue['type'] ?? ''));
        }, $issues)));
        if (in_array('state_mismatch', $issueTypes, true)) {
            if ($selectedState !== '') {
                $lines[] = $this->tr('bdr_distance_email_reason_selected_state', 'Negeri dalam rekod:') . ' ' . $selectedState;
            }
            if ($detectedState !== '') {
                $lines[] = $this->tr('bdr_distance_email_reason_address_state', 'Negeri yang dikesan daripada alamat:') . ' ' . $detectedState;
            }
        }

        $issueLabels = array_values(array_filter(array_map(static function (array $issue): string {
            return trim((string)($issue['label'] ?? ''));
        }, $issues)));
        if ($issueLabels !== []) {
            $lines[] = $this->tr('bdr_distance_email_reason_issue_category', 'Kategori isu:') . ' ' . implode(', ', $issueLabels);
        }

        return array_values(array_unique(array_filter($lines, static function (string $line): bool {
            return trim($line) !== '';
        })));
    }

    /**
     * @param array<int,string> $lines
     */
    protected function formatIssueReasonText(array $lines): string
    {
        if ($lines === []) {
            return $this->trDestination('bdr_distance_email_reason_default', 'Maklumat alamat semasa memerlukan semakan semula bagi membolehkan sistem mengesahkan jarak rumah ke {{office_label}} dengan tepat.');
        }

        return implode("\n", array_map(static function (string $line): string {
            return '- ' . rtrim(trim($line), ". \t\n\r\0\x0B") . '.';
        }, $lines));
    }

    /**
     * @param array<int,string> $lines
     */
    protected function formatIssueReasonHtml(array $lines): string
    {
        if ($lines === []) {
            return '<p>' . htmlspecialchars($this->trDestination('bdr_distance_email_reason_default', 'Maklumat alamat semasa memerlukan semakan semula bagi membolehkan sistem mengesahkan jarak rumah ke {{office_label}} dengan tepat.'), ENT_QUOTES, 'UTF-8') . '</p>';
        }

        $items = array_map(static function (string $line): string {
            return '<li>' . htmlspecialchars(rtrim(trim($line), ". \t\n\r\0\x0B") . '.', ENT_QUOTES, 'UTF-8') . '</li>';
        }, $lines);

        return '<ul style="margin:0.35rem 0 0 1.15rem;padding:0;">' . implode('', $items) . '</ul>';
    }

    /**
     * @return array<int,string>
     */
    protected function extractIssueReasonLines(string $reason): array
    {
        $reason = trim($reason);
        if ($reason === '') {
            return [];
        }

        $parts = preg_split('/\r\n|\r|\n/', $reason) ?: [];
        $lines = [];
        foreach ($parts as $part) {
            $line = trim((string)$part);
            if ($line === '') {
                continue;
            }
            $line = preg_replace('/^[-*•]\s*/u', '', $line) ?? $line;
            $lines[] = trim($line);
        }

        return array_values(array_unique(array_filter($lines, static function (string $line): bool {
            return $line !== '';
        })));
    }

    protected function buildIssueReasonFromType(string $issueType): string
    {
        switch ($issueType) {
            case 'no_address':
                return $this->trDestination('bdr_distance_email_reason_no_address', 'Tiada alamat surat menyurat direkodkan dalam sistem, jadi jarak rumah ke {{office_label}} tidak dapat dikira.');
            case 'incomplete_address':
                return $this->tr('bdr_distance_email_reason_incomplete', 'Alamat surat menyurat tidak lengkap atau tidak cukup jelas untuk sistem menentukan lokasi rumah dengan tepat.');
            case 'address_changed':
                return $this->tr('bdr_distance_email_reason_address_changed', 'Alamat surat menyurat semasa didapati berbeza daripada rekod terdahulu, jadi pengesahan jarak perlu dibuat semula.');
            case 'review_location':
                return $this->trDestination('bdr_distance_email_reason_review_location', 'Padanan lokasi yang dijumpai adalah meragukan atau tidak cukup tepat, jadi jarak rumah ke {{office_label}} tidak dapat disahkan dengan yakin.');
            case 'state_mismatch':
                return $this->tr('bdr_distance_email_reason_state_mismatch', 'Negeri yang dipilih dalam rekod tidak sepadan dengan alamat semasa yang direkodkan. Sila semak dan kemas kini maklumat negeri yang betul.');
            case 'too_close':
                return $this->tr('bdr_distance_email_reason_too_close', 'Jarak alamat yang direkodkan terlalu dekat dengan pejabat, jadi semakan manual diperlukan untuk memastikan alamat tersebut benar-benar alamat kediaman semasa.');
            case 'too_far':
                return $this->tr('bdr_distance_email_reason_too_far', 'Jarak alamat yang direkodkan adalah melebihi julat semakan biasa, jadi pengesahan manual diperlukan bagi memastikan alamat tersebut adalah betul.');
            case 'outside_allowed_region':
                return $this->tr('bdr_distance_email_reason_outside_region', 'Alamat yang direkodkan berada di luar kawasan kebiasaan semakan iaitu Selangor, Wilayah Persekutuan Kuala Lumpur, atau Putrajaya.');
            default:
                return $this->trDestination('bdr_distance_email_reason_default', 'Maklumat alamat semasa memerlukan semakan semula bagi membolehkan sistem mengesahkan jarak rumah ke {{office_label}} dengan tepat.');
        }
    }

    /**
     * @return array<int,string>
     */
    protected function allowedNotificationStates(): array
    {
        return [
            'SELANGOR',
            'WILAYAH PERSEKUTUAN KUALA LUMPUR',
            'WILAYAH PERSEKUTUAN PUTRAJAYA',
        ];
    }

    protected function buildNotificationAddressHash(array $row): string
    {
        $mailingAddress = trim((string)($row['mailing_address'] ?? ''));
        $addressInputs = is_array($row['address_inputs'] ?? null) ? $row['address_inputs'] : [];
        $normalizedAddress = $mailingAddress;
        if ($addressInputs !== []) {
            $normalizedAddress = function_exists('format_alamat_standard')
                ? format_alamat_standard($addressInputs)
                : $mailingAddress;
        }
        $normalizedAddress = trim((string)$normalizedAddress);
        if ($normalizedAddress === '') {
            return '';
        }

        return hash('sha256', $normalizedAddress);
    }

    protected function resolveSelectedNotificationStateForRow(array $row): string
    {
        $addressInputs = is_array($row['address_inputs'] ?? null) ? $row['address_inputs'] : [];
        $state = trim((string)($addressInputs['negeri'] ?? ''));
        if ($state === '') {
            return '';
        }

        return $this->extractStateFromAddress($state);
    }

    protected function resolveDetectedNotificationStateForRow(array $row): string
    {
        $candidates = [
            trim((string)($row['mailing_address'] ?? '')),
            trim((string)($row['provider_display_name'] ?? '')),
            trim((string)($row['matched_query'] ?? '')),
        ];

        foreach ($candidates as $candidate) {
            $state = $this->extractStateFromAddress($candidate);
            if ($state !== '') {
                return $state;
            }
        }

        return '';
    }

    protected function isNotificationStateAllowed(string $state): bool
    {
        return $state !== '' && in_array($state, $this->allowedNotificationStates(), true);
    }

    /**
     * @param array<string,true> $excludedIssueMap
     * @return array<int,array<string,mixed>>
     */
    protected function buildNotificationIssuesForRow(array $row, array $excludedIssueMap = []): array
    {
        $issues = [];
        $seen = [];
        $addressStatus = strtoupper(trim((string)($row['address_status'] ?? 'VALID')));
        $distanceKm = isset($row['distance_km']) && is_numeric($row['distance_km']) ? (float)$row['distance_km'] : null;
        $isAddressChanged = !empty($row['is_address_changed']);
        $isReviewLocation = !empty($row['is_review_location']);
        $selectedState = $this->resolveSelectedNotificationStateForRow($row);
        $detectedState = $this->resolveDetectedNotificationStateForRow($row);

        $appendIssue = function (string $type) use (&$issues, &$seen, $excludedIssueMap): void {
            if (isset($seen[$type])) {
                return;
            }
            $exclusionMeta = $excludedIssueMap[$type] ?? null;
            $isExcluded = $exclusionMeta !== null;
            if (!is_array($exclusionMeta)) {
                $exclusionMeta = [];
            }
            $seen[$type] = true;
            $issues[] = [
                'type' => $type,
                'label' => $this->buildIssueLabelFromType($type),
                'reason' => $this->buildIssueReasonFromType($type),
                'excluded' => $isExcluded,
                'exclusion_source' => (string)($exclusionMeta['source'] ?? ''),
                'confirmed_at' => (string)($exclusionMeta['confirmed_at'] ?? ''),
                'confirmed_by_staff_no' => (string)($exclusionMeta['confirmed_by_staff_no'] ?? ''),
            ];
        };

        if ($addressStatus === 'EMPTY') {
            $appendIssue('no_address');
        }
        if ($addressStatus === 'INVALID') {
            $appendIssue('incomplete_address');
        }
        if ($isAddressChanged) {
            $appendIssue('address_changed');
        }
        if ($isReviewLocation) {
            $appendIssue('review_location');
        }

        if ($addressStatus === 'VALID' && !$isAddressChanged && !$isReviewLocation && $distanceKm !== null) {
            if ($distanceKm < self::NOTIFY_TOO_CLOSE_KM) {
                $appendIssue('too_close');
            }
            if ($distanceKm > self::NOTIFY_TOO_FAR_KM) {
                $appendIssue('too_far');
            }
        }

        if (
            $addressStatus === 'VALID'
            && $selectedState !== ''
            && $detectedState !== ''
            && $selectedState !== $detectedState
            && $this->isNotificationStateAllowed($detectedState)
        ) {
            $appendIssue('state_mismatch');
        }

        if ($addressStatus === 'VALID' && $detectedState !== '' && !$this->isNotificationStateAllowed($detectedState)) {
            $appendIssue('outside_allowed_region');
        }

        return $issues;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<string,array<string,array<string,string>>>
     */
    protected function loadNotificationExclusionMap(array $rows): array
    {
        $this->ensureBulkEmailTables();

        $staffNos = [];
        $addressHashes = [];
        foreach ($rows as $row) {
            $staffNo = trim((string)($row['staff_no'] ?? ''));
            $addressHash = $this->buildNotificationAddressHash($row);
            if ($staffNo === '' || $addressHash === '') {
                continue;
            }
            $staffNos[] = $staffNo;
            $addressHashes[] = $addressHash;
        }

        $staffNos = array_values(array_unique(array_filter($staffNos)));
        $addressHashes = array_values(array_unique(array_filter($addressHashes)));
        if ($staffNos === [] || $addressHashes === []) {
            return [];
        }

        $staffPlaceholders = implode(',', array_fill(0, count($staffNos), '?'));
        $hashPlaceholders = implode(',', array_fill(0, count($addressHashes), '?'));
        $stmt = $this->pdoMysql->prepare(
            'SELECT f_staffNo, f_addressHash, f_issueType, f_source, f_confirmedAt, f_confirmedByStaffNo
             FROM ' . self::BULK_EMAIL_EXCLUSION_TABLE . '
             WHERE f_isActive = 1
             AND f_siteCode = ?
             AND f_staffNo IN (' . $staffPlaceholders . ')
             AND f_addressHash IN (' . $hashPlaceholders . ')'
        );
        $stmt->execute(array_merge([$this->siteCode], $staffNos, $addressHashes));

        $map = [];
        while ($record = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $staffNo = trim((string)($record['f_staffNo'] ?? ''));
            $addressHash = trim((string)($record['f_addressHash'] ?? ''));
            $issueType = trim((string)($record['f_issueType'] ?? ''));
            if ($staffNo === '' || $addressHash === '' || $issueType === '') {
                continue;
            }
            $key = $staffNo . '|' . $addressHash;
            if (!isset($map[$key])) {
                $map[$key] = [];
            }
            $map[$key][$issueType] = [
                'source' => trim((string)($record['f_source'] ?? '')),
                'confirmed_at' => trim((string)($record['f_confirmedAt'] ?? '')),
                'confirmed_by_staff_no' => trim((string)($record['f_confirmedByStaffNo'] ?? '')),
            ];
        }

        return $map;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    protected function enrichRowsWithNotificationMeta(array $rows): array
    {
        if ($rows === []) {
            return $rows;
        }

        $exclusionMap = $this->loadNotificationExclusionMap($rows);

        foreach ($rows as $index => $row) {
            $staffNo = trim((string)($row['staff_no'] ?? ''));
            $addressHash = $this->buildNotificationAddressHash($row);
            $exclusions = ($staffNo !== '' && $addressHash !== '' && isset($exclusionMap[$staffNo . '|' . $addressHash]))
                ? $exclusionMap[$staffNo . '|' . $addressHash]
                : [];
            $issues = $this->buildNotificationIssuesForRow($row, $exclusions);
            $activeIssues = array_values(array_filter($issues, static function (array $issue): bool {
                return empty($issue['excluded']);
            }));
            $primaryIssue = $activeIssues[0] ?? ($issues[0] ?? null);
            $rows[$index]['notification_address_hash'] = $addressHash;
            $rows[$index]['selected_state'] = $this->resolveSelectedNotificationStateForRow($row);
            $rows[$index]['detected_state'] = $this->resolveDetectedNotificationStateForRow($row);
            $rows[$index]['notification_issues'] = $issues;
            $rows[$index]['active_notification_issues'] = $activeIssues;
            $rows[$index]['excluded_notification_issues'] = array_values(array_map(static function (array $issue): string {
                return (string)($issue['type'] ?? '');
            }, array_filter($issues, static function (array $issue): bool {
                return !empty($issue['excluded']);
            })));
            $rows[$index]['confirmed_notification_issues'] = array_values(array_map(static function (array $issue): string {
                return (string)($issue['type'] ?? '');
            }, array_filter($issues, static function (array $issue): bool {
                return !empty($issue['excluded'])
                    && strtoupper(trim((string)($issue['exclusion_source'] ?? ''))) === 'SELF_CONFIRM';
            })));
            $rows[$index]['show_email_reminder'] = count($activeIssues) > 0;
            $rows[$index]['issue_type'] = is_array($primaryIssue) ? (string)($primaryIssue['type'] ?? 'none') : 'none';
            $rows[$index]['issue_label'] = is_array($primaryIssue) ? (string)($primaryIssue['label'] ?? '') : '';
        }

        return $rows;
    }

    protected function findSelfConfirmRecordByToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $this->ensureBulkEmailTables();
        $stmt = $this->pdoMysql->prepare(
            'SELECT *
             FROM ' . self::BULK_EMAIL_EXCLUSION_TABLE . '
             WHERE f_confirmToken = :token
             LIMIT 1'
        );
        $stmt->execute([':token' => $token]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($record) ? $record : null;
    }

    protected function loadNotificationRowByStaffAndAddressHash(string $staffNo, string $addressHash): ?array
    {
        if ($staffNo === '' || $addressHash === '' || !function_exists('distance_result_table_name')) {
            return null;
        }

        try {
            $stmt = $this->pdoMysql->prepare(
                'SELECT f_stafID, f_siteCode, f_homeAddressHash, f_homeAddress, f_homeAddressJson, f_distanceKm, f_source, f_routeProvider, f_matchQuality, f_resultJson, f_status, f_lastCalculatedAt
                 FROM ' . distance_result_table_name() . '
                 WHERE f_stafID = :staff_no
                   AND f_siteCode = :site_code
                   AND f_homeAddressHash = :address_hash
                 ORDER BY f_lastCalculatedAt DESC, f_cacheID DESC
                 LIMIT 1'
            );
            $stmt->execute([
                ':staff_no' => $staffNo,
                ':site_code' => $this->siteCode,
                ':address_hash' => $addressHash,
            ]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($record)) {
                return null;
            }

            $row = $this->buildApiRowFromDistanceCacheRecord($record, $this->loadStaffSiteRecordForApi($staffNo));
            if (!is_array($row)) {
                return null;
            }

            $rows = $this->enrichRowsWithNotificationMeta([$row]);
            return is_array($rows[0] ?? null) ? $rows[0] : $row;
        } catch (Throwable $e) {
            error_log('[BdrDistanceController][self_confirm_row] ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function buildSelfConfirmPageData(string $token): array
    {
        $record = $this->findSelfConfirmRecordByToken($token);
        if (!is_array($record)) {
            return ['status' => 'invalid_token'];
        }

        $this->setActiveSiteContext((string)($record['f_siteCode'] ?? $this->siteCode));
        $siteContext = [
            'site_code' => $this->siteCode,
            'office_label' => $this->officeLabel,
            'office_address' => $this->officeAddress,
        ];

        $confirmedAt = trim((string)($record['f_confirmedAt'] ?? ''));
        if ($confirmedAt !== '') {
            return [
                'status' => 'already_confirmed',
                'record' => $record,
                'staff_no' => trim((string)($record['f_staffNo'] ?? '')),
                'address_hash' => trim((string)($record['f_addressHash'] ?? '')),
                'confirmed_at' => $confirmedAt,
            ] + $siteContext;
        }

        $expiresAt = trim((string)($record['f_tokenExpiresAt'] ?? ''));
        $expiresTs = $expiresAt !== '' ? strtotime($expiresAt) : false;
        if ($expiresTs === false || $expiresTs < time()) {
            return ['status' => 'expired', 'record' => $record] + $siteContext;
        }

        $anchorIssueType = trim((string)($record['f_issueType'] ?? ''));
        if (!$this->isSelfConfirmableIssueType($anchorIssueType)) {
            return ['status' => 'not_allowed', 'record' => $record] + $siteContext;
        }

        $staffNo = trim((string)($record['f_staffNo'] ?? ''));
        $addressHash = trim((string)($record['f_addressHash'] ?? ''));
        $row = $this->loadNotificationRowByStaffAndAddressHash($staffNo, $addressHash);
        $activeIssues = is_array($row['active_notification_issues'] ?? null)
            ? array_values(array_filter($row['active_notification_issues'], function (array $issue): bool {
                return $this->isSelfConfirmableIssueType(trim((string)($issue['type'] ?? '')));
            }))
            : [];
        $activeIssueTypes = array_values(array_map(static function (array $issue): string {
            return trim((string)($issue['type'] ?? ''));
        }, $activeIssues));
        $activeIssueLabels = array_values(array_filter(array_map(static function (array $issue): string {
            return trim((string)($issue['label'] ?? ''));
        }, $activeIssues)));

        if ($row !== null && $activeIssueTypes === []) {
            return [
                'status' => 'issue_resolved',
                'record' => $record,
                'row' => $row,
            ] + $siteContext;
        }

        return [
            'status' => 'ready',
            'record' => $record,
            'row' => $row,
            'issue_type' => $anchorIssueType,
            'issue_label' => $activeIssueLabels !== [] ? implode(', ', $activeIssueLabels) : $this->buildIssueLabelFromType($anchorIssueType),
            'issue_labels' => $activeIssueLabels !== [] ? $activeIssueLabels : [$this->buildIssueLabelFromType($anchorIssueType)],
            'issue_types' => $activeIssueTypes !== [] ? $activeIssueTypes : [$anchorIssueType],
            'staff_no' => $staffNo,
            'address_hash' => $addressHash,
            'expires_at' => $expiresAt,
            'declaration_text' => $this->tr('bdr_distance_self_confirm_declaration', 'Saya mengesahkan alamat ini ialah kediaman semasa saya dan digunakan untuk berulang-alik bekerja. Sebarang maklumat palsu boleh dikenakan tindakan.'),
        ] + $siteContext;
    }

    /**
     * @return array<string,mixed>
     */
    public function submitSelfConfirmToken(string $token, string $confirmedByStaffNo, string $ipAddress, string $userAgent, bool $declared): array
    {
        $pageData = $this->buildSelfConfirmPageData($token);
        if (($pageData['status'] ?? '') !== 'ready') {
            return $pageData;
        }

        if (!$declared) {
            return ['status' => 'declaration_required'] + $pageData;
        }

        $record = is_array($pageData['record'] ?? null) ? $pageData['record'] : null;
        if (!is_array($record)) {
            return ['status' => 'invalid_token'];
        }

        $staffNo = trim((string)($record['f_staffNo'] ?? ''));
        if ($staffNo === '' || trim($confirmedByStaffNo) === '' || trim($confirmedByStaffNo) !== $staffNo) {
            return ['status' => 'staff_mismatch'] + $pageData;
        }

        $issueTypes = array_values(array_filter(array_map(static function ($issueType): string {
            return trim((string)$issueType);
        }, is_array($pageData['issue_types'] ?? null) ? $pageData['issue_types'] : [])));
        if ($issueTypes === []) {
            return ['status' => 'issue_resolved'] + $pageData;
        }

        $issueTypePlaceholders = [];
        $executeParams = [
            ':source' => 'SELF_CONFIRM',
            ':reason' => $this->tr('bdr_distance_self_confirm_declaration', 'Saya mengesahkan alamat ini ialah kediaman semasa saya dan digunakan untuk berulang-alik bekerja. Sebarang maklumat palsu boleh dikenakan tindakan.'),
            ':confirmed_at' => $now = date('Y-m-d H:i:s'),
            ':confirmed_ip' => trim($ipAddress) !== '' ? trim($ipAddress) : null,
            ':confirmed_user_agent' => trim($userAgent) !== '' ? trim($userAgent) : null,
            ':confirmed_by_staff_no' => $staffNo,
            ':updated_at' => $now,
            ':staff_no' => $staffNo,
            ':site_code' => $this->siteCode,
            ':address_hash' => trim((string)($record['f_addressHash'] ?? '')),
        ];
        foreach (array_values($issueTypes) as $index => $issueType) {
            $placeholder = ':issue_type_' . $index;
            $issueTypePlaceholders[] = $placeholder;
            $executeParams[$placeholder] = $issueType;
        }
        $stmt = $this->pdoMysql->prepare(
            'UPDATE ' . self::BULK_EMAIL_EXCLUSION_TABLE . '
             SET f_isActive = 1,
                 f_source = :source,
                 f_reason = :reason,
                 f_confirmedAt = :confirmed_at,
                 f_confirmedIp = :confirmed_ip,
                 f_confirmedUserAgent = :confirmed_user_agent,
                 f_confirmedByStaffNo = :confirmed_by_staff_no,
                 f_tokenExpiresAt = NULL,
                 f_updatedAt = :updated_at
             WHERE f_staffNo = :staff_no
               AND f_siteCode = :site_code
               AND f_addressHash = :address_hash
               AND f_issueType IN (' . implode(',', $issueTypePlaceholders) . ')'
        );
        $stmt->execute($executeParams);

        return ['status' => 'confirmed'] + $pageData;
    }

    /**
     * @return array{delivery_email:string,mode:string,test_recipient:string}
     */
    protected function resolveReminderDeliveryRecipient(string $recipientEmail): array
    {
        $testRecipient = $this->getEmailReminderTestRecipient();
        if ($testRecipient !== '' && filter_var($testRecipient, FILTER_VALIDATE_EMAIL)) {
            return [
                'delivery_email' => $testRecipient,
                'mode' => 'TEST',
                'test_recipient' => $testRecipient,
            ];
        }

        return [
            'delivery_email' => trim($recipientEmail),
            'mode' => 'DIRECT',
            'test_recipient' => '',
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    protected function getBulkEmailJobById(int $jobId): ?array
    {
        $stmt = $this->pdoMysql->prepare('SELECT * FROM ' . self::BULK_EMAIL_JOB_TABLE . ' WHERE f_id = :id AND f_siteCode = :site_code LIMIT 1');
        $stmt->execute([
            ':id' => $jobId,
            ':site_code' => $this->siteCode,
        ]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($job) ? $job : null;
    }

    /**
     * @return array<string,mixed>
     */
    protected function buildBulkEmailJobSummary(array $job): array
    {
        $total = (int)($job['f_totalCount'] ?? 0);
        $processed = (int)($job['f_processedCount'] ?? 0);
        $percent = $total > 0 ? (int)round(($processed / $total) * 100) : 0;

        return [
            'id' => (int)($job['f_id'] ?? 0),
            'token' => (string)($job['f_jobToken'] ?? ''),
            'status' => (string)($job['f_status'] ?? 'QUEUED'),
            'site_code' => (string)($job['f_siteCode'] ?? $this->siteCode),
            'scope_department' => (string)($job['f_scopeDepartment'] ?? ''),
            'total_count' => $total,
            'processed_count' => $processed,
            'success_count' => (int)($job['f_successCount'] ?? 0),
            'failed_count' => (int)($job['f_failedCount'] ?? 0),
            'skipped_count' => (int)($job['f_skippedCount'] ?? 0),
            'delivery_mode' => (string)($job['f_deliveryMode'] ?? 'TEST'),
            'test_recipient' => (string)($job['f_testRecipient'] ?? ''),
            'last_message' => (string)($job['f_lastMessage'] ?? ''),
            'started_at' => (string)($job['f_startedAt'] ?? ''),
            'finished_at' => (string)($job['f_finishedAt'] ?? ''),
            'progress_percent' => max(0, min(100, $percent)),
            'is_finished' => in_array((string)($job['f_status'] ?? ''), ['COMPLETED', 'FAILED', 'CANCELLED'], true),
        ];
    }

    protected function processBulkEmailJobBatch(int $jobId): int
    {
        $job = $this->getBulkEmailJobById($jobId);
        if (!is_array($job)) {
            throw new RuntimeException('Bulk email job not found.');
        }

        $status = strtoupper(trim((string)($job['f_status'] ?? 'QUEUED')));
        if (in_array($status, ['COMPLETED', 'FAILED', 'CANCELLED'], true)) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        if ($status === 'QUEUED') {
            $stmt = $this->pdoMysql->prepare(
                'UPDATE ' . self::BULK_EMAIL_JOB_TABLE . '
                 SET f_status = :status, f_startedAt = :started_at, f_lastMessage = :last_message, f_updatedAt = :updated_at
                 WHERE f_id = :id'
            );
            $stmt->execute([
                ':status' => 'RUNNING',
                ':started_at' => $now,
                ':last_message' => $this->tr('bdr_distance_bulk_email_status_running', 'Bulk email sedang dihantar secara berperingkat.'),
                ':updated_at' => $now,
                ':id' => $jobId,
            ]);
        }

        $stmt = $this->pdoMysql->prepare(
            'SELECT * FROM ' . self::BULK_EMAIL_ITEM_TABLE . '
             WHERE f_jobId = :job_id
               AND f_siteCode = :site_code
               AND f_status = :status
             ORDER BY f_id ASC
             LIMIT ' . self::BULK_EMAIL_BATCH_SIZE
        );
        $stmt->execute([
            ':job_id' => $jobId,
            ':site_code' => $this->siteCode,
            ':status' => 'PENDING',
        ]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $processedInBatch = 0;
        foreach ($items as $item) {
            $itemId = (int)($item['f_id'] ?? 0);
            if ($itemId <= 0) {
                continue;
            }

            $processedInBatch++;
            $deliveryEmail = trim((string)($item['f_deliveryEmail'] ?? ''));
            $payload = $this->buildEmailReminderPayloadFromData([
                'staff_name' => (string)($item['f_staffName'] ?? ''),
                'staff_no' => (string)($item['f_staffNo'] ?? ''),
                'recipient_email' => (string)($item['f_recipientEmail'] ?? ''),
                'department' => (string)($item['f_department'] ?? ''),
                'mailing_address' => (string)($item['f_mailingAddress'] ?? ''),
                'distance_label' => (string)($item['f_distanceLabel'] ?? ''),
                'issue_label' => (string)($item['f_issueLabel'] ?? ''),
                'issue_reason' => (string)($item['f_issueReason'] ?? ''),
                'self_confirm_action_html' => (string)($item['f_selfConfirmHtml'] ?? ''),
                'self_confirm_action_text' => (string)($item['f_selfConfirmText'] ?? ''),
                'site_code' => (string)($item['f_siteCode'] ?? $this->siteCode),
            ]);

            $sent = false;
            $lastError = '';

            if ($deliveryEmail === '' || !filter_var($deliveryEmail, FILTER_VALIDATE_EMAIL)) {
                $lastError = $this->tr('bdr_distance_bulk_email_invalid_delivery_email', 'Alamat emel penghantaran tidak sah.');
            } else {
                try {
                    $this->deliverReminderEmail($payload, $deliveryEmail);
                    $sent = true;
                } catch (Throwable $e) {
                    $lastError = $e->getMessage();
                    error_log('[BdrDistanceController][bulk_email_item] ' . $e->getMessage());
                }
            }

            $stmtUpdate = $this->pdoMysql->prepare(
                'UPDATE ' . self::BULK_EMAIL_ITEM_TABLE . '
                 SET f_status = :status,
                     f_attemptCount = f_attemptCount + 1,
                     f_lastError = :last_error,
                     f_sentAt = :sent_at,
                     f_updatedAt = :updated_at
                 WHERE f_id = :id'
            );
            $stmtUpdate->execute([
                ':status' => $sent ? 'SENT' : 'FAILED',
                ':last_error' => $lastError !== '' ? $lastError : null,
                ':sent_at' => $sent ? date('Y-m-d H:i:s') : null,
                ':updated_at' => date('Y-m-d H:i:s'),
                ':id' => $itemId,
            ]);

            if (self::BULK_EMAIL_DELAY_US > 0) {
                usleep(self::BULK_EMAIL_DELAY_US);
            }
        }

        $counts = $this->refreshBulkEmailJobCounts($jobId);
        $remaining = max(0, $counts['total_count'] - $counts['processed_count']);
        $isFinished = $remaining === 0;
        $stmtJob = $this->pdoMysql->prepare(
            'UPDATE ' . self::BULK_EMAIL_JOB_TABLE . '
             SET f_status = :status,
                 f_processedCount = :processed_count,
                 f_successCount = :success_count,
                 f_failedCount = :failed_count,
                 f_skippedCount = :skipped_count,
                 f_lastMessage = :last_message,
                 f_finishedAt = :finished_at,
                 f_updatedAt = :updated_at
             WHERE f_id = :id'
        );
        $stmtJob->execute([
            ':status' => $isFinished ? 'COMPLETED' : 'RUNNING',
            ':processed_count' => $counts['processed_count'],
            ':success_count' => $counts['success_count'],
            ':failed_count' => $counts['failed_count'],
            ':skipped_count' => 0,
            ':last_message' => $isFinished
                ? $this->tr('bdr_distance_bulk_email_status_completed', 'Bulk email job telah selesai.')
                : $this->tr('bdr_distance_bulk_email_status_running', 'Bulk email sedang dihantar secara berperingkat.'),
            ':finished_at' => $isFinished ? date('Y-m-d H:i:s') : null,
            ':updated_at' => date('Y-m-d H:i:s'),
            ':id' => $jobId,
        ]);

        return $processedInBatch;
    }

    /**
     * @return array{total_count:int,processed_count:int,success_count:int,failed_count:int}
     */
    protected function refreshBulkEmailJobCounts(int $jobId): array
    {
        $stmt = $this->pdoMysql->prepare(
            'SELECT
                COUNT(*) AS total_count,
                SUM(CASE WHEN f_status IN (\'SENT\', \'FAILED\', \'SKIPPED\') THEN 1 ELSE 0 END) AS processed_count,
                SUM(CASE WHEN f_status = \'SENT\' THEN 1 ELSE 0 END) AS success_count,
                SUM(CASE WHEN f_status = \'FAILED\' THEN 1 ELSE 0 END) AS failed_count
             FROM ' . self::BULK_EMAIL_ITEM_TABLE . '
             WHERE f_jobId = :job_id
               AND f_siteCode = :site_code'
        );
        $stmt->execute([
            ':job_id' => $jobId,
            ':site_code' => $this->siteCode,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total_count' => (int)($row['total_count'] ?? 0),
            'processed_count' => (int)($row['processed_count'] ?? 0),
            'success_count' => (int)($row['success_count'] ?? 0),
            'failed_count' => (int)($row['failed_count'] ?? 0),
        ];
    }

    /**
     * @param array{recipient_email:string,variables:array<string,mixed>,context:array<string,mixed>} $payload
     */
    protected function deliverReminderEmail(array $payload, string $deliveryEmail): void
    {
        $template = $this->resolveEmailReminderTemplate();
        $placeholderModel = new EmailPlaceholder($this->pdoMysql);
        $renderService = new EmailTemplateRenderService($placeholderModel);
        $rendered = $renderService->renderTemplate($template, $payload['variables'], $payload['context']);

        $mailer = Mailer::fromConfig($this->pdoMysql);
        $sent = $mailer->send(
            $deliveryEmail,
            (string)($rendered['subject'] ?? ''),
            (string)($rendered['html'] ?? ''),
            (string)($rendered['text'] ?? '')
        );

        if (!$sent) {
            throw new RuntimeException($mailer->getLastError() ?: 'Email could not be sent.');
        }
    }

    public function getEmailReminderTestRecipient(): string
    {
        $candidates = [
            $_ENV['BDR_EMAIL_TEST_RECIPIENT'] ?? null,
            getenv('BDR_EMAIL_TEST_RECIPIENT') ?: null,
        ];

        foreach ($candidates as $candidate) {
            $email = trim((string)$candidate);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }

        return '';
    }

    /**
     * @return array<string,mixed>
     */
    protected function resolveEmailReminderTemplate(): array
    {
        $templateModel = new EmailTemplate($this->pdoMysql);
        $template = $templateModel->findByCode('STAFF_REMINDER_BDR_ADDRESS');
        if (is_array($template) && strtoupper(trim((string)($template['f_status'] ?? 'DRAFT'))) === 'ACTIVE') {
            return $this->normalizeEmailReminderTemplateIntro($template);
        }

        return $this->normalizeEmailReminderTemplateIntro([
            'f_templateCode' => 'INLINE_BDR_ADDRESS_REMINDER',
            'f_templateName' => $this->tr('bdr_distance_email_template_inline', 'Reminder Semakan Alamat BDR'),
            'subject_template' => $this->tr('bdr_distance_email_subject', 'Peringatan Pengesahan Alamat Bekerja Dari Rumah (BDR) untuk {{recipient_name}}'),
            'body_html' => $this->tr('bdr_distance_email_body_html', '<p>Assalamualaikum dan salam sejahtera {{recipient_name}},</p><p>{{email_intro}}</p><p><strong>No. Staf:</strong> {{staff_no}}<br><strong>Jabatan:</strong> {{department_name}}<br><strong>Alamat Semasa:</strong> {{current_address}}<br><strong>Status Semakan:</strong> {{status_label}}<br><strong>Jarak Dipaparkan:</strong> {{distance_label}}</p><div><strong>Punca Semakan Diperlukan:</strong>{{issue_reason_html}}</div>{{primary_action_html}}<p>{{guidance_text}}</p><p>Terima kasih.</p>'),
            'body_text' => $this->tr('bdr_distance_email_body_text', "Assalamualaikum dan salam sejahtera {{recipient_name}},\n\n{{email_intro}}\n\nNo. Staf: {{staff_no}}\nJabatan: {{department_name}}\nAlamat Semasa: {{current_address}}\nStatus Semakan: {{status_label}}\nJarak Dipaparkan: {{distance_label}}\nPunca Semakan Diperlukan:\n{{issue_reason_text}}\n\n{{primary_action_text}}\n\n{{guidance_text}}\n\nTerima kasih."),
        ]);
    }

    /**
     * @param array<string,mixed> $template
     * @return array<string,mixed>
     */
    protected function normalizeEmailReminderTemplateIntro(array $template): array
    {
        $htmlKeys = ['f_bodyHtml', 'body_html'];
        $textKeys = ['f_bodyText', 'body_text'];

        $htmlNeedles = [
            'Emel ini adalah bagi memaklumkan bahawa sistem tidak dapat mengenal pasti jarak perjalanan dari alamat rumah semasa anda ke UPNM bagi tujuan semakan rekod <strong>Bekerja Dari Rumah (BDR)</strong>.',
            'This email is to inform you that the system was unable to determine the travel distance from your current home address to UPNM for <strong>Work From Home (BDR)</strong> record verification.',
        ];
        $textNeedles = [
            'Emel ini adalah bagi memaklumkan bahawa sistem tidak dapat mengenal pasti jarak perjalanan dari alamat rumah semasa anda ke UPNM bagi tujuan semakan rekod Bekerja Dari Rumah (BDR).',
            'This email is to inform you that the system was unable to determine the travel distance from your current home address to UPNM for Work From Home (BDR) record verification.',
        ];

        foreach ($htmlKeys as $key) {
            if (!isset($template[$key]) || !is_string($template[$key]) || str_contains($template[$key], '{{email_intro}}')) {
                continue;
            }
            $template[$key] = str_replace($htmlNeedles, '{{email_intro}}', $template[$key]);
        }

        foreach ($textKeys as $key) {
            if (!isset($template[$key]) || !is_string($template[$key]) || str_contains($template[$key], '{{email_intro}}')) {
                continue;
            }
            $template[$key] = str_replace($textNeedles, '{{email_intro}}', $template[$key]);
        }

        return $template;
    }

    protected function extractDepartmentOptions(array $rows): array
    {
        $departments = [];

        foreach ($rows as $row) {
            $department = trim((string)($row['department'] ?? ''));
            if ($department === '') {
                continue;
            }
            $departments[$department] = true;
        }

        $options = array_keys($departments);
        natcasesort($options);

        return array_values($options);
    }

    protected function filterRowsByDepartment(array $rows, string $department): array
    {
        $department = trim($department);
        if ($department === '') {
            return $rows;
        }

        return array_values(array_filter($rows, function (array $row) use ($department): bool {
            return trim((string)($row['department'] ?? '')) === $department;
        }));
    }

    protected function normalizeKey(string $value): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/', '', trim($value)) ?? '');
    }

    protected function resolveCurrentDepartmentFromSybase(): string
    {
        $staffNo = trim((string)($this->profile['f_nopekerja'] ?? $_SESSION['f_nopekerja'] ?? ''));
        $staffId = trim((string)($this->profile['f_stafID'] ?? $_SESSION['f_stafID'] ?? ''));

        if ($staffNo === '' && $staffId === '') {
            return '';
        }

        try {
            $pdoSybase = Database::pdoSybaseStaff();

            if ($staffNo !== '') {
                $stmt = $pdoSybase->prepare("
                    SELECT TOP 1 LTRIM(RTRIM(jabatansemasa)) AS jabatan
                    FROM v630staf_service_skim_all
                    WHERE LTRIM(RTRIM(nopekerja)) = :staff_no
                ");
                $stmt->execute([':staff_no' => $staffNo]);
                $jabatan = trim((string)($stmt->fetchColumn() ?: ''));
                if ($jabatan !== '') {
                    return $jabatan;
                }
            }

            if ($staffId !== '') {
                $stmt = $pdoSybase->prepare("
                    SELECT TOP 1 LTRIM(RTRIM(jabatansemasa)) AS jabatan
                    FROM v630staf_service_skim_all
                    WHERE LTRIM(RTRIM(idpekerja)) = :staff_id
                ");
                $stmt->execute([':staff_id' => $staffId]);
                $jabatan = trim((string)($stmt->fetchColumn() ?: ''));
                if ($jabatan !== '') {
                    return $jabatan;
                }
            }
        } catch (Throwable $e) {
            error_log('[BdrDistanceController] Failed resolving current department from Sybase: ' . $e->getMessage());
        }

        return '';
    }

    protected function resolveCurrentDepartmentFromSybaseByDepartmentCode(): string
    {
        $departmentCode = trim((string)($this->profile['f_jabatanKod'] ?? $_SESSION['f_jabatanKod'] ?? ''));
        if ($departmentCode === '') {
            return '';
        }

        try {
            $pdoSybase = Database::pdoSybaseStaff();
            $stmt = $pdoSybase->prepare("
                SELECT TOP 1 LTRIM(RTRIM(jabatansemasa)) AS jabatan
                FROM v630staf_service_skim_all
                WHERE LTRIM(RTRIM(kdjbtnsemasa)) = :department_code
                  AND LTRIM(RTRIM(jabatansemasa)) <> ''
                ORDER BY jabatansemasa
            ");
            $stmt->execute([':department_code' => $departmentCode]);
            return trim((string)($stmt->fetchColumn() ?: ''));
        } catch (Throwable $e) {
            error_log('[BdrDistanceController] Failed resolving current department by department code: ' . $e->getMessage());
        }

        return '';
    }

    protected function formatCoordsLabel(?array $coords): string
    {
        if (!is_array($coords) || !isset($coords['lat'], $coords['lon'])) {
            return '';
        }

        return number_format((float)$coords['lat'], 6, '.', '') . ', ' . number_format((float)$coords['lon'], 6, '.', '');
    }

    protected function assessMatchQuality(string $inputAddress, string $matchedQuery): string
    {
        $inputAddress = strtoupper(trim($inputAddress));
        $matchedQuery = strtoupper(trim($matchedQuery));
        if ($inputAddress === '' || $matchedQuery === '') {
            return 'NONE';
        }

        $inputState = $this->extractStateFromAddress($inputAddress);
        $matchedState = $this->extractStateFromAddress($matchedQuery);
        if ($inputState !== '' && $matchedState !== '' && $inputState !== $matchedState) {
            return 'LOW';
        }

        $inputPostcode = $this->extractPostcodeForMatchQuality($inputAddress);
        $matchedPostcode = $this->extractPostcodeForMatchQuality($matchedQuery);
        $postcodeMismatch = $inputPostcode !== '' && $matchedPostcode !== '' && $inputPostcode !== $matchedPostcode;

        $tokenize = static function (string $text): array {
            preg_match_all('/[A-Z0-9\/-]{3,}/', $text, $matches);
            $tokens = array_values(array_unique($matches[0] ?? []));
            $stop = ['MALAYSIA', 'WILAYAH', 'PERSEKUTUAN', 'KUALA', 'LUMPUR', 'JALAN'];
            return array_values(array_filter($tokens, static fn($token) => !in_array($token, $stop, true)));
        };

        $inputTokens = $tokenize($inputAddress);
        $matchedTokens = $tokenize($matchedQuery);
        $overlap = array_intersect($inputTokens, $matchedTokens);
        $overlapCount = count($overlap);

        if ($postcodeMismatch) {
            return $overlapCount >= 2 ? 'MEDIUM' : 'LOW';
        }

        if (
            $inputPostcode !== ''
            && $matchedPostcode !== ''
            && $inputPostcode === $matchedPostcode
            && $this->isGenericProviderMatch($inputAddress, $matchedQuery)
        ) {
            return 'MEDIUM';
        }

        if ($inputPostcode !== '' && $matchedPostcode !== '' && $inputPostcode === $matchedPostcode && $overlapCount >= 2) {
            return 'HIGH';
        }

        if ($inputPostcode !== '' && $matchedPostcode !== '' && $inputPostcode === $matchedPostcode && $overlapCount >= 1) {
            return 'MEDIUM';
        }

        if ($overlapCount >= 2) {
            return 'MEDIUM';
        }

        if ($inputPostcode !== '' && $matchedPostcode !== '' && $inputPostcode === $matchedPostcode) {
            return 'MEDIUM';
        }

        return 'LOW';
    }

    protected function resolveAddressForMatchComparison(string $address, array $addressData = []): string
    {
        $address = trim($address);
        $hasStructuredAddress = implode('', array_map(static function ($value): string {
            return trim((string)$value);
        }, $addressData)) !== '';

        if (!$hasStructuredAddress) {
            return $address;
        }

        if (function_exists('alamat_parse_components')) {
            $normalized = alamat_parse_components($addressData);
            $normalizedAddress = trim((string)($normalized['alamat_standard'] ?? ''));
            if ($normalizedAddress !== '') {
                return $normalizedAddress;
            }
        }

        if (function_exists('format_alamat_standard')) {
            $normalizedAddress = trim((string)format_alamat_standard($addressData));
            if ($normalizedAddress !== '') {
                return $normalizedAddress;
            }
        }

        return $address;
    }

    protected function resolveMatchDebugReason(string $inputAddress, string $matchedQuery, string $matchQuality, string $default = 'ok'): string
    {
        if (strtoupper(trim($matchQuality)) === 'LOW') {
            return 'low_match_quality';
        }

        $inputAddress = strtoupper(trim($inputAddress));
        $matchedQuery = strtoupper(trim($matchedQuery));
        $inputPostcode = $this->extractPostcodeForMatchQuality($inputAddress);
        $matchedPostcode = $this->extractPostcodeForMatchQuality($matchedQuery);

        if ($inputPostcode !== '' && $matchedPostcode !== '' && $inputPostcode !== $matchedPostcode) {
            return 'postcode_mismatch';
        }

        return $default;
    }

    protected function extractPostcodeForMatchQuality(string $address): string
    {
        $address = strtoupper(trim($address));
        if ($address === '') {
            return '';
        }

        if (preg_match_all('/\b\d{5}\b/', $address, $matches, PREG_OFFSET_CAPTURE) < 1) {
            return '';
        }

        $postcodes = [];
        foreach ($matches[0] as $match) {
            $postcode = (string)$match[0];
            $offset = (int)$match[1];
            $prefix = substr($address, max(0, $offset - 16), 16);
            if (preg_match('/\b(?:LOT|NO|PT|HS|H\s*S|GERAN|GM|PM|PN)\s+$/u', $prefix) === 1) {
                continue;
            }
            $postcodes[] = $postcode;
        }

        return $postcodes !== [] ? (string)end($postcodes) : '';
    }

    protected function isGenericProviderMatch(string $inputAddress, string $matchedQuery): bool
    {
        $inputTokens = $this->extractSpecificAddressTokensForMatchQuality($inputAddress);
        $matchedTokens = $this->extractSpecificAddressTokensForMatchQuality($matchedQuery);
        $overlapCount = count(array_intersect($inputTokens, $matchedTokens));
        if (count($inputTokens) < 2 || $overlapCount >= 2) {
            return false;
        }

        $hasPremise = preg_match('/\b(?:LOT|NO|PT|HS|H\s*S|GERAN|GM|PM|PN)\s+\d{2,}\b/u', strtoupper($inputAddress)) === 1;
        $areaTokens = [];
        if (preg_match_all('/\b(?:KAMPUNG|KG|TAMAN|TMN|PEKAN)\s+([A-Z0-9\/-]{3,})\b/u', strtoupper($inputAddress), $matches) >= 1) {
            $areaTokens = array_values(array_unique($matches[1] ?? []));
        }
        $missingAreaToken = $areaTokens !== [] && array_intersect($areaTokens, $matchedTokens) === [];

        return $hasPremise || $missingAreaToken;
    }

    protected function extractSpecificAddressTokensForMatchQuality(string $address): array
    {
        preg_match_all('/[A-Z0-9\/-]{3,}/', strtoupper(trim($address)), $matches);
        $tokens = array_values(array_unique($matches[0] ?? []));
        $stop = [
            'MALAYSIA',
            'WILAYAH',
            'PERSEKUTUAN',
            'KUALA',
            'LUMPUR',
            'JALAN',
            'LORONG',
            'TAMAN',
            'KAMPUNG',
            'PEKAN',
            'BANDAR',
            'LOT',
            'NO',
            'NEGERI',
            'SEMBILAN',
            'JOHOR',
            'KEDAH',
            'KELANTAN',
            'MELAKA',
            'PAHANG',
            'PERAK',
            'PERLIS',
            'PULAU',
            'PINANG',
            'SABAH',
            'SARAWAK',
            'SELANGOR',
            'TERENGGANU',
            'PUTRAJAYA',
            'LABUAN',
        ];

        return array_values(array_filter($tokens, function (string $token) use ($stop): bool {
            return !in_array($token, $stop, true) && preg_match('/^\d+$/', $token) !== 1;
        }));
    }

    protected function extractStateFromAddress(string $address): string
    {
        $normalized = preg_replace('/[^A-Z]+/', ' ', strtoupper(trim($address))) ?? '';
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? '';
        $normalized = trim($normalized);
        if ($normalized === '') {
            return '';
        }

        $stateMap = [
            'WILAYAH PERSEKUTUAN KUALA LUMPUR' => 'WILAYAH PERSEKUTUAN KUALA LUMPUR',
            'KUALA LUMPUR' => 'WILAYAH PERSEKUTUAN KUALA LUMPUR',
            'W P KUALA LUMPUR' => 'WILAYAH PERSEKUTUAN KUALA LUMPUR',
            'WP KUALA LUMPUR' => 'WILAYAH PERSEKUTUAN KUALA LUMPUR',
            'WPKL' => 'WILAYAH PERSEKUTUAN KUALA LUMPUR',
            'WILAYAH PERSEKUTUAN PUTRAJAYA' => 'WILAYAH PERSEKUTUAN PUTRAJAYA',
            'PUTRAJAYA' => 'WILAYAH PERSEKUTUAN PUTRAJAYA',
            'W P PUTRAJAYA' => 'WILAYAH PERSEKUTUAN PUTRAJAYA',
            'WP PUTRAJAYA' => 'WILAYAH PERSEKUTUAN PUTRAJAYA',
            'WILAYAH PERSEKUTUAN LABUAN' => 'WILAYAH PERSEKUTUAN LABUAN',
            'LABUAN' => 'WILAYAH PERSEKUTUAN LABUAN',
            'JOHOR' => 'JOHOR',
            'KEDAH' => 'KEDAH',
            'KELANTAN' => 'KELANTAN',
            'MELAKA' => 'MELAKA',
            'NEGERI SEMBILAN' => 'NEGERI SEMBILAN',
            'PAHANG' => 'PAHANG',
            'PERAK' => 'PERAK',
            'PERLIS' => 'PERLIS',
            'PULAU PINANG' => 'PULAU PINANG',
            'SABAH' => 'SABAH',
            'SARAWAK' => 'SARAWAK',
            'SELANGOR' => 'SELANGOR',
            'TERENGGANU' => 'TERENGGANU',
        ];

        foreach ($stateMap as $needle => $canonical) {
            if (str_contains($normalized, $needle)) {
                return $canonical;
            }
        }

        return '';
    }

    // Audit hook placeholder:
    // Use this section for create/update/delete or configuration changes when audit is required.
}
