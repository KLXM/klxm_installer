<!doctype html>
<html lang="de" data-theme="auto">
<head>
    <?php
    $assetRoot = dirname(__DIR__, 2) . '/public/assets/';
    $cssVersion = @filemtime($assetRoot . 'css/style.css') ?: 1;
    $jsVersion = @filemtime($assetRoot . 'js/app.js') ?: 1;
    ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(($title ?? 'Dashboard') . ' | ' . ($branding['brand_title'] ?? $appName), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="public/assets/css/style.css?v=<?= (int) $cssVersion ?>">
    <style>
        :root {
            --brand-accent: <?= htmlspecialchars((string) ($branding['brand_primary_color'] ?? '#0f766e'), ENT_QUOTES, 'UTF-8') ?>;
            --brand-accent-2: <?= htmlspecialchars((string) ($branding['brand_accent_color'] ?? '#0b5f59'), ENT_QUOTES, 'UTF-8') ?>;
        }
    </style>
</head>
<body>
<div class="bg-shape bg-shape-a"></div>
<div class="bg-shape bg-shape-b"></div>
<header class="topbar">
    <div class="brand-block">
        <?php if (($branding['brand_logo_url'] ?? '') !== ''): ?>
            <img class="brand-logo" src="<?= htmlspecialchars((string) $branding['brand_logo_url'], ENT_QUOTES, 'UTF-8') ?>" alt="Logo">
        <?php endif; ?>
        <div>
            <h1><?= htmlspecialchars((string) ($branding['brand_title'] ?? $appName), ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="subtitle"><?= htmlspecialchars((string) ($branding['brand_subtitle'] ?? 'Private REDAXO Addons sicher fuer Kunden und Sponsoren bereitstellen'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </div>
    <div class="topbar-actions">
        <button id="themeToggle" class="ghost-btn" type="button">Theme</button>
        <?php if (isset($user) && is_array($user)): ?>
            <?php if (($user['role'] ?? '') === 'admin'): ?>
                <a class="ghost-btn <?= ($activeRoute ?? '') === 'dashboard' ? 'is-active' : '' ?>" href="?route=dashboard">Dashboard</a>
                <a class="ghost-btn <?= ($activeRoute ?? '') === 'settings' ? 'is-active' : '' ?>" href="?route=settings">Settings</a>
            <?php endif; ?>
            <a class="ghost-btn" href="?route=logout">Logout</a>
        <?php endif; ?>
    </div>
</header>
<main class="container">
    <?php if (isset($flash) && is_array($flash)): ?>
        <div class="alert <?= ($flash['type'] ?? 'success') === 'error' ? 'alert-error' : 'alert-success' ?>">
            <?= htmlspecialchars((string) ($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php require $templateFile; ?>
</main>
<script src="public/assets/js/app.js?v=<?= (int) $jsVersion ?>"></script>
</body>
</html>
