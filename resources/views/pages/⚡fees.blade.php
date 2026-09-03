<?php

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Support\Payments\AvailableProviders;
use App\Support\Payments\EasyPaisaService;
use App\Support\Payments\JazzCashService;
use App\Support\StudentLookup;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component {
    #[Url]
    public ?string $cnic = null;

    #[Url]
    public ?string $phone = null;

    #[Url]
    public ?string $year = null;

    /**
     * Auto-submitting checkout form for the selected provider.
     *
     * @var array{provider: string, endpoint: string, fields: array<string, string>}|null
     */
    public ?array $checkout = null;

    /**
     * @return array<int, int>
     */
    public function getYearsProperty(): array
    {
        return range((int) today()->year - 9, (int) today()->year);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function getStudentsProperty(): \Illuminate\Support\Collection
    {
        if ($this->cnic === null || $this->phone === null) {
            return collect();
        }

        $students = StudentLookup::resolve($this->cnic, $this->phone);

        return $students->map(fn ($student): array => [
            'student' => $student,
            'fees' => $student->fees()
                ->where(function ($query): void {
                    $query->where('year', (int) ($this->year ?? today()->year))
                        ->orWhere('status', '!=', 'paid');
                })
                ->with('feeStructure')
                ->orderBy('year')
                ->get(),
        ]);
    }

    /**
     * Providers that can actually process payments right now.
     */
    public function getProvidersProperty(): \Illuminate\Support\Collection
    {
        return AvailableProviders::get();
    }

    /**
     * Begin the hosted checkout for an unpaid fee.
     */
    public function pay(string $feeId, string $providerValue): void
    {
        $provider = PaymentProvider::tryFrom($providerValue);

        if ($provider === null) {
            return;
        }

        $configured = $this->providers->firstWhere('provider', $provider);

        if ($configured === null) {
            $this->addError('checkout', 'This payment provider is not available.');

            return;
        }

        $fee = $this->students
            ->flatMap(fn (array $entry) => $entry['fees'])
            ->firstWhere('id', $feeId);

        if ($fee === null || $fee->status === PaymentStatus::Paid->value || (float) $fee->amount_paid >= (float) $fee->amount) {
            return;
        }

        $due = (float) $fee->amount - (float) $fee->amount_paid;

        $payment = $fee->payments()->create([
            'provider' => $provider->value,
            'payer_cnic' => $this->cnic,
            'payer_phone' => $this->phone,
            'amount' => $due,
            'status' => PaymentStatus::Pending->value,
        ]);

        $fields = match ($provider) {
            PaymentProvider::JazzCash => (new JazzCashService())->buildCheckoutFields($payment),
            PaymentProvider::EasyPaisa => (new EasyPaisaService())->buildCheckoutFields($payment),
        };

        $this->checkout = [
            'provider' => $provider->value,
            'endpoint' => $provider === PaymentProvider::JazzCash
                ? 'https://'.($configured->environment === 'live' ? 'payments' : 'sandbox').'.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform'
                : 'https://'.($configured->environment === 'live' ? 'easypay' : 'easypaystg').'.easypaisa.com.pk/easypay/Index.jsf',
            'fields' => $fields,
        ];
    }

    public function search(): void
    {
        // State is bound to the URL; re-rendering performs the lookup.
    }
};
?>

<div class="py-8 space-y-6">
    <div>
        <flux:heading size="lg">Fees & Payments</flux:heading>
        <flux:subheading>Check dues for any year and pay online through your preferred provider.</flux:subheading>
    </div>

    <flux:card>
        <form wire:submit="search" class="grid gap-4 sm:grid-cols-3">
            <flux:input wire:model="cnic" label="CNIC" placeholder="35202-1234567-1" />
            <flux:input wire:model="phone" label="Phone" placeholder="03001234567" />
            <div>
                <flux:label>Year</flux:label>
                <flux:select wire:model="year">
                    <option value="">All years</option>
                    @foreach ($this->years as $availableYear)
                        <option value="{{ $availableYear }}">{{ $availableYear }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div class="sm:col-span-3">
                <flux:button variant="primary" type="submit" icon="magnifying-glass">Search</flux:button>
            </div>
        </form>
    </flux:card>

    @foreach ($this->students as $entry)
        <flux:card class="space-y-3">
            <flux:heading size="md">{{ $entry['student']->name }} — {{ $entry['student']->studentClass?->name }}</flux:heading>

            @if ($entry['fees']->isEmpty())
                <flux:text>No fee records found.</flux:text>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column heading="Fee" />
                        <flux:table.column heading="Year" />
                        <flux:table.column heading="Amount" />
                        <flux:table.column heading="Paid" />
                        <flux:table.column heading="Status" />
                        <flux:table.column heading="" />
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($entry['fees'] as $fee)
                            <flux:table.row>
                                <flux:table.cell>{{ $fee->feeStructure->name }}</flux:table.cell>
                                <flux:table.cell>{{ $fee->year }}</flux:table.cell>
                                <flux:table.cell>PKR {{ number_format((float) $fee->amount, 2) }}</flux:table.cell>
                                <flux:table.cell>PKR {{ number_format((float) $fee->amount_paid, 2) }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge :variant="$fee->status->value === 'paid' ? 'outline' : ($fee->status->value === 'partial' ? 'soft' : 'filled')">
                                        {{ $fee->status->label() }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($fee->status->value !== 'paid')
                                        <div class="flex gap-2">
                                            @foreach ($this->providers as $providerSetting)
                                                <flux:button size="xs" wire:click="pay('{{ $fee->getKey() }}', '{{ $providerSetting->provider->value }}')" wire:loading.attr="disabled">
                                                    Pay via {{ $providerSetting->provider->label() }}
                                                </flux:button>
                                            @endforeach
                                        </div>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </flux:card>
    @endforeach

    @if ($cnic !== null && $phone !== null && $this->students->isEmpty())
        <flux:callout variant="warning">
            <flux:callout.heading>No records found</flux:callout.heading>
            <flux:callout.text>Check the CNIC and phone number and try again.</flux:callout.text>
        </flux:callout>
    @endif

    @if ($checkout !== null)
        <flux:callout variant="info">
            <flux:callout.heading>Redirecting to {{ $checkout['provider'] }}…</flux:callout.heading>
            <flux:callout.text>Do not close this page while the payment is being processed.</flux:callout.text>
        </flux:callout>

        <form id="gateway-checkout" method="POST" action="{{ $checkout['endpoint'] }}">
            @foreach ($checkout['fields'] as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
        </form>
        <script>document.getElementById('gateway-checkout').submit();</script>
    @endif
</div>
