<section class="dashboard-toolbar fade-in">
    <div class="dashboard-toolbar-head">
        <div>
            <h2>Kunden und Sponsoren</h2>
            <p class="muted">Benutzer anlegen, Zugänge übergeben und Freigaben kompakt verwalten.</p>
        </div>
        <button type="button" class="btn plus-btn" data-toggle-create-user aria-expanded="false" aria-controls="createUserForm">
            + Neuer Kunde / Sponsor
        </button>
    </div>

    <?php if (isset($handover) && is_array($handover)): ?>
        <?php
        $handoverText =
            "Zugang fuer: " . (string) ($handover['display_name'] ?? '') . "\n"
            . "Login URL: " . (string) ($handover['login_url'] ?? '') . "\n"
            . "E-Mail: " . (string) ($handover['email'] ?? '') . "\n"
            . "Passwort: " . (string) ($handover['password'] ?? '') . "\n"
            . "API-Token: " . (string) ($handover['api_token'] ?? '');
        ?>
        <div class="handover-box">
            <h3>Übergabe für Zwischenablage</h3>
            <p class="muted">Diesen Block direkt an den neuen Benutzer senden.</p>
            <textarea id="handoverText" rows="6" readonly><?= htmlspecialchars($handoverText, ENT_QUOTES, 'UTF-8') ?></textarea>
            <button type="button" class="ghost-btn" data-copy-target="handoverText">In Zwischenablage kopieren</button>
        </div>
    <?php endif; ?>

    <form method="post" class="form-grid create-user-form create-user-form-compact" id="createUserForm" hidden>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="action" value="create_customer">
        <input type="hidden" name="_redirect" value="dashboard">
        <div class="create-user-fields">
            <label>Name <input name="display_name" required></label>
            <label>E-Mail <input name="email" type="email" required></label>
            <label>Passwort <input name="password" type="password" minlength="12" required></label>
            <label>Typ
                <select name="customer_type">
                    <option value="customer">Kunde</option>
                    <option value="sponsor">Sponsor</option>
                </select>
            </label>
        </div>
        <div class="create-user-actions">
            <button class="btn btn-compact" type="submit">Konto anlegen</button>
        </div>
    </form>
</section>

<section class="panel fade-in delay-1">
    <h2>Private Repo-Freigaben</h2>
    <p class="muted">Pro Benutzer private Repositories freigeben, getrennt nach GitHub-Bereich.</p>

    <?php if ([] === array_filter($reposByOwner)): ?>
        <p class="owner-empty">Keine privaten Repositories vorhanden. Speichere je Owner einen Token und synchronisiere danach.</p>
    <?php endif; ?>

    <div class="access-grid">
        <?php foreach ($customers as $customer): ?>
            <?php $selectedMap = $userAccessRepoIds[(int) $customer['id']] ?? []; ?>
            <?php $selectedCount = count($selectedMap); ?>
            <details class="access-card access-entry <?= (string) $customer['customer_type'] === 'sponsor' ? 'sponsor' : '' ?>">
                <summary class="access-summary">
                    <div class="access-summary-main">
                        <strong><?= htmlspecialchars((string) $customer['display_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="access-summary-email"><?= htmlspecialchars((string) $customer['email'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="access-summary-meta">
                        <span class="summary-pill"><?= $selectedCount ?> Freigaben</span>
                        <span class="role-pill <?= (string) $customer['customer_type'] === 'sponsor' ? 'sponsor' : 'customer' ?>"><?= htmlspecialchars((string) $customer['customer_type'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </summary>

                <div class="access-body">
                    <form method="post" class="form-grid compact-form">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="save_user_repo_accesses">
                        <input type="hidden" name="user_id" value="<?= (int) $customer['id'] ?>">
                        <input type="hidden" name="_redirect" value="dashboard">

                        <div class="owner-groups owner-groups-compact">
                            <?php foreach ($reposByOwner as $owner => $ownerRepos): ?>
                                <div class="owner-block owner-block-compact">
                                    <h4><?= htmlspecialchars((string) $owner, ENT_QUOTES, 'UTF-8') ?></h4>
                                    <?php if ([] === $ownerRepos): ?>
                                        <p class="owner-empty">Keine privaten Repositories synchronisiert.</p>
                                    <?php else: ?>
                                        <div class="multi-select" data-multi-select>
                                            <button type="button" class="multi-toggle" data-multi-toggle>
                                                <span data-multi-count>0 gewaehlt</span>
                                                <span class="multi-arrow">▾</span>
                                            </button>
                                            <div class="multi-panel" data-multi-panel>
                                                <input type="search" class="multi-search" data-multi-search placeholder="Repo suchen...">
                                                <div class="multi-options" data-multi-options>
                                                    <?php foreach ($ownerRepos as $repo): ?>
                                                        <?php $repoId = (int) $repo['id']; ?>
                                                        <label class="multi-option-row" data-multi-row>
                                                            <input type="checkbox" data-multi-option name="repository_ids[]" value="<?= $repoId ?>" <?= isset($selectedMap[$repoId]) ? 'checked' : '' ?>>
                                                            <span><?= htmlspecialchars((string) $repo['repo_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <div class="chips" data-multi-chips></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="access-actions">
                            <button class="btn btn-compact" type="submit">Freigaben speichern</button>
                        </div>
                    </form>

                    <form method="post" class="inline-form token-admin-form token-admin-form-compact">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="generate_customer_token">
                        <input type="hidden" name="user_id" value="<?= (int) $customer['id'] ?>">
                        <input type="hidden" name="_redirect" value="dashboard">
                        <label>Neuer Token-Label
                            <input type="text" name="token_label" placeholder="z. B. Companion Addon" maxlength="120">
                        </label>
                        <button class="ghost-btn" type="submit">Token erzeugen</button>
                    </form>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
</section>
