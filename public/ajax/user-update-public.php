<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

try {
    ob_start();
    require_once __DIR__ . '/../includes/init.php';
    $initOutput = ob_get_clean();
    require_once __DIR__ . '/_helpers.php';
    logAjaxUnexpectedOutput('user-update-public:init.php', $initOutput);

    if (empty($_SESSION['f_stafID'])) {
        jsonErrorResponse((string)(__('unauthorized_access') ?: 'Sila log masuk terlebih dahulu.'), 401);
    }

    require_once __DIR__ . '/../classes/Database.php';
    $pdo = Database::getInstance('mysql')->getConnection();
    ensureAjaxGroupManagePermission($pdo);

    if (!checkRateLimit('user_update_public', 30, 60)) {
        jsonErrorResponse('Terlalu banyak permintaan. Sila cuba lagi selepas beberapa saat.', 429);
    }

    $csrfHeader = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!isValidCsrfToken($csrfHeader)) {
        jsonErrorResponse((string)__('userGroup_csrf_invalid'), 400);
    }

    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    if (!is_array($data)) {
        jsonErrorResponse('Data tidak sah.', 400);
    }

    $userID = (int)($data['userID'] ?? 0);
    $groupID = (int)($data['groupID'] ?? 0);
    $flag = isset($data['flag']) ? (int)$data['flag'] : 1;
    $name = trim((string)($data['name'] ?? ''));
    $nickname = trim((string)($data['nickname'] ?? ''));
    $email = strtolower(trim((string)($data['email'] ?? '')));
    $phone = trim((string)($data['phone'] ?? ''));
    $university = trim((string)($data['university'] ?? ''));
    $nokp = trim((string)($data['nokp'] ?? ''));
    $password = (string)($data['password'] ?? '');
    $passwordConfirm = (string)($data['password_confirm'] ?? '');

    if ($userID <= 0) {
        jsonErrorResponse('Parameter pengguna tidak sah.', 422);
    }
    if ($name === '') {
        jsonErrorResponse('Nama tidak boleh kosong.', 400);
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonErrorResponse('Alamat emel tidak sah.', 400);
    }
    if (!in_array($flag, [0, 1], true)) {
        $flag = 1;
    }
    if ($password !== '' && strlen($password) < 6) {
        jsonErrorResponse('Kata laluan mesti sekurang-kurangnya 6 aksara.', 400);
    }
    if (($password !== '' || $passwordConfirm !== '') && $password !== $passwordConfirm) {
        jsonErrorResponse('Pengesahan kata laluan tidak sepadan.', 400);
    }

    $stmtUser = $pdo->prepare("
        SELECT f_userID, f_categoryUser, f_stafID, f_loginID, f_email
        FROM tbl_m_user
        WHERE f_userID = :userID
        LIMIT 1
    ");
    $stmtUser->execute([':userID' => $userID]);
    $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if (!$userRow) {
        jsonErrorResponse('Pengguna tidak ditemui.', 404);
    }
    if (strtoupper(trim((string)($userRow['f_categoryUser'] ?? ''))) !== 'UMUM') {
        jsonErrorResponse('Hanya pengguna umum boleh dikemas kini melalui flow ini.', 400);
    }

    $stmtGroup = $pdo->prepare("
        SELECT f_groupID, f_groupKod, f_groupName, f_categoryUser
        FROM tbl_m_group
        WHERE f_groupID = :groupID
        LIMIT 1
    ");
    $stmtGroup->execute([':groupID' => $groupID]);
    $groupRow = $stmtGroup->fetch(PDO::FETCH_ASSOC);
    if (!$groupRow) {
        jsonErrorResponse('Kumpulan pengguna tidak sah atau tidak wujud dalam sistem.', 400);
    }
    if (strtoupper(trim((string)($groupRow['f_categoryUser'] ?? ''))) !== 'UMUM') {
        jsonErrorResponse('Kumpulan yang dipilih tidak sah untuk akses umum.', 400);
    }

    $stmtDup = $pdo->prepare("
        SELECT f_userID
        FROM tbl_m_user
        WHERE f_userID <> :userID
          AND (
            TRIM(COALESCE(f_loginID, '')) = :loginID
            OR LOWER(TRIM(COALESCE(f_email, ''))) = :email
          )
        LIMIT 1
    ");
    $stmtDup->execute([
        ':userID' => $userID,
        ':loginID' => $email,
        ':email' => $email,
    ]);
    if ($stmtDup->fetch(PDO::FETCH_ASSOC)) {
        jsonErrorResponse('Pengguna dengan alamat emel ini sudah wujud dalam sistem.', 409);
    }

    $setParts = [
        'f_loginID = :loginID',
        'f_nama = :nama',
        'f_nickname = :nickname',
        'f_nokp = :nokp',
        'f_email = :email',
        'f_handphone = :handphone',
        'f_namajabatan = :namajabatan',
        'f_groupID = :groupID',
        'f_groupKod = :groupKod',
        'f_flag = :flag',
        'f_status = :status',
        'f_updatedt = NOW()',
        'f_updateby = :updateby',
    ];
    $params = [
        ':userID' => $userID,
        ':loginID' => $email,
        ':nama' => $name,
        ':nickname' => ($nickname !== '' ? $nickname : $name),
        ':nokp' => ($nokp !== '' ? $nokp : null),
        ':email' => $email,
        ':handphone' => ($phone !== '' ? $phone : null),
        ':namajabatan' => ($university !== '' ? $university : null),
        ':groupID' => (int)$groupRow['f_groupID'],
        ':groupKod' => (string)$groupRow['f_groupKod'],
        ':flag' => $flag,
        ':status' => $flag === 1 ? 'AKTIF' : 'DISAHKAN',
        ':updateby' => (string)($_SESSION['f_stafID'] ?? ''),
    ];

    if ($password !== '') {
        $setParts[] = 'f_password = :password';
        $setParts[] = 'f_verified_at = COALESCE(f_verified_at, NOW())';
        $setParts[] = 'f_must_change_password = 1';
        $setParts[] = 'f_password_changed_at = NULL';
        $setParts[] = 'f_password_expires_at = NULL';
        $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    $sql = "UPDATE tbl_m_user SET " . implode(', ', $setParts) . " WHERE f_userID = :userID";
    $stmtUpdate = $pdo->prepare($sql);
    $stmtUpdate->execute($params);

    if (function_exists('audit_event')) {
        try {
            $actorName = $_SESSION['user']['f_nama'] ?? $_SESSION['f_nama'] ?? null;
            $actorLoginID = $_SESSION['f_loginID'] ?? $_SESSION['user']['f_loginID'] ?? ($_SESSION['f_stafID'] ?? null);
            $actorLabel = function_exists('audit_format_actor_label')
                ? audit_format_actor_label($actorName, $actorLoginID)
                : (string)($actorName ?? '');
            audit_event([
                'event_type' => 'UPDATE',
                'severity' => 'INFO',
                'outcome' => 'SUCCESS',
                'target_type' => 'user',
                'target_id' => (string)$userID,
                'target_label' => 'Public User: ' . $name,
                'message' => function_exists('audit_format_message')
                    ? audit_format_message('Public user updated manually', $actorLabel)
                    : 'Public user updated manually',
                'request_id' => $GLOBALS['__AUDIT_REQUEST_ID'] ?? null,
                'session_id' => session_id() ?: null,
                'user_id' => !empty($_SESSION['f_userID']) && is_numeric($_SESSION['f_userID']) ? (int)$_SESSION['f_userID'] : null,
                'actor_label' => $actorLabel,
                'meta' => [
                    'target_userID' => $userID,
                    'target_loginID' => $email,
                    'target_category' => 'UMUM',
                    'email' => $email,
                    'university' => $university,
                    'groupID' => (int)$groupRow['f_groupID'],
                    'groupKod' => (string)$groupRow['f_groupKod'],
                    'flag' => $flag,
                    'password_reset' => $password !== '',
                    'source' => 'user_update_public_ajax',
                ],
            ]);
        } catch (Throwable $auditError) {
            error_log('[user-update-public] Audit logging failed: ' . $auditError->getMessage());
        }
    }

    jsonSuccessResponse([
        'message' => 'Maklumat pengguna umum berjaya dikemas kini.',
        'group' => [
            'id' => (int)$groupRow['f_groupID'],
            'kod' => (string)$groupRow['f_groupKod'],
            'nama' => (string)($groupRow['f_groupName'] ?? $groupRow['f_groupKod']),
        ],
        'user' => [
            'id' => $userID,
            'name' => $name,
            'nickname' => ($nickname !== '' ? $nickname : $name),
            'loginID' => $email,
            'email' => $email,
            'phone' => $phone,
            'university' => $university,
            'nokp' => $nokp,
        ],
    ]);
} catch (PDOException $e) {
    error_log('[user-update-public] PDO Error: ' . $e->getMessage());
    jsonErrorResponse('Ralat database: ' . $e->getMessage(), 500);
} catch (Throwable $e) {
    error_log('[user-update-public] Error: ' . $e->getMessage());
    jsonErrorResponse('Ralat sistem. Sila hubungi pentadbir sistem.', 500);
}
