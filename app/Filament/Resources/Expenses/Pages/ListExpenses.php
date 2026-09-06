<?php

declare(strict_types=1);

namespace App\Filament\Resources\Expenses\Pages;

use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Support\ExportCsvAction;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListExpenses extends ListRecords
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportCsvAction::make('expenses'),
        ];
    }
}
