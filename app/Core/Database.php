<?php

namespace App\Core;

use InvalidArgumentException;
use PDO;
use PDOException;

class Database
{
    private static $instances = array();
    private static $config;
    private static $currentConnectionKey = null;
    private static $defaultConnectionKey = 'default';
    private static $authProfileJsonColumnEnsured = false;

    public static function connection($connectionKey = null)
    {
        self::ensureConfigLoaded();
        $connectionKey = self::resolveConnectionKey($connectionKey);

        if (isset(self::$instances[$connectionKey]) && self::$instances[$connectionKey] instanceof PDO) {
            return self::$instances[$connectionKey];
        }

        $settings = self::resolveConnectionDefinition($connectionKey);
        $options = self::buildOptions($settings['driver'], $settings);

        try {
            if ($settings['driver'] === 'mysql') {
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                    $settings['host'],
                    $settings['port'],
                    $settings['database'],
                    $settings['charset']
                );
            } else {
                $dsn = sprintf(
                    'sqlsrv:Server=%s,%d;Database=%s;Encrypt=%s;TrustServerCertificate=%s',
                    $settings['host'],
                    $settings['port'],
                    $settings['database'],
                    $settings['encrypt'],
                    $settings['trust_server_certificate']
                );
            }

            self::$instances[$connectionKey] = new PDO($dsn, $settings['username'], $settings['password'], $options);
        } catch (PDOException $exception) {
            throw new PDOException(
                'Database connection failed using driver "' . $settings['driver'] . '": ' . $exception->getMessage(),
                (int) $exception->getCode()
            );
        }

        return self::$instances[$connectionKey];
    }

    public static function authConnection()
    {
        $pdo = self::connection(self::$defaultConnectionKey);
        self::ensureAuthUserProfileJsonColumn($pdo);
        return $pdo;
    }

    public static function driver($connectionKey = null)
    {
        self::ensureConfigLoaded();
        $connectionKey = self::resolveConnectionKey($connectionKey);
        return self::resolveConnectionDefinition($connectionKey)['driver'];
    }

    public static function currentConnectionKey()
    {
        self::ensureConfigLoaded();
        return self::$currentConnectionKey === null ? self::$defaultConnectionKey : self::$currentConnectionKey;
    }

    public static function switchConnection($connectionKey = null)
    {
        self::ensureConfigLoaded();
        if ($connectionKey === null || trim((string) $connectionKey) === '') {
            self::$currentConnectionKey = self::$defaultConnectionKey;
            return self::$currentConnectionKey;
        }

        $normalizedKey = trim((string) $connectionKey);
        if (!self::hasConnection($normalizedKey)) {
            throw new InvalidArgumentException('Database connection "' . $normalizedKey . '" is not configured.');
        }

        self::$currentConnectionKey = $normalizedKey;
        return self::$currentConnectionKey;
    }

    public static function availableConnections()
    {
        self::ensureConfigLoaded();

        $connections = array();
        foreach ((array) (self::$config['connections'] ?? array()) as $alias => $definition) {
            if (!is_array($definition)) {
                continue;
            }

            $connections[(string) $alias] = array(
                'label' => trim((string) ($definition['label'] ?? $alias)),
                'driver' => trim((string) ($definition['driver'] ?? self::$config['driver'] ?? 'mysql')),
            );
        }

        return $connections;
    }

    public static function hasConnection($connectionKey = null)
    {
        self::ensureConfigLoaded();
        $connectionKey = self::resolveConnectionKey($connectionKey);

        if ($connectionKey === self::$defaultConnectionKey) {
            return true;
        }

        return is_array(self::$config['connections'] ?? null) && array_key_exists($connectionKey, self::$config['connections']);
    }

    private static function ensureConfigLoaded()
    {
        if (!is_array(self::$config)) {
            self::$config = require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
        }
    }

    private static function resolveConnectionKey($connectionKey = null)
    {
        if ($connectionKey === null) {
            return self::$currentConnectionKey === null ? self::$defaultConnectionKey : self::$currentConnectionKey;
        }

        $normalizedKey = trim((string) $connectionKey);
        if ($normalizedKey === '') {
            return self::$defaultConnectionKey;
        }

        return $normalizedKey;
    }

    private static function ensureAuthUserProfileJsonColumn(PDO $pdo)
    {
        if (self::$authProfileJsonColumnEnsured) {
            return;
        }

        $driver = self::driver(self::$defaultConnectionKey);
        $hasColumn = false;

        if ($driver === 'sqlsrv') {
            $statement = $pdo->query("SELECT TOP 1 COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'users' AND COLUMN_NAME = 'profile_json'");
            $hasColumn = $statement !== false && $statement->fetchColumn() !== false;
            if (!$hasColumn) {
                $pdo->exec("ALTER TABLE users ADD profile_json NVARCHAR(MAX) NULL");
            }
        } else {
            $statement = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'users' AND COLUMN_NAME = 'profile_json' LIMIT 1");
            $hasColumn = $statement !== false && $statement->fetchColumn() !== false;
            if (!$hasColumn) {
                $pdo->exec("ALTER TABLE users ADD COLUMN profile_json JSON NULL");
            }
        }

        self::$authProfileJsonColumnEnsured = true;
    }

    private static function resolveConnectionDefinition($connectionKey)
    {
        self::ensureConfigLoaded();

        if ($connectionKey === self::$defaultConnectionKey) {
            return self::normalizeDefinition(self::$config, 'default');
        }

        if (!is_array(self::$config['connections'] ?? null) || !array_key_exists($connectionKey, self::$config['connections'])) {
            throw new InvalidArgumentException('Database connection "' . $connectionKey . '" is not configured.');
        }

        return self::normalizeDefinition(self::$config['connections'][$connectionKey], $connectionKey);
    }

    private static function normalizeDefinition(array $definition, $label)
    {
        $driver = strtolower((string) ($definition['driver'] ?? self::$config['driver'] ?? 'mysql'));
        if ($driver === 'sqlserver') {
            $driver = 'sqlsrv';
        }

        if (!in_array($driver, array('mysql', 'sqlsrv'), true)) {
            $driver = 'mysql';
        }

        $options = isset($definition['options']) && is_array($definition['options'])
            ? $definition['options']
            : null;

        return array(
            'driver' => $driver,
            'label' => trim((string) ($definition['label'] ?? $label)),
            'host' => trim((string) ($definition['host'] ?? self::$config['host'] ?? '127.0.0.1')),
            'port' => (int) ($definition['port'] ?? self::$config['port'] ?? ($driver === 'sqlsrv' ? 1433 : 3306)),
            'database' => trim((string) ($definition['database'] ?? self::$config['database'] ?? 'geomap_crm')),
            'username' => trim((string) ($definition['username'] ?? self::$config['username'] ?? ($driver === 'sqlsrv' ? 'sa' : 'root'))),
            'password' => (string) ($definition['password'] ?? self::$config['password'] ?? ''),
            'charset' => trim((string) ($definition['charset'] ?? self::$config['charset'] ?? 'utf8mb4')),
            'encrypt' => trim((string) ($definition['encrypt'] ?? self::$config['encrypt'] ?? 'false')),
            'trust_server_certificate' => trim((string) ($definition['trust_server_certificate'] ?? self::$config['trust_server_certificate'] ?? 'true')),
            'options' => $options,
        );
    }

    private static function buildOptions($driver, array $settings)
    {
        if (isset($settings['options']) && is_array($settings['options'])) {
            return $settings['options'];
        }

        if ($driver === 'mysql') {
            return array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            );
        }

        return array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        );
    }
}
