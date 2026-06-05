<?php

declare(strict_types=1);

namespace Klxm\Installer\Services;

use Klxm\Installer\Support\Database;

final class AuditLogService
{
    public function log(?int $userId, string $event, array $meta = []): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, event_name, ip_address, user_agent, meta_json, created_at) VALUES (:user_id, :event_name, :ip_address, :user_agent, :meta_json, NOW())');
        $stmt->execute([
            ':user_id' => $userId,
            ':event_name' => $event,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ':meta_json' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
}
