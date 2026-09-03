<?php

declare(strict_types=1);

namespace App\Filament\Resources\FeeStructures\Pages;

use App\Filament\Resources\FeeStructures\FeeStructureResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateFeeStructure extends CreateRecord
{
    protected static string $resource = FeeStructureResource::class;
}
