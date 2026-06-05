<?php

declare(strict_types=1);

namespace Klxm\Installer\Services;

use Klxm\Installer\Support\Config;
use Klxm\Installer\Support\Database;

final class AuthService
{
    public function user(): ?array
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!is_int($userId) && !ctype_digit((string) $userId)) {
            return null;
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, email, display_name, role, customer_type, is_active, passkey_enabled FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => (int) $userId]);
        $user = $stmt->fetch();

        return is_array($user) ? $user : null;
    }

    public function isLoggedIn(): bool
    {
        return $this->user() !== null;
    }

    public function login(string $email, string $password): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, password_hash, is_active FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => mb_strtolower(trim($email))]);
        $row = $stmt->fetch();

        if (!is_array($row) || (int) $row['is_active'] !== 1) {
            return false;
        }

        if (!password_verify($password, (string) $row['password_hash'])) {
            return false;
        }

        $_SESSION['user_id'] = (int) $row['id'];
        $_SESSION['login_at'] = time();
        $_SESSION['login_expires'] = time() + ((int) Config::get('security.session_ttl_minutes', 120) * 60);
        session_regenerate_id(true);

        return true;
    }

    public function checkSessionFresh(): void
    {
        $expires = $_SESSION['login_expires'] ?? null;
        if ($expires !== null && time() > (int) $expires) {
            $this->logout();
        }
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
        session_start();
    }

    public function requireRole(string $role): bool
    {
        $user = $this->user();
        return is_array($user) && ($user['role'] ?? null) === $role;
    }
}
