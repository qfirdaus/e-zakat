<?php
// classes/Modul.php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * ✅ Model Modul untuk sistem e-Prestasi (MySQL)
 * - Papar menu jika f_flag = 1
 * - Susun ikut COALESCE(f_order, 99999), f_*ID
 * - Jika $menuIDs kosong, ambil SEMUA menu aktif di modul tersebut
 */
class Modul extends BaseModel
{
    private function getModulNameField(string $lang): string
    {
        $supported = ['ms', 'en', 'zh', 'ta'];
        return in_array($lang, $supported, true) ? "f_modulName_{$lang}" : "f_modulName_ms";
    }

    private function getMenuNameField(string $lang): string
    {
        $supported = ['ms', 'en', 'zh', 'ta'];
        return in_array($lang, $supported, true) ? "f_menuName_{$lang}" : "f_menuName_ms";
    }

    /** ✅ Dapatkan semua modul */
    public function getAllModul(string $lang = 'ms'): array
    {
        $nameField = $this->getModulNameField($lang);
        $sql = "SELECT 
                    f_modulID, 
                    {$nameField} AS modulName, 
                    COALESCE(f_icon,'ri-folder-fill') AS f_icon, 
                    f_order
                FROM tbl_m_modul
                ORDER BY COALESCE(f_order, 99999), f_modulID";
        return $this->fetchAll($sql);
    }

    /** ✅ Dapatkan semua menu anak bagi satu modul (flag=1 sahaja) */
    public function getChildMenu(int $modulID, string $lang = 'ms'): array
    {
        $nameField = $this->getMenuNameField($lang);
        $sql = "SELECT 
                    f_menuID, 
                    {$nameField} AS menuName, 
                    f_path, 
                    f_flag, 
                    f_order
                FROM tbl_m_menu
                WHERE f_modulID = :modulID
                  AND f_flag = 1
                ORDER BY COALESCE(f_order, 99999), f_menuID";
        return $this->fetchAll($sql, [':modulID' => $modulID]);
    }

    /** ✅ Dapatkan modul yang dibenarkan oleh group */
    public function getAllModulByGroup(array $modulIDs, string $lang = 'ms'): array
    {
        if (empty($modulIDs)) return [];
        [$ph, $bind] = $this->inClause('mid', array_map('intval', $modulIDs));

        $nameField = $this->getModulNameField($lang);
        $sql = "SELECT 
                    f_modulID, 
                    {$nameField} AS modulName, 
                    COALESCE(f_icon,'ri-folder-fill') AS f_icon, 
                    f_order
                FROM tbl_m_modul
                WHERE f_modulID IN ({$ph})
                ORDER BY COALESCE(f_order, 99999), f_modulID";
        return $this->fetchAll($sql, $bind);
    }

    /**
     * ✅ Dapatkan menu anak mengikut senarai menuID (akses group)
     * - Jika $menuIDs kosong → ambil SEMUA menu modul yang aktif (flag=1)
     * - Jika $menuIDs ada → tapis IN (...) + flag=1
     */
    public function getChildMenuByIDs(int $modulID, array $menuIDs, string $lang = 'ms'): array
    {
        $nameField = $this->getMenuNameField($lang);

        $where = "WHERE f_modulID = :modulID AND f_flag = 1";
        $bind  = [':modulID' => $modulID];

        if (!empty($menuIDs)) {
            [$ph, $inBind] = $this->inClause('menu', array_map('intval', $menuIDs));
            $where .= " AND f_menuID IN ({$ph})";
            $bind   = array_merge($bind, $inBind);
        }

        $sql = "SELECT 
                    f_menuID, 
                    f_path, 
                    {$nameField} AS menuName, 
                    f_flag, 
                    f_order
                FROM tbl_m_menu
                {$where}
                ORDER BY COALESCE(f_order, 99999), f_menuID";

        return $this->fetchAll($sql, $bind);
    }

    /**
     * ✅ Batch load semua menus untuk multiple moduls (fix N+1 query problem)
     * - Load semua menus untuk semua modulIDs sekali gus
     * - Return array grouped by modulID: [modulID => [menus...]]
     * 
     * @param array $modulIDs Array of modul IDs
     * @param array $menuIDs Array of allowed menu IDs (empty = all active menus)
     * @param string $lang Language code
     * @return array Associative array: [modulID => [menu1, menu2, ...]]
     */
    public function getAllMenusByModulIDs(array $modulIDs, array $menuIDs, string $lang = 'ms'): array
    {
        if (empty($modulIDs)) return [];

        $nameField = $this->getMenuNameField($lang);
        
        // Build WHERE clause for modulIDs
        [$modulPh, $modulBind] = $this->inClause('mod', array_map('intval', $modulIDs));
        
        $where = "WHERE f_modulID IN ({$modulPh}) AND f_flag = 1";
        $bind  = $modulBind;

        // Add menuIDs filter if provided
        if (!empty($menuIDs)) {
            [$menuPh, $menuBind] = $this->inClause('menu', array_map('intval', $menuIDs));
            $where .= " AND f_menuID IN ({$menuPh})";
            $bind = array_merge($bind, $menuBind);
        }

        $sql = "SELECT 
                    f_modulID,
                    f_menuID, 
                    f_path, 
                    {$nameField} AS menuName, 
                    COALESCE(f_domain, 'SHARED') AS f_domain,
                    COALESCE(f_show_staff_only, 1) AS f_show_staff_only,
                    f_flag, 
                    f_order
                FROM tbl_m_menu
                {$where}
                ORDER BY f_modulID, COALESCE(f_order, 99999), f_menuID";

        $allMenus = $this->fetchAll($sql, $bind);
        
        // Group by modulID
        $grouped = [];
        foreach ($allMenus as $menu) {
            $modulID = (int)$menu['f_modulID'];
            if (!isset($grouped[$modulID])) {
                $grouped[$modulID] = [];
            }
            // Remove f_modulID from menu array (not needed in result)
            unset($menu['f_modulID']);
            $grouped[$modulID][] = $menu;
        }

        return $grouped;
    }
}
