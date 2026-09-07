<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentClasses\Pages;

use App\Filament\Resources\StudentClasses\StudentClassResource;
use App\Filament\Support\ArchiveTabs;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListStudentClasses extends ListRecords
{
    protected static string $resource = StudentClassResource::class;

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
