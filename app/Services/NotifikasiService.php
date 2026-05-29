<?php

namespace App\Services;

use App\Repositories\AuditLogRepository;
use App\Repositories\NotifikasiRepository;
use InvalidArgumentException;

class NotifikasiService
{
    private $repository;
    private $audit;

    public function __construct()
    {
        $this->repository = new NotifikasiRepository();
        $this->audit = new AuditLogRepository();
    }

    public function list($limit = 100)
    {
        return $this->repository->listAll($limit);
    }

    public function create(array $payload, $actorUserId = null)
    {
        $title = trim((string) ($payload['title'] ?? ''));
        $message = trim((string) ($payload['message'] ?? ''));
        if ($title === '' || $message === '') {
            throw new InvalidArgumentException('title dan message wajib diisi.');
        }

        $channel = trim((string) ($payload['channel'] ?? 'in-app'));
        if (!in_array($channel, array('in-app', 'whatsapp'), true)) {
            $channel = 'in-app';
        }

        $status = trim((string) ($payload['status'] ?? 'queued'));
        if (!in_array($status, array('draft', 'queued', 'sent', 'failed'), true)) {
            $status = 'queued';
        }

        $id = $this->repository->create(array(
            'user_id' => $payload['user_id'] ?? null,
            'title' => $title,
            'message' => $message,
            'channel' => $channel,
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s'),
        ));

        $this->audit->log($actorUserId, 'notifikasi', 'create', null, null, json_encode(array(
            'id' => $id,
            'title' => $title,
            'channel' => $channel,
            'status' => $status,
        )));

        return $id;
    }
}
