<?php

declare(strict_types=1);

namespace App\Filament\Resources\Parents\Pages;

use App\Filament\Resources\Parents\ParentResource;
use App\Filament\Support\ExportCsvAction;
use App\Importers\ParentImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

final class ListParents extends ListRecords
{
    protected static string $resource = ParentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportCsvAction::make('parents'),
            ImportAction::make()
                ->importer(ParentImporter::class)
                ->label('Import CSV'),
        ];
    }
}
