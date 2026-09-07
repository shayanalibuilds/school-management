<?php

declare(strict_types=1);

namespace App\Filament\Resources\FeeStructures\Pages;

use App\Filament\Resources\FeeStructures\FeeStructureResource;
use App\Filament\Support\ArchiveAction;
use Filament\Resources\Pages\EditRecord;

final class EditFeeStructure extends EditRecord
{
    protected static string $resource = FeeStructureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ArchiveAction::make(),
        ];
    }
}
