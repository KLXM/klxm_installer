<?php

declare(strict_types=1);

namespace Klxm\Installer\Services;

use Klxm\Installer\Support\Database;

final class SettingsService
{
    public function getBranding(): array
    {
        $defaults = [
            'brand_title' => 'Installer Hub',
            'brand_subtitle' => 'Private Addons sicher fuer Kunden und Sponsoren bereitstellen',
            'brand_logo_url' => '',
            'brand_primary_color' => '#0f766e',
            'brand_accent_color' => '#0b5f59',
        ];

        try {
            $this->ensureTable();
            $pdo = Database::pdo();
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key LIKE 'brand_%'");
            $rows = $stmt->fetchAll() ?: [];
            foreach ($rows as $row) {
                $key = (string) ($row['setting_key'] ?? '');
                $value = (string) ($row['setting_value'] ?? '');
                if (array_key_exists($key, $defaults)) {
                    $defaults[$key] = $value;
                }
            }
        } catch (\Throwable) {
            // Falls Tabelle noch nicht existiert, auf sichere Defaults zurueckfallen.
        }

        return $defaults;
    }

    public function saveBranding(array $input): void
    {
        $this->ensureTable();

        $allowed = [
            'brand_title',
            'brand_subtitle',
            'brand_logo_url',
            'brand_primary_color',
            'brand_accent_color',
        ];

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('INSERT INTO app_settings (setting_key, setting_value, updated_at) VALUES (:setting_key, :setting_value, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()');

        foreach ($allowed as $key) {
            $value = trim((string) ($input[$key] ?? ''));
            if (str_ends_with($key, '_color') && $value !== '' && !preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
                throw new \RuntimeException('Farbwert fuer ' . $key . ' ist ungueltig. Bitte #RRGGBB verwenden.');
            }
            $stmt->execute([
                ':setting_key' => $key,
                ':setting_value' => $value,
            ]);
        }
    }

    private function ensureTable(): void
    {
        $pdo = Database::pdo();
        $pdo->exec('CREATE TABLE IF NOT EXISTS app_settings (
            setting_key VARCHAR(120) NOT NULL PRIMARY KEY,
            setting_value TEXT NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }
}
