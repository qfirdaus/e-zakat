<?php
// ajax/user-add-student.php
// Student-only add flow from v210 data into tbl_m_user
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

try {
    ob_start();
    require_once __DIR__ . '/../includes/init.php';
    $initOutput = ob_get_clean();
    require_once __DIR__ . '/_helpers.php';
    require_once __DIR__ . '/../includes/functions-db.php';
    logAjaxUnexpectedOutput('user-add-student:init.php', $initOutput);

    if (empty($_SESSION['f_stafID'])) {
        jsonErrorResponse((string)(__('unauthorized_access') ?: 'Sila log masuk terlebih dahulu.'), 401);
    }

    require_once __DIR__ . '/../classes/Database.php';

    $pdo = Database::getInstance('mysql')->getConnection();
    ensureAjaxGroupManagePermission($pdo);

    if (!is_student_mode_enabled()) {
        jsonErrorResponse((string)__('studentSearch_mode_disabled'), 403);
    }

    if (!checkRateLimit('user_add_student', 20, 60)) {
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

        $matrik = trim((string)($data['matrik'] ?? ''));
        $scope = strtolower(trim((string)($data['scope'] ?? 'student')));
        $groupID = (int)($data['groupID'] ?? 0);
        $flag = isset($data['flag']) ? (int)$data['flag'] : 1;

        if ($scope !== 'student' && $scope !== 'pelajar') {
            jsonErrorResponse('Flow tambah pengguna ini khusus untuk pelajar sahaja.', 400);
        }

        if ($matrik === '') {
            jsonErrorResponse('No. matrik tidak boleh kosong.', 400);
        }

        if (!in_array($flag, [0, 1], true)) {
            $flag = 1;
        }

        return [
            'matrik' => $matrik,
            'groupID' => $groupID,
            'flag' => $flag,
        ];
    };

    $resolveGroup = static function (PDO $pdo, int $groupID): array {
        if ($groupID <= 0) {
            jsonErrorResponse('Kumpulan pengguna tidak sah atau tidak wujud dalam sistem.', 400);
        }

        $sql = "SELECT f_groupID, f_groupKod, f_categoryUser FROM tbl_m_group WHERE f_groupID = :groupID LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':groupID' => $groupID]);
        $groupRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$groupRow) {
            jsonErrorResponse('Kumpulan pengguna tidak sah atau tidak wujud dalam sistem.', 400);
        }

        if (strtoupper(trim((string)($groupRow['f_categoryUser'] ?? ''))) !== 'PELAJAR') {
            jsonErrorResponse('Kumpulan yang dipilih tidak sah untuk akses pelajar.', 400);
        }

        return [
            'groupID' => (int)($groupRow['f_groupID'] ?? 0),
            'groupKod' => (string)($groupRow['f_groupKod'] ?? ''),
        ];
    };

    $ensureUserNotExists = static function (PDO $pdo, string $identifier): void {
        $sql = "SELECT f_userID FROM tbl_m_user WHERE f_stafID = :identifier OR TRIM(COALESCE(f_loginID, '')) = :identifier LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':identifier' => $identifier]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            jsonErrorResponse('Pelajar dengan nombor matrik ini sudah wujud dalam sistem.', 409);
        }
    };

    $fetchStudent = static function (string $matrik): array {
        $pdoSybase = Database::pdoSybaseStudent();
        if (!$pdoSybase) {
            jsonErrorResponse((string)__('studentSearch_mode_disabled'), 403);
        }

        $sql = "
            SELECT
                matrik,
                nama,
                nokp,
                email,
                hpno,
                telno,
                telno_terkini,
                notel_terkini,
                kdprogram,
                program,
                kdfakulti,
                fakulti,
                kdtahap,
                tahap_pengajian,
                kadet,
                kategori_kadet,
                status,
                statusketerangan,
                statuskategori
            FROM v210
            WHERE convert(varchar(50), matrik) = :matrik
              AND upper(convert(varchar(20), statuskategori)) = 'AKTIF'
        ";
        $stmt = $pdoSybase->prepare($sql);
        $stmt->execute([':matrik' => $matrik]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            jsonErrorResponse('Pelajar tidak dijumpai dalam sumber data atau tidak aktif.', 404);
        }

        return $row;
    };

    $derivePhone = static function (array $student): ?string {
        $candidates = [
            trim((string)($student['notel_terkini'] ?? '')),
            trim((string)($student['hpno'] ?? '')),
            trim((string)($student['telno_terkini'] ?? '')),
            trim((string)($student['telno'] ?? '')),
        ];
        foreach ($candidates as $candidate) {
            if ($candidate !== '') {
                return $candidate;
            }
        }
        return null;
    };

    $deriveAuditUserId = static function (): ?int {
        if (!empty($_SESSION['user']['f_userID']) && is_numeric($_SESSION['user']['f_userID'])) {
            return (int)$_SESSION['user']['f_userID'];
        }
        if (!empty($_SESSION['f_userID']) && is_numeric($_SESSION['f_userID'])) {
            return (int)$_SESSION['f_userID'];
        }
        return null;
    };

    $insertStudent = static function (
        PDO $pdo,
        array $student,
        int $groupID,
        string $groupKod,
        int $flag,
        ?string $loggedInStafID,
        callable $derivePhone
    ): int {
        $nokp = trim((string)($student['nokp'] ?? ''));
        $hashedPassword = $nokp !== '' ? password_hash($nokp, PASSWORD_DEFAULT) : '';
        $kumpjawatan = trim((string)($student['kadet'] ?? ''));

        $sql = "
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
                :identifier,
                'PELAJAR',
                NULL,
                :nama,
                :nickname,
                :nokp,
                :password,
                :email,
                :handphone,
                :kdprogram,
                :program,
                :kdtahap,
                :tahap,
                :kdfakulti,
                :fakulti,
                :kumpjawatan,
                NOW(),
                1,
                NULL,
                NULL,
                :statusid,
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

        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([
            ':loginID' => trim((string)($student['matrik'] ?? '')),
            ':identifier' => trim((string)($student['matrik'] ?? '')),
            ':nama' => trim((string)($student['nama'] ?? '')) ?: null,
            ':nickname' => trim((string)($student['nama'] ?? '')) ?: null,
            ':nokp' => $nokp !== '' ? $nokp : null,
            ':password' => $hashedPassword,
            ':email' => trim((string)($student['email'] ?? '')) ?: null,
            ':handphone' => $derivePhone($student),
            ':kdprogram' => trim((string)($student['kdprogram'] ?? '')) ?: null,
            ':program' => trim((string)($student['program'] ?? '')) ?: null,
            ':kdtahap' => trim((string)($student['kdtahap'] ?? '')) ?: null,
            ':tahap' => trim((string)($student['tahap_pengajian'] ?? '')) ?: null,
            ':kdfakulti' => trim((string)($student['kdfakulti'] ?? '')) ?: null,
            ':fakulti' => trim((string)($student['fakulti'] ?? '')) ?: null,
            ':kumpjawatan' => $kumpjawatan !== '' ? $kumpjawatan : null,
            ':statusid' => null,
            ':status' => trim((string)($student['statuskategori'] ?? '')) ?: null,
            ':groupID' => $groupID,
            ':groupKod' => $groupKod,
            ':flag' => $flag,
            ':updateby' => $loggedInStafID,
            ':remarks' => 'Added via Tambah Pelajar form',
        ]);

        if (!$ok) {
            throw new RuntimeException('Gagal menyimpan data pelajar.');
        }

        return (int)$pdo->lastInsertId();
    };

    $logStudentAudit = static function (array $student, int $groupID, string $groupKod, int $flag, int $newUserId) use ($deriveAuditUserId): void {
        if (!function_exists('audit_event')) {
            return;
        }

        $nama = $_SESSION['user']['f_nama'] ?? $_SESSION['f_nama'] ?? null;
        $nostaf = $_SESSION['f_nopekerja'] ?? $_SESSION['user']['f_nopekerja'] ?? null;
        $formattedActorLabel = function_exists('audit_format_actor_label')
            ? audit_format_actor_label($nama, $nostaf)
            : $nama;

        audit_event([
            'event_type' => 'CREATE',
            'severity' => 'INFO',
            'outcome' => 'SUCCESS',
            'target_type' => 'user',
            'target_id' => (string)($student['matrik'] ?? ''),
            'target_label' => 'Student: ' . trim((string)($student['nama'] ?? $student['matrik'] ?? '')),
            'message' => audit_format_message('Student user created from v210 data', $formattedActorLabel),
            'request_id' => $GLOBALS['__AUDIT_REQUEST_ID'] ?? null,
            'session_id' => session_id() ?: null,
            'user_id' => $deriveAuditUserId(),
            'actor_label' => $formattedActorLabel,
            'meta' => [
                'groupID' => $groupID,
                'groupKod' => $groupKod,
                'flag' => $flag,
                'source' => 'user_add_student_ajax',
                'userID' => $newUserId,
                'category' => 'PELAJAR',
            ],
        ]);
    };

    $clearStudentCaches = static function (): void {
        if (isset($_SESSION['userlist_cache']) && is_array($_SESSION['userlist_cache'])) {
            foreach (array_keys($_SESSION['userlist_cache']) as $key) {
                if (str_starts_with((string)$key, 'student_options')) {
                    unset($_SESSION['userlist_cache'][$key]);
                }
            }
        }
    };

    $payload = $readPayload();
    $matrik = $payload['matrik'];
    $group = $resolveGroup($pdo, $payload['groupID']);
    $ensureUserNotExists($pdo, $matrik);
    $student = $fetchStudent($matrik);

    $loggedInStafID = $_SESSION['f_stafID'] ?? null;
    $newUserId = $insertStudent($pdo, $student, $group['groupID'], $group['groupKod'], $payload['flag'], $loggedInStafID, $derivePhone);

    try {
        $logStudentAudit($student, $group['groupID'], $group['groupKod'], $payload['flag'], $newUserId);
    } catch (Throwable $e) {
        error_log('[user-add-student] Audit logging failed: ' . $e->getMessage());
    }

    $clearStudentCaches();

    jsonSuccessResponse([
        'message' => 'Pelajar berjaya ditambah.',
        'userID' => $newUserId,
    ]);
} catch (PDOException $e) {
    error_log('[user-add-student] PDO Error: ' . $e->getMessage());
    if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate') !== false) {
        jsonErrorResponse('Pelajar dengan nombor matrik ini sudah wujud dalam sistem.', 409);
    }
    jsonErrorResponse('Ralat database: ' . $e->getMessage(), 500);
} catch (Throwable $e) {
    error_log('[user-add-student] Error: ' . $e->getMessage());
    jsonErrorResponse('Ralat sistem semasa menambah pelajar.', 500);
}
