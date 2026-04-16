<?php
declare(strict_types=1);

@ini_set('display_errors', '0');
error_reporting(E_ALL);
require_once __DIR__ . '/../includes/init.php';
require_login();
require_once __DIR__ . '/../controllers/ProfileController.php';
require_once __DIR__ . '/../classes/User.php';

header('Content-Type: application/json; charset=utf-8');

$eventId = isset($_REQUEST['event_id']) ? (int)$_REQUEST['event_id'] : 0;
if ($eventId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid event id']);
    exit;
}

$pdo = null;
try {
    $controller = new ProfileController();
    // Reuse controller's PDO via reflection (fallback)
    $ref = new ReflectionClass($controller);
    $prop = $ref->getProperty('pdoMysql');
    $prop->setAccessible(true);
    $pdo = $prop->getValue($controller);
} catch (Throwable $e) {
    error_log('[profile-audit-event-meta] Failed to access DB: ' . $e->getMessage());
}

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Database unavailable']);
    exit;
}

try {
    $userModel = new User($pdo);
    $profile = $userModel->getProfile((string)($_SESSION['f_stafID'] ?? ''));
    $isSuperAdmin = $profile && function_exists('is_user_super_admin') && is_user_super_admin($profile, $pdo);
    if (!$isSuperAdmin) {
        http_response_code(403);
        echo json_encode([
            'error' => 'forbidden',
            'message' => __('profile_metadata_forbidden_text') ?: 'Metadata jejak audit hanya tersedia untuk semakan Super Admin.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Fetch event meta from audit_event
    $sql = "SELECT meta FROM audit_event WHERE id = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $eventId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    $meta = null;
    if ($row && !empty($row['meta'])) {
        $meta = json_decode($row['meta'], true);
    }

    // Fetch change sets and fields
    $changeSets = [];
    $sqlCS = "SELECT id, target_type, target_id, change_reason, meta FROM audit_change_set WHERE event_id = :eventId ORDER BY id ASC";
    $stmtCS = $pdo->prepare($sqlCS);
    $stmtCS->execute([':eventId' => $eventId]);
    while ($cs = $stmtCS->fetch(PDO::FETCH_ASSOC)) {
        $csId = (int)($cs['id'] ?? 0);
        $csMeta = null;
        if (!empty($cs['meta'])) $csMeta = json_decode($cs['meta'], true);

        $fields = [];
        if ($csId > 0) {
            $sqlF = "SELECT field, old_value, new_value, data_type, is_sensitive, diff_hint FROM audit_change_field WHERE change_set_id = :changeSetId ORDER BY id ASC";
            $stmtF = $pdo->prepare($sqlF);
            $stmtF->execute([':changeSetId' => $csId]);
            while ($f = $stmtF->fetch(PDO::FETCH_ASSOC)) {
                $fields[] = [
                    'field' => (string)($f['field'] ?? ''),
                    'old_value' => $f['old_value'],
                    'new_value' => $f['new_value'],
                    'data_type' => (string)($f['data_type'] ?? 'string'),
                    'is_sensitive' => !empty($f['is_sensitive']),
                    'diff_hint' => $f['diff_hint'] ? (string)$f['diff_hint'] : null,
                ];
            }
        }

        $changeSets[] = [
            'id' => $csId,
            'target_type' => (string)($cs['target_type'] ?? ''),
            'target_id' => (string)($cs['target_id'] ?? ''),
            'change_reason' => $cs['change_reason'] ? (string)$cs['change_reason'] : null,
            'meta' => $csMeta,
            'field_changes' => $fields,
        ];
    }

    echo json_encode(['meta' => $meta, 'change_sets' => $changeSets], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    error_log('[profile-audit-event-meta] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
    exit;
}
