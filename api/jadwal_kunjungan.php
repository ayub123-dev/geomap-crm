<?php

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Response;
use App\Core\Request;
use App\Repositories\JadwalKunjunganRepository;

// Validasi akses (supervisor/admin atau salesman sendiri)
if (!Auth::check() || !in_array(Auth::user()['role'], ['admin', 'supervisor', 'salesman'])) {
    Response::json([
        'success' => false,
        'message' => 'Unauthorized'
    ], 401);
    exit;
}

$request = new Request();
$method = $request->method();
$jadwalRepo = new JadwalKunjunganRepository();

// Extract ID dari URL jika ada: /api/jadwal-kunjungan/{id}
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($uri, '/'));
$jadwal_id = isset($parts[2]) && is_numeric($parts[2]) ? (int)$parts[2] : null;

if ($method === 'GET') {
    // GET /api/jadwal-kunjungan?salesman_id=X&hari=Senin
    
    $salesman_id = $request->query('salesman_id');
    $hari = $request->query('hari');
    
    if (!$salesman_id) {
        Response::json([
            'success' => false,
            'message' => 'salesman_id is required'
        ], 400);
        exit;
    }

    if ($hari) {
        // Get jadwal untuk hari tertentu
        $data = $jadwalRepo->getByHari($salesman_id, $hari);
    } else {
        // Get semua jadwal untuk salesman
        $data = $jadwalRepo->getBySalesmanId($salesman_id);
    }

    Response::json([
        'success' => true,
        'data' => $data,
        'hari' => $hari
    ]);
    exit;

} elseif ($method === 'POST') {
    // POST /api/jadwal-kunjungan (create baru)
    
    // Hanya admin/supervisor yang bisa create
    if (!in_array(Auth::user()['role'], ['admin', 'supervisor'])) {
        Response::json([
            'success' => false,
            'message' => 'Only admin/supervisor can create schedules'
        ], 403);
        exit;
    }

    $data = $request->all();
    
    $required = ['salesman_id', 'customer_id', 'hari_dalam_minggu'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            Response::json([
                'success' => false,
                'message' => "{$field} is required"
            ], 400);
            exit;
        }
    }

    $jadwal_data = [
        'salesman_id' => (int)$data['salesman_id'],
        'customer_id' => (int)$data['customer_id'],
        'hari_dalam_minggu' => $data['hari_dalam_minggu'],
        'minggu_ke' => isset($data['minggu_ke']) ? (int)$data['minggu_ke'] : null,
        'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1
    ];

    $result = $jadwalRepo->create($jadwal_data);

    if (!$result) {
        Response::json([
            'success' => false,
            'message' => 'Failed to create schedule'
        ], 500);
        exit;
    }

    Response::json([
        'success' => true,
        'message' => 'Schedule created successfully'
    ]);
    exit;

} elseif ($method === 'PUT') {
    // PUT /api/jadwal-kunjungan/{id} (update)
    
    if (!$jadwal_id) {
        Response::json([
            'success' => false,
            'message' => 'Jadwal ID is required'
        ], 400);
        exit;
    }

    $jadwal = $jadwalRepo->findById($jadwal_id);
    if (!$jadwal) {
        Response::json([
            'success' => false,
            'message' => 'Schedule not found'
        ], 404);
        exit;
    }

    $data = $request->all();
    
    $update_data = [];
    $allowed_fields = ['hari_dalam_minggu', 'minggu_ke', 'is_active'];
    
    foreach ($allowed_fields as $field) {
        if (isset($data[$field])) {
            $update_data[$field] = $data[$field];
        }
    }

    if (empty($update_data)) {
        Response::json([
            'success' => false,
            'message' => 'No fields to update'
        ], 400);
        exit;
    }

    $result = $jadwalRepo->update($jadwal_id, $update_data);

    Response::json([
        'success' => $result,
        'message' => $result ? 'Schedule updated successfully' : 'Failed to update schedule'
    ]);
    exit;

} elseif ($method === 'DELETE') {
    // DELETE /api/jadwal-kunjungan/{id} (deactivate)
    
    // Hanya admin/supervisor yang bisa delete
    if (!in_array(Auth::user()['role'], ['admin', 'supervisor'])) {
        Response::json([
            'success' => false,
            'message' => 'Only admin/supervisor can delete schedules'
        ], 403);
        exit;
    }

    if (!$jadwal_id) {
        Response::json([
            'success' => false,
            'message' => 'Jadwal ID is required'
        ], 400);
        exit;
    }

    $result = $jadwalRepo->deactivate($jadwal_id);

    Response::json([
        'success' => $result,
        'message' => $result ? 'Schedule deleted successfully' : 'Failed to delete schedule'
    ]);
    exit;
}

Response::json([
    'success' => false,
    'message' => 'Method not allowed'
], 405);
