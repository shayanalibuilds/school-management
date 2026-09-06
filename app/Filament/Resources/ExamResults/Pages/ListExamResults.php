<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExamResults\Pages;

use App\Filament\Resources\ExamResults\ExamResultResource;
use App\Filament\Support\ExportCsvAction;
use App\Importers\ExamResultImporter;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

final class ListExamResults extends ListRecords
{
    protected static string $resource = ExamResultResource::class;

    protected function getHeaderActions(): array
    {
        // Results are recorded from the Fill exam results page;
        // this table is for reviewing and correcting records.
        return [
            ExportCsvAction::make('exam-results'),
            ImportAction::make()
                ->importer(ExamResultImporter::class)
                ->label('Import CSV'),
        ];
    }
}
