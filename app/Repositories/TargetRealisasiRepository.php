<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class TargetRealisasiRepository
{
    private $pdo;
    private $driver;

    public function __construct()
    {
        $this->pdo = Database::connection();
        $this->driver = Database::driver();
    }

    public function listAll($year = null, $month = null)
    {
        $year = $year ?: (int) date('Y');
        $month = $month ?: (int) date('n');

        $statement = $this->pdo->prepare(
            'SELECT tr.id, tr.salesman_id, s.nama AS salesman_nama, tr.tahun, tr.bulan, tr.target_kunjungan, tr.realisasi_kunjungan, tr.updated_at
             FROM target_realisasi tr
             LEFT JOIN salesman s ON s.id = tr.salesman_id
             WHERE tr.tahun = :tahun AND tr.bulan = :bulan
             ORDER BY s.nama ASC'
        );
        $statement->bindValue(':tahun', (int) $year, PDO::PARAM_INT);
        $statement->bindValue(':bulan', (int) $month, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function upsert($salesmanId, $year, $month, $target, $realisasi = 0)
    {
        $year = (int) $year;
        $month = (int) $month;
        $salesmanId = (int) $salesmanId;

        $select = $this->pdo->prepare(
            'SELECT id FROM target_realisasi
             WHERE salesman_id = :salesman_id AND tahun = :tahun AND bulan = :bulan'
        );
        $select->execute(array(
            ':salesman_id' => $salesmanId,
            ':tahun' => $year,
            ':bulan' => $month,
        ));
        $row = $select->fetch();
        $now = date('Y-m-d H:i:s');

        if ($row) {
            $update = $this->pdo->prepare(
                'UPDATE target_realisasi
                 SET target_kunjungan = :target_kunjungan,
                     realisasi_kunjungan = :realisasi_kunjungan,
                     updated_at = :updated_at
                 WHERE id = :id'
            );
            $update->execute(array(
                ':target_kunjungan' => (int) $target,
                ':realisasi_kunjungan' => (int) $realisasi,
                ':updated_at' => $now,
                ':id' => (int) $row['id'],
            ));
            return (int) $row['id'];
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO target_realisasi (salesman_id, tahun, bulan, target_kunjungan, realisasi_kunjungan, created_at, updated_at)
             VALUES (:salesman_id, :tahun, :bulan, :target_kunjungan, :realisasi_kunjungan, :created_at, :updated_at)'
        );
        $insert->execute(array(
            ':salesman_id' => $salesmanId,
            ':tahun' => $year,
            ':bulan' => $month,
            ':target_kunjungan' => (int) $target,
            ':realisasi_kunjungan' => (int) $realisasi,
            ':created_at' => $now,
            ':updated_at' => $now,
        ));
        return (int) $this->pdo->lastInsertId();
    }
}
