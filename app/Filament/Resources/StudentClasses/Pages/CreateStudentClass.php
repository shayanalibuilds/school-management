<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentClasses\Pages;

use App\Filament\Resources\StudentClasses\StudentClassResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateStudentClass extends CreateRecord
{
    protected static string $resource = StudentClassResource::class;
}
