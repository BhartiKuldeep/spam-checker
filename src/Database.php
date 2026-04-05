<?php

declare(strict_types=1);

namespace SpamChecker\Src;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private PDO $pdo;

    public function __construct(string $dbPath)
    {
        try {
            $this->pdo = new PDO('sqlite:' . $dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to connect to SQLite database: ' . $e->getMessage());
        }
    }

    public function connection(): PDO
    {
        return $this->pdo;
    }

    public function migrate(): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS training_samples (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            message TEXT NOT NULL,
            label TEXT NOT NULL CHECK(label IN ('spam', 'ham')),
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        SQL;

        $this->pdo->exec($sql);
    }
}
