<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Core\Request;
use App\Core\Response;
use App\Core\Rbac;
use App\Services\CustomerService;

$service = new CustomerService();
$method = Request::method();
$id = (int) Request::query('id', 0);

if ($id <= 0) {
    Response::json(
        array(
            'success' => false,
            'message' => 'Parameter "id" wajib diisi.',
        ),
        422
    );
}

try {
    if ($method === 'GET') {
        Rbac::authorize('customer_inti.view');
        $customer = $service->getCustomer($id);
        if (!$customer) {
            Response::json(
                array(
                    'success' => false,
                    'message' => 'Customer tidak ditemukan.',
                ),
                404
            );
        }

        Response::json(
            array(
                'success' => true,
                'message' => 'Detail customer berhasil diambil.',
                'data' => $customer,
            )
        );
    }

    if ($method === 'PUT') {
        Rbac::authorize('customer_inti.update');
        $payload = Request::data();
        $customer = $service->updateCustomer($id, $payload);
        if (!$customer) {
            Response::json(
                array(
                    'success' => false,
                    'message' => 'Customer tidak ditemukan.',
                ),
                404
            );
        }

        Response::json(
            array(
                'success' => true,
                'message' => 'Customer berhasil diperbarui.',
                'data' => $customer,
            )
        );
    }

    if ($method === 'DELETE') {
        Rbac::authorize('customer_inti.delete');
        $deleted = $service->deleteCustomer($id);
        Response::json(
            array(
                'success' => (bool) $deleted,
                'message' => $deleted ? 'Customer berhasil dihapus (soft delete).' : 'Customer tidak dapat dihapus.',
            )
        );
    }

    Response::json(
        array(
            'success' => false,
            'message' => 'Method tidak didukung untuk endpoint ini.',
        ),
        405
    );
} catch (\InvalidArgumentException $exception) {
    Response::json(
        array(
            'success' => false,
            'message' => $exception->getMessage(),
        ),
        422
    );
} catch (\Throwable $exception) {
    Response::json(
        array(
            'success' => false,
            'message' => 'Terjadi kesalahan saat memproses permintaan.',
            'error' => $exception->getMessage(),
        ),
        api_exception_status($exception, 500)
    );
}
