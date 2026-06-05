<?php

declare(strict_types=1);

$error = null;
$success = null;

$configPath = __DIR__ . '/config.php';
if (is_file($configPath)) {
    $success = 'Konfiguration bereits vorhanden. Du kannst direkt zur Anmeldung wechseln.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !is_file($configPath)) {
    $appUrl = trim((string) ($_POST['app_url'] ?? ''));
    $dbHost = trim((string) ($_POST['db_host'] ?? '127.0.0.1'));
    $dbPort = (int) ($_POST['db_port'] ?? 3306);
    $dbName = trim((string) ($_POST['db_name'] ?? 'klxm_installer_proxy'));
    $dbUser = trim((string) ($_POST['db_user'] ?? 'redaxo'));
    $dbPass = (string) ($_POST['db_pass'] ?? '');
    $adminName = trim((string) ($_POST['admin_name'] ?? 'Administrator'));
    $adminEmail = mb_strtolower(trim((string) ($_POST['admin_email'] ?? 'admin@example.com')));
    $adminPassword = (string) ($_POST['admin_password'] ?? '');

    try {
        if ($appUrl === '' || $dbName === '' || $dbUser === '' || $adminEmail === '' || $adminPassword === '') {
            throw new RuntimeException('Bitte alle Pflichtfelder ausfuellen.');
        }

        $appKey = bin2hex(random_bytes(32));

        $dsn = 'mysql:host=' . $dbHost . ';port=' . $dbPort . ';dbname=' . $dbName . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $schema = file_get_contents(__DIR__ . '/sql/schema.sql');
        if (!is_string($schema) || trim($schema) === '') {
            throw new RuntimeException('Schema-Datei fehlt oder ist leer.');
        }

        foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) {
            if ($statement !== '') {
                $pdo->exec($statement);
            }
        }

        $stmt = $pdo->prepare('INSERT INTO users (email, display_name, password_hash, role, customer_type, is_active, passkey_enabled, created_at, updated_at) VALUES (:email, :display_name, :password_hash, :role, :customer_type, 1, 0, NOW(), NOW())');
        $stmt->execute([
            ':email' => $adminEmail,
            ':display_name' => $adminName,
            ':password_hash' => password_hash($adminPassword, PASSWORD_DEFAULT),
            ':role' => 'admin',
            ':customer_type' => 'customer',
        ]);

        $config = <<<'PHP'
<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'KLXM Installer Proxy',
        'base_url' => '__APP_URL__',
        'env' => 'production',
        'debug' => false,
        'key' => '__APP_KEY__',
        'timezone' => 'Europe/Berlin',
        'session_name' => 'klxm_proxy_session',
    ],
    'db' => [
        'driver' => 'mysql',
        'host' => '__DB_HOST__',
        'port' => __DB_PORT__,
        'database' => '__DB_NAME__',
        'username' => '__DB_USER__',
        'password' => '__DB_PASS__',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
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
PHP;

        $config = str_replace(
            ['__APP_URL__', '__APP_KEY__', '__DB_HOST__', '__DB_PORT__', '__DB_NAME__', '__DB_USER__', '__DB_PASS__'],
            [$appUrl, $appKey, addslashes($dbHost), (string) $dbPort, addslashes($dbName), addslashes($dbUser), addslashes($dbPass)],
            $config
        );

        file_put_contents($configPath, $config);
        $success = 'Installation erfolgreich. Du kannst dich jetzt anmelden.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KLXM Installer Proxy Setup</title>
    <link rel="stylesheet" href="public/assets/css/style.css">
</head>
<body>
<main class="setup-wrap">
    <section class="panel setup-panel">
        <h1>KLXM Installer Proxy Setup</h1>
        <p>Installer fuer MySQL oder MariaDB. Legt Schema und Admin-User an.</p>

        <?php if ($error !== null): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($success !== null): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
            <p><a class="btn" href="index.php?route=login">Zur Anmeldung</a></p>
        <?php elseif (!is_file($configPath)): ?>
            <form method="post" class="setup-form">
                <label>App URL <input name="app_url" required placeholder="https://domain.tld/installer"></label>
                <label>DB Host <input name="db_host" value="127.0.0.1" required></label>
                <label>DB Port <input name="db_port" type="number" value="3306" required></label>
                <label>DB Name <input name="db_name" value="klxm_installer_proxy" required></label>
                <label>DB User <input name="db_user" value="redaxo" required></label>
                <label>DB Passwort <input name="db_pass" type="password"></label>
                <label>Admin Name <input name="admin_name" value="Administrator" required></label>
                <label>Admin E-Mail <input name="admin_email" type="email" required></label>
                <label>Admin Passwort <input name="admin_password" type="password" minlength="12" required></label>
                <button class="btn" type="submit">Installation starten</button>
            </form>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
