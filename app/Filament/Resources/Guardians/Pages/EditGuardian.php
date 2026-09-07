<?php

declare(strict_types=1);

namespace App\Filament\Resources\Guardians\Pages;

use App\Filament\Resources\Guardians\GuardianResource;
use App\Filament\Support\ArchiveAction;
use Filament\Resources\Pages\EditRecord;

final class EditGuardian extends EditRecord
{
    protected static string $resource = GuardianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ArchiveAction::make(),
        ];
    }
}
