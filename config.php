<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'KLXM Installer Proxy',
        'base_url' => 'http://localhost:8088/installer',
        'env' => 'development',
        'debug' => true,
        'key' => '242e7ffc1e3a1e5ad33b4b395894893f3fc4e61da2dd6fec96f274aef41a4b13',
        'timezone' => 'Europe/Berlin',
        'session_name' => 'klxm_proxy_session',
    ],
    'db' => [
        'driver' => 'mysql',
        'host' => 'db',
        'port' => 3306,
        'database' => 'redaxo',
        'username' => 'redaxo',
        'password' => 'redaxo',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
    'db_fallbacks' => [
        [
            'host' => 'db',
            'port' => 3306,
            'database' => 'core',
            'username' => 'core',
            'password' => 'core',
            'charset' => 'utf8mb4',
        ],
        [
            'host' => 'coredb',
            'port' => 3306,
            'database' => 'core',
            'username' => 'core',
            'password' => 'core',
            'charset' => 'utf8mb4',
        ],
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
