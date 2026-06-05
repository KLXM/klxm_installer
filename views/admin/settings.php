<section class="grid two-col fade-in">
    <article class="panel">
        <h2>Branding</h2>
        <p class="muted">Titel, Logo und Farben fuer den gesamten Proxy.</p>

        <form method="post" class="form-grid">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="save_branding_settings">
            <input type="hidden" name="_redirect" value="settings">

            <label>Titel
                <input name="brand_title" value="<?= htmlspecialchars((string) ($branding['brand_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            </label>

            <label>Untertitel
                <input name="brand_subtitle" value="<?= htmlspecialchars((string) ($branding['brand_subtitle'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>

            <label>Logo URL
                <input name="brand_logo_url" placeholder="https://.../logo.svg" value="<?= htmlspecialchars((string) ($branding['brand_logo_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>

            <div class="color-section">
                <h3>Markenfarben</h3>
                <div class="color-grid">
                    <label class="color-field">Primary
                        <input type="color" name="brand_primary_color" value="<?= htmlspecialchars((string) ($branding['brand_primary_color'] ?? '#0f766e'), ENT_QUOTES, 'UTF-8') ?>" required>
                    </label>
                    <label class="color-field">Accent
                        <input type="color" name="brand_accent_color" value="<?= htmlspecialchars((string) ($branding['brand_accent_color'] ?? '#0b5f59'), ENT_QUOTES, 'UTF-8') ?>" required>
                    </label>
                </div>
            </div>

            <div class="color-section">
                <h3>Light Theme</h3>
                <div class="color-grid">
                    <label class="color-field">Hintergrund<input type="color" name="theme_light_bg" value="<?= htmlspecialchars((string) ($branding['theme_light_bg'] ?? '#f3f6fb'), ENT_QUOTES, 'UTF-8') ?>"></label>
                    <label class="color-field">Panel<input type="color" name="theme_light_panel" value="<?= htmlspecialchars((string) ($branding['theme_light_panel'] ?? '#ffffff'), ENT_QUOTES, 'UTF-8') ?>"></label>
                    <label class="color-field">Panel Verlauf<input type="color" name="theme_light_panel_2" value="<?= htmlspecialchars((string) ($branding['theme_light_panel_2'] ?? '#f8fbff'), ENT_QUOTES, 'UTF-8') ?>"></label>
                    <label class="color-field">Text<input type="color" name="theme_light_text" value="<?= htmlspecialchars((string) ($branding['theme_light_text'] ?? '#12202f'), ENT_QUOTES, 'UTF-8') ?>"></label>
                    <label class="color-field">Sekundärtext<input type="color" name="theme_light_muted" value="<?= htmlspecialchars((string) ($branding['theme_light_muted'] ?? '#4b6178'), ENT_QUOTES, 'UTF-8') ?>"></label>
                    <label class="color-field">Placeholder<input type="color" name="theme_light_placeholder" value="<?= htmlspecialchars((string) ($branding['theme_light_placeholder'] ?? '#6f8398'), ENT_QUOTES, 'UTF-8') ?>"></label>
                    <label class="color-field">Linien<input type="color" name="theme_light_line" value="<?= htmlspecialchars((string) ($branding['theme_light_line'] ?? '#d0dbe8'), ENT_QUOTES, 'UTF-8') ?>"></label>
                    <label class="color-field">Glow A<input type="color" name="theme_light_glow_a" value="<?= htmlspecialchars((string) ($branding['theme_light_glow_a'] ?? '#14b8a6'), ENT_QUOTES, 'UTF-8') ?>"></label>
                    <label class="color-field">Glow B<input type="color" name="theme_light_glow_b" value="<?= htmlspecialchars((string) ($branding['theme_light_glow_b'] ?? '#155e75'), ENT_QUOTES, 'UTF-8') ?>"></label>
                </div>
            </div>

            <div class="color-section">
                <h3>Dark Theme</h3>
                <div class="color-grid">
                    <label class="color-field">Hintergrund<input type="color" name="theme_dark_bg" value="<?= htmlspecialchars((string) ($branding['theme_dark_bg'] ?? '#0f1012'), ENT_QUOTES, 'UTF-8') ?>"></label>
                    <label class="color-field">Panel<input type="color" name="theme_dark_panel" value="<?= htmlspecialchars((string) ($branding['theme_dark_panel'] ?? '#0f1f2f'), ENT_QUOTES, 'UTF-8') ?>"></label>
                    <label class="color-field">Panel Verlauf<input type="color" name="theme_dark_panel_2" value="<?= htmlspecialchars((string) ($branding['theme_dark_panel_2'] ?? '#13263a'), ENT_QUOTES, 'UTF-8') ?>"></label>
                    <label class="color-field">Text<input type="color" name="theme_dark_text" value="<?= htmlspecialchars((string) ($branding['theme_dark_text'] ?? '#e6f0f8'), ENT_QUOTES, 'UTF-8') ?>"></label>
                    <label class="color-field">Sekundärtext<input type="color" name="theme_dark_muted" value="<?= htmlspecialchars((string) ($branding['theme_dark_muted'] ?? '#9bb3ca'), ENT_QUOTES, 'UTF-8') ?>"></label>
                    <label class="color-field">Placeholder<input type="color" name="theme_dark_placeholder" value="<?= htmlspecialchars((string) ($branding['theme_dark_placeholder'] ?? '#b8cadd'), ENT_QUOTES, 'UTF-8') ?>"></label>
                    <label class="color-field">Linien<input type="color" name="theme_dark_line" value="<?= htmlspecialchars((string) ($branding['theme_dark_line'] ?? '#29445e'), ENT_QUOTES, 'UTF-8') ?>"></label>
                    <label class="color-field">Glow A<input type="color" name="theme_dark_glow_a" value="<?= htmlspecialchars((string) ($branding['theme_dark_glow_a'] ?? '#ffffff'), ENT_QUOTES, 'UTF-8') ?>"></label>
                    <label class="color-field">Glow B<input type="color" name="theme_dark_glow_b" value="<?= htmlspecialchars((string) ($branding['theme_dark_glow_b'] ?? '#737980'), ENT_QUOTES, 'UTF-8') ?>"></label>
                </div>
            </div>

            <button class="btn" type="submit">Branding speichern</button>
        </form>
    </article>

    <article class="panel">
        <h2>GitHub-Bereiche</h2>
        <p class="muted">Beliebig viele Owner/Orgs hinzufügen. Keine festen Vorgaben.</p>

        <form method="post" class="form-grid owner-create-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="save_github_token">
            <input type="hidden" name="_redirect" value="settings">
            <label>Owner / Organisation
                <input name="owner_name" placeholder="z. B. KLXM oder musteragentur" required>
            </label>
            <label>Token
                <input type="password" name="token_value" placeholder="github_pat_..." required>
            </label>
            <button class="btn" type="submit">Bereich anlegen</button>
        </form>

        <div class="stack owner-stack">
            <?php foreach ($owners as $owner): ?>
                <div class="owner-item">
                    <div class="owner-item-head">
                        <strong><?= htmlspecialchars((string) $owner, ENT_QUOTES, 'UTF-8') ?></strong>
                        <?php foreach ($githubCredentials as $cred): ?>
                            <?php if ((string) $cred['owner_name'] === (string) $owner): ?>
                                <small>Token aktualisiert: <?= htmlspecialchars((string) $cred['updated_at'], ENT_QUOTES, 'UTF-8') ?></small>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <form method="post" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="save_github_token">
                        <input type="hidden" name="owner_name" value="<?= htmlspecialchars((string) $owner, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="_redirect" value="settings">
                        <div>
                            <input type="password" name="token_value" placeholder="Neuen Token setzen" required>
                        </div>
                        <button class="btn" type="submit">Token speichern</button>
                    </form>

                    <div class="owner-actions">
                        <form method="post">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="sync_owner_repos">
                            <input type="hidden" name="owner_name" value="<?= htmlspecialchars((string) $owner, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="_redirect" value="settings">
                            <button class="ghost-btn" type="submit">Private Repos synchronisieren</button>
                        </form>
                        <form method="post" onsubmit="return confirm('Bereich inklusive Repos wirklich entfernen?');">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="remove_owner">
                            <input type="hidden" name="owner_name" value="<?= htmlspecialchars((string) $owner, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="_redirect" value="settings">
                            <button class="ghost-btn danger" type="submit">Bereich entfernen</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ([] === $owners): ?>
                <p class="owner-empty">Noch kein GitHub-Bereich angelegt.</p>
            <?php endif; ?>
        </div>
    </article>
</section>
