<?php

declare(strict_types=1);

namespace App\Filament\Resources\Timetables\Pages;

use App\Filament\Resources\Timetables\TimetableResource;
use App\Filament\Support\CreateWizardRecord;

final class CreateTimetableSlot extends CreateWizardRecord
{
    protected static string $resource = TimetableResource::class;
}
