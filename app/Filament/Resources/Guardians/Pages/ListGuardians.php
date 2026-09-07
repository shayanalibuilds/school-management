<?php

declare(strict_types=1);

namespace App\Filament\Resources\Guardians\Pages;

use App\Filament\Resources\Guardians\GuardianResource;
use App\Filament\Support\ArchiveTabs;
use App\Filament\Support\ExportCsvAction;
use App\Importers\GuardianImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

final class ListGuardians extends ListRecords
{
    protected static string $resource = GuardianResource::class;

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
            ExportCsvAction::make('guardians'),
            ImportAction::make()
                ->importer(GuardianImporter::class)
                ->label('Import CSV'),
        ];
    }
}
