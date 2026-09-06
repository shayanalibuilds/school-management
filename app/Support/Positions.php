<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\ExamResult;
use App\Models\StudentClass;
use Illuminate\Support\Collection;

final class Positions
{
    /**
     * Standard competition ranking (1, 2, 2, 4) for a class in a given year,
     * based on total marks across all subjects.
     *
     * @return Collection<int, int> student id => position
     */
    public static function forClass(StudentClass $class, int $year): Collection
    {
        $totals = ExamResult::query()
            ->where('student_class_id', $class->getKey())
            ->where('year', $year)
            ->selectRaw('student_id, SUM(marks) as total_marks')
            ->groupBy('student_id')
            ->orderByDesc('total_marks')
            ->pluck('total_marks', 'student_id');

        $positions = collect();
        $previousTotal = null;
        $previousPosition = 0;

        $rank = 0;

        foreach ($totals as $studentId => $total) {
            $rank++;

            if ($previousTotal !== null && (float) $total === (float) $previousTotal) {
                $positions->put($studentId, $previousPosition);

                continue;
            }

            $positions->put($studentId, $rank);
            $previousTotal = $total;
            $previousPosition = $rank;
        }

        return $positions;
    }

    /**
     * Ordinal label for a position, e.g. 1 => 1st.
     */
    public static function label(int $position): string
    {
        $suffix = match (true) {
            $position % 100 >= 11 && $position % 100 <= 13 => 'th',
            $position % 10 === 1 => 'st',
            $position % 10 === 2 => 'nd',
            $position % 10 === 3 => 'rd',
            default => 'th',
        };

        return $position.$suffix;
    }
}
