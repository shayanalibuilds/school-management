<?php

declare(strict_types=1);

namespace App\Support\Payments;

use App\Enums\PaymentProvider;
use App\Models\Payment;
use App\Models\PaymentSetting;
use RuntimeException;

final class EasyPaisaService
{
    public function setting(): ?PaymentSetting
    {
        return PaymentSetting::query()
            ->where('provider', PaymentProvider::EasyPaisa->value)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Hosted-checkout fields for a pending payment.
     *
     * @return array<string, string>
     */
    public function buildCheckoutFields(Payment $payment): array
    {
        $setting = $this->requireSetting();
        $credentials = $setting->credentials;

        $fields = [
            'storeId' => (string) $credentials['store_id'],
            'amount' => (string) ((int) $payment->amount),
            'postBackURL' => route('payments.callback', ['provider' => 'easypaisa']),
            'orderRefNum' => $payment->getKey(),
            'expiryDate' => now()->addDay()->format('YmdHis'),
            'autoRedirect' => '1',
            'paymentMethod' => 'MA_PAYMENT',
            'emailAddr' => '',
            'mobileNum' => (string) ($payment->payer_phone ?? ''),
        ];

        $fields['hashedRequest'] = $this->hashFields($fields);

        return $fields;
    }

    /**
     * HMAC-SHA256 over sorted key=value pairs joined with ampersands, keyed
     * by the store hash key — the same signing EasyPaisa applies to its
     * hosted checkout requests.
     *
     * @param  array<string, string>  $fields
     */
    public function hashFields(array $fields): string
    {
        $hashKey = (string) $this->requireSetting()->credentials['hash_key'];

        $message = collect($fields)
            ->sortKeys()
            ->filter(fn ($value): bool => $value !== null && $value !== '')
            ->map(fn ($value, $key): string => $key.'='.$value)
            ->implode('&');

        return hash_hmac('sha256', $message, $hashKey);
    }

    /**
     * Verify an incoming callback for the given payment.
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifyCallback(Payment $payment, array $payload): bool
    {
        if ((string) ($payload['orderRefNumber'] ?? '') !== $payment->getKey()) {
            return false;
        }

        $received = (string) ($payload['hashedRequest'] ?? '');

        if ($received === '') {
            return false;
        }

        $fields = collect($payload)
            ->except('hashedRequest')
            ->map(fn ($value): string => (string) $value)
            ->all();

        return hash_equals($this->hashFields($fields), $received);
    }

    private function requireSetting(): PaymentSetting
    {
        $setting = $this->setting();

        if (! $setting instanceof PaymentSetting) {
            throw new RuntimeException('EasyPaisa is not configured.');
        }

        return $setting;
    }
}
