<?php

declare(strict_types=1);

namespace App\Filament\Resources\Fees\Pages;

use App\Filament\Resources\Fees\FeeResource;
use App\Filament\Support\CreateWizardRecord;

final class CreateFee extends CreateWizardRecord
{
    protected static string $resource = FeeResource::class;
}
