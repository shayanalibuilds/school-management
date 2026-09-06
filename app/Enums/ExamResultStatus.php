<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ExamResultStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';

    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
        };
    }

    /**
     * Filament badge/table label.
     */
    public function getLabel(): ?string
    {
        return $this->label();
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'warning',
            self::Published => 'success',
        };
    }

    /**
     * Filament badge/table color.
     */
    public function getColor(): ?string
    {
        return $this->color();
    }
}
