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
use App\Support\StudentLookup;

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

it('resolves students by parent cnic with flexible formatting', function (): void {
    $student = Student::factory()->create();
    $parent = ParentModel::factory()->create(['cnic' => '35202-1234567-1']);
    $parent->students()->attach($student->getKey());

    expect(StudentLookup::resolve('35202-1234567-1')->pluck('id'))->toContain($student->getKey())
        ->and(StudentLookup::resolve('3520212345671')->pluck('id'))->toContain($student->getKey())
        ->and(StudentLookup::resolve(' 35202 1234567 1 ')->pluck('id'))->toContain($student->getKey());
});

it('resolves students by guardian cnic', function (): void {
    $student = Student::factory()->create();
    $guardian = Guardian::factory()->create(['cnic' => '35202-5555555-5']);
    $guardian->students()->attach($student->getKey());

    expect(StudentLookup::resolve('35202-5555555-5')->pluck('id'))->toContain($student->getKey());
});

it('resolves a student by roll number', function (): void {
    $student = Student::factory()->create(['sr_no' => 77]);

    expect(StudentLookup::resolve('77')->pluck('id'))->toContain($student->getKey())
        ->and(StudentLookup::resolve('0077')->pluck('id'))->toContain($student->getKey());
});

it('returns every active child of one parent', function (): void {
    $eldest = Student::factory()->create(['name' => 'Eldest Child']);
    $youngest = Student::factory()->create(['name' => 'Youngest Child']);
    $parent = ParentModel::factory()->create(['cnic' => '35202-6666666-6']);
    $parent->students()->attach([$eldest->getKey(), $youngest->getKey()]);

    $resolved = StudentLookup::resolve('35202-6666666-6');

    expect($resolved->pluck('id'))->toContain($eldest->getKey())
        ->and($resolved->pluck('id'))->toContain($youngest->getKey());
});

it('excludes inactive students from every lookup', function (): void {
    $active = Student::factory()->create(['sr_no' => 80]);
    $left = Student::factory()->left()->create(['sr_no' => 81]);
    $parent = ParentModel::factory()->create(['cnic' => '35202-7777777-7']);
    $parent->students()->attach([$active->getKey(), $left->getKey()]);

    $byCnic = StudentLookup::resolve('35202-7777777-7');

    expect($byCnic->pluck('id'))->toContain($active->getKey())
        ->and($byCnic->pluck('id'))->not->toContain($left->getKey())
        ->and(StudentLookup::resolve('81')->isEmpty())->toBeTrue()
        ->and(StudentLookup::resolve('80')->pluck('id'))->toContain($active->getKey());
});

it('returns nothing for unknown or empty identifiers', function (): void {
    Student::factory()->create(['sr_no' => 5]);
    ParentModel::factory()->create(['cnic' => '35202-0000000-0']);

    expect(StudentLookup::resolve('9999999999999')->isEmpty())->toBeTrue()
        ->and(StudentLookup::resolve('junk-identifier')->isEmpty())->toBeTrue()
        ->and(StudentLookup::resolve('')->isEmpty())->toBeTrue()
        ->and(StudentLookup::resolve(null)->isEmpty())->toBeTrue();
});

it('shows public results for a child by year as grades', function (): void {
    $student = Student::factory()->create();
    $parent = ParentModel::factory()->create(['cnic' => '35202-7654321-9']);
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

    get('/results?identifier=35202-7654321-9&year=2025&type=grades')
        ->assertOk()
        ->assertSee($student->name)
        ->assertSee('Mathematics')
        ->assertSee('A+');
});

it('shows public results as positions searched by roll number', function (): void {
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();

    $first = Student::factory()->create(['student_class_id' => $class->getKey(), 'name' => 'Top Student', 'sr_no' => 301]);
    $second = Student::factory()->create(['student_class_id' => $class->getKey(), 'name' => 'Second Student', 'sr_no' => 302]);

    App\Models\ExamResult::factory()->create([
        'student_id' => $first->getKey(), 'student_class_id' => $class->getKey(),
        'subject_id' => $subject->getKey(), 'year' => 2025, 'marks' => 95,
    ]);
    App\Models\ExamResult::factory()->create([
        'student_id' => $second->getKey(), 'student_class_id' => $class->getKey(),
        'subject_id' => $subject->getKey(), 'year' => 2025, 'marks' => 80,
    ]);

    get('/results?identifier=301&year=2025&type=positions')
        ->assertOk()
        ->assertSee('1st');
});

it('shows public attendance for linked children', function (): void {
    $student = Student::factory()->create(['name' => 'Attendance Kid']);
    $parent = ParentModel::factory()->create(['cnic' => '35202-2222222-2']);
    $parent->students()->attach($student->getKey());

    Attendance::factory()->create([
        'student_id' => $student->getKey(),
        'student_class_id' => $student->student_class_id,
        'date' => today(),
        'status' => AttendanceStatus::Present,
    ]);

    get('/attendance?identifier=35202-2222222-2')
        ->assertOk()
        ->assertSee('Attendance Kid')
        ->assertSee('Present');
});

it('shows public fees for linked children with configured providers only', function (): void {
    App\Models\PaymentSetting::factory()->easypaisa()->create();

    $student = Student::factory()->create(['name' => 'Fee Kid']);
    $parent = ParentModel::factory()->create(['cnic' => '35202-3333333-3']);
    $parent->students()->attach($student->getKey());

    Fee::factory()->create([
        'student_id' => $student->getKey(),
        'year' => 2025,
        'amount' => 2500,
        'amount_paid' => 0,
    ]);

    get('/fees?identifier=35202-3333333-3&year=2025')
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
