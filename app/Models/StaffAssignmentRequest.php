<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AssignmentAction;
use App\Enums\AssignmentRequestStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StaffAssignmentRequest extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'staff_id',
        'student_class_id',
        'subject_id',
        'action',
        'target_staff_assignment_id',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_note',
    ];

    /**
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
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
     * @return BelongsTo<StaffAssignment, $this>
     */
    public function targetAssignment(): BelongsTo
    {
        return $this->belongsTo(StaffAssignment::class, 'target_staff_assignment_id');
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    public function approve(Admin $admin): void
    {
        if ($this->status !== AssignmentRequestStatus::Pending) {
            return;
        }

        if ($this->action === AssignmentAction::Add) {
            // A class + subject pair can only ever have one teacher;
            // only create when the pair is still unowned.
            $pairTaken = StaffAssignment::query()
                ->where('student_class_id', $this->student_class_id)
                ->where('subject_id', $this->subject_id)
                ->exists();

            if (! $pairTaken) {
                StaffAssignment::query()->create([
                    'staff_id' => $this->staff_id,
                    'student_class_id' => $this->student_class_id,
                    'subject_id' => $this->subject_id,
                ]);
            }
        }

        if ($this->action === AssignmentAction::Remove) {
            $this->targetAssignment()->first()?->delete();
        }

        $this->update([
            'status' => AssignmentRequestStatus::Approved,
            'reviewed_by' => $admin->getKey(),
            'reviewed_at' => now(),
        ]);
    }

    public function reject(Admin $admin, ?string $note = null): void
    {
        if ($this->status !== AssignmentRequestStatus::Pending) {
            return;
        }

        $this->update([
            'status' => AssignmentRequestStatus::Rejected,
            'reviewed_by' => $admin->getKey(),
            'reviewed_at' => now(),
            'admin_note' => $note,
        ]);
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'action' => AssignmentAction::class,
            'status' => AssignmentRequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }
}
