<?php

declare(strict_types=1);

namespace App\Filament\Staff\Resources\MyRequests\Pages;

use App\Filament\Staff\Resources\MyRequests\MyRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListMyRequests extends ListRecords
{
    protected static string $resource = MyRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
