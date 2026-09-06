<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\ExamResultStatus;
use App\Models\Admin;
use App\Models\ExamResult;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\Subject;
use BackedEnum;
use Carbon\CarbonInterface;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use UnitEnum;

final class FillExamResults extends Page
{
    public ?string $classId = null;

    public ?string $subjectId = null;

    public ?string $year = null;

    /**
     * @var array<string, string|float|null>
     */
    public array $marks = [];

    protected string $view = 'filament.admin.pages.fill-exam-results';

    protected static ?string $navigationLabel = 'Fill exam results';

    protected static string|BackedEnum|null $navigationIcon = \Filament\Support\Icons\Heroicon::OutlinedPencilSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Academics';

    public function mount(): void
    {
        $this->year = (string) today()->year;
    }

    /**
     * @return Collection<int, StudentClass>
     */
    public function getClassesProperty(): Collection
    {
        return StudentClass::query()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Subject>
     */
    public function getSubjectsProperty(): Collection
    {
        return Subject::query()->orderBy('name')->get();
    }

    /**
     * Active students of the selected class.
     *
     * @return Collection<int, Student>
     */
    public function getStudentsProperty(): Collection
    {
        if ($this->classId === null) {
            /** @var Collection<int, Student> */
            return collect();
        }

        return Student::query()
            ->active()
            ->where('student_class_id', $this->classId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Load saved marks for the selected class, subject and year
     * into the input state. Runs only when the selection changes
     * so unsaved input is never overwritten on re-render.
     */
    public function loadExistingMarks(): void
    {
        $this->marks = [];

        if ($this->classId === null || $this->subjectId === null || $this->year === null) {
            return;
        }

        $existing = ExamResult::query()
            ->where('student_class_id', $this->classId)
            ->where('subject_id', $this->subjectId)
            ->where('year', (int) $this->year)
            ->pluck('marks', 'student_id');

        foreach ($existing as $studentId => $mark) {
            $this->marks[$studentId] = (string) $mark;
        }
    }

    public function updatedClassId(): void
    {
        $this->loadExistingMarks();
    }

    public function updatedSubjectId(): void
    {
        $this->loadExistingMarks();
    }

    public function updatedYear(): void
    {
        $this->loadExistingMarks();
    }

    /**
     * Publishing state of the selected result sheet, used by the
     * view to show the recheck window and lock the form once it closes.
     *
     * @return array{ends_at: CarbonInterface, locked: bool, published: bool}|null
     */
    public function getSheetStateProperty(): ?array
    {
        if ($this->classId === null || $this->subjectId === null || $this->year === null) {
            return null;
        }

        $endsAt = ExamResult::recheckWindowFor($this->classId, $this->subjectId, (int) $this->year);

        if ($endsAt === null) {
            return null;
        }

        return [
            'ends_at' => $endsAt,
            'locked' => $endsAt->isPast(),
            'published' => true,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getYearsProperty(): array
    {
        $current = (int) today()->year;

        return collect(range($current - 9, $current))
            ->mapWithKeys(fn (int $year): array => [$year => (string) $year])
            ->all();
    }

    public function save(): void
    {
        $admin = auth('admin')->user();

        if (! $admin instanceof Admin) {
            $this->addError('classId', 'Only admins can record results here.');

            return;
        }

        if ($this->classId === null || $this->subjectId === null || $this->year === null) {
            $this->addError('classId', 'Select a class, subject and year first.');

            return;
        }

        if (ExamResult::sheetIsLocked($this->classId, $this->subjectId, (int) $this->year)) {
            FilamentNotification::make()
                ->title('These results are locked')
                ->body('They were published more than 30 days ago, so no further corrections are possible.')
                ->danger()
                ->send();

            return;
        }

        $students = Student::query()
            ->active()
            ->where('student_class_id', $this->classId)
            ->get();

        $saved = 0;

        foreach ($students as $student) {
            $marks = $this->marks[$student->getKey()] ?? null;

            if ($marks === null || $marks === '') {
                continue;
            }

            $marks = (float) $marks;

            if ($marks < 0 || $marks > 100) {
                $this->addError('marks', 'Marks must be between 0 and 100.');

                return;
            }

            // Results are updated in place: an existing record for the
            // student + subject + year never spawns a duplicate row.
            $result = ExamResult::query()->firstOrNew([
                'student_id' => $student->getKey(),
                'subject_id' => $this->subjectId,
                'year' => (int) $this->year,
            ]);

            $result->student_class_id = $this->classId;
            $result->marks = $marks;
            $result->total_marks = 100;

            if (! $result->exists) {
                $result->status = ExamResultStatus::Draft->value;
            }

            $result->save();

            $saved++;
        }

        FilamentNotification::make()
            ->title($saved === 0 ? 'Nothing to fill' : "Results filled for {$saved} students")
            ->body($saved === 0 ? 'Enter marks for at least one student first.' : 'Results are saved as drafts until you publish them.')
            ->{$saved === 0 ? 'warning' : 'success'}()
            ->send();
    }

    public function publish(): void
    {
        $admin = auth('admin')->user();

        if (! $admin instanceof Admin) {
            $this->addError('classId', 'Only admins can publish results here.');

            return;
        }

        if ($this->classId === null || $this->subjectId === null || $this->year === null) {
            $this->addError('classId', 'Select a class, subject and year first.');

            return;
        }

        if (ExamResult::sheetIsLocked($this->classId, $this->subjectId, (int) $this->year)) {
            FilamentNotification::make()
                ->title('These results are locked')
                ->body('They were published more than 30 days ago, so no further corrections are possible.')
                ->danger()
                ->send();

            return;
        }

        $publishable = ExamResult::query()
            ->where('student_class_id', $this->classId)
            ->where('subject_id', $this->subjectId)
            ->where('year', (int) $this->year)
            ->count();

        if ($publishable === 0) {
            FilamentNotification::make()
                ->title('Nothing to publish')
                ->body('Fill the results first, then publish them.')
                ->warning()
                ->send();

            return;
        }

        ExamResult::query()
            ->where('student_class_id', $this->classId)
            ->where('subject_id', $this->subjectId)
            ->where('year', (int) $this->year)
            ->update([
                'status' => ExamResultStatus::Published->value,
                'published_at' => now(),
            ]);

        FilamentNotification::make()
            ->title("Results published for {$publishable} students")
            ->body('Students can see them now. Corrections stay open for 30 days.')
            ->success()
            ->send();
    }
}
