<?php

declare(strict_types=1);

namespace App\Filament\Resources\Subjects\Pages;

use App\Filament\Resources\Subjects\SubjectResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateSubject extends CreateRecord
{
    protected static string $resource = SubjectResource::class;
}
