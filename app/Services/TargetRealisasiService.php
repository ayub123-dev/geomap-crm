<?php

namespace App\Services;

use App\Repositories\AuditLogRepository;
use App\Repositories\TargetRealisasiRepository;
use InvalidArgumentException;

class TargetRealisasiService
{
    private $repository;
    private $audit;

    public function __construct()
    {
        $this->repository = new TargetRealisasiRepository();
        $this->audit = new AuditLogRepository();
    }

    public function list($year = null, $month = null)
    {
        return $this->repository->listAll($year, $month);
    }

    public function save(array $payload, $userId = null)
    {
        $salesmanId = (int) ($payload['salesman_id'] ?? 0);
        if ($salesmanId <= 0) {
            throw new InvalidArgumentException('salesman_id wajib diisi.');
        }

        $year = (int) ($payload['tahun'] ?? date('Y'));
        $month = (int) ($payload['bulan'] ?? date('n'));
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('bulan harus bernilai 1-12.');
        }

        $target = max(0, (int) ($payload['target_kunjungan'] ?? 0));
        $realisasi = max(0, (int) ($payload['realisasi_kunjungan'] ?? 0));

        $id = $this->repository->upsert($salesmanId, $year, $month, $target, $realisasi);
        $this->audit->log($userId, 'target_realisasi', 'upsert', null, null, json_encode(array(
            'id' => $id,
            'salesman_id' => $salesmanId,
            'tahun' => $year,
            'bulan' => $month,
            'target_kunjungan' => $target,
            'realisasi_kunjungan' => $realisasi,
        )));

        return $id;
    }
}
