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
            'theme_light_bg' => '#f3f6fb',
            'theme_light_panel' => '#ffffff',
            'theme_light_panel_2' => '#f8fbff',
            'theme_light_text' => '#12202f',
            'theme_light_muted' => '#4b6178',
            'theme_light_placeholder' => '#6f8398',
            'theme_light_line' => '#d0dbe8',
            'theme_light_glow_a' => '#14b8a6',
            'theme_light_glow_b' => '#155e75',
            'theme_dark_bg' => '#0f1012',
            'theme_dark_panel' => '#0f1f2f',
            'theme_dark_panel_2' => '#13263a',
            'theme_dark_text' => '#e6f0f8',
            'theme_dark_muted' => '#9bb3ca',
            'theme_dark_placeholder' => '#b8cadd',
            'theme_dark_line' => '#29445e',
            'theme_dark_glow_a' => '#ffffff',
            'theme_dark_glow_b' => '#737980',
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
            'theme_light_bg',
            'theme_light_panel',
            'theme_light_panel_2',
            'theme_light_text',
            'theme_light_muted',
            'theme_light_placeholder',
            'theme_light_line',
            'theme_light_glow_a',
            'theme_light_glow_b',
            'theme_dark_bg',
            'theme_dark_panel',
            'theme_dark_panel_2',
            'theme_dark_text',
            'theme_dark_muted',
            'theme_dark_placeholder',
            'theme_dark_line',
            'theme_dark_glow_a',
            'theme_dark_glow_b',
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
