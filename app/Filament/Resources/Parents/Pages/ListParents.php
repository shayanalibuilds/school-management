<?php

declare(strict_types=1);

namespace App\Filament\Resources\Parents\Pages;

use App\Filament\Resources\Parents\ParentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListParents extends ListRecords
{
    protected static string $resource = ParentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
