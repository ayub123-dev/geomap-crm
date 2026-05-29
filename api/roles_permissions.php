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
        $roleId = (int) Request::query('role_id', 0);
        if ($roleId <= 0) {
            Response::json(array(
                'success' => true,
                'roles' => $service->listRoles(),
                'permissions' => $service->listPermissions(),
            ));
        }

        Response::json(array(
            'success' => true,
            'role_id' => $roleId,
            'permissions' => $service->rolePermissions($roleId),
        ));
    }

    if ($method === 'POST') {
        Rbac::authorize('users.manage');
        $payload = Request::data();
        $roleId = (int) ($payload['role_id'] ?? 0);
        if ($roleId <= 0) {
            Response::json(array('success' => false, 'message' => 'role_id wajib diisi.'), 422);
        }

        $permissionIds = $payload['permission_ids'] ?? array();
        if (!is_array($permissionIds)) {
            $permissionIds = array($permissionIds);
        }
        $service->syncRolePermissions($roleId, array_map('intval', $permissionIds), $actorId);
        Response::json(array('success' => true, 'message' => 'Permission role berhasil diperbarui.'));
    }

    Response::json(array('success' => false, 'message' => 'Method tidak didukung.'), 405);
} catch (\Throwable $exception) {
    Response::json(array('success' => false, 'message' => $exception->getMessage()), api_exception_status($exception, 500));
}
