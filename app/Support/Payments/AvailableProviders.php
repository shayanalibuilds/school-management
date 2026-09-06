<?php

declare(strict_types=1);

namespace App\Support\Payments;

use App\Enums\PaymentProvider;
use App\Models\PaymentSetting;

final class AvailableProviders
{
    /**
     * Providers that are active AND have complete credentials — the only
     * ones that may be offered to parents on public pages.
     *
     * @return \Illuminate\Support\Collection<int, PaymentSetting>
     */
    public static function get(): \Illuminate\Support\Collection
    {
        return PaymentSetting::query()
            ->where('is_active', true)
            ->get()
            ->filter(fn (PaymentSetting $setting): bool => $setting->hasCompleteCredentials())
            ->values();
    }

    /**
     * The provider to preselect when only one is available.
     */
    public static function defaultProvider(): ?PaymentProvider
    {
        $available = self::get();

        if ($available->count() === 1) {
            return $available->first()->provider;
        }

        return null;
    }
}
