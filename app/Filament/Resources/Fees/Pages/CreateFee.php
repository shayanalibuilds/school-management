<?php

declare(strict_types=1);

namespace App\Filament\Resources\Fees\Pages;

use App\Filament\Resources\Fees\FeeResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateFee extends CreateRecord
{
    protected static string $resource = FeeResource::class;
}
