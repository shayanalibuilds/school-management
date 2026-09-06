<?php

declare(strict_types=1);

namespace App\Support\Payments;

use App\Enums\PaymentProvider;
use App\Models\Payment;
use App\Models\PaymentSetting;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Str;
use RuntimeException;

final class JazzCashService
{
    public function setting(): ?PaymentSetting
    {
        return PaymentSetting::query()
            ->where('provider', PaymentProvider::JazzCash->value)
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
        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Karachi'));

        $fields = [
            'pp_Version' => '1.1',
            'pp_TxnType' => 'MWALLET',
            'pp_Language' => 'en',
            'pp_MerchantID' => (string) $credentials['merchant_id'],
            'pp_Password' => (string) $credentials['password'],
            'pp_TxnRefNo' => 'T'.$now->format('YmdHis').Str::upper(Str::random(4)),
            'pp_Amount' => (string) ((int) round(((float) $payment->amount) * 100)),
            'pp_TxnCurrency' => 'PKR',
            'pp_TxnDateTime' => $now->format('YmdHis'),
            'pp_BillReference' => (string) $payment->fee->student->gr_no,
            'pp_Description' => 'Fee payment: '.$payment->fee->feeStructure->name,
            'pp_TxnExpiryDateTime' => $now->modify('+1 day')->format('YmdHis'),
            'pp_ReturnURL' => route('payments.callback', ['provider' => 'jazzcash']),
            'ppmpf_1' => $payment->getKey(),
        ];

        $fields['pp_SecureHash'] = $this->hashFields($fields);

        return $fields;
    }

    /**
     * HMAC-SHA256 over the sorted field values with the integrity salt —
     * the verification algorithm published by JazzCash.
     *
     * @param  array<string, string>  $fields
     */
    public function hashFields(array $fields): string
    {
        $salt = (string) $this->requireSetting()->credentials['integrity_salt'];

        $message = $salt.'&'.implode('&', collect($fields)
            ->sortKeys()
            ->filter(fn ($value): bool => $value !== null && $value !== '')
            ->values()
            ->all());

        return hash_hmac('sha256', $message, $salt);
    }

    /**
     * Verify an incoming callback for the given payment.
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifyCallback(Payment $payment, array $payload): bool
    {
        if ((string) ($payload['ppmpf_1'] ?? '') !== $payment->getKey()) {
            return false;
        }

        $received = (string) ($payload['pp_SecureHash'] ?? '');

        if ($received === '') {
            return false;
        }

        $fields = collect($payload)
            ->filter(fn ($value, $key): bool => (str_starts_with((string) $key, 'pp_') || str_starts_with((string) $key, 'ppmpf_')) && $key !== 'pp_SecureHash')
            ->map(fn ($value): string => (string) $value)
            ->all();

        return hash_equals($this->hashFields($fields), $received);
    }

    private function requireSetting(): PaymentSetting
    {
        $setting = $this->setting();

        if (! $setting instanceof PaymentSetting) {
            throw new RuntimeException('JazzCash is not configured.');
        }

        return $setting;
    }
}
