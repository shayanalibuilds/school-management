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
    public ?string $identifier = null;

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
        if ($this->identifier === null) {
            return collect();
        }

        $students = StudentLookup::resolve($this->identifier);

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

        $digits = preg_replace('/[^0-9]/', '', (string) $this->identifier);

        $payment = $fee->payments()->create([
            'provider' => $provider->value,
            'payer_cnic' => $digits !== null && mb_strlen($digits) === 13 ? $digits : null,
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

<div class="space-y-6 py-4">
    <div class="space-y-1">
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-950">Fees &amp; Payments</h1>
        <p class="text-sm text-zinc-600">Enter the parent or guardian CNIC, or the student GR number (GR #), to check dues and pay online.</p>
    </div>

    <x-filament::section>
        <form wire:submit="search" class="grid gap-4 sm:grid-cols-3">
            <div>
                <label for="identifier" class="mb-1 block text-sm font-medium text-zinc-700">CNIC or roll number</label>
                <x-filament::input.wrapper>
                    <x-filament::input
                        id="identifier"
                        type="text"
                        wire:model="identifier"
                        placeholder="35202-1234567-1 or 42"
                    />
                </x-filament::input.wrapper>
            </div>
            <div>
                <label for="year" class="mb-1 block text-sm font-medium text-zinc-700">Year</label>
                <x-filament::input.select id="year" wire:model="year">
                    <option value="">All years</option>
                    @foreach ($this->years as $availableYear)
                        <option value="{{ $availableYear }}">{{ $availableYear }}</option>
                    @endforeach
                </x-filament::input.select>
            </div>
            <div class="flex items-end">
                <x-filament::button type="submit" icon="heroicon-m-magnifying-glass">Search</x-filament::button>
            </div>
        </form>
    </x-filament::section>

    @foreach ($this->students as $entry)
        <x-filament::section
            :heading="$entry['student']->name.' — '.$entry['student']->studentClass?->name"
        >
            @if ($entry['fees']->isEmpty())
                <p class="text-sm text-zinc-600">No fee records found.</p>
            @else
                <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-50">
                            <tr class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                <th class="px-4 py-2 text-start">Fee</th>
                                <th class="px-4 py-2 text-start">Year</th>
                                <th class="px-4 py-2 text-start">Amount</th>
                                <th class="px-4 py-2 text-start">Paid</th>
                                <th class="px-4 py-2 text-start">Status</th>
                                <th class="px-4 py-2 text-start"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200">
                            @foreach ($entry['fees'] as $fee)
                                <tr>
                                    <td class="px-4 py-2 text-zinc-950">{{ $fee->feeStructure->name }}</td>
                                    <td class="px-4 py-2 text-zinc-950">{{ $fee->year }}</td>
                                    <td class="px-4 py-2 text-zinc-950">PKR {{ number_format((float) $fee->amount, 2) }}</td>
                                    <td class="px-4 py-2 text-zinc-950">PKR {{ number_format((float) $fee->amount_paid, 2) }}</td>
                                    <td class="px-4 py-2">
                                        <x-filament::badge
                                            :color="$fee->status->value === 'paid' ? 'success' : ($fee->status->value === 'partial' ? 'warning' : 'danger')"
                                        >
                                            {{ $fee->status->label() }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="px-4 py-2">
                                        @if ($fee->status->value !== 'paid')
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($this->providers as $providerSetting)
                                                    <x-filament::button
                                                        size="xs"
                                                        color="gray"
                                                        wire:click="pay('{{ $fee->getKey() }}', '{{ $providerSetting->provider->value }}')"
                                                        wire:loading.attr="disabled"
                                                    >
                                                        Pay via {{ $providerSetting->provider->label() }}
                                                    </x-filament::button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    @endforeach

    @if ($identifier !== null && $this->students->isEmpty())
        <x-filament::callout
            color="warning"
            icon="heroicon-m-exclamation-triangle"
            heading="No records found"
        >
            Check the CNIC or roll number and try again.
        </x-filament::callout>
    @endif

    @if ($checkout !== null)
        <x-filament::callout
            color="info"
            icon="heroicon-m-arrow-right-circle"
            heading="Redirecting to {{ $checkout['provider'] }}…"
        >
            Do not close this page while the payment is being processed.
        </x-filament::callout>

        <form id="gateway-checkout" method="POST" action="{{ $checkout['endpoint'] }}">
            @foreach ($checkout['fields'] as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
        </form>
        <script>document.getElementById('gateway-checkout').submit();</script>
    @endif
</div>
