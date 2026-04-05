<?php

declare(strict_types=1);

use SpamChecker\Src\Config;
use SpamChecker\Src\Database;
use SpamChecker\Src\SpamAnalyzer;
use SpamChecker\Src\TrainingRepository;

spl_autoload_register(function (string $class): void {
    $prefix = 'SpamChecker\\Src\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

function app_analyzer(): SpamAnalyzer
{
    static $analyzer = null;

    if ($analyzer instanceof SpamAnalyzer) {
        return $analyzer;
    }

    $pdo = null;

    if (extension_loaded('pdo_sqlite')) {
        try {
            $database = new Database(Config::DB_PATH);
            $database->migrate();
            $pdo = $database->connection();
        } catch (Throwable $e) {
            $pdo = null;
        }
    }

    $repository = new TrainingRepository($pdo, Config::JSON_DATA_PATH);
    $repository->seedIfEmpty(Config::seedTrainingData());

    $analyzer = new SpamAnalyzer($repository, Config::spamKeywords());
    return $analyzer;
}

function json_response(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
