<?php

if (class_exists('App\\Core\\Env')) {
    App\Core\Env::load(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env');
} else {
    $envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
    if (is_readable($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines !== false) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || strpos($line, '#') === 0) {
                    continue;
                }
                $parts = explode('=', $line, 2);
                if (count($parts) !== 2) {
                    continue;
                }
                $key = trim($parts[0]);
                $value = trim($parts[1], "\"'");
                if ($key !== '' && getenv($key) === false) {
                    putenv($key . '=' . $value);
                }
            }
        }
    }
}

$normalizeDriver = static function ($driver) {
    $driver = strtolower(trim((string) $driver));
    if ($driver === 'sqlserver') {
        return 'sqlsrv';
    }

    if (!in_array($driver, array('mysql', 'sqlsrv'), true)) {
        return 'mysql';
    }

    return $driver;
};

$defaultDriver = $normalizeDriver(getenv('DB_DRIVER') ?: 'mysql');
$defaultConnection = array(
    'driver' => $defaultDriver,
    'label' => trim((string) (getenv('DB_LABEL') ?: 'Default')),
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => (int) (getenv('DB_PORT') ?: ($defaultDriver === 'sqlsrv' ? 1433 : 3306)),
    'database' => getenv('DB_NAME') ?: 'geomap_crm',
    'username' => getenv('DB_USER') ?: ($defaultDriver === 'sqlsrv' ? 'sa' : 'root'),
    'password' => getenv('DB_PASS') ?: '',
    'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    'encrypt' => getenv('DB_ENCRYPT') ?: 'false',
    'trust_server_certificate' => getenv('DB_TRUST_CERT') ?: 'true',
);

$connections = array();
$rawConnections = trim((string) getenv('DB_CONNECTIONS'));
if ($rawConnections !== '') {
    $decodedConnections = json_decode($rawConnections, true);
    if (is_array($decodedConnections)) {
        foreach ($decodedConnections as $alias => $payload) {
            if (!is_array($payload)) {
                continue;
            }

            $payloadDriver = $normalizeDriver((string) ($payload['driver'] ?? $defaultDriver));
            $driverPayload = array();
            if (isset($payload[$payloadDriver]) && is_array($payload[$payloadDriver])) {
                $driverPayload = $payload[$payloadDriver];
            }

            $connections[(string) $alias] = array_merge(
                $defaultConnection,
                $driverPayload,
                $payload,
                array(
                    'driver' => $payloadDriver,
                    'label' => trim((string) ($payload['label'] ?? ($payload['name'] ?? (string) $alias))),
                )
            );
        }
    }
}

return array(
    'driver' => $defaultDriver,
    'label' => $defaultConnection['label'],
    'host' => $defaultConnection['host'],
    'port' => $defaultConnection['port'],
    'database' => $defaultConnection['database'],
    'username' => $defaultConnection['username'],
    'password' => $defaultConnection['password'],
    'charset' => $defaultConnection['charset'],
    'encrypt' => $defaultConnection['encrypt'],
    'trust_server_certificate' => $defaultConnection['trust_server_certificate'],
    'options' => $defaultDriver === 'mysql'
        ? array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        )
        : array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ),
    'connections' => $connections,
);
