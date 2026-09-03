<?php

declare(strict_types=1);

use App\Filament\Widgets\AttendanceChart;
use App\Filament\Widgets\StudentPerformanceChart;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\Staff;
use App\Models\StaffAssignment;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\Subject;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shows both charts on the staff dashboard', function (): void {
    $staff = Staff::factory()->create();

    actingAs($staff, 'staff');
    Filament::setCurrentPanel(Filament::getPanel('staff'));

    get('/staff')
        ->assertOk()
        ->assertSee('StudentPerformanceChart')
        ->assertSee('AttendanceChart');
});

it('scopes the staff attendance chart to assigned classes', function (): void {
    $staff = Staff::factory()->create();
    $mine = StudentClass::factory()->create(['name' => 'Alpha One']);
    $other = StudentClass::factory()->create(['name' => 'Zeta Nine']);
    $subject = Subject::factory()->create();

    StaffAssignment::create([
        'staff_id' => $staff->getKey(),
        'student_class_id' => $mine->getKey(),
        'subject_id' => $subject->getKey(),
    ]);

    Attendance::factory()->create(['student_id' => Student::factory()->create(['student_class_id' => $mine->getKey()])]);
    Attendance::factory()->create(['student_id' => Student::factory()->create(['student_class_id' => $other->getKey()])]);

    actingAs($staff, 'staff');
    Filament::setCurrentPanel(Filament::getPanel('staff'));

    Livewire::test(AttendanceChart::class)
        ->assertSuccessful()
        ->assertSee('Alpha One')
        ->assertDontSee('Zeta Nine');
});

it('scopes the staff performance chart to assigned classes across all subjects', function (): void {
    $staff = Staff::factory()->create();
    $mine = StudentClass::factory()->create();
    $other = StudentClass::factory()->create();
    $taught = Subject::factory()->create();
    $untouched = Subject::factory()->create();
    $year = (int) today()->year;

    StaffAssignment::create([
        'staff_id' => $staff->getKey(),
        'student_class_id' => $mine->getKey(),
        'subject_id' => $taught->getKey(),
    ]);

    $inClassA = Student::factory()->create(['student_class_id' => $mine->getKey()]);
    $alsoClassA = Student::factory()->create(['student_class_id' => $mine->getKey()]);
    $inClassB = Student::factory()->create(['student_class_id' => $other->getKey()]);
    $alsoClassB = Student::factory()->create(['student_class_id' => $other->getKey()]);

    ExamResult::factory()->create(['student_id' => $inClassA->getKey(), 'student_class_id' => $mine->getKey(), 'subject_id' => $untouched->getKey(), 'year' => $year, 'marks' => 85]);
    ExamResult::factory()->create(['student_id' => $alsoClassA->getKey(), 'student_class_id' => $mine->getKey(), 'subject_id' => $untouched->getKey(), 'year' => $year, 'marks' => 86]);
    ExamResult::factory()->create(['student_id' => $inClassB->getKey(), 'student_class_id' => $other->getKey(), 'subject_id' => $untouched->getKey(), 'year' => $year, 'marks' => 66]);
    ExamResult::factory()->create(['student_id' => $alsoClassB->getKey(), 'student_class_id' => $other->getKey(), 'subject_id' => $untouched->getKey(), 'year' => $year, 'marks' => 68]);

    actingAs($staff, 'staff');
    Filament::setCurrentPanel(Filament::getPanel('staff'));

    Livewire::test(StudentPerformanceChart::class)
        ->assertSuccessful()
        ->assertSeeHtml('85.5')
        ->assertDontSee('76.25');
});

it('keeps admin charts global across all classes', function (): void {
    $alpha = StudentClass::factory()->create(['name' => 'Alpha One']);
    $zeta = StudentClass::factory()->create(['name' => 'Zeta Nine']);

    Attendance::factory()->create(['student_id' => Student::factory()->create(['student_class_id' => $alpha->getKey()])]);
    Attendance::factory()->create(['student_id' => Student::factory()->create(['student_class_id' => $zeta->getKey()])]);

    actingAs(Admin::factory()->create(), 'admin');
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(AttendanceChart::class)
        ->assertSuccessful()
        ->assertSee('Alpha One')
        ->assertSee('Zeta Nine');
});

it('renders empty charts for staff without assignments', function (): void {
    $staff = Staff::factory()->create();

    actingAs($staff, 'staff');
    Filament::setCurrentPanel(Filament::getPanel('staff'));

    Livewire::test(AttendanceChart::class)
        ->assertSuccessful();

    Livewire::test(StudentPerformanceChart::class)
        ->assertSuccessful();
});
