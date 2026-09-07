<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Models\Fee;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\PaymentSetting;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\StudentParent as ParentModel;
use Illuminate\Support\Facades\Config;

use function Pest\Laravel\get;

it('starts a hosted checkout when a parent pays an unpaid fee from the public ledger', function (): void {
    Config::set('services.easypaisa.store_id', 'STORE-1');
    Config::set('services.easypaisa.hash_key', 'HASH-KEY-1');

    PaymentSetting::factory()->easypaisa()->create();

    $class = StudentClass::factory()->create();
    $student = Student::factory()->create([
        'name' => 'Pay Kid',
        'gr_no' => 'GR-PAY-1',
        'student_class_id' => $class->getKey(),
    ]);
    $parent = ParentModel::factory()->create(['cnic' => '35202-4444444-4']);
    $parent->students()->attach($student->getKey());

    $fee = Fee::factory()->create([
        'student_id' => $student->getKey(),
        'year' => 2026,
        'amount' => 2500,
        'amount_paid' => 1000,
        'status' => 'partial',
    ]);

    get('/fees?identifier=GR-PAY-1&year=2026')->assertOk()->assertSee('Pay via EasyPaisa');

    $component = Livewire\Livewire::test('pages::fees', ['identifier' => 'GR-PAY-1', 'year' => '2026']);

    $component->call('pay', $fee->getKey(), 'easypaisa');

    $payment = Payment::query()->where('fee_id', $fee->getKey())->latest('id')->first();

    expect($payment)->not->toBeNull()
        ->and($payment->provider->value)->toBe('easypaisa')
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and((float) $payment->amount)->toBe(1500.0)
        ->and($component->get('checkout'))->not->toBeNull()
        ->and($component->get('checkout')['provider'])->toBe('easypaisa');
});

it('does nothing when paying an already settled fee', function (): void {
    PaymentSetting::factory()->easypaisa()->create();

    $class = StudentClass::factory()->create();
    $student = Student::factory()->create([
        'name' => 'Settled Kid',
        'gr_no' => 'GR-PAY-2',
        'student_class_id' => $class->getKey(),
    ]);
    $guardian = Guardian::factory()->create(['cnic' => '35202-5555555-5']);
    $guardian->students()->attach($student->getKey());

    $fee = Fee::factory()->create([
        'student_id' => $student->getKey(),
        'year' => 2026,
        'amount' => 2000,
        'amount_paid' => 2000,
        'status' => 'paid',
    ]);

    $component = Livewire\Livewire::test('pages::fees', ['identifier' => 'GR-PAY-2']);

    $component->call('pay', $fee->getKey(), 'easypaisa');

    expect(Payment::query()->where('fee_id', $fee->getKey())->count())->toBe(0)
        ->and($component->get('checkout'))->toBeNull();
});
