<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class CustomerExistingRepository
{
    private $pdo;
    private $driver;

    public function __construct()
    {
        $this->pdo = Database::connection();
        $this->driver = Database::driver();
    }

    // fungsi listAll dengan parameter pencarian, limit, dan offset
    public function listAll($search = '', $limit = 50, $offset = 0)
    {
        $limit = max(1, min(100000, (int) $limit)); // Batasi limit antara 1 dan 100000
        $offset = max(0, (int) $offset); // Pastikan offset tidak negatif , jika negatif set ke 0 , berdampak pada hasil query yang diambil
        $search = trim((string) $search);

        $where = 'WHERE 1=1';
        if ($search !== '') {
            $where .= ' AND (kode_existing LIKE :search_1 OR nama_toko LIKE :search_2 OR brand_kompetitor LIKE :search_3 OR alamat LIKE :search_4)';
        }

        if ($this->driver === 'sqlsrv') {
            $sql = 'SELECT id, kode_existing, nama_toko, brand_kompetitor, alamat, lat, lng, sumber_data, catatan, created_at
                    FROM customer_existing
                    ' . $where . '
                    ORDER BY created_at DESC
                    OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY';
        } else {
            $sql = 'SELECT id, kode_existing, nama_toko, brand_kompetitor, alamat, lat, lng, sumber_data, catatan, created_at
                    FROM customer_existing
                    ' . $where . '
                    ORDER BY created_at DESC
                    LIMIT :limit OFFSET :offset';
        }

        //variable $statement berfungsi untuk menyiapkan pernyataan SQL yang akan dieksekusi. 
        //Dengan menggunakan metode prepare() dari objek PDO, kita dapat membuat pernyataan yang aman terhadap serangan SQL injection. 
        //Setelah itu, kita dapat mengikat nilai parameter seperti :search, :limit, dan :offset sesuai
        $statement = $this->pdo->prepare($sql);
        if ($search !== '') {
            $statement->bindValue(':search_1', '%' . $search . '%', PDO::PARAM_STR);
            $statement->bindValue(':search_2', '%' . $search . '%', PDO::PARAM_STR);
            $statement->bindValue(':search_3', '%' . $search . '%', PDO::PARAM_STR);
            $statement->bindValue(':search_4', '%' . $search . '%', PDO::PARAM_STR);
        }
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function countAll($search = '')
    {
        $search = trim((string) $search);
        $sql = 'SELECT COUNT(*) AS total FROM customer_existing WHERE 1=1';
        if ($search !== '') {
            $sql .= ' AND (kode_existing LIKE :search_1 OR nama_toko LIKE :search_2 OR brand_kompetitor LIKE :search_3 OR alamat LIKE :search_4)';
        }

        $statement = $this->pdo->prepare($sql);
        if ($search !== '') {
            $statement->bindValue(':search_1', '%' . $search . '%', PDO::PARAM_STR);
            $statement->bindValue(':search_2', '%' . $search . '%', PDO::PARAM_STR);
            $statement->bindValue(':search_3', '%' . $search . '%', PDO::PARAM_STR);
            $statement->bindValue(':search_4', '%' . $search . '%', PDO::PARAM_STR);
        }
        $statement->execute();
        $row = $statement->fetch();
        return (int) ($row['total'] ?? 0);
    }

    public function find($id)
    {
        $statement = $this->pdo->prepare(
            'SELECT id, kode_existing, nama_toko, brand_kompetitor, alamat, lat, lng, sumber_data, catatan, created_at
             FROM customer_existing
             WHERE id = :id'
        );
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetch() ?: null;
    }

    public function create(array $data)
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO customer_existing (
                kode_existing, nama_toko, brand_kompetitor, alamat, lat, lng, sumber_data, catatan, created_at
            ) VALUES (
                :kode_existing, :nama_toko, :brand_kompetitor, :alamat, :lat, :lng, :sumber_data, :catatan, :created_at
            )'
        );
        $statement->bindValue(':kode_existing', (string) $data['kode_existing'], PDO::PARAM_STR);
        $statement->bindValue(':nama_toko', (string) $data['nama_toko'], PDO::PARAM_STR);
        $this->bindNullable($statement, ':brand_kompetitor', $data['brand_kompetitor'] ?? null);
        $this->bindNullable($statement, ':alamat', $data['alamat'] ?? null);
        $this->bindNullableFloat($statement, ':lat', $data['lat'] ?? null);
        $this->bindNullableFloat($statement, ':lng', $data['lng'] ?? null);
        $statement->bindValue(':sumber_data', (string) $data['sumber_data'], PDO::PARAM_STR);
        $this->bindNullable($statement, ':catatan', $data['catatan'] ?? null);
        $statement->bindValue(':created_at', (string) $data['created_at'], PDO::PARAM_STR);
        $statement->execute();

        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function update($id, array $data)
    {
        $statement = $this->pdo->prepare(
            'UPDATE customer_existing
             SET kode_existing = :kode_existing,
                 nama_toko = :nama_toko,
                 brand_kompetitor = :brand_kompetitor,
                 alamat = :alamat,
                 lat = :lat,
                 lng = :lng,
                 sumber_data = :sumber_data,
                 catatan = :catatan
             WHERE id = :id'
        );
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $statement->bindValue(':kode_existing', (string) $data['kode_existing'], PDO::PARAM_STR);
        $statement->bindValue(':nama_toko', (string) $data['nama_toko'], PDO::PARAM_STR);
        $this->bindNullable($statement, ':brand_kompetitor', $data['brand_kompetitor'] ?? null);
        $this->bindNullable($statement, ':alamat', $data['alamat'] ?? null);
        $this->bindNullableFloat($statement, ':lat', $data['lat'] ?? null);
        $this->bindNullableFloat($statement, ':lng', $data['lng'] ?? null);
        $statement->bindValue(':sumber_data', (string) $data['sumber_data'], PDO::PARAM_STR);
        $this->bindNullable($statement, ':catatan', $data['catatan'] ?? null);
        $statement->execute();
        return $this->find($id);
    }

    public function delete($id)
    {
        $statement = $this->pdo->prepare('DELETE FROM customer_existing WHERE id = :id');
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);
        return $statement->execute();
    }

    // fungsi untuk memeriksa apakah kode_existing sudah ada (untuk mencegah duplikasi)
    // dependensi untuk fungsi ini ada pada CustomerExistingService->importRows() yang digunakan untuk memeriksa duplikasi kode_existing saat proses import data
public function existsByKodeExisting($kodeExisting)
{
    $statement = $this->pdo->prepare(
        'SELECT COUNT(*) AS total
         FROM customer_existing
         WHERE kode_existing = :kode_existing'
    );

    $statement->bindValue(':kode_existing', trim((string) $kodeExisting), PDO::PARAM_STR);
    $statement->execute();

    $row = $statement->fetch();

    return ((int) ($row['total'] ?? 0)) > 0;
}


// fungsi untuk memeriksa apakah kode_existing sudah ada selain id tertentu (untuk mencegah duplikasi saat update)
public function existsByKodeExistingExceptId($kodeExisting, $id)
{
    $statement = $this->pdo->prepare(
        'SELECT COUNT(*) AS total
         FROM customer_existing
         WHERE kode_existing = :kode_existing
         AND id != :id'
    );

    $statement->bindValue(':kode_existing', trim((string) $kodeExisting), PDO::PARAM_STR);
    $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);
    $statement->execute();

    $row = $statement->fetch();

    return ((int) ($row['total'] ?? 0)) > 0;
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
}