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

/**
 * Einheitliche Sicherheits-Header fuer alle Antworten.
 */
if (!headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=()');
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'; object-src 'none'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; connect-src 'self' https://api.github.com");
}

/**
 * HTTPS-Erkennung, inkl. Reverse-Proxy-Header.
 */
$isHttps = false;
$httpsServer = $_SERVER['HTTPS'] ?? null;
if (is_string($httpsServer) && strtolower($httpsServer) !== 'off' && $httpsServer !== '') {
    $isHttps = true;
}
if (($_SERVER['SERVER_PORT'] ?? null) === '443') {
    $isHttps = true;
}
$forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
if (is_string($forwardedProto) && strtolower($forwardedProto) === 'https') {
    $isHttps = true;
}

if ($isHttps && !headers_sent()) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

session_name((string) ($config['app']['session_name'] ?? 'klxm_proxy_session'));

$sessionTtlSeconds = max(300, ((int) ($config['security']['session_ttl_minutes'] ?? 120)) * 60);

ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.gc_maxlifetime', (string) $sessionTtlSeconds);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Strict',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['_session_created_at'])) {
    $_SESSION['_session_created_at'] = time();
}

$rotationInterval = max(60, ((int) ($config['security']['session_rotation_minutes'] ?? 15)) * 60);
$lastRotation = (int) ($_SESSION['_session_last_rotation'] ?? 0);
if (time() - $lastRotation >= $rotationInterval) {
    session_regenerate_id(true);
    $_SESSION['_session_last_rotation'] = time();
}

$GLOBALS['klxm_config'] = $config;
