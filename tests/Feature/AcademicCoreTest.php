<?php

declare(strict_types=1);

use App\Enums\StudentStatus;
use App\Filament\Resources\StudentClasses\Pages\CreateStudentClass;
use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Models\Admin;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\Subject;
use Illuminate\Support\Str;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('creates and links the academic core models', function (): void {
    $class = StudentClass::factory()->create(['name' => 'Nursery']);
    $subject = Subject::factory()->create(['name' => 'English']);
    $class->subjects()->attach($subject);

    $student = Student::factory()->create([
        'student_class_id' => $class->getKey(),
        'sr_no' => 1,
    ]);

    expect($student->studentClass->is($class))->toBeTrue()
        ->and($class->subjects()->whereKey($subject->getKey())->exists())->toBeTrue()
        ->and($student->status)->toBe(StudentStatus::Active);
});

it('scopes students to active status', function (): void {
    Student::factory()->count(2)->create();
    Student::factory()->create(['status' => StudentStatus::Left]);

    expect(Student::query()->active()->count())->toBe(2);
});

it('rejects duplicate class names', function (): void {
    StudentClass::factory()->create(['name' => 'Class 1']);

    Livewire::test(CreateStudentClass::class)
        ->fillForm(['name' => 'Class 1'])
        ->call('create')
        ->assertHasFormErrors(['name']);
});

it('lets an admin create a student through the panel', function (): void {
    $admin = Admin::factory()->create();
    $class = StudentClass::factory()->create();

    actingAs($admin, 'admin');

    Livewire::test(CreateStudent::class)
        ->fillForm([
            'sr_no' => 42,
            'name' => 'Aisha Khan',
            'student_class_id' => $class->getKey(),
            'joining_date' => '2026-09-01',
            'status' => StudentStatus::Active->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Student::query()->where('sr_no', 42)->where('name', 'Aisha Khan')->exists())->toBeTrue();
});

it('shows academic resource index pages to admins', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    foreach (['student-classes', 'subjects', 'students'] as $slug) {
        $this->get("/dashboard/{$slug}")->assertOk();
    }
});

it('generates sequential sr numbers when not provided', function (): void {
    $first = Student::factory()->create(['sr_no' => null]);
    $second = Student::factory()->create(['sr_no' => null]);

    expect($first->sr_no)->toBeInt()
        ->and($second->sr_no)->toBe($first->sr_no + 1)
        ->and(Str::length((string) $second->sr_no))->toBeGreaterThan(0);
});
