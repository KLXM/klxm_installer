<section class="grid two-col fade-in">
    <article class="panel">
        <h2>Kunden und Sponsoren</h2>
        <div class="create-user-wrap">
            <button type="button" class="btn plus-btn" data-toggle-create-user aria-expanded="false" aria-controls="createUserForm">
                + Neuer Kunde / Sponsor
            </button>

            <form method="post" class="form-grid create-user-form" id="createUserForm" hidden>
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="create_customer">
                <input type="hidden" name="_redirect" value="dashboard">
                <label>Name <input name="display_name" required></label>
                <label>E-Mail <input name="email" type="email" required></label>
                <label>Passwort <input name="password" type="password" minlength="12" required></label>
                <label>Typ
                    <select name="customer_type">
                        <option value="customer">Kunde</option>
                        <option value="sponsor">Sponsor</option>
                    </select>
                </label>
                <button class="btn" type="submit">Konto anlegen</button>
            </form>
        </div>
    </article>
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
            <div class="access-card <?= (string) $customer['customer_type'] === 'sponsor' ? 'sponsor' : '' ?>">

                <div class="access-head">
                    <h3><?= htmlspecialchars((string) $customer['display_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <span class="role-pill <?= (string) $customer['customer_type'] === 'sponsor' ? 'sponsor' : 'customer' ?>"><?= htmlspecialchars((string) $customer['customer_type'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <form method="post" class="form-grid">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="save_user_repo_accesses">
                    <input type="hidden" name="user_id" value="<?= (int) $customer['id'] ?>">
                    <input type="hidden" name="_redirect" value="dashboard">

                    <div class="owner-groups">
                        <?php foreach ($reposByOwner as $owner => $ownerRepos): ?>
                            <div class="owner-block">
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

                    <button class="btn" type="submit">Freigaben speichern</button>
                </form>

                <form method="post" class="inline-form token-admin-form">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="generate_customer_token">
                    <input type="hidden" name="user_id" value="<?= (int) $customer['id'] ?>">
                    <input type="hidden" name="_redirect" value="dashboard">
                    <label>Neuer Token-Label
                        <input type="text" name="token_label" placeholder="z. B. Companion Addon" maxlength="120">
                    </label>
                    <button class="ghost-btn" type="submit">Neuen Token erzeugen</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</section>
