<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\PaymentProvider;
use App\Models\PaymentSetting;
use BackedEnum;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use UnitEnum;

final class PaymentSettings extends Page
{
    /**
     * @var array<string, array<string, mixed>>
     */
    public array $settings = [];

    protected string $view = 'filament.admin.pages.payment-settings';

    protected static ?string $navigationLabel = 'Payment Settings';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static string|BackedEnum|null $navigationIcon = \Filament\Support\Icons\Heroicon::OutlinedCreditCard;

    public function mount(): void
    {
        foreach (PaymentProvider::cases() as $provider) {
            $setting = PaymentSetting::query()->where('provider', $provider->value)->first();

            $credentials = $setting?->credentials ?? [];

            $this->settings[$provider->value] = [
                'is_active' => $setting?->is_active ?? false,
                'environment' => $setting?->environment ?? 'sandbox',
                ...collect($provider->requiredCredentials())
                    ->mapWithKeys(fn (string $key): array => [$key => (string) ($credentials[$key] ?? '')])
                    ->all(),
            ];
        }
    }

    public function save(): void
    {
        foreach (PaymentProvider::cases() as $provider) {
            $state = $this->settings[$provider->value];

            $credentials = collect($provider->requiredCredentials())
                ->mapWithKeys(fn (string $key): array => [$key => mb_trim((string) ($state[$key] ?? ''))])
                ->all();

            PaymentSetting::updateOrCreate(
                ['provider' => $provider->value],
                [
                    'environment' => ($state['environment'] ?? 'sandbox') === 'live' ? 'live' : 'sandbox',
                    'is_active' => (bool) ($state['is_active'] ?? false),
                    'credentials' => $credentials,
                ],
            );
        }

        FilamentNotification::make()
            ->title('Payment settings saved')
            ->success()
            ->send();
    }
}
