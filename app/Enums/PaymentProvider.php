<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentProvider: string
{
    case JazzCash = 'jazzcash';

    case EasyPaisa = 'easypaisa';

    public function label(): string
    {
        return match ($this) {
            self::JazzCash => 'JazzCash',
            self::EasyPaisa => 'EasyPaisa',
        };
    }

    /**
     * Credential keys required before this provider can be offered.
     *
     * @return list<string>
     */
    public function requiredCredentials(): array
    {
        return match ($this) {
            self::JazzCash => ['merchant_id', 'password', 'integrity_salt'],
            self::EasyPaisa => ['store_id', 'hash_key'],
        };
    }
}
