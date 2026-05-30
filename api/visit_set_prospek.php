<?php

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Response;
use App\Core\Request;
use App\Services\KunjunganService;
use App\Services\ProspekService;

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
$prospekService = new ProspekService();

// Extract ID dari URL: /api/visit/{id}/set-prospek
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($uri, '/'));
$kunjungan_id = isset($parts[2]) ? (int)$parts[2] : null;

if (!$kunjungan_id) {
    Response::json([
        'success' => false,
        'message' => 'Visit ID is required'
    ], 400);
    exit;
}

// Validasi kunjungan milik salesman
$kunjungan = $kunjunganService->getDetailKunjungan($kunjungan_id);
if (!$kunjungan || $kunjungan['salesman_id'] !== Auth::user()['id']) {
    Response::json([
        'success' => false,
        'message' => 'Kunjungan not found or unauthorized'
    ], 404);
    exit;
}

if ($method === 'PUT') {
    // PUT /api/visit/{id}/set-prospek
    
    $data = $request->all();
    
    // Data minimal yang harus ada
    if (empty($data['nama_toko'])) {
        Response::json([
            'success' => false,
            'message' => 'nama_toko is required'
        ], 400);
        exit;
    }

    // Default dari kunjungan jika tidak ada
    $prospek_data = [
        'nama_toko' => $data['nama_toko'],
        'alamat' => $data['alamat'] ?? $kunjungan['address'] ?? '',
        'latitude' => $data['latitude'] ?? $kunjungan['gps_latitude'],
        'longitude' => $data['longitude'] ?? $kunjungan['gps_longitude'],
        'estimasi_omzet' => $data['estimasi_omzet'] ?? null,
        'kategori_produk' => $data['kategori_produk'] ?? null,
        'prioritas' => $data['prioritas'] ?? 'C',
        'foto_toko_path' => $data['foto_toko_path'] ?? $kunjungan['foto_toko_path'],
        'catatan' => $data['catatan'] ?? null
    ];

    $result = $prospekService->createProspekFromKunjungan($kunjungan_id, $prospek_data);

    Response::json($result);
    exit;
}

Response::json([
    'success' => false,
    'message' => 'Method not allowed'
], 405);
