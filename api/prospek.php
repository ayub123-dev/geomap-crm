<?php

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Auth;
use App\Core\Response;
use App\Core\Request;
use App\Services\ProspekService;

// Validasi akses
if (!Auth::check() || !in_array(Auth::user()['role'], ['admin', 'supervisor', 'salesman'])) {
    Response::json([
        'success' => false,
        'message' => 'Unauthorized'
    ], 401);
    exit;
}

$request = new Request();
$method = $request->method();
$prospekService = new ProspekService();

// Extract ID dari URL: /api/prospek/{id}
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($uri, '/'));
$prospek_id = isset($parts[2]) && is_numeric($parts[2]) ? (int)$parts[2] : null;

if ($method === 'GET') {
    if ($prospek_id) {
        // GET /api/prospek/{id}
        $prospek = $prospekService->getDetailProspek($prospek_id);
        
        if (!$prospek) {
            Response::json([
                'success' => false,
                'message' => 'Prospek not found'
            ], 404);
            exit;
        }

        Response::json([
            'success' => true,
            'data' => $prospek
        ]);
    } else {
        // GET /api/prospek (list dengan filter)
        $filters = [];
        
        if ($request->query('status')) {
            $filters['status'] = $request->query('status');
        }
        
        if ($request->query('prioritas')) {
            $filters['prioritas'] = $request->query('prioritas');
        }

        // Jika salesman, hanya lihat prospek mereka
        if (Auth::user()['role'] === 'salesman') {
            $filters['salesman_id'] = Auth::user()['id'];
        }

        $data = $prospekService->getProspekList($filters);

        Response::json([
            'success' => true,
            'data' => $data,
            'filters' => $filters
        ]);
    }
    exit;

} elseif ($method === 'PUT') {
    // PUT /api/prospek/{id}
    
    if (!$prospek_id) {
        Response::json([
            'success' => false,
            'message' => 'Prospek ID is required'
        ], 400);
        exit;
    }

    $prospek = $prospekService->getDetailProspek($prospek_id);
    if (!$prospek) {
        Response::json([
            'success' => false,
            'message' => 'Prospek not found'
        ], 404);
        exit;
    }

    // Validasi ownership jika salesman
    if (Auth::user()['role'] === 'salesman' && $prospek['salesman_id'] !== Auth::user()['id']) {
        Response::json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 403);
        exit;
    }

    $data = $request->all();
    
    // Jika ada perubahan status
    if (isset($data['status'])) {
        $result = $prospekService->updateStatus($prospek_id, $data['status']);
        Response::json($result);
    } else {
        // Update data prospek
        $result = $prospekService->updateProspek($prospek_id, $data);
        Response::json([
            'success' => $result,
            'message' => $result ? 'Prospek updated successfully' : 'Failed to update prospek'
        ]);
    }
    exit;

} elseif ($method === 'DELETE') {
    // DELETE /api/prospek/{id} (soft delete - change status to inactive)
    
    if (!$prospek_id) {
        Response::json([
            'success' => false,
            'message' => 'Prospek ID is required'
        ], 400);
        exit;
    }

    $prospek = $prospekService->getDetailProspek($prospek_id);
    if (!$prospek) {
        Response::json([
            'success' => false,
            'message' => 'Prospek not found'
        ], 404);
        exit;
    }

    // Hanya admin/supervisor/pemilik yang bisa delete
    if (Auth::user()['role'] === 'salesman' && $prospek['salesman_id'] !== Auth::user()['id']) {
        Response::json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 403);
        exit;
    }

    $result = $prospekService->updateProspek($prospek_id, ['status' => 'inactive']);

    Response::json([
        'success' => $result,
        'message' => $result ? 'Prospek deleted successfully' : 'Failed to delete prospek'
    ]);
    exit;
}

Response::json([
    'success' => false,
    'message' => 'Method not allowed'
], 405);
