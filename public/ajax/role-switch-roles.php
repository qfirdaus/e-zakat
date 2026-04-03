<?php
// ajax/role-switch-roles.php
// Return latest allowed roles for role switcher modal
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_login();
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = Database::getInstance('mysql')->getConnection();
    $userModel = new User($pdo);

    $stafSession = trim((string)($_SESSION['f_stafID'] ?? ''));
    $loginSession = trim((string)($_SESSION['f_loginID'] ?? ''));
    $profile = [];
    if ($loginSession !== '') {
        $profile = $userModel->getProfileByLoginID($loginSession) ?: [];
    }
    if (!$profile && $stafSession !== '') {
        $profile = $userModel->getProfile($stafSession) ?: [];
    }
    if (!$profile) {
        http_response_code(403);
        echo json_encode(['error' => true, 'message' => 'Akses ditolak.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $userID = (int)($profile['f_userID'] ?? $_SESSION['f_userID'] ?? 0);
    $stafID = (string)($profile['f_stafID'] ?? $stafSession ?? '');
    $stafRaw = trim($stafID);
    $stafNorm = str_replace('-', '', $stafRaw);
    if ($userID <= 0 && $stafRaw === '' && $stafNorm === '') {
        http_response_code(403);
        echo json_encode(['error' => true, 'message' => 'Akses ditolak.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $defaultGroupId = (int)($profile['f_groupID'] ?? 0);
    $defaultGroupName = (string)($profile['f_groupName'] ?? 'Pengguna');

    if (!isset($_SESSION['group_default_id']) && $defaultGroupId > 0) {
        $_SESSION['group_default_id'] = $defaultGroupId;
    }
    if (!isset($_SESSION['group_active_id']) && $defaultGroupId > 0) {
        $_SESSION['group_active_id'] = $defaultGroupId;
    }
    $activeGroupId = (int)($_SESSION['group_active_id'] ?? $defaultGroupId);

    // SECURITY CRITICAL – DO NOT MODIFY: allowed roles list for role switcher
    $stmtRoles = $pdo->prepare("
      SELECT a.f_groupID, g.f_groupName
      FROM tbl_ref_access a
      JOIN tbl_m_group g ON g.f_groupID = a.f_groupID
      WHERE a.f_status = 1
        AND (
          a.f_userID = :uid
          OR (
            :uid_zero = 0
            AND (TRIM(a.f_stafID) = :staf OR REPLACE(TRIM(a.f_stafID), '-', '') = :staf_norm)
          )
        )
      ORDER BY g.f_groupName ASC
    ");
    $stmtRoles->execute([':uid' => $userID, ':uid_zero' => $userID, ':staf' => $stafRaw, ':staf_norm' => $stafNorm]);
    $roles = $stmtRoles->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $out = [
        'error' => false,
        'default' => ['id' => $defaultGroupId, 'name' => $defaultGroupName],
        'active_id' => $activeGroupId,
        'roles' => array_map(function($r){
            return [
                'id' => (int)($r['f_groupID'] ?? 0),
                'name' => (string)($r['f_groupName'] ?? ''),
            ];
        }, $roles),
    ];

    echo json_encode($out, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Ralat pelayan.'], JSON_UNESCAPED_UNICODE);
}
