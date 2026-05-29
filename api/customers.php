<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Core\Request;
use App\Core\Response;
use App\Core\Rbac;
use App\Services\CustomerService;

$service = new CustomerService();
$method = Request::method();

try {
    if ($method === 'GET') {
        Rbac::authorize('customer_inti.view');
        $search = (string) Request::query('search', '');
        $limit = (int) Request::query('limit', 50);
        $offset = (int) Request::query('offset', 0);

        $data = $service->listCustomers($search, $limit, $offset);
        $total = $service->countCustomers($search);

        Response::json(
            array(
                'success' => true,
                'message' => 'Data customer berhasil diambil.',
                'data' => $data,
                'meta' => array(
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset,
                    'search' => $search,
                ),
            )
        );
    }

    if ($method === 'POST') {
        Rbac::authorize('customer_inti.create');
        $payload = Request::data();
        $customer = $service->createCustomer($payload);

        Response::json(
            array(
                'success' => true,
                'message' => 'Customer berhasil ditambahkan.',
                'data' => $customer,
            ),
            201
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
