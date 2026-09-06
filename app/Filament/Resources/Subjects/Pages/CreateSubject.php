<?php

declare(strict_types=1);

namespace App\Filament\Resources\Subjects\Pages;

use App\Filament\Resources\Subjects\SubjectResource;
use App\Filament\Support\CreateWizardRecord;

final class CreateSubject extends CreateWizardRecord
{
    protected static string $resource = SubjectResource::class;
}
