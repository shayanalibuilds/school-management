<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Support\AppSettings;
use App\Support\SchoolProgress;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class SchoolProgressStats extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return AppSettings::statsEnabled();
    }

    /**
     * @return list<Stat>
     */
    protected function getStats(): array
    {
        $summary = SchoolProgress::currentMonthSummary();

        $netColor = $summary['net'] >= 0 ? 'success' : 'danger';

        return [
            Stat::make('Income this month', 'PKR '.number_format($summary['income'], 0))
                ->description('Completed fee payments')
                ->color('success'),
            Stat::make('Estimated spending this month', 'PKR '.number_format($summary['spending'], 0))
                ->description('Paid salaries + recurring expenses')
                ->color('danger'),
            Stat::make('Net this month', 'PKR '.number_format($summary['net'], 0))
                ->description('Income minus estimated spending')
                ->color($netColor),
        ];
    }
}
