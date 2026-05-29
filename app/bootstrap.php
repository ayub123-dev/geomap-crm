<?php

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Jakarta');

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . DIRECTORY_SEPARATOR;

    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

App\Core\Env::load(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env');
App\Core\Session::start();

try {
    App\Core\Auth::ensureDefaultSuperAdmin();
} catch (\Throwable $exception) {
    // Abaikan saat tabel users/roles belum tersedia.
}
