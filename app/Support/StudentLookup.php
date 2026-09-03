<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentParent;
use Illuminate\Support\Collection;

final class StudentLookup
{
    /**
     * Resolve the students reachable through a parent/guardian CNIC +
     * matching phone number. Both values must belong to the same record,
     * so guessing a CNIC alone reveals nothing.
     *
     * @return Collection<int, Student>
     */
    public static function resolve(string $cnic, string $phone): Collection
    {
        $cnic = mb_trim($cnic);
        $phone = mb_trim($phone);

        if ($cnic === '' || $phone === '') {
            return collect();
        }

        $students = StudentParent::query()
            ->where('cnic', $cnic)
            ->where('phone', $phone)
            ->with('students')
            ->get()
            ->flatMap(fn (StudentParent $parent) => $parent->students)
            ->merge(
                Guardian::query()
                    ->where('cnic', $cnic)
                    ->where('phone', $phone)
                    ->with('students')
                    ->get()
                    ->flatMap(fn (Guardian $guardian) => $guardian->students),
            )
            ->unique('id')
            ->values();

        return $students;
    }
}
