<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'KLXM Installer Proxy',
        'base_url' => 'https://installer.example.com',
        'env' => 'production',
        'debug' => false,
        'key' => 'change-me-to-a-random-64-char-string',
        'timezone' => 'Europe/Berlin',
        'session_name' => 'klxm_proxy_session',
    ],
    'db' => [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'klxm_installer_proxy',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
    // Optional: Fallback-Verbindungen fuer lokale Multi-Stack-Setups
    'db_fallbacks' => [
        // [
        //     'host' => 'coredb',
        //     'port' => 3306,
        //     'database' => 'core',
        //     'username' => 'core',
        //     'password' => 'core',
        //     'charset' => 'utf8mb4',
        // ],
    ],
    'security' => [
        'token_ttl_minutes' => 525600,
        'download_link_ttl_seconds' => 120,
        'session_ttl_minutes' => 120,
        'password_min_length' => 12,
    ],
    'github' => [
        'api_base' => 'https://api.github.com',
    ],
];
