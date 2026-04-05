<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/helpers.php';

$action = $_GET['action'] ?? 'analyze';
$rawBody = file_get_contents('php://input') ?: '';
$data = json_decode($rawBody, true);
$data = is_array($data) ? $data : $_POST;

$analyzer = app_analyzer();

if ($action === 'feedback') {
    $message = (string) ($data['message'] ?? '');
    $label = strtolower(trim((string) ($data['label'] ?? '')));

    if ($message === '' || !in_array($label, ['spam', 'ham'], true)) {
        json_response([
            'success' => false,
            'message' => 'Valid message and label (spam/ham) are required.',
        ], 422);
    }

    $analyzer->addFeedback($message, $label);

    json_response([
        'success' => true,
        'message' => 'Feedback saved successfully.',
    ]);
}

$message = (string) ($data['message'] ?? '');
$result = $analyzer->analyze($message);

json_response([
    'success' => true,
    'input' => $message,
    'result' => $result,
]);
