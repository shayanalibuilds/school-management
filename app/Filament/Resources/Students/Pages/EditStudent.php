<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Filament\Support\ArchiveAction;
use Filament\Resources\Pages\EditRecord;

final class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ArchiveAction::make(),
        ];
    }
}
