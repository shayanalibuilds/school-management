<?php

declare(strict_types=1);

use App\Enums\FeeStatus;
use App\Enums\FeeStructureType;
use App\Enums\PaymentStatus;
use App\Models\Admin;
use App\Models\Fee;
use App\Models\FeeStructure;
use App\Models\Payment;
use App\Support\Payments\AvailableProviders;
use App\Support\Payments\EasyPaisaService;
use App\Support\Payments\JazzCashService;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('manages fee structures with types', function (): void {
    $monthly = FeeStructure::factory()->create(['type' => FeeStructureType::Monthly, 'amount' => 2500]);
    $onetime = FeeStructure::factory()->create(['type' => FeeStructureType::OneTime, 'amount' => 5000]);

    expect($monthly->type)->toBe(FeeStructureType::Monthly)
        ->and((string) $monthly->amount)->toBe('2500.00')
        ->and($onetime->type)->toBe(FeeStructureType::OneTime);
});

it('tracks fee payment state as amounts are paid', function (): void {
    $fee = Fee::factory()->create(['amount' => 2500, 'amount_paid' => 0]);

    expect($fee->status)->toBe(FeeStatus::Unpaid);

    $fee->recordPaymentAmount(1000);
    expect($fee->refresh()->status)->toBe(FeeStatus::Partial);

    $fee->recordPaymentAmount(1500);
    expect($fee->refresh()->status)->toBe(FeeStatus::Paid);
});

it('stores payment provider credentials encrypted at rest', function (): void {
    App\Models\PaymentSetting::create([
        'provider' => App\Enums\PaymentProvider::JazzCash,
        'environment' => 'sandbox',
        'is_active' => true,
        'credentials' => ['merchant_id' => 'MC-12345', 'password' => 'secret123', 'integrity_salt' => 'saltabc'],
    ]);

    $row = App\Models\PaymentSetting::query()->where('provider', App\Enums\PaymentProvider::JazzCash->value)->first();

    expect($row->credentials['merchant_id'])->toBe('MC-12345');

    $raw = (string) $row->getRawOriginal('credentials');

    expect($raw)->not->toContain('MC-12345')
        ->not->toContain('secret123');
});

it('only offers providers that are active with complete credentials', function (): void {
    App\Models\PaymentSetting::factory()->jazzcash()->create();
    App\Models\PaymentSetting::factory()->easypaisa()->inactive()->create();

    $available = AvailableProviders::get();

    expect($available->pluck('provider')->all())->toBe([App\Enums\PaymentProvider::JazzCash])
        ->and(AvailableProviders::defaultProvider())->toBe(App\Enums\PaymentProvider::JazzCash);
});

it('builds signed jazzcash checkout fields', function (): void {
    App\Models\PaymentSetting::factory()->jazzcash()->create();
    $payment = Payment::factory()->create(['provider' => App\Enums\PaymentProvider::JazzCash, 'amount' => 2500, 'status' => PaymentStatus::Pending]);

    $fields = new JazzCashService()->buildCheckoutFields($payment);

    expect($fields['pp_Version'])->toBe('1.1')
        ->and($fields['pp_Amount'])->toBe('250000')
        ->and($fields['pp_TxnDateTime'])->not->toBeEmpty()
        ->and($fields)->toHaveKey('pp_SecureHash')
        ->and($fields['ppmpf_1'])->toBe($payment->getKey());

    $hash = new JazzCashService()->hashFields(collect($fields)->except('pp_SecureHash')->all());

    expect($hash)->toBe($fields['pp_SecureHash']);
});

it('completes a payment and fee through a verified jazzcash callback', function (): void {
    App\Models\PaymentSetting::factory()->jazzcash()->create();
    $fee = Fee::factory()->create(['amount' => 2500, 'amount_paid' => 0]);
    $payment = Payment::factory()->create([
        'fee_id' => $fee->getKey(),
        'provider' => App\Enums\PaymentProvider::JazzCash,
        'amount' => 2500,
        'status' => PaymentStatus::Pending,
    ]);

    $service = new JazzCashService();
    $fields = $service->buildCheckoutFields($payment);
    $fields['pp_TxnType'] = 'ONLINE';
    $fields['pp_ResponseCode'] = '000';
    $fields['pp_SecureHash'] = $service->hashFields(collect($fields)->except('pp_SecureHash')->all());

    post('/payments/callback/jazzcash', $fields);

    expect($payment->refresh()->status)->toBe(PaymentStatus::Completed)
        ->and($payment->reference)->not->toBeEmpty()
        ->and($fee->refresh()->amount_paid)->toBe('2500.00')
        ->and($fee->status)->toBe(FeeStatus::Paid);
});

it('rejects tampered jazzcash callbacks', function (): void {
    App\Models\PaymentSetting::factory()->jazzcash()->create();
    $fee = Fee::factory()->create(['amount' => 2500]);
    $payment = Payment::factory()->create([
        'fee_id' => $fee->getKey(),
        'provider' => App\Enums\PaymentProvider::JazzCash,
        'amount' => 2500,
        'status' => PaymentStatus::Pending,
    ]);

    $service = new JazzCashService();
    $fields = $service->buildCheckoutFields($payment);
    $fields['pp_ResponseCode'] = '000';
    $fields['pp_SecureHash'] = 'forged-hash-value';

    post('/payments/callback/jazzcash', $fields);

    expect($payment->refresh()->status)->toBe(PaymentStatus::Pending);
});

it('builds signed easypaisa checkout fields and completes via callback', function (): void {
    App\Models\PaymentSetting::factory()->easypaisa()->create();
    $fee = Fee::factory()->create(['amount' => 3000]);
    $payment = Payment::factory()->create([
        'fee_id' => $fee->getKey(),
        'provider' => App\Enums\PaymentProvider::EasyPaisa,
        'amount' => 3000,
        'status' => PaymentStatus::Pending,
    ]);

    $service = new EasyPaisaService();
    $fields = $service->buildCheckoutFields($payment);

    expect($fields['storeId'])->not->toBeEmpty()
        ->and($fields['amount'])->toBe('3000')
        ->and($fields['orderRefNum'])->toBe($payment->getKey())
        ->and($fields)->toHaveKey('hashedRequest')
        ->and($service->hashFields(collect($fields)->except('hashedRequest')->all()))->toBe($fields['hashedRequest']);

    $callback = [
        'orderRefNumber' => $payment->getKey(),
        'status' => '0000',
        'amount' => '3000',
    ];
    $callback['hashedRequest'] = $service->hashFields($callback);

    post('/payments/callback/easypaisa', $callback);

    expect($payment->refresh()->status)->toBe(PaymentStatus::Completed)
        ->and($fee->refresh()->status)->toBe(FeeStatus::Paid);
});

it('renders an official receipt for a completed payment', function (): void {
    $payment = Payment::factory()->completed()->create(['amount' => 2500, 'reference' => 'TXN-1001']);

    get("/receipts/{$payment->getKey()}")
        ->assertOk()
        ->assertSee(config('app.name'))
        ->assertSee('TXN-1001')
        ->assertSee('EasyPaisa');
});

it('shows the payment settings page to admins', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    get('/dashboard/payment-settings')->assertOk();
});

it('manages fees through the admin panel', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    get('/dashboard/fee-structures')->assertOk();
    get('/dashboard/fees')->assertOk();
    get('/dashboard/payments')->assertOk();
});
