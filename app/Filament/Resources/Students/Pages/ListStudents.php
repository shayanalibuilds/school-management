<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Filament\Support\ExportCsvAction;
use App\Importers\StudentImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

final class ListStudents extends ListRecords
{
    /**
     * The sidebar's Active / Inactive accordion entries own the switch:
     * `?tab=inactive` deep-links into the archived view and no tab bar is
     * rendered on the page itself.
     */
    #[Url]
    public string $tab = 'active';

    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportCsvAction::make('students'),
            ImportAction::make()
                ->importer(StudentImporter::class)
                ->label('Import CSV'),
        ];
    }

    /**
     * @param  Builder<Student>  $query
     * @return Builder<Student>
     */
    protected function modifyQueryWithActiveTab(Builder $query, bool $isResolvingRecord = false): Builder
    {
        // Nothing is ever deleted: the inactive side of the ledger is the
        // view behind the sidebar's "Inactive ..." accordion entry.
        return $query->where('status', $this->tab === 'inactive' ? '!=' : '=', 'active');
    }
}
