<?php

declare(strict_types=1);

namespace SpamChecker\Src;

use PDO;

final class TrainingRepository
{
    public function __construct(
        private readonly ?PDO $pdo,
        private readonly string $jsonPath
    ) {
    }

    public function countAll(): int
    {
        return count($this->all());
    }

    public function insert(string $message, string $label): void
    {
        if ($this->pdo instanceof PDO) {
            $stmt = $this->pdo->prepare('INSERT INTO training_samples (message, label) VALUES (:message, :label)');
            $stmt->execute([
                ':message' => trim($message),
                ':label' => $label,
            ]);
            return;
        }

        $rows = $this->readJson();
        $rows[] = [
            'message' => trim($message),
            'label' => $label,
        ];
        $this->writeJson($rows);
    }

    /**
     * @return array<int, array{message: string, label: string}>
     */
    public function all(): array
    {
        if ($this->pdo instanceof PDO) {
            $stmt = $this->pdo->query('SELECT message, label FROM training_samples ORDER BY id ASC');
            $rows = $stmt->fetchAll();
            return is_array($rows) ? $rows : [];
        }

        return $this->readJson();
    }

    public function seedIfEmpty(array $samples): void
    {
        if ($this->countAll() > 0) {
            return;
        }

        foreach ($samples as $sample) {
            $this->insert($sample['message'], $sample['label']);
        }
    }

    /**
     * @return array<int, array{message: string, label: string}>
     */
    private function readJson(): array
    {
        if (!is_file($this->jsonPath)) {
            return [];
        }

        $content = file_get_contents($this->jsonPath);
        if ($content === false || $content === '') {
            return [];
        }

        $rows = json_decode($content, true);
        return is_array($rows) ? $rows : [];
    }

    private function writeJson(array $rows): void
    {
        file_put_contents($this->jsonPath, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
