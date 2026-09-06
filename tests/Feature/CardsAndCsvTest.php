<?php

declare(strict_types=1);

use App\Models\Admin;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Http\UploadedFile;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('shows a printable student id card to admins', function (): void {
    $student = Student::factory()->create(['gr_no' => 'GR-77']);

    actingAs(Admin::factory()->create(), 'admin')
        ->get("/cards/student/{$student->getKey()}")
        ->assertOk()
        ->assertSee($student->name)
        ->assertSee('GR-77');
});

it('blocks staff from student id cards', function (): void {
    $student = Student::factory()->create();

    actingAs(Staff::factory()->create(), 'staff')
        ->get("/cards/student/{$student->getKey()}")
        ->assertRedirect();
});

it('shows staff their own staff card', function (): void {
    $staff = Staff::factory()->create(['name' => 'Card Teacher']);

    actingAs($staff, 'staff')
        ->get('/cards/me')
        ->assertOk()
        ->assertSee('Card Teacher')
        ->assertSee('Staff Card');
});

it('exports students as csv', function (): void {
    Student::factory()->create(['gr_no' => 'GR-1000', 'name' => 'Export Kid']);

    $response = actingAs(Admin::factory()->create(), 'admin')->get('/exports/students');

    $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->getContent();

    expect(str_contains($csv, 'gr_no,name,class,joining_date'))->toBeTrue()
        ->and(str_contains($csv, 'Export Kid'))->toBeTrue();
});

it('exports staff attendance and exam results as csv', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    foreach (['staff', 'attendance', 'exam-results'] as $type) {
        get("/exports/{$type}")
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
});

it('imports students from an uploaded csv', function (): void {
    $csv = "gr_no,name,class,joining_date,status\nGR-101,Imported Kid,Nursery,2026-08-15,active\n";

    $class = App\Models\StudentClass::factory()->create(['name' => 'Nursery']);

    actingAs(Admin::factory()->create(), 'admin');

    post('/imports/students', [
        'csv' => UploadedFile::fake()->createWithContent('students.csv', $csv),
    ])->assertRedirect();

    $student = Student::query()->where('gr_no', 'GR-101')->first();

    expect($student)->not->toBeNull()
        ->and($student->name)->toBe('Imported Kid')
        ->and($student->student_class_id)->toBe($class->getKey());
});

it('imports staff from an uploaded csv', function (): void {
    $csv = "name,cnic,email,joining_date,status\nImported Teacher,11111-1111111-1,imported@school.test,2025-04-01,active\n";

    actingAs(Admin::factory()->create(), 'admin');

    post('/imports/staff', [
        'csv' => UploadedFile::fake()->createWithContent('staff.csv', $csv),
    ])->assertRedirect();

    expect(Staff::query()->where('cnic', '11111-1111111-1')->exists())->toBeTrue();
});

it('shows the import export page to admins', function (): void {
    actingAs(Admin::factory()->create(), 'admin')
        ->get('/dashboard/import-export')
        ->assertOk()
        ->assertSee('Import staff')
        ->assertSee('Export to CSV');
});
