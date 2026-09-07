<?php

declare(strict_types=1);

namespace App\Filament\Resources\Fees\Pages;

use App\Filament\Resources\Fees\FeeResource;
use Filament\Resources\Pages\EditRecord;

final class EditFee extends EditRecord
{
    protected static string $resource = FeeResource::class;
}
