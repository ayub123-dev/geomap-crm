<?php

namespace App\Services;

use App\Core\GeoCoder;
use App\Repositories\AuditLogRepository;
use App\Repositories\CustomerExistingRepository;
use InvalidArgumentException;

class CustomerExistingService
{
    private $repository;
    private $audit;

    public function __construct()
    {
        $this->repository = new CustomerExistingRepository();
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
        $data = $this->preparePayload($payload);
        $result = $this->repository->create($data);
        $this->audit->log($userId, 'customer_existing', 'create', null, null, json_encode($result));
        return $result;
    }

    public function update($id, array $payload, $userId = null)
    {
        $existing = $this->repository->find($id);
        if (!$existing) {
            return null;
        }
        $data = $this->preparePayload(array_merge($existing, $payload), false);
        $result = $this->repository->update($id, $data);
        $this->audit->log($userId, 'customer_existing', 'update', null, json_encode($existing), json_encode($result));
        return $result;
    }

    public function delete($id, $userId = null)
    {
        $existing = $this->repository->find($id);
        $deleted = $this->repository->delete($id);
        if ($deleted) {
            $this->audit->log($userId, 'customer_existing', 'delete', null, json_encode($existing), null);
        }
        return $deleted;
    }

    public function importRows(array $rows, $userId = null, $execute = false)
    {
        $result = array();
        $success = 0;
        $failed = 0;

        foreach ($rows as $index => $row) {
            try {
                $payload = $this->preparePayload($row);
                if ($execute) {
                    $this->repository->create($payload);
                    $this->audit->log($userId, 'customer_existing', 'import_create', null, null, json_encode($payload));
                }

                $success++;
                $result[] = array(
                    'row' => $index + 1,
                    'status' => 'ok',
                    'message' => $execute ? 'Tersimpan' : 'Siap disimpan',
                    'data' => $payload,
                );
            } catch (\Throwable $exception) {
                $failed++;
                $result[] = array(
                    'row' => $index + 1,
                    'status' => 'failed',
                    'message' => $exception->getMessage(),
                    'data' => $row,
                );
            }
        }

        return array(
            'summary' => array(
                'total' => count($rows),
                'success' => $success,
                'failed' => $failed,
                'mode' => $execute ? 'commit' : 'preview',
            ),
            'rows' => $result,
        );
    }

    private function preparePayload(array $payload, $isCreate = true)
    {
        $kodeExisting = trim((string) ($payload['kode_existing'] ?? ''));
        if ($kodeExisting === '') {
            $kodeExisting = 'CEX-' . date('YmdHis') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 4));
        }

        $namaToko = trim((string) ($payload['nama_toko'] ?? ''));
        if ($namaToko === '') {
            throw new InvalidArgumentException('Field nama_toko wajib diisi.');
        }

        $sumberData = trim((string) ($payload['sumber_data'] ?? 'Internal'));
        $allowedSumber = array('Internal', 'Survei Lapangan', 'Import');
        if (!in_array($sumberData, $allowedSumber, true)) {
            $sumberData = 'Internal';
        }

        $lat = $payload['lat'] ?? null;
        $lng = $payload['lng'] ?? null;
        $alamat = trim((string) ($payload['alamat'] ?? ''));

        if (($lat === '' || $lat === null || $lng === '' || $lng === null) && $alamat !== '') {
            $geo = GeoCoder::geocodeAddress($alamat);
            if ($geo) {
                $lat = $geo['lat'];
                $lng = $geo['lng'];
            }
        }

        return array(
            'kode_existing' => $kodeExisting,
            'nama_toko' => $namaToko,
            'brand_kompetitor' => trim((string) ($payload['brand_kompetitor'] ?? '')),
            'alamat' => $alamat,
            'lat' => $lat,
            'lng' => $lng,
            'sumber_data' => $sumberData,
            'catatan' => trim((string) ($payload['catatan'] ?? '')),
            'created_at' => $isCreate ? date('Y-m-d H:i:s') : ($payload['created_at'] ?? date('Y-m-d H:i:s')),
        );
    }
}
