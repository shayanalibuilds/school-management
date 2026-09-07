<?php

declare(strict_types=1);

use App\Models\Expense;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\Payroll;
use App\Models\Student;
use App\Models\StudentClass;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

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
