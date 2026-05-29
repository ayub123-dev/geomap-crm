<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Core\ImportExport;
use App\Core\Request;
use App\Core\Response;
use App\Core\Rbac;
use App\Services\CustomerExistingService;

$service = new CustomerExistingService();

try {
    Rbac::authorize('import_export.manage');

    $format = strtolower((string) Request::query('format', 'excel'));
    $rows = $service->list((string) Request::query('search', ''), 100000, 0);

    $headers = array(
        'kode_existing',
        'nama_toko',
        'brand_kompetitor',
        'alamat',
        'lat',
        'lng',
        'sumber_data',
        'catatan',
    );

    if ($format === 'pdf') {
        if (class_exists('\\Dompdf\\Dompdf')) {
            $html = '<h2>GeoMap CRM - Export Customer Existing</h2><table border="1" cellspacing="0" cellpadding="4"><tr>';
            foreach ($headers as $header) {
                $html .= '<th>' . htmlspecialchars($header) . '</th>';
            }
            $html .= '</tr>';
            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ($headers as $header) {
                    $html .= '<td>' . htmlspecialchars((string) ($row[$header] ?? '')) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</table>';

            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="customer_existing_' . date('Ymd_His') . '.pdf"');
            echo $dompdf->output();
            exit;
        }

        Response::json(array(
            'success' => false,
            'message' => 'Dompdf belum tersedia. Jalankan: composer require dompdf/dompdf',
        ), 422);
    }

    if (class_exists('\\PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $columnIndex = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($columnIndex, 1, $header);
            $columnIndex++;
        }

        $rowNumber = 2;
        foreach ($rows as $row) {
            $columnIndex = 1;
            foreach ($headers as $header) {
                $sheet->setCellValueByColumnAndRow($columnIndex, $rowNumber, (string) ($row[$header] ?? ''));
                $columnIndex++;
            }
            $rowNumber++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="customer_existing_' . date('Ymd_His') . '.xlsx"');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    ImportExport::downloadCsv('customer_existing_' . date('Ymd_His') . '.csv', $headers, $rows);
} catch (\Throwable $exception) {
    Response::json(array('success' => false, 'message' => $exception->getMessage()), api_exception_status($exception, 500));
}
