<?php

namespace App\Services;

use App\Repositories\KunjunganRepository;
use App\Repositories\JadwalKunjunganRepository;
use App\Repositories\CustomerRepository;
use App\Core\Request;

class KunjunganService
{
    private $kunjunganRepo;
    private $jadwalRepo;
    private $customerRepo;

    public function __construct()
    {
        $this->kunjunganRepo = new KunjunganRepository();
        $this->jadwalRepo = new JadwalKunjunganRepository();
        $this->customerRepo = new CustomerRepository();
    }

    /**
     * Get target kunjungan untuk hari ini
     */
    public function getTargetHariIni($salesman_id)
    {
        return $this->kunjunganRepo->getTargetHariIni($salesman_id);
    }

    /**
     * Check-in kunjungan dengan validasi GPS
     * 
     * @param int $salesman_id
     * @param int $customer_id
     * @param float $gps_latitude
     * @param float $gps_longitude
     * @param float $customer_latitude
     * @param float $customer_longitude
     * 
     * @return array ['success' => bool, 'message' => string, 'kunjungan_id' => int|null, 'validasi_jarak' => bool]
     */
    public function checkin($salesman_id, $customer_id, $gps_latitude, $gps_longitude, $customer_latitude, $customer_longitude)
    {
        // Validasi jarak
        $jarak_meter = $this->calculateDistance($gps_latitude, $gps_longitude, $customer_latitude, $customer_longitude);
        
        $validasi_jarak = $jarak_meter <= 200;

        if (!$validasi_jarak) {
            return [
                'success' => false,
                'message' => "Jarak GPS tidak valid. Anda {$jarak_meter}m dari lokasi pelanggan (max 200m)",
                'jarak_meter' => $jarak_meter,
                'validasi_jarak' => false
            ];
        }

        // Cek apakah sudah ada kunjungan hari ini
        $hari_ini = date('Y-m-d');
        $kunjungan_hari_ini = $this->kunjunganRepo->getByTanggal($salesman_id, $hari_ini);
        
        $existing = array_filter($kunjungan_hari_ini, function($k) use ($customer_id) {
            return $k['customer_id'] == $customer_id;
        });

        if (!empty($existing)) {
            return [
                'success' => false,
                'message' => 'Anda sudah check-in ke pelanggan ini hari ini'
            ];
        }

        // Cari jadwal kunjungan
        $jadwal_kunjungan_id = null;
        $dayName = $this->getDayNameIndonesian(date('N'));
        $jadwal = $this->jadwalRepo->getByHari($salesman_id, $dayName);
        
        foreach ($jadwal as $j) {
            if ($j['customer_id'] == $customer_id) {
                $jadwal_kunjungan_id = $j['id'];
                break;
            }
        }

        // Simpan checkin
        $result = $this->kunjunganRepo->create([
            'salesman_id' => $salesman_id,
            'customer_id' => $customer_id,
            'jadwal_kunjungan_id' => $jadwal_kunjungan_id,
            'tanggal_kunjungan' => $hari_ini,
            'waktu_checkin' => date('Y-m-d H:i:s'),
            'gps_latitude' => $gps_latitude,
            'gps_longitude' => $gps_longitude,
            'customer_latitude' => $customer_latitude,
            'customer_longitude' => $customer_longitude,
            'jarak_meter' => $jarak_meter,
            'validasi_jarak' => $validasi_jarak ? 1 : 0,
            'status' => 'checkin'
        ]);

        if (!$result) {
            return ['success' => false, 'message' => 'Gagal menyimpan checkin'];
        }

        // Ambil ID yang baru dibuat
        $kunjungan_id = $this->kunjunganRepo->db->lastInsertId() ?? 
                       $this->getLastKunjunganId($salesman_id, $hari_ini, $customer_id);

        return [
            'success' => true,
            'message' => 'Check-in berhasil',
            'kunjungan_id' => $kunjungan_id,
            'validasi_jarak' => true,
            'jarak_meter' => $jarak_meter
        ];
    }

    /**
     * Update kunjungan dengan data kondisi toko
     */
    public function updateKunjungan($kunjungan_id, $data)
    {
        $allowed_fields = ['kondisi_toko', 'potensi_penjualan', 'catatan', 'rating', 'foto_toko_path'];
        
        $update_data = [];
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
            }
        }

        return $this->kunjunganRepo->update($kunjungan_id, $update_data);
    }

    /**
     * Checkout kunjungan
     */
    public function checkout($kunjungan_id)
    {
        return $this->kunjunganRepo->update($kunjungan_id, [
            'waktu_checkout' => date('Y-m-d H:i:s'),
            'status' => 'checkout'
        ]);
    }

    /**
     * Get daftar kunjungan untuk salesman
     */
    public function getKunjunganByTanggal($salesman_id, $tanggal = null)
    {
        $tanggal = $tanggal ?? date('Y-m-d');
        return $this->kunjunganRepo->getByTanggal($salesman_id, $tanggal);
    }

    /**
     * Get detail kunjungan
     */
    public function getDetailKunjungan($kunjungan_id)
    {
        return $this->kunjunganRepo->findById($kunjungan_id);
    }

    /**
     * Get status summary
     */
    public function getStatusSummary($salesman_id, $tanggal = null)
    {
        return $this->kunjunganRepo->getStatusSummary($salesman_id, $tanggal);
    }

    /**
     * Helper: Hitung jarak antara dua koordinat GPS (Haversine Formula)
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earth_radius = 6371000; // dalam meter

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * asin(sqrt($a));

        return round($earth_radius * $c);
    }

    /**
     * Helper: Konversi nomor hari ke nama hari Indonesia
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

    /**
     * Helper: Get last kunjungan ID (fallback)
     */
    private function getLastKunjunganId($salesman_id, $tanggal, $customer_id)
    {
        $kunjungan = $this->kunjunganRepo->getByTanggal($salesman_id, $tanggal);
        foreach ($kunjungan as $k) {
            if ($k['customer_id'] == $customer_id) {
                return $k['id'];
            }
        }
        return null;
    }
}
