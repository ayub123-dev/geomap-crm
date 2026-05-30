<?php

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Response;
use App\Core\Request;
use App\Services\KunjunganService;

// Validasi akses
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

if ($method === 'GET') {
    // GET /api/salesman/targets?date=YYYY-MM-DD
    
    $date = $request->query('date') ?? date('Y-m-d');
    $salesman_id = Auth::user()['id'];

    // Validasi format tanggal
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        Response::json([
            'success' => false,
            'message' => 'Invalid date format. Use YYYY-MM-DD'
        ], 400);
        exit;
    }

    $targets = $kunjunganService->getTargetHariIni($salesman_id);
    
    // Filter jika ada tanggal tertentu (yang bukan hari ini)
    // Untuk MVP, kita fokus ke hari ini
    
    $summary = $kunjunganService->getStatusSummary($salesman_id, $date);

    Response::json([
        'success' => true,
        'date' => $date,
        'targets' => $targets,
        'summary' => $summary,
        'total_target' => count($targets),
        'total_completed' => array_sum(array_map(function($s) { 
            return $s['status'] === 'checkout' ? $s['jumlah'] : 0; 
        }, $summary ?? []))
    ]);
    exit;
}

Response::json([
    'success' => false,
    'message' => 'Method not allowed'
], 405);
