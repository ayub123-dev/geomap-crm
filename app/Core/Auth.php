<?php

namespace App\Core;

use PDO;

class Auth
{
    const SESSION_KEY = 'geomap_auth_user';

    public static function currentUser()
    {
        $user = Session::get(self::SESSION_KEY);
        if (!is_array($user) || empty($user['id'])) {
            Database::switchConnection();
            return self::guestFallback();
        }

        $databaseAlias = trim((string) ($user['database_alias'] ?? ''));
        if ($databaseAlias === '') {
            $databaseAlias = self::resolveDatabaseAliasFromUserId((int) ($user['id'] ?? 0));
            if ($databaseAlias !== '') {
                $user['database_alias'] = $databaseAlias;
                $user['database_label'] = self::resolveDatabaseLabel($databaseAlias);
                Session::set(self::SESSION_KEY, $user);
            }
        }

        try {
            if ($databaseAlias === '') {
                Database::switchConnection();
            } else {
                Database::switchConnection($databaseAlias);
            }
        } catch (\Throwable $exception) {
            Database::switchConnection();
        }

        return $user;
    }

    public static function login($username, $password)
    {
        $username = trim((string) $username);
        if ($username === '' || $password === '') {
            return false;
        }

        $pdo = Database::authConnection();
        $driver = Database::driver('default');
        if ($driver === 'sqlsrv') {
            $sql = 'SELECT TOP 1 id, username, full_name, email, password_hash, status, profile_json
                    FROM users
                    WHERE username = :username';
        } else {
            $sql = 'SELECT id, username, full_name, email, password_hash, status, profile_json
                    FROM users
                    WHERE username = :username
                    LIMIT 1';
        }

        $statement = $pdo->prepare($sql);
        $statement->bindValue(':username', $username, PDO::PARAM_STR);
        $statement->execute();
        $row = $statement->fetch();

        $statusNormalized = self::normalizeStatus($row['status'] ?? '');
        if (!$row || !in_array($statusNormalized, array('AKTIF', 'ACTIVE'), true)) {
            return false;
        }

        if (!password_verify($password, $row['password_hash'])) {
            return false;
        }

        $databaseAlias = self::resolveDatabaseAlias($row['profile_json'] ?? null);
        if ($databaseAlias !== '' && !Database::hasConnection($databaseAlias)) {
            return false;
        }

        try {
            if ($databaseAlias === '') {
                Database::switchConnection();
            } else {
                Database::switchConnection($databaseAlias);
            }
        } catch (\Throwable $exception) {
            return false;
        }

        $user = self::hydrateUser($pdo, $row);
        $user['database_alias'] = $databaseAlias;
        $user['database_label'] = self::resolveDatabaseLabel($databaseAlias);

        Session::regenerate();
        Session::set(self::SESSION_KEY, $user);

        return true;
    }

    public static function logout()
    {
        Database::switchConnection();
        Session::forget(self::SESSION_KEY);
        Session::regenerate();
    }

    public static function ensureDefaultSuperAdmin()
    {
        $pdo = Database::authConnection();
        $count = (int) $pdo->query('SELECT COUNT(*) AS total FROM users')->fetch()['total'];
        if ($count > 0) {
            return;
        }

        $passwordHash = password_hash('admin123', PASSWORD_BCRYPT);
        $insertUser = $pdo->prepare(
            'INSERT INTO users (username, full_name, email, password_hash, status, created_at, updated_at)
             VALUES (:username, :full_name, :email, :password_hash, :status, :created_at, :updated_at)'
        );

        $now = date('Y-m-d H:i:s');
        $insertUser->execute(array(
            ':username' => 'admin',
            ':full_name' => 'Super Admin',
            ':email' => 'admin@geomap.local',
            ':password_hash' => $passwordHash,
            ':status' => 'Aktif',
            ':created_at' => $now,
            ':updated_at' => $now,
        ));

        $userId = (int) $pdo->lastInsertId();

        if (Database::driver('default') === 'sqlsrv') {
            $roleStmt = $pdo->prepare('SELECT TOP 1 id FROM roles WHERE code = :code');
        } else {
            $roleStmt = $pdo->prepare('SELECT id FROM roles WHERE code = :code LIMIT 1');
        }
        $roleStmt->execute(array(':code' => 'SUPER_ADMIN'));
        $role = $roleStmt->fetch();

        $userRoleTable = RbacTable::userRole($pdo);
        if ($role && $userRoleTable !== null) {
            $bindRole = $pdo->prepare(
                'INSERT INTO ' . $userRoleTable . ' (role_id, user_id)
                 VALUES (:role_id, :user_id)'
            );
            $bindRole->execute(array(
                ':role_id' => (int) $role['id'],
                ':user_id' => $userId,
            ));
        }
    }

    private static function hydrateUser(PDO $pdo, array $row)
    {
        $userRoleTable = RbacTable::userRole($pdo);
        $permissionRoleTable = RbacTable::permissionRole($pdo);
        $roles = array();
        $permissionRows = array();

        if ($userRoleTable !== null) {
            $rolesStmt = $pdo->prepare(
                'SELECT r.id, r.code, r.name
                 FROM roles r
                 INNER JOIN ' . $userRoleTable . ' ru ON ru.role_id = r.id
                 WHERE ru.user_id = :user_id'
            );
            $rolesStmt->execute(array(':user_id' => (int) $row['id']));
            $roles = $rolesStmt->fetchAll();
        }

        if ($userRoleTable !== null && $permissionRoleTable !== null) {
            $permissionsStmt = $pdo->prepare(
                'SELECT DISTINCT p.code
                 FROM permissions p
                 INNER JOIN ' . $permissionRoleTable . ' pr ON pr.permission_id = p.id
                 INNER JOIN ' . $userRoleTable . ' ru ON ru.role_id = pr.role_id
                 WHERE ru.user_id = :user_id'
            );
            $permissionsStmt->execute(array(':user_id' => (int) $row['id']));
            $permissionRows = $permissionsStmt->fetchAll();
        }

        $permissions = array();
        foreach ($permissionRows as $permissionRow) {
            if (!empty($permissionRow['code'])) {
                $permissions[] = $permissionRow['code'];
            }
        }

        return array(
            'id' => (int) $row['id'],
            'username' => $row['username'],
            'full_name' => $row['full_name'],
            'email' => $row['email'],
            'roles' => $roles,
            'permissions' => $permissions,
        );
    }

    private static function resolveDatabaseAlias($profileJson)
    {
        if (!is_string($profileJson) || trim($profileJson) === '') {
            return '';
        }

        $decoded = json_decode($profileJson, true);
        if (!is_array($decoded)) {
            return '';
        }

        return trim((string) ($decoded['database_alias'] ?? ''));
    }

    private static function resolveDatabaseLabel($databaseAlias)
    {
        if ($databaseAlias === '') {
            return '';
        }

        $connections = Database::availableConnections();
        if (!isset($connections[$databaseAlias])) {
            return $databaseAlias;
        }

        return trim((string) ($connections[$databaseAlias]['label'] ?? $databaseAlias));
    }

    private static function resolveDatabaseAliasFromUserId($userId)
    {
        if ($userId <= 0) {
            return '';
        }

        $pdo = Database::authConnection();
        $statement = $pdo->prepare('SELECT profile_json FROM users WHERE id = :id');
        $statement->execute(array(':id' => (int) $userId));
        $row = $statement->fetch();
        if (!$row || !is_string($row['profile_json'] ?? null)) {
            return '';
        }

        $decoded = json_decode($row['profile_json'], true);
        if (!is_array($decoded)) {
            return '';
        }

        return trim((string) ($decoded['database_alias'] ?? ''));
    }

    private static function guestFallback()
    {
        if (strtolower((string) Env::get('ALLOW_GUEST_SUPERADMIN', 'false')) !== 'true') {
            return null;
        }

        return array(
            'id' => 0,
            'username' => 'guest-superadmin',
            'full_name' => 'Guest Super Admin',
            'email' => null,
            'roles' => array(
                array('id' => 0, 'code' => 'SUPER_ADMIN', 'name' => 'SUPER ADMIN'),
            ),
            'permissions' => array('*'),
        );
    }

    private static function normalizeStatus($status)
    {
        return strtoupper(trim((string) $status));
    }
}
