<?php

declare(strict_types=1);

namespace App\Filament\Resources\Fees\Pages;

use App\Filament\Resources\Fees\FeeResource;
use App\Filament\Support\ExportCsvAction;
use App\Importers\FeeImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

final class ListFees extends ListRecords
{
    protected static string $resource = FeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportCsvAction::make('fees'),
            ImportAction::make()
                ->importer(FeeImporter::class)
                ->label('Import CSV'),
        ];
    }
}
