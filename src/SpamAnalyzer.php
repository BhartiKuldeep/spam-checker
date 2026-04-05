<?php

declare(strict_types=1);

namespace SpamChecker\Src;

final class SpamAnalyzer
{
    /** @param array<string, int> $keywordWeights */
    public function __construct(
        private readonly TrainingRepository $repository,
        private readonly array $keywordWeights
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function analyze(string $message): array
    {
        $message = trim($message);

        if ($message === '') {
            return [
                'label' => 'Empty Input',
                'score' => 0,
                'confidence' => 0,
                'reasons' => ['Please enter a message to analyze.'],
                'stats' => [
                    'length' => 0,
                    'word_count' => 0,
                    'uppercase_ratio' => 0,
                    'links_found' => 0,
                    'training_samples' => $this->repository->countAll(),
                ],
            ];
        }

        $lower = $this->lower($message);
        $score = 0;
        $reasons = [];

        foreach ($this->keywordWeights as $keyword => $weight) {
            if (str_contains($lower, $keyword)) {
                $score += $weight;
                $reasons[] = sprintf('Contains suspicious keyword/phrase: "%s" (+%d)', $keyword, $weight);
            }
        }

        preg_match_all('/https?:\/\/\S+|www\.\S+/iu', $message, $links);
        $linksFound = count($links[0]);
        if ($linksFound > 0) {
            $urlScore = min(20, $linksFound * 8);
            $score += $urlScore;
            $reasons[] = sprintf('Contains %d link(s) (+%d)', $linksFound, $urlScore);
        }

        preg_match_all('/[A-Z]/', $message, $uppercaseLetters);
        preg_match_all('/[a-zA-Z]/', $message, $letters);
        $uppercaseRatio = count($letters[0]) > 0
            ? count($uppercaseLetters[0]) / count($letters[0])
            : 0.0;

        if ($uppercaseRatio > 0.35) {
            $score += 10;
            $reasons[] = 'Excessive uppercase usage (+10)';
        }

        preg_match_all('/[!?$₹€£]/u', $message, $specialMarks);
        if (count($specialMarks[0]) >= 4) {
            $score += 8;
            $reasons[] = 'Heavy urgency / money punctuation detected (+8)';
        }

        if (preg_match('/\b\d{10,}\b/u', $message) === 1) {
            $score += 6;
            $reasons[] = 'Contains unusually long number pattern (+6)';
        }

        $bayes = $this->bayesianProbability($message);
        $bayesContribution = (int) round($bayes * 35);
        $score += $bayesContribution;
        $reasons[] = sprintf('Training-based spam probability contributed +%d', $bayesContribution);

        $score = min(100, $score);
        $label = $this->classify($score);
        $confidence = $this->confidenceFromScore($score);

        return [
            'label' => $label,
            'score' => $score,
            'confidence' => $confidence,
            'reasons' => $reasons,
            'stats' => [
                'length' => $this->length($message),
                'word_count' => count($this->tokenize($message)),
                'uppercase_ratio' => round($uppercaseRatio, 3),
                'links_found' => $linksFound,
                'training_samples' => $this->repository->countAll(),
                'bayesian_spam_probability' => round($bayes, 3),
            ],
        ];
    }

    public function addFeedback(string $message, string $label): void
    {
        $label = strtolower(trim($label));
        if (!in_array($label, ['spam', 'ham'], true)) {
            return;
        }

        $message = trim($message);
        if ($message === '') {
            return;
        }

        $this->repository->insert($message, $label);
    }

    /**
     * @return list<string>
     */
    private function tokenize(string $message): array
    {
        $normalized = $this->lower($message);
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $normalized) ?: [];
        $tokens = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '' && $this->length($part) >= 2) {
                $tokens[] = $part;
            }
        }

        return $tokens;
    }


    private function lower(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }

    private function bayesianProbability(string $message): float
    {
        $samples = $this->repository->all();
        $tokens = array_unique($this->tokenize($message));

        if ($tokens === [] || $samples === []) {
            return 0.15;
        }

        $spamDocs = 0;
        $hamDocs = 0;
        $spamTokenCounts = [];
        $hamTokenCounts = [];
        $vocabulary = [];

        foreach ($samples as $sample) {
            $sampleTokens = array_unique($this->tokenize($sample['message']));
            if ($sample['label'] === 'spam') {
                $spamDocs++;
                foreach ($sampleTokens as $token) {
                    $spamTokenCounts[$token] = ($spamTokenCounts[$token] ?? 0) + 1;
                    $vocabulary[$token] = true;
                }
            } else {
                $hamDocs++;
                foreach ($sampleTokens as $token) {
                    $hamTokenCounts[$token] = ($hamTokenCounts[$token] ?? 0) + 1;
                    $vocabulary[$token] = true;
                }
            }
        }

        $totalDocs = max(1, $spamDocs + $hamDocs);
        $vocabSize = max(1, count($vocabulary));

        $pSpam = $spamDocs / $totalDocs;
        $pHam = $hamDocs / $totalDocs;

        $logSpam = log(max($pSpam, 0.0001));
        $logHam = log(max($pHam, 0.0001));

        foreach ($tokens as $token) {
            $tokenSpam = (($spamTokenCounts[$token] ?? 0) + 1) / ($spamDocs + $vocabSize);
            $tokenHam = (($hamTokenCounts[$token] ?? 0) + 1) / ($hamDocs + $vocabSize);
            $logSpam += log($tokenSpam);
            $logHam += log($tokenHam);
        }

        $maxLog = max($logSpam, $logHam);
        $spamExp = exp($logSpam - $maxLog);
        $hamExp = exp($logHam - $maxLog);

        return $spamExp / max(0.0001, $spamExp + $hamExp);
    }

    private function classify(int $score): string
    {
        return match (true) {
            $score >= 75 => 'Spam',
            $score >= 55 => 'Likely Spam',
            $score >= 35 => 'Needs Review',
            default => 'Likely Safe',
        };
    }

    private function confidenceFromScore(int $score): int
    {
        $distanceFromBoundary = abs($score - 50);
        return max(50, min(99, 50 + $distanceFromBoundary));
    }
}
