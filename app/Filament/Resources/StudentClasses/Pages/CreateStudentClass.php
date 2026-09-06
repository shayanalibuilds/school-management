<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentClasses\Pages;

use App\Filament\Resources\StudentClasses\StudentClassResource;
use App\Filament\Support\CreateWizardRecord;

final class CreateStudentClass extends CreateWizardRecord
{
    protected static string $resource = StudentClassResource::class;
}
