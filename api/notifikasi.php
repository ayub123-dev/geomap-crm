<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Rbac;
use App\Services\NotifikasiService;

$service = new NotifikasiService();
$method = Request::method();
$user = Auth::currentUser();
$userId = $user['id'] ?? null;

try {
    if ($method === 'GET') {
        Rbac::authorize('notifikasi.manage');
        $limit = (int) Request::query('limit', 100);
        Response::json(array('success' => true, 'data' => $service->list($limit)));
    }

    if ($method === 'POST') {
        Rbac::authorize('notifikasi.manage');
        $payload = Request::data();
        $id = $service->create($payload, $userId);
        Response::json(array('success' => true, 'message' => 'Notifikasi berhasil dibuat.', 'data' => array('id' => $id)), 201);
    }

    Response::json(array('success' => false, 'message' => 'Method tidak didukung.'), 405);
} catch (\Throwable $exception) {
    Response::json(array('success' => false, 'message' => $exception->getMessage()), api_exception_status($exception, 500));
}
