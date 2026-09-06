<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Support\ExportCsvAction;
use App\Importers\PaymentImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

final class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportCsvAction::make('payments'),
            ImportAction::make()
                ->importer(PaymentImporter::class)
                ->label('Import CSV'),
        ];
    }
}
