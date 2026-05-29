<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class SalesmanRepository
{
    private $pdo;
    private $driver;

    public function __construct()
    {
        $this->pdo = Database::connection();
        $this->driver = Database::driver();
    }

    public function listAll($search = '', $limit = 50, $offset = 0)
    {
        $limit = max(1, min(1000, (int) $limit));
        $offset = max(0, (int) $offset);
        $search = trim((string) $search);

        $where = 'WHERE 1=1';
        if ($search !== '') {
            $where .= ' AND (nik LIKE :search OR nama LIKE :search OR no_hp LIKE :search OR email LIKE :search)';
        }

        if ($this->driver === 'sqlsrv') {
            $sql = 'SELECT id, nik, nama, no_hp, email, wilayah_id, target_kunjungan_bulan, foto, status, user_id, created_at, updated_at
                    FROM salesman
                    ' . $where . '
                    ORDER BY created_at DESC
                    OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY';
        } else {
            $sql = 'SELECT id, nik, nama, no_hp, email, wilayah_id, target_kunjungan_bulan, foto, status, user_id, created_at, updated_at
                    FROM salesman
                    ' . $where . '
                    ORDER BY created_at DESC
                    LIMIT :limit OFFSET :offset';
        }

        $statement = $this->pdo->prepare($sql);
        if ($search !== '') {
            $statement->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function countAll($search = '')
    {
        $search = trim((string) $search);
        $sql = 'SELECT COUNT(*) AS total FROM salesman WHERE 1=1';
        if ($search !== '') {
            $sql .= ' AND (nik LIKE :search OR nama LIKE :search OR no_hp LIKE :search OR email LIKE :search)';
        }
        $statement = $this->pdo->prepare($sql);
        if ($search !== '') {
            $statement->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }
        $statement->execute();
        $row = $statement->fetch();
        return (int) ($row['total'] ?? 0);
    }

    public function find($id)
    {
        $statement = $this->pdo->prepare(
            'SELECT id, nik, nama, no_hp, email, wilayah_id, target_kunjungan_bulan, foto, status, user_id, created_at, updated_at
             FROM salesman
             WHERE id = :id'
        );
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetch() ?: null;
    }

    public function create(array $data)
    {
        $data['status'] = $this->normalizeStatus($data['status'] ?? 'Aktif');
        $statement = $this->pdo->prepare(
            'INSERT INTO salesman (
                nik, nama, no_hp, email, wilayah_id, target_kunjungan_bulan, foto, status, user_id, created_at, updated_at
            ) VALUES (
                :nik, :nama, :no_hp, :email, :wilayah_id, :target_kunjungan_bulan, :foto, :status, :user_id, :created_at, :updated_at
            )'
        );
        $this->bindData($statement, $data, true);
        $statement->execute();
        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function update($id, array $data)
    {
        $data['status'] = $this->normalizeStatus($data['status'] ?? 'Aktif');
        $statement = $this->pdo->prepare(
            'UPDATE salesman
             SET nik = :nik,
                 nama = :nama,
                 no_hp = :no_hp,
                 email = :email,
                 wilayah_id = :wilayah_id,
                 target_kunjungan_bulan = :target_kunjungan_bulan,
                 foto = :foto,
                 status = :status,
                 user_id = :user_id,
                 updated_at = :updated_at
             WHERE id = :id'
        );
        $this->bindData($statement, $data, false);
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $statement->execute();
        return $this->find($id);
    }

    public function delete($id)
    {
        $statement = $this->pdo->prepare('DELETE FROM salesman WHERE id = :id');
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);
        return $statement->execute();
    }

    public function dashboard($salesmanId, $year, $month)
    {
        $totalCustomersStmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM customer_inti
             WHERE deleted_at IS NULL AND salesman_id = :salesman_id'
        );
        $totalCustomersStmt->bindValue(':salesman_id', (int) $salesmanId, PDO::PARAM_INT);
        $totalCustomersStmt->execute();
        $totalCustomers = (int) ($totalCustomersStmt->fetch()['total'] ?? 0);

        $targetStmt = $this->pdo->prepare(
            'SELECT target_kunjungan, realisasi_kunjungan
             FROM target_realisasi
             WHERE salesman_id = :salesman_id AND tahun = :tahun AND bulan = :bulan'
        );
        $targetStmt->bindValue(':salesman_id', (int) $salesmanId, PDO::PARAM_INT);
        $targetStmt->bindValue(':tahun', (int) $year, PDO::PARAM_INT);
        $targetStmt->bindValue(':bulan', (int) $month, PDO::PARAM_INT);
        $targetStmt->execute();
        $targetRow = $targetStmt->fetch();

        $target = (int) ($targetRow['target_kunjungan'] ?? 0);
        if ($target <= 0) {
            $salesmanTargetStmt = $this->pdo->prepare(
                'SELECT target_kunjungan_bulan
                 FROM salesman
                 WHERE id = :salesman_id'
            );
            $salesmanTargetStmt->bindValue(':salesman_id', (int) $salesmanId, PDO::PARAM_INT);
            $salesmanTargetStmt->execute();
            $target = (int) ($salesmanTargetStmt->fetch()['target_kunjungan_bulan'] ?? 0);
        }

        $realisasi = (int) ($targetRow['realisasi_kunjungan'] ?? 0);

        if ($realisasi === 0) {
            try {
                $realisasiStmt = $this->pdo->prepare(
                    'SELECT COUNT(*) AS total
                     FROM kunjungan
                     WHERE salesman_id = :salesman_id
                       AND YEAR(tanggal_kunjungan) = :tahun
                       AND MONTH(tanggal_kunjungan) = :bulan'
                );
                $realisasiStmt->bindValue(':salesman_id', (int) $salesmanId, PDO::PARAM_INT);
                $realisasiStmt->bindValue(':tahun', (int) $year, PDO::PARAM_INT);
                $realisasiStmt->bindValue(':bulan', (int) $month, PDO::PARAM_INT);
                $realisasiStmt->execute();
                $realisasi = (int) ($realisasiStmt->fetch()['total'] ?? 0);
            } catch (\Throwable $exception) {
                $realisasi = 0;
            }
        }

        $percentage = $target > 0 ? round(($realisasi / $target) * 100, 2) : 0;

        $coverageStmt = $this->pdo->prepare(
            'SELECT id, nama_toko, lat, lng
             FROM customer_inti
             WHERE deleted_at IS NULL
               AND salesman_id = :salesman_id
               AND lat IS NOT NULL
               AND lng IS NOT NULL'
        );
        $coverageStmt->bindValue(':salesman_id', (int) $salesmanId, PDO::PARAM_INT);
        $coverageStmt->execute();

        return array(
            'total_customer' => $totalCustomers,
            'target_kunjungan' => $target,
            'realisasi_kunjungan' => $realisasi,
            'persentase_target' => $percentage,
            'coverage_points' => $coverageStmt->fetchAll(),
        );
    }

    private function bindData($statement, array $data, $withCreatedAt)
    {
        $statement->bindValue(':nik', (string) $data['nik'], PDO::PARAM_STR);
        $statement->bindValue(':nama', (string) $data['nama'], PDO::PARAM_STR);
        $this->bindNullable($statement, ':no_hp', $data['no_hp'] ?? null);
        $this->bindNullable($statement, ':email', $data['email'] ?? null);
        $this->bindNullableInt($statement, ':wilayah_id', $data['wilayah_id'] ?? null);
        $statement->bindValue(':target_kunjungan_bulan', (int) ($data['target_kunjungan_bulan'] ?? 0), PDO::PARAM_INT);
        $this->bindNullable($statement, ':foto', $data['foto'] ?? null);
        $statement->bindValue(':status', (string) ($data['status'] ?? 'Aktif'), PDO::PARAM_STR);
        $this->bindNullableInt($statement, ':user_id', $data['user_id'] ?? null);
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

    private function bindNullableInt($statement, $param, $value)
    {
        if ($value === '' || $value === null) {
            $statement->bindValue($param, null, PDO::PARAM_NULL);
            return;
        }
        $statement->bindValue($param, (int) $value, PDO::PARAM_INT);
    }

    private function normalizeStatus($status)
    {
        $status = trim((string) $status);
        if ($status === 'Aktif' || $status === 'active') {
            return 'active';
        }
        if ($status === 'NonAktif' || $status === 'inactive') {
            return 'inactive';
        }
        return 'active';
    }
}
