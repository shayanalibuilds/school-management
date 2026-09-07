<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Support\AppSettings;
use App\Support\SchoolProgress;
use Filament\Widgets\ChartWidget;

final class SchoolProgressChart extends ChartWidget
{
    protected ?string $heading = 'School progress (income vs spending, last 12 months)';

    protected ?string $maxHeight = '280px';

    protected static ?int $sort = -1;

    public static function canView(): bool
    {
        return AppSettings::statsEnabled();
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $months = SchoolProgress::monthKeys();

        $income = SchoolProgress::incomeByMonth();
        $spending = SchoolProgress::spendingByMonth();

        $labels = collect($months)
            ->map(fn (string $month): string => SchoolProgress::monthLabel($month))
            ->values()
            ->all();

        return [
            'datasets' => [
                [
                    'label' => 'Income (PKR)',
                    'data' => collect($months)->map(fn (string $month): float => $income[$month] ?? 0.0)->values()->all(),
                    'backgroundColor' => '#2563eb',
                ],
                [
                    'label' => 'Estimated spending (PKR)',
                    'data' => collect($months)->map(fn (string $month): float => $spending[$month] ?? 0.0)->values()->all(),
                    'backgroundColor' => '#ef4444',
                ],
            ],
            'labels' => $labels,
        ];
    }
}
