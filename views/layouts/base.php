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
            --theme-light-bg: <?= htmlspecialchars((string) ($branding['theme_light_bg'] ?? '#f3f6fb'), ENT_QUOTES, 'UTF-8') ?>;
            --theme-light-panel: <?= htmlspecialchars((string) ($branding['theme_light_panel'] ?? '#ffffff'), ENT_QUOTES, 'UTF-8') ?>;
            --theme-light-panel-2: <?= htmlspecialchars((string) ($branding['theme_light_panel_2'] ?? '#f8fbff'), ENT_QUOTES, 'UTF-8') ?>;
            --theme-light-text: <?= htmlspecialchars((string) ($branding['theme_light_text'] ?? '#12202f'), ENT_QUOTES, 'UTF-8') ?>;
            --theme-light-muted: <?= htmlspecialchars((string) ($branding['theme_light_muted'] ?? '#4b6178'), ENT_QUOTES, 'UTF-8') ?>;
            --theme-light-placeholder: <?= htmlspecialchars((string) ($branding['theme_light_placeholder'] ?? '#6f8398'), ENT_QUOTES, 'UTF-8') ?>;
            --theme-light-line: <?= htmlspecialchars((string) ($branding['theme_light_line'] ?? '#d0dbe8'), ENT_QUOTES, 'UTF-8') ?>;
            --theme-light-glow-a: <?= htmlspecialchars((string) ($branding['theme_light_glow_a'] ?? '#14b8a6'), ENT_QUOTES, 'UTF-8') ?>;
            --theme-light-glow-b: <?= htmlspecialchars((string) ($branding['theme_light_glow_b'] ?? '#155e75'), ENT_QUOTES, 'UTF-8') ?>;
            --theme-dark-bg: <?= htmlspecialchars((string) ($branding['theme_dark_bg'] ?? '#0f1012'), ENT_QUOTES, 'UTF-8') ?>;
            --theme-dark-panel: <?= htmlspecialchars((string) ($branding['theme_dark_panel'] ?? '#0f1f2f'), ENT_QUOTES, 'UTF-8') ?>;
            --theme-dark-panel-2: <?= htmlspecialchars((string) ($branding['theme_dark_panel_2'] ?? '#13263a'), ENT_QUOTES, 'UTF-8') ?>;
            --theme-dark-text: <?= htmlspecialchars((string) ($branding['theme_dark_text'] ?? '#e6f0f8'), ENT_QUOTES, 'UTF-8') ?>;
            --theme-dark-muted: <?= htmlspecialchars((string) ($branding['theme_dark_muted'] ?? '#9bb3ca'), ENT_QUOTES, 'UTF-8') ?>;
            --theme-dark-placeholder: <?= htmlspecialchars((string) ($branding['theme_dark_placeholder'] ?? '#b8cadd'), ENT_QUOTES, 'UTF-8') ?>;
            --theme-dark-line: <?= htmlspecialchars((string) ($branding['theme_dark_line'] ?? '#29445e'), ENT_QUOTES, 'UTF-8') ?>;
            --theme-dark-glow-a: <?= htmlspecialchars((string) ($branding['theme_dark_glow_a'] ?? '#ffffff'), ENT_QUOTES, 'UTF-8') ?>;
            --theme-dark-glow-b: <?= htmlspecialchars((string) ($branding['theme_dark_glow_b'] ?? '#737980'), ENT_QUOTES, 'UTF-8') ?>;
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
