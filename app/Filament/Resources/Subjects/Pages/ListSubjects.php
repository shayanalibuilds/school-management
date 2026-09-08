<?php

declare(strict_types=1);

namespace App\Filament\Resources\Subjects\Pages;

use App\Filament\Resources\Subjects\SubjectResource;
use App\Filament\Support\ArchiveTabs;
use App\Filament\Support\ReadsArchiveTabFromUrl;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListSubjects extends ListRecords
{
    use ReadsArchiveTabFromUrl;

    protected static string $resource = SubjectResource::class;

    /**
     * Nothing is ever deleted: the Inactive tab is where archived
     * records live.
     */
    public function getTabs(): array
    {
        return ArchiveTabs::make();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
