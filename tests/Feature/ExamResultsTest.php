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

it('lets an admin insert results for a whole class at once', function (): void {
    $admin = Admin::factory()->create();
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();
    $students = Student::factory()->count(2)->create(['student_class_id' => $class->getKey()]);

    actingAs($admin, 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire::test(App\Filament\Pages\FillExamResults::class)
        ->set('classId', $class->getKey())
        ->set('subjectId', $subject->getKey())
        ->set('year', (string) today()->year)
        ->set('marks.'.$students[0]->getKey(), '88')
        ->set('marks.'.$students[1]->getKey(), '92')
        ->call('save')
        ->assertSuccessful()
        ->assertNotified();

    $rows = ExamResult::query()->where('subject_id', $subject->getKey())->get();

    expect($rows)->toHaveCount(2)
        ->and($rows->firstWhere('student_id', $students[0]->getKey())->marks)->toBe(88.0)
        ->and($rows->firstWhere('student_id', $students[0]->getKey())->student_class_id)->toBe($class->getKey())
        ->and($rows->firstWhere('student_id', $students[1]->getKey())->marks)->toBe(92.0);
});

it('rejects marks above the total when an admin fills results', function (): void {
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();
    $student = Student::factory()->create(['student_class_id' => $class->getKey()]);

    actingAs(Admin::factory()->create(), 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire::test(App\Filament\Pages\FillExamResults::class)
        ->set('classId', $class->getKey())
        ->set('subjectId', $subject->getKey())
        ->set('year', (string) today()->year)
        ->set('marks.'.$student->getKey(), '120')
        ->call('save');

    expect(ExamResult::query()->count())->toBe(0);
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

    Livewire::test(App\Filament\Staff\Pages\FillExamResults::class)
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

    Livewire::test(App\Filament\Staff\Pages\FillExamResults::class)
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

it('shows the fill exam results page to staff', function (): void {
    actingAs(Staff::factory()->create(), 'staff');

    get('/staff/fill-exam-results')->assertOk();
});
