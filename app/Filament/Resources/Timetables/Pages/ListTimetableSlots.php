<?php

declare(strict_types=1);

namespace App\Filament\Resources\Timetables\Pages;

use App\Filament\Resources\Timetables\TimetableResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListTimetableSlots extends ListRecords
{
    protected static string $resource = TimetableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'day_of_week';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'asc';
    }
}
