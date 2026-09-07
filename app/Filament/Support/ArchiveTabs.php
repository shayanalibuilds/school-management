<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

/**
 * Active / Inactive tabs for list pages of archiveable school records.
 * Deleting is not possible in this system, so the inactive side of the
 * ledger needs to be reachable: the tabs switch between the live
 * records and the archived ones.
 */
final class ArchiveTabs
{
    /**
     * @return array<string|int, Tab>
     */
    public static function make(): array
    {
        return [
            'active' => Tab::make()
                ->label('Active')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'active')),
            'inactive' => Tab::make()
                ->label('Inactive')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', '!=', 'active')),
        ];
    }
}
