<?php

declare(strict_types=1);

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Fee;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\StudentParent as ParentModel;
use App\Models\Subject;

use function Pest\Laravel\get;

it('links students to parents and guardians', function (): void {
    $student = Student::factory()->create();
    $parent = ParentModel::factory()->create();
    $guardian = Guardian::factory()->create();

    $parent->students()->attach($student->getKey());
    $guardian->students()->attach($student->getKey());

    expect($parent->students()->whereKey($student->getKey())->exists())->toBeTrue()
        ->and($guardian->students()->whereKey($student->getKey())->exists())->toBeTrue();
});

it('resolves students by guardian cnic and phone', function (): void {
    $student = Student::factory()->create();
    $parent = ParentModel::factory()->create([
        'cnic' => '35202-1234567-1',
        'phone' => '03001234567',
    ]);
    $parent->students()->attach($student->getKey());

    $resolved = App\Support\StudentLookup::resolve('35202-1234567-1', '03001234567');

    expect($resolved->pluck('id'))->toContain($student->getKey());
});

it('rejects a cnic with a mismatched phone', function (): void {
    $student = Student::factory()->create();
    $parent = ParentModel::factory()->create([
        'cnic' => '35202-1234567-2',
        'phone' => '03001234567',
    ]);
    $parent->students()->attach($student->getKey());

    expect(App\Support\StudentLookup::resolve('35202-1234567-2', '03009999999')->isEmpty())->toBeTrue();
});

it('shows public results for a child by year as grades', function (): void {
    $student = Student::factory()->create();
    $parent = ParentModel::factory()->create(['cnic' => '35202-7654321-9', 'phone' => '03005556667']);
    $parent->students()->attach($student->getKey());
    $subject = Subject::factory()->create(['name' => 'Mathematics']);

    App\Models\ExamResult::factory()->create([
        'student_id' => $student->getKey(),
        'student_class_id' => $student->student_class_id,
        'subject_id' => $subject->getKey(),
        'year' => 2025,
        'marks' => 92,
        'total_marks' => 100,
    ]);

    get('/results?cnic=35202-7654321-9&phone=03005556667&year=2025&type=grades')
        ->assertOk()
        ->assertSee($student->name)
        ->assertSee('Mathematics')
        ->assertSee('A+');
});

it('shows public results as positions', function (): void {
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();

    $first = Student::factory()->create(['student_class_id' => $class->getKey(), 'name' => 'Top Student']);
    $second = Student::factory()->create(['student_class_id' => $class->getKey(), 'name' => 'Second Student']);

    $parent = ParentModel::factory()->create(['cnic' => '35202-1111111-1', 'phone' => '03001111111']);
    $parent->students()->attach($first->getKey());

    App\Models\ExamResult::factory()->create([
        'student_id' => $first->getKey(), 'student_class_id' => $class->getKey(),
        'subject_id' => $subject->getKey(), 'year' => 2025, 'marks' => 95,
    ]);
    App\Models\ExamResult::factory()->create([
        'student_id' => $second->getKey(), 'student_class_id' => $class->getKey(),
        'subject_id' => $subject->getKey(), 'year' => 2025, 'marks' => 80,
    ]);

    get('/results?cnic=35202-1111111-1&phone=03001111111&year=2025&type=positions')
        ->assertOk()
        ->assertSee('1st');
});

it('shows public attendance for linked children', function (): void {
    $student = Student::factory()->create(['name' => 'Attendance Kid']);
    $parent = ParentModel::factory()->create(['cnic' => '35202-2222222-2', 'phone' => '03002222222']);
    $parent->students()->attach($student->getKey());

    Attendance::factory()->create([
        'student_id' => $student->getKey(),
        'student_class_id' => $student->student_class_id,
        'date' => today(),
        'status' => AttendanceStatus::Present,
    ]);

    get('/attendance?cnic=35202-2222222-2&phone=03002222222')
        ->assertOk()
        ->assertSee('Attendance Kid')
        ->assertSee('Present');
});

it('shows public fees for linked children with configured providers only', function (): void {
    App\Models\PaymentSetting::factory()->easypaisa()->create();

    $student = Student::factory()->create(['name' => 'Fee Kid']);
    $parent = ParentModel::factory()->create(['cnic' => '35202-3333333-3', 'phone' => '03003333333']);
    $parent->students()->attach($student->getKey());

    Fee::factory()->create([
        'student_id' => $student->getKey(),
        'year' => 2025,
        'amount' => 2500,
        'amount_paid' => 0,
    ]);

    get('/fees?cnic=35202-3333333-3&phone=03003333333&year=2025')
        ->assertOk()
        ->assertSee('Fee Kid')
        ->assertSee('EasyPaisa')
        ->assertDontSee('JazzCash');
});

it('renders the public pages without auth', function (): void {
    get('/results')->assertOk();
    get('/attendance')->assertOk();
    get('/fees')->assertOk();
});
