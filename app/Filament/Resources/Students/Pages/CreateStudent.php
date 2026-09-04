<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Filament\Support\CreateWizardRecord;

final class CreateStudent extends CreateWizardRecord
{
    protected static string $resource = StudentResource::class;
}
