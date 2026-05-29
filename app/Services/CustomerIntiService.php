<?php

namespace App\Services;

use App\Core\GeoCoder;
use App\Repositories\AuditLogRepository;
use App\Repositories\CustomerIntiRepository;
use InvalidArgumentException;

class CustomerIntiService
{
    private $repository;
    private $auditLogRepository;

    public function __construct()
    {
        $this->repository = new CustomerIntiRepository();
        $this->auditLogRepository = new AuditLogRepository();
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
        $duplicate = $this->repository->findDuplicateByNameAndRadius($data['nama_toko'], $data['lat'], $data['lng']);
        if ($duplicate) {
            throw new InvalidArgumentException('Duplikat terdeteksi: toko dengan nama serupa berada dalam radius 50 meter.');
        }

        $result = $this->repository->create($data);
        $this->auditLogRepository->log($userId, 'customer_inti', 'create', null, null, json_encode($result));
        return $result;
    }

    public function update($id, array $payload, $userId = null)
    {
        $existing = $this->repository->find($id);
        if (!$existing) {
            return null;
        }

        $merged = array_merge($existing, $payload);
        $data = $this->preparePayload($merged, false, $existing);

        $duplicate = $this->repository->findDuplicateByNameAndRadius($data['nama_toko'], $data['lat'], $data['lng'], $id);
        if ($duplicate) {
            throw new InvalidArgumentException('Duplikat terdeteksi saat update: nama toko + koordinat terlalu dekat (<= 50m).');
        }

        $result = $this->repository->update($id, $data);
        $this->auditLogRepository->log($userId, 'customer_inti', 'update', null, json_encode($existing), json_encode($result));
        return $result;
    }

    public function delete($id, $userId = null)
    {
        $existing = $this->repository->find($id);
        $deleted = $this->repository->softDelete($id);
        if ($deleted) {
            $this->auditLogRepository->log($userId, 'customer_inti', 'delete', null, json_encode($existing), null);
        }
        return $deleted;
    }

    public function importRows(array $rows, $userId = null, $execute = false)
    {
        $preview = array();
        $success = 0;
        $failed = 0;

        foreach ($rows as $index => $row) {
            try {
                $payload = $this->preparePayload($row, true);
                $duplicate = $this->repository->findDuplicateByNameAndRadius($payload['nama_toko'], $payload['lat'], $payload['lng']);
                if ($duplicate) {
                    $failed++;
                    $preview[] = array(
                        'row' => $index + 1,
                        'status' => 'failed',
                        'message' => 'Duplikat radius 50m',
                        'data' => $payload,
                    );
                    continue;
                }

                if ($execute) {
                    $this->repository->create($payload);
                    $this->auditLogRepository->log($userId, 'customer_inti', 'import_create', null, null, json_encode($payload));
                }

                $success++;
                $preview[] = array(
                    'row' => $index + 1,
                    'status' => 'ok',
                    'message' => $execute ? 'Tersimpan' : 'Siap disimpan',
                    'data' => $payload,
                );
            } catch (\Throwable $exception) {
                $failed++;
                $preview[] = array(
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
            'rows' => $preview,
        );
    }

    // Fungsi bantu untuk mempersiapkan payload sebelum simpan/update
    private function preparePayload(array $payload, $isCreate = true, array $existing = array())
    {
        $kodeCustomer = trim((string) ($payload['kode_customer'] ?? $existing['kode_customer'] ?? ''));
        if ($kodeCustomer === '') {
            $kodeCustomer = 'CINTI-' . date('YmdHis') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 4));
        }

        $namaToko = trim((string) ($payload['nama_toko'] ?? $existing['nama_toko'] ?? ''));
        if ($namaToko === '') {
            throw new InvalidArgumentException('Field nama_toko wajib diisi.');
        }

        $status = trim((string) ($payload['status'] ?? $existing['status'] ?? 'Aktif'));
        if (!in_array($status, array('Aktif', 'NonAktif'), true)) {
            $status = 'Aktif';
        }

        $lat = $payload['lat'] ?? $existing['lat'] ?? null;
        $lng = $payload['lng'] ?? $existing['lng'] ?? null;
        $alamat = trim((string) ($payload['alamat'] ?? $existing['alamat'] ?? ''));

        if (($lat === '' || $lat === null || $lng === '' || $lng === null) && $alamat !== '') {
            $geo = GeoCoder::geocodeAddress($alamat);
            if ($geo) {
                $lat = $geo['lat'];
                $lng = $geo['lng'];
            }
        }

        $now = date('Y-m-d H:i:s');

        return array(
            'kode_customer' => $kodeCustomer,
            'nama_toko' => $namaToko,
            'pemilik' => trim((string) ($payload['pemilik'] ?? $existing['pemilik'] ?? '')),
            'no_hp' => trim((string) ($payload['no_hp'] ?? $existing['no_hp'] ?? '')),
            'alamat' => $alamat,
            'kelurahan' => trim((string) ($payload['kelurahan'] ?? $existing['kelurahan'] ?? '')),
            'kecamatan' => trim((string) ($payload['kecamatan'] ?? $existing['kecamatan'] ?? '')),
            'kota' => trim((string) ($payload['kota'] ?? $existing['kota'] ?? '')),
            'provinsi' => trim((string) ($payload['provinsi'] ?? $existing['provinsi'] ?? '')),
            'lat' => $lat === '' ? null : $lat,
            'lng' => $lng === '' ? null : $lng,
            'kategori_toko' => trim((string) ($payload['kategori_toko'] ?? $existing['kategori_toko'] ?? '')),
            'omzet_estimasi' => (float) ($payload['omzet_estimasi'] ?? $existing['omzet_estimasi'] ?? 0),
            'salesman_id' => $payload['salesman_id'] ?? $existing['salesman_id'] ?? null,
            'status' => $status,
            'foto_toko' => trim((string) ($payload['foto_toko'] ?? $existing['foto_toko'] ?? '')),
            'created_at' => $isCreate ? $now : ($existing['created_at'] ?? $now),
            'updated_at' => $now,
        );
    }
}