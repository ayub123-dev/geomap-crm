<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Rbac;
use App\Services\TargetRealisasiService;

$service = new TargetRealisasiService();
$method = Request::method();
$user = Auth::currentUser();
$userId = $user['id'] ?? null;

try {
    if ($method === 'GET') {
        Rbac::authorize('target_realisasi.manage');
        $year = (int) Request::query('tahun', date('Y'));
        $month = (int) Request::query('bulan', date('n'));
        Response::json(array(
            'success' => true,
            'data' => $service->list($year, $month),
        ));
    }

    if ($method === 'POST') {
        Rbac::authorize('target_realisasi.manage');
        $payload = Request::data();
        $id = $service->save($payload, $userId);
        Response::json(array(
            'success' => true,
            'message' => 'Target realisasi berhasil disimpan.',
            'data' => array('id' => $id),
        ));
    }

    Response::json(array('success' => false, 'message' => 'Method tidak didukung.'), 405);
} catch (\Throwable $exception) {
    Response::json(array('success' => false, 'message' => $exception->getMessage()), api_exception_status($exception, 500));
}
