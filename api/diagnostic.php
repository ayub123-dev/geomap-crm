<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Core\Response;
use App\Core\Rbac;
use App\Services\CustomerIntiService;
use App\Services\CustomerExistingService;
use App\Services\SalesmanService;

try {
    Rbac::authorize('users.manage');

    $intiService = new CustomerIntiService();
    $existingService = new CustomerExistingService();
    $salesmanService = new SalesmanService();

    $intiCount = $intiService->count();
    $existingCount = $existingService->count();
    $salesmanCount = $salesmanService->count();

    $intiSample = $intiService->list('', 3, 0);
    $existingSample = $existingService->list('', 3, 0);
    $salesmanSample = $salesmanService->list('', 3, 0);

    Response::json(array(
        'success' => true,
        'message' => 'Diagnostic data loaded',
        'counts' => array(
            'customer_inti_total' => $intiCount,
            'customer_existing_total' => $existingCount,
            'salesman_total' => $salesmanCount,
        ),
        'samples' => array(
            'customer_inti' => $intiSample,
            'customer_existing' => $existingSample,
            'salesman' => $salesmanSample,
        ),
    ));
} catch (\Throwable $e) {
    Response::json(array(
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ), api_exception_status($e, 500));
}
