<?php

declare(strict_types=1);

namespace App\Filament\Staff\Pages;

use App\Enums\AttendanceStatus;
use App\Jobs\SyncAttendance;
use App\Models\Attendance;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentClass;
use App\Support\AppSettings;
use App\Support\AttendanceSync;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Collection;

final class FillAttendance extends Page
{
    public ?string $classId = null;

    /**
     * @var array<string, string>
     */
    public array $statuses = [];

    protected string $view = 'filament.staff.pages.fill-attendance';

    protected ?string $heading = 'Fill attendance';

    protected static ?string $navigationLabel = 'Fill attendance';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = \Filament\Support\Icons\Heroicon::OutlinedClipboardDocumentList;

    /**
     * Attendance can only ever be recorded for the current day.
     */
    public static function attendanceDate(): string
    {
        return today()->toDateString();
    }

    public function mount(): void
    {
        //
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
     * Seed the status state from saved records when the class
     * selection changes. Defaults everything to present.
     */
    public function loadExistingStatuses(): void
    {
        $this->statuses = [];

        if ($this->classId === null) {
            return;
        }

        $existing = Attendance::query()
            ->where('student_class_id', $this->classId)
            ->whereDate('date', self::attendanceDate())
            ->pluck('status', 'student_id');

        $students = Student::query()
            ->active()
            ->where('student_class_id', $this->classId)
            ->get();

        foreach ($students as $student) {
            $status = $existing->get($student->getKey());

            $this->statuses[$student->getKey()] = $status?->value ?? AttendanceStatus::Present->value;
        }
    }

    public function updatedClassId(): void
    {
        $this->loadExistingStatuses();
    }

    public function save(): void
    {
        $staff = auth('staff')->user();

        if (! $staff instanceof Staff) {
            throw new Halt('Not signed in.');
        }

        if ($this->classId === null) {
            $this->addError('classId', 'Select a class first.');

            return;
        }

        $isAssigned = $staff->assignments()
            ->where('student_class_id', $this->classId)
            ->exists();

        if (! $isAssigned) {
            $this->addError('classId', 'You are not assigned to this class.');

            return;
        }

        if (StudentClass::query()->whereKey($this->classId)->doesntExist()) {
            $this->addError('classId', 'Select a class first.');

            return;
        }

        $statuses = $this->statuses;

        if (AppSettings::queueEverything()) {
            SyncAttendance::dispatch('staff', (string) $staff->getKey(), $this->classId, $statuses);

            Notification::make()
                ->title('Attendance queued')
                ->body("Today's attendance is being recorded in the background.")
                ->info()
                ->send();

            return;
        }

        AttendanceSync::execute('staff', (string) $staff->getKey(), $this->classId, $statuses);

        Notification::make()
            ->title('Attendance filled')
            ->success()
            ->send();
    }
}
