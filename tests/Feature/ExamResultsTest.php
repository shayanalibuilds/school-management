<?php

declare(strict_types=1);

use App\Models\Admin;
use App\Models\ExamResult;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\Subject;
use App\Support\Grades;
use App\Support\Positions;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('computes grades from marks', function (): void {
    expect(Grades::fromMarks(95, 100))->toBe('A+')
        ->and(Grades::fromMarks(85, 100))->toBe('A')
        ->and(Grades::fromMarks(72, 100))->toBe('B')
        ->and(Grades::fromMarks(65, 100))->toBe('C')
        ->and(Grades::fromMarks(55, 100))->toBe('D')
        ->and(Grades::fromMarks(40, 100))->toBe('F');
});

it('computes class positions for a year with competition ranking', function (): void {
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();
    $year = today()->year;

    $a = Student::factory()->create(['student_class_id' => $class->getKey()]);
    $b = Student::factory()->create(['student_class_id' => $class->getKey()]);
    $c = Student::factory()->create(['student_class_id' => $class->getKey()]);
    $d = Student::factory()->create(['student_class_id' => $class->getKey()]);

    ExamResult::factory()->create(['student_id' => $a->getKey(), 'student_class_id' => $class->getKey(), 'subject_id' => $subject->getKey(), 'year' => $year, 'marks' => 90]);
    ExamResult::factory()->create(['student_id' => $b->getKey(), 'student_class_id' => $class->getKey(), 'subject_id' => $subject->getKey(), 'year' => $year, 'marks' => 90]);
    ExamResult::factory()->create(['student_id' => $c->getKey(), 'student_class_id' => $class->getKey(), 'subject_id' => $subject->getKey(), 'year' => $year, 'marks' => 80]);
    ExamResult::factory()->create(['student_id' => $d->getKey(), 'student_class_id' => $class->getKey(), 'subject_id' => $subject->getKey(), 'year' => $year, 'marks' => 70]);

    $positions = Positions::forClass($class, $year);

    expect($positions[$a->getKey()])->toBe(1)
        ->and($positions[$b->getKey()])->toBe(1)
        ->and($positions[$c->getKey()])->toBe(3)
        ->and($positions[$d->getKey()])->toBe(4);
});

it('lets an admin record an exam result through the panel', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    $student = Student::factory()->create();
    $subject = Subject::factory()->create();

    Livewire::test(App\Filament\Resources\ExamResults\Pages\CreateExamResult::class)
        ->fillForm([
            'student_id' => $student->getKey(),
            'subject_id' => $subject->getKey(),
            'year' => (string) today()->year,
            'marks' => 88,
            'total_marks' => 100,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(ExamResult::query()->where('student_id', $student->getKey())->where('subject_id', $subject->getKey())->exists())->toBeTrue()
        ->and(ExamResult::query()->first()->student_class_id)->toBe($student->student_class_id);
});

it('rejects marks above total marks', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    $student = Student::factory()->create();
    $subject = Subject::factory()->create();

    Livewire::test(App\Filament\Resources\ExamResults\Pages\CreateExamResult::class)
        ->fillForm([
            'student_id' => $student->getKey(),
            'subject_id' => $subject->getKey(),
            'year' => (string) today()->year,
            'marks' => 120,
            'total_marks' => 100,
        ])
        ->call('create')
        ->assertHasFormErrors(['marks']);
});

it('auto-derives the student class when staff enter results', function (): void {
    $staff = Staff::factory()->create();
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();
    $student = Student::factory()->create(['student_class_id' => $class->getKey()]);

    $staff->assignments()->create([
        'student_class_id' => $class->getKey(),
        'subject_id' => $subject->getKey(),
    ]);

    actingAs($staff, 'staff');
    Filament\Facades\Filament::setCurrentPanel('staff');

    Livewire::test(App\Filament\Staff\Pages\EnterResults::class)
        ->set('classId', $class->getKey())
        ->set('subjectId', $subject->getKey())
        ->set('year', (string) today()->year)
        ->set('marks.'.$student->getKey(), '75')
        ->call('save')
        ->assertSuccessful();

    $result = ExamResult::query()->where('student_id', $student->getKey())->sole();

    expect($result->marks)->toBe(75.0)
        ->and($result->student_class_id)->toBe($class->getKey());
});

it('blocks staff from entering results for unassigned class-subject pairs', function (): void {
    $staff = Staff::factory()->create();
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();
    $student = Student::factory()->create(['student_class_id' => $class->getKey()]);

    actingAs($staff, 'staff');
    Filament\Facades\Filament::setCurrentPanel('staff');

    Livewire::test(App\Filament\Staff\Pages\EnterResults::class)
        ->set('classId', $class->getKey())
        ->set('subjectId', $subject->getKey())
        ->set('year', (string) today()->year)
        ->set('marks.'.$student->getKey(), '75')
        ->call('save');

    expect(ExamResult::query()->count())->toBe(0);
});

it('shows the exam results resource to admins', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    get('/dashboard/exam-results')->assertOk();
});

it('shows the enter results page to staff', function (): void {
    actingAs(Staff::factory()->create(), 'staff');

    get('/staff/enter-results')->assertOk();
});
