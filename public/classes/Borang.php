<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class Borang extends BaseModel
{
    /**
     * Ambil semua borang aktif
     */
    public function getAllActive(): array
    {
        $sql = "
            SELECT 
                b.f_borangID,
                b.f_nama_ms,
                b.f_nama_en,
                b.f_path,
                b.f_icon,
                b.f_flag,
                b.f_order,
                g.f_groupName
            FROM tbl_m_borang b
            LEFT JOIN tbl_m_group g 
                ON g.f_groupID = b.f_kategoriID
            WHERE b.f_flag = 1
            ORDER BY b.f_order ASC
        ";

        return $this->fetchAll($sql);
    }

    /**
     * Ambil satu borang ikut ID
     */
    public function findById(int $borangID): ?array
    {
        $sql = "
            SELECT *
            FROM tbl_m_borang
            WHERE f_borangID = :id
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id' => $borangID]);
    }

    /**
     * Ambil borang ikut kategori (group)
     */
    public function getByKategori(int $groupID): array
    {
        $sql = "
                        SELECT 
                                b.f_borangID,
                                b.f_nama_ms,
                                b.f_nama_en,
                                b.f_path,
                                b.f_icon,
                                b.f_flag,
                                b.f_order,
                                g.f_groupName
                        FROM tbl_m_borang b
                        LEFT JOIN tbl_m_group g
                                ON g.f_groupID = b.f_kategoriID
                        WHERE b.f_kategoriID = :gid
                            AND b.f_flag = 1
                        ORDER BY b.f_order ASC
        ";

        return $this->fetchAll($sql, [':gid' => $groupID]);
    }

    /**
     * Tambah borang baharu
     */
    public function create(array $data): bool
    {
        $sql = "
            INSERT INTO tbl_m_borang
            (f_nama_ms, f_nama_en, f_kategoriID, f_path, f_icon, f_flag, f_order)
            VALUES
            (:nama_ms, :nama_en, :kategoriID, :path, :icon, :flag, :order)
        ";

        return $this->execute($sql, [
            ':nama_ms'    => $data['nama_ms'] ?? '',
            ':nama_en'    => $data['nama_en'] ?? null,
            ':kategoriID' => $data['kategoriID'] ?? null,
            ':path'       => $data['path'] ?? '',
            ':icon'       => $data['icon'] ?? null,
            ':flag'       => $data['flag'] ?? 1,
            ':order'      => $data['order'] ?? 1,
        ]);
    }

    public function update(int $id, array $data): bool
{
    $sql = "
        UPDATE tbl_m_borang SET
            f_nama_ms = :nama_ms,
            f_nama_en = :nama_en,
            f_kategoriID = :kategoriID,
            f_path = :path,
            f_icon = :icon,
            f_flag = :flag,
            f_order = :order
        WHERE f_borangID = :id
    ";

    return $this->execute($sql, [
        ':id'         => $id,
        ':nama_ms'    => $data['nama_ms'] ?? '',
        ':nama_en'    => $data['nama_en'] ?? null,
        ':kategoriID' => $data['kategoriID'] ?? null,
        ':path'       => $data['path'] ?? '',
        ':icon'       => $data['icon'] ?? null,
        ':flag'       => $data['flag'] ?? 1,
        ':order'      => $data['order'] ?? 1,
    ]);
}

public function softDelete(int $id): bool
 {
    return $this->execute(
        "UPDATE tbl_m_borang SET f_flag = 0 WHERE f_borangID = :id",
        [':id' => $id]
    );
 }
}