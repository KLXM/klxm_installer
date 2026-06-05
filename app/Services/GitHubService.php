<?php

declare(strict_types=1);

namespace Klxm\Installer\Services;

use Klxm\Installer\Support\Config;
use Klxm\Installer\Support\Database;

final class GitHubService
{
    public function upsertOwnerToken(string $owner, string $token): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('INSERT INTO github_credentials (owner_name, token_value, created_at, updated_at) VALUES (:owner_name, :token_value, NOW(), NOW()) ON DUPLICATE KEY UPDATE token_value = VALUES(token_value), updated_at = NOW()');
        $stmt->execute([
            ':owner_name' => $owner,
            ':token_value' => $token,
        ]);
    }

    public function getOwnerToken(string $owner): ?string
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT token_value FROM github_credentials WHERE owner_name = :owner_name LIMIT 1');
        $stmt->execute([':owner_name' => $owner]);
        $row = $stmt->fetch();
        return is_array($row) ? (string) $row['token_value'] : null;
    }

    public function syncReposForOwner(string $owner): array
    {
        $token = $this->getOwnerToken($owner);
        if ($token === null || $token === '') {
            throw new \RuntimeException('Kein GitHub-Token fuer Owner hinterlegt: ' . $owner);
        }

        $repos = $this->fetchRepos($owner, $token);
        $pdo = Database::pdo();

        foreach ($repos as $repo) {
            $stmt = $pdo->prepare('INSERT INTO repositories (owner_name, repo_name, full_name, is_private, default_branch, is_active, created_at, updated_at) VALUES (:owner_name, :repo_name, :full_name, :is_private, :default_branch, 1, NOW(), NOW()) ON DUPLICATE KEY UPDATE is_private = VALUES(is_private), default_branch = VALUES(default_branch), updated_at = NOW()');
            $stmt->execute([
                ':owner_name' => (string) $repo['owner']['login'],
                ':repo_name' => (string) $repo['name'],
                ':full_name' => (string) $repo['full_name'],
                ':is_private' => 1,
                ':default_branch' => (string) ($repo['default_branch'] ?? 'main'),
            ]);
        }

        return $repos;
    }

    public function fetchAllowedPackagesForUser(int $userId): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT r.id, r.owner_name, r.repo_name, r.full_name, r.default_branch
            FROM repositories r
            INNER JOIN repository_access ra ON ra.repository_id = r.id
            WHERE ra.user_id = :user_id AND r.is_active = 1 AND r.is_private = 1
            ORDER BY r.owner_name, r.repo_name');
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchReleases(string $owner, string $repo, string $token): array
    {
        $url = rtrim((string) Config::get('github.api_base', 'https://api.github.com'), '/') . '/repos/' . rawurlencode($owner) . '/' . rawurlencode($repo) . '/releases?per_page=50';
        return $this->requestJson($url, $token);
    }

    public function streamZipball(string $owner, string $repo, string $ref, string $token): void
    {
        $url = 'https://api.github.com/repos/' . rawurlencode($owner) . '/' . rawurlencode($repo) . '/zipball/' . rawurlencode($ref);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'Accept: application/vnd.github+json',
                'Authorization: Bearer ' . $token,
                'User-Agent: KLXM-Installer-Proxy',
            ],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HEADER => false,
            CURLOPT_WRITEFUNCTION => static function ($ch, $data) {
                echo $data;
                return strlen($data);
            },
        ]);

        $result = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($result === false || $status >= 400) {
            throw new \RuntimeException('Download von GitHub fehlgeschlagen.');
        }
    }

    private function fetchRepos(string $owner, string $token): array
    {
        $base = rtrim((string) Config::get('github.api_base', 'https://api.github.com'), '/');
        $userReposUrl = $base . '/user/repos?visibility=all&affiliation=owner,collaborator,organization_member&per_page=100';
        $orgUrl = $base . '/orgs/' . rawurlencode($owner) . '/repos?type=private&per_page=100';
        $userUrl = $base . '/users/' . rawurlencode($owner) . '/repos?type=owner&per_page=100';

        // Fine-grained Tokens liefern Org-Repos oft verlässlicher über /user/repos.
        try {
            $repos = $this->requestJson($userReposUrl, $token);
            $filtered = array_values(array_filter($repos, static function (array $repo) use ($owner): bool {
                $repoOwner = (string) ($repo['owner']['login'] ?? '');
                $isPrivate = (bool) ($repo['private'] ?? false);

                return strcasecmp($repoOwner, $owner) === 0 && $isPrivate;
            }));

            if ([] !== $filtered) {
                return $filtered;
            }
        } catch (\Throwable) {
            // Fallback auf orgs/users Endpunkte.
        }

        try {
            $orgRepos = $this->requestJson($orgUrl, $token);

            return array_values(array_filter($orgRepos, static function (array $repo): bool {
                return (bool) ($repo['private'] ?? false);
            }));
        } catch (\Throwable) {
            $userRepos = $this->requestJson($userUrl, $token);

            return array_values(array_filter($userRepos, static function (array $repo): bool {
                return (bool) ($repo['private'] ?? false);
            }));
        }
    }


    private function requestJson(string $url, string $token): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/vnd.github+json',
                'Authorization: Bearer ' . $token,
                'User-Agent: KLXM-Installer-Proxy',
            ],
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($body) || $status >= 400) {
            throw new \RuntimeException('GitHub API Fehler (' . $status . ').');
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('GitHub Antwort ungueltig.');
        }

        return $decoded;
    }
}
