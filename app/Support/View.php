<?php

declare(strict_types=1);

namespace Klxm\Installer\Support;

use Klxm\Installer\Services\SettingsService;

final class View
{
    public static function render(string $template, array $data = []): void
    {
        $templateFile = dirname(__DIR__, 2) . '/views/' . $template . '.php';
        if (!is_file($templateFile)) {
            throw new \RuntimeException('Template not found: ' . $template);
        }

        $appName = (string) Config::get('app.name', 'KLXM Installer Proxy');
        $branding = (new SettingsService())->getBranding();
        $flash = $_SESSION['_flash'] ?? null;
        unset($_SESSION['_flash']);

        extract($data, EXTR_SKIP);
        require dirname(__DIR__, 2) . '/views/layouts/base.php';
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'] = [
            'type' => $type,
            'message' => $message,
        ];
    }
}
