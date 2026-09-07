<?php

declare(strict_types=1);

namespace App\Filament\Resources\FeeStructures\Pages;

use App\Filament\Resources\FeeStructures\FeeStructureResource;
use App\Filament\Support\ArchiveTabs;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListFeeStructures extends ListRecords
{
    protected static string $resource = FeeStructureResource::class;

    /**
     * Nothing is ever deleted: the Inactive tab is where archived
     * records live.
     */
    public function getTabs(): array
    {
        return ArchiveTabs::make();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
