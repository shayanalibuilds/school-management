<?php

declare(strict_types=1);

use App\Enums\AttendanceStatus;
use App\Filament\Staff\Pages\MarkAttendance;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentClass;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('records attendance for a student on a date', function (): void {
    $student = Student::factory()->create();

    $attendance = Attendance::create([
        'student_id' => $student->getKey(),
        'student_class_id' => $student->student_class_id,
        'staff_id' => Staff::factory()->create()->getKey(),
        'date' => today(),
        'status' => AttendanceStatus::Present,
    ]);

    expect($attendance->status)->toBe(AttendanceStatus::Present)
        ->and($attendance->student->is($student))->toBeTrue();
});

it('prevents duplicate attendance for the same student and date', function (): void {
    $student = Student::factory()->create();
    $staff = Staff::factory()->create();

    Attendance::create([
        'student_id' => $student->getKey(),
        'student_class_id' => $student->student_class_id,
        'staff_id' => $staff->getKey(),
        'date' => today(),
        'status' => AttendanceStatus::Present,
    ]);

    Attendance::create([
        'student_id' => $student->getKey(),
        'student_class_id' => $student->student_class_id,
        'staff_id' => $staff->getKey(),
        'date' => today(),
        'status' => AttendanceStatus::Absent,
    ]);
})->throws(UniqueConstraintViolationException::class);

it('notifies admins when a class has not submitted attendance by the deadline', function (): void {
    Notification::fake();

    $admin = Admin::factory()->create();
    $pendingClass = StudentClass::factory()->create();
    Student::factory()->count(2)->create(['student_class_id' => $pendingClass->getKey()]);

    $doneClass = StudentClass::factory()->create();
    Student::factory()->create(['student_class_id' => $doneClass->getKey()]);
    Attendance::factory()->create([
        'student_id' => Student::query()->where('student_class_id', $doneClass->getKey())->first()->getKey(),
        'student_class_id' => $doneClass->getKey(),
        'date' => today(),
    ]);

    $this->artisan('attendance:check-deadline');

    Notification::assertSentTo($admin, App\Notifications\AttendanceDeadlineNotification::class, function ($notification) use ($pendingClass): bool {
        return $notification->studentClass->is($pendingClass);
    });
    Notification::assertSentToTimes($admin, App\Notifications\AttendanceDeadlineNotification::class, 1);
});

it('lets staff mark attendance for their assigned class', function (): void {
    $staff = Staff::factory()->create();
    $class = StudentClass::factory()->create();
    $students = Student::factory()->count(3)->create(['student_class_id' => $class->getKey()]);
    $staff->assignments()->create([
        'student_class_id' => $class->getKey(),
        'subject_id' => App\Models\Subject::factory()->create()->getKey(),
    ]);

    actingAs($staff, 'staff');
    Filament\Facades\Filament::setCurrentPanel('staff');

    Livewire::test(MarkAttendance::class)
        ->set('classId', $class->getKey())
        ->set('date', today()->toDateString())
        ->set('statuses.'.$students[0]->getKey(), AttendanceStatus::Present->value)
        ->set('statuses.'.$students[1]->getKey(), AttendanceStatus::Absent->value)
        ->set('statuses.'.$students[2]->getKey(), AttendanceStatus::Leave->value)
        ->call('save')
        ->assertNotified()
        ->assertSuccessful();

    expect(Attendance::query()->where('student_class_id', $class->getKey())->whereDate('date', today())->count())->toBe(3);
});

it('blocks staff from saving attendance for a class they are not assigned to', function (): void {
    $staff = Staff::factory()->create();
    $class = StudentClass::factory()->create();
    Student::factory()->count(2)->create(['student_class_id' => $class->getKey()]);

    actingAs($staff, 'staff');
    Filament\Facades\Filament::setCurrentPanel('staff');

    Livewire::test(MarkAttendance::class)
        ->set('classId', $class->getKey())
        ->set('date', today()->toDateString())
        ->call('save');

    expect(Attendance::query()->count())->toBe(0);
});

it('shows the attendance resource to admins', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    get('/dashboard/attendances')->assertOk();
});

it('shows the mark attendance page to staff', function (): void {
    actingAs(Staff::factory()->create(), 'staff');

    get('/staff/mark-attendance')->assertOk();
});
