<?php

declare(strict_types=1);

namespace App\Filament\Resources\FeeStructures\Pages;

use App\Filament\Resources\FeeStructures\FeeStructureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

final class ListFeeStructures extends ListRecords
{
    /**
     * The sidebar's Active / Inactive accordion entries own the switch:
     * `?tab=inactive` deep-links into the archived view and no tab bar is
     * rendered on the page itself.
     */
    #[Url]
    public string $tab = 'active';

    protected static string $resource = FeeStructureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * @param  Builder<FeeStructure>  $query
     * @return Builder<FeeStructure>
     */
    protected function modifyQueryWithActiveTab(Builder $query, bool $isResolvingRecord = false): Builder
    {
        // Nothing is ever deleted: the inactive side of the ledger is the
        // view behind the sidebar's "Inactive ..." accordion entry.
        return $query->where('status', $this->tab === 'inactive' ? '!=' : '=', 'active');
    }
}
