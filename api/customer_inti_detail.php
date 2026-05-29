<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Rbac;
use App\Services\CustomerIntiService;

$service = new CustomerIntiService();
$method = Request::method();
$id = (int) Request::query('id', 0);
$user = Auth::currentUser();
$userId = $user['id'] ?? null;

if ($id <= 0) {
    Response::json(array('success' => false, 'message' => 'Parameter id wajib diisi.'), 422);
}

try {
    if ($method === 'GET') {
        Rbac::authorize('customer_inti.view');
        $result = $service->detail($id);
        if (!$result) {
            Response::json(array('success' => false, 'message' => 'Data tidak ditemukan.'), 404);
        }

        Response::json(array('success' => true, 'message' => 'Detail customer inti.', 'data' => $result));
    }

    if ($method === 'PUT') {
        Rbac::authorize('customer_inti.update');
        $payload = Request::data();
        $result = $service->update($id, $payload, $userId);
        if (!$result) {
            Response::json(array('success' => false, 'message' => 'Data tidak ditemukan.'), 404);
        }

        Response::json(array('success' => true, 'message' => 'Customer inti berhasil diperbarui.', 'data' => $result));
    }

    if ($method === 'DELETE') {
        Rbac::authorize('customer_inti.delete');
        $deleted = $service->delete($id, $userId);
        Response::json(array('success' => $deleted, 'message' => $deleted ? 'Customer inti dihapus.' : 'Gagal menghapus data.'));
    }

    Response::json(array('success' => false, 'message' => 'Method tidak didukung.'), 405);
} catch (\Throwable $exception) {
    Response::json(array(
        'success' => false,
        'message' => $exception->getMessage(),
    ), api_exception_status($exception, 500));
}
