<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Admin;
use App\Models\StaffAssignment;
use App\Notifications\AttendanceFilledNotification;
use App\Notifications\ExamResultsPublishedNotification;
use Illuminate\Support\Collection;

final class PanelNotifier
{
    /**
     * Tell every admin (on all their devices) that a class's
     * attendance for today has been submitted.
     */
    public static function attendanceFilled(string $className, string $markerName): void
    {
        $admins = Admin::query()->get();

        if ($admins->isEmpty()) {
            return;
        }

        foreach ($admins as $admin) {
            $admin->notify(new AttendanceFilledNotification($className, $markerName));
        }
    }

    /**
     * Tell every admin plus the teachers assigned to the class +
     * subject that a result sheet has just been published.
     */
    public static function examResultsPublished(string $className, string $subjectName, string $publisherName, string $classId, string $subjectId): void
    {
        $recipients = self::adminsAndAssignedStaff($classId, $subjectId);

        if ($recipients->isEmpty()) {
            return;
        }

        foreach ($recipients as $recipient) {
            $recipient->notify(new ExamResultsPublishedNotification($className, $subjectName, $publisherName));
        }
    }

    /**
     * All admins plus every staff member assigned to teach the given
     * class + subject, deduplicated.
     *
     * @return Collection<int, Admin|App\Models\Staff>
     */
    private static function adminsAndAssignedStaff(string $classId, string $subjectId): Collection
    {
        $assignedStaff = StaffAssignment::query()
            ->where('student_class_id', $classId)
            ->where('subject_id', $subjectId)
            ->with('staff')
            ->get()
            ->pluck('staff')
            ->filter();

        return Admin::query()->get()->merge($assignedStaff);
    }
}
