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

            <label>Primary Farbe (#RRGGBB)
                <input name="brand_primary_color" value="<?= htmlspecialchars((string) ($branding['brand_primary_color'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            </label>

            <label>Accent Farbe (#RRGGBB)
                <input name="brand_accent_color" value="<?= htmlspecialchars((string) ($branding['brand_accent_color'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            </label>

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
