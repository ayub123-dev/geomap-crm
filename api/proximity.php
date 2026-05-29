<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Core\Database;
use App\Core\Response;
use App\Core\Request;
use App\Core\Rbac;

try {
    if (Request::method() !== 'GET') {
        Response::json(array('success' => false, 'message' => 'Method tidak didukung.'), 405);
    }

    Rbac::authorize('laporan.view');

    $radius = (int) (Request::query('radius', 100));
    $radius = max(1, min(5000, $radius));

    $pdo = Database::connection();

    $intiStmt = $pdo->query("SELECT id, nama_toko, alamat, lat, lng, salesman_id FROM customer_inti WHERE deleted_at IS NULL AND lat IS NOT NULL AND lng IS NOT NULL");
    $existingStmt = $pdo->query("SELECT id, nama_toko, alamat, lat, lng FROM customer_existing WHERE lat IS NOT NULL AND lng IS NOT NULL");

    $intis = $intiStmt->fetchAll();
    $existings = $existingStmt->fetchAll();

    $pairs = array();

    function haversine($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * pow(sin($lngDelta / 2), 2)
        ));
        return $earthRadius * $angle;
    }

    foreach ($intis as $inti) {
        foreach ($existings as $ex) {
            $d = haversine((float) $inti['lat'], (float) $inti['lng'], (float) $ex['lat'], (float) $ex['lng']);
            if ($d <= $radius) {
                $pairs[] = array(
                    'customer_inti_id' => (int) $inti['id'],
                    'customer_inti_name' => $inti['nama_toko'],
                    'customer_inti_address' => $inti['alamat'],
                    'customer_inti_lat' => (float) $inti['lat'],
                    'customer_inti_lng' => (float) $inti['lng'],
                    'salesman_id' => isset($inti['salesman_id']) ? (int) $inti['salesman_id'] : null,
                    'customer_existing_id' => (int) $ex['id'],
                    'customer_existing_name' => $ex['nama_toko'],
                    'customer_existing_address' => $ex['alamat'],
                    'customer_existing_lat' => (float) $ex['lat'],
                    'customer_existing_lng' => (float) $ex['lng'],
                    'distance_meters' => round($d, 2),
                );
            }
        }
    }

    Response::json(array('success' => true, 'message' => 'Proximity pairs retrieved.', 'data' => array('radius' => $radius, 'total_pairs' => count($pairs), 'pairs' => $pairs)));
} catch (\Throwable $e) {
    Response::json(array('success' => false, 'message' => $e->getMessage()), api_exception_status($e, 500));
}
