<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(\App\Observers\StudentObserver::class)]
final class Student extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sr_no',
        'name',
        'student_class_id',
        'joining_date',
        'leaving_date',
        'status',
    ];

    /**
     * @return BelongsTo<StudentClass, $this>
     */
    public function studentClass(): BelongsTo
    {
        return $this->belongsTo(StudentClass::class);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Student>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Student>
     */
    public function scopeActive($query)
    {
        return $query->where('status', StudentStatus::Active);
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'leaving_date' => 'date',
            'status' => StudentStatus::class,
        ];
    }
}
