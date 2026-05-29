<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Rbac;
use App\Services\UserRoleService;

$service = new UserRoleService();
$method = Request::method();
$id = (int) Request::query('id', 0);
$actor = Auth::currentUser();
$actorId = $actor['id'] ?? null;

if ($id <= 0) {
    Response::json(array('success' => false, 'message' => 'Parameter id wajib diisi.'), 422);
}

try {
    if ($method === 'GET') {
        Rbac::authorize('users.manage');
        $user = $service->getUser($id);
        if (!$user) {
            Response::json(array('success' => false, 'message' => 'Data user tidak ditemukan.'), 404);
        }
        Response::json(array('success' => true, 'data' => $user));
    }

    if ($method === 'PUT') {
        Rbac::authorize('users.manage');
        $payload = Request::data();
        $result = $service->updateUser($id, $payload, $actorId);
        if (!$result) {
            Response::json(array('success' => false, 'message' => 'Data user tidak ditemukan.'), 404);
        }
        Response::json(array('success' => true, 'message' => 'User berhasil diperbarui.', 'data' => $result));
    }

    if ($method === 'DELETE') {
        Rbac::authorize('users.manage');
        $deleted = $service->deleteUser($id, $actorId);
        Response::json(array('success' => $deleted, 'message' => $deleted ? 'User dihapus.' : 'Gagal menghapus user.'));
    }

    Response::json(array('success' => false, 'message' => 'Method tidak didukung.'), 405);
} catch (\Throwable $exception) {
    Response::json(array('success' => false, 'message' => $exception->getMessage()), api_exception_status($exception, 500));
}
