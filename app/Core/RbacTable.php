<?php

namespace App\Core;

use PDO;

class RbacTable
{
    private static $cache = array();

    public static function clearCache()
    {
        self::$cache = array();
    }

    public static function userRole(PDO $pdo = null)
    {
        if ($pdo === null) {
            $pdo = Database::connection();
        }

        return self::resolve($pdo, 'user_role', array('role_user', 'user_role'));
    }

    public static function permissionRole(PDO $pdo = null)
    {
        if ($pdo === null) {
            $pdo = Database::connection();
        }

        return self::resolve($pdo, 'permission_role', array('permission_role', 'role_permission'));
    }

    private static function resolve(PDO $pdo, $cacheKey, array $candidates)
    {
        $driver = Database::driver();
        $finalKey = $driver . ':' . $cacheKey;
        if (array_key_exists($finalKey, self::$cache)) {
            return self::$cache[$finalKey];
        }

        foreach ($candidates as $tableName) {
            if (self::exists($pdo, $tableName)) {
                self::$cache[$finalKey] = $tableName;
                return $tableName;
            }
        }

        self::$cache[$finalKey] = null;
        return null;
    }

    private static function exists(PDO $pdo, $tableName)
    {
        $driver = Database::driver();

        if ($driver === 'sqlsrv') {
            $statement = $pdo->prepare(
                'SELECT COUNT(*) AS total
                 FROM INFORMATION_SCHEMA.TABLES
                 WHERE TABLE_SCHEMA = :schema
                   AND TABLE_NAME = :table_name'
            );
            $statement->execute(array(
                ':schema' => 'dbo',
                ':table_name' => $tableName,
            ));
        } else {
            $statement = $pdo->prepare(
                'SELECT COUNT(*) AS total
                 FROM INFORMATION_SCHEMA.TABLES
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table_name'
            );
            $statement->execute(array(
                ':table_name' => $tableName,
            ));
        }

        $result = $statement->fetch();
        return isset($result['total']) && (int) $result['total'] > 0;
    }
}
