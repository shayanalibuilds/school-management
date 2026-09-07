<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TimetableSlot extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'student_class_id',
        'subject_id',
        'day_of_week',
        'start_time',
        'end_time',
        'room',
    ];

    /**
     * @return array<int, string>
     */
    public static function dayLabels(): array
    {
        return [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];
    }

    /**
     * Whether another slot already occupies this time range for the same
     * class, or for the same teacher. Schools have multiple rooms, so the
     * same subject may run for several classes at once - but a class
     * cannot sit two subjects at the same time, and one teacher cannot
     * teach two classes at the same time.
     */
    public static function findConflict(string $classId, string $subjectId, int $dayOfWeek, string $startTime, string $endTime, ?string $ignoreId = null): ?self
    {
        $classConflict = self::query()
            ->where('day_of_week', $dayOfWeek)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->where('student_class_id', $classId)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->first();

        if ($classConflict !== null) {
            return $classConflict;
        }

        // The teacher who would take this new slot (from the class +
        // subject assignment). Without an assigned teacher there is no
        // one to double-book.
        $staffAssignment = StaffAssignment::query()
            ->where('student_class_id', $classId)
            ->where('subject_id', $subjectId)
            ->first();

        if ($staffAssignment === null) {
            return null;
        }

        $overlapping = self::query()
            ->where('day_of_week', $dayOfWeek)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->get();

        foreach ($overlapping as $slot) {
            $slotTeacherId = StaffAssignment::query()
                ->where('student_class_id', $slot->student_class_id)
                ->where('subject_id', $slot->subject_id)
                ->value('staff_id');

            if ($slotTeacherId !== null && (string) $slotTeacherId === (string) $staffAssignment->staff_id) {
                return $slot;
            }
        }

        return null;
    }

    public function dayLabel(): string
    {
        return self::dayLabels()[$this->day_of_week] ?? (string) $this->day_of_week;
    }

    /**
     * @return BelongsTo<StudentClass, $this>
     */
    public function studentClass(): BelongsTo
    {
        return $this->belongsTo(StudentClass::class);
    }

    /**
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * The teacher who teaches this class + subject pair, if assigned.
     */
    public function teacher(): ?Staff
    {
        return Staff::query()
            ->whereHas('assignments', fn ($query) => $query
                ->where('student_class_id', $this->student_class_id)
                ->where('subject_id', $this->subject_id))
            ->first();
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
        ];
    }
}
