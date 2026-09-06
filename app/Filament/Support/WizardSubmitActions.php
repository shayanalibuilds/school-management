<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;

/**
 * The closing row of a wizard form.
 *
 * Renders "Create" and "Create another" inside the wizard's final step so
 * the buttons are only reachable once every step is complete. On edit
 * pages the row is hidden ("Save changes" lives in the page footer and is
 * visible at every step instead).
 */
final class WizardSubmitActions
{
    public static function make(): Actions
    {
        return Actions::make([
            Action::make('create')
                ->label(__('filament-panels::resources/pages/create-record.form.actions.create.label'))
                ->submit('create'),
            Action::make('createAnother')
                ->label(__('filament-panels::resources/pages/create-record.form.actions.create_another.label'))
                ->action('createAnother')
                ->color('gray'),
        ])
            ->visible(fn (string $operation): bool => $operation === 'create')
            ->columnSpanFull();
    }
}
