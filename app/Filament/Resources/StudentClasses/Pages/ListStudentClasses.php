<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentClasses\Pages;

use App\Filament\Resources\StudentClasses\StudentClassResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

final class ListStudentClasses extends ListRecords
{
    /**
     * The sidebar's Active / Inactive accordion entries own the switch:
     * `?tab=inactive` deep-links into the archived view and no tab bar is
     * rendered on the page itself.
     */
    #[Url]
    public string $tab = 'active';

    protected static string $resource = StudentClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * @param  Builder<StudentClass>  $query
     * @return Builder<StudentClass>
     */
    protected function modifyQueryWithActiveTab(Builder $query, bool $isResolvingRecord = false): Builder
    {
        // Nothing is ever deleted: the inactive side of the ledger is the
        // view behind the sidebar's "Inactive ..." accordion entry.
        return $query->where('status', $this->tab === 'inactive' ? '!=' : '=', 'active');
    }
}
