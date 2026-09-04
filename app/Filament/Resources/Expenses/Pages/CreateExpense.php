<?php

declare(strict_types=1);

namespace App\Filament\Resources\Expenses\Pages;

use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Support\CreateWizardRecord;

final class CreateExpense extends CreateWizardRecord
{
    protected static string $resource = ExpenseResource::class;
}
