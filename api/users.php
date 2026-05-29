<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Rbac;
use App\Services\UserRoleService;

$service = new UserRoleService();
$method = Request::method();
$actor = Auth::currentUser();
$actorId = $actor['id'] ?? null;

try {
    if ($method === 'GET') {
        Rbac::authorize('users.manage');
        $search = (string) Request::query('search', '');
        $limit = (int) Request::query('limit', 50);
        $offset = (int) Request::query('offset', 0);
        Response::json(array(
            'success' => true,
            'data' => $service->listUsers($search, $limit, $offset),
            'roles' => $service->listRoles(),
            'permissions' => $service->listPermissions(),
            'connections' => $service->listConnections(),
        ));
    }

    if ($method === 'POST') {
        Rbac::authorize('users.manage');
        $payload = Request::data();
        $result = $service->createUser($payload, $actorId);
        Response::json(array('success' => true, 'message' => 'User berhasil ditambahkan.', 'data' => $result), 201);
    }

    Response::json(array('success' => false, 'message' => 'Method tidak didukung.'), 405);
} catch (\Throwable $exception) {
    Response::json(array('success' => false, 'message' => $exception->getMessage()), api_exception_status($exception, 500));
}
