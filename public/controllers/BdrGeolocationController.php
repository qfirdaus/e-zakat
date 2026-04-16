<?php
declare(strict_types=1);

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';

class BdrGeolocationController
{
    protected const STAFF_SITE_TABLE = 'tbl_bdr_staff_site';

    public string $lang = 'ms';
    public array $profile = [];
    protected PDO $pdoMysql;
    protected User $userModel;

    public function __construct(bool $mapDataOnly = false)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->lang = $_SESSION['lang'] ?? 'ms';
        $this->pdoMysql = Database::getInstance('mysql')->getConnection();
        $this->userModel = new User($this->pdoMysql);
        $this->profile = $this->loadProfile();
        if ($mapDataOnly) {
            return;
        }

        $this->applyUserTheme();
    }

    protected function loadProfile(): array
    {
        $loginID = trim((string)($_SESSION['f_loginID'] ?? ''));
        if ($loginID !== '') {
            $profile = $this->userModel->getProfileByLoginID($loginID) ?: [];
            if ($profile !== []) {
                return $profile;
            }
        }

        $fStafID = trim((string)($_SESSION['f_stafID'] ?? ''));
        return $fStafID !== '' ? ($this->userModel->getProfile($fStafID) ?: []) : [];
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
        $this->discardBufferedOutputForJson();
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=UTF-8');
        }

        echo json_encode(array_merge(['success' => true], $payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function jsonError(string $message, int $status = 400, array $extra = []): void
    {
        $this->discardBufferedOutputForJson();
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

    protected function discardBufferedOutputForJson(): void
    {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
    }

    protected function logMapEndpointIssue(string $event, array $context = []): void
    {
        $payload = array_merge([
            'event' => $event,
            'timestamp' => date('c'),
            'uri' => (string)($_SERVER['REQUEST_URI'] ?? ''),
            'login_id' => (string)($_SESSION['f_loginID'] ?? $_SESSION['user']['f_loginID'] ?? ''),
            'staff_id' => (string)($_SESSION['f_stafID'] ?? $_SESSION['user']['f_stafID'] ?? ''),
            'remote_addr' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
        ], $context);

        @file_put_contents(
            __DIR__ . '/../log/bdr-geolocation-map.log',
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    public function setLang(string $lang): void
    {
        $this->lang = trim($lang) !== '' ? trim($lang) : 'ms';
        $_SESSION['lang'] = $this->lang;
    }

    public function handleMapStaffData(): void
    {
        try {
            $siteCode = function_exists('distance_normalize_site_code')
                ? distance_normalize_site_code($this->getQueryString('site', ''))
                : $this->getQueryString('site', 'upnm_kampus');
            if ($siteCode === '') {
                $siteCode = 'upnm_kampus';
            }

            $limit = $this->getQueryInt('limit', 5000);
            if ($limit < 1) {
                $limit = 5000;
            }
            $limit = min($limit, 10000);

            $records = $this->loadStaffMapRecords($siteCode, $limit);
            $site = function_exists('distance_site_config') ? distance_site_config($siteCode) : [];
            $officeCoords = function_exists('distance_office_coords') ? distance_office_coords($siteCode) : [];

            $this->jsonSuccess([
                'site' => [
                    'site_code' => $siteCode,
                    'office_label' => (string)($site['label'] ?? ''),
                    'office_address' => (string)($site['address'] ?? ''),
                    'office_coords' => [
                        'lat' => isset($officeCoords['lat']) ? (float)$officeCoords['lat'] : null,
                        'lng' => isset($officeCoords['lon']) ? (float)$officeCoords['lon'] : null,
                    ],
                ],
                'total' => count($records),
                'records' => $records,
            ]);
        } catch (Throwable $e) {
            error_log('[BdrGeolocationController] handleMapStaffData failed: ' . $e->getMessage());
            $this->logMapEndpointIssue('map_staff_failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $this->jsonError('Data geolokasi staf tidak dapat dimuatkan buat masa ini.', 500);
        }
    }

    public function handleMapHeatData(): void
    {
        try {
            $siteCode = function_exists('distance_normalize_site_code')
                ? distance_normalize_site_code($this->getQueryString('site', ''))
                : $this->getQueryString('site', 'upnm_kampus');
            if ($siteCode === '') {
                $siteCode = 'upnm_kampus';
            }

            $limit = $this->getQueryInt('limit', 10000);
            if ($limit < 1) {
                $limit = 10000;
            }
            $limit = min($limit, 20000);

            $records = $this->loadStaffHeatRecords($siteCode, $limit);
            $site = function_exists('distance_site_config') ? distance_site_config($siteCode) : [];
            $officeCoords = function_exists('distance_office_coords') ? distance_office_coords($siteCode) : [];

            $this->jsonSuccess([
                'site' => [
                    'site_code' => $siteCode,
                    'office_label' => (string)($site['label'] ?? ''),
                    'office_address' => (string)($site['address'] ?? ''),
                    'office_coords' => [
                        'lat' => isset($officeCoords['lat']) ? (float)$officeCoords['lat'] : null,
                        'lng' => isset($officeCoords['lon']) ? (float)$officeCoords['lon'] : null,
                    ],
                ],
                'staff_total' => array_sum(array_column($records, 'weight')),
                'total' => count($records),
                'records' => $records,
            ]);
        } catch (Throwable $e) {
            error_log('[BdrGeolocationController] handleMapHeatData failed: ' . $e->getMessage());
            $this->logMapEndpointIssue('map_heat_failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $this->jsonError('Data heatmap geolokasi staf tidak dapat dimuatkan buat masa ini.', 500);
        }
    }

    public function handleMapStaffRoute(): void
    {
        try {
            $siteCode = function_exists('distance_normalize_site_code')
                ? distance_normalize_site_code($this->getQueryString('site', ''))
                : $this->getQueryString('site', 'upnm_kampus');
            $staffNo = $this->getQueryString('staff_no', '');
            if ($staffNo === '') {
                $this->jsonError('No. staf tidak lengkap.', 400);
            }

            $record = $this->loadStaffMapRouteRecord($siteCode, $staffNo);
            if ($record === null) {
                $this->jsonError('Route staf tidak ditemui.', 404);
            }

            $this->jsonSuccess(['record' => $record]);
        } catch (Throwable $e) {
            error_log('[BdrGeolocationController] handleMapStaffRoute failed: ' . $e->getMessage());
            $this->logMapEndpointIssue('map_staff_route_failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $this->jsonError('Route geolokasi staf tidak dapat dimuatkan buat masa ini.', 500);
        }
    }

    public function handleSelfMapData(): void
    {
        try {
            $staffCandidates = $this->resolveCurrentStaffCandidates();
            if ($staffCandidates === []) {
                $this->jsonError('Identiti staf semasa tidak ditemui.', 404);
            }

            $siteCode = $this->resolveCurrentUserSiteCode($staffCandidates);
            $record = $this->loadCurrentUserMapRecord($staffCandidates, $siteCode);
            $site = function_exists('distance_site_config') ? distance_site_config($siteCode) : [];
            $officeCoords = function_exists('distance_office_coords') ? distance_office_coords($siteCode) : [];

            $this->jsonSuccess([
                'site' => [
                    'site_code' => $siteCode,
                    'office_label' => (string)($site['label'] ?? ''),
                    'office_address' => (string)($site['address'] ?? ''),
                    'office_coords' => [
                        'lat' => isset($officeCoords['lat']) ? (float)$officeCoords['lat'] : null,
                        'lng' => isset($officeCoords['lon']) ? (float)$officeCoords['lon'] : null,
                    ],
                ],
                'record' => $record,
                'identity' => [
                    'staff_candidates' => $staffCandidates,
                ],
            ]);
        } catch (Throwable $e) {
            error_log('[BdrGeolocationController] handleSelfMapData failed: ' . $e->getMessage());
            $this->logMapEndpointIssue('self_map_failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $this->jsonError('Data geolokasi diri anda tidak dapat dimuatkan buat masa ini.', 500);
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    protected function loadStaffMapRecords(string $siteCode, int $limit = 5000): array
    {
        $distanceTable = function_exists('distance_result_table_name')
            ? distance_result_table_name()
            : 'tbl_m_staff_distance_cache';

        $sql = '
            SELECT
                c.f_cacheID,
                c.f_stafID,
                c.f_siteCode AS f_cacheSiteCode,
                COALESCE(s.f_siteCode, c.f_siteCode) AS f_siteCode,
                c.f_homeAddress,
                c.f_homeAddressJson,
                c.f_distanceKm,
                c.f_source,
                c.f_routeProvider,
                c.f_matchQuality,
                c.f_resultJson,
                c.f_status,
                c.f_lastCalculatedAt,
                s.f_staffName,
                s.f_email,
                s.f_department,
                s.f_departmentCode,
                s.f_position,
                s.f_isActive
            FROM ' . $distanceTable . ' c
            LEFT JOIN ' . self::STAFF_SITE_TABLE . ' s
              ON s.f_staffNo = c.f_stafID
            WHERE COALESCE(s.f_siteCode, c.f_siteCode) = :site_code
              AND (s.f_staffNo IS NULL OR s.f_isActive = 1)
              AND c.f_stafID IS NOT NULL
              AND TRIM(c.f_stafID) <> \'\'
              AND c.f_resultJson IS NOT NULL
              AND TRIM(c.f_resultJson) <> \'\'
              AND NOT EXISTS (
                  SELECT 1
                  FROM ' . $distanceTable . ' newer
                  LEFT JOIN ' . self::STAFF_SITE_TABLE . ' newer_s
                    ON newer_s.f_staffNo = newer.f_stafID
                  WHERE newer.f_stafID = c.f_stafID
                    AND COALESCE(newer_s.f_siteCode, newer.f_siteCode) = :newer_site_code
                    AND (newer_s.f_staffNo IS NULL OR newer_s.f_isActive = 1)
                    AND (
                        (
                            newer.f_siteCode = :newer_exact_site_code
                            AND c.f_siteCode <> :current_exact_site_code
                        )
                        OR (
                            (newer.f_siteCode = :newer_compare_site_code) = (c.f_siteCode = :current_compare_site_code)
                            AND newer.f_lastCalculatedAt > c.f_lastCalculatedAt
                        )
                        OR (
                            (newer.f_siteCode = :newer_tiebreak_site_code) = (c.f_siteCode = :current_tiebreak_site_code)
                            AND newer.f_lastCalculatedAt = c.f_lastCalculatedAt
                            AND newer.f_cacheID > c.f_cacheID
                        )
                    )
              )
            ORDER BY COALESCE(s.f_department, \'\') ASC, c.f_stafID ASC
            LIMIT ' . (int)$limit;

        $stmt = $this->pdoMysql->prepare($sql);
        $stmt->execute([
            ':site_code' => $siteCode,
            ':newer_site_code' => $siteCode,
            ':newer_exact_site_code' => $siteCode,
            ':current_exact_site_code' => $siteCode,
            ':newer_compare_site_code' => $siteCode,
            ':current_compare_site_code' => $siteCode,
            ':newer_tiebreak_site_code' => $siteCode,
            ':current_tiebreak_site_code' => $siteCode,
        ]);

        $records = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $record = $this->mapCacheRowToStaffLocation($row, false);
            if ($record !== null) {
                $records[] = $record;
            }
        }

        return $records;
    }

    /**
     * @return array<int,array{lat:float,lng:float,weight:int}>
     */
    protected function loadStaffHeatRecords(string $siteCode, int $limit = 10000): array
    {
        $distanceTable = function_exists('distance_result_table_name')
            ? distance_result_table_name()
            : 'tbl_m_staff_distance_cache';

        $sql = '
            SELECT
                c.f_cacheID,
                c.f_stafID,
                c.f_siteCode AS f_cacheSiteCode,
                COALESCE(s.f_siteCode, c.f_siteCode) AS f_siteCode,
                c.f_resultJson,
                c.f_lastCalculatedAt
            FROM ' . $distanceTable . ' c
            LEFT JOIN ' . self::STAFF_SITE_TABLE . ' s
              ON s.f_staffNo = c.f_stafID
            WHERE COALESCE(s.f_siteCode, c.f_siteCode) = :site_code
              AND (s.f_staffNo IS NULL OR s.f_isActive = 1)
              AND c.f_stafID IS NOT NULL
              AND TRIM(c.f_stafID) <> \'\'
              AND c.f_resultJson IS NOT NULL
              AND TRIM(c.f_resultJson) <> \'\'
              AND NOT EXISTS (
                  SELECT 1
                  FROM ' . $distanceTable . ' newer
                  LEFT JOIN ' . self::STAFF_SITE_TABLE . ' newer_s
                    ON newer_s.f_staffNo = newer.f_stafID
                  WHERE newer.f_stafID = c.f_stafID
                    AND COALESCE(newer_s.f_siteCode, newer.f_siteCode) = :newer_site_code
                    AND (newer_s.f_staffNo IS NULL OR newer_s.f_isActive = 1)
                    AND (
                        (
                            newer.f_siteCode = :newer_exact_site_code
                            AND c.f_siteCode <> :current_exact_site_code
                        )
                        OR (
                            (newer.f_siteCode = :newer_compare_site_code) = (c.f_siteCode = :current_compare_site_code)
                            AND newer.f_lastCalculatedAt > c.f_lastCalculatedAt
                        )
                        OR (
                            (newer.f_siteCode = :newer_tiebreak_site_code) = (c.f_siteCode = :current_tiebreak_site_code)
                            AND newer.f_lastCalculatedAt = c.f_lastCalculatedAt
                            AND newer.f_cacheID > c.f_cacheID
                        )
                    )
              )
            ORDER BY c.f_stafID ASC
            LIMIT ' . (int)$limit;

        $stmt = $this->pdoMysql->prepare($sql);
        $stmt->execute([
            ':site_code' => $siteCode,
            ':newer_site_code' => $siteCode,
            ':newer_exact_site_code' => $siteCode,
            ':current_exact_site_code' => $siteCode,
            ':newer_compare_site_code' => $siteCode,
            ':current_compare_site_code' => $siteCode,
            ':newer_tiebreak_site_code' => $siteCode,
            ':current_tiebreak_site_code' => $siteCode,
        ]);

        $grouped = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result = $this->decodeJsonObject((string)($row['f_resultJson'] ?? ''));
            $homeCoords = is_array($result['home_coords'] ?? null) ? $result['home_coords'] : [];
            $coords = $this->extractLatLng($homeCoords);
            if ($coords === null) {
                continue;
            }

            $key = number_format((float)$coords['lat'], 6, '.', '') . ',' . number_format((float)$coords['lng'], 6, '.', '');
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'lat' => (float)$coords['lat'],
                    'lng' => (float)$coords['lng'],
                    'weight' => 0,
                ];
            }
            $grouped[$key]['weight']++;
        }

        return array_values($grouped);
    }

    protected function loadStaffMapRouteRecord(string $siteCode, string $staffNo): ?array
    {
        $distanceTable = function_exists('distance_result_table_name')
            ? distance_result_table_name()
            : 'tbl_m_staff_distance_cache';

        $sql = '
            SELECT
                c.f_cacheID,
                c.f_stafID,
                c.f_siteCode AS f_cacheSiteCode,
                COALESCE(s.f_siteCode, c.f_siteCode) AS f_siteCode,
                c.f_homeAddress,
                c.f_homeAddressJson,
                c.f_distanceKm,
                c.f_source,
                c.f_routeProvider,
                c.f_matchQuality,
                c.f_resultJson,
                c.f_status,
                c.f_lastCalculatedAt,
                s.f_staffName,
                s.f_email,
                s.f_department,
                s.f_departmentCode,
                s.f_position,
                s.f_isActive
            FROM ' . $distanceTable . ' c
            LEFT JOIN ' . self::STAFF_SITE_TABLE . ' s
              ON s.f_staffNo = c.f_stafID
            WHERE COALESCE(s.f_siteCode, c.f_siteCode) = :site_code
              AND (s.f_staffNo IS NULL OR s.f_isActive = 1)
              AND c.f_stafID = :staff_no
              AND c.f_resultJson IS NOT NULL
              AND TRIM(c.f_resultJson) <> \'\'
            ORDER BY (c.f_siteCode = :order_site_code) DESC, c.f_lastCalculatedAt DESC, c.f_cacheID DESC
            LIMIT 1';

        $stmt = $this->pdoMysql->prepare($sql);
        $stmt->execute([
            ':site_code' => $siteCode,
            ':order_site_code' => $siteCode,
            ':staff_no' => $staffNo,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }

        return $this->mapCacheRowToStaffLocation($row, true);
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>|null
     */
    protected function mapCacheRowToStaffLocation(array $row, bool $includeRoute = false): ?array
    {
        $staffNo = trim((string)($row['f_stafID'] ?? ''));
        if ($staffNo === '') {
            return null;
        }

        $result = $this->decodeJsonObject((string)($row['f_resultJson'] ?? ''));
        $homeCoords = is_array($result['home_coords'] ?? null) ? $result['home_coords'] : [];
        $coords = $this->extractLatLng($homeCoords);
        if ($coords === null) {
            return null;
        }

        $addressInputs = $this->decodeJsonObject((string)($row['f_homeAddressJson'] ?? ''));
        $matchedAddress = trim((string)($homeCoords['display_name'] ?? $homeCoords['matched_query'] ?? ''));
        $inputAddress = trim((string)($row['f_homeAddress'] ?? ''));
        $matchQuality = trim((string)($row['f_matchQuality'] ?? $result['match_quality'] ?? ''));
        $status = strtoupper(trim((string)($row['f_status'] ?? '')));
        $avatarIdentifier = $this->staffNoToAvatarIdentifier($staffNo);
        $siteCode = function_exists('distance_normalize_site_code')
            ? distance_normalize_site_code((string)($row['f_siteCode'] ?? ''))
            : trim((string)($row['f_siteCode'] ?? ''));
        $cacheSiteCode = function_exists('distance_normalize_site_code')
            ? distance_normalize_site_code((string)($row['f_cacheSiteCode'] ?? $row['f_siteCode'] ?? ''))
            : trim((string)($row['f_cacheSiteCode'] ?? $row['f_siteCode'] ?? ''));
        $isSiteFallbackCache = $siteCode !== '' && $cacheSiteCode !== '' && $siteCode !== $cacheSiteCode;
        $routePoints = $includeRoute && is_array($result['route_points'] ?? null)
            ? $this->extractRoutePoints($result['route_points'])
            : [];
        $officeCoords = is_array($result['office_coords'] ?? null)
            ? $this->extractLatLng($result['office_coords'])
            : null;
        if ($isSiteFallbackCache) {
            $routePoints = [];
            $officeCoords = function_exists('distance_office_coords')
                ? $this->extractLatLng(distance_office_coords($siteCode))
                : null;
        }

        return [
            'staff_no' => $staffNo,
            'name' => trim((string)($row['f_staffName'] ?? '')),
            'email' => trim((string)($row['f_email'] ?? '')),
            'department' => trim((string)($row['f_department'] ?? '')),
            'department_code' => trim((string)($row['f_departmentCode'] ?? '')),
            'position' => trim((string)($row['f_position'] ?? '')),
            'avatar_url' => $this->userModel->getAvatarUrl($avatarIdentifier),
            'site_code' => $siteCode,
            'cache_site_code' => $cacheSiteCode,
            'uses_site_fallback_cache' => $isSiteFallbackCache,
            'lat' => $coords['lat'],
            'lng' => $coords['lng'],
            'office_lat' => $officeCoords['lat'] ?? null,
            'office_lng' => $officeCoords['lng'] ?? null,
            'route_points' => $routePoints,
            'address' => $inputAddress,
            'matched_address' => $matchedAddress,
            'postcode' => trim((string)($addressInputs['poskod'] ?? '')),
            'state' => trim((string)($addressInputs['negeri'] ?? '')),
            'country' => trim((string)($addressInputs['negara'] ?? '')),
            'distance_km' => !$isSiteFallbackCache && is_numeric($row['f_distanceKm'] ?? null) ? (float)$row['f_distanceKm'] : null,
            'distance_source' => !$isSiteFallbackCache ? trim((string)($row['f_source'] ?? $result['source'] ?? '')) : '',
            'route_provider' => !$isSiteFallbackCache ? trim((string)($row['f_routeProvider'] ?? $result['route_provider'] ?? '')) : '',
            'match_quality' => $matchQuality,
            'status' => $status,
            'is_review_location' => strtoupper($matchQuality) === 'LOW',
            'last_calculated_at' => trim((string)($row['f_lastCalculatedAt'] ?? '')),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    protected function decodeJsonObject(string $json): array
    {
        $json = trim($json);
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string,mixed> $coords
     * @return array{lat:float,lng:float}|null
     */
    protected function extractLatLng(array $coords): ?array
    {
        $lat = $coords['lat'] ?? $coords['latitude'] ?? null;
        $lng = $coords['lng'] ?? $coords['lon'] ?? $coords['longitude'] ?? null;
        if (!is_numeric($lat) || !is_numeric($lng)) {
            return null;
        }

        $lat = (float)$lat;
        $lng = (float)$lng;
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }

    /**
     * @param array<int,mixed> $points
     * @return array<int,array{lat:float,lng:float}>
     */
    protected function extractRoutePoints(array $points): array
    {
        $route = [];
        foreach ($points as $point) {
            if (!is_array($point)) {
                continue;
            }

            $coords = $this->extractLatLng($point);
            if ($coords !== null) {
                $route[] = $coords;
            }
        }

        return $route;
    }

    protected function staffNoToAvatarIdentifier(string $staffNo): string
    {
        $staffNo = trim($staffNo);
        if ($staffNo === '') {
            return '';
        }

        if (preg_match('/^([0-9]+)/', $staffNo, $matches) === 1) {
            return (string) ((int) $matches[1]);
        }

        return $staffNo;
    }

    /**
     * @return array<int,string>
     */
    protected function resolveCurrentStaffCandidates(): array
    {
        $loginID = trim((string)($_SESSION['f_loginID'] ?? ''));
        $resolvedUser = $this->resolveCurrentUserByLoginID($loginID);

        $seedCandidates = [
            $loginID,
            (string)($resolvedUser['f_stafID'] ?? ''),
            (string)($resolvedUser['f_nopekerja'] ?? ''),
            (string)($resolvedUser['f_userID'] ?? ''),
            (string)($_SESSION['f_stafID'] ?? ''),
            (string)($_SESSION['f_nopekerja'] ?? ''),
            (string)($_SESSION['f_userID'] ?? ''),
            (string)($this->profile['f_stafID'] ?? ''),
            (string)($this->profile['f_loginID'] ?? ''),
            (string)($this->profile['f_nopekerja'] ?? ''),
            (string)($this->profile['f_userID'] ?? ''),
            (string)($this->profile['stafid'] ?? ''),
            (string)($this->profile['nopekerja'] ?? ''),
        ];

        $candidates = [];
        foreach ($seedCandidates as $candidate) {
            $this->appendIdentityVariants($candidates, $candidate);
        }

        foreach ($this->resolveDatabaseIdentityCandidates(array_values($candidates)) as $candidate) {
            $this->appendIdentityVariants($candidates, $candidate);
        }

        if ($resolvedUser !== []) {
            foreach (['f_stafID', 'f_nopekerja', 'f_loginID', 'f_userID'] as $key) {
                $this->appendIdentityVariants($candidates, (string)($resolvedUser[$key] ?? ''));
            }
        }

        return array_values($candidates);
    }

    /**
     * @return array<string,mixed>
     */
    protected function resolveCurrentUserByLoginID(string $loginID): array
    {
        $loginID = trim($loginID);
        if ($loginID === '') {
            return [];
        }

        $profile = $this->userModel->getProfileByLoginID($loginID) ?? [];
        if ($profile !== []) {
            return $profile;
        }

        try {
            $stmt = $this->pdoMysql->prepare(
                'SELECT f_userID, f_loginID, f_stafID, f_nopekerja
                 FROM tbl_m_user
                 WHERE TRIM(COALESCE(f_loginID, "")) = :login_id
                   AND COALESCE(f_statusID, 0) != 9
                 LIMIT 1'
            );
            $stmt->execute([':login_id' => $loginID]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param array<string,string> $collector
     */
    protected function appendIdentityVariants(array &$collector, string $value): void
    {
        $value = trim($value);
        if ($value === '') {
            return;
        }

        $variants = [$value];
        if (ctype_digit($value)) {
            $variants[] = (string)((int)$value);
            if (strlen($value) < 4) {
                $variants[] = str_pad($value, 4, '0', STR_PAD_LEFT);
            }
        }

        if (preg_match('/^(\d+)-\d+$/', $value, $matches) === 1) {
            $prefix = (string)$matches[1];
            $variants[] = $prefix;
            $variants[] = (string)((int)$prefix);
            if (strlen($prefix) < 4) {
                $variants[] = str_pad($prefix, 4, '0', STR_PAD_LEFT);
            }
        }

        foreach ($variants as $variant) {
            $variant = trim((string)$variant);
            if ($variant !== '') {
                $collector[$variant] = $variant;
            }
        }
    }

    /**
     * @param array<int,string> $seedCandidates
     * @return array<int,string>
     */
    protected function resolveDatabaseIdentityCandidates(array $seedCandidates): array
    {
        $seedCandidates = array_values(array_filter(array_map('trim', $seedCandidates), static fn (string $value): bool => $value !== ''));
        if ($seedCandidates === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($seedCandidates), '?'));
        $sql = '
            SELECT f_userID, f_loginID, f_stafID, f_nopekerja
            FROM tbl_m_user
            WHERE COALESCE(f_statusID, 0) != 9
              AND (
                    TRIM(COALESCE(f_loginID, \"\")) IN (' . $placeholders . ')
                 OR TRIM(COALESCE(f_stafID, \"\")) IN (' . $placeholders . ')
                 OR TRIM(COALESCE(f_nopekerja, \"\")) IN (' . $placeholders . ')
                 OR CAST(f_userID AS CHAR) IN (' . $placeholders . ')
              )';

        $params = array_merge($seedCandidates, $seedCandidates, $seedCandidates, $seedCandidates);
        try {
            $stmt = $this->pdoMysql->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable) {
            return [];
        }

        $resolved = [];
        foreach ($rows as $row) {
            foreach (['f_loginID', 'f_stafID', 'f_nopekerja', 'f_userID'] as $key) {
                $value = trim((string)($row[$key] ?? ''));
                if ($value !== '') {
                    $resolved[$value] = $value;
                }
            }
        }

        return array_values($resolved);
    }

    protected function resolveCurrentUserSiteCode(array $staffCandidates): string
    {
        if ($staffCandidates === []) {
            return function_exists('distance_default_site_code') ? distance_default_site_code() : 'upnm_kampus';
        }

        $siteCode = $this->findActiveStaffSiteCode($staffCandidates);
        if ($siteCode !== '') {
            return $siteCode;
        }

        $siteCode = $this->findLatestCacheSiteCode($staffCandidates);
        if ($siteCode !== '') {
            return $siteCode;
        }

        return function_exists('distance_default_site_code') ? distance_default_site_code() : 'upnm_kampus';
    }

    protected function findActiveStaffSiteCode(array $staffCandidates): string
    {
        if ($staffCandidates === []) {
            return '';
        }

        $this->ensureStaffSiteTableExistsForRead();
        $placeholders = implode(', ', array_fill(0, count($staffCandidates), '?'));
        $sql = 'SELECT f_staffNo, f_siteCode
                FROM ' . self::STAFF_SITE_TABLE . '
                WHERE f_isActive = 1
                  AND f_staffNo IN (' . $placeholders . ')';

        $stmt = $this->pdoMysql->prepare($sql);
        $stmt->execute($staffCandidates);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if ($rows === []) {
            return '';
        }

        $siteByStaff = [];
        foreach ($rows as $row) {
            $staffNo = trim((string)($row['f_staffNo'] ?? ''));
            $siteCode = function_exists('distance_normalize_site_code')
                ? distance_normalize_site_code((string)($row['f_siteCode'] ?? ''))
                : trim((string)($row['f_siteCode'] ?? ''));
            if ($staffNo !== '' && $siteCode !== '') {
                $siteByStaff[$staffNo] = $siteCode;
            }
        }

        foreach ($staffCandidates as $candidate) {
            if (isset($siteByStaff[$candidate])) {
                return (string)$siteByStaff[$candidate];
            }
        }

        return '';
    }

    protected function findLatestCacheSiteCode(array $staffCandidates): string
    {
        $distanceTable = function_exists('distance_result_table_name')
            ? distance_result_table_name()
            : 'tbl_m_staff_distance_cache';
        if ($staffCandidates === []) {
            return '';
        }

        $placeholders = implode(', ', array_fill(0, count($staffCandidates), '?'));
        $sql = 'SELECT f_siteCode
                FROM ' . $distanceTable . '
                WHERE f_stafID IN (' . $placeholders . ')
                  AND f_resultJson IS NOT NULL
                  AND TRIM(f_resultJson) <> ""
                ORDER BY f_lastCalculatedAt DESC, f_cacheID DESC
                LIMIT 1';
        $stmt = $this->pdoMysql->prepare($sql);
        $stmt->execute($staffCandidates);
        $siteCode = (string)($stmt->fetchColumn() ?: '');

        return $siteCode !== '' && function_exists('distance_normalize_site_code')
            ? distance_normalize_site_code($siteCode)
            : trim($siteCode);
    }

    protected function loadCurrentUserMapRecord(array $staffCandidates, string $resolvedSiteCode): ?array
    {
        $distanceTable = function_exists('distance_result_table_name')
            ? distance_result_table_name()
            : 'tbl_m_staff_distance_cache';
        if ($staffCandidates === []) {
            return null;
        }

        $this->ensureStaffSiteTableExistsForRead();
        $placeholders = implode(', ', array_fill(0, count($staffCandidates), '?'));
        $sql = '
            SELECT
                c.f_cacheID,
                c.f_stafID,
                c.f_siteCode AS f_cacheSiteCode,
                COALESCE(s.f_siteCode, c.f_siteCode) AS f_siteCode,
                c.f_homeAddress,
                c.f_homeAddressJson,
                c.f_distanceKm,
                c.f_source,
                c.f_routeProvider,
                c.f_matchQuality,
                c.f_resultJson,
                c.f_status,
                c.f_lastCalculatedAt,
                s.f_staffName,
                s.f_email,
                s.f_department,
                s.f_departmentCode,
                s.f_position,
                s.f_isActive
            FROM ' . $distanceTable . ' c
            LEFT JOIN ' . self::STAFF_SITE_TABLE . ' s
              ON s.f_staffNo = c.f_stafID
            WHERE c.f_stafID IN (' . $placeholders . ')
              AND (s.f_staffNo IS NULL OR s.f_isActive = 1)
              AND c.f_resultJson IS NOT NULL
              AND TRIM(c.f_resultJson) <> \'\'
            ORDER BY (COALESCE(s.f_siteCode, c.f_siteCode) = ?) DESC,
                     f_lastCalculatedAt DESC,
                     f_cacheID DESC
            LIMIT 1';

        $params = array_merge($staffCandidates, [$resolvedSiteCode]);
        $stmt = $this->pdoMysql->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }

        return $this->mapCacheRowToStaffLocation($row, true);
    }

    protected function ensureStaffSiteTableExistsForRead(): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }

        $checked = true;
        try {
            $this->pdoMysql->query('SELECT 1 FROM ' . self::STAFF_SITE_TABLE . ' LIMIT 1');
        } catch (Throwable) {
        }
    }
}
