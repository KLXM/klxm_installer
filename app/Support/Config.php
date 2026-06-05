<?php

declare(strict_types=1);

namespace Klxm\Installer\Support;

final class Config
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $config = $GLOBALS['klxm_config'] ?? [];
        $segments = explode('.', $key);
        $value = $config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
