<?php

declare(strict_types=1);

namespace Klxm\Installer\Support;

use Klxm\Installer\Services\AuditLogService;
use Klxm\Installer\Services\AuthService;
use Klxm\Installer\Services\GitHubService;
use Klxm\Installer\Services\SettingsService;
use Klxm\Installer\Services\TokenService;

final class App
{
    private AuthService $auth;
    private TokenService $tokens;
    private GitHubService $github;
    private AuditLogService $audit;
    private SettingsService $settings;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->tokens = new TokenService();
        $this->github = new GitHubService();
        $this->audit = new AuditLogService();
        $this->settings = new SettingsService();
    }

    public function run(): void
    {
        try {
            $this->auth->checkSessionFresh();

            $route = $this->resolveRoute();

            if (str_starts_with($route, 'api/v1/')) {
                $this->handleApi($route);
                return;
            }

            if ($route === 'logout') {
                $this->auth->logout();
                View::flash('success', 'Du wurdest abgemeldet.');
                Response::redirect('?route=login');
            }

            if ($route === 'login') {
                $this->handleLogin();
                return;
            }

            $user = $this->auth->user();
            if (!is_array($user)) {
                Response::redirect('?route=login');
            }

            if (($user['role'] ?? '') === 'admin') {
                if (!$this->isAdminIpAllowed()) {
                    $this->audit->log((int) ($user['id'] ?? 0), 'admin_ip_blocked', ['ip' => $this->getClientIp()]);
                    $this->auth->logout();
                    View::flash('error', 'Admin-Zugriff von dieser IP-Adresse ist nicht erlaubt.');
                    Response::redirect('?route=login');
                }

                $this->handleAdmin($user, $route);
                return;
            }

            $this->handleCustomer($user);
        } catch (\Throwable $e) {
            $route = $this->resolveRoute();
            if (str_starts_with($route, 'api/v1/')) {
                Response::json([
                    'error' => 'server_error',
                    'message' => $this->buildFriendlyErrorMessage($e),
                ], 500);
            }

            $this->auth->logout();
            View::flash('error', $this->buildFriendlyErrorMessage($e));
            Response::redirect('?route=login');
        }
    }

    private function buildFriendlyErrorMessage(\Throwable $e): string
    {
        $message = $e->getMessage();
        if (
            str_contains($message, 'Keine gueltige Installer-Datenbank gefunden') ||
            str_contains($message, 'Installer-Schema fehlt') ||
            str_contains($message, 'Base table or view not found')
        ) {
            return 'Installer-Datenbank ist noch nicht eingerichtet. Bitte zuerst /installer/install.php aufrufen.';
        }

        return 'Unerwarteter Fehler: ' . $message;
    }

    private function resolveRoute(): string
    {
        $queryRoute = $_GET['route'] ?? null;
        if (is_string($queryRoute) && $queryRoute !== '') {
            return trim($queryRoute, '/');
        }

        $pathRoute = $_GET['_url'] ?? '';
        if (is_string($pathRoute) && $pathRoute !== '') {
            return trim($pathRoute, '/');
        }

        return 'dashboard';
    }

    private function handleLogin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::verify($_POST['_csrf'] ?? null)) {
                View::flash('error', 'CSRF-Pruefung fehlgeschlagen.');
                Response::redirect('?route=login');
            }

            $email = (string) ($_POST['email'] ?? '');
            $password = (string) ($_POST['password'] ?? '');
            if ($this->auth->login($email, $password)) {
                $user = $this->auth->user();
                $this->audit->log((int) ($user['id'] ?? 0), 'login_success');
                Response::redirect('?route=dashboard');
            }

            $this->audit->log(null, 'login_failed', ['email' => $email]);
            View::flash('error', $this->auth->getLastError() ?? 'Login fehlgeschlagen.');
            Response::redirect('?route=login');
        }

        View::render('auth/login', [
            'title' => 'Anmeldung',
            'csrf' => Csrf::token(),
        ]);
    }

    private function handleAdmin(array $user, string $route): void
    {
        $activeRoute = $route === 'settings' ? 'settings' : 'dashboard';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::verify($_POST['_csrf'] ?? null)) {
                View::flash('error', 'CSRF-Pruefung fehlgeschlagen.');
                Response::redirect('?route=' . $activeRoute);
            }

            $action = (string) ($_POST['action'] ?? '');
            $redirectRoute = (string) ($_POST['_redirect'] ?? $activeRoute);
            $pdo = Database::pdo();

            try {
                if ($action === 'save_github_token') {
                    $owner = (string) ($_POST['owner_name'] ?? '');
                    $token = trim((string) ($_POST['token_value'] ?? ''));
                    if ($owner === '' || $token === '') {
                        throw new \RuntimeException('Owner und Token sind erforderlich.');
                    }
                    if (!preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]{0,98}[A-Za-z0-9])?$/', $owner)) {
                        throw new \RuntimeException('Owner-Name ist ungueltig.');
                    }
                    $this->github->upsertOwnerToken($owner, $token);
                    $this->audit->log((int) $user['id'], 'github_token_saved', ['owner' => $owner]);
                    View::flash('success', 'GitHub-Bereich gespeichert: ' . $owner);
                }

                if ($action === 'save_branding_settings') {
                    $this->settings->saveBranding([
                        'brand_title' => (string) ($_POST['brand_title'] ?? ''),
                        'brand_subtitle' => (string) ($_POST['brand_subtitle'] ?? ''),
                        'brand_logo_url' => (string) ($_POST['brand_logo_url'] ?? ''),
                        'brand_primary_color' => (string) ($_POST['brand_primary_color'] ?? ''),
                        'brand_accent_color' => (string) ($_POST['brand_accent_color'] ?? ''),
                        'theme_light_bg' => (string) ($_POST['theme_light_bg'] ?? ''),
                        'theme_light_panel' => (string) ($_POST['theme_light_panel'] ?? ''),
                        'theme_light_panel_2' => (string) ($_POST['theme_light_panel_2'] ?? ''),
                        'theme_light_text' => (string) ($_POST['theme_light_text'] ?? ''),
                        'theme_light_muted' => (string) ($_POST['theme_light_muted'] ?? ''),
                        'theme_light_placeholder' => (string) ($_POST['theme_light_placeholder'] ?? ''),
                        'theme_light_line' => (string) ($_POST['theme_light_line'] ?? ''),
                        'theme_light_glow_a' => (string) ($_POST['theme_light_glow_a'] ?? ''),
                        'theme_light_glow_b' => (string) ($_POST['theme_light_glow_b'] ?? ''),
                        'theme_dark_bg' => (string) ($_POST['theme_dark_bg'] ?? ''),
                        'theme_dark_panel' => (string) ($_POST['theme_dark_panel'] ?? ''),
                        'theme_dark_panel_2' => (string) ($_POST['theme_dark_panel_2'] ?? ''),
                        'theme_dark_text' => (string) ($_POST['theme_dark_text'] ?? ''),
                        'theme_dark_muted' => (string) ($_POST['theme_dark_muted'] ?? ''),
                        'theme_dark_placeholder' => (string) ($_POST['theme_dark_placeholder'] ?? ''),
                        'theme_dark_line' => (string) ($_POST['theme_dark_line'] ?? ''),
                        'theme_dark_glow_a' => (string) ($_POST['theme_dark_glow_a'] ?? ''),
                        'theme_dark_glow_b' => (string) ($_POST['theme_dark_glow_b'] ?? ''),
                    ]);
                    $this->audit->log((int) $user['id'], 'branding_updated');
                    View::flash('success', 'Branding gespeichert.');
                }

                if ($action === 'sync_owner_repos') {
                    $owner = (string) ($_POST['owner_name'] ?? '');
                    $repos = $this->github->syncReposForOwner($owner);
                    $this->audit->log((int) $user['id'], 'github_sync_repos', ['owner' => $owner, 'count' => count($repos)]);
                    if ([] === $repos) {
                        View::flash('error', 'Keine Repositories gefunden. Pruefe Token-Rechte, Org-Zugriff und ggf. Configure SSO fuer ' . $owner . '.');
                    } else {
                        View::flash('success', 'Repos synchronisiert: ' . count($repos));
                    }
                }

                if ($action === 'remove_owner') {
                    $owner = trim((string) ($_POST['owner_name'] ?? ''));
                    if ($owner === '') {
                        throw new \RuntimeException('Owner ist erforderlich.');
                    }

                    $pdo->beginTransaction();
                    $deleteCredentialsStmt = $pdo->prepare('DELETE FROM github_credentials WHERE owner_name = :owner_name');
                    $deleteCredentialsStmt->execute([':owner_name' => $owner]);

                    $deleteReposStmt = $pdo->prepare('DELETE FROM repositories WHERE owner_name = :owner_name');
                    $deleteReposStmt->execute([':owner_name' => $owner]);

                    $pdo->commit();
                    $this->audit->log((int) $user['id'], 'owner_removed', ['owner' => $owner]);
                    View::flash('success', 'GitHub-Bereich entfernt: ' . $owner);
                }

                if ($action === 'create_customer') {
                    $name = trim((string) ($_POST['display_name'] ?? ''));
                    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
                    $password = (string) ($_POST['password'] ?? '');
                    $type = (string) ($_POST['customer_type'] ?? 'customer');

                    if ($name === '' || $email === '' || $password === '') {
                        throw new \RuntimeException('Name, E-Mail und Passwort sind erforderlich.');
                    }
                    if (strlen($password) < (int) Config::get('security.password_min_length', 12)) {
                        throw new \RuntimeException('Passwort zu kurz.');
                    }

                    $stmt = $pdo->prepare('INSERT INTO users (email, display_name, password_hash, role, customer_type, is_active, passkey_enabled, created_at, updated_at) VALUES (:email, :display_name, :password_hash, :role, :customer_type, 1, 0, NOW(), NOW())');
                    $stmt->execute([
                        ':email' => $email,
                        ':display_name' => $name,
                        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        ':role' => 'customer',
                        ':customer_type' => in_array($type, ['customer', 'sponsor'], true) ? $type : 'customer',
                    ]);
                    $newUserId = (int) $pdo->lastInsertId();
                    $plainToken = $this->tokens->createUserToken($newUserId, 'Ersttoken');
                    $loginUrl = $this->buildLoginUrl();
                    $_SESSION['_customer_handover'] = [
                        'display_name' => $name,
                        'email' => $email,
                        'password' => $password,
                        'login_url' => $loginUrl,
                        'api_token' => $plainToken,
                    ];
                    $this->audit->log((int) $user['id'], 'customer_created', ['customer_id' => $newUserId]);
                    View::flash('success', 'Kunde erstellt. Initialer Token: ' . $plainToken);
                }

                if ($action === 'save_user_repo_accesses') {
                    $targetUserId = (int) ($_POST['user_id'] ?? 0);
                    if ($targetUserId <= 0) {
                        throw new \RuntimeException('Ungueltiger Benutzer.');
                    }

                    $rawRepoIds = $_POST['repository_ids'] ?? [];
                    $repoIds = [];
                    if (is_array($rawRepoIds)) {
                        foreach ($rawRepoIds as $repoId) {
                            $id = (int) $repoId;
                            if ($id > 0) {
                                $repoIds[$id] = $id;
                            }
                        }
                    }

                    $allowedRepoRows = $pdo->query('SELECT id FROM repositories WHERE is_private = 1 AND is_active = 1')->fetchAll() ?: [];
                    $allowedRepoIds = [];
                    foreach ($allowedRepoRows as $row) {
                        $allowedRepoIds[(int) $row['id']] = true;
                    }

                    $pdo->beginTransaction();
                    $deleteStmt = $pdo->prepare('DELETE FROM repository_access WHERE user_id = :user_id');
                    $deleteStmt->execute([':user_id' => $targetUserId]);

                    $inserted = 0;
                    if ([] !== $repoIds) {
                        $insertStmt = $pdo->prepare('INSERT INTO repository_access (user_id, repository_id, created_at) VALUES (:user_id, :repository_id, NOW())');
                        foreach ($repoIds as $repoId) {
                            if (!isset($allowedRepoIds[$repoId])) {
                                continue;
                            }
                            $insertStmt->execute([
                                ':user_id' => $targetUserId,
                                ':repository_id' => $repoId,
                            ]);
                            $inserted++;
                        }
                    }

                    $pdo->commit();
                    $this->audit->log((int) $user['id'], 'repo_access_bulk_saved', ['customer_id' => $targetUserId, 'count' => $inserted]);
                    View::flash('success', 'Freigaben gespeichert: ' . $inserted . ' private Repositories.');
                }

                if ($action === 'generate_customer_token') {
                    $targetUserId = (int) ($_POST['user_id'] ?? 0);
                    if ($targetUserId <= 0) {
                        throw new \RuntimeException('Ungueltiger Benutzer.');
                    }

                    $userStmt = $pdo->prepare("SELECT id, display_name FROM users WHERE id = :id AND role = 'customer' LIMIT 1");
                    $userStmt->execute([':id' => $targetUserId]);
                    $targetUser = $userStmt->fetch();
                    if (!is_array($targetUser)) {
                        throw new \RuntimeException('Benutzer nicht gefunden oder kein Kunde/Sponsor.');
                    }

                    $label = trim((string) ($_POST['token_label'] ?? ''));
                    if ($label === '') {
                        $label = 'Admin erstellt';
                    }

                    $plainToken = $this->tokens->createUserToken($targetUserId, $label);
                    $this->audit->log((int) $user['id'], 'admin_token_created', [
                        'customer_id' => $targetUserId,
                        'label' => $label,
                    ]);

                    View::flash('success', 'Neuer Token fuer ' . (string) $targetUser['display_name'] . ': ' . $plainToken);
                }
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                View::flash('error', 'Fehler: ' . $e->getMessage());
            }

            Response::redirect('?route=' . $redirectRoute);
        }

        $pdo = Database::pdo();
        $ownerRows = $pdo->query('SELECT owner_name FROM github_credentials UNION SELECT owner_name FROM repositories ORDER BY owner_name ASC')->fetchAll() ?: [];
        $owners = [];
        foreach ($ownerRows as $ownerRow) {
            $ownerName = (string) ($ownerRow['owner_name'] ?? '');
            if ($ownerName !== '') {
                $owners[] = $ownerName;
            }
        }
        $customers = $pdo->query("SELECT id, display_name, email, customer_type, is_active FROM users WHERE role = 'customer' ORDER BY display_name ASC")->fetchAll() ?: [];
        $repos = $pdo->query('SELECT id, owner_name, repo_name, full_name, is_private, is_active FROM repositories WHERE is_private = 1 AND is_active = 1 ORDER BY owner_name, repo_name')->fetchAll() ?: [];
        $githubCredentials = $pdo->query('SELECT owner_name, updated_at FROM github_credentials ORDER BY owner_name')->fetchAll() ?: [];

        $accessRows = $pdo->query('SELECT user_id, repository_id FROM repository_access')->fetchAll() ?: [];
        $userAccessRepoIds = [];
        foreach ($accessRows as $row) {
            $uid = (int) $row['user_id'];
            $rid = (int) $row['repository_id'];
            if (!isset($userAccessRepoIds[$uid])) {
                $userAccessRepoIds[$uid] = [];
            }
            $userAccessRepoIds[$uid][$rid] = true;
        }

        $reposByOwner = [];
        foreach ($owners as $owner) {
            $reposByOwner[(string) $owner] = [];
        }
        foreach ($repos as $repo) {
            $owner = (string) $repo['owner_name'];
            if (!isset($reposByOwner[$owner])) {
                $reposByOwner[$owner] = [];
            }
            $reposByOwner[$owner][] = $repo;
        }

        $handover = $_SESSION['_customer_handover'] ?? null;
        if (is_array($handover)) {
            unset($_SESSION['_customer_handover']);
        } else {
            $handover = null;
        }

        if ($activeRoute === 'settings') {
            View::render('admin/settings', [
                'title' => 'Settings',
                'activeRoute' => $activeRoute,
                'csrf' => Csrf::token(),
                'user' => $user,
                'owners' => $owners,
                'githubCredentials' => $githubCredentials,
                'branding' => $this->settings->getBranding(),
            ]);

            return;
        }

        View::render('admin/dashboard', [
            'title' => 'Admin Area',
            'activeRoute' => $activeRoute,
            'csrf' => Csrf::token(),
            'user' => $user,
            'owners' => $owners,
            'customers' => $customers,
            'reposByOwner' => $reposByOwner,
            'userAccessRepoIds' => $userAccessRepoIds,
            'handover' => $handover,
        ]);
    }

    private function buildLoginUrl(): string
    {
        $baseUrl = rtrim((string) Config::get('app.base_url', ''), '/');
        if ($baseUrl === '') {
            return '/installer';
        }

        return $baseUrl;
    }

    private function handleCustomer(array $user): void
    {
        $pdo = Database::pdo();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::verify($_POST['_csrf'] ?? null)) {
                View::flash('error', 'CSRF-Pruefung fehlgeschlagen.');
                Response::redirect('?route=dashboard');
            }

            $action = (string) ($_POST['action'] ?? '');

            try {
                if ($action === 'rotate_token') {
                    $tokenId = (int) ($_POST['token_id'] ?? 0);
                    $newToken = $this->tokens->rotateUserToken((int) $user['id'], $tokenId);
                    $this->audit->log((int) $user['id'], 'token_rotated', ['token_id' => $tokenId]);
                    View::flash('success', 'Token erneuert: ' . $newToken);
                }
            } catch (\Throwable $e) {
                View::flash('error', 'Fehler: ' . $e->getMessage());
            }

            Response::redirect('?route=dashboard');
        }

        $tokenRowsStmt = $pdo->prepare('SELECT id, label, expires_at, last_used_at, created_at FROM api_tokens WHERE user_id = :user_id ORDER BY id DESC');
        $tokenRowsStmt->execute([':user_id' => (int) $user['id']]);
        $tokens = $tokenRowsStmt->fetchAll() ?: [];

        $packages = $this->github->fetchAllowedPackagesForUser((int) $user['id']);

        View::render('customer/dashboard', [
            'title' => 'Kundenbereich',
            'activeRoute' => 'dashboard',
            'csrf' => Csrf::token(),
            'user' => $user,
            'tokens' => $tokens,
            'packages' => $packages,
            'baseUrl' => (string) Config::get('app.base_url'),
        ]);
    }

    private function handleApi(string $route): void
    {
        $token = $this->extractBearerToken();
        if ($token === null) {
            Response::json(['error' => 'missing_token'], 401);
        }

        $tokenData = $this->tokens->verifyBearerToken($token);
        if (!is_array($tokenData)) {
            Response::json(['error' => 'invalid_token'], 401);
        }

        $userId = (int) $tokenData['user_id'];

        if ($route === 'api/v1/packages') {
            $packages = $this->github->fetchAllowedPackagesForUser($userId);
            Response::json(['packages' => $packages]);
        }

        if ($route === 'api/v1/versions') {
            $owner = (string) ($_GET['owner'] ?? '');
            $repo = (string) ($_GET['repo'] ?? '');
            $allowed = $this->isRepoAllowed($userId, $owner, $repo);
            if (!$allowed) {
                Response::json(['error' => 'forbidden'], 403);
            }

            $ownerToken = $this->github->getOwnerToken($owner);
            if ($ownerToken === null) {
                Response::json(['error' => 'owner_not_configured'], 500);
            }

            $releases = $this->github->fetchReleases($owner, $repo, $ownerToken);
            $data = array_map(static function (array $release): array {
                return [
                    'name' => (string) ($release['name'] ?? ''),
                    'tag_name' => (string) ($release['tag_name'] ?? ''),
                    'published_at' => (string) ($release['published_at'] ?? ''),
                    'prerelease' => (bool) ($release['prerelease'] ?? false),
                    'draft' => (bool) ($release['draft'] ?? false),
                ];
            }, $releases);

            Response::json(['versions' => $data]);
        }

        if ($route === 'api/v1/download') {
            $owner = (string) ($_GET['owner'] ?? '');
            $repo = (string) ($_GET['repo'] ?? '');
            $ref = (string) ($_GET['ref'] ?? '');

            if ($owner === '' || $repo === '' || $ref === '') {
                Response::json(['error' => 'missing_params'], 422);
            }

            if (!$this->isRepoAllowed($userId, $owner, $repo)) {
                Response::json(['error' => 'forbidden'], 403);
            }

            $ownerToken = $this->github->getOwnerToken($owner);
            if ($ownerToken === null) {
                Response::json(['error' => 'owner_not_configured'], 500);
            }

            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $owner . '-' . $repo . '-' . $ref . '.zip"');
            $this->github->streamZipball($owner, $repo, $ref, $ownerToken);
            exit;
        }

        Response::json(['error' => 'not_found'], 404);
    }

    private function extractBearerToken(): ?string
    {
        $headerCandidates = [
            $_SERVER['HTTP_AUTHORIZATION'] ?? null,
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null,
        ];

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                $headerCandidates[] = $headers['Authorization'] ?? $headers['authorization'] ?? null;
            }
        }

        $header = '';
        foreach ($headerCandidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                $header = $candidate;
                break;
            }
        }

        if ($header === '' || !str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($header, 7));
        return $token !== '' ? $token : null;
    }

    private function isRepoAllowed(int $userId, string $owner, string $repo): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT r.id FROM repositories r INNER JOIN repository_access ra ON ra.repository_id = r.id WHERE ra.user_id = :user_id AND r.owner_name = :owner_name AND r.repo_name = :repo_name AND r.is_active = 1 AND r.is_private = 1 LIMIT 1');
        $stmt->execute([
            ':user_id' => $userId,
            ':owner_name' => $owner,
            ':repo_name' => $repo,
        ]);

        return is_array($stmt->fetch());
    }

    private function isAdminIpAllowed(): bool
    {
        $allowlist = Config::get('security.admin_ip_allowlist', []);
        if (!is_array($allowlist) || $allowlist === []) {
            return true;
        }

        $clientIp = $this->getClientIp();
        if ($clientIp === '') {
            return false;
        }

        foreach ($allowlist as $ip) {
            if (!is_string($ip)) {
                continue;
            }

            if (trim($ip) === $clientIp) {
                return true;
            }
        }

        return false;
    }

    private function getClientIp(): string
    {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        return is_string($ipAddress) ? trim($ipAddress) : '';
    }
}
