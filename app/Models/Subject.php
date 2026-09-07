<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RecordStatus;
use App\Models\Concerns\ArchivesInsteadOfDeleting;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Subject extends Model
{
    use ArchivesInsteadOfDeleting;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * @return BelongsToMany<StudentClass, $this>
     */
    public function studentClasses(): BelongsToMany
    {
        return $this->belongsToMany(StudentClass::class, 'class_subject', 'subject_id', 'student_class_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<StaffAssignment, $this>
     */
    public function staffAssignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StaffAssignment::class, 'subject_id');
    }

    protected function archiveStatus(): string
    {
        return RecordStatus::Inactive->value;
    }

    protected function activeStatus(): string
    {
        return RecordStatus::Active->value;
    }
}
