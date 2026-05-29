<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Core\Request;
use App\Core\Response;
use App\Core\Rbac;
use App\Repositories\WilayahRepository;

$repository = new WilayahRepository();

try {
    Rbac::authorize('wilayah.manage');

    $type = (string) Request::query('type', 'summary');
    if ($type === 'provinces') {
        Response::json(array('success' => true, 'data' => $repository->provinces()));
    }
    if ($type === 'cities') {
        Response::json(array('success' => true, 'data' => $repository->cities((int) Request::query('province_id', 0))));
    }
    if ($type === 'districts') {
        Response::json(array('success' => true, 'data' => $repository->districts((int) Request::query('city_id', 0))));
    }
    if ($type === 'villages') {
        Response::json(array('success' => true, 'data' => $repository->villages((int) Request::query('district_id', 0))));
    }

    Response::json(array('success' => true, 'data' => $repository->flatSummary()));
} catch (\Throwable $exception) {
    Response::json(array('success' => false, 'message' => $exception->getMessage()), api_exception_status($exception, 500));
}
