<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExamResults\Pages;

use App\Filament\Resources\ExamResults\ExamResultResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListExamResults extends ListRecords
{
    protected static string $resource = ExamResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
