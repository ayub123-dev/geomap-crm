<?php

namespace App\Services;

use App\Repositories\CustomerRepository;
use InvalidArgumentException;

class CustomerService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new CustomerRepository();
    }

    public function listCustomers($search = '', $limit = 50, $offset = 0)
    {
        return $this->repository->listAll($search, $limit, $offset);
    }

    public function countCustomers($search = '')
    {
        return $this->repository->countAll($search);
    }

    public function getSummary()
    {
        return $this->repository->summary();
    }

    public function getCustomer($id)
    {
        return $this->repository->findById($id);
    }

    public function createCustomer(array $payload)
    {
        $prepared = $this->preparePayload($payload, true);
        return $this->repository->create($prepared);
    }

    public function updateCustomer($id, array $payload)
    {
        $existing = $this->repository->findById($id);
        if (!$existing) {
            return null;
        }

        $prepared = $this->preparePayload($payload, false, $existing);
        return $this->repository->update($id, $prepared);
    }

    public function deleteCustomer($id)
    {
        return $this->repository->delete($id);
    }

    public function listMarkers()
    {
        return $this->repository->markers();
    }

    private function preparePayload(array $payload, $isCreate = true, array $existing = array())
    {
        $name = trim((string) ($payload['name'] ?? $existing['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Field "name" wajib diisi.');
        }

        $customerCode = trim((string) ($payload['customer_code'] ?? $existing['customer_code'] ?? ''));
        if ($customerCode === '') {
            $customerCode = $this->generateCustomerCode();
        }

        $status = trim((string) ($payload['status'] ?? $existing['status'] ?? 'active'));
        if (!in_array($status, array('active', 'inactive'), true)) {
            $status = 'active';
        }

        $now = date('Y-m-d H:i:s');

        return array(
            'customer_code' => $customerCode,
            'name' => $name,
            'email' => trim((string) ($payload['email'] ?? $existing['email'] ?? '')),
            'phone' => trim((string) ($payload['phone'] ?? $existing['phone'] ?? '')),
            'address' => trim((string) ($payload['address'] ?? $existing['address'] ?? '')),
            'latitude' => $payload['latitude'] ?? $existing['latitude'] ?? null,
            'longitude' => $payload['longitude'] ?? $existing['longitude'] ?? null,
            'status' => $status,
            'created_at' => $isCreate ? $now : ($existing['created_at'] ?? $now),
            'updated_at' => $now,
        );
    }

    private function generateCustomerCode()
    {
        return 'CUST-' . date('Ymd-His') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 4));
    }
}
