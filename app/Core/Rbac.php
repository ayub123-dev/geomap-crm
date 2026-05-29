<?php

namespace App\Core;

use RuntimeException;

class Rbac
{
    public static function isSuperAdminUser(array $user = null)
    {
        if (!is_array($user)) {
            $user = Auth::currentUser();
        }
        if (!is_array($user)) {
            return false;
        }

        $permissions = $user['permissions'] ?? array();
        if (in_array('*', $permissions, true)) {
            return true;
        }

        $roles = $user['roles'] ?? array();
        foreach ($roles as $role) {
            $roleCode = self::normalizeRoleIdentity($role['code'] ?? '');
            $roleName = self::normalizeRoleIdentity($role['name'] ?? '');
            if (in_array($roleCode, array('SUPERADMIN', 'ROOT'), true) || in_array($roleName, array('SUPERADMIN', 'ROOT'), true)) {
                return true;
            }
        }

        return false;
    }

    public static function hasPermission($permissionCode)
    {
        $user = Auth::currentUser();
        if (!$user) {
            return false;
        }

        $permissions = $user['permissions'] ?? array();
        if (in_array('*', $permissions, true)) {
            return true;
        }

        if (self::isSuperAdminUser($user)) {
            return true;
        }

        return in_array($permissionCode, $permissions, true);
    }

    public static function authorize($permissionCode)
    {
        if (!self::hasPermission($permissionCode)) {
            throw new RuntimeException('Akses ditolak untuk permission: ' . $permissionCode, 403);
        }
    }

    private static function normalizeRoleIdentity($value)
    {
        $value = strtoupper((string) $value);
        return preg_replace('/[^A-Z0-9]/', '', $value);
    }
}
