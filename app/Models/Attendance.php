<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Attendance extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'student_id',
        'student_class_id',
        'staff_id',
        'admin_id',
        'date',
        'status',
    ];

    /**
     * Upsert one student's attendance for a given day. The lookup uses
     * whereDate() so it matches however the storage engine persists the
     * date ('2026-09-06' vs '2026-09-06 00:00:00') and never spawns a
     * duplicate row for the same student + day.
     *
     * @param  array<string, mixed>  $values
     */
    public static function updateOrCreateForDay(int|string $studentId, string $date, array $values): self
    {
        $attendance = self::query()
            ->where('student_id', $studentId)
            ->whereDate('date', $date)
            ->first();

        if ($attendance === null) {
            $attendance = new self();
            $attendance->student_id = $studentId;
            $attendance->date = $date;
        }

        foreach ($values as $key => $value) {
            $attendance->{$key} = $value;
        }

        $attendance->save();

        return $attendance;
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<StudentClass, $this>
     */
    public function studentClass(): BelongsTo
    {
        return $this->belongsTo(StudentClass::class);
    }

    /**
     * @return BelongsTo<Staff, $this>
     */
    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function markedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    /**
     * Who recorded the attendance, whichever panel it came from.
     */
    public function markerName(): ?string
    {
        return $this->markedBy?->name ?? $this->markedByAdmin?->name;
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => AttendanceStatus::class,
        ];
    }
}
