<?php

namespace App\Services;

use App\Core\Database;
use App\Repositories\AuditLogRepository;
use App\Repositories\UserRoleRepository;
use InvalidArgumentException;

class UserRoleService
{
    private $repository;
    private $audit;

    public function __construct()
    {
        $this->repository = new UserRoleRepository();
        $this->audit = new AuditLogRepository();
    }

    public function listUsers($search = '', $limit = 50, $offset = 0)
    {
        return $this->repository->listUsers($search, $limit, $offset);
    }

    public function listRoles()
    {
        return $this->repository->listRoles();
    }

    public function listPermissions()
    {
        return $this->repository->listPermissions();
    }

    public function listConnections()
    {
        return Database::availableConnections();
    }

    public function getUser($id)
    {
        return $this->repository->getUserById($id);
    }

    public function createUser(array $payload, $actorUserId = null)
    {
        $data = $this->prepareUserPayload($payload, true);
        $userId = $this->repository->createUser($data);
        $user = $this->repository->getUserById($userId);
        $this->audit->log($actorUserId, 'users_roles', 'create_user', null, null, json_encode($user));
        return $user;
    }

    public function updateUser($id, array $payload, $actorUserId = null)
    {
        $existing = $this->repository->getUserById($id);
        if (!$existing) {
            return null;
        }

        if (!array_key_exists('role_ids', $payload)) {
            $payload['role_ids'] = $this->extractRoleIds($existing['roles'] ?? array());
        }

        $data = $this->prepareUserPayload(array_merge($existing, $payload), false);
        $this->repository->updateUser($id, $data);
        $updated = $this->repository->getUserById($id);
        $this->audit->log($actorUserId, 'users_roles', 'update_user', null, json_encode($existing), json_encode($updated));
        return $updated;
    }

    public function deleteUser($id, $actorUserId = null)
    {
        $existing = $this->repository->getUserById($id);
        $deleted = $this->repository->deleteUser($id);
        if ($deleted) {
            $this->audit->log($actorUserId, 'users_roles', 'delete_user', null, json_encode($existing), null);
        }
        return $deleted;
    }

    public function rolePermissions($roleId)
    {
        return $this->repository->rolePermissions($roleId);
    }

    public function syncRolePermissions($roleId, array $permissionIds, $actorUserId = null)
    {
        $permissionIds = array_values(array_unique(array_filter(array_map('intval', $permissionIds), function ($id) {
            return $id > 0;
        })));
        $before = $this->repository->rolePermissions($roleId);
        $this->repository->syncRolePermissions($roleId, $permissionIds);
        $after = $this->repository->rolePermissions($roleId);
        $this->audit->log($actorUserId, 'users_roles', 'sync_role_permissions', null, json_encode($before), json_encode($after));
    }

    private function prepareUserPayload(array $payload, $isCreate)
    {
        $username = trim((string) ($payload['username'] ?? ''));
        if ($username === '') {
            throw new InvalidArgumentException('Field username wajib diisi.');
        }

        $fullName = trim((string) ($payload['full_name'] ?? ''));
        if ($fullName === '') {
            throw new InvalidArgumentException('Field full_name wajib diisi.');
        }

        $status = trim((string) ($payload['status'] ?? 'Aktif'));
        if (!in_array($status, array('Aktif', 'NonAktif'), true)) {
            $status = 'Aktif';
        }

        $roleIds = $payload['role_ids'] ?? array();
        if (!is_array($roleIds)) {
            $roleIds = array($roleIds);
        }
        $roleIds = array_values(array_unique(array_map('intval', $roleIds)));
        $roleIds = array_filter($roleIds, function ($value) {
            return $value > 0;
        });
        if (empty($roleIds)) {
            throw new InvalidArgumentException('Pilih minimal 1 role untuk user.');
        }

        $databaseAlias = trim((string) ($payload['database_alias'] ?? ''));
        if ($databaseAlias !== '' && !Database::hasConnection($databaseAlias)) {
            throw new InvalidArgumentException('Database cabang tidak valid.');
        }

        $profileJson = null;
        if ($databaseAlias !== '') {
            $profileJson = json_encode(array('database_alias' => $databaseAlias));
            if ($profileJson === false) {
                throw new InvalidArgumentException('Gagal menyimpan konfigurasi database cabang.');
            }
        }

        $now = date('Y-m-d H:i:s');
        $password = (string) ($payload['password'] ?? '');
        $passwordHash = null;

        if ($isCreate && trim($password) === '') {
            throw new InvalidArgumentException('Field password wajib diisi untuk user baru.');
        }

        if (trim($password) !== '') {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        }

        return array(
            'username' => $username,
            'full_name' => $fullName,
            'email' => trim((string) ($payload['email'] ?? '')),
            'password_hash' => $passwordHash,
            'status' => $status,
            'role_ids' => array_values($roleIds),
            'profile_json' => $profileJson,
            'created_at' => $payload['created_at'] ?? $now,
            'updated_at' => $now,
        );
    }

    private function extractRoleIds(array $roles)
    {
        $ids = array();
        foreach ($roles as $role) {
            if (is_array($role) && isset($role['id'])) {
                $ids[] = (int) $role['id'];
            }
        }
        return $ids;
    }
}
