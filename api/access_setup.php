<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Rbac;
use App\Core\RbacTable;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

$method = Request::method();
$user = Auth::currentUser();
if (!$user || empty($user['id'])) {
    Response::json(array('success' => false, 'message' => 'Anda belum login.'), 401);
}

try {
    $pdo = Database::connection();

    if ($method === 'GET') {
        Response::json(array(
            'success' => true,
            'data' => collectAccessStatus($pdo, $user),
        ));
    }

    if ($method === 'POST') {
        $payload = Request::data();
        $action = trim((string) ($payload['action'] ?? ''));
        if ($action !== 'auto_fix_access') {
            Response::json(array('success' => false, 'message' => 'Action tidak didukung.'), 422);
        }

        if (!canRunRepair($pdo, $user)) {
            Response::json(array('success' => false, 'message' => 'Akun ini tidak diizinkan menjalankan auto-fix akses.'), 403);
        }

        $repairResult = runAutoRepair($pdo, $user);
        refreshUserSession($pdo, (int) $user['id']);

        Response::json(array(
            'success' => true,
            'message' => 'Auto-fix akses berhasil dijalankan.',
            'data' => array(
                'repair' => $repairResult,
                'status' => collectAccessStatus($pdo, Auth::currentUser()),
            ),
        ));
    }

    Response::json(array('success' => false, 'message' => 'Method tidak didukung.'), 405);
} catch (\Throwable $exception) {
    Response::json(array('success' => false, 'message' => $exception->getMessage()), api_exception_status($exception, 500));
}

function collectAccessStatus(PDO $pdo, array $user)
{
    $userRoleTable = RbacTable::userRole($pdo);
    $permissionRoleTable = RbacTable::permissionRole($pdo);
    $roleCount = (int) $pdo->query('SELECT COUNT(*) AS total FROM roles')->fetch()['total'];
    $permissionCount = (int) $pdo->query('SELECT COUNT(*) AS total FROM permissions')->fetch()['total'];

    $issues = array();
    if ($userRoleTable === null) {
        $issues[] = 'Tabel relasi user-role belum ada (`role_user` atau `user_role`).';
    }
    if ($permissionRoleTable === null) {
        $issues[] = 'Tabel relasi role-permission belum ada (`permission_role` atau `role_permission`).';
    }

    return array(
        'user' => array(
            'id' => (int) ($user['id'] ?? 0),
            'username' => (string) ($user['username'] ?? ''),
            'full_name' => (string) ($user['full_name'] ?? ''),
            'roles' => $user['roles'] ?? array(),
            'permissions' => $user['permissions'] ?? array(),
        ),
        'is_super_admin' => Rbac::isSuperAdminUser($user),
        'can_run_repair' => canRunRepair($pdo, $user),
        'tables' => array(
            'user_role_table' => $userRoleTable,
            'permission_role_table' => $permissionRoleTable,
        ),
        'counts' => array(
            'roles' => $roleCount,
            'permissions' => $permissionCount,
        ),
        'issues' => $issues,
    );
}

function canRunRepair(PDO $pdo, array $user)
{
    if (Rbac::isSuperAdminUser($user)) {
        return true;
    }

    if ((int) ($user['id'] ?? 0) === 1) {
        return true;
    }

    $userRoleTable = RbacTable::userRole($pdo);
    if ($userRoleTable === null) {
        return true;
    }

    $countStmt = $pdo->query('SELECT COUNT(*) AS total FROM ' . $userRoleTable);
    $mappingCount = (int) $countStmt->fetch()['total'];
    return $mappingCount === 0;
}

function runAutoRepair(PDO $pdo, array $user)
{
    $driver = Database::driver();
    $changes = array();
    $userId = (int) $user['id'];

    $pdo->beginTransaction();
    try {
        $userRoleTable = RbacTable::userRole($pdo);
        if ($userRoleTable === null) {
            createUserRoleTable($pdo, $driver);
            $changes[] = 'Membuat tabel role_user.';
            RbacTable::clearCache();
            $userRoleTable = 'role_user';
        }

        $permissionRoleTable = RbacTable::permissionRole($pdo);
        if ($permissionRoleTable === null) {
            createPermissionRoleTable($pdo, $driver);
            $changes[] = 'Membuat tabel permission_role.';
            RbacTable::clearCache();
            $permissionRoleTable = 'permission_role';
        }

        ensureDefaultRoles($pdo);
        ensureDefaultPermissions($pdo);
        $superRoleId = ensureSuperAdminRole($pdo);

        if (assignRoleIfMissing($pdo, $userRoleTable, $superRoleId, $userId)) {
            $changes[] = 'Menambahkan role SUPER_ADMIN ke user saat ini.';
        }

        $grantedCount = grantAllPermissionsToRole($pdo, $permissionRoleTable, $superRoleId);
        $changes[] = 'Sinkronisasi permission SUPER_ADMIN: +' . $grantedCount . ' mapping baru.';

        $pdo->commit();
    } catch (\Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }

    return array(
        'changes' => $changes,
    );
}

function createUserRoleTable(PDO $pdo, $driver)
{
    if ($driver === 'sqlsrv') {
        $pdo->exec(
            "CREATE TABLE dbo.role_user (
                role_id INT NOT NULL,
                user_id INT NOT NULL,
                created_at DATETIME2 NOT NULL CONSTRAINT df_role_user_rescue_created DEFAULT GETDATE(),
                CONSTRAINT pk_role_user_rescue PRIMARY KEY (role_id, user_id)
            )"
        );
        $pdo->exec("ALTER TABLE dbo.role_user WITH CHECK ADD CONSTRAINT fk_role_user_rescue_role FOREIGN KEY (role_id) REFERENCES dbo.roles(id) ON DELETE CASCADE");
        $pdo->exec("ALTER TABLE dbo.role_user WITH CHECK ADD CONSTRAINT fk_role_user_rescue_user FOREIGN KEY (user_id) REFERENCES dbo.users(id) ON DELETE CASCADE");
        return;
    }

    $pdo->exec(
        'CREATE TABLE role_user (
            role_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (role_id, user_id)
        )'
    );
}

function createPermissionRoleTable(PDO $pdo, $driver)
{
    if ($driver === 'sqlsrv') {
        $pdo->exec(
            "CREATE TABLE dbo.permission_role (
                permission_id INT NOT NULL,
                role_id INT NOT NULL,
                created_at DATETIME2 NOT NULL CONSTRAINT df_permission_role_rescue_created DEFAULT GETDATE(),
                CONSTRAINT pk_permission_role_rescue PRIMARY KEY (permission_id, role_id)
            )"
        );
        $pdo->exec("ALTER TABLE dbo.permission_role WITH CHECK ADD CONSTRAINT fk_permission_role_rescue_permission FOREIGN KEY (permission_id) REFERENCES dbo.permissions(id) ON DELETE CASCADE");
        $pdo->exec("ALTER TABLE dbo.permission_role WITH CHECK ADD CONSTRAINT fk_permission_role_rescue_role FOREIGN KEY (role_id) REFERENCES dbo.roles(id) ON DELETE CASCADE");
        return;
    }

    $pdo->exec(
        'CREATE TABLE permission_role (
            permission_id INT NOT NULL,
            role_id INT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (permission_id, role_id)
        )'
    );
}

function ensureDefaultRoles(PDO $pdo)
{
    $roles = array(
        array('code' => 'SUPER_ADMIN', 'name' => 'SUPER ADMIN', 'description' => 'Akses semua fitur + konfigurasi sistem + multi-perusahaan'),
        array('code' => 'ADMIN', 'name' => 'ADMIN', 'description' => 'CRUD semua master, lihat semua laporan, manage salesman'),
        array('code' => 'SUPERVISOR', 'name' => 'SUPERVISOR', 'description' => 'Lihat area sendiri, approve prospek, assign target'),
        array('code' => 'SALESMAN', 'name' => 'SALESMAN', 'description' => 'Mobile app, customer sendiri, input kunjungan'),
        array('code' => 'VIEWER', 'name' => 'VIEWER', 'description' => 'Read-only peta dan laporan'),
    );

    foreach ($roles as $role) {
        $exists = $pdo->prepare('SELECT COUNT(*) AS total FROM roles WHERE code = :code');
        $exists->execute(array(':code' => $role['code']));
        if ((int) $exists->fetch()['total'] > 0) {
            continue;
        }

        $insert = $pdo->prepare('INSERT INTO roles (code, name, description) VALUES (:code, :name, :description)');
        $insert->execute(array(
            ':code' => $role['code'],
            ':name' => $role['name'],
            ':description' => $role['description'],
        ));
    }
}

function ensureDefaultPermissions(PDO $pdo)
{
    $columns = tableColumns($pdo, 'permissions');
    $hasNameModule = in_array('name', $columns, true) && in_array('module', $columns, true);
    $hasModuleAction = in_array('module_name', $columns, true) && in_array('action_name', $columns, true);

    $permissions = array(
        array('code' => 'customer_inti.view', 'name' => 'Lihat Customer Inti', 'module' => 'customer_inti'),
        array('code' => 'customer_inti.create', 'name' => 'Tambah Customer Inti', 'module' => 'customer_inti'),
        array('code' => 'customer_inti.update', 'name' => 'Ubah Customer Inti', 'module' => 'customer_inti'),
        array('code' => 'customer_inti.delete', 'name' => 'Hapus Customer Inti', 'module' => 'customer_inti'),
        array('code' => 'customer_existing.view', 'name' => 'Lihat Customer Existing', 'module' => 'customer_existing'),
        array('code' => 'customer_existing.create', 'name' => 'Tambah Customer Existing', 'module' => 'customer_existing'),
        array('code' => 'customer_existing.update', 'name' => 'Ubah Customer Existing', 'module' => 'customer_existing'),
        array('code' => 'customer_existing.delete', 'name' => 'Hapus Customer Existing', 'module' => 'customer_existing'),
        array('code' => 'salesman.view', 'name' => 'Lihat Salesman', 'module' => 'salesman'),
        array('code' => 'salesman.manage', 'name' => 'Kelola Salesman', 'module' => 'salesman'),
        array('code' => 'users.manage', 'name' => 'Kelola User & Role', 'module' => 'users_roles'),
        array('code' => 'wilayah.manage', 'name' => 'Kelola Master Wilayah', 'module' => 'wilayah'),
        array('code' => 'target_realisasi.manage', 'name' => 'Kelola Target Realisasi', 'module' => 'target_realisasi'),
        array('code' => 'laporan.view', 'name' => 'Lihat Laporan', 'module' => 'laporan'),
        array('code' => 'notifikasi.manage', 'name' => 'Kelola Notifikasi', 'module' => 'notifikasi'),
        array('code' => 'import_export.manage', 'name' => 'Kelola Import Export', 'module' => 'import_export'),
        array('code' => 'audit_log.view', 'name' => 'Lihat Audit Log', 'module' => 'audit_log'),
    );

    foreach ($permissions as $permission) {
        $exists = $pdo->prepare('SELECT COUNT(*) AS total FROM permissions WHERE code = :code');
        $exists->execute(array(':code' => $permission['code']));
        if ((int) $exists->fetch()['total'] > 0) {
            continue;
        }

        if ($hasNameModule) {
            $insert = $pdo->prepare('INSERT INTO permissions (code, name, module) VALUES (:code, :name, :module)');
            $insert->execute(array(
                ':code' => $permission['code'],
                ':name' => $permission['name'],
                ':module' => $permission['module'],
            ));
            continue;
        }

        if ($hasModuleAction) {
            $action = explode('.', $permission['code']);
            $insert = $pdo->prepare(
                'INSERT INTO permissions (module_name, action_name, code, description)
                 VALUES (:module_name, :action_name, :code, :description)'
            );
            $insert->execute(array(
                ':module_name' => $permission['module'],
                ':action_name' => isset($action[1]) ? $action[1] : 'manage',
                ':code' => $permission['code'],
                ':description' => $permission['name'],
            ));
            continue;
        }

        $insert = $pdo->prepare('INSERT INTO permissions (code) VALUES (:code)');
        $insert->execute(array(':code' => $permission['code']));
    }
}

function ensureSuperAdminRole(PDO $pdo)
{
    $roles = $pdo->query('SELECT id, code, name FROM roles')->fetchAll();
    foreach ($roles as $role) {
        $code = normalizeIdentity($role['code'] ?? '');
        $name = normalizeIdentity($role['name'] ?? '');
        if ($code === 'SUPERADMIN' || $name === 'SUPERADMIN') {
            return (int) $role['id'];
        }
    }

    $insert = $pdo->prepare('INSERT INTO roles (code, name, description) VALUES (:code, :name, :description)');
    $insert->execute(array(
        ':code' => 'SUPER_ADMIN',
        ':name' => 'SUPER ADMIN',
        ':description' => 'Akses semua fitur',
    ));

    return (int) $pdo->lastInsertId();
}

function assignRoleIfMissing(PDO $pdo, $userRoleTable, $roleId, $userId)
{
    $check = $pdo->prepare('SELECT COUNT(*) AS total FROM ' . $userRoleTable . ' WHERE role_id = :role_id AND user_id = :user_id');
    $check->execute(array(':role_id' => $roleId, ':user_id' => $userId));
    if ((int) $check->fetch()['total'] > 0) {
        return false;
    }

    $insert = $pdo->prepare('INSERT INTO ' . $userRoleTable . ' (role_id, user_id) VALUES (:role_id, :user_id)');
    $insert->execute(array(':role_id' => $roleId, ':user_id' => $userId));
    return true;
}

function grantAllPermissionsToRole(PDO $pdo, $permissionRoleTable, $roleId)
{
    $permissionIds = $pdo->query('SELECT id FROM permissions')->fetchAll();
    $inserted = 0;
    foreach ($permissionIds as $permissionRow) {
        $permissionId = (int) $permissionRow['id'];
        $check = $pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM ' . $permissionRoleTable . '
             WHERE permission_id = :permission_id AND role_id = :role_id'
        );
        $check->execute(array(
            ':permission_id' => $permissionId,
            ':role_id' => $roleId,
        ));
        if ((int) $check->fetch()['total'] > 0) {
            continue;
        }

        $insert = $pdo->prepare(
            'INSERT INTO ' . $permissionRoleTable . ' (permission_id, role_id)
             VALUES (:permission_id, :role_id)'
        );
        $insert->execute(array(
            ':permission_id' => $permissionId,
            ':role_id' => $roleId,
        ));
        $inserted++;
    }

    return $inserted;
}

function refreshUserSession(PDO $pdo, $userId)
{
    $authPdo = Database::authConnection();
    $userStmt = $authPdo->prepare(
        'SELECT id, username, full_name, email, profile_json
         FROM users
         WHERE id = :id'
    );
    $userStmt->execute(array(':id' => (int) $userId));
    $row = $userStmt->fetch();
    if (!$row) {
        return;
    }

    $profile = array();
    if (is_string($row['profile_json'] ?? null) && trim($row['profile_json']) !== '') {
        $decodedProfile = json_decode($row['profile_json'], true);
        if (is_array($decodedProfile)) {
            $profile = $decodedProfile;
        }
    }

    $databaseAlias = trim((string) ($profile['database_alias'] ?? ''));
    $databaseLabel = '';
    if ($databaseAlias !== '') {
        $connections = Database::availableConnections();
        $databaseLabel = trim((string) ($connections[$databaseAlias]['label'] ?? $databaseAlias));
    }

    $roles = array();
    $permissions = array();
    $userRoleTable = RbacTable::userRole($pdo);
    $permissionRoleTable = RbacTable::permissionRole($pdo);

    if ($userRoleTable !== null) {
        $rolesStmt = $pdo->prepare(
            'SELECT r.id, r.code, r.name
             FROM roles r
             INNER JOIN ' . $userRoleTable . ' ru ON ru.role_id = r.id
             WHERE ru.user_id = :user_id'
        );
        $rolesStmt->execute(array(':user_id' => (int) $userId));
        $roles = $rolesStmt->fetchAll();
    }

    if ($userRoleTable !== null && $permissionRoleTable !== null) {
        $permStmt = $pdo->prepare(
            'SELECT DISTINCT p.code
             FROM permissions p
             INNER JOIN ' . $permissionRoleTable . ' pr ON pr.permission_id = p.id
             INNER JOIN ' . $userRoleTable . ' ru ON ru.role_id = pr.role_id
             WHERE ru.user_id = :user_id'
        );
        $permStmt->execute(array(':user_id' => (int) $userId));
        $permissionRows = $permStmt->fetchAll();
        foreach ($permissionRows as $permissionRow) {
            if (!empty($permissionRow['code'])) {
                $permissions[] = $permissionRow['code'];
            }
        }
    }

    Session::set(Auth::SESSION_KEY, array(
        'id' => (int) $row['id'],
        'username' => (string) $row['username'],
        'full_name' => (string) $row['full_name'],
        'email' => $row['email'],
        'database_alias' => $databaseAlias,
        'database_label' => $databaseLabel,
        'roles' => $roles,
        'permissions' => $permissions,
    ));
}

function tableColumns(PDO $pdo, $tableName)
{
    $driver = Database::driver();
    if ($driver === 'sqlsrv') {
        $statement = $pdo->prepare(
            'SELECT COLUMN_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = :schema
               AND TABLE_NAME = :table_name'
        );
        $statement->execute(array(':schema' => 'dbo', ':table_name' => $tableName));
    } else {
        $statement = $pdo->prepare(
            'SELECT COLUMN_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name'
        );
        $statement->execute(array(':table_name' => $tableName));
    }

    $rows = $statement->fetchAll();
    $columns = array();
    foreach ($rows as $row) {
        $columns[] = strtolower((string) $row['COLUMN_NAME']);
    }
    return $columns;
}

function normalizeIdentity($value)
{
    $value = strtoupper((string) $value);
    return preg_replace('/[^A-Z0-9]/', '', $value);
}
