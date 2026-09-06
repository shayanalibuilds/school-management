<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payrolls\Pages;

use App\Filament\Resources\Payrolls\PayrollResource;
use App\Filament\Support\ExportCsvAction;
use App\Importers\PayrollImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

final class ListPayrolls extends ListRecords
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportCsvAction::make('payrolls'),
            ImportAction::make()
                ->importer(PayrollImporter::class)
                ->label('Import CSV'),
        ];
    }
}
