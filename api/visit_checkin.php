<?php

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Response;
use App\Core\Request;
use App\Services\KunjunganService;

// Validasi akses (hanya salesman)
if (!Auth::check() || Auth::user()['role'] !== 'salesman') {
    Response::json([
        'success' => false,
        'message' => 'Unauthorized'
    ], 401);
    exit;
}

$request = new Request();
$method = $request->method();
$kunjunganService = new KunjunganService();

if ($method === 'POST') {
    // POST /api/visit/checkin
    $data = $request->all();
    
    $salesman_id = Auth::user()['id'];
    $customer_id = $data['customer_id'] ?? null;
    $gps_latitude = $data['gps_latitude'] ?? null;
    $gps_longitude = $data['gps_longitude'] ?? null;
    $customer_latitude = $data['customer_latitude'] ?? null;
    $customer_longitude = $data['customer_longitude'] ?? null;

    if (!$customer_id || $gps_latitude === null || $gps_longitude === null || 
        $customer_latitude === null || $customer_longitude === null) {
        Response::json([
            'success' => false,
            'message' => 'Missing required fields: customer_id, gps_latitude, gps_longitude, customer_latitude, customer_longitude'
        ], 400);
        exit;
    }

    $result = $kunjunganService->checkin(
        $salesman_id,
        $customer_id,
        (float)$gps_latitude,
        (float)$gps_longitude,
        (float)$customer_latitude,
        (float)$customer_longitude
    );

    Response::json($result);
    exit;

} elseif ($method === 'GET') {
    // GET /api/visit - Get daftar kunjungan
    $date = $request->query('date') ?? date('Y-m-d');
    $salesman_id = Auth::user()['id'];

    $kunjungan = $kunjunganService->getKunjunganByTanggal($salesman_id, $date);
    
    Response::json([
        'success' => true,
        'data' => $kunjungan,
        'date' => $date
    ]);
    exit;
}

Response::json([
    'success' => false,
    'message' => 'Method not allowed'
], 405);
