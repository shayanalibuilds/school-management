<?php

declare(strict_types=1);

namespace App\Filament\Resources\Attendances\Pages;

use App\Filament\Resources\Attendances\AttendanceResource;
use App\Filament\Support\ExportCsvAction;
use App\Importers\AttendanceImporter;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

final class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        // Attendance is recorded from the Fill attendance page;
        // this table is for reviewing and correcting records.
        return [
            ExportCsvAction::make('attendance'),
            ImportAction::make()
                ->importer(AttendanceImporter::class)
                ->label('Import CSV'),
        ];
    }
}
