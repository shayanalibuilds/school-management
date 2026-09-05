<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\AttendanceStatus;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\StudentClass;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use UnitEnum;

final class FillAttendance extends Page
{
    public ?string $classId = null;

    public ?string $date = null;

    /**
     * @var array<string, string>
     */
    public array $statuses = [];

    protected string $view = 'filament.admin.pages.fill-attendance';

    protected static ?string $navigationLabel = 'Fill attendance';

    protected static string|BackedEnum|null $navigationIcon = \Filament\Support\Icons\Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Academics';

    public function mount(): void
    {
        $this->date = today()->toDateString();
    }

    /**
     * Admins can record attendance for every class.
     *
     * @return Collection<int, StudentClass>
     */
    public function getClassesProperty(): Collection
    {
        return StudentClass::query()->orderBy('name')->get();
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
     * Seed the status state from saved records when the class or
     * date selection changes. Defaults everything to present.
     */
    public function loadExistingStatuses(): void
    {
        $this->statuses = [];

        if ($this->classId === null) {
            return;
        }

        $existing = Attendance::query()
            ->where('student_class_id', $this->classId)
            ->whereDate('date', $this->date)
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

    public function updatedDate(): void
    {
        $this->loadExistingStatuses();
    }

    public function setStatus(int|string $studentId, string $status): void
    {
        if (AttendanceStatus::tryFrom($status) !== null) {
            $this->statuses[$studentId] = $status;
        }
    }

    public function save(): void
    {
        $admin = auth('admin')->user();

        if (! $admin instanceof Admin) {
            $this->addError('classId', 'Only admins can record attendance here.');

            return;
        }

        if ($this->date === null || $this->classId === null) {
            $this->addError('classId', 'Select a class and a date first.');

            return;
        }

        if ($this->date > today()->toDateString()) {
            $this->addError('date', 'Attendance cannot be recorded for a future date.');

            return;
        }

        $statuses = $this->statuses;

        $students = Student::query()
            ->active()
            ->where('student_class_id', $this->classId)
            ->get();

        foreach ($students as $student) {
            $status = $statuses[$student->getKey()] ?? AttendanceStatus::Present->value;

            Attendance::updateOrCreate(
                [
                    'student_id' => $student->getKey(),
                    'date' => $this->date,
                ],
                [
                    'student_class_id' => $this->classId,
                    'staff_id' => null,
                    'admin_id' => $admin->getKey(),
                    'status' => $status,
                ],
            );
        }

        Notification::make()
            ->title('Attendance inserted')
            ->success()
            ->send();
    }
}
