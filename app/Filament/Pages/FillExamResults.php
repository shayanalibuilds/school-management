<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\ExamResult;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\Subject;
use BackedEnum;
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
     * @return array<int, string>
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

        if (! $admin instanceof \App\Models\Admin) {
            $this->addError('classId', 'Only admins can record results here.');

            return;
        }

        if ($this->classId === null || $this->subjectId === null || $this->year === null) {
            $this->addError('classId', 'Select a class, subject and year first.');

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

            ExamResult::updateOrCreate(
                [
                    'student_id' => $student->getKey(),
                    'subject_id' => $this->subjectId,
                    'year' => (int) $this->year,
                ],
                [
                    'student_class_id' => $this->classId,
                    'marks' => $marks,
                    'total_marks' => 100,
                ],
            );

            $saved++;
        }

        FilamentNotification::make()
            ->title("Results filled for {$saved} students")
            ->success()
            ->send();
    }
}
