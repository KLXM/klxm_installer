<?php

declare(strict_types=1);

namespace Klxm\Installer\Support;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $connections = [];
        $connections[] = [
            'host' => (string) Config::get('db.host'),
            'port' => (int) Config::get('db.port', 3306),
            'database' => (string) Config::get('db.database'),
            'username' => (string) Config::get('db.username'),
            'password' => (string) Config::get('db.password'),
            'charset' => (string) Config::get('db.charset', 'utf8mb4'),
        ];

        $fallbacks = Config::get('db_fallbacks', []);
        if (is_array($fallbacks)) {
            foreach ($fallbacks as $fallback) {
                if (!is_array($fallback)) {
                    continue;
                }
                $connections[] = [
                    'host' => (string) ($fallback['host'] ?? ''),
                    'port' => (int) ($fallback['port'] ?? 3306),
                    'database' => (string) ($fallback['database'] ?? ''),
                    'username' => (string) ($fallback['username'] ?? ''),
                    'password' => (string) ($fallback['password'] ?? ''),
                    'charset' => (string) ($fallback['charset'] ?? 'utf8mb4'),
                ];
            }
        }

        $lastError = null;
        foreach ($connections as $conn) {
            if ($conn['host'] === '' || $conn['database'] === '' || $conn['username'] === '') {
                continue;
            }

            try {
                $pdo = self::connect($conn);
                if (!self::hasTable($pdo, 'users')) {
                    throw new \RuntimeException('Installer-Schema fehlt (Tabelle users) in ' . $conn['host'] . '/' . $conn['database']);
                }

                self::$pdo = $pdo;
                return self::$pdo;
            } catch (\Throwable $e) {
                $lastError = $e;
            }
        }

        if ($lastError instanceof \Throwable) {
            throw new \RuntimeException('Keine gueltige Installer-Datenbank gefunden. Bitte /installer/install.php ausfuehren oder DB-Konfiguration anpassen. Letzter Fehler: ' . $lastError->getMessage(), 0, $lastError);
        }

        throw new \RuntimeException('Keine gueltige Datenbankverbindung konfiguriert.');
    }

    /**
     * @param array{host:string,port:int,database:string,username:string,password:string,charset:string} $conn
     */
    private static function connect(array $conn): PDO
    {
        $dsn = "mysql:host={$conn['host']};port={$conn['port']};dbname={$conn['database']};charset={$conn['charset']}";

        return new PDO($dsn, $conn['username'], $conn['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    private static function hasTable(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table LIMIT 1');
        $stmt->execute([':table' => $table]);

        return (bool) $stmt->fetchColumn();
    }
}
