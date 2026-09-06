<?php

declare(strict_types=1);

namespace App\Filament\Resources\Parents\Pages;

use App\Filament\Resources\Parents\ParentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditParent extends EditRecord
{
    protected static string $resource = ParentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
