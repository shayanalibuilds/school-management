<?php

declare(strict_types=1);

namespace App\Filament\Resources\FeeStructures\Pages;

use App\Filament\Resources\FeeStructures\FeeStructureResource;
use App\Filament\Support\CreateWizardRecord;

final class CreateFeeStructure extends CreateWizardRecord
{
    protected static string $resource = FeeStructureResource::class;
}
