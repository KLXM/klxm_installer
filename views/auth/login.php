<section class="panel narrow fade-in">
    <h2>Anmeldung</h2>
    <p class="muted">Admin, Kunde oder Sponsor. Kunden und Sponsoren melden sich mit ihrem eigenen Konto an.</p>
    <form method="post" class="form-grid" data-allow-paste-form>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <label>E-Mail
            <input type="email" name="email" required autocomplete="username" data-allow-paste>
        </label>
        <label>Passwort
            <input type="password" name="password" required autocomplete="current-password" data-allow-paste>
        </label>
        <button type="submit" class="btn">Einloggen</button>
    </form>
</section>
