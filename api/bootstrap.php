<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';

try {
    App\Core\Auth::ensureDefaultSuperAdmin();
} catch (\Throwable $exception) {
    // Abaikan jika tabel users/roles belum tersedia.
}

try {
    App\Core\Auth::currentUser();
} catch (\Throwable $exception) {
    // Biarkan tenant fallback ke default jika session tidak dapat dipulihkan.
}

if (!in_array($method, array('GET', 'HEAD', 'OPTIONS'), true)) {
    if (!App\Core\Csrf::validateRequest()) {
        http_response_code(419);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array(
            'success' => false,
            'message' => 'Token CSRF tidak valid atau sudah kedaluwarsa.',
        ));
        exit;
    }
}

if (!function_exists('api_exception_status')) {
    function api_exception_status(\Throwable $exception, $default = 500)
    {
        $statusCode = (int) $exception->getCode();
        if ($statusCode >= 400 && $statusCode <= 599) {
            return $statusCode;
        }

        return (int) $default;
    }
}
