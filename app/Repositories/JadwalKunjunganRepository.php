<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class JadwalKunjunganRepository
{
    private $db;
    private $table = 'jadwal_kunjungan';

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Simpan jadwal kunjungan baru
     */
    public function create($data)
    {
        $sql = "INSERT INTO {$this->table} (
                    salesman_id, customer_id,
                    hari_dalam_minggu, minggu_ke,
                    is_active, created_at, updated_at
                ) VALUES (
                    :salesman_id, :customer_id,
                    :hari_dalam_minggu, :minggu_ke,
                    :is_active, :created_at, :updated_at
                )";

        $stmt = $this->db->prepare($sql);
        
        $now = date('Y-m-d H:i:s');
        $data['created_at'] = $data['created_at'] ?? $now;
        $data['updated_at'] = $data['updated_at'] ?? $now;
        $data['is_active'] = $data['is_active'] ?? 1;

        return $stmt->execute($data);
    }

    /**
     * Update jadwal kunjungan
     */
    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = "{$key} = :{$key}";
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $data['id'] = $id;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Hapus jadwal kunjungan (soft delete via is_active)
     */
    public function deactivate($id)
    {
        return $this->update($id, ['is_active' => 0]);
    }

    /**
     * Get jadwal kunjungan by ID
     */
    public function findById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get jadwal kunjungan untuk salesman
     */
    public function getBySalesmanId($salesman_id)
    {
        $sql = "SELECT jk.*, 
                    c.name as customer_name,
                    c.address,
                    c.latitude, c.longitude
                FROM {$this->table} jk
                JOIN customers c ON jk.customer_id = c.id
                WHERE jk.salesman_id = :salesman_id 
                AND jk.is_active = 1
                ORDER BY jk.hari_dalam_minggu, jk.minggu_ke";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['salesman_id' => $salesman_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get jadwal untuk hari tertentu
     */
    public function getByHari($salesman_id, $hari_dalam_minggu)
    {
        $sql = "SELECT jk.*, 
                    c.name as customer_name,
                    c.address,
                    c.latitude, c.longitude,
                    c.phone, c.email
                FROM {$this->table} jk
                JOIN customers c ON jk.customer_id = c.id
                WHERE jk.salesman_id = :salesman_id 
                AND jk.hari_dalam_minggu = :hari_dalam_minggu
                AND jk.is_active = 1
                ORDER BY c.name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'salesman_id' => $salesman_id,
            'hari_dalam_minggu' => $hari_dalam_minggu
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Bulk insert jadwal (untuk master setup)
     */
    public function bulkCreate($schedules)
    {
        $sql = "INSERT INTO {$this->table} (
                    salesman_id, customer_id,
                    hari_dalam_minggu, minggu_ke,
                    is_active, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $now = date('Y-m-d H:i:s');

        foreach ($schedules as $schedule) {
            $stmt->execute([
                $schedule['salesman_id'],
                $schedule['customer_id'],
                $schedule['hari_dalam_minggu'],
                $schedule['minggu_ke'] ?? null,
                $schedule['is_active'] ?? 1,
                $now,
                $now
            ]);
        }

        return true;
    }
}
