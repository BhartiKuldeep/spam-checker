<?php

declare(strict_types=1);

namespace SpamChecker\Src;

final class Config
{
    public const DB_PATH = __DIR__ . '/../data/spam_checker.sqlite';
    public const JSON_DATA_PATH = __DIR__ . '/../data/training_samples.json';

    /**
     * Base words that strongly indicate spam.
     *
     * @return array<string, int>
     */
    public static function spamKeywords(): array
    {
        return [
            'free' => 12,
            'winner' => 14,
            'won' => 10,
            'congratulations' => 12,
            'urgent' => 9,
            'limited' => 8,
            'offer' => 8,
            'click' => 10,
            'claim' => 10,
            'credit' => 8,
            'loan' => 11,
            'guaranteed' => 12,
            'prize' => 14,
            'bonus' => 9,
            'cash' => 10,
            'money' => 8,
            'investment' => 9,
            'crypto' => 9,
            'bitcoin' => 8,
            'viagra' => 18,
            'casino' => 16,
            'cheap' => 7,
            'risk-free' => 12,
            'act now' => 10,
            'buy now' => 9,
            'no cost' => 10,
            'refund' => 6,
            'verify account' => 16,
            'bank account' => 16,
            'password' => 7,
            'otp' => 9,
            'login now' => 12,
        ];
    }

    /**
     * @return array<int, array{message: string, label: string}>
     */
    public static function seedTrainingData(): array
    {
        return [
            ['message' => 'Congratulations, you have won a free cash prize. Click here now.', 'label' => 'spam'],
            ['message' => 'Urgent! Your bank account needs verification immediately.', 'label' => 'spam'],
            ['message' => 'Limited time loan offer with guaranteed approval.', 'label' => 'spam'],
            ['message' => 'Get cheap medicines online with free delivery.', 'label' => 'spam'],
            ['message' => 'You are selected as a lucky winner. Claim your reward today.', 'label' => 'spam'],
            ['message' => 'Team meeting moved to 4 PM. Please join on time.', 'label' => 'ham'],
            ['message' => 'Can you review the attached project document?', 'label' => 'ham'],
            ['message' => 'Your order has been shipped and will arrive tomorrow.', 'label' => 'ham'],
            ['message' => 'Lunch at 1 PM? Let me know if that works.', 'label' => 'ham'],
            ['message' => 'Please reset your password using the internal portal.', 'label' => 'ham'],
        ];
    }
}
