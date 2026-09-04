<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\StudentClass;
use App\Support\ChartScope;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Database\Eloquent\Builder;

final class AttendanceChart extends ChartWidget
{
    protected ?string $heading = 'Attendance (present % per class, last 30 days)';

    protected ?string $maxHeight = '280px';

    protected static ?int $sort = -1;

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $classes = StudentClass::query()
            ->withCount('students')
            ->orderBy('name')
            ->when(is_array($scope = ChartScope::assignedClassIds()), fn (Builder $query): Builder => $query->whereIn('id', $scope))
            ->get();

        if ($classes->isEmpty()) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $since = today()->subDays(30);

        $presentRates = Attendance::query()
            ->whereDate('date', '>=', $since)
            ->selectRaw("student_class_id, AVG(CASE WHEN status = '".AttendanceStatus::Present->value."' THEN 1.0 ELSE 0.0 END) * 100 as present_rate")
            ->groupBy('student_class_id')
            ->pluck('present_rate', 'student_class_id');

        $labels = $classes->map(fn (StudentClass $class): string => $class->name);
        $data = $classes->map(
            fn (StudentClass $class): ?float => isset($presentRates[$class->getKey()]) ? round((float) $presentRates[$class->getKey()], 1) : null,
        );

        return [
            'datasets' => [
                [
                    'label' => 'Present %',
                    'data' => $data->values()->all(),
                    'backgroundColor' => '#2563eb',
                ],
            ],
            'labels' => $labels->all(),
        ];
    }
}
