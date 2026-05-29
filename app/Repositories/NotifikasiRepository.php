<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class NotifikasiRepository
{
    private $pdo;
    private $driver;

    public function __construct()
    {
        $this->pdo = Database::connection();
        $this->driver = Database::driver();
    }

    public function listAll($limit = 100)
    {
        $limit = max(1, min(1000, (int) $limit));
        if ($this->driver === 'sqlsrv') {
            return $this->pdo->query(
                'SELECT TOP ' . (int) $limit . ' id, user_id, title, message, channel, status, created_at
                 FROM notifikasi
                 ORDER BY created_at DESC'
            )->fetchAll();
        }

        $statement = $this->pdo->prepare(
            'SELECT id, user_id, title, message, channel, status, created_at
             FROM notifikasi
             ORDER BY created_at DESC
             LIMIT :limit'
        );
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function create(array $data)
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO notifikasi (user_id, title, message, channel, status, created_at)
             VALUES (:user_id, :title, :message, :channel, :status, :created_at)'
        );
        if ($data['user_id'] === null || $data['user_id'] === '') {
            $statement->bindValue(':user_id', null, PDO::PARAM_NULL);
        } else {
            $statement->bindValue(':user_id', (int) $data['user_id'], PDO::PARAM_INT);
        }
        $statement->bindValue(':title', (string) $data['title'], PDO::PARAM_STR);
        $statement->bindValue(':message', (string) $data['message'], PDO::PARAM_STR);
        $statement->bindValue(':channel', (string) $data['channel'], PDO::PARAM_STR);
        $statement->bindValue(':status', (string) $data['status'], PDO::PARAM_STR);
        $statement->bindValue(':created_at', (string) $data['created_at'], PDO::PARAM_STR);
        $statement->execute();
        return (int) $this->pdo->lastInsertId();
    }
}
