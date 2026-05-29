<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Rbac;
use App\Services\SalesmanService;

$service = new SalesmanService();
$method = Request::method();
$user = Auth::currentUser();
$userId = $user['id'] ?? null;

try {
    if ($method === 'GET') {
        Rbac::authorize('salesman.view');
        $search = (string) Request::query('search', '');
        $limit = (int) Request::query('limit', 50);
        $offset = (int) Request::query('offset', 0);
        Response::json(array(
            'success' => true,
            'data' => $service->list($search, $limit, $offset),
            'meta' => array(
                'total' => $service->count($search),
                'limit' => $limit,
                'offset' => $offset,
            ),
        ));
    }

    if ($method === 'POST') {
        Rbac::authorize('salesman.manage');
        $payload = Request::data();
        $result = $service->create($payload, $userId);
        Response::json(array('success' => true, 'message' => 'Salesman berhasil ditambahkan.', 'data' => $result), 201);
    }

    Response::json(array('success' => false, 'message' => 'Method tidak didukung.'), 405);
} catch (\Throwable $exception) {
    Response::json(array('success' => false, 'message' => $exception->getMessage()), api_exception_status($exception, 500));
}
