<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/helpers.php';

$analyzer = app_analyzer();
$message = isset($_POST['message']) ? trim((string) $_POST['message']) : '';
$result = null;
$feedbackStatus = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['analyze'])) {
        $result = $analyzer->analyze($message);
    }

    if (isset($_POST['feedback_label'])) {
        $feedbackLabel = strtolower(trim((string) $_POST['feedback_label']));
        if ($message !== '' && in_array($feedbackLabel, ['spam', 'ham'], true)) {
            $analyzer->addFeedback($message, $feedbackLabel);
            $result = $analyzer->analyze($message);
            $feedbackStatus = 'Feedback saved. The analyzer has learned from your input.';
        }
    }
}

function badge_class(?array $result): string
{
    if ($result === null) {
        return 'badge badge-neutral';
    }

    return match ($result['label']) {
        'Spam' => 'badge badge-danger',
        'Likely Spam' => 'badge badge-warning',
        'Needs Review' => 'badge badge-review',
        default => 'badge badge-safe',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spam Checker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="hero card">
        <div>
            <p class="eyebrow">PHP Project</p>
            <h1>Spam Checker</h1>
            <p class="subtext">Analyze emails, messages, or notification text using a lightweight hybrid spam detection engine.</p>
        </div>
        <div class="hero-stats">
            <div class="stat-box">
                <span>Training Samples</span>
                <strong><?= htmlspecialchars((string) app_analyzer()->analyze('hello')['stats']['training_samples']) ?></strong>
            </div>
            <div class="stat-box">
                <span>Mode</span>
                <strong>Rule + Bayesian</strong>
            </div>
        </div>
    </div>

    <div class="grid">
        <section class="card">
            <h2>Analyze Message</h2>
            <form method="post">
                <label for="message">Paste text</label>
                <textarea id="message" name="message" rows="12" placeholder="Paste an email, SMS, or suspicious message here..."><?= htmlspecialchars($message) ?></textarea>

                <div class="button-row">
                    <button type="submit" name="analyze" value="1">Analyze</button>
                </div>
            </form>
        </section>

        <aside class="card">
            <h2>How scoring works</h2>
            <ul class="feature-list">
                <li>Suspicious phrases like <code>free</code>, <code>winner</code>, <code>claim</code></li>
                <li>Links, urgency patterns, money symbols, uppercase abuse</li>
                <li>Light training system using stored spam/ham examples</li>
                <li>Feedback buttons improve future checks</li>
            </ul>
        </aside>
    </div>

    <?php if ($feedbackStatus !== null): ?>
        <div class="notice success"><?= htmlspecialchars($feedbackStatus) ?></div>
    <?php endif; ?>

    <?php if ($result !== null): ?>
        <section class="card result-card">
            <div class="result-header">
                <h2>Analysis Result</h2>
                <span class="<?= badge_class($result) ?>"><?= htmlspecialchars($result['label']) ?></span>
            </div>

            <div class="result-grid">
                <div class="metric">
                    <span>Spam Score</span>
                    <strong><?= htmlspecialchars((string) $result['score']) ?>/100</strong>
                </div>
                <div class="metric">
                    <span>Confidence</span>
                    <strong><?= htmlspecialchars((string) $result['confidence']) ?>%</strong>
                </div>
                <div class="metric">
                    <span>Words</span>
                    <strong><?= htmlspecialchars((string) $result['stats']['word_count']) ?></strong>
                </div>
                <div class="metric">
                    <span>Links</span>
                    <strong><?= htmlspecialchars((string) $result['stats']['links_found']) ?></strong>
                </div>
            </div>

            <h3>Reasons</h3>
            <ul class="reason-list">
                <?php foreach ($result['reasons'] as $reason): ?>
                    <li><?= htmlspecialchars((string) $reason) ?></li>
                <?php endforeach; ?>
            </ul>

            <div class="feedback-box">
                <h3>Was this result correct?</h3>
                <form method="post" class="inline-form">
                    <input type="hidden" name="message" value="<?= htmlspecialchars($message) ?>">
                    <button type="submit" name="feedback_label" value="spam" class="secondary danger">Mark as Spam</button>
                    <button type="submit" name="feedback_label" value="ham" class="secondary safe">Mark as Safe</button>
                </form>
            </div>
        </section>
    <?php endif; ?>
</div>
</body>
</html>
