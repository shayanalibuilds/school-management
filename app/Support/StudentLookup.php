<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\StudentStatus;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentParent;
use Illuminate\Support\Collection;

final class StudentLookup
{
    /**
     * Resolve the students a public visitor may see for one identifier:
     * a parent/guardian CNIC or a student GR #.
     * Dashes and spaces are ignored, and only active students are returned.
     *
     * @return Collection<int, Student>
     */
    public static function resolve(?string $identifier): Collection
    {
        $normalized = preg_replace('/[\s\-]+/', '', (string) $identifier);

        if ($normalized === null || $normalized === '') {
            return collect();
        }

        $students = self::byGrNo($normalized)
            ->merge(self::byCnic(StudentParent::class, $normalized))
            ->merge(self::byCnic(Guardian::class, $normalized));

        return $students
            ->filter(fn (Student $student): bool => $student->status === StudentStatus::Active)
            ->unique('id')
            ->sortBy('gr_no')
            ->values();
    }

    /**
     * GR # is a free-form string, so it is matched the same way it is
     * typed: case-insensitive, ignoring dashes and spaces.
     *
     * @return Collection<int, Student>
     */
    private static function byGrNo(string $identifier): Collection
    {
        /** @var Collection<int, Student> */
        return Student::query()
            ->whereRaw('REPLACE(REPLACE(LOWER(gr_no), \'-\', \'\'), \' \', \'\') = ?', [mb_strtolower($identifier)])
            ->get();
    }

    /**
     * @param  class-string<StudentParent|Guardian>  $modelClass
     * @return Collection<int, Student>
     */
    private static function byCnic(string $modelClass, string $identifier): Collection
    {
        /** @var Collection<int, StudentParent|Guardian> $records */
        $records = $modelClass::query()
            ->whereRaw("REPLACE(REPLACE(cnic, '-', ''), ' ', '') = ?", [$identifier])
            ->with('students')
            ->get();

        return $records
            ->flatMap(fn (StudentParent|Guardian $record): Collection => $record->students)
            ->values();
    }
}
