<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\ImportExport;
use App\Core\Rbac;

try {
    Rbac::authorize('import_export.manage');

    $radius = (int) (Request::query('radius', 100));
    $format = strtolower((string) Request::query('format', 'excel'));
    $radius = max(1, min(5000, $radius));

    $pdo = Database::connection();
    $intiStmt = $pdo->query("SELECT id,kode_customer ,nama_toko, alamat, lat, lng, salesman_id FROM customer_inti WHERE deleted_at IS NULL AND lat IS NOT NULL AND lng IS NOT NULL");
    $existingStmt = $pdo->query("SELECT id, kode_existing, nama_toko, alamat, lat, lng FROM customer_existing WHERE lat IS NOT NULL AND lng IS NOT NULL");
    $intis = $intiStmt->fetchAll();
    $existings = $existingStmt->fetchAll();

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

    $rows = array();
    foreach ($intis as $inti) {
        foreach ($existings as $ex) {
            $d = haversine((float) $inti['lat'], (float) $inti['lng'], (float) $ex['lat'], (float) $ex['lng']);
            if ($d <= $radius) {
                $rows[] = array(
                    'customer_inti_kode_customer' => $inti['kode_customer'],
                    'customer_inti_name' => $inti['nama_toko'],
                    'customer_inti_address' => $inti['alamat'],
                    'customer_inti_lat' => (float) $inti['lat'],
                    'customer_inti_lng' => (float) $inti['lng'],
                    'salesman_id' => isset($inti['salesman_id']) ? (int) $inti['salesman_id'] : null,
                    'customer_existing_kode_existing' => $ex['kode_existing'],
                    'customer_existing_name' => $ex['nama_toko'],
                    'customer_existing_address' => $ex['alamat'],
                    'customer_existing_lat' => (float) $ex['lat'],
                    'customer_existing_lng' => (float) $ex['lng'],
                    'distance_meters' => round($d, 2),
                );
            }
        }
    }

    $headers = array('customer_inti_kode_customer','customer_inti_name','customer_inti_address','customer_inti_lat','customer_inti_lng','salesman_id','customer_existing_kode_existing','customer_existing_name','customer_existing_address','customer_existing_lat','customer_existing_lng','distance_meters');

    if ($format === 'pdf') {
        if (class_exists('\Dompdf\Dompdf')) {
            $html = '<h2>Proximity Report (radius ' . htmlspecialchars((string)$radius) . ' m)</h2><table border="1" cellspacing="0" cellpadding="4"><tr>';
            foreach ($headers as $h) $html .= '<th>' . htmlspecialchars($h) . '</th>';
            $html .= '</tr>';
            foreach ($rows as $r) {
                $html .= '<tr>';
                foreach ($headers as $h) $html .= '<td>' . htmlspecialchars((string)($r[$h] ?? '')) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="proximity_' . date('Ymd_His') . '.pdf"');
            echo $dompdf->output();
            exit;
        }
        Response::json(array('success' => false, 'message' => 'Dompdf belum tersedia.'), 422);
    }

    if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $col = 1; foreach ($headers as $h) { $sheet->setCellValueByColumnAndRow($col, 1, $h); $col++; }
        $rowNum = 2;
        foreach ($rows as $r) {
            $col = 1; foreach ($headers as $h) { $sheet->setCellValueByColumnAndRow($col, $rowNum, (string)($r[$h] ?? '')); $col++; } $rowNum++; }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="proximity_' . date('Ymd_His') . '.xlsx"');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    ImportExport::downloadCsv('proximity_' . date('Ymd_His') . '.csv', $headers, $rows);

} catch (\Throwable $e) {
    Response::json(array('success' => false, 'message' => $e->getMessage()), api_exception_status($e, 500));
}
