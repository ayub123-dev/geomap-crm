<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class CustomerRepository
{
    private $pdo;
    private $driver;
    private $table = 'customer_inti';

    public function __construct()
    {
        $this->pdo = Database::connection();
        $this->driver = Database::driver();
    }

    public function listAll($search = '', $limit = 50, $offset = 0)
    {
        $search = trim((string) $search);
        $limit = max(1, min(500, (int) $limit));
        $offset = max(0, (int) $offset);

        $where = 'WHERE deleted_at IS NULL';
        if ($search !== '') {
            $where .= ' AND (customer_code LIKE :search OR nama_usaha LIKE :search OR email LIKE :search OR pic_hp LIKE :search OR pic_nama LIKE :search)';
        }

        if ($this->driver === 'sqlsrv') {
            $sql = 'SELECT id, customer_code, nama_usaha AS name, email, pic_hp AS phone, alamat AS address, lat AS latitude, lng AS longitude, status, created_at, updated_at
                    FROM ' . $this->table . '
                    ' . $where . '
                    ORDER BY created_at DESC
                    OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY';
        } else {
            $sql = 'SELECT id, customer_code, nama_usaha AS name, email, pic_hp AS phone, alamat AS address, lat AS latitude, lng AS longitude, status, created_at, updated_at
                    FROM ' . $this->table . '
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
        $sql = 'SELECT COUNT(*) AS total FROM ' . $this->table . ' WHERE deleted_at IS NULL';
        if ($search !== '') {
            $sql .= ' AND (customer_code LIKE :search OR nama_usaha LIKE :search OR email LIKE :search OR pic_hp LIKE :search OR pic_nama LIKE :search)';
        }

        $statement = $this->pdo->prepare($sql);
        if ($search !== '') {
            $statement->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }
        $statement->execute();

        $row = $statement->fetch();
        return (int) ($row['total'] ?? 0);
    }

    public function summary()
    {
        $sql = 'SELECT
                    COUNT(*) AS total_customers,
                    SUM(CASE WHEN status = \'active\' THEN 1 ELSE 0 END) AS active_customers,
                    SUM(CASE WHEN status = \'inactive\' THEN 1 ELSE 0 END) AS inactive_customers,
                    SUM(CASE WHEN lat IS NOT NULL AND lng IS NOT NULL THEN 1 ELSE 0 END) AS geotagged_customers
                FROM ' . $this->table . '
                WHERE deleted_at IS NULL';

        $statement = $this->pdo->query($sql);
        $row = $statement->fetch();

        return array(
            'total_customers' => (int) ($row['total_customers'] ?? 0),
            'active_customers' => (int) ($row['active_customers'] ?? 0),
            'inactive_customers' => (int) ($row['inactive_customers'] ?? 0),
            'geotagged_customers' => (int) ($row['geotagged_customers'] ?? 0),
        );
    }

    public function findById($id)
    {
        $statement = $this->pdo->prepare(
            'SELECT id, customer_code, nama_usaha AS name, email, pic_hp AS phone, alamat AS address, lat AS latitude, lng AS longitude, status, created_at, updated_at
             FROM ' . $this->table . '
             WHERE id = :id AND deleted_at IS NULL'
        );
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetch() ?: null;
    }

    public function create(array $data)
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO ' . $this->table . ' (
                customer_code, nama_usaha, pic_nama, email, pic_hp, alamat, lat, lng, status, source, created_at, updated_at
            ) VALUES (
                :customer_code, :nama_usaha, :pic_nama, :email, :pic_hp, :alamat, :lat, :lng, :status, :source, :created_at, :updated_at
            )'
        );

        $statement->bindValue(':customer_code', $data['customer_code'], PDO::PARAM_STR);
        $statement->bindValue(':nama_usaha', $data['name'], PDO::PARAM_STR);
        $this->bindNullable($statement, ':pic_nama', $this->nullable($data['contact_name'] ?? null));
        $this->bindNullable($statement, ':email', $this->nullable($data['email']));
        $this->bindNullable($statement, ':pic_hp', $this->nullable($data['phone']));
        $this->bindNullable($statement, ':alamat', $this->nullable($data['address']));
        $this->bindNullableFloat($statement, ':lat', $this->nullableFloat($data['latitude']));
        $this->bindNullableFloat($statement, ':lng', $this->nullableFloat($data['longitude']));
        $statement->bindValue(':status', $data['status'], PDO::PARAM_STR);
        $statement->bindValue(':source', 'manual', PDO::PARAM_STR);
        $statement->bindValue(':created_at', $data['created_at'], PDO::PARAM_STR);
        $statement->bindValue(':updated_at', $data['updated_at'], PDO::PARAM_STR);
        $statement->execute();

        return $this->findByCode($data['customer_code']);
    }

    public function update($id, array $data)
    {
        $statement = $this->pdo->prepare(
            'UPDATE ' . $this->table . '
             SET
                customer_code = :customer_code,
                nama_usaha = :nama_usaha,
                pic_nama = :pic_nama,
                email = :email,
                pic_hp = :pic_hp,
                alamat = :alamat,
                lat = :lat,
                lng = :lng,
                status = :status,
                updated_at = :updated_at
             WHERE id = :id AND deleted_at IS NULL'
        );

        $statement->bindValue(':customer_code', $data['customer_code'], PDO::PARAM_STR);
        $statement->bindValue(':nama_usaha', $data['name'], PDO::PARAM_STR);
        $this->bindNullable($statement, ':pic_nama', $this->nullable($data['contact_name'] ?? null));
        $this->bindNullable($statement, ':email', $this->nullable($data['email']));
        $this->bindNullable($statement, ':pic_hp', $this->nullable($data['phone']));
        $this->bindNullable($statement, ':alamat', $this->nullable($data['address']));
        $this->bindNullableFloat($statement, ':lat', $this->nullableFloat($data['latitude']));
        $this->bindNullableFloat($statement, ':lng', $this->nullableFloat($data['longitude']));
        $statement->bindValue(':status', $data['status'], PDO::PARAM_STR);
        $statement->bindValue(':updated_at', $data['updated_at'], PDO::PARAM_STR);
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $statement->execute();

        return $this->findById($id);
    }

    public function delete($id)
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare(
            'UPDATE ' . $this->table . '
             SET deleted_at = :deleted_at, status = :status, updated_at = :updated_at
             WHERE id = :id AND deleted_at IS NULL'
        );
        $statement->bindValue(':deleted_at', $now, PDO::PARAM_STR);
        $statement->bindValue(':status', 'inactive', PDO::PARAM_STR);
        $statement->bindValue(':updated_at', $now, PDO::PARAM_STR);
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);

        return $statement->execute();
    }

    public function markers()
    {
        $statement = $this->pdo->query(
            'SELECT id, customer_code, nama_usaha AS name, email, pic_hp AS phone, alamat AS address, lat AS latitude, lng AS longitude, status
             FROM ' . $this->table . '
             WHERE deleted_at IS NULL AND lat IS NOT NULL AND lng IS NOT NULL
             ORDER BY nama_usaha ASC'
        );

        return $statement->fetchAll();
    }

    private function findByCode($customerCode)
    {
        $statement = $this->pdo->prepare(
            'SELECT id, customer_code, nama_usaha AS name, email, pic_hp AS phone, alamat AS address, lat AS latitude, lng AS longitude, status, created_at, updated_at
             FROM ' . $this->table . '
             WHERE customer_code = :customer_code AND deleted_at IS NULL'
        );
        $statement->bindValue(':customer_code', $customerCode, PDO::PARAM_STR);
        $statement->execute();

        return $statement->fetch() ?: null;
    }

    private function nullable($value)
    {
        $value = is_string($value) ? trim($value) : $value;
        if ($value === '' || $value === null) {
            return null;
        }

        return $value;
    }

    private function nullableFloat($value)
    {
        if ($value === '' || $value === null) {
            return null;
        }

        return (float) $value;
    }

    private function bindNullable($statement, $parameter, $value)
    {
        if ($value === null) {
            $statement->bindValue($parameter, null, PDO::PARAM_NULL);
            return;
        }

        $statement->bindValue($parameter, $value, PDO::PARAM_STR);
    }

    private function bindNullableFloat($statement, $parameter, $value)
    {
        if ($value === null) {
            $statement->bindValue($parameter, null, PDO::PARAM_NULL);
            return;
        }

        $statement->bindValue($parameter, (string) $value, PDO::PARAM_STR);
    }
}
