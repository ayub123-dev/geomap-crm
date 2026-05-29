<?php

namespace App\Services;

use App\Repositories\AuditLogRepository;
use App\Repositories\SalesmanRepository;
use InvalidArgumentException;

class SalesmanService
{
    private $repository;
    private $audit;

    public function __construct()
    {
        $this->repository = new SalesmanRepository();
        $this->audit = new AuditLogRepository();
    }

    public function list($search = '', $limit = 50, $offset = 0)
    {
        return $this->repository->listAll($search, $limit, $offset);
    }

    public function count($search = '')
    {
        return $this->repository->countAll($search);
    }

    public function detail($id)
    {
        return $this->repository->find($id);
    }

    public function create(array $payload, $userId = null)
    {
        $data = $this->prepare($payload, true);
        $result = $this->repository->create($data);
        $this->audit->log($userId, 'salesman', 'create', null, null, json_encode($result));
        return $result;
    }

    public function update($id, array $payload, $userId = null)
    {
        $existing = $this->repository->find($id);
        if (!$existing) {
            return null;
        }
        $data = $this->prepare(array_merge($existing, $payload), false, $existing);
        $result = $this->repository->update($id, $data);
        $this->audit->log($userId, 'salesman', 'update', null, json_encode($existing), json_encode($result));
        return $result;
    }

    public function delete($id, $userId = null)
    {
        $existing = $this->repository->find($id);
        $deleted = $this->repository->delete($id);
        if ($deleted) {
            $this->audit->log($userId, 'salesman', 'delete', null, json_encode($existing), null);
        }
        return $deleted;
    }

    public function dashboard($salesmanId, $year = null, $month = null)
    {
        $year = $year ?: (int) date('Y');
        $month = $month ?: (int) date('n');
        return $this->repository->dashboard($salesmanId, $year, $month);
    }

    private function prepare(array $payload, $isCreate = true, array $existing = array())
    {
        $nik = trim((string) ($payload['nik'] ?? $existing['nik'] ?? ''));
        if ($nik === '') {
            throw new InvalidArgumentException('Field nik wajib diisi.');
        }

        $nama = trim((string) ($payload['nama'] ?? $existing['nama'] ?? ''));
        if ($nama === '') {
            throw new InvalidArgumentException('Field nama wajib diisi.');
        }

        $status = trim((string) ($payload['status'] ?? $existing['status'] ?? 'Aktif'));
        if (!in_array($status, array('Aktif', 'NonAktif'), true)) {
            $status = 'Aktif';
        }

        $now = date('Y-m-d H:i:s');

        return array(
            'nik' => $nik,
            'nama' => $nama,
            'no_hp' => trim((string) ($payload['no_hp'] ?? $existing['no_hp'] ?? '')),
            'email' => trim((string) ($payload['email'] ?? $existing['email'] ?? '')),
            'wilayah_id' => $payload['wilayah_id'] ?? $existing['wilayah_id'] ?? null,
            'target_kunjungan_bulan' => (int) ($payload['target_kunjungan_bulan'] ?? $existing['target_kunjungan_bulan'] ?? 0),
            'foto' => trim((string) ($payload['foto'] ?? $existing['foto'] ?? '')),
            'status' => $status,
            'user_id' => $payload['user_id'] ?? $existing['user_id'] ?? null,
            'created_at' => $isCreate ? $now : ($existing['created_at'] ?? $now),
            'updated_at' => $now,
        );
    }
}
