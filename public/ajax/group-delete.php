<?php
// ajax/group-delete.php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_login();
require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../setting/constants/prestasi_constants.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => true, 'message' => 'Method tidak dibenarkan.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $csrfHdr = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? '';
    if ($csrfHdr === '' || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$csrfHdr)) {
        http_response_code(400);
        echo json_encode(['error' => true, 'message' => 'CSRF token tidak sah.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!checkRateLimit('group_delete', 10, 60)) {
        http_response_code(429);
        echo json_encode(['error' => true, 'message' => 'Terlalu banyak permintaan.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo = Database::getInstance('mysql')->getConnection();
    if (!hasGroupManagePermission($pdo)) {
        http_response_code(403);
        echo json_encode(['error' => true, 'message' => 'Anda tidak mempunyai kebenaran untuk memadam kumpulan.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $json = json_decode((string)file_get_contents('php://input'), true) ?: [];
    $groupID = (int)($json['groupID'] ?? 0);
    if ($groupID <= 0) {
        http_response_code(422);
        echo json_encode(['error' => true, 'message' => 'ID kumpulan tidak sah.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT f_groupID, f_groupKod, f_groupName, COALESCE(f_modulAccess,'') AS f_modulAccess, COALESCE(f_menuAccess,'') AS f_menuAccess
        FROM tbl_m_group
        WHERE f_groupID = :gid
        LIMIT 1
    ");
    $stmt->execute([':gid' => $groupID]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$group) {
        http_response_code(404);
        echo json_encode(['error' => true, 'message' => 'Kumpulan tidak ditemui.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $groupKod = (string)($group['f_groupKod'] ?? '');
    $groupName = (string)($group['f_groupName'] ?? '');
    $modulAccess = trim((string)($group['f_modulAccess'] ?? ''));
    $menuAccess = trim((string)($group['f_menuAccess'] ?? ''));

    // Safety: only allow deletion when both access fields are empty.
    if ($modulAccess !== '' || $menuAccess !== '') {
        http_response_code(422);
        echo json_encode(['error' => true, 'message' => 'Kumpulan yang masih mempunyai akses modul/menu tidak boleh dipadam.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Safety: prevent deletion of system/default group (Super Admin).
    $saId = defined('PRESTASI_ROLE_ID_ADM_SA') ? (int)PRESTASI_ROLE_ID_ADM_SA : 0;
    $saCode = defined('PRESTASI_ROLE_KOD_ADM_SA')
        ? (string)PRESTASI_ROLE_KOD_ADM_SA
        : (defined('PRESTASI_ROLE_ADM_SA') ? (string)PRESTASI_ROLE_ADM_SA : 'ADM-SA');
    if (($saId > 0 && $groupID === $saId) || (strtoupper(trim($groupKod)) === strtoupper(trim($saCode)))) {
        try {
            audit_event([
                'event_type' => 'DELETE',
                'severity' => 'WARN',
                'outcome' => 'DENIED',
                'target_type' => 'group',
                'target_id' => (string)$groupID,
                'target_label' => $groupName !== '' ? $groupName : $groupKod,
                'message' => 'DELETE_GROUP blocked (system/default group)',
                'meta' => [
                    'action_type' => 'DELETE_GROUP',
                    'status' => 'FAILED',
                    'group_id' => $groupID,
                    'group_code' => $groupKod,
                    'reason' => 'system_group_protected',
                ],
            ]);
        } catch (Throwable $e) {
            error_log('[group-delete] Audit failed: ' . $e->getMessage());
        }

        http_response_code(422);
        echo json_encode(['error' => true, 'message' => 'Kumpulan sistem tidak boleh dipadam.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_m_user WHERE f_groupID = :gid");
    $cntStmt->execute([':gid' => $groupID]);
    $userCount = (int)($cntStmt->fetchColumn() ?: 0);

    if ($userCount > 0) {
        try {
            audit_event([
                'event_type' => 'DELETE',
                'severity' => 'WARN',
                'outcome' => 'DENIED',
                'target_type' => 'group',
                'target_id' => (string)$groupID,
                'target_label' => $groupName !== '' ? $groupName : $groupKod,
                'message' => 'DELETE_GROUP blocked (users still assigned)',
                'meta' => [
                    'action_type' => 'DELETE_GROUP',
                    'status' => 'FAILED',
                    'group_id' => $groupID,
                    'group_code' => $groupKod,
                    'users_assigned' => $userCount,
                    'reason' => 'users_still_assigned',
                ],
            ]);
        } catch (Throwable $e) {
            error_log('[group-delete] Audit failed: ' . $e->getMessage());
        }

        http_response_code(409);
        echo json_encode([
            'error' => true,
            'message' => 'Masih terdapat pengguna yang ditetapkan kepada kumpulan ini. Sila pindahkan pengguna terlebih dahulu sebelum memadam kumpulan.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $del = $pdo->prepare("DELETE FROM tbl_m_group WHERE f_groupID = :gid LIMIT 1");
    $del->execute([':gid' => $groupID]);
    if ($del->rowCount() <= 0) {
        http_response_code(500);
        echo json_encode(['error' => true, 'message' => 'Gagal memadam kumpulan.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    clearGroupUiCaches($groupID);
    clearSidebarNavigationCaches();

    try {
        audit_event([
            'event_type' => 'DELETE',
            'severity' => 'INFO',
            'outcome' => 'SUCCESS',
            'target_type' => 'group',
            'target_id' => (string)$groupID,
            'target_label' => $groupName !== '' ? $groupName : $groupKod,
            'message' => 'DELETE_GROUP success',
            'meta' => [
                'action_type' => 'DELETE_GROUP',
                'status' => 'SUCCESS',
                'group_id' => $groupID,
                'group_code' => $groupKod,
                'group_name' => $groupName,
            ],
        ]);
    } catch (Throwable $e) {
        error_log('[group-delete] Audit failed: ' . $e->getMessage());
    }

    echo json_encode(['error' => false, 'message' => 'Kumpulan berjaya dipadam.'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Ralat server: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
