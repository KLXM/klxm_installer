<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'Klxm\\Installer\\';
    $baseDir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

$configFile = dirname(__DIR__) . '/config.php';
if (!is_file($configFile)) {
    $configFile = dirname(__DIR__) . '/config.example.php';
}

$config = require $configFile;

date_default_timezone_set((string) ($config['app']['timezone'] ?? 'Europe/Berlin'));

session_name((string) ($config['app']['session_name'] ?? 'klxm_proxy_session'));
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$GLOBALS['klxm_config'] = $config;
