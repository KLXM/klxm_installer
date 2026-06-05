<?php

declare(strict_types=1);

namespace Klxm\Installer\Services;

use DateTimeImmutable;
use Klxm\Installer\Support\Config;
use Klxm\Installer\Support\Database;

final class TokenService
{
    public function createUserToken(int $userId, string $label = 'Standard'): string
    {
        $token = 'klxm_' . bin2hex(random_bytes(24));
        $tokenHash = hash('sha256', $token);

        $minutes = (int) Config::get('security.token_ttl_minutes', 525600);
        $expiresAt = (new DateTimeImmutable('+' . $minutes . ' minutes'))->format('Y-m-d H:i:s');

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('INSERT INTO api_tokens (user_id, label, token_hash, expires_at, created_at, updated_at) VALUES (:user_id, :label, :token_hash, :expires_at, NOW(), NOW())');
        $stmt->execute([
            ':user_id' => $userId,
            ':label' => $label,
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt,
        ]);

        return $token;
    }

    public function rotateUserToken(int $userId, int $tokenId): string
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id FROM api_tokens WHERE id = :id AND user_id = :user_id LIMIT 1');
        $stmt->execute([':id' => $tokenId, ':user_id' => $userId]);
        $existing = $stmt->fetch();

        if (!is_array($existing)) {
            throw new \RuntimeException('Token nicht gefunden.');
        }

        $newToken = 'klxm_' . bin2hex(random_bytes(24));
        $newHash = hash('sha256', $newToken);
        $minutes = (int) Config::get('security.token_ttl_minutes', 525600);
        $expiresAt = (new DateTimeImmutable('+' . $minutes . ' minutes'))->format('Y-m-d H:i:s');

        $update = $pdo->prepare('UPDATE api_tokens SET token_hash = :token_hash, expires_at = :expires_at, updated_at = NOW() WHERE id = :id');
        $update->execute([
            ':token_hash' => $newHash,
            ':expires_at' => $expiresAt,
            ':id' => $tokenId,
        ]);

        return $newToken;
    }

    public function verifyBearerToken(string $token): ?array
    {
        $hash = hash('sha256', $token);
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT t.id as token_id, t.user_id, u.email, u.display_name, u.role, u.customer_type, u.is_active
            FROM api_tokens t
            INNER JOIN users u ON u.id = t.user_id
            WHERE t.token_hash = :token_hash
              AND t.expires_at > NOW()
              AND u.is_active = 1
            LIMIT 1');
        $stmt->execute([':token_hash' => $hash]);
        $row = $stmt->fetch();

        if (!is_array($row)) {
            return null;
        }

        $upd = $pdo->prepare('UPDATE api_tokens SET last_used_at = NOW(), updated_at = NOW() WHERE id = :id');
        $upd->execute([':id' => (int) $row['token_id']]);

        return $row;
    }
}
