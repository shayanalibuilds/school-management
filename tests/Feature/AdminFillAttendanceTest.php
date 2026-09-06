<?php

declare(strict_types=1);

use App\Enums\AttendanceStatus;
use App\Filament\Pages\FillAttendance;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentClass;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('lets an admin insert attendance for a whole class at once', function (): void {
    $admin = Admin::factory()->create();
    $class = StudentClass::factory()->create();
    $students = Student::factory()->count(3)->create(['student_class_id' => $class->getKey()]);

    actingAs($admin, 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire::test(FillAttendance::class)
        ->set('classId', $class->getKey())
        ->set('date', today()->toDateString())
        ->set('statuses.'.$students[0]->getKey(), AttendanceStatus::Present->value)
        ->set('statuses.'.$students[1]->getKey(), AttendanceStatus::Absent->value)
        ->set('statuses.'.$students[2]->getKey(), AttendanceStatus::Leave->value)
        ->call('save')
        ->assertNotified()
        ->assertSuccessful();

    $rows = Attendance::query()->where('student_class_id', $class->getKey())->whereDate('date', today())->get();

    expect($rows)->toHaveCount(3)
        ->and($rows->firstWhere('student_id', $students[0]->getKey())->status)->toBe(AttendanceStatus::Present)
        ->and($rows->firstWhere('student_id', $students[1]->getKey())->status)->toBe(AttendanceStatus::Absent)
        ->and($rows->firstWhere('student_id', $students[2]->getKey())->status)->toBe(AttendanceStatus::Leave)
        ->and($rows->firstWhere('student_id', $students[1]->getKey())->admin_id)->toBe($admin->getKey())
        ->and($rows->firstWhere('student_id', $students[1]->getKey())->staff_id)->toBeNull()
        ->and($rows->firstWhere('student_id', $students[1]->getKey())->markerName())->toBe($admin->name);
});

it('blocks admins from inserting attendance for a future date', function (): void {
    $class = StudentClass::factory()->create();
    Student::factory()->create(['student_class_id' => $class->getKey()]);

    actingAs(Admin::factory()->create(), 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire::test(FillAttendance::class)
        ->set('classId', $class->getKey())
        ->set('date', today()->addDay()->toDateString())
        ->call('save');

    expect(Attendance::query()->count())->toBe(0);
});

it('keeps staff and admin markers distinguishable', function (): void {
    $staff = Staff::factory()->create();
    $student = Student::factory()->create();

    $byStaff = Attendance::create([
        'student_id' => $student->getKey(),
        'student_class_id' => $student->student_class_id,
        'staff_id' => $staff->getKey(),
        'date' => today(),
        'status' => AttendanceStatus::Present,
    ]);

    $otherStudent = Student::factory()->create();

    $byAdmin = Attendance::create([
        'student_id' => $otherStudent->getKey(),
        'student_class_id' => $otherStudent->student_class_id,
        'admin_id' => Admin::factory()->create()->getKey(),
        'date' => today(),
        'status' => AttendanceStatus::Present,
    ]);

    expect($byStaff->markerName())->toBe($staff->name)
        ->and($byAdmin->markerName())->not->toBe($staff->name);
});

it('shows the fill attendance page to admins', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    get('/dashboard/fill-attendance')->assertOk();
});
