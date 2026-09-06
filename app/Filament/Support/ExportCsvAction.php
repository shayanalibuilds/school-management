<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Actions\Action;

/**
 * Header action that downloads one of the CSV exports
 * (see App\Http\Controllers\ExportController for the type map).
 */
final class ExportCsvAction
{
    public static function make(string $type, ?string $label = null): Action
    {
        return Action::make('export'.str_replace('-', '', ucwords($type, '-')).'Csv')
            ->label($label ?? 'Export CSV')
            ->icon('heroicon-m-arrow-down-tray')
            ->color('gray')
            ->url(route('exports', ['type' => $type]), shouldOpenInNewTab: true);
    }
}
