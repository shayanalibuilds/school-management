<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Student;

final class StudentObserver
{
    /**
     * Assign the next sequential serial number when none is given.
     */
    public function creating(Student $student): void
    {
        if ($student->sr_no !== null) {
            return;
        }

        $student->sr_no = (int) Student::query()->max('sr_no') + 1;
    }
}
