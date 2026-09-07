<?php

declare(strict_types=1);

use App\Enums\ExpenseRecurrence;
use App\Enums\PaymentStatus;
use App\Enums\PayrollStatus;
use App\Filament\Widgets\SchoolProgressChart;
use App\Filament\Widgets\SchoolProgressStats;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Payroll;
use App\Support\AppSettings;
use App\Support\SchoolProgress;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('is disabled by default and persists the toggle', function (): void {
    expect(AppSettings::statsEnabled())->toBeFalse();

    AppSettings::set(AppSettings::STATS_ENABLED, '1');

    expect(AppSettings::statsEnabled())->toBeTrue();

    AppSettings::set(AppSettings::STATS_ENABLED, '0');

    expect(AppSettings::statsEnabled())->toBeFalse();
});

it('counts only completed payments as income in their paid month', function (): void {
    $currentMonth = today()->format('Y-m');
    $lastMonth = today()->subMonth()->format('Y-m');

    Payment::factory()->completed()->create(['amount' => 2500, 'paid_at' => today()]);
    Payment::factory()->completed()->create(['amount' => 1000, 'paid_at' => today()->subMonth()]);
    Payment::factory()->create(['amount' => 5000, 'status' => PaymentStatus::Pending, 'paid_at' => today()]);
    Payment::factory()->create(['amount' => 700, 'status' => PaymentStatus::Failed, 'paid_at' => today()]);

    $income = SchoolProgress::incomeByMonth();

    expect($income[$currentMonth])->toBe(2500.0)
        ->and($income[$lastMonth])->toBe(1000.0);
});

it('estimates spending from paid salaries and expense recurrences', function (): void {
    $currentMonth = today()->format('Y-m');

    Payroll::factory()->create([
        'amount' => 10000,
        'status' => PayrollStatus::Paid,
        'paid_at' => today(),
    ]);

    // A pending payroll is not money out yet.
    Payroll::factory()->create([
        'amount' => 40000,
        'status' => PayrollStatus::Pending,
        'paid_at' => null,
    ]);

    Expense::factory()->create(['name' => 'Cleaning', 'amount' => 100, 'recurrence' => ExpenseRecurrence::Daily]);
    Expense::factory()->create(['name' => 'Security', 'amount' => 200, 'recurrence' => ExpenseRecurrence::Weekly]);
    Expense::factory()->create(['name' => 'Internet', 'amount' => 500, 'recurrence' => ExpenseRecurrence::Monthly]);
    Expense::factory()->create(['name' => 'Insurance', 'amount' => 12000, 'recurrence' => ExpenseRecurrence::Yearly]);
    Expense::factory()->create(['name' => 'Whiteboard', 'amount' => 4000, 'recurrence' => ExpenseRecurrence::OneTime]);

    $spending = SchoolProgress::spendingByMonth();

    // 10000 salary + 3000 daily + 800 weekly + 500 monthly + 1000 yearly + 4000 one-time.
    expect($spending[$currentMonth])->toBe(19300.0);
});

it('moves one-time expenses into the month they were recorded', function (): void {
    $lastMonthKey = today()->subMonth()->format('Y-m');

    Expense::factory()->create([
        'name' => 'Generator repair',
        'amount' => 6000,
        'recurrence' => ExpenseRecurrence::OneTime,
        'created_at' => today()->subMonth(),
    ]);

    $spending = SchoolProgress::spendingByMonth();

    expect($spending[$lastMonthKey])->toBe(6000.0);
});

it('summarises the current month as income, spending and net', function (): void {
    Payment::factory()->completed()->create(['amount' => 2500, 'paid_at' => today()]);
    Payroll::factory()->create(['amount' => 1000, 'status' => PayrollStatus::Paid, 'paid_at' => today()]);
    Expense::factory()->create(['amount' => 100, 'recurrence' => ExpenseRecurrence::Monthly]);

    $summary = SchoolProgress::currentMonthSummary();

    expect($summary['income'])->toBe(2500.0)
        ->and($summary['spending'])->toBe(1100.0)
        ->and($summary['net'])->toBe(1400.0);
});

it('hides the stats widgets until enabled', function (): void {
    expect(SchoolProgressStats::canView())->toBeFalse()
        ->and(SchoolProgressChart::canView())->toBeFalse();

    AppSettings::set(AppSettings::STATS_ENABLED, '1');

    expect(SchoolProgressStats::canView())->toBeTrue()
        ->and(SchoolProgressChart::canView())->toBeTrue();
});

it('renders the stats widgets on the admin dashboard once enabled', function (): void {
    actingAs(App\Models\Admin::factory()->create(), 'admin');

    AppSettings::set(AppSettings::STATS_ENABLED, '1');

    Livewire::test(SchoolProgressStats::class)->assertOk();
    Livewire::test(SchoolProgressChart::class)->assertOk();

    get('/dashboard')->assertOk();
});

it('formats month keys for the chart labels', function (): void {
    expect(SchoolProgress::monthLabel(Carbon::parse('2026-09-01')->format('Y-m')))->toBe('Sep 2026')
        ->and(SchoolProgress::monthKeys())->toHaveCount(12)
        ->and(SchoolProgress::monthKeys()[11])->toBe(today()->format('Y-m'));
});
