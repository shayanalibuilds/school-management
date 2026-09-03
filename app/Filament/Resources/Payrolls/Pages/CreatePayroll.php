<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payrolls\Pages;

use App\Filament\Resources\Payrolls\PayrollResource;
use Filament\Resources\Pages\CreateRecord;

final class CreatePayroll extends CreateRecord
{
    protected static string $resource = PayrollResource::class;
}
