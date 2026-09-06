<?php

declare(strict_types=1);

namespace App\Filament\Resources\Timetables\Pages;

use App\Filament\Resources\Timetables\TimetableResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditTimetableSlot extends EditRecord
{
    protected static string $resource = TimetableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
