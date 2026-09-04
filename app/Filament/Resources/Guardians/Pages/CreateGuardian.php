<?php

declare(strict_types=1);

namespace App\Filament\Resources\Guardians\Pages;

use App\Filament\Resources\Guardians\GuardianResource;
use App\Filament\Support\CreateWizardRecord;

final class CreateGuardian extends CreateWizardRecord
{
    protected static string $resource = GuardianResource::class;
}
