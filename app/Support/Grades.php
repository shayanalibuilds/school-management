<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\GradingScale;
use Illuminate\Support\Facades\Cache;

final class Grades
{
    public const string CACHE_KEY = 'grading_scale';

    /**
     * Built-in grade boundaries used while the admin has not configured
     * a custom grading scale: minimum percentage required for each grade.
     *
     * @var array<string, float>
     */
    private const array BOUNDARIES = [
        'A+' => 90.0,
        'A' => 80.0,
        'B' => 70.0,
        'C' => 60.0,
        'D' => 50.0,
    ];

    public static function fromMarks(float|int $marks, float|int $totalMarks): string
    {
        if ($totalMarks <= 0) {
            return 'F';
        }

        $percentage = ($marks / $totalMarks) * 100;

        foreach (self::boundaries() as $grade => $minimum) {
            if ($percentage >= $minimum) {
                return $grade;
            }
        }

        return 'F';
    }

    /**
     * Grade boundaries currently in force, highest threshold first.
     * Rows come from the admin-editable grading scale; while the scale
     * is empty the built-in boundaries apply. Marks below the lowest
     * threshold always grade F.
     *
     * @return array<string, float> grade name => minimum percentage
     */
    public static function boundaries(): array
    {
        /** @var array<string, float> $rows */
        $rows = Cache::remember(self::CACHE_KEY, now()->addMinutes(5), fn (): array => GradingScale::query()
            ->orderByDesc('min_percentage')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (GradingScale $scale): array => [
                $scale->name => (float) $scale->min_percentage,
            ])
            ->all());

        return $rows === [] ? self::BOUNDARIES : $rows;
    }

    /**
     * Drop the cached boundaries so admin edits take effect at once.
     */
    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
