<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Filament\Support\ArchiveTabs;
use App\Filament\Support\ExportCsvAction;
use App\Filament\Support\ReadsArchiveTabFromUrl;
use App\Importers\StudentImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

final class ListStudents extends ListRecords
{
    use ReadsArchiveTabFromUrl;

    protected static string $resource = StudentResource::class;

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
            ExportCsvAction::make('students'),
            ImportAction::make()
                ->importer(StudentImporter::class)
                ->label('Import CSV'),
        ];
    }
}
