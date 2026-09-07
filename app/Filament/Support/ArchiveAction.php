<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Deleting a school record never removes the row - it archives it by
 * flipping the status (see App\Models\Concerns\ArchivesInsteadOfDeleting).
 * These actions give that flow an honest name in the UI.
 */
final class ArchiveAction
{
    public static function make(): Action
    {
        return Action::make('archive')
            ->label('Archive')
            ->icon('heroicon-m-archive-box-arrow-down')
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('The record is kept but marked inactive, so nothing is ever deleted from the system. You can find it under the Inactive tab.')
            ->modalSubmitActionLabel('Archive')
            ->action(function (Model $record): void {
                $record->archive();
            });
    }

    public static function bulk(): BulkAction
    {
        return BulkAction::make('archive')
            ->label('Archive')
            ->icon('heroicon-m-archive-box-arrow-down')
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('The records are kept but marked inactive, so nothing is ever deleted from the system. You can find them under the Inactive tab.')
            ->modalSubmitActionLabel('Archive')
            /** @param Collection<int, Model> $records */
            ->action(function (Collection $records): void {
                $records->each(fn (Model $record) => $record->archive());
            })
            ->deselectRecordsAfterCompletion();
    }
}
