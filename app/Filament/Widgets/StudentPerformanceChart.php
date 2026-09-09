<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\ExamResult;
use App\Support\ChartScope;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class StudentPerformanceChart extends ChartWidget
{
    protected ?string $heading = 'Student performance (average marks %)';

    protected ?string $maxHeight = '280px';

    protected static ?int $sort = -3;

    /**
     * @return array<int, string>
     */
    protected function getFilters(): array
    {
        return [
            '5' => 'Last 5 years',
            '10' => 'Last 10 years',
            'all' => 'All time',
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $years = $this->getYears();

        if ($years->isEmpty()) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $averages = ExamResult::query()
            ->whereIn('year', $years->values()->all())
            ->when(is_array($scope = ChartScope::assignedClassIds()), fn (Builder $query): Builder => $query->whereIn('student_class_id', $scope))
            ->selectRaw('year, AVG(marks * 100.0 / NULLIF(total_marks, 0)) as average_percentage')
            ->groupBy('year')
            ->orderBy('year')
            ->pluck('average_percentage', 'year');

        $data = $years->map(
            fn (int $label, int $key): ?float => isset($averages[$label]) ? round((float) $averages[$label], 1) : null,
        );

        return [
            'datasets' => [
                [
                    'label' => 'Average %',
                    'data' => $data->values()->all(),
                    'borderColor' => '#23e7ba',
                    'backgroundColor' => '#23e7ba',
                    'fill' => false,
                    'spanGaps' => true,
                ],
            ],
            'labels' => $years->values()->all(),
        ];
    }

    /**
     * @return Collection<int, int>
     */
    private function getYears(): Collection
    {
        $current = (int) today()->year;
        $filter = $this->filter ?? '5';

        $start = match ($filter) {
            '10' => $current - 9,
            'all' => (int) (ExamResult::query()->min('year') ?? $current),
            default => $current - 4,
        };

        return collect(range($start, $current))->values();
    }
}
