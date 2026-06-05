<?php

declare(strict_types=1);

namespace Klxm\Installer\Services;

use DateInterval;
use DateTimeImmutable;
use Klxm\Installer\Support\Config;
use Klxm\Installer\Support\Database;

final class AuthService
{
    private ?string $lastError = null;

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
        $this->lastError = null;
        $email = mb_strtolower(trim($email));
        $ipAddress = $this->getClientIp();

        $this->ensureLoginAttemptsTable();

        $blockedUntil = $this->getBlockedUntil($email, $ipAddress);
        if ($blockedUntil instanceof DateTimeImmutable) {
            $this->lastError = 'Zu viele fehlgeschlagene Anmeldungen. Bitte versuche es ab ' . $blockedUntil->format('H:i') . ' erneut.';
            return false;
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, password_hash, is_active FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();

        if (!is_array($row) || (int) $row['is_active'] !== 1) {
            $this->registerFailedAttempt($email, $ipAddress);
            $this->lastError = 'Login fehlgeschlagen.';
            return false;
        }

        if (!password_verify($password, (string) $row['password_hash'])) {
            $this->registerFailedAttempt($email, $ipAddress);
            $this->lastError = 'Login fehlgeschlagen.';
            return false;
        }

        $this->clearFailedAttempts($email, $ipAddress);

        $_SESSION['user_id'] = (int) $row['id'];
        $_SESSION['login_at'] = time();
        $_SESSION['login_last_activity'] = time();
        $_SESSION['login_expires'] = time() + ((int) Config::get('security.session_ttl_minutes', 120) * 60);
        session_regenerate_id(true);

        return true;
    }

    public function checkSessionFresh(): void
    {
        if (!isset($_SESSION['user_id'])) {
            return;
        }

        $now = time();
        $idleTimeout = max(60, ((int) Config::get('security.session_idle_timeout_minutes', 30)) * 60);

        $lastActivity = $_SESSION['login_last_activity'] ?? null;
        if ($lastActivity !== null && ($now - (int) $lastActivity) > $idleTimeout) {
            $this->logout();
            return;
        }

        $expires = $_SESSION['login_expires'] ?? null;
        if ($expires !== null && $now > (int) $expires) {
            $this->logout();
            return;
        }

        $_SESSION['login_last_activity'] = $now;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);
    }

    public function requireRole(string $role): bool
    {
        $user = $this->user();
        return is_array($user) && ($user['role'] ?? null) === $role;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    private function ensureLoginAttemptsTable(): void
    {
        $pdo = Database::pdo();
        $pdo->exec('CREATE TABLE IF NOT EXISTS login_attempts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(190) NOT NULL,
            ip_address VARCHAR(64) NOT NULL,
            attempts INT UNSIGNED NOT NULL DEFAULT 0,
            first_attempt_at DATETIME NOT NULL,
            last_attempt_at DATETIME NOT NULL,
            locked_until DATETIME NULL,
            UNIQUE KEY uniq_email_ip (email, ip_address),
            INDEX idx_locked_until (locked_until)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    private function getBlockedUntil(string $email, string $ipAddress): ?DateTimeImmutable
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT locked_until FROM login_attempts WHERE email = :email AND ip_address = :ip_address LIMIT 1');
        $stmt->execute([
            ':email' => $email,
            ':ip_address' => $ipAddress,
        ]);
        $lockedUntil = $stmt->fetchColumn();

        if (!is_string($lockedUntil) || $lockedUntil === '') {
            return null;
        }

        $blockedUntil = new DateTimeImmutable($lockedUntil);
        if ($blockedUntil <= new DateTimeImmutable()) {
            $this->clearFailedAttempts($email, $ipAddress);
            return null;
        }

        return $blockedUntil;
    }

    private function registerFailedAttempt(string $email, string $ipAddress): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT attempts, first_attempt_at FROM login_attempts WHERE email = :email AND ip_address = :ip_address LIMIT 1');
        $stmt->execute([
            ':email' => $email,
            ':ip_address' => $ipAddress,
        ]);
        $row = $stmt->fetch();

        $now = new DateTimeImmutable();
        $windowMinutes = (int) Config::get('security.login_attempt_window_minutes', 15);
        $maxAttempts = (int) Config::get('security.login_max_attempts', 5);
        $lockMinutes = (int) Config::get('security.login_lock_minutes', 15);

        $attempts = 1;
        $firstAttemptAt = $now;

        if (is_array($row)) {
            $previousFirst = new DateTimeImmutable((string) $row['first_attempt_at']);
            $windowStart = $now->sub(new DateInterval('PT' . max(1, $windowMinutes) . 'M'));

            if ($previousFirst >= $windowStart) {
                $attempts = ((int) $row['attempts']) + 1;
                $firstAttemptAt = $previousFirst;
            }
        }

        $lockedUntil = null;
        if ($attempts >= max(1, $maxAttempts)) {
            $lockedUntil = $now->add(new DateInterval('PT' . max(1, $lockMinutes) . 'M'))->format('Y-m-d H:i:s');
        }

        $upsert = $pdo->prepare('INSERT INTO login_attempts (email, ip_address, attempts, first_attempt_at, last_attempt_at, locked_until)
            VALUES (:email, :ip_address, :attempts, :first_attempt_at, :last_attempt_at, :locked_until)
            ON DUPLICATE KEY UPDATE
                attempts = VALUES(attempts),
                first_attempt_at = VALUES(first_attempt_at),
                last_attempt_at = VALUES(last_attempt_at),
                locked_until = VALUES(locked_until)');
        $upsert->execute([
            ':email' => $email,
            ':ip_address' => $ipAddress,
            ':attempts' => $attempts,
            ':first_attempt_at' => $firstAttemptAt->format('Y-m-d H:i:s'),
            ':last_attempt_at' => $now->format('Y-m-d H:i:s'),
            ':locked_until' => $lockedUntil,
        ]);
    }

    private function clearFailedAttempts(string $email, string $ipAddress): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE email = :email AND ip_address = :ip_address');
        $stmt->execute([
            ':email' => $email,
            ':ip_address' => $ipAddress,
        ]);
    }

    private function getClientIp(): string
    {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        return is_string($ipAddress) && $ipAddress !== '' ? $ipAddress : 'unknown';
    }
}
