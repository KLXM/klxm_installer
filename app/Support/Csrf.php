<?php

declare(strict_types=1);

namespace Klxm\Installer\Support;

final class Csrf
{
    public static function token(): string
    {
        if (!isset($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['_csrf'];
    }

    public static function verify(?string $token): bool
    {
        $sessionToken = $_SESSION['_csrf'] ?? '';
        return is_string($token) && is_string($sessionToken) && hash_equals($sessionToken, $token);
    }
}
