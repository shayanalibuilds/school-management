<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payrolls\Pages;

use App\Filament\Resources\Payrolls\PayrollResource;
use App\Filament\Support\CreateWizardRecord;

final class CreatePayroll extends CreateWizardRecord
{
    protected static string $resource = PayrollResource::class;
}
