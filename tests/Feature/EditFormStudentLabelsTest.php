<?php

declare(strict_types=1);

use App\Models\Admin;
use App\Models\ExamResult;
use App\Models\Fee;
use App\Models\Student;
use App\Models\StudentClass;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shows the student name, not the id, when editing an exam result of an inactive student', function (): void {
    $class = StudentClass::factory()->create();
    $student = Student::factory()->create([
        'name' => 'Departed Kid',
        'student_class_id' => $class->getKey(),
        'status' => 'left',
    ]);

    $result = ExamResult::factory()->create(['student_id' => $student->getKey()]);

    actingAs(Admin::factory()->create(), 'admin');

    // An inactive student is absent from the option list, so the edit
    // form used to fall back to displaying the raw record key.
    get("/dashboard/exam-results/{$result->getKey()}/edit")
        ->assertOk()
        ->assertSee('Departed Kid');
});

it('shows the student name, not the id, when editing a fee of an inactive student', function (): void {
    $class = StudentClass::factory()->create();
    $student = Student::factory()->create([
        'name' => 'Graduated Kid',
        'student_class_id' => $class->getKey(),
        'status' => 'graduated',
    ]);

    $fee = Fee::factory()->create(['student_id' => $student->getKey()]);

    actingAs(Admin::factory()->create(), 'admin');

    get("/dashboard/fees/{$fee->getKey()}/edit")
        ->assertOk()
        ->assertSee('Graduated Kid');
});
