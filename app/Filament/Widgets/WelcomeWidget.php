<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\AccountWidget;

final class WelcomeWidget extends AccountWidget
{
    protected static ?int $sort = -3;

    protected int|string|array $columnSpan = 'full';
}
