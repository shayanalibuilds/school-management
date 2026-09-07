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

final class Guardian extends Model
{
    use ArchivesInsteadOfDeleting;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'guardians';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'cnic',
        'phone',
        'relation',
    ];

    /**
     * @return BelongsToMany<Student, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'guardian_student', 'guardian_id', 'student_id');
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
