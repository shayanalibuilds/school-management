<?php

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Enums\FeeStatus;
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
                ->with(['feeStructure', 'payments'])
                ->orderBy('year')
                ->get()
                ->map(fn ($fee): array => [
                    'fee' => $fee,
                    'receipt_id' => $fee->payments
                        ->first(fn ($payment): bool => $payment->status === PaymentStatus::Completed)
                        ?->getKey(),
                ]),
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
            ->pluck('fee')
            ->firstWhere('id', $feeId);

        if ($fee === null || $fee->status === FeeStatus::Paid || (float) $fee->amount_paid >= (float) $fee->amount) {
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
}
?>

<div class="space-y-8 py-2">
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div class="max-w-2xl">
            <div class="mb-2 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-tint px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wider text-green">
                    <x-portal-icon name="shield-check" class="h-3.5 w-3.5" />
                    Institutional Treasury
                </span>
            </div>
            <h1 class="font-display text-4xl font-medium tracking-tight text-ink">Fee status &amp; ledger</h1>
            <p class="mt-1.5 text-base text-ink-soft">
                Public verified ledger for tuition and fees. Enter a CNIC or GR number to check dues and pay online.
            </p>
        </div>
        <div class="flex items-center gap-2.5 rounded-xl border border-haze bg-card px-4 py-2.5 shadow-sm">
            <x-portal-icon name="banknotes" class="h-5 w-5 text-green" />
            <div class="leading-tight">
                <span class="block text-[11px] font-semibold uppercase tracking-wider text-ink-soft">Currency</span>
                <span class="text-sm font-semibold text-ink">PKR (₨)</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12">
        {{-- Lookup column --}}
        <div class="flex flex-col gap-6 lg:col-span-4">
            <div class="rounded-xl border border-haze bg-card p-6 shadow-sm">
                <div class="mb-5 flex items-center justify-between border-b border-haze pb-4">
                    <div>
                        <span class="block text-xs font-bold uppercase tracking-wider text-ink-soft">Bursar Desk</span>
                        <span class="text-lg font-bold text-ink">Fee Ledger Query</span>
                    </div>
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-mist text-navy">
                        <x-portal-icon name="document-text" class="h-5 w-5" />
                    </span>
                </div>

                <form wire:submit="search" class="flex flex-col gap-4">
                    <div>
                        <label for="identifier" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-ink-soft">
                            CNIC or GR number <span class="text-alert" aria-hidden="true">*</span>
                        </label>
                        <input
                            id="identifier"
                            type="text"
                            wire:model="identifier"
                            required
                            autocomplete="off"
                            placeholder="35202-1234567-1 or GR 42"
                            class="w-full rounded-lg border border-line bg-card px-3.5 py-2.5 text-sm font-semibold uppercase tracking-wide tabular text-ink shadow-sm transition-[color,background-color,border-color,box-shadow] placeholder:font-normal placeholder:normal-case placeholder:tracking-normal placeholder:text-line focus:outline-none focus:ring-2 focus:ring-navy"
                        />
                    </div>
                    <div>
                        <label for="year" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-ink-soft">Fee year</label>
                        <div class="relative">
                            <select
                                id="year"
                                wire:model="year"
                                class="w-full cursor-pointer appearance-none rounded-lg border border-line bg-card px-3.5 py-2.5 pr-10 text-sm text-ink shadow-sm transition-[color,background-color,border-color,box-shadow] focus:outline-none focus:ring-2 focus:ring-navy"
                            >
                                <option value="">All years</option>
                                @foreach ($this->years as $availableYear)
                                    <option value="{{ $availableYear }}">{{ $availableYear }}</option>
                                @endforeach
                            </select>
                            <x-portal-icon name="chevron-down" class="pointer-events-none absolute right-3 top-3.5 h-4 w-4 text-ink-soft" />
                        </div>
                    </div>
                    <div class="pt-1">
                        <button
                            type="submit"
                            class="flex min-h-[44px] w-full items-center justify-center gap-2 rounded-lg bg-navy px-4 py-3 text-sm font-semibold text-white shadow-sm transition-[color,background-color,border-color,box-shadow] hover:bg-navy-hover"
                        >
                            <x-portal-icon name="search" class="h-4 w-4 text-glow" />
                            Look up fees
                        </button>
                    </div>
                </form>

                <div class="mt-4 flex items-start gap-2 text-ink-soft">
                    <x-portal-icon name="shield-check" class="mt-0.5 h-4 w-4 shrink-0 text-green" />
                    <p class="text-[13px] leading-snug">
                        Unpaid fees can be settled online through the provider's hosted checkout —
                        the school never sees your card or wallet details.
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3 rounded-xl border border-haze bg-card p-5 shadow-sm">
                <x-portal-icon name="shield-check" class="mt-0.5 h-5 w-5 shrink-0 text-green" />
                <div class="text-[13px] leading-relaxed text-ink-soft">
                    <span class="mb-0.5 block text-sm font-semibold text-ink">Verified Bursar Record</span>
                    Amounts, settlements, and receipts on this ledger are generated directly from
                    the school's accounts office. Keep every receipt for your records.
                </div>
            </div>
        </div>

        {{-- Ledger column --}}
        <div class="flex flex-col gap-8 lg:col-span-8">
            @foreach ($this->students as $entry)
                @php
                    $student = $entry['student'];
                    $fees = collect($entry['fees']);
                    $assessed = (float) $fees->sum(fn (array $row): float => (float) $row['fee']->amount);
                    $paid = (float) $fees->sum(fn (array $row): float => (float) $row['fee']->amount_paid);
                    $balance = max(0, $assessed - $paid);
                    $initials = collect(explode(' ', trim($student->name)))
                        ->filter()
                        ->take(2)
                        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
                        ->implode('');
                @endphp

                <div class="flex flex-col gap-6">
                    {{-- Student banner --}}
                    <div class="flex flex-col justify-between gap-6 rounded-xl bg-navy p-6 shadow-sm md:flex-row md:items-center md:p-8">
                        <div class="flex items-center gap-5">
                            <span
                                aria-hidden="true"
                                class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-navy-hover font-display text-2xl font-semibold text-glow shadow-sm"
                            >{{ $initials }}</span>
                            <div>
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <span class="text-xs font-bold uppercase tracking-widest text-glow">Fee Ledger</span>
                                    <span class="text-steel-bright">•</span>
                                    <span class="font-mono text-xs font-semibold tabular text-steel-bright">GR {{ \App\Support\GrNumber::bare($student->gr_no) }}</span>
                                </div>
                                <h2 class="mt-1 text-xl font-bold text-white">{{ $student->name }}</h2>
                                <span class="mt-0.5 block text-sm text-steel-bright">
                                    {{ $student->studentClass?->name ?? '—' }} · Ledger year {{ $year ?? today()->year }}
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            @if ($balance <= 0 && $assessed > 0)
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-mint px-3 py-1.5 text-xs font-bold text-mint-ink">
                                    <x-portal-icon name="check-circle" class="h-4 w-4" />
                                    All dues settled
                                </span>
                            @elseif ($balance > 0)
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-alert-soft px-3 py-1.5 text-xs font-bold text-alert-ink">
                                    <x-portal-icon name="clock" class="h-4 w-4" />
                                    Balance outstanding
                                </span>
                            @endif
                        </div>
                    </div>

                    @if ($fees->isEmpty())
                        <div class="rounded-xl border border-haze bg-card p-10 text-center shadow-sm">
                            <span class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-tint text-navy">
                                <x-portal-icon name="document-text" class="h-8 w-8" />
                            </span>
                            <h3 class="text-lg font-bold text-ink">No fee records for this year</h3>
                            <p class="mx-auto mt-1 max-w-sm text-sm text-ink-soft">
                                Pick a different year above, or clear it to see every fee on this student's ledger.
                            </p>
                        </div>
                    @else
                        {{-- Totals --}}
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div class="flex flex-col justify-between rounded-xl border border-haze bg-card p-6 shadow-sm">
                                <div class="mb-3 flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase tracking-wider text-ink-soft">Total assessed</span>
                                    <x-portal-icon name="document-text" class="h-5 w-5 text-ink-soft" />
                                </div>
                                <div class="text-2xl font-bold tabular text-ink">PKR {{ number_format($assessed, 0) }}</div>
                                <div class="mt-6 flex items-center justify-between rounded-lg bg-mist p-3">
                                    <span class="text-xs text-ink-soft">Fee records</span>
                                    <span class="text-xs font-bold tabular text-ink">{{ $fees->count() }}</span>
                                </div>
                            </div>
                            <div class="flex flex-col justify-between rounded-xl border border-haze bg-card p-6 shadow-sm">
                                <div class="mb-3 flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase tracking-wider text-green">Total paid</span>
                                    <x-portal-icon name="check-circle" class="h-5 w-5 text-green" />
                                </div>
                                <div class="text-2xl font-bold tabular text-green">PKR {{ number_format($paid, 0) }}</div>
                                <div class="mt-6 flex items-center justify-between rounded-lg bg-mist p-3">
                                    <span class="text-xs text-ink-soft">Settled ratio</span>
                                    <span class="text-xs font-bold tabular text-green">
                                        {{ $assessed > 0 ? round(($paid / $assessed) * 100, 1) : 100 }}%
                                    </span>
                                </div>
                            </div>
                            <div class="flex flex-col justify-between rounded-xl border border-haze bg-card p-6 shadow-sm">
                                <div class="mb-3 flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase tracking-wider text-clay-ink">Balance due</span>
                                    <x-portal-icon name="clock" class="h-5 w-5 text-alert" />
                                </div>
                                <div class="flex flex-wrap items-baseline gap-2">
                                    <span class="text-2xl font-bold tabular {{ $balance > 0 ? 'text-alert' : 'text-green' }}">
                                        PKR {{ number_format($balance, 0) }}
                                    </span>
                                </div>
                                <div class="mt-6 flex items-center justify-between rounded-lg bg-mist p-3">
                                    <span class="text-xs text-ink-soft">Settle online below</span>
                                    <span class="text-xs font-bold text-ink">PKR (₨)</span>
                                </div>
                            </div>
                        </div>

                        {{-- Ledger table --}}
                        <div class="overflow-hidden rounded-xl border border-haze bg-card shadow-sm">
                            <div class="flex flex-col items-start justify-between gap-4 border-b border-haze bg-mist/60 px-6 py-4 sm:flex-row sm:items-center">
                                <div>
                                    <h3 class="text-base font-bold text-ink">Institutional ledger breakdown</h3>
                                    <p class="mt-0.5 text-xs text-ink-soft">Official bursar record, newest fee year first</p>
                                </div>
                                <span class="rounded bg-haze px-2.5 py-1 text-xs font-bold text-ink">PKR (₨)</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse text-left">
                                    <thead class="bg-navy text-xs font-semibold uppercase tracking-wider text-white">
                                        <tr>
                                            <th scope="col" class="px-6 py-4">Fee head</th>
                                            <th scope="col" class="px-4 py-4">Year</th>
                                            <th scope="col" class="px-4 py-4 text-right">Amount</th>
                                            <th scope="col" class="px-4 py-4 text-center">Status</th>
                                            <th scope="col" class="px-6 py-4 text-right">Settlement</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-haze text-sm">
                                        @foreach ($entry['fees'] as $row)
                                            @php
                                                $fee = $row['fee'];
                                            @endphp
                                            <tr class="transition-colors hover:bg-mist/50">
                                                <td class="px-6 py-5">
                                                    <div class="font-semibold text-ink">{{ $fee->feeStructure->name }}</div>
                                                    <div class="mt-0.5 text-xs text-ink-soft">
                                                        {{ $fee->feeStructure->type->label() }} · assessed PKR {{ number_format((float) $fee->amount, 0) }}
                                                    </div>
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-5 tabular text-ink-soft">{{ $fee->year }}</td>
                                                <td class="whitespace-nowrap px-4 py-5 text-right font-bold tabular text-ink">
                                                    PKR {{ number_format((float) $fee->amount, 2) }}
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-5 text-center">
                                                    @php
                                                        $feeTone = match ($fee->status->value) {
                                                            'paid' => 'bg-mint text-mint-ink',
                                                            'partial' => 'bg-clay text-clay-ink',
                                                            default => 'bg-alert-soft text-alert-ink',
                                                        };
                                                    @endphp
                                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold {{ $feeTone }}">
                                                        @if ($fee->status->value === 'paid')
                                                            <x-portal-icon name="check-circle" class="h-3.5 w-3.5" />
                                                        @elseif ($fee->status->value === 'partial')
                                                            <x-portal-icon name="clock" class="h-3.5 w-3.5" />
                                                        @else
                                                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                                        @endif
                                                        {{ $fee->status->label() }}
                                                    </span>
                                                    <div class="mt-1 text-[11px] tabular text-ink-soft">
                                                        Paid PKR {{ number_format((float) $fee->amount_paid, 0) }}
                                                    </div>
                                                </td>
                                                <td class="whitespace-nowrap px-6 py-5 text-right">
                                                    @if ($fee->status->value !== 'paid' && (float) $fee->amount_paid < (float) $fee->amount)
                                                        <div class="flex flex-wrap justify-end gap-2">
                                                            @foreach ($this->providers as $providerSetting)
                                                                <button
                                                                    type="button"
                                                                    wire:click="pay('{{ $fee->getKey() }}', '{{ $providerSetting->provider->value }}')"
                                                                    wire:loading.attr="disabled"
                                                                    class="inline-flex min-h-[40px] items-center gap-1.5 rounded-lg bg-navy px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-navy-hover disabled:opacity-60"
                                                                >
                                                                    Pay via {{ $providerSetting->provider->label() }}
                                                                    <x-portal-icon name="arrow-right" class="h-3.5 w-3.5 text-glow" />
                                                                </button>
                                                            @endforeach
                                                        </div>
                                                    @elseif ($row['receipt_id'] !== null)
                                                        <a
                                                            href="/receipts/{{ $row['receipt_id'] }}"
                                                            class="inline-flex items-center gap-1 rounded font-semibold text-green hover:underline"
                                                        >
                                                            View receipt
                                                            <x-portal-icon name="arrow-right" class="h-3.5 w-3.5" />
                                                        </a>
                                                    @else
                                                        <span class="text-xs text-ink-soft">Awaiting confirmation</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach

            @if ($identifier !== null && $this->students->isEmpty())
                <div class="flex flex-col items-center gap-3 rounded-xl border border-haze bg-alert-soft/40 p-10 text-center">
                    <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-alert-soft text-alert-ink">
                        <x-portal-icon name="exclamation-triangle" class="h-8 w-8" />
                    </span>
                    <h3 class="text-xl font-bold text-ink">No records found</h3>
                    <p class="max-w-md text-sm text-ink-soft">
                        Check the CNIC or GR number and try again — the ledger only shows
                        currently enrolled students.
                    </p>
                </div>
            @elseif ($identifier === null)
                <div class="flex flex-col items-center justify-center rounded-xl bg-card p-12 text-center shadow-md">
                    <span class="mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-tint text-navy shadow-inner">
                        <x-portal-icon name="banknotes" class="h-10 w-10" />
                    </span>
                    <h3 class="mb-2 font-display text-2xl font-semibold text-ink">Your fee ledger appears here</h3>
                    <p class="mx-auto max-w-md text-sm leading-relaxed text-ink-soft">
                        Enter a parent or guardian CNIC, or the student GR number, to review
                        assessed fees, settlements, and outstanding dues for every year.
                    </p>
                </div>
            @endif
        </div>
    </div>

    @if ($checkout !== null)
        <div class="flex items-start gap-3 rounded-xl border border-green/20 bg-mint/30 p-5">
            <x-portal-icon name="paper-airplane" class="mt-0.5 h-5 w-5 shrink-0 text-green" />
            <div class="text-sm">
                <span class="block font-bold text-ink">Redirecting to {{ $checkout['provider'] }}…</span>
                <span class="text-ink-soft">Do not close this page while the payment is being processed.</span>
            </div>
        </div>

        <form id="gateway-checkout" method="POST" action="{{ $checkout['endpoint'] }}">
            @foreach ($checkout['fields'] as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
        </form>
        <script>document.getElementById('gateway-checkout').submit();</script>
    @endif
</div>
