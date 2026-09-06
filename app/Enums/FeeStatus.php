<?php

declare(strict_types=1);

namespace App\Enums;

enum FeeStatus: string
{
    case Unpaid = 'unpaid';

    case Partial = 'partial';

    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::Partial => 'Partial',
            self::Paid => 'Paid',
        };
    }
}
