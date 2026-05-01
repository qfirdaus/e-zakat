<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/init.php';
require_login();
require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../classes/Database.php';

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error'=>true,'message'=>(string)__('userGroup_method_not_allowed')], JSON_UNESCAPED_UNICODE); exit;
  }
  
  // Rate limiting: max 30 requests per 60 seconds (read operation)
  if (!checkRateLimit('menu_list', 30, 60)) {
    http_response_code(429);
    echo json_encode(['error'=>true,'message'=>(string)__('userGroup_rate_limit_text')], JSON_UNESCAPED_UNICODE); exit;
  }
  
  // Check cache (10 min TTL untuk menu list - lebih kerap berubah)
  $modulID = isset($_GET['modulID']) ? (int)$_GET['modulID'] : null;
  $all = isset($_GET['all']) ? (int)$_GET['all'] : 0;
  $active = isset($_GET['active']) ? (int)$_GET['active'] : null;
  $cacheKey = 'menu_list_' . ($modulID ?? 'all') . '_' . ($all ? 'all' : '') . '_' . ($active ?? 'any');
  $cached = GroupDataCache::get($cacheKey, 600);
  if ($cached !== null) {
    header('X-Cache: HIT');
    echo json_encode($cached, JSON_UNESCAPED_UNICODE);
    exit;
  }

  $pdo = Database::pdoMysql();
  ensureAjaxGroupManagePermission($pdo);

  $modulID = isset($_GET['modulID']) ? (int)$_GET['modulID'] : null;
  $all     = isset($_GET['all']) ? (int)$_GET['all'] : 0;          // ?all=1 → semua modul
  $active  = isset($_GET['active']) ? (int)$_GET['active'] : null; // ?active=1 → hanya aktif

  $sql = "SELECT
            f_menuID  AS id,
            f_modulID AS modulID,
            COALESCE(NULLIF(f_menuName_ms,''), NULLIF(f_menuName_en,''), f_path, CONCAT('Menu ', f_menuID)) AS nama,
            f_path    AS path,
            COALESCE(f_domain,'SHARED') AS domain,
            COALESCE(f_show_staff_only,1) AS show_staff_only,
            CAST(f_flag AS UNSIGNED) AS flag,
            f_order AS menuOrder
          FROM tbl_m_menu";
  $conds = [];
  $params = [];

  if (!$all && $modulID) { $conds[] = "f_modulID = ?"; $params[] = $modulID; }
  if ($all && $modulID)  { $conds[] = "f_modulID = ?"; $params[] = $modulID; }
  if ($active !== null)  { $conds[] = "f_flag = ?";    $params[] = $active; }

  if ($conds) $sql .= " WHERE ".implode(' AND ', $conds);
  $sql .= " ORDER BY f_modulID ASC,
                   COALESCE(f_order, 99999) ASC,
                   f_menuID ASC";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $result = ['error'=>false, 'menus'=>$menus, 'count'=>count($menus)];
  
  // Store in cache
  GroupDataCache::set($cacheKey, $result);
  header('X-Cache: MISS');
  echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>true,'message'=>(string)__('userGroup_server_error_prefix') . ' ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
