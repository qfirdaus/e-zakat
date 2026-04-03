<?php
// ajax/role-switch.php
// Switch active role for current session (group_active_id)
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

while (ob_get_level() > 0) {
    @ob_end_clean();
}

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../includes/init.php';
    require_login();
    require_once __DIR__ . '/_helpers.php';
    require_once __DIR__ . '/../classes/Database.php';
    require_once __DIR__ . '/../classes/User.php';

    if (!isValidCsrfToken()) {
        http_response_code(400);
        echo json_encode(['error' => true, 'message' => (string)__('userGroup_csrf_invalid')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['error' => true, 'message' => 'Data tidak sah.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $groupID = (int)($data['groupID'] ?? 0);
    if ($groupID <= 0) {
        http_response_code(422);
        echo json_encode(['error' => true, 'message' => 'Peranan tidak sah.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = Database::getInstance('mysql')->getConnection();
    $userModel = new User($pdo);
    $profile = [];
    $sessionLoginID = trim((string)($_SESSION['f_loginID'] ?? ''));
    $sessionStafID = trim((string)($_SESSION['f_stafID'] ?? ''));
    if ($sessionLoginID !== '') {
        $profile = $userModel->getProfileByLoginID($sessionLoginID) ?: [];
    }
    if (!$profile && $sessionStafID !== '') {
        $profile = $userModel->getProfile($sessionStafID) ?: [];
    }

    if (!$profile) {
        http_response_code(403);
        echo json_encode(['error' => true, 'message' => 'Akses ditolak.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $userID = (int)($profile['f_userID'] ?? $_SESSION['f_userID'] ?? 0);
    $stafID = (string)($profile['f_stafID'] ?? $_SESSION['f_stafID'] ?? '');
    $stafRaw = trim($stafID);
    $stafNorm = str_replace('-', '', $stafRaw);
    if ($userID <= 0 && $stafRaw === '' && $stafNorm === '') {
        http_response_code(403);
        echo json_encode(['error' => true, 'message' => 'Akses ditolak.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $defaultGroupId = (int)($profile['f_groupID'] ?? 0);
    if (!isset($_SESSION['group_default_id']) && $defaultGroupId > 0) {
        $_SESSION['group_default_id'] = $defaultGroupId;
    }
    // SECURITY CRITICAL – DO NOT MODIFY: role switch validation must enforce allowed roles
    // Allow switch to default role without tbl_ref_access
    if ($groupID !== $defaultGroupId) {
        // Validate role exists in tbl_ref_access for this user
        $stmt = $pdo->prepare("
            SELECT a.f_groupID
            FROM tbl_ref_access a
            WHERE a.f_status = 1
              AND a.f_groupID = :gid
              AND (
                a.f_userID = :uid
                OR (
                  :uid_zero = 0
                  AND (TRIM(a.f_stafID) = :staf OR REPLACE(TRIM(a.f_stafID), '-', '') = :staf_norm)
                )
              )
            LIMIT 1
        ");
        $stmt->execute([':uid' => $userID, ':uid_zero' => $userID, ':staf' => $stafRaw, ':staf_norm' => $stafNorm, ':gid' => $groupID]);
        $ok = $stmt->fetchColumn();
        if (!$ok) {
            http_response_code(403);
            echo json_encode(['error' => true, 'message' => 'Peranan tidak dibenarkan untuk pengguna ini.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    $oldGroupId = (int)($_SESSION['group_active_id'] ?? 0);

    // SECURITY CRITICAL – DO NOT MODIFY: session active role drives access decisions
    $_SESSION['group_active_id'] = $groupID;

    // Store flash for UI message after reload
    try {
        $stmtName = $pdo->prepare("SELECT f_groupName FROM tbl_m_group WHERE f_groupID = :gid LIMIT 1");
        $stmtName->execute([':gid' => $groupID]);
        $rowName = $stmtName->fetch(PDO::FETCH_ASSOC);
        $_SESSION['role_switch_success'] = [
            'group_id' => $groupID,
            'group_name' => (string)($rowName['f_groupName'] ?? '')
        ];
    } catch (Throwable $e) {
        $_SESSION['role_switch_success'] = [
            'group_id' => $groupID,
            'group_name' => ''
        ];
    }

    // GOVERNANCE CRITICAL – DO NOT MODIFY: role switch must be auditable
    // Audit: Log role switch (session-only change)
    try {
        if (!function_exists('audit_event')) {
            $auditHelperPath = __DIR__ . '/../setting/helper/audit_helper.php';
            if (file_exists($auditHelperPath)) {
                require_once $auditHelperPath;
            }
        }
        if (function_exists('audit_event')) {
            $requestId = $GLOBALS['__AUDIT_REQUEST_ID'] ?? null;

            // Actor label
            $nama = $_SESSION['user']['f_nama'] ?? $_SESSION['f_nama'] ?? null;
            $nostaf = $_SESSION['f_nopekerja'] ?? $_SESSION['user']['f_nopekerja'] ?? null;
            $actorLabel = null;
            if (function_exists('audit_format_actor_label')) {
                $actorLabel = audit_format_actor_label($nama, $nostaf);
            } else {
                $actorLabel = $nama;
            }

            $oldGroupName = '';
            if ($oldGroupId > 0) {
                try {
                    $stmtOld = $pdo->prepare("SELECT f_groupName FROM tbl_m_group WHERE f_groupID = :gid LIMIT 1");
                    $stmtOld->execute([':gid' => $oldGroupId]);
                    $rowOld = $stmtOld->fetch(PDO::FETCH_ASSOC);
                    $oldGroupName = (string)($rowOld['f_groupName'] ?? '');
                } catch (Throwable $e) {}
            }

            $newGroupName = (string)($_SESSION['role_switch_success']['group_name'] ?? '');
            $message = function_exists('audit_format_message')
                ? audit_format_message('Role switched', $actorLabel)
                : ('Role switched' . ($actorLabel ? (' by ' . $actorLabel) : ''));

            audit_event([
                'event_type'  => 'UPDATE',
                'severity'    => 'INFO',
                'outcome'     => 'SUCCESS',
                'target_type' => 'role_switch',
                'target_id'   => (string)($_SESSION['f_stafID'] ?? ''),
                'target_label' => 'Role switch: ' . ($newGroupName !== '' ? $newGroupName : $groupID),
                'message'     => $message,
                'request_id'  => $requestId,
                'session_id'  => session_id() ?: null,
                'user_id'     => !empty($_SESSION['f_nopekerja']) && is_numeric($_SESSION['f_nopekerja']) ? (int)$_SESSION['f_nopekerja'] : (!empty($_SESSION['user']['f_nopekerja']) && is_numeric($_SESSION['user']['f_nopekerja']) ? (int)$_SESSION['user']['f_nopekerja'] : null),
                'actor_label' => $actorLabel,
                'meta'        => [
                    'old_group_id' => $oldGroupId,
                    'old_group_name' => $oldGroupName,
                    'new_group_id' => $groupID,
                    'new_group_name' => $newGroupName,
                    'default_group_id' => $defaultGroupId
                ]
            ]);
        }
    } catch (Throwable $e) {
        error_log('[role-switch] Audit logging failed: ' . $e->getMessage());
    }

    echo json_encode(['error' => false, 'message' => 'Peranan berjaya dikemas kini.'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('[role-switch] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Ralat sistem semasa menukar peranan.'], JSON_UNESCAPED_UNICODE);
}
