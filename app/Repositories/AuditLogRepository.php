<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class AuditLogRepository
{
    private $pdo;
    private $driver;

    public function __construct()
    {
        $this->pdo = Database::connection();
        $this->driver = Database::driver();
    }

    public function log($userId, $module, $action, $fieldName = null, $oldValue = null, $newValue = null)
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO audit_log (user_id, module_name, action_name, field_name, old_value, new_value, created_at)
             VALUES (:user_id, :module_name, :action_name, :field_name, :old_value, :new_value, :created_at)'
        );
        $statement->bindValue(':user_id', $userId ? (int) $userId : null, $userId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $statement->bindValue(':module_name', (string) $module, PDO::PARAM_STR);
        $statement->bindValue(':action_name', (string) $action, PDO::PARAM_STR);
        $statement->bindValue(':field_name', $fieldName, $fieldName === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $statement->bindValue(':old_value', $oldValue, $oldValue === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $statement->bindValue(':new_value', $newValue, $newValue === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $statement->bindValue(':created_at', date('Y-m-d H:i:s'), PDO::PARAM_STR);
        $statement->execute();
    }

    public function listAll($limit = 200)
    {
        $limit = max(1, min(2000, (int) $limit));
        if ($this->driver === 'sqlsrv') {
            $statement = $this->pdo->prepare(
                'SELECT TOP ' . (int) $limit . ' id, user_id, module_name, action_name, field_name, old_value, new_value, created_at
                 FROM audit_log
                 ORDER BY created_at DESC'
            );
        } else {
            $statement = $this->pdo->prepare(
                'SELECT id, user_id, module_name, action_name, field_name, old_value, new_value, created_at
                 FROM audit_log
                 ORDER BY created_at DESC
                 LIMIT :limit'
            );
            $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        }
        $statement->execute();
        return $statement->fetchAll();
    }
}
