<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Core\Request;
use App\Core\Response;
use App\Core\Rbac;
use App\Services\CustomerService;

$service = new CustomerService();

try {
    if (Request::method() !== 'GET') {
        Response::json(
            array(
                'success' => false,
                'message' => 'Method tidak didukung untuk endpoint ini.',
            ),
            405
        );
    }

    Rbac::authorize('laporan.view');

    $markers = $service->listMarkers();

    Response::json(
        array(
            'success' => true,
            'message' => 'Data marker berhasil diambil.',
            'data' => $markers,
        )
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
