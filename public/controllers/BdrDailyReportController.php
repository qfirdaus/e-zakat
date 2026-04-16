<?php
declare(strict_types=1);

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';

class BdrDailyReportController
{
    protected const TASK_EXECUTION_STATUS_LABELS = [
        'SELESAI' => 'Selesai',
        'TIDAK_SELESAI' => 'Tidak Selesai',
        'DALAM_PROSES' => 'Dalam Proses',
        'DITANGGUHKAN' => 'Ditangguhkan',
        'MENUNGGU_MAKLUM_BALAS' => 'Menunggu Maklum Balas',
    ];

    protected const REPORT_ENTRY_TABLE = 'tbl_bdr_report_entry';
    protected const REPORT_ITEM_TABLE = 'tbl_bdr_report_item';
    protected const REPORT_SOURCE_TABLE = 'tbl_bdr_report_source';

    public string $lang = 'ms';
    public array $profile = [];
    public array $rows = [];
    public string $currentLoginID = '';
    public string $currentStaffID = '';
    public string $apiUrl = '';
    public string $pageAlert = 'info';
    public string $pageMessage = '';
    public string $tableEmptyMessage = 'Tiada rekod BDR diluluskan ditemui.';
    public bool $reportStorageReady = false;
    protected PDO $pdoMysql;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->lang = $_SESSION['lang'] ?? 'ms';
        $this->tableEmptyMessage = $this->t('bdr_daily_report_empty_table', 'Tiada rekod BDR diluluskan ditemui.');
        $this->pdoMysql = Database::getInstance('mysql')->getConnection();
        $this->currentLoginID = trim((string)($_SESSION['f_loginID'] ?? ''));
        $this->currentStaffID = trim((string)($_SESSION['f_stafID'] ?? ''));
        $this->reportStorageReady = $this->hasReportStorageTables();
        $this->profile = $this->loadProfile();
        if ($this->currentLoginID === '') {
            $this->currentLoginID = $this->resolveFallbackLoginID();
            if ($this->currentLoginID !== '') {
                $_SESSION['f_loginID'] = $this->currentLoginID;
            }
        }
        $this->applyUserTheme();
        $this->loadRows();
    }

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
        $line = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        $targetPath = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        $written = @file_put_contents($targetPath, $line, FILE_APPEND | LOCK_EX);
        if ($written !== false) {
            return;
        }

        @file_put_contents(
            rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'app.log',
            '[BDR_DAILY_REPORT_DEBUG] ' . $line,
            FILE_APPEND | LOCK_EX
        );

        error_log('[BDR_DAILY_REPORT_DEBUG] ' . rtrim($line));
    }

    protected function debugIdentityMatching(array $context): void
    {
        if (!$this->debugLogEnabled('BDR_DAILY_REPORT_DEBUG_LOG_ENABLED') && !$this->debugLogEnabled('BDR_DEBUG_LOGS')) {
            return;
        }

        $this->appendDebugLog('bdr-daily-report-identity.log', $context);
    }

    protected function exposeIdentityDebugSummary(array $context): string
    {
        if (!$this->debugLogEnabled('BDR_DAILY_REPORT_DEBUG_LOG_ENABLED') && !$this->debugLogEnabled('BDR_DEBUG_LOGS')) {
            return '';
        }

        $parts = [];

        $currentLoginId = trim((string)($context['current_login_id'] ?? ''));
        if ($currentLoginId !== '') {
            $parts[] = 'login_id=' . $currentLoginId;
        }

        $currentStaffId = trim((string)($context['current_staff_id'] ?? ''));
        if ($currentStaffId !== '') {
            $parts[] = 'staff_id=' . $currentStaffId;
        }

        $identityCandidates = is_array($context['identity_candidates'] ?? null) ? $context['identity_candidates'] : [];
        if ($identityCandidates !== []) {
            $parts[] = 'candidates=' . implode(',', array_slice(array_map('strval', $identityCandidates), 0, 10));
        }

        $apiRecordTotal = isset($context['api_record_total']) ? (int)$context['api_record_total'] : 0;
        if ($apiRecordTotal > 0) {
            $parts[] = 'api_total=' . $apiRecordTotal;
        }

        $apiStaffSamples = is_array($context['api_staff_id_samples'] ?? null) ? $context['api_staff_id_samples'] : [];
        if ($apiStaffSamples !== []) {
            $parts[] = 'api_samples=' . implode(',', array_slice(array_map('strval', $apiStaffSamples), 0, 10));
        }

        return $parts === [] ? '' : ' [debug: ' . implode(' | ', $parts) . ']';
    }

    protected function loadProfile(): array
    {
        $userModel = new User($this->pdoMysql);

        if ($this->currentLoginID !== '') {
            $profile = $userModel->getProfileByLoginID($this->currentLoginID) ?: [];
            if ($profile) {
                return $profile;
            }
        }

        $fStafID = trim((string)($_SESSION['f_stafID'] ?? ''));
        return $fStafID !== '' ? ($userModel->getProfile($fStafID) ?: []) : [];
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

    public function setLang(string $lang): void
    {
        $this->lang = trim($lang) !== '' ? trim($lang) : 'ms';
        $_SESSION['lang'] = $this->lang;
    }

    protected function t(string $key, string $fallback): string
    {
        if (function_exists('__')) {
            $value = __($key);
            if ($value !== $key && $value !== null && $value !== '') {
                return (string)$value;
            }
        }

        return $fallback;
    }

    protected static function ts(string $key, string $fallback): string
    {
        if (function_exists('__')) {
            $value = __($key);
            if ($value !== $key && $value !== null && $value !== '') {
                return (string)$value;
            }
        }

        return $fallback;
    }

    public function handleRequest(): void
    {
        $action = strtolower(trim((string)($_REQUEST['action'] ?? '')));
        if ($action === '') {
            return;
        }

        if (!$this->isAjaxRequest()) {
            return;
        }

        if (!$this->reportStorageReady) {
            $this->jsonError($this->t('bdr_daily_report_storage_not_ready', 'Storage laporan harian belum tersedia. Sila jalankan migration modul BDR Report terlebih dahulu.'), 503);
        }

        $this->validateCsrfToken();

        switch ($action) {
            case 'entry-load':
                $this->handleEntryLoad();
                break;

            case 'entry-save-draft':
                $this->handleEntrySave(false);
                break;

            case 'entry-submit':
                $this->handleEntrySave(true);
                break;

            default:
                $this->jsonError($this->t('bdr_daily_report_invalid_request_action', 'Tindakan permintaan tidak sah.'), 404);
        }
    }

    protected function loadRows(): void
    {
        $identityCandidates = $this->resolveIdentityCandidates();
        if ($identityCandidates === []) {
            $this->rows = [];
            $this->pageAlert = 'warning';
            $this->pageMessage = $this->t('bdr_daily_report_missing_login_message', 'ID login semasa tidak ditemui. Rekod BDR tidak dapat dimuatkan.');
            $this->tableEmptyMessage = $this->t('bdr_daily_report_missing_login_table', 'ID login semasa tidak tersedia.');
            $this->debugIdentityMatching([
                'event' => 'missing_identity_candidates',
                'session_login_id' => (string)($_SESSION['f_loginID'] ?? ''),
                'session_staff_id' => (string)($_SESSION['f_stafID'] ?? ''),
                'session_nopekerja' => (string)($_SESSION['f_nopekerja'] ?? ''),
            ]);
            return;
        }

        try {
            $records = $this->fetchApprovedRecords();
            $matchedRecords = $this->filterRecordsByIdentityCandidates($records, $identityCandidates);
            $this->rows = $this->decorateRowsWithEntryState($this->normalizeRows($matchedRecords));

            if ($this->rows === []) {
                $apiStaffIds = [];
                foreach ($records as $record) {
                    if (!is_array($record)) {
                        continue;
                    }

                    $staffId = trim((string)($record['f_stafID'] ?? ''));
                    if ($staffId === '') {
                        continue;
                    }

                    $normalizedStaffId = $this->normalizeIdentityValue($staffId);
                    if ($normalizedStaffId === '' || isset($apiStaffIds[$normalizedStaffId])) {
                        continue;
                    }

                    $apiStaffIds[$normalizedStaffId] = $staffId;
                    if (count($apiStaffIds) >= 25) {
                        break;
                    }
                }

                $debugContext = [
                    'event' => 'identity_match_empty',
                    'current_login_id' => $this->currentLoginID,
                    'current_staff_id' => $this->currentStaffID,
                    'identity_candidates' => $identityCandidates,
                    'api_record_total' => count($records),
                    'api_staff_id_samples' => array_values($apiStaffIds),
                ];
                $this->debugIdentityMatching($debugContext);
                $this->pageAlert = 'warning';
                $this->pageMessage = $this->t('bdr_daily_report_no_records_login_message', 'Tiada rekod BDR diluluskan ditemui untuk ID login semasa.')
                    . $this->exposeIdentityDebugSummary($debugContext);
                $this->tableEmptyMessage = $this->t('bdr_daily_report_no_records_login_table', 'Tiada rekod BDR diluluskan untuk ID login ini.');
                return;
            }

            $this->debugIdentityMatching([
                'event' => 'identity_match_success',
                'current_login_id' => $this->currentLoginID,
                'current_staff_id' => $this->currentStaffID,
                'identity_candidates' => $identityCandidates,
                'matched_record_total' => count($matchedRecords),
                'expanded_row_total' => count($this->rows),
            ]);

            $this->pageAlert = 'success';
            $this->pageMessage = '';
            $this->tableEmptyMessage = $this->t('bdr_daily_report_empty_table', 'Tiada rekod BDR diluluskan ditemui.');
        } catch (Throwable $exception) {
            $this->rows = [];
            $this->pageAlert = 'danger';
            $this->pageMessage = $this->t('bdr_daily_report_load_failed_message', 'Data BDR approved tidak dapat dimuatkan buat masa ini.');
            $this->tableEmptyMessage = $this->t('bdr_daily_report_load_failed_table', 'Data BDR approved tidak dapat dimuatkan.');
            error_log('[BdrDailyReportController] ' . $exception->getMessage());
        }
    }

    protected function fetchApprovedRecords(): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL extension is not available.');
        }

        $this->apiUrl = $this->getEnvString('BDR_DAILY_REPORT_API_URL', 'https://attendance.upnm.edu.my/_api/get_bdr_approved.php');
        $apiUsername = $this->getEnvString('BDR_DAILY_REPORT_API_USERNAME', 'admin_apiBDR');
        $apiPassword = $this->getEnvString('BDR_DAILY_REPORT_API_PASSWORD', '@piBDR2016!!');

        if ($this->apiUrl === '') {
            throw new RuntimeException('BDR daily report API URL is empty.');
        }

        $curlHandle = curl_init();
        curl_setopt_array($curlHandle, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $apiUsername . ':' . $apiPassword,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($curlHandle);
        $curlError = curl_error($curlHandle);
        $httpCode = (int)curl_getinfo($curlHandle, CURLINFO_RESPONSE_CODE);
        curl_close($curlHandle);

        if ($response === false) {
            throw new RuntimeException('Attendance API request failed: ' . $curlError);
        }

        if ($httpCode !== 200) {
            throw new RuntimeException('Attendance API returned HTTP ' . $httpCode . '.');
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Attendance API returned invalid JSON payload.');
        }

        $records = $decoded['data'] ?? [];
        if (!is_array($records)) {
            throw new RuntimeException('Attendance API payload missing data array.');
        }

        $normalized = [];
        foreach ($records as $record) {
            if (is_array($record)) {
                $normalized[] = $record;
            }
        }

        return $normalized;
    }

    protected function filterRecordsByIdentityCandidates(array $records, array $identityCandidates): array
    {
        if ($identityCandidates === []) {
            return [];
        }

        $candidateMap = array_fill_keys($identityCandidates, true);

        return array_values(array_filter($records, function (array $record) use ($candidateMap): bool {
            $recordStaffId = $this->normalizeIdentityValue((string)($record['f_stafID'] ?? ''));
            if ($recordStaffId === '') {
                return false;
            }

            return isset($candidateMap[$recordStaffId]);
        }));
    }

    protected function resolveFallbackLoginID(): string
    {
        $candidates = [
            (string)($this->profile['f_loginID'] ?? ''),
            (string)($_SESSION['f_stafID'] ?? ''),
            (string)($_SESSION['f_nopekerja'] ?? ''),
            (string)($this->profile['f_stafID'] ?? ''),
            (string)($this->profile['f_nopekerja'] ?? ''),
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    protected function resolveIdentityCandidates(): array
    {
        $rawCandidates = [
            $this->currentLoginID,
            $this->currentStaffID,
            (string)($_SESSION['f_nopekerja'] ?? ''),
            (string)($this->profile['f_loginID'] ?? ''),
            (string)($this->profile['f_stafID'] ?? ''),
            (string)($this->profile['f_nopekerja'] ?? ''),
        ];

        $normalized = [];
        foreach ($rawCandidates as $candidate) {
            $value = $this->normalizeIdentityValue((string)$candidate);
            if ($value === '') {
                continue;
            }

            $normalized[$value] = $value;
        }

        return array_values($normalized);
    }

    protected function normalizeIdentityValue(string $value): string
    {
        $normalized = trim($value);
        if ($normalized === '') {
            return '';
        }

        $normalized = str_replace(["\u{2010}", "\u{2011}", "\u{2012}", "\u{2013}", "\u{2014}", "\u{2212}"], '-', $normalized);

        return function_exists('mb_strtolower') ? mb_strtolower($normalized, 'UTF-8') : strtolower($normalized);
    }

    protected function normalizeRows(array $records): array
    {
        $rows = [];

        foreach ($records as $record) {
            foreach ($this->expandRecordByDate($record) as $row) {
                $rows[] = $row;
            }
        }

        usort($rows, static function (array $left, array $right): int {
            $dateSort = strcmp((string)($right['date_sort'] ?? ''), (string)($left['date_sort'] ?? ''));
            if ($dateSort !== 0) {
                return $dateSort;
            }

            return strcmp((string)($left['reference_no'] ?? ''), (string)($right['reference_no'] ?? ''));
        });

        return $rows;
    }

    protected function expandRecordByDate(array $record): array
    {
        $startDate = $this->parseApiDate((string)($record['f_tarikh_mula'] ?? ''));
        $endDate = $this->parseApiDate((string)($record['f_tarikh_tamat'] ?? ''));

        if (!$startDate || !$endDate) {
            error_log('[BdrDailyReportController] Skip record with invalid date range. Reference: ' . trim((string)($record['f_norujukan'] ?? '-')));
            return [];
        }

        if ($endDate < $startDate) {
            $endDate = $startDate;
        }

        $rows = [];
        $currentDate = $startDate;

        while ($currentDate <= $endDate) {
            $clockInTime = $this->normalizeFullTimeLabel((string)($record['f_clock_in'] ?? '')) ?: '-';
            $clockOutTime = $this->normalizeFullTimeLabel((string)($record['f_clock_out'] ?? '')) ?: '-';
            $clockInLatitude = trim((string)($record['f_clock_in_latitude'] ?? ''));
            $clockInLongitude = trim((string)($record['f_clock_in_longitude'] ?? ''));
            $clockOutLatitude = trim((string)($record['f_clock_out_latitude'] ?? ''));
            $clockOutLongitude = trim((string)($record['f_clock_out_longitude'] ?? ''));
            $approvalName = $this->buildApprovalName($record);

            $rows[] = [
                'date' => $currentDate->format('d/m/Y'),
                'date_sort' => $currentDate->format('Y-m-d'),
                'reference_no' => $this->fallbackDash((string)($record['f_norujukan'] ?? '')),
                'category' => $this->fallbackDash((string)($record['f_kategori'] ?? '')),
                'approval_flag' => trim((string)($record['f_flag_kj'] ?? '')),
                'approval_comment' => trim((string)($record['f_komenkj'] ?? '')),
                'approval_director_name' => trim((string)($record['f_namakj'] ?? '')),
                'approval_name' => $approvalName,
                'clock_in' => $clockInTime,
                'clock_out' => $clockOutTime,
                'clock_in_latitude' => $clockInLatitude,
                'clock_in_longitude' => $clockInLongitude,
                'clock_in_label' => $this->buildCoordinateLabel($clockInLongitude, $clockInLatitude),
                'clock_out_latitude' => $clockOutLatitude,
                'clock_out_longitude' => $clockOutLongitude,
                'clock_out_label' => $this->buildCoordinateLabel($clockOutLongitude, $clockOutLatitude),
                'source_bdr_id' => trim((string)($record['f_bdrID'] ?? '')),
                'source_staff_id' => trim((string)($record['f_stafID'] ?? '')),
                'source_staff_name' => trim((string)($record['f_nama'] ?? '')),
                'modal_tabs' => $this->buildModalTabs($record, $currentDate),
            ];

            $currentDate = $currentDate->modify('+1 day');
        }

        return $rows;
    }

    protected function buildApprovalName(array $record): string
    {
        $name = trim((string)($record['f_namakj'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        $staffNo = trim((string)($record['f_nokj'] ?? ''));
        return $staffNo !== '' ? $staffNo : '-';
    }

    protected function buildModalTabs(array $record, DateTimeImmutable $currentDate): array
    {
        $tabs = [
            [
                'key' => 'rekod',
                'title' => $this->t('bdr_daily_report_modal_tab_record', 'Maklumat Rekod'),
                'fields' => [
                    ['key' => 'date', 'label' => $this->t('bdr_daily_report_modal_field_date', 'Tarikh'), 'value' => $currentDate->format('d/m/Y')],
                    ['key' => 'reference_no', 'label' => $this->t('bdr_daily_report_modal_field_reference', 'No. Rujukan'), 'value' => $record['f_norujukan'] ?? ''],
                    ['key' => 'created_at', 'label' => $this->t('bdr_daily_report_modal_field_created_at', 'Created At'), 'value' => $record['f_created_at'] ?? ''],
                    ['key' => 'updated_at', 'label' => $this->t('bdr_daily_report_modal_field_updated_at', 'Updated At'), 'value' => $record['f_updated_at'] ?? ''],
                ],
            ],
            [
                'key' => 'pemohon',
                'title' => $this->t('bdr_daily_report_modal_tab_applicant', 'Maklumat Pemohon'),
                'fields' => [
                    ['key' => 'staff_id', 'label' => $this->t('bdr_daily_report_modal_field_staff_id', 'Staf ID'), 'value' => $record['f_stafID'] ?? ''],
                    ['key' => 'name', 'label' => $this->t('bdr_daily_report_modal_field_name', 'Nama'), 'value' => $record['f_nama'] ?? ''],
                    ['key' => 'category', 'label' => $this->t('bdr_daily_report_modal_field_category', 'Kategori'), 'value' => $record['f_kategori'] ?? ''],
                    ['key' => 'reason', 'label' => $this->t('bdr_daily_report_modal_field_reason', 'Sebab'), 'value' => $record['f_sebab'] ?? ''],
                    ['key' => 'start_date', 'label' => $this->t('bdr_daily_report_modal_field_start_date', 'Tarikh Mula'), 'value' => $record['f_tarikh_mula'] ?? ''],
                    ['key' => 'end_date', 'label' => $this->t('bdr_daily_report_modal_field_end_date', 'Tarikh Tamat'), 'value' => $record['f_tarikh_tamat'] ?? ''],
                    ['key' => 'start_time', 'label' => $this->t('bdr_daily_report_modal_field_start_time', 'Masa Mula'), 'value' => $record['f_masa_mula'] ?? ''],
                    ['key' => 'end_time', 'label' => $this->t('bdr_daily_report_modal_field_end_time', 'Masa Tamat'), 'value' => $record['f_masa_tamat'] ?? ''],
                ],
            ],
            [
                'key' => 'kelulusan',
                'title' => $this->t('bdr_daily_report_modal_tab_approval', 'Kelulusan'),
                'fields' => [
                    [
                        'key' => 'head_department',
                        'label' => $this->t('bdr_daily_report_modal_field_head_department', 'Ketua Jabatan'),
                        'value' => $this->buildKetuaJabatanLabel($record),
                        'full_width' => true,
                    ],
                    ['key' => 'head_comment', 'label' => $this->t('bdr_daily_report_modal_field_head_comment', 'Komen Ketua Jabatan'), 'value' => $record['f_komenkj'] ?? ''],
                    ['key' => 'head_date', 'label' => $this->t('bdr_daily_report_modal_field_head_date', 'Tarikh Ketua Jabatan'), 'value' => $record['f_tarikhkj'] ?? ''],
                    ['key' => 'approval_flag', 'label' => $this->t('bdr_daily_report_modal_field_approval_flag', 'Flag Kelulusan KJ'), 'value' => $record['f_flag_kj'] ?? ''],
                ],
            ],
            [
                'key' => 'lokasi',
                'title' => $this->t('bdr_daily_report_modal_tab_location', 'Lokasi dan Jarak'),
                'fields' => [
                    ['key' => 'work_location', 'label' => $this->t('bdr_daily_report_modal_field_work_location', 'Lokasi Bekerja'), 'value' => $record['f_lokasi_bekerja'] ?? ''],
                    ['key' => 'distance', 'label' => $this->t('bdr_daily_report_modal_field_distance', 'Jarak'), 'value' => $record['f_jarak'] ?? ''],
                    ['key' => 'work_location_latitude', 'label' => $this->t('bdr_daily_report_modal_field_work_location_latitude', 'Lokasi Bekerja Latitude'), 'value' => $record['f_lokasi_bekerja_latitude'] ?? ''],
                    ['key' => 'work_location_longitude', 'label' => $this->t('bdr_daily_report_modal_field_work_location_longitude', 'Lokasi Bekerja Longitude'), 'value' => $record['f_lokasi_bekerja_longitude'] ?? ''],
                    ['key' => 'clock_in', 'label' => $this->t('bdr_daily_report_modal_field_clock_in', 'Masa Masuk'), 'value' => $this->buildModalClockDateTimeValue($currentDate, (string)($record['f_clock_in'] ?? ''))],
                    ['key' => 'clock_out', 'label' => $this->t('bdr_daily_report_modal_field_clock_out', 'Masa Keluar'), 'value' => $this->buildModalClockDateTimeValue($currentDate, (string)($record['f_clock_out'] ?? ''))],
                    ['key' => 'clock_in_latitude', 'label' => $this->t('bdr_daily_report_modal_field_clock_in_latitude', 'Clock In Latitude'), 'value' => $record['f_clock_in_latitude'] ?? ''],
                    ['key' => 'clock_in_longitude', 'label' => $this->t('bdr_daily_report_modal_field_clock_in_longitude', 'Clock In Longitude'), 'value' => $record['f_clock_in_longitude'] ?? ''],
                    ['key' => 'location_in', 'label' => $this->t('bdr_daily_report_modal_field_location_in', 'Lokasi In'), 'value' => $record['f_lokasi_in'] ?? ''],
                    ['key' => 'location_out', 'label' => $this->t('bdr_daily_report_modal_field_location_out', 'Lokasi Out'), 'value' => $record['f_lokasi_out'] ?? ''],
                    ['key' => 'clock_out_latitude', 'label' => $this->t('bdr_daily_report_modal_field_clock_out_latitude', 'Clock Out Latitude'), 'value' => $record['f_clock_out_latitude'] ?? ''],
                    ['key' => 'clock_out_longitude', 'label' => $this->t('bdr_daily_report_modal_field_clock_out_longitude', 'Clock Out Longitude'), 'value' => $record['f_clock_out_longitude'] ?? ''],
                ],
            ],
            [
                'key' => 'sokongan',
                'title' => $this->t('bdr_daily_report_modal_tab_support', 'Sokongan dan Laporan'),
                'fields' => [
                    ['key' => 'equipment_required', 'label' => $this->t('bdr_daily_report_modal_field_equipment_required', 'Peralatan Diperlukan'), 'value' => $record['f_peralatan_diperlukan'] ?? ''],
                    ['key' => 'equipment_serial', 'label' => $this->t('bdr_daily_report_modal_field_equipment_serial', 'No. Siri Peralatan'), 'value' => $record['f_no_siri_peralatan'] ?? ''],
                    ['key' => 'supporting_document', 'label' => $this->t('bdr_daily_report_modal_field_supporting_document', 'Dokumen Sokongan'), 'value' => $record['f_dokumen_sokongan'] ?? ''],
                    ['key' => 'daily_report', 'label' => $this->t('bdr_daily_report_modal_field_daily_report', 'Daily Report'), 'value' => $record['f_daily_report'] ?? ''],
                ],
            ],
        ];

        return array_values(array_map(function (array $tab): array {
            $fields = array_map(function (array $field): array {
                return [
                    'label' => (string)($field['label'] ?? '-'),
                    'value' => $this->formatModalFieldValue((string)($field['key'] ?? ''), $field['value'] ?? ''),
                    'full_width' => !empty($field['full_width']),
                ];
            }, $tab['fields'] ?? []);

            return [
                'key' => (string)($tab['key'] ?? 'tab'),
                'title' => (string)($tab['title'] ?? $this->t('bdr_daily_report_modal_default_tab', 'Maklumat')),
                'fields' => $fields,
            ];
        }, $tabs));
    }

    protected function buildKetuaJabatanLabel(array $record): string
    {
        $name = trim((string)($record['f_namakj'] ?? ''));
        $staffNo = trim((string)($record['f_nokj'] ?? ''));

        if ($name !== '' && $staffNo !== '') {
            return $name . ' (' . $staffNo . ')';
        }

        if ($name !== '') {
            return $name;
        }

        if ($staffNo !== '') {
            return $staffNo;
        }

        return '-';
    }

    protected function formatModalFieldValue(string $fieldKey, mixed $value): string
    {
        if ($value === null) {
            return '-';
        }

        $text = trim((string)$value);
        if ($text === '') {
            return '-';
        }

        if ($fieldKey === 'approval_flag') {
            return $text === '1' ? $this->t('bdr_daily_report_modal_approval_passed', 'LULUS') : $text;
        }

        if (in_array($fieldKey, ['clock_in', 'clock_out'], true)) {
            $formatted = $this->formatDateTimeWithSecondsLabel($text);
            return $formatted !== '' ? $formatted : $text;
        }

        if (in_array($fieldKey, ['start_time', 'end_time'], true)) {
            return $this->normalizeTimeLabel($text) ?: '-';
        }

        if (in_array($fieldKey, ['date', 'start_date', 'end_date', 'head_date', 'created_at', 'updated_at'], true)) {
            $formatted = $this->formatDateTimeLabel($text);
            return $formatted !== '' ? $formatted : $text;
        }

        if ($fieldKey === 'duration_type') {
            return $this->humanizeDurationType($text);
        }

        return $text;
    }

    protected function humanizeDurationType(string $value): string
    {
        $normalized = strtolower(trim($value));
        return match ($normalized) {
            'fullday' => 'Sepenuh Hari',
            'halfday' => 'Separuh Hari',
            default => $value !== '' ? ucwords(str_replace(['_', '-'], ' ', $value)) : '-',
        };
    }

    protected function formatDateTimeLabel(string $value): string
    {
        $cleanValue = trim($value);
        if ($cleanValue === '') {
            return '';
        }

        $formats = ['Y-m-d H:i:s', 'Y-m-d'];
        foreach ($formats as $format) {
            $parsed = DateTimeImmutable::createFromFormat($format, $cleanValue);
            if ($parsed instanceof DateTimeImmutable) {
                return $format === 'Y-m-d' ? $parsed->format('d/m/Y') : $parsed->format('d/m/Y H:i');
            }
        }

        try {
            $parsed = new DateTimeImmutable($cleanValue);
            return $parsed->format('d/m/Y H:i');
        } catch (Throwable) {
            return $cleanValue;
        }
    }

    protected function formatDateTimeWithSecondsLabel(string $value): string
    {
        $cleanValue = trim($value);
        if ($cleanValue === '') {
            return '';
        }

        $formats = ['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'];
        foreach ($formats as $format) {
            $parsed = DateTimeImmutable::createFromFormat($format, $cleanValue);
            if ($parsed instanceof DateTimeImmutable) {
                return $format === 'Y-m-d' ? $parsed->format('d/m/Y') : $parsed->format('d/m/Y H:i:s');
            }
        }

        try {
            $parsed = new DateTimeImmutable($cleanValue);
            return $parsed->format('d/m/Y H:i:s');
        } catch (Throwable) {
            return $cleanValue;
        }
    }

    protected function buildModalClockDateTimeValue(DateTimeImmutable $currentDate, string $time): string
    {
        $normalizedTime = $this->normalizeFullTimeLabel($time);
        if ($normalizedTime === '') {
            return '';
        }

        return $currentDate->format('Y-m-d') . ' ' . $normalizedTime;
    }

    protected function buildDurationLabel(array $record): string
    {
        $durationTypeRaw = trim((string)($record['f_jenis_tempoh'] ?? ''));
        $durationType = strtolower($durationTypeRaw);
        $startTime = $this->normalizeTimeLabel((string)($record['f_masa_mula'] ?? ''));
        $endTime = $this->normalizeTimeLabel((string)($record['f_masa_tamat'] ?? ''));

        $durationLabel = match ($durationType) {
            'fullday' => 'Sepenuh Hari',
            'halfday' => 'Separuh Hari',
            default => $durationTypeRaw !== '' ? ucwords(str_replace(['_', '-'], ' ', $durationTypeRaw)) : '',
        };

        if ($durationLabel === '') {
            if ($startTime !== '' && $endTime !== '') {
                return $startTime . ' - ' . $endTime;
            }

            return '-';
        }

        if ($durationType === 'fullday') {
            return $durationLabel;
        }

        if ($startTime !== '' && $endTime !== '') {
            return $durationLabel . ' (' . $startTime . ' - ' . $endTime . ')';
        }

        return $durationLabel;
    }

    protected function buildCoordinateLabel(string $longitude, string $latitude): string
    {
        $cleanLongitude = trim($longitude);
        $cleanLatitude = trim($latitude);

        if ($cleanLongitude === '' && $cleanLatitude === '') {
            return '-';
        }

        if ($cleanLongitude === '') {
            return $cleanLatitude;
        }

        if ($cleanLatitude === '') {
            return $cleanLongitude;
        }

        return $cleanLongitude . ', ' . $cleanLatitude;
    }

    protected function normalizeStatusLabel(string $status): string
    {
        $cleanStatus = trim($status);
        if ($cleanStatus === '') {
            return '-';
        }

        return ucwords(strtolower(str_replace(['_', '-'], ' ', $cleanStatus)));
    }

    protected function resolveStatusClass(string $status): string
    {
        $normalized = strtolower(trim($status));

        if ($normalized === '-' || $normalized === '') {
            return 'is-neutral';
        }

        if (str_contains($normalized, 'approve') || str_contains($normalized, 'lulus')) {
            return 'is-success';
        }

        if (str_contains($normalized, 'pending') || str_contains($normalized, 'semak')) {
            return 'is-warning';
        }

        if (str_contains($normalized, 'reject') || str_contains($normalized, 'tolak') || str_contains($normalized, 'gagal')) {
            return 'is-danger';
        }

        return 'is-neutral';
    }

    protected function normalizeTimeLabel(string $time): string
    {
        $cleanTime = trim($time);
        if ($cleanTime === '' || $cleanTime === '00:00:00') {
            return '';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $cleanTime) === 1) {
            return substr($cleanTime, 0, 5);
        }

        if (preg_match('/^\d{2}:\d{2}$/', $cleanTime) === 1) {
            return $cleanTime;
        }

        return $cleanTime;
    }

    protected function normalizeFullTimeLabel(string $time): string
    {
        $cleanTime = trim($time);
        if ($cleanTime === '' || $cleanTime === '00:00:00' || $cleanTime === '00:00') {
            return '';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $cleanTime) === 1) {
            return $cleanTime;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $cleanTime) === 1) {
            return $cleanTime . ':00';
        }

        if (preg_match('/\b(\d{2}:\d{2}:\d{2})\b/', $cleanTime, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/\b(\d{2}:\d{2})\b/', $cleanTime, $matches) === 1) {
            return $matches[1] . ':00';
        }

        try {
            return (new DateTimeImmutable($cleanTime))->format('H:i:s');
        } catch (Throwable) {
        }

        return $cleanTime;
    }

    protected function parseApiDate(string $date): ?DateTimeImmutable
    {
        $cleanDate = trim($date);
        if ($cleanDate === '') {
            return null;
        }

        $dateOnly = substr($cleanDate, 0, 10);
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $dateOnly);
        if ($parsed instanceof DateTimeImmutable) {
            return $parsed;
        }

        try {
            return new DateTimeImmutable($cleanDate);
        } catch (Throwable) {
            return null;
        }
    }

    protected function fallbackDash(string $value): string
    {
        $cleanValue = trim($value);
        return $cleanValue !== '' ? $cleanValue : '-';
    }

    protected function hasReportStorageTables(): bool
    {
        return $this->tableExists(self::REPORT_ENTRY_TABLE)
            && $this->tableExists(self::REPORT_ITEM_TABLE)
            && $this->tableExists(self::REPORT_SOURCE_TABLE);
    }

    protected function tableExists(string $tableName): bool
    {
        try {
            $statement = $this->pdoMysql->prepare(
                'SELECT 1
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table_name
                 LIMIT 1'
            );
            $statement->execute([':table_name' => $tableName]);
            if ((bool)$statement->fetchColumn()) {
                return true;
            }

            $fallback = $this->pdoMysql->prepare('SHOW TABLES LIKE :table_name');
            $fallback->execute([':table_name' => $tableName]);
            return (bool)$fallback->fetchColumn();
        } catch (Throwable) {
            return false;
        }
    }

    protected function decorateRowsWithEntryState(array $rows): array
    {
        $decorated = array_map(function (array $row): array {
            $row['entry_exists'] = false;
            $row['entry_status'] = null;
            $row['entry_id'] = null;
            $row['report_action_label'] = $this->t('bdr_daily_report_entry_action_fill', 'Isi laporan');
            $row['report_badge'] = null;
            $row['report_badge_class'] = 'is-draft';
            return $row;
        }, $rows);

        if ($decorated === [] || !$this->reportStorageReady || $this->currentLoginID === '') {
            return $decorated;
        }

        $reportDates = array_values(array_unique(array_filter(array_map(static fn(array $row): string => trim((string)($row['date_sort'] ?? '')), $decorated))));
        if ($reportDates === []) {
            return $decorated;
        }

        $stateMap = $this->fetchEntryStateByDates($reportDates);
        foreach ($decorated as &$row) {
            $reportDate = trim((string)($row['date_sort'] ?? ''));
            $state = $stateMap[$reportDate] ?? null;
            if (!is_array($state)) {
                continue;
            }

            $row['entry_exists'] = true;
            $row['entry_status'] = $state['status'];
            $row['entry_id'] = $state['entry_id'];
            $row['report_action_label'] = $state['status'] === 'submitted'
                ? $this->t('bdr_daily_report_entry_action_update', 'Kemaskini laporan')
                : $this->t('bdr_daily_report_entry_action_fill', 'Isi laporan');
            $row['report_badge'] = $this->mapEntryStatusLabel($state['status']);
            $row['report_badge_class'] = $this->mapEntryStatusClass($state['status']);
        }
        unset($row);

        return $decorated;
    }

    protected function fetchEntryStateByDates(array $reportDates): array
    {
        $reportDates = array_values(array_filter(array_map(static fn($value): string => trim((string)$value), $reportDates)));
        if ($reportDates === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($reportDates), '?'));
        $sql = 'SELECT f_entryID, f_reportDate, f_status
                FROM ' . self::REPORT_ENTRY_TABLE . '
                WHERE f_loginID = ?
                  AND f_reportDate IN (' . $placeholders . ')';

        try {
            $statement = $this->pdoMysql->prepare($sql);
            $statement->execute(array_merge([$this->currentLoginID], $reportDates));
            $map = [];
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $date = trim((string)($row['f_reportDate'] ?? ''));
                if ($date === '') {
                    continue;
                }

                $map[$date] = [
                    'entry_id' => isset($row['f_entryID']) ? (int)$row['f_entryID'] : null,
                    'status' => $this->normalizeEntryStatus((string)($row['f_status'] ?? 'draft')),
                ];
            }

            return $map;
        } catch (Throwable $exception) {
            error_log('[BdrDailyReportController] fetchEntryStateByDates failed: ' . $exception->getMessage());
            return [];
        }
    }

    protected function mapEntryStatusLabel(?string $status): ?string
    {
        return match ($this->normalizeEntryStatus((string)$status)) {
            'submitted' => $this->t('bdr_daily_report_badge_saved', 'Disimpan'),
            'draft' => $this->t('bdr_daily_report_badge_draft', 'Draf'),
            default => null,
        };
    }

    protected function mapEntryStatusClass(?string $status): string
    {
        return match ($this->normalizeEntryStatus((string)$status)) {
            'submitted' => 'is-submitted',
            default => 'is-draft',
        };
    }

    protected function normalizeEntryStatus(string $status): string
    {
        $normalized = strtolower(trim($status));
        return $normalized === 'submitted' ? 'submitted' : 'draft';
    }

    protected function validateCsrfToken(): void
    {
        $sessionToken = trim((string)($_SESSION['csrf_token'] ?? ''));
        $requestToken = trim((string)($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')));

        if ($sessionToken === '' || $requestToken === '' || !hash_equals($sessionToken, $requestToken)) {
            $this->jsonError($this->t('bdr_daily_report_session_expired', 'Sesi anda telah tamat. Sila muat semula halaman dan cuba lagi.'), 403);
        }
    }

    protected function handleEntryLoad(): void
    {
        $reportDate = $this->normalizeReportDate((string)($_POST['report_date'] ?? ''));
        if ($reportDate === null) {
            $this->jsonError($this->t('bdr_daily_report_invalid_report_date_message', 'Tarikh laporan harian tidak sah.'), 422);
        }

        $sourceRows = $this->requireApprovedRowsForReportDate($reportDate);
        $entry = $this->ensureEntryForDate($reportDate, $sourceRows);

        $this->jsonSuccess([
            'data' => [
                'entry' => $this->buildEntryPayload($entry),
                'items' => $this->fetchItemsByEntryId((int)$entry['f_entryID']),
                'sources' => $this->fetchSourcesByEntryId((int)$entry['f_entryID']),
                'meta' => [
                    'can_edit' => true,
                    'can_submit' => true,
                    'max_recommended_items_first_page' => 6,
                ],
            ],
        ]);
    }

    protected function handleEntrySave(bool $submit): void
    {
        $reportDate = $this->normalizeReportDate((string)($_POST['report_date'] ?? ''));
        if ($reportDate === null) {
            $this->jsonError($this->t('bdr_daily_report_invalid_report_date_message', 'Tarikh laporan harian tidak sah.'), 422);
        }

        $sourceRows = $this->requireApprovedRowsForReportDate($reportDate);
        $items = $this->normalizeSubmittedItems((string)($_POST['items_json'] ?? ''), $submit);
        $entry = $this->saveEntryForDate($reportDate, $sourceRows, $items, $submit ? 'submitted' : 'draft');

        $status = $this->normalizeEntryStatus((string)($entry['f_status'] ?? 'draft'));
        $this->jsonSuccess([
            'message' => $submit
                ? $this->t('bdr_daily_report_submit_success', 'Laporan harian berjaya disimpan.')
                : $this->t('bdr_daily_report_save_draft_success', 'Draf laporan harian berjaya disimpan.'),
            'data' => [
                'entry' => $this->buildEntryPayload($entry),
                'badge' => $this->mapEntryStatusLabel($status),
                'badge_class' => $this->mapEntryStatusClass($status),
                'preview_url' => (function_exists('base_url') ? (string)base_url('pages/bdr-daily-report-print.php') : 'bdr-daily-report-print.php')
                    . '?entry_id=' . (int)$entry['f_entryID'],
            ],
        ]);
    }

    protected function normalizeReportDate(string $value): ?string
    {
        $cleanValue = trim($value);
        if ($cleanValue === '') {
            return null;
        }

        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $cleanValue);
        return $parsed instanceof DateTimeImmutable ? $parsed->format('Y-m-d') : null;
    }

    protected function requireApprovedRowsForReportDate(string $reportDate): array
    {
        $matchingRows = array_values(array_filter($this->rows, static function (array $row) use ($reportDate): bool {
            return trim((string)($row['date_sort'] ?? '')) === $reportDate;
        }));

        if ($matchingRows === []) {
            $this->jsonError($this->t('bdr_daily_report_approved_not_found', 'Rekod BDR approved bagi tarikh tersebut tidak ditemui.'), 404);
        }

        return $matchingRows;
    }

    protected function normalizeSubmittedItems(string $rawItems, bool $requireAtLeastOne): array
    {
        $decoded = json_decode($rawItems, true);
        if (!is_array($decoded)) {
            if ($requireAtLeastOne) {
                $this->jsonError($this->t('bdr_daily_report_invalid_task_list', 'Senarai tugasan tidak sah.'), 422);
            }
            return [];
        }

        $items = [];
        foreach ($decoded as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $taskTitle = trim((string)($item['task_title'] ?? ''));
            $taskStatus = strtoupper(trim((string)($item['task_execution_status'] ?? 'SELESAI')));
            $staffComment = trim((string)($item['staff_comment'] ?? ''));

            if ($taskTitle === '' && $staffComment === '') {
                continue;
            }

            if ($taskTitle === '') {
                $this->jsonError($this->t('bdr_daily_report_task_title_required_each', 'Setiap tugasan mesti mempunyai ketetapan tugas.'), 422);
            }

            if (!array_key_exists($taskStatus, self::TASK_EXECUTION_STATUS_LABELS)) {
                $this->jsonError($this->t('bdr_daily_report_invalid_task_status', 'Status tugas tidak sah.'), 422);
            }

            $items[] = [
                'sort_order' => count($items) + 1,
                'task_title' => mb_substr($taskTitle, 0, 500),
                'task_execution_status' => $taskStatus,
                'staff_comment' => $staffComment,
            ];
        }

        if ($requireAtLeastOne && $items === []) {
            $this->jsonError($this->t('bdr_daily_report_at_least_one_task', 'Sekurang-kurangnya satu tugasan diperlukan sebelum laporan disimpan.'), 422);
        }

        return $items;
    }

    public static function getTaskExecutionStatusLabel(string $status): string
    {
        $normalized = strtoupper(trim($status));
        return match ($normalized) {
            'TIDAK_SELESAI' => self::ts('bdr_daily_report_status_tidak_selesai', 'Tidak Selesai'),
            'DALAM_PROSES' => self::ts('bdr_daily_report_status_dalam_proses', 'Dalam Proses'),
            'DITANGGUHKAN' => self::ts('bdr_daily_report_status_ditangguhkan', 'Ditangguhkan'),
            'MENUNGGU_MAKLUM_BALAS' => self::ts('bdr_daily_report_status_menunggu_maklum_balas', 'Menunggu Maklum Balas'),
            default => self::ts('bdr_daily_report_status_selesai', 'Selesai'),
        };
    }

    protected function ensureEntryForDate(string $reportDate, array $sourceRows): array
    {
        $now = date('Y-m-d H:i:s');
        $summary = $this->buildSourceSummary($sourceRows, $reportDate);
        $existingEntry = $this->findEntryByReportDate($reportDate);

        $this->pdoMysql->beginTransaction();

        try {
            if ($existingEntry) {
                $update = $this->pdoMysql->prepare(
                    'UPDATE ' . self::REPORT_ENTRY_TABLE . '
                     SET f_stafID = :staf_id,
                         f_staffName = :staff_name,
                         f_referenceSummary = :reference_summary,
                         f_categorySummary = :category_summary,
                         f_approvalName = :approval_name,
                         f_sourceStatus = :source_status,
                         f_updatedt = :updated_at
                     WHERE f_entryID = :entry_id
                     LIMIT 1'
                );
                $update->execute([
                    ':staf_id' => $summary['staff_id'],
                    ':staff_name' => $summary['staff_name'],
                    ':reference_summary' => $summary['reference_summary'],
                    ':category_summary' => $summary['category_summary'],
                    ':approval_name' => $summary['approval_name'],
                    ':source_status' => 'approved',
                    ':updated_at' => $now,
                    ':entry_id' => (int)$existingEntry['f_entryID'],
                ]);

                $entryId = (int)$existingEntry['f_entryID'];
            } else {
                $insert = $this->pdoMysql->prepare(
                    'INSERT INTO ' . self::REPORT_ENTRY_TABLE . ' (
                        f_loginID,
                        f_stafID,
                        f_staffName,
                        f_reportDate,
                        f_referenceSummary,
                        f_categorySummary,
                        f_approvalName,
                        f_sourceStatus,
                        f_status,
                        f_lastDraftSavedAt,
                        f_insertdt,
                        f_updatedt
                    ) VALUES (
                        :login_id,
                        :staf_id,
                        :staff_name,
                        :report_date,
                        :reference_summary,
                        :category_summary,
                        :approval_name,
                        :source_status,
                        :status,
                        :last_draft_saved_at,
                        :inserted_at,
                        :updated_at
                    )'
                );
                $insert->execute([
                    ':login_id' => $this->currentLoginID,
                    ':staf_id' => $summary['staff_id'],
                    ':staff_name' => $summary['staff_name'],
                    ':report_date' => $reportDate,
                    ':reference_summary' => $summary['reference_summary'],
                    ':category_summary' => $summary['category_summary'],
                    ':approval_name' => $summary['approval_name'],
                    ':source_status' => 'approved',
                    ':status' => 'draft',
                    ':last_draft_saved_at' => $now,
                    ':inserted_at' => $now,
                    ':updated_at' => $now,
                ]);

                $entryId = (int)$this->pdoMysql->lastInsertId();
            }

            $this->syncEntrySources($entryId, $sourceRows, $reportDate, $now);
            $this->pdoMysql->commit();
        } catch (Throwable $exception) {
            if ($this->pdoMysql->inTransaction()) {
                $this->pdoMysql->rollBack();
            }

            error_log('[BdrDailyReportController] ensureEntryForDate failed: ' . $exception->getMessage());
            $this->jsonError('Laporan harian tidak dapat dimuatkan buat masa ini.', 500);
        }

        $entry = $this->findEntryByIdForCurrentUser($entryId);
        if (!$entry) {
            $this->jsonError('Rekod laporan harian tidak ditemui selepas penyimpanan.', 500);
        }

        return $entry;
    }

    protected function saveEntryForDate(string $reportDate, array $sourceRows, array $items, string $status): array
    {
        $entry = $this->ensureEntryForDate($reportDate, $sourceRows);
        $entryId = (int)$entry['f_entryID'];
        $status = $this->normalizeEntryStatus($status);
        $now = date('Y-m-d H:i:s');

        $this->pdoMysql->beginTransaction();

        try {
            $update = $this->pdoMysql->prepare(
                'UPDATE ' . self::REPORT_ENTRY_TABLE . '
                 SET f_status = :status,
                     f_lastDraftSavedAt = :last_draft_saved_at,
                     f_submittedAt = :submitted_at,
                     f_submittedBy = :submitted_by,
                     f_updatedt = :updated_at
                 WHERE f_entryID = :entry_id
                 LIMIT 1'
            );
            $update->execute([
                ':status' => $status,
                ':last_draft_saved_at' => $now,
                ':submitted_at' => $status === 'submitted' ? $now : null,
                ':submitted_by' => $status === 'submitted' ? $this->currentLoginID : null,
                ':updated_at' => $now,
                ':entry_id' => $entryId,
            ]);

            $deleteItems = $this->pdoMysql->prepare('DELETE FROM ' . self::REPORT_ITEM_TABLE . ' WHERE f_entryID = :entry_id');
            $deleteItems->execute([':entry_id' => $entryId]);

            if ($items !== []) {
                $insertItem = $this->pdoMysql->prepare(
                    'INSERT INTO ' . self::REPORT_ITEM_TABLE . ' (
                        f_entryID,
                        f_sortOrder,
                        f_taskTitle,
                        f_taskExecutionStatus,
                        f_staffComment,
                        f_insertdt,
                        f_updatedt
                    ) VALUES (
                        :entry_id,
                        :sort_order,
                        :task_title,
                        :task_execution_status,
                        :staff_comment,
                        :inserted_at,
                        :updated_at
                    )'
                );

                foreach ($items as $item) {
                    $insertItem->execute([
                        ':entry_id' => $entryId,
                        ':sort_order' => (int)$item['sort_order'],
                        ':task_title' => $item['task_title'],
                        ':task_execution_status' => $item['task_execution_status'],
                        ':staff_comment' => $item['staff_comment'],
                        ':inserted_at' => $now,
                        ':updated_at' => $now,
                    ]);
                }
            }

            $this->syncEntrySources($entryId, $sourceRows, $reportDate, $now);
            $this->pdoMysql->commit();
        } catch (Throwable $exception) {
            if ($this->pdoMysql->inTransaction()) {
                $this->pdoMysql->rollBack();
            }

            error_log('[BdrDailyReportController] saveEntryForDate failed: ' . $exception->getMessage());
            $this->jsonError('Laporan harian tidak dapat disimpan buat masa ini.', 500);
        }

        $savedEntry = $this->findEntryByIdForCurrentUser($entryId);
        if (!$savedEntry) {
            $this->jsonError('Rekod laporan harian tidak ditemui selepas simpanan.', 500);
        }

        return $savedEntry;
    }

    protected function syncEntrySources(int $entryId, array $sourceRows, string $reportDate, string $now): void
    {
        $deleteSources = $this->pdoMysql->prepare('DELETE FROM ' . self::REPORT_SOURCE_TABLE . ' WHERE f_entryID = :entry_id');
        $deleteSources->execute([':entry_id' => $entryId]);

        $insertSource = $this->pdoMysql->prepare(
            'INSERT INTO ' . self::REPORT_SOURCE_TABLE . ' (
                f_entryID,
                f_bdrID,
                f_referenceNo,
                f_sourceDate,
                f_category,
                f_approvalName,
                f_insertdt
            ) VALUES (
                :entry_id,
                :bdr_id,
                :reference_no,
                :source_date,
                :category,
                :approval_name,
                :inserted_at
            )'
        );

        $seen = [];
        foreach ($sourceRows as $row) {
            $bdrId = trim((string)($row['source_bdr_id'] ?? ''));
            if ($bdrId === '') {
                continue;
            }

            $dedupeKey = $bdrId . '|' . $reportDate;
            if (isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;

            $insertSource->execute([
                ':entry_id' => $entryId,
                ':bdr_id' => $bdrId,
                ':reference_no' => $this->limitText((string)($row['reference_no'] ?? ''), 255),
                ':source_date' => $reportDate,
                ':category' => $this->limitText((string)($row['category'] ?? ''), 255),
                ':approval_name' => $this->limitText((string)($row['approval_name'] ?? ''), 255),
                ':inserted_at' => $now,
            ]);
        }
    }

    protected function buildSourceSummary(array $sourceRows, string $reportDate): array
    {
        $staffId = '';
        $staffName = '';
        $references = [];
        $categories = [];
        $approvals = [];

        foreach ($sourceRows as $row) {
            if ($staffId === '') {
                $staffId = trim((string)($row['source_staff_id'] ?? ''));
            }
            if ($staffName === '') {
                $staffName = trim((string)($row['source_staff_name'] ?? ''));
            }

            $referenceNo = trim((string)($row['reference_no'] ?? ''));
            $category = trim((string)($row['category'] ?? ''));
            $approvalName = trim((string)($row['approval_name'] ?? ''));

            if ($referenceNo !== '' && $referenceNo !== '-') {
                $references[$referenceNo] = $referenceNo;
            }
            if ($category !== '' && $category !== '-') {
                $categories[$category] = $category;
            }
            if ($approvalName !== '' && $approvalName !== '-') {
                $approvals[$approvalName] = $approvalName;
            }
        }

        return [
            'staff_id' => $staffId !== '' ? $staffId : $this->currentLoginID,
            'staff_name' => $staffName,
            'report_date' => $reportDate,
            'reference_summary' => $this->limitText(implode(', ', array_values($references)), 255),
            'category_summary' => $this->limitText(implode(', ', array_values($categories)), 255),
            'approval_name' => $this->limitText(implode(', ', array_values($approvals)), 255),
        ];
    }

    protected function limitText(string $value, int $limit): ?string
    {
        $cleanValue = trim($value);
        if ($cleanValue === '') {
            return null;
        }

        return mb_substr($cleanValue, 0, $limit);
    }

    protected function findEntryByReportDate(string $reportDate): ?array
    {
        try {
            $statement = $this->pdoMysql->prepare(
                'SELECT *
                 FROM ' . self::REPORT_ENTRY_TABLE . '
                 WHERE f_loginID = :login_id
                   AND f_reportDate = :report_date
                 LIMIT 1'
            );
            $statement->execute([
                ':login_id' => $this->currentLoginID,
                ':report_date' => $reportDate,
            ]);
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (Throwable $exception) {
            error_log('[BdrDailyReportController] findEntryByReportDate failed: ' . $exception->getMessage());
            return null;
        }
    }

    protected function findEntryByIdForCurrentUser(int $entryId): ?array
    {
        if ($entryId <= 0) {
            return null;
        }

        try {
            $statement = $this->pdoMysql->prepare(
                'SELECT *
                 FROM ' . self::REPORT_ENTRY_TABLE . '
                 WHERE f_entryID = :entry_id
                   AND f_loginID = :login_id
                 LIMIT 1'
            );
            $statement->execute([
                ':entry_id' => $entryId,
                ':login_id' => $this->currentLoginID,
            ]);
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (Throwable $exception) {
            error_log('[BdrDailyReportController] findEntryByIdForCurrentUser failed: ' . $exception->getMessage());
            return null;
        }
    }

    protected function fetchItemsByEntryId(int $entryId): array
    {
        if ($entryId <= 0) {
            return [];
        }

        $statement = $this->pdoMysql->prepare(
            'SELECT f_itemID, f_sortOrder, f_taskTitle, f_taskExecutionStatus, f_staffComment
             FROM ' . self::REPORT_ITEM_TABLE . '
             WHERE f_entryID = :entry_id
             ORDER BY f_sortOrder ASC, f_itemID ASC'
        );
        $statement->execute([':entry_id' => $entryId]);

        $items = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $items[] = [
                'item_id' => isset($row['f_itemID']) ? (int)$row['f_itemID'] : null,
                'sort_order' => isset($row['f_sortOrder']) ? (int)$row['f_sortOrder'] : count($items) + 1,
                'task_title' => (string)($row['f_taskTitle'] ?? ''),
                'task_execution_status' => (string)($row['f_taskExecutionStatus'] ?? 'SELESAI'),
                'staff_comment' => (string)($row['f_staffComment'] ?? ''),
            ];
        }

        return $items;
    }

    protected function fetchSourcesByEntryId(int $entryId): array
    {
        if ($entryId <= 0) {
            return [];
        }

        $statement = $this->pdoMysql->prepare(
            'SELECT f_entrySourceID, f_bdrID, f_referenceNo, f_sourceDate, f_category, f_approvalName
             FROM ' . self::REPORT_SOURCE_TABLE . '
             WHERE f_entryID = :entry_id
             ORDER BY f_entrySourceID ASC'
        );
        $statement->execute([':entry_id' => $entryId]);

        $sources = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $sources[] = [
                'entry_source_id' => isset($row['f_entrySourceID']) ? (int)$row['f_entrySourceID'] : null,
                'bdr_id' => (string)($row['f_bdrID'] ?? ''),
                'reference_no' => (string)($row['f_referenceNo'] ?? ''),
                'source_date' => (string)($row['f_sourceDate'] ?? ''),
                'category' => (string)($row['f_category'] ?? ''),
                'approval_name' => (string)($row['f_approvalName'] ?? ''),
            ];
        }

        return $sources;
    }

    protected function buildEntryPayload(array $entry): array
    {
        $status = $this->normalizeEntryStatus((string)($entry['f_status'] ?? 'draft'));

        return [
            'entry_id' => isset($entry['f_entryID']) ? (int)$entry['f_entryID'] : null,
            'status' => $status,
            'report_date' => (string)($entry['f_reportDate'] ?? ''),
            'report_date_label' => $this->formatDateTimeLabel((string)($entry['f_reportDate'] ?? '')),
            'staff_name' => (string)($entry['f_staffName'] ?? ''),
            'staf_id' => (string)($entry['f_stafID'] ?? ''),
            'login_id' => (string)($entry['f_loginID'] ?? ''),
            'reference_summary' => (string)($entry['f_referenceSummary'] ?? ''),
            'category_summary' => (string)($entry['f_categorySummary'] ?? ''),
            'approval_name' => (string)($entry['f_approvalName'] ?? ''),
            'submitted_at' => (string)($entry['f_submittedAt'] ?? ''),
            'preview_url' => (function_exists('base_url') ? (string)base_url('pages/bdr-daily-report-print.php') : 'bdr-daily-report-print.php')
                . '?entry_id=' . (int)($entry['f_entryID'] ?? 0),
        ];
    }

    public function getPrintableEntryById(int $entryId): ?array
    {
        if (!$this->reportStorageReady) {
            return null;
        }

        $entry = $this->findEntryByIdForCurrentUser($entryId);
        if (!$entry) {
            return null;
        }

        return [
            'entry' => $this->buildEntryPayload($entry),
            'items' => $this->fetchItemsByEntryId($entryId),
            'sources' => $this->fetchSourcesByEntryId($entryId),
        ];
    }

    protected function getEnvString(string $key, string $default = ''): string
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }

        $cleanValue = trim((string)$value);
        return $cleanValue !== '' ? $cleanValue : $default;
    }
}
