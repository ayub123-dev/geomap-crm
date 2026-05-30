<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class KunjunganRepository
{
    private $db;
    private $table = 'kunjungan';

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Simpan kunjungan baru (checkin)
     */
    public function create($data)
    {
        $sql = "INSERT INTO {$this->table} (
                    salesman_id, customer_id, jadwal_kunjungan_id,
                    tanggal_kunjungan, waktu_checkin,
                    gps_latitude, gps_longitude,
                    customer_latitude, customer_longitude,
                    jarak_meter, validasi_jarak,
                    kondisi_toko, potensi_penjualan, catatan, rating,
                    status, created_at, updated_at
                ) VALUES (
                    :salesman_id, :customer_id, :jadwal_kunjungan_id,
                    :tanggal_kunjungan, :waktu_checkin,
                    :gps_latitude, :gps_longitude,
                    :customer_latitude, :customer_longitude,
                    :jarak_meter, :validasi_jarak,
                    :kondisi_toko, :potensi_penjualan, :catatan, :rating,
                    :status, :created_at, :updated_at
                )";

        $stmt = $this->db->prepare($sql);
        
        $now = date('Y-m-d H:i:s');
        $data['created_at'] = $data['created_at'] ?? $now;
        $data['updated_at'] = $data['updated_at'] ?? $now;

        return $stmt->execute($data);
    }

    /**
     * Update kunjungan
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
     * Get kunjungan by ID
     */
    public function findById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get daftar kunjungan salesman pada tanggal tertentu
     */
    public function getByTanggal($salesman_id, $tanggal)
    {
        $sql = "SELECT k.*, 
                    c.name as customer_name, c.latitude as customer_latitude, c.longitude as customer_longitude,
                    c.address, c.phone, c.email
                FROM {$this->table} k
                JOIN customers c ON k.customer_id = c.id
                WHERE k.salesman_id = :salesman_id 
                AND k.tanggal_kunjungan = :tanggal
                ORDER BY k.created_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['salesman_id' => $salesman_id, 'tanggal' => $tanggal]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get target kunjungan hari ini untuk salesman
     */
    public function getTargetHariIni($salesman_id)
    {
        $today = date('Y-m-d');
        $dayName = $this->getDayNameIndonesian(date('N'));

        $sql = "SELECT DISTINCT
                    ci.id as customer_inti_id,
                    c.id as customer_id,
                    c.name,
                    c.address,
                    c.latitude,
                    c.longitude,
                    c.phone,
                    c.email,
                    jk.id as jadwal_kunjungan_id,
                    COALESCE(k.id, NULL) as kunjungan_hari_ini
                FROM customer_inti ci
                JOIN customers c ON ci.customer_id = c.id
                LEFT JOIN jadwal_kunjungan jk ON ci.customer_id = jk.customer_id 
                    AND jk.salesman_id = ci.salesman_id
                    AND jk.hari_dalam_minggu = :day_name
                    AND jk.is_active = 1
                LEFT JOIN kunjungan k ON ci.customer_id = k.customer_id 
                    AND k.salesman_id = ci.salesman_id
                    AND k.tanggal_kunjungan = :today
                WHERE ci.salesman_id = :salesman_id
                ORDER BY c.name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'salesman_id' => $salesman_id,
            'day_name' => $dayName,
            'today' => $today
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get kunjungan status summary untuk salesman
     */
    public function getStatusSummary($salesman_id, $tanggal = null)
    {
        $tanggal = $tanggal ?? date('Y-m-d');

        $sql = "SELECT 
                    status,
                    COUNT(*) as jumlah
                FROM {$this->table}
                WHERE salesman_id = :salesman_id
                AND tanggal_kunjungan = :tanggal
                GROUP BY status";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['salesman_id' => $salesman_id, 'tanggal' => $tanggal]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Helper: konversi nomor hari (1-7) ke nama hari Indonesia
     */
    private function getDayNameIndonesian($dayNumber)
    {
        $days = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu'
        ];
        return $days[$dayNumber] ?? 'Senin';
    }
}
