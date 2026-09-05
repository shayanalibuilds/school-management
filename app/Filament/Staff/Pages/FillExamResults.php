<?php

declare(strict_types=1);

namespace App\Filament\Staff\Pages;

use App\Models\ExamResult;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\Subject;
use BackedEnum;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Collection;

final class FillExamResults extends Page
{
    public ?string $classId = null;

    public ?string $subjectId = null;

    public ?string $year = null;

    /**
     * @var array<string, string|float|null>
     */
    public array $marks = [];

    protected string $view = 'filament.staff.pages.fill-exam-results';

    protected static ?string $navigationLabel = 'Fill exam results';

    protected static string|BackedEnum|null $navigationIcon = \Filament\Support\Icons\Heroicon::OutlinedPencilSquare;

    public function mount(): void
    {
        $this->year = (string) today()->year;
    }

    /**
     * Classes the signed-in staff member is assigned to teach.
     *
     * @return Collection<int, StudentClass>
     */
    public function getClassesProperty(): Collection
    {
        $staff = auth('staff')->user();

        if (! $staff instanceof Staff) {
            return collect();
        }

        return StudentClass::query()
            ->whereRelation('staffAssignments', 'staff_id', $staff->getKey())
            ->orderBy('name')
            ->get();
    }

    /**
     * Subjects the signed-in staff member is assigned to teach.
     *
     * @return Collection<int, Subject>
     */
    public function getSubjectsProperty(): Collection
    {
        $staff = auth('staff')->user();

        if (! $staff instanceof Staff) {
            return collect();
        }

        return Subject::query()
            ->whereRelation('staffAssignments', 'staff_id', $staff->getKey())
            ->orderBy('name')
            ->get();
    }

    /**
     * Active students of the selected class.
     *
     * @return Collection<int, Student>
     */
    public function getStudentsProperty(): Collection
    {
        if ($this->classId === null) {
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
        $staff = auth('staff')->user();

        if (! $staff instanceof Staff) {
            throw new Halt('Not signed in.');
        }

        if ($this->classId === null || $this->subjectId === null || $this->year === null) {
            $this->addError('classId', 'Select a class, subject and year first.');

            return;
        }

        $isAssigned = $staff->assignments()
            ->where('student_class_id', $this->classId)
            ->where('subject_id', $this->subjectId)
            ->exists();

        if (! $isAssigned) {
            $this->addError('classId', 'You are not assigned to teach this subject to this class.');

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
            ->title("Results saved for {$saved} students")
            ->success()
            ->send();
    }
}
