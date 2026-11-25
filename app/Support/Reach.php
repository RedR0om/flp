<?php

namespace App\Support;

final class Reach
{
    public static function rate(?int $learners, ?int $reached): ?float
    {
        if ($learners === null || $learners <= 0 || $reached === null) {
            return null;
        }

        return round($reached / $learners * 100, 1);
    }

    public static function band(?float $rate): string
    {
        return match (true) {
            $rate === null => 'none',
            $rate >= 95 => 'ex',
            $rate >= 90 => 'ok',
            $rate >= 80 => 'na',
            $rate >= 70 => 'hp',
            default => 'cr',
        };
    }

    public static function label(string $band): string
    {
        return match ($band) {
            'ex' => 'Excellent / Fully Reached',
            'ok' => 'On Track',
            'na' => 'Needs Attention',
            'hp' => 'High Priority',
            'cr' => 'Critical',
            default => 'No data yet',
        };
    }

    public static function isIssue(string $band): bool
    {
        return in_array($band, ['na', 'hp', 'cr'], true);
    }
}
