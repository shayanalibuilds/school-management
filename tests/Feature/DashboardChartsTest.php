<?php

declare(strict_types=1);

use App\Filament\Widgets\StudentPerformanceChart;
use App\Models\Admin;
use App\Models\ExamResult;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\Subject;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('charts average marks for real exam years on the admin dashboard', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    $class = StudentClass::factory()->create();
    $subjectA = Subject::factory()->create();
    $subjectB = Subject::factory()->create();
    $student = Student::factory()->create(['student_class_id' => $class->getKey()]);
    $year = (int) today()->year;

    ExamResult::factory()->create([
        'student_id' => $student->getKey(),
        'student_class_id' => $class->getKey(),
        'subject_id' => $subjectA->getKey(),
        'year' => $year,
        'marks' => 85,
    ]);
    ExamResult::factory()->create([
        'student_id' => $student->getKey(),
        'student_class_id' => $class->getKey(),
        'subject_id' => $subjectB->getKey(),
        'year' => $year,
        'marks' => 86,
    ]);
    ExamResult::factory()->create([
        'student_id' => $student->getKey(),
        'student_class_id' => $class->getKey(),
        'subject_id' => $subjectA->getKey(),
        'year' => $year - 1,
        'marks' => 70,
    ]);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(StudentPerformanceChart::class)
        ->assertSuccessful()
        ->assertSeeHtml('85.5');
});
