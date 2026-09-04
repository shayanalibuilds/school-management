<?php

declare(strict_types=1);

namespace App\Filament\Resources\Parents\Pages;

use App\Filament\Resources\Parents\ParentResource;
use App\Filament\Support\CreateWizardRecord;

final class CreateParent extends CreateWizardRecord
{
    protected static string $resource = ParentResource::class;
}
