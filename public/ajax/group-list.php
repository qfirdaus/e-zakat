<?php
// ajax/group-list.php
declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';
require_login();
require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../includes/functions-db.php';
header('Content-Type: application/json; charset=utf-8');

function group_category_for_scope(string $scope): ?string {
    return match (strtolower(trim($scope))) {
        'staff', 'staf' => 'STAF',
        'student', 'pelajar' => 'PELAJAR',
        'public', 'umum' => 'UMUM',
        default => null,
    };
}

try {
    // Konsisten dengan modul-list.php
    $db = Database::getInstance('mysql')->getConnection();
    ensureAjaxGroupManagePermission($db);

    $scope = strtolower(trim((string)($_GET['scope'] ?? 'staff')));
    $groupID = (int)($_GET['groupID'] ?? 0);
    $category = group_category_for_scope($scope);
    if ($category === 'PELAJAR' && function_exists('is_student_mode_enabled') && !is_student_mode_enabled()) {
        http_response_code(403);
        echo json_encode(['error' => true, 'message' => (string)__('studentSearch_mode_disabled')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Jika ada lajur penanda aktif (contoh f_flag), boleh aktifkan WHERE di bawah:
    $where = '1=1';
    $params = [];
    if ($groupID > 0) {
        $where .= ' AND f_groupID = :groupID';
        $params[':groupID'] = $groupID;
    } elseif ($category !== null) {
        $where .= ' AND TRIM(COALESCE(f_categoryUser, \'\')) = :category';
        $params[':category'] = $category;
    }
    // $where = 'COALESCE(f_flag,1)=1'; // uncomment jika jadual ada f_flag

    $sql = "
      SELECT
        f_groupID   AS id,
        f_groupKod  AS kod,
        f_groupName AS nama,
        f_categoryUser AS categoryUser,
        COALESCE(f_modulAccess, '') AS modulAccess,
        COALESCE(f_menuAccess, '') AS menuAccess,
        COALESCE(f_color, '') AS color,
        COALESCE(f_priority, 0) AS priority,
        COALESCE(f_mod, 0) AS mod,
        COALESCE(f_badge_class, '') AS badgeClass,
        COALESCE(f_row_class, '') AS rowClass
      FROM tbl_m_group
      WHERE $where
      ORDER BY f_groupKod ASC, f_groupName ASC, f_groupID ASC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // (Opsyenal) pastikan id integer
    foreach ($rows as &$r) {
        if (isset($r['id'])) $r['id'] = (int)$r['id'];
        $r['modulAccess'] = array_values(array_filter(array_map('trim', explode(',', (string)($r['modulAccess'] ?? ''))), static fn($v) => $v !== ''));
        $r['menuAccess'] = array_values(array_filter(array_map('trim', explode(',', (string)($r['menuAccess'] ?? ''))), static fn($v) => $v !== ''));
        $r['priority'] = (int)($r['priority'] ?? 0);
        $r['mod'] = (int)($r['mod'] ?? 0);
    }

    $payload = ['error' => false, 'groups' => $rows];
    if ($groupID > 0) {
        $payload['group'] = $rows[0] ?? null;
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(
        ['error' => true, 'message' => 'Ralat server: ' . $e->getMessage()],
        JSON_UNESCAPED_UNICODE
    );
}
