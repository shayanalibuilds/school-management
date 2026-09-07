<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ExamResultStatus;
use App\Models\Admin;
use App\Models\ExamResult;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\Subject;

final class ExamResultsSync
{
    /**
     * Write one subject's marks for a whole class in place (existing
     * rows are updated, never duplicated), then notify. Runs inline or
     * on the queue depending on the "queue everything" app setting.
     *
     * @param  array<string, string|float|null>  $marks
     */
    public static function execute(string $markerType, string $markerId, string $classId, string $subjectId, int $year, array $marks): int
    {
        $students = Student::query()
            ->active()
            ->where('student_class_id', $classId)
            ->get();

        $saved = 0;

        foreach ($students as $student) {
            $value = $marks[(string) $student->getKey()] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $result = ExamResult::query()->firstOrNew([
                'student_id' => $student->getKey(),
                'subject_id' => $subjectId,
                'year' => $year,
            ]);

            $result->student_class_id = $classId;
            $result->marks = (float) $value;
            $result->total_marks = 100;

            if (! $result->exists) {
                $result->status = ExamResultStatus::Draft->value;
            }

            $result->save();

            $saved++;
        }

        return $saved;
    }

    /**
     * Publish every row of a result sheet and open the 30 day
     * correction window.
     */
    public static function publish(string $classId, string $subjectId, int $year, string $publisherName): int
    {
        $publishable = ExamResult::query()
            ->where('student_class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('year', $year)
            ->count();

        if ($publishable === 0) {
            return 0;
        }

        ExamResult::query()
            ->where('student_class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('year', $year)
            ->update([
                'status' => ExamResultStatus::Published->value,
                'published_at' => now(),
            ]);

        $className = StudentClass::query()->find($classId)?->name ?? 'Unknown class';
        $subjectName = Subject::query()->find($subjectId)?->name ?? 'Unknown subject';

        PanelNotifier::examResultsPublished(
            className: $className,
            subjectName: $subjectName,
            publisherName: $publisherName,
            classId: $classId,
            subjectId: $subjectId,
        );

        return $publishable;
    }

    /**
     * Display name of whoever triggered a sync or publish.
     */
    public static function actorName(string $markerType, string $markerId): string
    {
        $actor = $markerType === 'staff'
            ? Staff::query()->find($markerId)
            : Admin::query()->find($markerId);

        if ($actor === null) {
            return 'Staff';
        }

        return (string) $actor->name;
    }
}
