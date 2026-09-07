<?php

declare(strict_types=1);

namespace App\Filament\Staff\Pages;

use App\Jobs\PublishExamResults;
use App\Jobs\SyncExamResults;
use App\Models\ExamResult;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\Subject;
use App\Support\AppSettings;
use App\Support\ExamResultsSync;
use BackedEnum;
use Carbon\CarbonInterface;
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

    protected ?string $heading = 'Fill exam results';

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

        if (ExamResult::sheetIsLocked($this->classId, $this->subjectId, (int) $this->year)) {
            FilamentNotification::make()
                ->title('These results are locked')
                ->body('They were published more than 30 days ago, so no further corrections are possible.')
                ->danger()
                ->send();

            return;
        }

        // Validate every provided mark before any write happens.
        foreach ($this->marks as $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if ((float) $value < 0 || (float) $value > 100) {
                $this->addError('marks', 'Marks must be between 0 and 100.');

                return;
            }
        }

        $marks = $this->marks;

        if (AppSettings::queueEverything()) {
            SyncExamResults::dispatch('staff', (string) $staff->getKey(), $this->classId, $this->subjectId, (int) $this->year, $marks);

            FilamentNotification::make()
                ->title('Results queued')
                ->body('The marks are being recorded in the background.')
                ->info()
                ->send();

            return;
        }

        $saved = ExamResultsSync::execute('staff', (string) $staff->getKey(), $this->classId, $this->subjectId, (int) $this->year, $marks);

        FilamentNotification::make()
            ->title($saved === 0 ? 'Nothing to fill' : "Results filled for {$saved} students")
            ->body($saved === 0 ? 'Enter marks for at least one student first.' : 'Results are saved as drafts until you publish them.')
            ->{$saved === 0 ? 'warning' : 'success'}()
            ->send();
    }

    public function publish(): void
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

        if (AppSettings::queueEverything()) {
            PublishExamResults::dispatch('staff', (string) $staff->getKey(), $this->classId, $this->subjectId, (int) $this->year);

            FilamentNotification::make()
                ->title('Publishing queued')
                ->body('The results are being published in the background.')
                ->info()
                ->send();

            return;
        }

        ExamResultsSync::publish($this->classId, $this->subjectId, (int) $this->year, $staff->name);

        FilamentNotification::make()
            ->title("Results published for {$publishable} students")
            ->body('Students can see them now. Corrections stay open for 30 days.')
            ->success()
            ->send();
    }
}
