<?php

declare(strict_types=1);

use App\Enums\ExamResultStatus;
use App\Importers\AttendanceImporter;
use App\Importers\ExamResultImporter;
use App\Importers\GuardianImporter;
use App\Importers\ParentImporter;
use App\Importers\StaffImporter;
use App\Importers\StudentImporter;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\Guardian;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\StudentParent;
use App\Models\Subject;
use Filament\Actions\Imports\Models\Import as FilamentImport;
use Illuminate\Support\Carbon;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function testImport(string $importer): FilamentImport
{
    return FilamentImport::query()->create([
        'file_name' => 'test.csv',
        'file_path' => 'test.csv',
        'importer' => $importer,
        'total_rows' => 0,
        'user_id' => Admin::factory()->create()->getKey(),
    ]);
}

it('shows the import CSV button next to export on resource pages', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    foreach ([
        'students', 'attendances', 'exam-results', 'fees', 'payments',
        'payrolls', 'expenses', 'parents', 'guardians',
    ] as $slug) {
        get("/dashboard/{$slug}")
            ->assertOk()
            ->assertSee('Import CSV')
            ->assertSee('Export CSV');
    }
});

it('imports students keyed by GR # and updates existing rows', function (): void {
    $class = StudentClass::factory()->create(['name' => 'Class 2']);
    Student::factory()->create(['gr_no' => 'GR9001', 'name' => 'Old Name', 'student_class_id' => $class->getKey()]);

    $importer = new StudentImporter(
        testImport(StudentImporter::class),
        ['gr_no' => 'gr_no', 'name' => 'name', 'class' => 'class', 'joining_date' => 'joining_date', 'status' => 'status'],
        [],
    );

    $importer(['gr_no' => 'GR9001', 'name' => 'Ahmed Khan', 'class' => 'Class 2', 'joining_date' => '2026-01-10', 'status' => 'active']);
    $importer(['gr_no' => 'GR9002', 'name' => 'Sara Ali', 'class' => 'Class 2', 'joining_date' => '2026-02-01', 'status' => 'active']);

    expect(Student::query()->count())->toBe(2)
        ->and(Student::query()->where('gr_no', 'GR9001')->first()->name)->toBe('Ahmed Khan');
});

it('imports attendance rows without duplicating a student and day', function (): void {
    $class = StudentClass::factory()->create();
    $student = Student::factory()->create(['gr_no' => 'GR7001', 'student_class_id' => $class->getKey()]);

    Carbon::setTestNow(Carbon::parse('2026-05-10 09:00:00'));

    $importer = new AttendanceImporter(
        testImport(AttendanceImporter::class),
        ['student_gr_no' => 'student_gr_no', 'date' => 'date', 'status' => 'status'],
        [],
    );

    $importer(['student_gr_no' => 'GR7001', 'date' => '2026-05-10', 'status' => 'absent']);
    $importer(['student_gr_no' => 'GR7001', 'date' => '2026-05-10', 'status' => 'present']);

    expect(Attendance::query()->where('student_id', $student->getKey())->count())->toBe(1)
        ->and(Attendance::query()->where('student_id', $student->getKey())->first()->status->value)->toBe('present');

    Carbon::setTestNow();
});

it('imports exam results as drafts and refuses locked sheets', function (): void {
    $class = StudentClass::factory()->create();
    Subject::factory()->create(['name' => 'Physics']);
    Student::factory()->create(['gr_no' => 'GR6001', 'student_class_id' => $class->getKey()]);

    $importer = new ExamResultImporter(
        testImport(ExamResultImporter::class),
        ['student_gr_no' => 'student_gr_no', 'subject' => 'subject', 'year' => 'year', 'marks' => 'marks', 'total_marks' => 'total_marks'],
        [],
    );

    $importer(['student_gr_no' => 'GR6001', 'subject' => 'Physics', 'year' => '2026', 'marks' => '88', 'total_marks' => '100']);

    expect(ExamResult::query()->count())->toBe(1)
        ->and(ExamResult::query()->sole()->marks)->toEqual(88.0);

    ExamResult::query()->sole()->update([
        'status' => ExamResultStatus::Published->value,
        'published_at' => Carbon::parse('2026-03-01'),
    ]);

    Carbon::setTestNow(Carbon::parse('2026-04-15 09:00:00'));

    $importer(['student_gr_no' => 'GR6001', 'subject' => 'Physics', 'year' => '2026', 'marks' => '10', 'total_marks' => '100']);

    expect(ExamResult::query()->sole()->marks)->toEqual(88.0);

    Carbon::setTestNow();
});

it('links imported parents and guardians to children by GR #', function (): void {
    $class = StudentClass::factory()->create();
    $student = Student::factory()->create(['gr_no' => 'GR5001', 'student_class_id' => $class->getKey()]);

    $parentImporter = new ParentImporter(
        testImport(ParentImporter::class),
        ['name' => 'name', 'cnic' => 'cnic', 'phone' => 'phone', 'occupation' => 'occupation', 'children_gr_no' => 'children_gr_no'],
        [],
    );
    $parentImporter(['name' => 'Kamran', 'cnic' => '35202-1111111-1', 'phone' => '0300-1111111', 'occupation' => 'Engineer', 'children_gr_no' => 'GR5001;GR9999']);

    $guardianImporter = new GuardianImporter(
        testImport(GuardianImporter::class),
        ['name' => 'name', 'cnic' => 'cnic', 'phone' => 'phone', 'relation' => 'relation', 'students_gr_no' => 'students_gr_no'],
        [],
    );
    $guardianImporter(['name' => 'Nadia', 'cnic' => '35202-2222222-2', 'phone' => '0300-2222222', 'relation' => 'Aunt', 'students_gr_no' => 'GR5001']);

    expect(StudentParent::query()->where('cnic', '35202-1111111-1')->count())->toBe(1)
        ->and(StudentParent::query()->where('cnic', '35202-1111111-1')->first()->students()->pluck('students.id'))->toContain($student->getKey())
        ->and(Guardian::query()->where('cnic', '35202-2222222-2')->first()->students()->pluck('students.id'))->toContain($student->getKey());
});

it('imports staff keyed by CNIC with a default status', function (): void {
    $importer = new StaffImporter(
        testImport(StaffImporter::class),
        ['name' => 'name', 'cnic' => 'cnic', 'email' => 'email', 'phone' => 'phone', 'joining_date' => 'joining_date', 'status' => 'status'],
        [],
    );

    $importer(['name' => 'Imported Teacher', 'cnic' => '11111-1111111-1', 'email' => 'imported@school.test', 'phone' => '0300-1111111', 'joining_date' => '2025-04-01', 'status' => 'active']);
    $importer(['name' => 'Updated Teacher', 'cnic' => '11111-1111111-1', 'email' => 'updated@school.test', 'phone' => '', 'joining_date' => '2025-04-01', 'status' => 'on_leave']);
    $importer(['name' => 'Default Status', 'cnic' => '22222-2222222-2', 'email' => '', 'phone' => '', 'joining_date' => '2026-01-10', 'status' => '']);

    $imported = Staff::query()->where('cnic', '11111-1111111-1')->first();

    expect(Staff::query()->count())->toBe(2)
        ->and($imported->name)->toBe('Updated Teacher')
        ->and($imported->email)->toBe('updated@school.test')
        ->and($imported->status->value)->toBe('on_leave')
        ->and(Staff::query()->where('cnic', '22222-2222222-2')->first()->status->value)->toBe('active');
});
