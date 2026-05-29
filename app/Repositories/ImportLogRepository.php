<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class ImportLogRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function create($moduleName, $fileName, $userId = null)
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO import_log (module_name, file_name, status, total_rows, success_rows, failed_rows, created_by, created_at)
             VALUES (:module_name, :file_name, :status, :total_rows, :success_rows, :failed_rows, :created_by, :created_at)'
        );
        $statement->execute(array(
            ':module_name' => $moduleName,
            ':file_name' => $fileName,
            ':status' => 'processing',
            ':total_rows' => 0,
            ':success_rows' => 0,
            ':failed_rows' => 0,
            ':created_by' => $userId,
            ':created_at' => date('Y-m-d H:i:s'),
        ));

        return (int) $this->pdo->lastInsertId();
    }

    public function complete($id, $totalRows, $successRows, $failedRows, $status = 'completed', $errorMessage = null)
    {
        $statement = $this->pdo->prepare(
            'UPDATE import_log
             SET total_rows = :total_rows,
                 success_rows = :success_rows,
                 failed_rows = :failed_rows,
                 status = :status,
                 error_message = :error_message
             WHERE id = :id'
        );
        $statement->bindValue(':total_rows', (int) $totalRows, PDO::PARAM_INT);
        $statement->bindValue(':success_rows', (int) $successRows, PDO::PARAM_INT);
        $statement->bindValue(':failed_rows', (int) $failedRows, PDO::PARAM_INT);
        $statement->bindValue(':status', $status, PDO::PARAM_STR);
        $statement->bindValue(':error_message', $errorMessage, $errorMessage === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $statement->execute();
    }

    public function latest($limit = 50)
    {
        $driver = Database::driver();
        $limit = max(1, min(500, (int) $limit));

        if ($driver === 'sqlsrv') {
            $statement = $this->pdo->query(
                'SELECT TOP ' . (int) $limit . ' id, module_name, file_name, total_rows, success_rows, failed_rows, status, error_message, created_by, created_at
                 FROM import_log
                 ORDER BY created_at DESC'
            );
            return $statement->fetchAll();
        }

        $statement = $this->pdo->prepare(
            'SELECT id, module_name, file_name, total_rows, success_rows, failed_rows, status, error_message, created_by, created_at
             FROM import_log
             ORDER BY created_at DESC
             LIMIT :limit'
        );
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }
}