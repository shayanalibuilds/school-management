<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Importers\StaffImporter;
use BackedEnum;
use Filament\Actions\ImportAction;
use Filament\Pages\Page;
use UnitEnum;

final class ImportExport extends Page
{
    protected string $view = 'filament.admin.pages.import-export';

    protected ?string $heading = 'Import / Export';

    protected static ?string $navigationLabel = 'Import / Export';

    protected static string|BackedEnum|null $navigationIcon = \Filament\Support\Icons\Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(StaffImporter::class)
                ->label('Import staff')
                ->color('primary'),
        ];
    }
}
