<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Staff;
use Filament\Facades\Filament;
use Filament\Panel;

final class ChartScope
{
    /**
     * Class ids the dashboard charts may read for the current panel user.
     *
     * @return array<int, int|string>|null null when unscoped (admin panel),
     *                                     an empty array hides all data
     */
    public static function assignedClassIds(): ?array
    {
        $panel = Filament::getCurrentPanel();

        if (! $panel instanceof Panel || $panel->getId() !== 'staff') {
            return null;
        }

        $staff = auth($panel->getAuthGuard())->user();

        if (! $staff instanceof Staff) {
            return [];
        }

        /** @var array<int, int|string> */
        return $staff->assignments()
            ->distinct()
            ->pluck('student_class_id')
            ->all();
    }
}
