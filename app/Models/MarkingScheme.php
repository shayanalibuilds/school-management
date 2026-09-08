<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MarkingScheme extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The bounds used when the admin has not configured a scheme for a
     * class + subject pair: the legacy full-percent scale.
     *
     * @return array{min: float, max: float}
     */
    public const array DEFAULT_BOUNDS = ['min' => 0.0, 'max' => 100.0];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'student_class_id',
        'subject_id',
        'min_marks',
        'max_marks',
    ];

    /**
     * Mark limits for one class + subject pair, falling back to the
     * default 0 - 100 scale when the admin has not configured one.
     *
     * @return array{min: float, max: float}
     */
    public static function boundsFor(string $classId, string $subjectId): array
    {
        $scheme = self::query()
            ->where('student_class_id', $classId)
            ->where('subject_id', $subjectId)
            ->first();

        if ($scheme === null) {
            return self::DEFAULT_BOUNDS;
        }

        return [
            'min' => (float) $scheme->min_marks,
            'max' => (float) $scheme->max_marks,
        ];
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
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'min_marks' => 'float',
            'max_marks' => 'float',
        ];
    }
}
