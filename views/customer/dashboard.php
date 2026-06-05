<section class="grid two-col fade-in">
    <article class="panel">
        <h2>Dein Zugang</h2>
        <p><strong><?= htmlspecialchars((string) $user['display_name'], ENT_QUOTES, 'UTF-8') ?></strong><br><?= htmlspecialchars((string) $user['email'], ENT_QUOTES, 'UTF-8') ?>, Typ: <?= htmlspecialchars((string) $user['customer_type'], ENT_QUOTES, 'UTF-8') ?></p>

        <h3>API-Tokens</h3>
        <div class="stack">
            <?php foreach ($tokens as $token): ?>
                <div class="card">
                    <div>
                        <strong><?= htmlspecialchars((string) $token['label'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                        <small>Letzte Nutzung: <?= htmlspecialchars((string) ($token['last_used_at'] ?? 'nie'), ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                    <form method="post">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="rotate_token">
                        <input type="hidden" name="token_id" value="<?= (int) $token['id'] ?>">
                        <button class="ghost-btn" type="submit">Neu generieren</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="api-hint">
            <h4>Fuer REDAXO KLXM Installer AddOn</h4>
            <pre><?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/index.php?route=api/v1/packages</pre>
            <p class="muted">Header: Authorization: Bearer DEIN_TOKEN</p>
        </div>
    </article>

    <article class="panel">
        <h2>Freigegebene AddOns</h2>
        <ul class="list">
            <?php foreach ($packages as $pkg): ?>
                <li>
                    <strong><?= htmlspecialchars((string) $pkg['full_name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                    <small>Default-Branch: <?= htmlspecialchars((string) $pkg['default_branch'], ENT_QUOTES, 'UTF-8') ?></small>
                </li>
            <?php endforeach; ?>
            <?php if (empty($packages)): ?>
                <li>Aktuell keine Freigaben vorhanden.</li>
            <?php endif; ?>
        </ul>
    </article>
</section>
