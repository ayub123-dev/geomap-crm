<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Core\Request;
use App\Core\Response;
use App\Core\Rbac;
use App\Services\SalesmanService;

$service = new SalesmanService();
$salesmanId = (int) Request::query('salesman_id', 0);
$year = (int) Request::query('tahun', date('Y'));
$month = (int) Request::query('bulan', date('n'));

if ($salesmanId <= 0) {
    Response::json(array('success' => false, 'message' => 'Parameter salesman_id wajib diisi.'), 422);
}

try {
    Rbac::authorize('salesman.view');
    $data = $service->dashboard($salesmanId, $year, $month);
    Response::json(array(
        'success' => true,
        'message' => 'Dashboard salesman berhasil diambil.',
        'data' => $data,
    ));
} catch (\Throwable $exception) {
    Response::json(array('success' => false, 'message' => $exception->getMessage()), api_exception_status($exception, 500));
}
