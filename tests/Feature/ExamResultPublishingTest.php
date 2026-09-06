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
        ->set('classId', $class->getKey())
        ->set('subjectId', $subject->getKey())
        ->set('year', '2026')
        ->call('publish')
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
