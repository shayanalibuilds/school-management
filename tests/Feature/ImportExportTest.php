<?php

declare(strict_types=1);

use App\Models\Expense;
use App\Models\Fee;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\Payroll;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\StudentParent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('exports fees, payments, payrolls, expenses, parents and guardians as csv', function (): void {
    actingAs(App\Models\Admin::factory()->create(), 'admin');

    foreach (['fees', 'payments', 'payrolls', 'expenses', 'parents', 'guardians'] as $type) {
        get("/exports/{$type}")
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
});

it('aborts for unknown export types', function (): void {
    actingAs(App\Models\Admin::factory()->create(), 'admin');

    get('/exports/not-a-type')->assertNotFound();
});

it('lists gr numbers and class names in the students export', function (): void {
    $class = StudentClass::factory()->create(['name' => 'Export Class']);
    Student::factory()->create(['gr_no' => 'GR-X1', 'name' => 'Export Kid', 'student_class_id' => $class->getKey()]);

    $csv = actingAs(App\Models\Admin::factory()->create(), 'admin')
        ->get('/exports/students')
        ->getContent();

    expect(str_contains($csv, 'gr_no,name,class,joining_date'))->toBeTrue()
        ->and(str_contains($csv, 'GR-X1'))->toBeTrue()
        ->and(str_contains($csv, 'Export Class'))->toBeTrue();
});

it('imports parents and links children by gr number', function (): void {
    $student = Student::factory()->create(['gr_no' => 'GR-77'.Str::random(2)]);

    $csv = "name,cnic,phone,occupation,children_gr_no\nTest Mom,35202-8888888-8,03008888888,Doctor,{$student->gr_no}\n";

    actingAs(App\Models\Admin::factory()->create(), 'admin');

    post('/imports/parents', [
        'csv' => UploadedFile::fake()->createWithContent('parents.csv', $csv),
    ])->assertRedirect();

    $parent = StudentParent::query()->where('cnic', '35202-8888888-8')->first();

    expect($parent)->not->toBeNull()
        ->and($parent->students->pluck('id'))->toContain($student->getKey());
});

it('imports guardians and links students by gr number', function (): void {
    $student = Student::factory()->create(['gr_no' => 'GR-99'.Str::random(2)]);

    $csv = "name,cnic,phone,relation,students_gr_no\nTest Uncle,35202-7777777-1,03007777777,Uncle,{$student->gr_no}\n";

    actingAs(App\Models\Admin::factory()->create(), 'admin');

    post('/imports/guardians', [
        'csv' => UploadedFile::fake()->createWithContent('guardians.csv', $csv),
    ])->assertRedirect();

    $guardian = Guardian::query()->where('cnic', '35202-7777777-1')->first();

    expect($guardian)->not->toBeNull()
        ->and($guardian->students->pluck('id'))->toContain($student->getKey());
});

it('writes domain-correct status defaults when none is given', function (): void {
    $fee = Fee::create([
        'student_id' => Student::factory()->create()->getKey(),
        'fee_structure_id' => App\Models\FeeStructure::factory()->create()->getKey(),
        'year' => 2026,
        'amount' => 1000,
    ]);

    $payment = Payment::create([
        'fee_id' => $fee->getKey(),
        'provider' => 'easypaisa',
        'amount' => 500,
    ]);

    $payroll = Payroll::create([
        'staff_id' => App\Models\Staff::factory()->create()->getKey(),
        'month' => '2026-08',
        'amount' => 40000,
    ]);

    $expense = Expense::create([
        'name' => 'Chalk',
        'amount' => 500,
        'recurrence' => 'monthly',
    ]);

    expect($fee->refresh()->status->value)->toBe('unpaid')
        ->and($payment->refresh()->status->value)->toBe('pending')
        ->and($payroll->refresh()->status->value)->toBe('pending')
        ->and($expense->recurrence->value)->toBe('monthly');
});
