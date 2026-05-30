<?php

namespace App\Services;

use App\Repositories\ProspekRepository;
use App\Repositories\KunjunganRepository;
use App\Repositories\CustomerRepository;

class ProspekService
{
    private $prospekRepo;
    private $kunjunganRepo;
    private $customerRepo;

    public function __construct()
    {
        $this->prospekRepo = new ProspekRepository();
        $this->kunjunganRepo = new KunjunganRepository();
        $this->customerRepo = new CustomerRepository();
    }

    /**
     * Buat prospek dari kunjungan
     */
    public function createProspekFromKunjungan($kunjungan_id, $data)
    {
        $kunjungan = $this->kunjunganRepo->findById($kunjungan_id);
        
        if (!$kunjungan) {
            return ['success' => false, 'message' => 'Kunjungan tidak ditemukan'];
        }

        $prospek_data = [
            'kunjungan_id' => $kunjungan_id,
            'customer_id' => $kunjungan['customer_id'] ?? null,
            'nama_toko' => $data['nama_toko'] ?? '',
            'alamat' => $data['alamat'] ?? $kunjungan['address'] ?? '',
            'latitude' => $data['latitude'] ?? $kunjungan['gps_latitude'],
            'longitude' => $data['longitude'] ?? $kunjungan['gps_longitude'],
            'estimasi_omzet' => $data['estimasi_omzet'] ?? null,
            'kategori_produk' => $data['kategori_produk'] ?? null,
            'prioritas' => $data['prioritas'] ?? 'C',
            'status' => 'prospek',
            'foto_toko_path' => $data['foto_toko_path'] ?? $kunjungan['foto_toko_path'] ?? null,
            'catatan' => $data['catatan'] ?? null
        ];

        $result = $this->prospekRepo->create($prospek_data);

        if (!$result) {
            return ['success' => false, 'message' => 'Gagal membuat prospek'];
        }

        // Update kunjungan status
        $this->kunjunganRepo->update($kunjungan_id, ['status' => 'prospek']);

        return [
            'success' => true,
            'message' => 'Prospek berhasil dibuat'
        ];
    }

    /**
     * Update prospek
     */
    public function updateProspek($prospek_id, $data)
    {
        $allowed_fields = ['nama_toko', 'alamat', 'latitude', 'longitude', 
                          'estimasi_omzet', 'kategori_produk', 'prioritas', 
                          'status', 'foto_toko_path', 'catatan'];
        
        $update_data = [];
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
            }
        }

        return $this->prospekRepo->update($prospek_id, $update_data);
    }

    /**
     * Ubah status prospek (prospek -> qualified -> aktif -> customer_inti)
     */
    public function updateStatus($prospek_id, $status)
    {
        $valid_status = ['prospek', 'qualified', 'aktif', 'customer_inti'];
        
        if (!in_array($status, $valid_status)) {
            return ['success' => false, 'message' => 'Status tidak valid'];
        }

        $result = $this->prospekRepo->update($prospek_id, ['status' => $status]);

        if ($status === 'customer_inti') {
            $this->prospekRepo->convertToCustomerInti($prospek_id, null);
        }

        return ['success' => $result, 'message' => 'Status berhasil diubah'];
    }

    /**
     * Get detail prospek
     */
    public function getDetailProspek($prospek_id)
    {
        return $this->prospekRepo->findById($prospek_id);
    }

    /**
     * Get prospek by kunjungan
     */
    public function getProspekByKunjungan($kunjungan_id)
    {
        return $this->prospekRepo->getByKunjunganId($kunjungan_id);
    }

    /**
     * Get daftar prospek dengan filter
     */
    public function getProspekList($filters = [])
    {
        return $this->prospekRepo->getList($filters);
    }

    /**
     * Get status summary
     */
    public function getStatusSummary($salesman_id = null)
    {
        return $this->prospekRepo->getStatusSummary($salesman_id);
    }

    /**
     * Tambah customer baru dari lapangan
     */
    public function createCustomerFromField($data)
    {
        $customer_data = [
            'customer_code' => 'FIELD-' . date('YmdHis'),
            'name' => $data['nama_toko'] ?? '',
            'address' => $data['alamat'] ?? '',
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'status' => 'draft'
        ];

        return $this->customerRepo->create($customer_data);
    }
}
