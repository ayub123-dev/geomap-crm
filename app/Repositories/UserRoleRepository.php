<?php

namespace App\Repositories;

use App\Core\Database;
use App\Core\RbacTable;
use PDO;
use RuntimeException;

class UserRoleRepository
{
    private $pdo;
    private $driver;
    private $userRoleTable;
    private $permissionRoleTable;
    private $columnCache = array();

    public function __construct()
    {
        $this->pdo = Database::authConnection();
        $this->driver = Database::driver('default');
        $this->userRoleTable = RbacTable::userRole($this->pdo);
        $this->permissionRoleTable = RbacTable::permissionRole($this->pdo);
    }

    public function listUsers($search = '', $limit = 50, $offset = 0)
    {
        $limit = max(1, min(500, (int) $limit));
        $offset = max(0, (int) $offset);
        $search = trim((string) $search);

        $where = 'WHERE 1=1';
        if ($search !== '') {
            $where .= ' AND (u.username LIKE :search OR u.full_name LIKE :search OR u.email LIKE :search)';
        }

        if ($this->driver === 'sqlsrv') {
            $sql = 'SELECT u.id, u.username, u.full_name, u.email, u.status, u.created_at, u.profile_json
                    FROM users u
                    ' . $where . '
                    ORDER BY u.created_at DESC
                    OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY';
        } else {
            $sql = 'SELECT u.id, u.username, u.full_name, u.email, u.status, u.created_at, u.profile_json
                    FROM users u
                    ' . $where . '
                    ORDER BY u.created_at DESC
                    LIMIT :limit OFFSET :offset';
        }

        $statement = $this->pdo->prepare($sql);
        if ($search !== '') {
            $statement->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        $users = $statement->fetchAll();

        foreach ($users as &$user) {
            $user = $this->hydrateUserRow($user);
            $user['roles'] = $this->rolesByUserId((int) $user['id']);
        }

        return $users;
    }

    public function listRoles()
    {
        return $this->pdo->query('SELECT id, code, name, description FROM roles ORDER BY id ASC')->fetchAll();
    }

    public function listPermissions()
    {
        $nameExpr = $this->permissionNameExpr();
        $moduleExpr = $this->permissionModuleExpr();
        return $this->pdo->query(
            'SELECT id, code, ' . $nameExpr . ' AS name, ' . $moduleExpr . ' AS module
             FROM permissions
             ORDER BY module ASC, code ASC'
        )->fetchAll();
    }

    public function createUser(array $data)
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (username, full_name, email, password_hash, status, profile_json, created_at, updated_at)
             VALUES (:username, :full_name, :email, :password_hash, :status, :profile_json, :created_at, :updated_at)'
        );
        $statement->bindValue(':username', $data['username'], PDO::PARAM_STR);
        $statement->bindValue(':full_name', $data['full_name'], PDO::PARAM_STR);
        $this->bindNullable($statement, ':email', $data['email'] ?? null);
        $statement->bindValue(':password_hash', $data['password_hash'], PDO::PARAM_STR);
        $statement->bindValue(':status', $data['status'], PDO::PARAM_STR);
        $this->bindNullable($statement, ':profile_json', $data['profile_json'] ?? null);
        $statement->bindValue(':created_at', $data['created_at'], PDO::PARAM_STR);
        $statement->bindValue(':updated_at', $data['updated_at'], PDO::PARAM_STR);
        $statement->execute();

        $id = (int) $this->pdo->lastInsertId();
        $this->syncRoles($id, $data['role_ids'] ?? array());
        return $id;
    }

    public function updateUser($id, array $data)
    {
        $sql = 'UPDATE users
                SET username = :username,
                    full_name = :full_name,
                    email = :email,
                    status = :status,
                    profile_json = :profile_json,
                    updated_at = :updated_at';
        if (!empty($data['password_hash'])) {
            $sql .= ', password_hash = :password_hash';
        }
        $sql .= ' WHERE id = :id';

        $statement = $this->pdo->prepare($sql);
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $statement->bindValue(':username', $data['username'], PDO::PARAM_STR);
        $statement->bindValue(':full_name', $data['full_name'], PDO::PARAM_STR);
        $this->bindNullable($statement, ':email', $data['email'] ?? null);
        $statement->bindValue(':status', $data['status'], PDO::PARAM_STR);
        $this->bindNullable($statement, ':profile_json', $data['profile_json'] ?? null);
        $statement->bindValue(':updated_at', $data['updated_at'], PDO::PARAM_STR);
        if (!empty($data['password_hash'])) {
            $statement->bindValue(':password_hash', $data['password_hash'], PDO::PARAM_STR);
        }
        $statement->execute();

        $this->syncRoles($id, $data['role_ids'] ?? array());
    }

    public function deleteUser($id)
    {
        $statement = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);
        return $statement->execute();
    }

    public function rolePermissions($roleId)
    {
        if ($this->permissionRoleTable === null) {
            return array();
        }

        $statement = $this->pdo->prepare(
            'SELECT p.id, p.code, ' . $this->permissionNameExpr('p') . ' AS name, ' . $this->permissionModuleExpr('p') . ' AS module
             FROM permissions p
             INNER JOIN ' . $this->permissionRoleTable . ' pr ON pr.permission_id = p.id
             WHERE pr.role_id = :role_id'
        );
        $statement->bindValue(':role_id', (int) $roleId, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function syncRolePermissions($roleId, array $permissionIds)
    {
        if ($this->permissionRoleTable === null) {
            throw new RuntimeException('Tabel relasi role-permission tidak ditemukan. Jalankan migration SQL terbaru.');
        }

        $delete = $this->pdo->prepare('DELETE FROM ' . $this->permissionRoleTable . ' WHERE role_id = :role_id');
        $delete->bindValue(':role_id', (int) $roleId, PDO::PARAM_INT);
        $delete->execute();

        if (empty($permissionIds)) {
            return;
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO ' . $this->permissionRoleTable . ' (permission_id, role_id)
             VALUES (:permission_id, :role_id)'
        );
        foreach ($permissionIds as $permissionId) {
            $insert->execute(array(
                ':permission_id' => (int) $permissionId,
                ':role_id' => (int) $roleId,
            ));
        }
    }

    public function getUserById($id)
    {
        $statement = $this->pdo->prepare(
            'SELECT id, username, full_name, email, status, created_at, updated_at, profile_json
             FROM users
             WHERE id = :id'
        );
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $statement->execute();
        $user = $statement->fetch();
        if (!$user) {
            return null;
        }

        $user = $this->hydrateUserRow($user);
        $user['roles'] = $this->rolesByUserId((int) $id);
        return $user;
    }

    private function hydrateUserRow(array $user)
    {
        $profile = $this->decodeProfile($user['profile_json'] ?? null);
        $databaseAlias = trim((string) ($profile['database_alias'] ?? ''));
        $connections = Database::availableConnections();
        $databaseLabel = '';
        if ($databaseAlias !== '' && isset($connections[$databaseAlias]) && is_array($connections[$databaseAlias])) {
            $databaseLabel = trim((string) ($connections[$databaseAlias]['label'] ?? $databaseAlias));
        } elseif ($databaseAlias !== '') {
            $databaseLabel = $databaseAlias;
        }

        $user['profile_json'] = $user['profile_json'] ?? null;
        $user['database_alias'] = $databaseAlias;
        $user['database_label'] = $databaseLabel;

        return $user;
    }

    private function decodeProfile($profileJson)
    {
        if (!is_string($profileJson) || trim($profileJson) === '') {
            return array();
        }

        $decoded = json_decode($profileJson, true);
        if (!is_array($decoded)) {
            return array();
        }

        return $decoded;
    }

    private function rolesByUserId($userId)
    {
        if ($this->userRoleTable === null) {
            return array();
        }

        $statement = $this->pdo->prepare(
            'SELECT r.id, r.code, r.name
             FROM roles r
             INNER JOIN ' . $this->userRoleTable . ' ru ON ru.role_id = r.id
             WHERE ru.user_id = :user_id
             ORDER BY r.id ASC'
        );
        $statement->bindValue(':user_id', (int) $userId, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    private function syncRoles($userId, array $roleIds)
    {
        if ($this->userRoleTable === null) {
            if (!empty($roleIds)) {
                throw new RuntimeException('Tabel relasi user-role tidak ditemukan. Jalankan migration SQL terbaru.');
            }
            return;
        }

        $delete = $this->pdo->prepare('DELETE FROM ' . $this->userRoleTable . ' WHERE user_id = :user_id');
        $delete->bindValue(':user_id', (int) $userId, PDO::PARAM_INT);
        $delete->execute();

        if (empty($roleIds)) {
            return;
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO ' . $this->userRoleTable . ' (role_id, user_id)
             VALUES (:role_id, :user_id)'
        );
        foreach ($roleIds as $roleId) {
            $insert->execute(array(
                ':role_id' => (int) $roleId,
                ':user_id' => (int) $userId,
            ));
        }
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

    private function permissionNameExpr($alias = '')
    {
        $prefix = $alias !== '' ? $alias . '.' : '';
        if ($this->hasColumn('permissions', 'name')) {
            return $prefix . 'name';
        }

        if ($this->hasColumn('permissions', 'description')) {
            return $prefix . 'description';
        }

        return $prefix . 'code';
    }

    private function permissionModuleExpr($alias = '')
    {
        $prefix = $alias !== '' ? $alias . '.' : '';
        if ($this->hasColumn('permissions', 'module')) {
            return $prefix . 'module';
        }

        if ($this->hasColumn('permissions', 'module_name')) {
            return $prefix . 'module_name';
        }

        return "''";
    }

    private function hasColumn($tableName, $columnName)
    {
        $key = strtolower($tableName) . '.' . strtolower($columnName);
        if (array_key_exists($key, $this->columnCache)) {
            return $this->columnCache[$key];
        }

        if ($this->driver === 'sqlsrv') {
            $statement = $this->pdo->prepare(
                'SELECT COUNT(*) AS total
                 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = :schema
                   AND TABLE_NAME = :table_name
                   AND COLUMN_NAME = :column_name'
            );
            $statement->execute(array(
                ':schema' => 'dbo',
                ':table_name' => $tableName,
                ':column_name' => $columnName,
            ));
        } else {
            $statement = $this->pdo->prepare(
                'SELECT COUNT(*) AS total
                 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table_name
                   AND COLUMN_NAME = :column_name'
            );
            $statement->execute(array(
                ':table_name' => $tableName,
                ':column_name' => $columnName,
            ));
        }

        $result = $statement->fetch();
        $exists = isset($result['total']) && (int) $result['total'] > 0;
        $this->columnCache[$key] = $exists;
        return $exists;
    }
}
