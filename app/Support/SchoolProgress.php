<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ExpenseRecurrence;
use App\Enums\PaymentStatus;
use App\Enums\PayrollStatus;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Payroll;
use Illuminate\Support\Carbon;

final class SchoolProgress
{
    /**
     * Month keys (Y-m) covered by progress stats: the current month
     * and the ones before it, oldest first.
     *
     * @return list<string>
     */
    public static function monthKeys(int $months = 12): array
    {
        $keys = [];

        for ($i = $months - 1; $i >= 1; $i--) {
            $keys[] = today()->subMonths($i)->format('Y-m');
        }

        $keys[] = today()->format('Y-m');

        return $keys;
    }

    /**
     * Completed fee payments grouped by month (Y-m). Pending and
     * failed payments never count as income.
     *
     * @return array<string, float>
     */
    public static function incomeByMonth(): array
    {
        return self::sumByMonth(
            Payment::query()
                ->where('status', PaymentStatus::Completed->value)
                ->whereNotNull('paid_at')
                ->where('paid_at', '>=', today()->subMonths(11)->startOfMonth()->startOfDay())
                ->get(),
            fn (Payment $payment): string => (string) $payment->paid_at?->format('Y-m'),
            fn (Payment $payment): float => (float) $payment->amount,
        );
    }

    /**
     * Paid salaries grouped by month (Y-m). Pending payrolls are not
     * money out of the door yet.
     *
     * @return array<string, float>
     */
    public static function payrollByMonth(): array
    {
        return self::sumByMonth(
            Payroll::query()
                ->where('status', PayrollStatus::Paid->value)
                ->whereNotNull('paid_at')
                ->where('paid_at', '>=', today()->subMonths(11)->startOfMonth()->startOfDay())
                ->get(),
            fn (Payroll $payroll): string => (string) $payroll->paid_at?->format('Y-m'),
            fn (Payroll $payroll): float => (float) $payroll->amount,
        );
    }

    /**
     * Monthly cost of one expense. Recurring expenses are normalized
     * to a per-month equivalent (a daily expense costs ~30x its amount
     * each month, a yearly one 1/12th). One-time expenses cost their
     * full amount once, in the month they were recorded.
     */
    public static function expenseMonthlyEquivalent(Expense $expense): float
    {
        $amount = (float) $expense->amount;

        return match ($expense->recurrence) {
            ExpenseRecurrence::OneTime => $amount,
            ExpenseRecurrence::Daily => round($amount * 30, 2),
            ExpenseRecurrence::Weekly => round($amount * 4, 2),
            ExpenseRecurrence::Monthly => $amount,
            ExpenseRecurrence::Yearly => round($amount / 12, 2),
        };
    }

    /**
     * Estimated spending per month (Y-m): paid salaries plus every
     * expense's monthly equivalent. One-time expenses land in the
     * month they were recorded.
     *
     * @return array<string, float>
     */
    public static function spendingByMonth(): array
    {
        $months = self::monthKeys();
        $spending = array_fill_keys($months, 0.0);

        foreach (self::payrollByMonth() as $month => $amount) {
            if (array_key_exists($month, $spending)) {
                $spending[$month] += $amount;
            }
        }

        foreach (Expense::query()->get() as $expense) {
            $equivalent = self::expenseMonthlyEquivalent($expense);

            if ($expense->recurrence === ExpenseRecurrence::OneTime) {
                $recorded = $expense->created_at?->format('Y-m');

                if ($recorded !== null && array_key_exists($recorded, $spending)) {
                    $spending[$recorded] += $equivalent;
                }

                continue;
            }

            foreach ($months as $month) {
                $spending[$month] += $equivalent;
            }
        }

        return collect($spending)
            ->map(fn (float $amount): float => round($amount, 2))
            ->all();
    }

    /**
     * Current month at a glance: income, estimated spending and net.
     *
     * @return array{income: float, spending: float, net: float}
     */
    public static function currentMonthSummary(): array
    {
        $month = today()->format('Y-m');

        $income = self::incomeByMonth()[$month] ?? 0.0;
        $spending = self::spendingByMonth()[$month] ?? 0.0;

        return [
            'income' => $income,
            'spending' => $spending,
            'net' => round($income - $spending, 2),
        ];
    }

    /**
     * Human label for a Y-m month key, e.g. "Sep 2026".
     */
    public static function monthLabel(string $month): string
    {
        $date = Carbon::createFromFormat('Y-m', $month);

        return $date instanceof Carbon ? $date->format('M Y') : $month;
    }

    /**
     * Group models by month and sum a money column, limited to the
     * last 12 months.
     *
     * @template TModel of Payment|Payroll
     *
     * @param  iterable<int, TModel>  $models
     * @param  callable(TModel): string  $monthOf
     * @param  callable(TModel): float  $amountOf
     * @return array<string, float>
     */
    private static function sumByMonth(iterable $models, callable $monthOf, callable $amountOf): array
    {
        $months = self::monthKeys();
        $sums = array_fill_keys($months, 0.0);

        foreach ($models as $model) {
            $month = $monthOf($model);

            if (array_key_exists($month, $sums)) {
                $sums[$month] += $amountOf($model);
            }
        }

        return collect($sums)
            ->map(fn (float $amount): float => round($amount, 2))
            ->all();
    }
}
