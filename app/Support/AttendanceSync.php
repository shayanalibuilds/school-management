<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\AttendanceStatus;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentClass;
use Illuminate\Support\Facades\DB;

final class AttendanceSync
{
    /**
     * Write a whole class's attendance for today, then tell every
     * admin's devices. Runs inline or on the queue depending on the
     * "queue everything" app setting.
     *
     * @param  array<string, string>  $statuses
     */
    public static function execute(string $markerType, string $markerId, string $classId, array $statuses): int
    {
        $class = StudentClass::query()->find($classId);

        if ($class === null) {
            return 0;
        }

        $students = Student::query()
            ->active()
            ->where('student_class_id', $classId)
            ->get();

        // One transaction per class: SQLite serialises writers, so two
        // simultaneous submissions (queued job + inline save, admin +
        // staff) can never interleave between lookup and insert.
        DB::transaction(function () use ($students, $statuses, $classId, $markerType, $markerId): void {
            foreach ($students as $student) {
                $studentId = (string) $student->getKey();
                $status = $statuses[$studentId] ?? AttendanceStatus::Present->value;

                Attendance::updateOrCreateForDay($studentId, today()->toDateString(), [
                    'student_class_id' => $classId,
                    'staff_id' => $markerType === 'staff' ? $markerId : null,
                    'admin_id' => $markerType === 'admin' ? $markerId : null,
                    'status' => $status,
                ]);
            }
        });

        $marker = $markerType === 'staff'
            ? Staff::query()->find($markerId)
            : Admin::query()->find($markerId);

        $markerName = $marker === null ? null : (string) $marker->name;

        PanelNotifier::attendanceFilled($class->name, $markerName ?? 'Staff');

        return $students->count();
    }
}
