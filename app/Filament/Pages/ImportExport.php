<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

final class ImportExport extends Page
{
    protected string $view = 'filament.admin.pages.import-export';

    protected static ?string $navigationLabel = 'Import / Export';

    protected static string|BackedEnum|null $navigationIcon = \Filament\Support\Icons\Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';
}
