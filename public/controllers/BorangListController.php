<?php
declare(strict_types=1);

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Borang.php';

class BorangListController
{
    public array $senaraiBorang = [];
    public array $senaraiKategori = [];
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance('mysql')->getConnection();
        $model = new Borang($this->pdo);

        $groupID = $_SESSION['f_groupID'] ?? null;
        $isSuperAdmin = ($_SESSION['f_groupKod'] ?? '') === 'ADM-SA';

        // ✅ Super Admin nampak semua
        if ($isSuperAdmin) {
            $this->senaraiBorang = $model->getAllActive();
        } 
        // ✅ User biasa ikut kategori
        elseif ($groupID) {
            $this->senaraiBorang = $model->getByKategori((int)$groupID);
        } 
        else {
            $this->senaraiBorang = [];
        }

        // Dropdown kategori (jika perlu)
        $this->senaraiKategori = $this->pdo
        ->query("SELECT f_groupID,f_groupName FROM tbl_m_group ORDER BY f_groupID")
        ->fetchAll(PDO::FETCH_ASSOC);
        }
}