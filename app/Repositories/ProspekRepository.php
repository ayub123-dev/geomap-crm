<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class ProspekRepository
{
    private $db;
    private $table = 'prospek';

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Create prospek dari kunjungan
     */
    public function create($data)
    {
        $sql = "INSERT INTO {$this->table} (
                    kunjungan_id, customer_id,
                    nama_toko, alamat,
                    latitude, longitude,
                    estimasi_omzet, kategori_produk,
                    prioritas, status,
                    foto_toko_path, catatan,
                    created_at, updated_at
                ) VALUES (
                    :kunjungan_id, :customer_id,
                    :nama_toko, :alamat,
                    :latitude, :longitude,
                    :estimasi_omzet, :kategori_produk,
                    :prioritas, :status,
                    :foto_toko_path, :catatan,
                    :created_at, :updated_at
                )";

        $stmt = $this->db->prepare($sql);
        
        $now = date('Y-m-d H:i:s');
        $data['created_at'] = $data['created_at'] ?? $now;
        $data['updated_at'] = $data['updated_at'] ?? $now;
        $data['status'] = $data['status'] ?? 'prospek';

        return $stmt->execute($data);
    }

    /**
     * Update prospek
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
     * Get prospek by ID
     */
    public function findById($id)
    {
        $sql = "SELECT p.*, 
                    k.salesman_id,
                    c.name as customer_name
                FROM {$this->table} p
                LEFT JOIN kunjungan k ON p.kunjungan_id = k.id
                LEFT JOIN customers c ON p.customer_id = c.id
                WHERE p.id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get prospek by kunjungan ID
     */
    public function getByKunjunganId($kunjungan_id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE kunjungan_id = :kunjungan_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['kunjungan_id' => $kunjungan_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get daftar prospek dengan filter
     */
    public function getList($filters = [])
    {
        $sql = "SELECT p.*, 
                    k.salesman_id,
                    c.name as customer_name
                FROM {$this->table} p
                LEFT JOIN kunjungan k ON p.kunjungan_id = k.id
                LEFT JOIN customers c ON p.customer_id = c.id
                WHERE 1=1";

        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND p.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['prioritas'])) {
            $sql .= " AND p.prioritas = :prioritas";
            $params['prioritas'] = $filters['prioritas'];
        }

        if (!empty($filters['salesman_id'])) {
            $sql .= " AND k.salesman_id = :salesman_id";
            $params['salesman_id'] = $filters['salesman_id'];
        }

        $sql .= " ORDER BY p.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Konversi prospek ke customer inti
     */
    public function convertToCustomerInti($prospek_id, $salesman_id)
    {
        $this->update($prospek_id, ['status' => 'customer_inti']);

        // Jika belum ada customer, buat baru
        $prospek = $this->findById($prospek_id);
        if (!$prospek['customer_id']) {
            // Create customer dulu
            $customerRepo = new CustomerRepository();
            $customer_id = $customerRepo->create([
                'customer_code' => 'PROSPEK-' . date('YmdHis'),
                'name' => $prospek['nama_toko'],
                'address' => $prospek['alamat'],
                'latitude' => $prospek['latitude'],
                'longitude' => $prospek['longitude'],
                'status' => 'active'
            ]);

            // Link prospek ke customer
            $this->update($prospek_id, ['customer_id' => $customer_id]);
        }

        return true;
    }

    /**
     * Get status summary
     */
    public function getStatusSummary($salesman_id = null)
    {
        $sql = "SELECT 
                    p.status,
                    p.prioritas,
                    COUNT(*) as jumlah
                FROM {$this->table} p
                LEFT JOIN kunjungan k ON p.kunjungan_id = k.id
                WHERE 1=1";

        $params = [];
        if ($salesman_id) {
            $sql .= " AND k.salesman_id = :salesman_id";
            $params['salesman_id'] = $salesman_id;
        }

        $sql .= " GROUP BY p.status, p.prioritas";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
