<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Support\Payments\EasyPaisaService;
use App\Support\Payments\JazzCashService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class PaymentCallbackController extends Controller
{
    public function __invoke(Request $request, string $provider): RedirectResponse
    {
        $payload = $request->all();

        $paymentId = $provider === 'jazzcash'
            ? ($payload['ppmpf_1'] ?? null)
            : ($payload['orderRefNumber'] ?? null);

        $payment = $paymentId === null ? null : Payment::query()->find($paymentId);

        if ($payment === null || $payment->provider !== PaymentProvider::tryFrom($provider)) {
            return redirect('/')->with('error', 'Unknown payment reference.');
        }

        $service = match ($provider) {
            'jazzcash' => new JazzCashService(),
            'easypaisa' => new EasyPaisaService(),
            default => throw new InvalidArgumentException('Unknown provider.'),
        };

        if (! $service->verifyCallback($payment, $payload)) {
            // Tampered or malformed callback — leave the payment untouched.
            return redirect('/')->with('error', 'Payment verification failed.');
        }

        $succeeded = match ($provider) {
            'jazzcash' => ($payload['pp_ResponseCode'] ?? '') === '000',
            'easypaisa' => ($payload['status'] ?? '') === '0000',
        };

        $reference = (string) ($payload['pp_TxnRefNo'] ?? $payload['orderRefNumber'] ?? '');

        if ($succeeded && $payment->status === PaymentStatus::Pending) {
            $payment->complete($reference, $payload);
        } elseif ($payment->status === PaymentStatus::Pending) {
            $payment->fail($reference, $payload);
        }

        return redirect()->route('receipts.show', ['payment' => $payment->getKey()]);
    }
}
