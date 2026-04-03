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
    $category = group_category_for_scope($scope);
    if ($category === 'PELAJAR' && function_exists('is_student_mode_enabled') && !is_student_mode_enabled()) {
        http_response_code(403);
        echo json_encode(['error' => true, 'message' => (string)__('studentSearch_mode_disabled')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Jika ada lajur penanda aktif (contoh f_flag), boleh aktifkan WHERE di bawah:
    $where = '1=1';
    $params = [];
    if ($category !== null) {
        $where .= ' AND TRIM(COALESCE(f_categoryUser, \'\')) = :category';
        $params[':category'] = $category;
    }
    // $where = 'COALESCE(f_flag,1)=1'; // uncomment jika jadual ada f_flag

    $sql = "
      SELECT
        f_groupID   AS id,
        f_groupKod  AS kod,
        f_groupName AS nama,
        f_categoryUser AS categoryUser
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
    }

    echo json_encode(['error' => false, 'groups' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(
        ['error' => true, 'message' => 'Ralat server: ' . $e->getMessage()],
        JSON_UNESCAPED_UNICODE
    );
}
