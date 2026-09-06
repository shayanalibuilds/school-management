<?php

declare(strict_types=1);

namespace App\Enums;

enum ExpenseRecurrence: string
{
    case OneTime = 'one_time';

    case Weekly = 'weekly';

    case Monthly = 'monthly';

    case Yearly = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::OneTime => 'One time',
            self::Weekly => 'Weekly',
            self::Monthly => 'Monthly',
            self::Yearly => 'Yearly',
        };
    }
}
