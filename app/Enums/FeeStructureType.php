<?php

declare(strict_types=1);

namespace App\Enums;

enum FeeStructureType: string
{
    case Monthly = 'monthly';

    case OneTime = 'one_time';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::OneTime => 'One time',
        };
    }
}
