<?php

declare(strict_types=1);

namespace App\Support;

final class Grades
{
    /**
     * Grade boundaries: minimum percentage required for each grade.
     *
     * @var array<string, float>
     */
    private const BOUNDARIES = [
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

        foreach (self::BOUNDARIES as $grade => $minimum) {
            if ($percentage >= $minimum) {
                return $grade;
            }
        }

        return 'F';
    }
}
