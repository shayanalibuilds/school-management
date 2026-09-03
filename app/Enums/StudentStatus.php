<?php

declare(strict_types=1);

namespace App\Enums;

enum StudentStatus: string
{
    case Active = 'active';

    case Graduated = 'graduated';

    case Left = 'left';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Graduated => 'Graduated',
            self::Left => 'Left',
        };
    }
}
