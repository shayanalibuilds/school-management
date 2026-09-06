<?php

declare(strict_types=1);

namespace App\Enums;

enum AssignmentAction: string
{
    case Add = 'add';

    case Remove = 'remove';

    public function label(): string
    {
        return match ($this) {
            self::Add => 'Request assignment',
            self::Remove => 'Request removal',
        };
    }
}
