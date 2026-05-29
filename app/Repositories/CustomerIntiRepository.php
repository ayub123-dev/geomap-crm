<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class CustomerIntiRepository
{
    private $pdo;
    private $driver;
    private $statusFormat = 'new';

    public function __construct()
    {
        $this->pdo = Database::connection();
        $this->driver = Database::driver();
        $this->statusFormat = $this->detectStatusFormat();
    }

    public function listAll($search = '', $limit = 50, $offset = 0)
    {
        $limit = max(1, min(100000, (int) $limit));
        $offset = max(0, (int) $offset);
        $search = trim((string) $search);

        $where = 'WHERE deleted_at IS NULL';
        if ($search !== '') {
            $where .= ' AND (kode_customer LIKE :search_1 OR nama_toko LIKE :search_2 OR pemilik LIKE :search_3 OR no_hp LIKE :search_4 OR alamat LIKE :search_5)';
        }

        if ($this->driver === 'sqlsrv') {
            $sql = 'SELECT id, kode_customer, nama_toko, pemilik, no_hp, alamat, kelurahan, kecamatan, kota, provinsi, lat, lng,
                           kategori_toko, omzet_estimasi, salesman_id, status, foto_toko, created_at, updated_at
                    FROM customer_inti
                    ' . $where . '
                    ORDER BY created_at DESC
                    OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY';
        } else {
            $sql = 'SELECT id, kode_customer, nama_toko, pemilik, no_hp, alamat, kelurahan, kecamatan, kota, provinsi, lat, lng,
                           kategori_toko, omzet_estimasi, salesman_id, status, foto_toko, created_at, updated_at
                    FROM customer_inti
                    ' . $where . '
                    ORDER BY created_at DESC
                    LIMIT :limit OFFSET :offset';
        }

        $statement = $this->pdo->prepare($sql);
        if ($search !== '') {
            $statement->bindValue(':search_1', '%' . $search . '%', PDO::PARAM_STR);
            $statement->bindValue(':search_2', '%' . $search . '%', PDO::PARAM_STR);
            $statement->bindValue(':search_3', '%' . $search . '%', PDO::PARAM_STR);
            $statement->bindValue(':search_4', '%' . $search . '%', PDO::PARAM_STR);
            $statement->bindValue(':search_5', '%' . $search . '%', PDO::PARAM_STR);
        }
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        $rows = $statement->fetchAll();
        foreach ($rows as &$row) {
            $row['status'] = $this->toPublicStatus($row['status'] ?? null);
        }
        return $rows;
    }

    // Fungsi untuk mengubah status internal ke format yang ditampilkan kepada user
    public function countAll($search = '')
    {
        $search = trim((string) $search);
        $sql = 'SELECT COUNT(*) AS total FROM customer_inti WHERE deleted_at IS NULL';
        if ($search !== '') {
            $sql .= ' AND (kode_customer LIKE :search_1 OR nama_toko LIKE :search_2 OR pemilik LIKE :search_3 OR no_hp LIKE :search_4 OR alamat LIKE :search_5)';
        }
        $statement = $this->pdo->prepare($sql);
        if ($search !== '') {
            $statement->bindValue(':search_1', '%' . $search . '%', PDO::PARAM_STR);
            $statement->bindValue(':search_2', '%' . $search . '%', PDO::PARAM_STR);
            $statement->bindValue(':search_3', '%' . $search . '%', PDO::PARAM_STR);
            $statement->bindValue(':search_4', '%' . $search . '%', PDO::PARAM_STR);
            $statement->bindValue(':search_5', '%' . $search . '%', PDO::PARAM_STR);
        }
        $statement->execute();
        $row = $statement->fetch();
        return (int) ($row['total'] ?? 0);
    }

    // fungsi dari find() akan mengambil data customer inti berdasarkan ID yang diberikan. 
    // Fungsi ini menggunakan prepared statement untuk mencegah SQL injection dan memastikan keamanan data. 
    // Setelah data diambil, fungsi ini juga mengubah format status internal menjadi format yang lebih mudah dipahami oleh pengguna sebelum mengembalikannya.
    public function find($id)
    {
        $statement = $this->pdo->prepare(
            'SELECT id, kode_customer, nama_toko, pemilik, no_hp, alamat, kelurahan, kecamatan, kota, provinsi, lat, lng,
                    kategori_toko, omzet_estimasi, salesman_id, status, foto_toko, created_at, updated_at
             FROM customer_inti
             WHERE id = :id AND deleted_at IS NULL'
        );
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $statement->execute();
        $row = $statement->fetch() ?: null;
        if (is_array($row)) {
            $row['status'] = $this->toPublicStatus($row['status'] ?? null);
        }
        return $row;
    }

    public function create(array $data)
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO customer_inti (
                kode_customer, nama_toko, pemilik, no_hp, alamat, kelurahan, kecamatan, kota, provinsi, lat, lng,
                kategori_toko, omzet_estimasi, salesman_id, status, foto_toko, created_at, updated_at
            ) VALUES (
                :kode_customer, :nama_toko, :pemilik, :no_hp, :alamat, :kelurahan, :kecamatan, :kota, :provinsi, :lat, :lng,
                :kategori_toko, :omzet_estimasi, :salesman_id, :status, :foto_toko, :created_at, :updated_at
            )'
        );

        $this->bindData($statement, $data, true);
        $statement->execute();
        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function update($id, array $data)
    {
        $statement = $this->pdo->prepare(
            'UPDATE customer_inti
             SET kode_customer = :kode_customer,
                 nama_toko = :nama_toko,
                 pemilik = :pemilik,
                 no_hp = :no_hp,
                 alamat = :alamat,
                 kelurahan = :kelurahan,
                 kecamatan = :kecamatan,
                 kota = :kota,
                 provinsi = :provinsi,
                 lat = :lat,
                 lng = :lng,
                 kategori_toko = :kategori_toko,
                 omzet_estimasi = :omzet_estimasi,
                 salesman_id = :salesman_id,
                 status = :status,
                 foto_toko = :foto_toko,
                 updated_at = :updated_at
             WHERE id = :id AND deleted_at IS NULL'
        );

        $this->bindData($statement, $data, false);
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $statement->execute();
        return $this->find($id);
    }

    public function softDelete($id)
    {
        $statement = $this->pdo->prepare(
            'UPDATE customer_inti
             SET deleted_at = :deleted_at, updated_at = :updated_at, status = :status
             WHERE id = :id AND deleted_at IS NULL'
        );
        $now = date('Y-m-d H:i:s');
        $statement->bindValue(':deleted_at', $now, PDO::PARAM_STR);
        $statement->bindValue(':updated_at', $now, PDO::PARAM_STR);
        $statement->bindValue(':status', 'NonAktif', PDO::PARAM_STR);
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);
        return $statement->execute();
    }

    public function findDuplicateByNameAndRadius($namaToko, $lat, $lng, $excludeId = null, $radiusMeters = 50)
    {
        if ($lat === null || $lng === null) {
            return null;
        }

        $statement = $this->pdo->prepare(
            'SELECT id, kode_customer, nama_toko, lat, lng
             FROM customer_inti
             WHERE deleted_at IS NULL
               AND LOWER(nama_toko) = LOWER(:nama_toko)
               AND lat IS NOT NULL
               AND lng IS NOT NULL'
        );
        $statement->bindValue(':nama_toko', trim((string) $namaToko), PDO::PARAM_STR);
        $statement->execute();
        $rows = $statement->fetchAll();

        foreach ($rows as $row) {
            if ($excludeId !== null && (int) $row['id'] === (int) $excludeId) {
                continue;
            }

            $distance = $this->haversine(
                (float) $lat,
                (float) $lng,
                (float) $row['lat'],
                (float) $row['lng']
            );

            if ($distance <= $radiusMeters) {
                $row['distance_meters'] = $distance;
                return $row;
            }
        }

        return null;
    }

    public function geotaggedBySalesman()
    {
        $statement = $this->pdo->query(
            'SELECT salesman_id, COUNT(*) AS total
             FROM customer_inti
             WHERE deleted_at IS NULL AND lat IS NOT NULL AND lng IS NOT NULL
             GROUP BY salesman_id'
        );
        return $statement->fetchAll();
    }

    public function mapMarkersBySalesman($salesmanId)
    {
        $statement = $this->pdo->prepare(
            'SELECT id, kode_customer, nama_toko, lat, lng, status
             FROM customer_inti
             WHERE deleted_at IS NULL
               AND salesman_id = :salesman_id
               AND lat IS NOT NULL
               AND lng IS NOT NULL
             ORDER BY created_at DESC'
        );
        $statement->bindValue(':salesman_id', (int) $salesmanId, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    private function bindData($statement, array $data, $withCreatedAt)
    {
        $statement->bindValue(':kode_customer', (string) $data['kode_customer'], PDO::PARAM_STR);
        $statement->bindValue(':nama_toko', (string) $data['nama_toko'], PDO::PARAM_STR);
        $this->bindNullable($statement, ':pemilik', $data['pemilik'] ?? null);
        $this->bindNullable($statement, ':no_hp', $data['no_hp'] ?? null);
        $this->bindNullable($statement, ':alamat', $data['alamat'] ?? null);
        $this->bindNullable($statement, ':kelurahan', $data['kelurahan'] ?? null);
        $this->bindNullable($statement, ':kecamatan', $data['kecamatan'] ?? null);
        $this->bindNullable($statement, ':kota', $data['kota'] ?? null);
        $this->bindNullable($statement, ':provinsi', $data['provinsi'] ?? null);
        $this->bindNullableFloat($statement, ':lat', $data['lat'] ?? null);
        $this->bindNullableFloat($statement, ':lng', $data['lng'] ?? null);
        $this->bindNullable($statement, ':kategori_toko', $data['kategori_toko'] ?? null);
        $statement->bindValue(':omzet_estimasi', (string) ((float) ($data['omzet_estimasi'] ?? 0)), PDO::PARAM_STR);
        $this->bindNullableInt($statement, ':salesman_id', $data['salesman_id'] ?? null);
        $statement->bindValue(':status', $this->normalizeStatus($data['status'] ?? 'Aktif'), PDO::PARAM_STR);
        $this->bindNullable($statement, ':foto_toko', $data['foto_toko'] ?? null);
        if ($withCreatedAt) {
            $statement->bindValue(':created_at', (string) $data['created_at'], PDO::PARAM_STR);
        }
        $statement->bindValue(':updated_at', (string) $data['updated_at'], PDO::PARAM_STR);
    }

    private function bindNullable($statement, $param, $value)
    {
        $value = is_string($value) ? trim($value) : $value;
        if ($value === '' || $value === null) {
            $statement->bindValue($param, null, PDO::PARAM_NULL);
            return;
        }
        $statement->bindValue($param, $value, PDO::PARAM_STR);
    }

    private function bindNullableFloat($statement, $param, $value)
    {
        if ($value === '' || $value === null) {
            $statement->bindValue($param, null, PDO::PARAM_NULL);
            return;
        }
        $statement->bindValue($param, (string) ((float) $value), PDO::PARAM_STR);
    }

    private function bindNullableInt($statement, $param, $value)
    {
        if ($value === '' || $value === null) {
            $statement->bindValue($param, null, PDO::PARAM_NULL);
            return;
        }
        $statement->bindValue($param, (int) $value, PDO::PARAM_INT);
    }

    private function detectStatusFormat()
    {
        try {
            if ($this->driver !== 'mysql') {
                return 'new';
            }
            $statement = $this->pdo->query("SHOW COLUMNS FROM customer_inti LIKE 'status'");
            $row = $statement->fetch();
            if (!$row) {
                return 'new';
            }
            $type = strtolower((string) ($row['Type'] ?? ''));
            if (strpos($type, "'active'") !== false) {
                return 'legacy';
            }
        } catch (\Throwable $exception) {
            return 'new';
        }
        return 'new';
    }

    private function normalizeStatus($status)
    {
        $status = trim((string) $status);
        if ($this->statusFormat === 'legacy') {
            if ($status === 'Aktif' || $status === 'active') {
                return 'active';
            }
            if ($status === 'NonAktif' || $status === 'inactive') {
                return 'inactive';
            }
            return 'active';
        }

        if ($status === 'active') {
            return 'Aktif';
        }
        if ($status === 'inactive') {
            return 'NonAktif';
        }
        if (!in_array($status, array('Aktif', 'NonAktif'), true)) {
            return 'Aktif';
        }
        return $status;
    }

    private function toPublicStatus($status)
    {
        $status = (string) $status;
        if ($status === 'active') {
            return 'Aktif';
        }
        if ($status === 'inactive') {
            return 'NonAktif';
        }
        return $status;
    }

    private function haversine($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371000;

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * pow(sin($lngDelta / 2), 2)
        ));

        return $earthRadius * $angle;
    }
}