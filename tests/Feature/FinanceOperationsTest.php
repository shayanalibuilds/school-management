<?php

declare(strict_types=1);

use App\Enums\ExpenseRecurrence;
use App\Enums\PayrollStatus;
use App\Models\Admin;
use App\Models\Expense;
use App\Models\Payroll;
use App\Models\Staff;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('records payroll for a staff member for a month', function (): void {
    $payroll = Payroll::factory()->create(['month' => '2026-09', 'amount' => 45000]);

    expect($payroll->status)->toBe(PayrollStatus::Pending)
        ->and($payroll->month)->toBe('2026-09')
        ->and($payroll->staff)->toBeInstanceOf(Staff::class);
});

it('marks payroll as paid with a timestamp', function (): void {
    $payroll = Payroll::factory()->create();

    $payroll->markPaid();

    expect($payroll->status)->toBe(PayrollStatus::Paid)
        ->and($payroll->paid_at)->not->toBeNull();
});

it('records expenses with recurrence', function (): void {
    $expense = Expense::factory()->create(['recurrence' => ExpenseRecurrence::Monthly]);

    expect($expense->recurrence)->toBe(ExpenseRecurrence::Monthly);
});

it('shows payroll and expense resources to admins', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    get('/dashboard/payrolls')->assertOk();
    get('/dashboard/expenses')->assertOk();
});
