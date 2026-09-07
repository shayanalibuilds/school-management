<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\AttendanceStatus;
use App\Jobs\SyncAttendance;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\StudentClass;
use App\Support\AppSettings;
use App\Support\AttendanceSync;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use UnitEnum;

final class FillAttendance extends Page
{
    public ?string $classId = null;

    /**
     * @var array<string, string>
     */
    public array $statuses = [];

    protected string $view = 'filament.admin.pages.fill-attendance';

    protected ?string $heading = 'Fill attendance';

    protected static ?string $navigationLabel = 'Fill attendance';

    protected static string|BackedEnum|null $navigationIcon = \Filament\Support\Icons\Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Academics';

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
     * Admins can record attendance for every class.
     *
     * @return Collection<int, StudentClass>
     */
    public function getClassesProperty(): Collection
    {
        return StudentClass::query()->where('status', 'active')->orderBy('name')->get();
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
        $admin = auth('admin')->user();

        if (! $admin instanceof Admin) {
            $this->addError('classId', 'Only admins can record attendance here.');

            return;
        }

        if ($this->classId === null) {
            $this->addError('classId', 'Select a class first.');

            return;
        }

        if (StudentClass::query()->whereKey($this->classId)->doesntExist()) {
            $this->addError('classId', 'Select a class first.');

            return;
        }

        $statuses = $this->statuses;

        if (AppSettings::queueEverything()) {
            SyncAttendance::dispatch('admin', (string) $admin->getKey(), $this->classId, $statuses);

            Notification::make()
                ->title('Attendance queued')
                ->body("Today's attendance is being recorded in the background.")
                ->info()
                ->send();

            return;
        }

        AttendanceSync::execute('admin', (string) $admin->getKey(), $this->classId, $statuses);

        Notification::make()
            ->title('Attendance filled')
            ->success()
            ->send();
    }
}
