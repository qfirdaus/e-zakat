<?php
// ajax/user-add.php
// Staff-only add flow from Sybase data into tbl_m_user
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

try {
    ob_start();
    require_once __DIR__ . '/../includes/init.php';
    $initOutput = ob_get_clean();
    require_once __DIR__ . '/_helpers.php';
    logAjaxUnexpectedOutput('user-add:init.php', $initOutput);

    if (empty($_SESSION['f_stafID'])) {
        jsonErrorResponse((string)(__('unauthorized_access') ?: 'Sila log masuk terlebih dahulu.'), 401);
    }

    require_once __DIR__ . '/../classes/Database.php';

    $pdo = Database::getInstance('mysql')->getConnection();
    ensureAjaxGroupManagePermission($pdo);

    // Rate limiting: max 20 requests per 60 seconds
    if (!checkRateLimit('user_add', 20, 60)) {
        jsonErrorResponse('Terlalu banyak permintaan. Sila cuba lagi selepas beberapa saat.', 429);
    }

    $readPayload = static function (): array {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (!is_array($data)) {
            jsonErrorResponse('Data tidak sah.', 400);
        }

        if (!isValidCsrfToken((string)($data['csrf_token'] ?? ''))) {
            jsonErrorResponse((string)__('userGroup_csrf_invalid'), 400);
        }

        $nopekerja = trim((string)($data['nopekerja'] ?? ''));
        $idpekerja = trim((string)($data['idpekerja'] ?? ''));
        $scope = strtolower(trim((string)($data['scope'] ?? 'staff')));
        $groupID = (int)($data['groupID'] ?? 0);
        $flag = isset($data['flag']) ? (int)$data['flag'] : 1;

        if ($scope !== 'staff' && $scope !== 'staf') {
            jsonErrorResponse('Flow tambah pengguna ini khusus untuk staf sahaja.', 400);
        }

        if ($nopekerja === '') {
            jsonErrorResponse('No. pekerja tidak boleh kosong.', 400);
        }

        if (!in_array($flag, [0, 1], true)) {
            $flag = 1;
        }

        return [
            'data' => $data,
            'scope' => $scope,
            'nopekerja' => $nopekerja,
            'idpekerja' => $idpekerja,
            'groupID' => $groupID,
            'flag' => $flag,
        ];
    };

    $resolveGroup = static function (PDO $pdo, int $groupID): array {
        if ($groupID <= 0) {
            jsonErrorResponse('Kumpulan pengguna tidak sah atau tidak wujud dalam sistem.', 400);
        }

        $groupCheckSql = "SELECT f_groupID, f_groupKod, f_categoryUser FROM tbl_m_group WHERE f_groupID = :groupID LIMIT 1";
        $groupCheckStmt = $pdo->prepare($groupCheckSql);
        $groupCheckStmt->execute([':groupID' => $groupID]);
        $groupRow = $groupCheckStmt->fetch(PDO::FETCH_ASSOC);

        if (!$groupRow) {
            jsonErrorResponse('Kumpulan pengguna tidak sah atau tidak wujud dalam sistem.', 400);
        }

        $groupCategory = strtoupper(trim((string)($groupRow['f_categoryUser'] ?? '')));
        if ($groupCategory !== 'STAF') {
            jsonErrorResponse('Kumpulan yang dipilih tidak sah untuk akses staf.', 400);
        }

        return [
            'groupID' => (int)($groupRow['f_groupID'] ?? 0),
            'groupKod' => (string)($groupRow['f_groupKod'] ?? ''),
        ];
    };

    $ensureUserNotExists = static function (PDO $pdo, string $nopekerja): void {
        $checkSql = "SELECT f_userID FROM tbl_m_user WHERE f_stafID = :staff_identifier OR TRIM(COALESCE(f_loginID, '')) = :login_identifier LIMIT 1";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([
            ':staff_identifier' => $nopekerja,
            ':login_identifier' => $nopekerja,
        ]);
        if ($checkStmt->fetch()) {
            jsonErrorResponse('Pengguna dengan no. pekerja ini sudah wujud dalam sistem.', 409);
        }
    };

    $fetchSybaseUser = static function (string $nopekerja): array {
        $pdoSybase = Database::pdoSybaseStaff();
        $sybaseSql = "
            SELECT 
                nopekerja,
                idpekerja,
                gelar_nama,
                nama,
                nokp,
                email,
                handphone,
                kdjwtsemasa,
                jawatansemasa,
                kdjenis,
                jenis,
                kdjbtnsemasa,
                jabatansemasa,
                kumpjwt,
                kodstatus,
                status
            FROM v630staf_service_skim_all
            WHERE nopekerja = :nopekerja
              AND CONVERT(INT, kodstatus) = 1
        ";
        $sybaseStmt = $pdoSybase->prepare($sybaseSql);
        $sybaseStmt->execute([':nopekerja' => $nopekerja]);
        $sybaseUser = $sybaseStmt->fetch(PDO::FETCH_ASSOC);
        error_log('[user-add] Sybase query result: ' . ($sybaseUser ? 'found' : 'not found') . ' for nopekerja: ' . $nopekerja);

        if (!$sybaseUser) {
            jsonErrorResponse('Staf tidak dijumpai dalam sistem Sybase atau tidak aktif.', 404);
        }

        return $sybaseUser;
    };

    $deriveAuditUserId = static function (): ?int {
        if (!empty($_SESSION['user']['f_userID']) && is_numeric($_SESSION['user']['f_userID'])) {
            return (int)$_SESSION['user']['f_userID'];
        }
        if (!empty($_SESSION['f_userID']) && is_numeric($_SESSION['f_userID'])) {
            return (int)$_SESSION['f_userID'];
        }

        $candidate = $_SESSION['f_nopekerja'] ?? $_SESSION['user']['f_nopekerja'] ?? $_SESSION['f_stafID'] ?? null;
        if ($candidate) {
            if (is_numeric($candidate)) {
                return (int)$candidate;
            }
            if (preg_match('/^(\d+)/', (string)$candidate, $m)) {
                return (int)$m[1];
            }
        }

        if (!empty($_SESSION['f_stafID'])) {
            try {
                $lookupPdo = Database::getInstance('mysql')->getConnection();
                require_once __DIR__ . '/../classes/User.php';
                $lookupUserModel = new User($lookupPdo);
                $userProfile = $lookupUserModel->getProfile($_SESSION['f_stafID']);
                if (!empty($userProfile['f_nopekerja'])) {
                    $cand = $userProfile['f_nopekerja'];
                    if (is_numeric($cand)) {
                        return (int)$cand;
                    }
                    if (preg_match('/^(\d+)/', (string)$cand, $m2)) {
                        return (int)$m2[1];
                    }
                }
            } catch (Throwable $e) {
                error_log('[user-add] user_id derivation DB lookup failed: ' . $e->getMessage());
            }
        }

        return null;
    };

    $buildAddAuditData = static function (string $nopekerja, array $sybaseUser, int $groupID, string $groupKod, int $flag, int $newUserId) use ($deriveAuditUserId): array {
        $requestId = $GLOBALS['__AUDIT_REQUEST_ID'] ?? null;
        $sessionId = session_id() ?: null;
        $nama = $_SESSION['user']['f_nama'] ?? $_SESSION['f_nama'] ?? null;
        $nostaf = $_SESSION['f_nopekerja'] ?? $_SESSION['user']['f_nopekerja'] ?? null;
        $formattedActorLabel = function_exists('audit_format_actor_label')
            ? audit_format_actor_label($nama, $nostaf)
            : $nama;
        $message = audit_format_message('User created from Sybase data', $formattedActorLabel);
        $userId = $deriveAuditUserId();
        $targetName = trim((string)($sybaseUser['gelar_nama'] ?? $sybaseUser['nama'] ?? $nopekerja));
        $statusSuffix = '';
        if (!empty($sybaseUser['status'])) {
            $st = trim((string)$sybaseUser['status']);
            if ($st !== '' && stripos($targetName, $st) === false) {
                $statusSuffix = ' (' . $st . ')';
            }
        }

        error_log("[user-add] Audit prep: request_id={$requestId}, session_id={$sessionId}, user_id=" . ($userId ?? 'null') . ", actor={$formattedActorLabel}");

        return [
            'event_type' => 'CREATE',
            'severity' => 'INFO',
            'outcome' => 'SUCCESS',
            'target_type' => 'user',
            'target_id' => (string)$nopekerja,
            'target_label' => 'User: ' . $targetName . $statusSuffix,
            'message' => $message,
            'request_id' => $requestId,
            'session_id' => $sessionId,
            'user_id' => $userId,
            'actor_label' => $formattedActorLabel,
            'meta' => [
                'groupID' => $groupID,
                'groupKod' => $groupKod,
                'flag' => $flag,
                'source' => 'user_add_ajax',
                'userID' => $newUserId,
            ],
        ];
    };

    $insertUserFromSybase = static function (
        PDO $pdo,
        string $nopekerja,
        string $idpekerja,
        array $sybaseUser,
        int $groupID,
        string $groupKod,
        int $flag,
        ?string $loggedInStafID
    ): int {
        $nokp = $sybaseUser['nokp'] ?? '';
        $hashedPassword = '';
        if (!empty($nokp)) {
            $hashedPassword = password_hash($nokp, PASSWORD_DEFAULT);
        }

        $insertSql = "
            INSERT INTO tbl_m_user (
                f_loginID,
                f_stafID,
                f_categoryUser,
                f_nopekerja,
                f_nama,
                f_nickname,
                f_nokp,
                f_password,
                f_email,
                f_handphone,
                f_jawatanKod,
                f_jawatan,
                f_jenisID,
                f_jenis,
                f_jabatanKod,
                f_namajabatan,
                f_kumpjawatan,
                f_verified_at,
                f_must_change_password,
                f_password_changed_at,
                f_password_expires_at,
                f_statusID,
                f_status,
                f_groupID,
                f_groupKod,
                f_flag,
                f_insertdt,
                f_updatedt,
                f_updateby,
                f_remarks
            ) VALUES (
                :loginID,
                :stafID,
                :categoryUser,
                :idpekerja,
                :gelar_nama,
                :nama,
                :nokp,
                :password,
                :email,
                :handphone,
                :kdjwtsemasa,
                :jawatansemasa,
                :kdjenis,
                :jenis,
                :kdjbtnsemasa,
                :jabatansemasa,
                :kumpjwt,
                NOW(),
                1,
                NULL,
                NULL,
                :kodstatus,
                :status,
                :groupID,
                :groupKod,
                :flag,
                NOW(),
                NOW(),
                :updateby,
                :remarks
            )
        ";

        $insertStmt = $pdo->prepare($insertSql);
        $result = $insertStmt->execute([
            ':loginID' => $nopekerja,
            ':stafID' => $nopekerja,
            ':categoryUser' => 'STAF',
            ':idpekerja' => $idpekerja ?: ($sybaseUser['idpekerja'] ?? null),
            ':gelar_nama' => $sybaseUser['gelar_nama'] ?? null,
            ':nama' => $sybaseUser['nama'] ?? null,
            ':nokp' => $sybaseUser['nokp'] ?? null,
            ':password' => $hashedPassword,
            ':email' => $sybaseUser['email'] ?? null,
            ':handphone' => $sybaseUser['handphone'] ?? null,
            ':kdjwtsemasa' => $sybaseUser['kdjwtsemasa'] ?? null,
            ':jawatansemasa' => $sybaseUser['jawatansemasa'] ?? null,
            ':kdjenis' => !empty($sybaseUser['kdjenis']) ? (int)$sybaseUser['kdjenis'] : null,
            ':jenis' => $sybaseUser['jenis'] ?? null,
            ':kdjbtnsemasa' => $sybaseUser['kdjbtnsemasa'] ?? null,
            ':jabatansemasa' => $sybaseUser['jabatansemasa'] ?? null,
            ':kumpjwt' => $sybaseUser['kumpjwt'] ?? null,
            ':kodstatus' => !empty($sybaseUser['kodstatus']) ? (int)$sybaseUser['kodstatus'] : null,
            ':status' => $sybaseUser['status'] ?? null,
            ':groupID' => $groupID,
            ':groupKod' => $groupKod,
            ':flag' => $flag,
            ':updateby' => $loggedInStafID,
            ':remarks' => 'Added via Tambah Pengguna form',
        ]);

        if (!$result) {
            throw new Exception('Gagal menyimpan data pengguna.');
        }

        return (int)$pdo->lastInsertId();
    };

    $logUserAddAudit = static function (string $nopekerja, array $sybaseUser, int $groupID, string $groupKod, int $flag, int $newUserId) use ($buildAddAuditData): ?int {
        if (!function_exists('audit_event')) {
            error_log('[user-add] audit_event() function not found');
            return null;
        }

        $auditData = $buildAddAuditData($nopekerja, $sybaseUser, $groupID, $groupKod, $flag, $newUserId);
        $eventId = audit_event($auditData);
        error_log("[user-add] Audit event result: event_id=" . ($eventId ?? 'null') . ", request_id=" . ($auditData['request_id'] ?? 'null') . ", session_id=" . ($auditData['session_id'] ?? 'null') . ", user_id=" . ($auditData['user_id'] ?? 'null'));
        return $eventId ?: null;
    };

    $clearAddCaches = static function (): void {
        if (isset($_SESSION['userlist_cache']['staf_options_list'])) {
            unset($_SESSION['userlist_cache']['staf_options_list']);
        }
    };

    $payload = $readPayload();
    $nopekerja = $payload['nopekerja'];
    $idpekerja = $payload['idpekerja'];
    $groupID = $payload['groupID'];
    $flag = $payload['flag'];

    $group = $resolveGroup($pdo, $groupID);
    $groupID = $group['groupID'];
    $groupKod = $group['groupKod'];

    $ensureUserNotExists($pdo, $nopekerja);
    $sybaseUser = $fetchSybaseUser($nopekerja);

    // Get logged in user for audit
    $loggedInStafID = $_SESSION['f_stafID'] ?? null;
    $newUserId = $insertUserFromSybase($pdo, $nopekerja, $idpekerja, $sybaseUser, $groupID, $groupKod, $flag, $loggedInStafID);

    // Audit: Log user creation
    try {
        $logUserAddAudit($nopekerja, $sybaseUser, $groupID, $groupKod, $flag, $newUserId);
    } catch (\Throwable $e) {
        // Don't block user creation if audit fails
        error_log('[user-add] Audit logging failed: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    }

    $clearAddCaches();
    
    jsonSuccessResponse([
        'message' => 'Pengguna berjaya ditambah.',
        'userID' => $newUserId
    ]);

} catch (PDOException $e) {
    error_log('[user-add] PDO Error: ' . $e->getMessage());
    
    // Check for duplicate entry
    if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate') !== false) {
        jsonErrorResponse('Pengguna dengan no. pekerja ini sudah wujud dalam sistem.', 409);
    } else {
        jsonErrorResponse('Ralat database: ' . $e->getMessage(), 500);
    }
} catch (Throwable $e) {
    error_log('[user-add] Error: ' . $e->getMessage());
    jsonErrorResponse('Ralat sistem semasa menambah pengguna.', 500);
}
