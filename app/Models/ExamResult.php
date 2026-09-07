<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExamResultStatus;
use App\Support\Grades;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

final class ExamResult extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * How long after publishing results stay correctable. Parents can
     * report mistakes and the teacher can fix them within this window;
     * afterwards the results are locked for good.
     */
    final public const RECHECK_WINDOW_DAYS = 30;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'student_id',
        'student_class_id',
        'subject_id',
        'year',
        'marks',
        'total_marks',
        'status',
        'published_at',
    ];

    /**
     * The moment the correction window closes for a whole
     * class + subject + year result sheet, or null when none of
     * its rows have ever been published.
     */
    public static function recheckWindowFor(string $classId, string $subjectId, int $year): ?CarbonInterface
    {
        $publishedAt = self::query()
            ->where('student_class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('year', $year)
            ->whereNotNull('published_at')
            ->max('published_at');

        if ($publishedAt === null) {
            return null;
        }

        return Carbon::parse((string) $publishedAt)->addDays(self::RECHECK_WINDOW_DAYS);
    }

    /**
     * Class + subject sheets for the year that have no results recorded
     * yet - i.e. exams that are still unchecked. A class counts as
     * checked once every subject assigned to it has at least one result
     * for the year; classes without active students are skipped because
     * there is nobody to examine.
     *
     * @return list<string> e.g. ['Class 1 - Mathematics']
     */
    public static function uncheckedSheets(int $year): array
    {
        $missing = [];

        $classes = StudentClass::query()
            ->with('subjects')
            ->orderBy('name')
            ->get();

        foreach ($classes as $class) {
            $hasActiveStudents = $class->students()
                ->where('status', 'active')
                ->exists();

            if (! $hasActiveStudents) {
                continue;
            }

            foreach ($class->subjects as $subject) {
                $hasResults = self::query()
                    ->where('student_class_id', $class->getKey())
                    ->where('subject_id', $subject->getKey())
                    ->where('year', $year)
                    ->exists();

                if (! $hasResults) {
                    $missing[] = $class->name.' - '.$subject->name;
                }
            }
        }

        return $missing;
    }

    /**
     * Whether every class has checked exams for the year. The global
     * publish button stays disabled until this is true.
     */
    public static function allClassesChecked(int $year): bool
    {
        return self::uncheckedSheets($year) === [];
    }

    /**
     * Whether a whole result sheet is locked (published more than
     * 30 days ago and therefore no longer editable anywhere).
     */
    public static function sheetIsLocked(string $classId, string $subjectId, int $year): bool
    {
        $windowEndsAt = self::recheckWindowFor($classId, $subjectId, $year);

        return $windowEndsAt !== null && $windowEndsAt->isPast();
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
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Only published results; drafts never leak to students or public pages.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopePublished($query)
    {
        return $query->where('status', ExamResultStatus::Published->value);
    }

    /**
     * Grade derived from marks against total marks.
     */
    public function grade(): string
    {
        return Grades::fromMarks($this->marks, $this->total_marks);
    }

    /**
     * When the 30-day correction window for this result closes,
     * or null while the result is still a draft.
     */
    public function recheckWindowEndsAt(): ?CarbonInterface
    {
        $publishedAt = $this->published_at;

        if (! $publishedAt instanceof CarbonInterface) {
            return null;
        }

        return $publishedAt->copy()->addDays(self::RECHECK_WINDOW_DAYS);
    }

    /**
     * Drafts are always editable; published results stay editable
     * until the recheck window closes.
     */
    public function isEditable(): bool
    {
        $endsAt = $this->recheckWindowEndsAt();

        return $endsAt === null || $endsAt->isFuture();
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'marks' => 'float',
            'total_marks' => 'float',
            'year' => 'integer',
            'status' => ExamResultStatus::class,
            'published_at' => 'datetime',
        ];
    }
}
