<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Support\CreateWizardRecord;

final class CreatePayment extends CreateWizardRecord
{
    protected static string $resource = PaymentResource::class;
}
