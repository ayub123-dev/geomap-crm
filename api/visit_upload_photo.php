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

if ($method === 'POST') {
    // POST /api/visit/upload-photo
    
    $kunjungan_id = $request->input('kunjungan_id');
    if (!$kunjungan_id) {
        Response::json([
            'success' => false,
            'message' => 'kunjungan_id is required'
        ], 400);
        exit;
    }

    // Cek apakah kunjungan ada dan milik salesman ini
    $kunjungan = $kunjunganService->getDetailKunjungan($kunjungan_id);
    if (!$kunjungan || $kunjungan['salesman_id'] !== Auth::user()['id']) {
        Response::json([
            'success' => false,
            'message' => 'Kunjungan not found or unauthorized'
        ], 404);
        exit;
    }

    // Handle file upload
    if (!isset($_FILES['photo'])) {
        Response::json([
            'success' => false,
            'message' => 'No file uploaded'
        ], 400);
        exit;
    }

    $file = $_FILES['photo'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowed_types)) {
        Response::json([
            'success' => false,
            'message' => 'Invalid file type. Only JPG, PNG, WebP allowed'
        ], 400);
        exit;
    }

    if ($file['size'] > $max_size) {
        Response::json([
            'success' => false,
            'message' => 'File size exceeds 5MB limit'
        ], 400);
        exit;
    }

    // Buat direktori jika belum ada
    $upload_dir = __DIR__ . '/../assets/uploads/kunjungan/' . date('Y/m');
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generate nama file unik
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'visit_' . $kunjungan_id . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . '/' . $filename;

    // Move file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        Response::json([
            'success' => false,
            'message' => 'Failed to upload file'
        ], 500);
        exit;
    }

    // Simpan path ke database
    $relative_path = 'assets/uploads/kunjungan/' . date('Y/m') . '/' . $filename;
    
    $kunjunganService->updateKunjungan($kunjungan_id, [
        'foto_toko_path' => $relative_path
    ]);

    Response::json([
        'success' => true,
        'message' => 'Photo uploaded successfully',
        'photo_path' => $relative_path
    ]);
    exit;
}

Response::json([
    'success' => false,
    'message' => 'Method not allowed'
], 405);
