<?php

declare(strict_types=1);

use App\Enums\ExamResultStatus;
use App\Filament\Pages\FillExamResults;
use App\Models\Admin;
use App\Models\ExamResult;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\Subject;
use App\Support\ExamResultsSync;
use App\Support\Positions;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('saves results as drafts and never duplicates existing records', function (): void {
    $admin = Admin::factory()->create();
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();
    $students = Student::factory()->count(2)->create(['student_class_id' => $class->getKey()]);

    actingAs($admin, 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire::test(FillExamResults::class)
        ->set('classId', $class->getKey())
        ->set('subjectId', $subject->getKey())
        ->set('year', (string) today()->year)
        ->set("marks.{$students[0]->getKey()}", '85')
        ->set("marks.{$students[1]->getKey()}", '90')
        ->call('save')
        ->assertNotified()
        ->assertSuccessful();

    expect(ExamResult::query()->count())->toBe(2)
        ->and(ExamResult::query()->first()->status)->toBe(ExamResultStatus::Draft)
        ->and(ExamResult::query()->first()->published_at)->toBeNull();

    // Re-saving updates the same rows instead of adding records.
    Livewire::test(FillExamResults::class)
        ->set('classId', $class->getKey())
        ->set('subjectId', $subject->getKey())
        ->set('year', (string) today()->year)
        ->set("marks.{$students[0]->getKey()}", '88')
        ->call('save');

    expect(ExamResult::query()->count())->toBe(2)
        ->and(ExamResult::query()->where('student_id', $students[0]->getKey())->first()->marks)->toEqual(88.0);
});

it('publishes a whole result sheet and opens the 30 day correction window', function (): void {
    $admin = Admin::factory()->create();
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();
    $student = Student::factory()->create(['student_class_id' => $class->getKey()]);

    actingAs($admin, 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    Carbon::setTestNow(Carbon::parse('2026-03-01 10:00:00'));

    Livewire::test(FillExamResults::class)
        ->set('classId', $class->getKey())
        ->set('subjectId', $subject->getKey())
        ->set('year', '2026')
        ->set("marks.{$student->getKey()}", '72')
        ->call('save')
        ->assertNotified();

    expect(ExamResult::query()->count())->toBe(1)
        ->and(ExamResult::query()->sole()->status)->toBe(ExamResultStatus::Draft);

    Livewire::test(FillExamResults::class)
        ->set('year', '2026')
        ->call('publishAll')
        ->assertNotified();

    $result = ExamResult::query()->sole();
    $result->refresh();

    expect($result->status)->toBe(ExamResultStatus::Published)
        ->and($result->published_at->toDateTimeString())->toBe('2026-03-01 10:00:00')
        ->and($result->recheckWindowEndsAt()->toDateString())->toBe('2026-03-31')
        ->and($result->isEditable())->toBeTrue();

    Carbon::setTestNow();
});

it('lets teachers correct published results inside the window', function (): void {
    $staff = Staff::factory()->create();
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();
    $student = Student::factory()->create(['student_class_id' => $class->getKey()]);

    $staff->assignments()->create([
        'student_class_id' => $class->getKey(),
        'subject_id' => $subject->getKey(),
    ]);

    Carbon::setTestNow(Carbon::parse('2026-03-01 10:00:00'));

    $result = ExamResult::factory()->create([
        'student_id' => $student->getKey(),
        'student_class_id' => $class->getKey(),
        'subject_id' => $subject->getKey(),
        'year' => 2026,
        'marks' => 55,
        'status' => ExamResultStatus::Published->value,
        'published_at' => now(),
    ]);

    actingAs($staff, 'staff');
    Filament\Facades\Filament::setCurrentPanel('staff');

    Carbon::setTestNow(Carbon::parse('2026-03-20 10:00:00'));

    Livewire::test(App\Filament\Staff\Pages\FillExamResults::class)
        ->set('classId', $class->getKey())
        ->set('subjectId', $subject->getKey())
        ->set('year', '2026')
        ->set("marks.{$student->getKey()}", '61')
        ->call('save')
        ->assertSuccessful();

    $result->refresh();

    expect($result->marks)->toEqual(61.0)
        ->and($result->status)->toBe(ExamResultStatus::Published)
        ->and($result->published_at->toDateString())->toBe('2026-03-01');

    Carbon::setTestNow();
});

it('locks a result sheet 30 days after publishing and refuses all edits', function (): void {
    $admin = Admin::factory()->create();
    $staff = Staff::factory()->create();
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();
    $student = Student::factory()->create(['student_class_id' => $class->getKey()]);

    $staff->assignments()->create([
        'student_class_id' => $class->getKey(),
        'subject_id' => $subject->getKey(),
    ]);

    $result = ExamResult::factory()->create([
        'student_id' => $student->getKey(),
        'student_class_id' => $class->getKey(),
        'subject_id' => $subject->getKey(),
        'year' => 2026,
        'marks' => 55,
        'status' => ExamResultStatus::Published->value,
        'published_at' => Carbon::parse('2026-03-01 10:00:00'),
    ]);

    actingAs($admin, 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    // Day 30: still correctable.
    Carbon::setTestNow(Carbon::parse('2026-03-31 09:59:59'));
    expect($result->isEditable())->toBeTrue()
        ->and(ExamResult::sheetIsLocked($class->getKey(), $subject->getKey(), 2026))->toBeFalse();

    // Day 31: locked for everyone.
    Carbon::setTestNow(Carbon::parse('2026-04-01 10:00:01'));
    expect($result->isEditable())->toBeFalse()
        ->and(ExamResult::sheetIsLocked($class->getKey(), $subject->getKey(), 2026))->toBeTrue();

    Livewire::test(FillExamResults::class)
        ->set('classId', $class->getKey())
        ->set('subjectId', $subject->getKey())
        ->set('year', '2026')
        ->set("marks.{$student->getKey()}", '99')
        ->call('save')
        ->assertNotified('These results are locked');

    $result->refresh();

    expect($result->marks)->toEqual(55.0);

    Carbon::setTestNow();
});

it('hides drafts from public results and class positions', function (): void {
    $class = StudentClass::factory()->create();
    $students = Student::factory()->count(2)->create(['student_class_id' => $class->getKey()]);
    $subject = Subject::factory()->create();

    ExamResult::factory()->create([
        'student_id' => $students[0]->getKey(),
        'student_class_id' => $class->getKey(),
        'subject_id' => $subject->getKey(),
        'year' => 2026,
        'marks' => 90,
        'status' => ExamResultStatus::Published->value,
        'published_at' => now(),
    ]);

    ExamResult::factory()->create([
        'student_id' => $students[1]->getKey(),
        'student_class_id' => $class->getKey(),
        'subject_id' => $subject->getKey(),
        'year' => 2026,
        'marks' => 10,
        'status' => ExamResultStatus::Draft->value,
    ]);

    expect($students[0]->examResults()->published()->count())->toBe(1)
        ->and($students[1]->examResults()->published()->count())->toBe(0)
        ->and(Positions::forClass($class, 2026)->has($students[1]->getKey()))->toBeFalse()
        ->and(Positions::forClass($class, 2026)->get($students[0]->getKey()))->toBe(1);
});

it('reports unchecked class subject sheets for the year', function (): void {
    $class = StudentClass::factory()->create(['name' => 'Class 5']);
    $otherClass = StudentClass::factory()->create(['name' => 'Class 6']);
    $maths = Subject::factory()->create(['name' => 'Mathematics']);
    $english = Subject::factory()->create(['name' => 'English']);
    $class->subjects()->attach([$maths->getKey(), $english->getKey()]);
    $student = Student::factory()->create(['student_class_id' => $class->getKey()]);
    $otherStudent = Student::factory()->create(['student_class_id' => $otherClass->getKey()]);
    $otherClass->subjects()->attach($maths->getKey());

    expect(ExamResult::uncheckedSheets(2026))->toBe([
        'Class 5 - Mathematics',
        'Class 5 - English',
        'Class 6 - Mathematics',
    ])->and(ExamResult::allClassesChecked(2026))->toBeFalse();

    ExamResult::factory()->create(['student_id' => $student->getKey(), 'student_class_id' => $class->getKey(), 'subject_id' => $maths->getKey(), 'year' => 2026, 'marks' => 60]);
    ExamResult::factory()->create(['student_id' => $student->getKey(), 'student_class_id' => $class->getKey(), 'subject_id' => $english->getKey(), 'year' => 2026, 'marks' => 60]);
    ExamResult::factory()->create(['student_id' => $otherStudent->getKey(), 'student_class_id' => $otherClass->getKey(), 'subject_id' => $maths->getKey(), 'year' => 2026, 'marks' => 60]);

    expect(ExamResult::uncheckedSheets(2026))->toBeEmpty()
        ->and(ExamResult::allClassesChecked(2026))->toBeTrue();
});

it('publishes every class at once and keeps already published windows intact', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-05-01 10:00:00'));

    $admin = Admin::factory()->create();
    $classOne = StudentClass::factory()->create();
    $classTwo = StudentClass::factory()->create();
    $subject = Subject::factory()->create();
    $studentOne = Student::factory()->create(['student_class_id' => $classOne->getKey()]);
    $studentTwo = Student::factory()->create(['student_class_id' => $classTwo->getKey()]);

    // A draft in class one, a draft in class two, and an already
    // published row whose correction window must not restart.
    ExamResult::factory()->create(['student_id' => $studentOne->getKey(), 'student_class_id' => $classOne->getKey(), 'subject_id' => $subject->getKey(), 'year' => 2026, 'marks' => 70]);
    ExamResult::factory()->create(['student_id' => $studentTwo->getKey(), 'student_class_id' => $classTwo->getKey(), 'subject_id' => $subject->getKey(), 'year' => 2026, 'marks' => 80]);
    $alreadyPublished = ExamResult::factory()->published()->create([
        'student_id' => $studentTwo->getKey(),
        'student_class_id' => $classTwo->getKey(),
        'subject_id' => $subject->getKey(),
        'year' => 2025,
        'marks' => 90,
        'published_at' => Carbon::parse('2026-04-01 10:00:00'),
    ]);

    $published = ExamResultsSync::publishAll(2026, $admin->name);

    expect($published)->toBe(2)
        ->and(ExamResult::query()->where('year', 2026)->whereNotNull('published_at')->count())->toBe(2)
        ->and($alreadyPublished->refresh()->published_at->toDateTimeString())->toBe('2026-04-01 10:00:00')
        ->and(ExamResult::query()->where('year', 2026)->where('status', ExamResultStatus::Published->value)->count())->toBe(2);

    Carbon::setTestNow();
});

it('exposes the global publish state and refuses to publish unchecked sheets', function (): void {
    $admin = Admin::factory()->create();
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();
    $class->subjects()->attach($subject->getKey());
    $student = Student::factory()->create(['student_class_id' => $class->getKey()]);

    actingAs($admin, 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    $page = Livewire::test(FillExamResults::class)
        ->set('year', '2026');

    expect($page->instance()->globalPublish)->toBe([
        'drafts' => 0,
        'missing' => [$class->name.' - '.$subject->name],
        'ready' => false,
    ]);

    // Fill the missing sheet: the state flips to ready only when drafts exist.
    Livewire::test(FillExamResults::class)
        ->set('classId', $class->getKey())
        ->set('subjectId', $subject->getKey())
        ->set('year', '2026')
        ->set("marks.{$student->getKey()}", '64')
        ->call('save')
        ->assertNotified();

    $page = Livewire::test(FillExamResults::class)->set('year', '2026');

    expect($page->instance()->globalPublish['ready'])->toBeTrue()
        ->and($page->instance()->globalPublish['drafts'])->toBe(1);

    $page->call('publishAll')->assertNotified('Exam results published for all classes (1 students)');

    expect(ExamResult::query()->sole()->status)->toBe(ExamResultStatus::Published);
});
