<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Core\Auth;
use App\Core\ImportExport;
use App\Core\Response;
use App\Core\Rbac;
use App\Repositories\ImportLogRepository;
use App\Services\CustomerIntiService;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    Response::json(array('success' => false, 'message' => 'Method tidak didukung.'), 405);
}

$service = new CustomerIntiService();
$importLog = new ImportLogRepository();
$user = Auth::currentUser();
$userId = $user['id'] ?? null;

try {
    Rbac::authorize('import_export.manage');

    if (empty($_FILES['file']['tmp_name'])) {
        Response::json(array('success' => false, 'message' => 'File import wajib diunggah.'), 422);
    }

    $mode = trim((string) ($_POST['mode'] ?? 'preview'));
    $execute = $mode === 'commit';

    $originalName = $_FILES['file']['name'] ?? ('import-' . date('YmdHis'));
    $logId = $importLog->create('customer_inti', $originalName, $userId);

    $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'geomap-import-' . uniqid('', true) . '-' . basename($originalName);
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $tempFile)) {
        $tempFile = $_FILES['file']['tmp_name'];
    }

    $rows = ImportExport::loadRowsFromFile($tempFile);
    $result = $service->importRows($rows, $userId, $execute);
    $summary = $result['summary'];

    $importLog->complete(
        $logId,
        $summary['total'],
        $summary['success'],
        $summary['failed'],
        $summary['failed'] > 0 ? 'completed' : 'completed',
        null
    );

    Response::json(array(
        'success' => true,
        'message' => $execute ? 'Import customer inti berhasil diproses.' : 'Preview import customer inti berhasil.',
        'data' => $result,
    ));
} catch (\Throwable $exception) {
    Response::json(array('success' => false, 'message' => $exception->getMessage()), api_exception_status($exception, 500));
}
