<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentClasses\Pages;

use App\Filament\Resources\StudentClasses\StudentClassResource;
use App\Filament\Support\ArchiveAction;
use Filament\Resources\Pages\EditRecord;

final class EditStudentClass extends EditRecord
{
    protected static string $resource = StudentClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ArchiveAction::make(),
        ];
    }
}
