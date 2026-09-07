<?php

use App\Support\StudentLookup;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component {
    #[Url]
    public ?string $identifier = null;

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
            'records' => $student->attendances()
                ->orderByDesc('date')
                ->limit(30)
                ->get()
                ->map(fn ($attendance): array => [
                    'date' => $attendance->date->format('d M Y'),
                    'iso' => $attendance->date->toDateString(),
                    'day' => $attendance->date->format('l'),
                    'status' => $attendance->status,
                ]),
        ]);
    }

    public function search(): void
    {
        // State is bound to the URL; re-rendering performs the lookup.
    }
}
?>

<div class="space-y-8 py-2">
    <div class="flex flex-col justify-between gap-4 border-b border-haze pb-6 md:flex-row md:items-end">
        <div class="max-w-2xl">
            <div class="mb-2 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-full bg-tint px-3 py-1 text-xs font-semibold uppercase tracking-wider text-ink-soft">
                    <span class="h-2 w-2 rounded-full bg-green"></span>
                    Official Record Portal
                </span>
            </div>
            <h1 class="font-display text-4xl font-medium tracking-tight text-ink">Daily attendance ledger</h1>
            <p class="mt-1.5 text-base text-ink-soft">
                Official roll call archives. Enter a CNIC or GR number to view the last 30 recorded school days.
            </p>
        </div>
        <div class="flex items-center gap-2.5 rounded-lg border border-haze bg-mist px-3.5 py-2 text-xs font-medium text-ink-soft">
            <x-portal-icon name="shield-check" class="h-4.5 w-4.5 text-green" />
            <span>Synchronised with the daily register · Academic year {{ today()->year }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12">
        {{-- Lookup column --}}
        <div class="flex flex-col gap-6 lg:col-span-4">
            <div class="rounded-xl border border-haze bg-card p-6 shadow-sm">
                <div class="mb-5 flex items-center justify-between border-b border-haze pb-4">
                    <div>
                        <span class="block text-xs font-bold uppercase tracking-wider text-ink-soft">Public Search</span>
                        <span class="text-lg font-bold text-ink">Attendance Query</span>
                    </div>
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-mist text-navy">
                        <x-portal-icon name="identification" class="h-5 w-5" />
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
                        <p class="mt-1.5 text-xs text-ink-soft">
                            The GR number is printed on the student ID card and every fee receipt.
                        </p>
                    </div>
                    <div class="pt-1">
                        <button
                            type="submit"
                            class="flex min-h-[44px] w-full items-center justify-center gap-2 rounded-lg bg-navy px-4 py-3 text-sm font-semibold text-white shadow-sm transition-[color,background-color,border-color,box-shadow] hover:bg-navy-hover"
                        >
                            <x-portal-icon name="search" class="h-4 w-4 text-glow" />
                            Look up attendance
                        </button>
                    </div>
                </form>
            </div>

            <div class="flex items-start gap-3 rounded-xl border border-haze bg-card p-5 shadow-sm">
                <x-portal-icon name="shield-check" class="mt-0.5 h-5 w-5 shrink-0 text-green" />
                <div class="text-[13px] leading-relaxed text-ink-soft">
                    <span class="mb-0.5 block text-sm font-semibold text-ink">Official Registry Verification</span>
                    All entries represent the institutional daily roll call log exactly as recorded
                    by the class teacher. No record is added or amended from this portal.
                </div>
            </div>
        </div>

        {{-- Ledger column --}}
        <div class="flex flex-col gap-6 lg:col-span-8">
            @if ($this->students->isNotEmpty())
                @foreach ($this->students as $entry)
                    @php
                        $student = $entry['student'];
                        $records = $entry['records'];
                        $present = $records->filter(fn (array $record): bool => $record['status']->value === 'present')->count();
                        $leave = $records->filter(fn (array $record): bool => $record['status']->value === 'leave')->count();
                        $absent = $records->filter(fn (array $record): bool => $record['status']->value === 'absent')->count();
                        $rate = $records->isNotEmpty() ? round(($present / $records->count()) * 100, 1) : 0;
                        $initials = collect(explode(' ', trim($student->name)))
                            ->filter()
                            ->take(2)
                            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
                            ->implode('');
                    @endphp

                    <div class="flex flex-col gap-6">
                        {{-- Profile card --}}
                        <div class="rounded-xl border border-haze bg-card p-6 shadow-sm">
                            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-haze pb-5">
                                <div class="flex items-center gap-4">
                                    <span
                                        aria-hidden="true"
                                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-navy font-display text-xl font-semibold text-white shadow-sm"
                                    >{{ $initials }}</span>
                                    <div>
                                        <div class="mb-1 flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-mint px-2.5 py-0.5 text-xs font-bold text-mint-ink">
                                                <span class="h-1.5 w-1.5 rounded-full bg-green"></span>
                                                Enrolled
                                            </span>
                                        </div>
                                        <h2 class="text-xl font-bold text-ink">{{ $student->name }}</h2>
                                        <p class="mt-0.5 text-sm text-ink-soft">
                                            {{ $student->studentClass?->name ?? '—' }} · GR {{ \App\Support\GrNumber::bare($student->gr_no) }}
                                        </p>
                                    </div>
                                </div>
                                <span class="rounded-lg border border-haze bg-mist px-3 py-1.5 font-mono text-sm font-bold tabular text-ink">
                                    GR{{ \App\Support\GrNumber::bare($student->gr_no) }}
                                </span>
                            </div>

                            {{-- 30-day summary --}}
                            <div class="grid grid-cols-2 gap-4 pt-5 md:grid-cols-4">
                                <div class="rounded-xl border border-haze bg-card p-4 shadow-sm">
                                    <span class="mb-1 block text-xs font-medium uppercase tracking-wider text-ink-soft">Days present</span>
                                    <div class="text-2xl font-bold tabular text-ink">{{ $present }}</div>
                                    <span class="mt-2 inline-flex items-center gap-1 rounded-full border border-green/20 bg-mint px-2.5 py-1 text-[11px] font-bold text-mint-ink">
                                        <span class="h-1.5 w-1.5 rounded-full bg-green"></span>
                                        In class
                                    </span>
                                </div>
                                <div class="rounded-xl border border-haze bg-card p-4 shadow-sm">
                                    <span class="mb-1 block text-xs font-medium uppercase tracking-wider text-ink-soft">Days on leave</span>
                                    <div class="text-2xl font-bold tabular text-ink">{{ $leave }}</div>
                                    <span class="mt-2 inline-flex items-center gap-1 rounded-full border border-clay-ink/30 bg-clay px-2.5 py-1 text-[11px] font-bold text-clay-ink">
                                        <x-portal-icon name="clock" class="h-3 w-3" />
                                        Sanctioned
                                    </span>
                                </div>
                                <div class="rounded-xl border border-haze bg-card p-4 shadow-sm">
                                    <span class="mb-1 block text-xs font-medium uppercase tracking-wider text-ink-soft">Days absent</span>
                                    <div class="text-2xl font-bold tabular text-ink">{{ $absent }}</div>
                                    <span class="mt-2 inline-flex items-center gap-1 rounded-full border border-line bg-tint px-2.5 py-1 text-[11px] font-bold text-ink-soft">
                                        <span class="h-1.5 w-1.5 rounded-full bg-alert"></span>
                                        Unexcused
                                    </span>
                                </div>
                                <div class="rounded-xl border border-haze bg-card p-4 shadow-sm">
                                    <span class="mb-1 block text-xs font-medium uppercase tracking-wider text-ink-soft">Attendance rate</span>
                                    <div class="text-2xl font-bold tabular {{ $rate >= 75 ? 'text-green' : 'text-alert' }}">{{ $rate }}%</div>
                                    <span class="mt-2 inline-flex items-center gap-1 rounded-full border border-green/20 bg-mint px-2.5 py-1 text-[11px] font-bold text-mint-ink">
                                        Across {{ $records->count() }} recorded day{{ $records->count() === 1 ? '' : 's' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Ledger table --}}
                        <div class="overflow-hidden rounded-xl border border-haze bg-card shadow-sm">
                            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-haze p-5">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-mist text-green">
                                        <x-portal-icon name="clipboard-list" class="h-5 w-5" />
                                    </span>
                                    <div>
                                        <h3 class="text-base font-bold text-ink">Attendance ledger</h3>
                                        <span class="text-xs text-ink-soft">Chronological roll call register, newest first</span>
                                    </div>
                                </div>
                                <span class="rounded-full bg-mist px-3 py-1 text-xs font-semibold text-ink-soft">
                                    {{ $records->count() }} record{{ $records->count() === 1 ? '' : 's' }}
                                </span>
                            </div>

                            @if ($records->isNotEmpty())
                                <div class="overflow-x-auto">
                                    <table class="w-full border-collapse text-left text-sm">
                                        <thead class="border-b border-haze bg-mist">
                                            <tr class="text-xs font-bold uppercase tracking-wider text-ink">
                                                <th scope="col" class="px-6 py-4">Date</th>
                                                <th scope="col" class="px-6 py-4">Day</th>
                                                <th scope="col" class="px-6 py-4">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-haze text-ink">
                                            @foreach ($records as $record)
                                                <tr class="transition-colors hover:bg-mist/50">
                                                    <td class="whitespace-nowrap px-6 py-4 font-semibold">
                                                        <time datetime="{{ $record['iso'] }}">{{ $record['date'] }}</time>
                                                    </td>
                                                    <td class="whitespace-nowrap px-6 py-4 text-ink-soft">{{ $record['day'] }}</td>
                                                    <td class="whitespace-nowrap px-6 py-4">
                                                        @php
                                                            $statusTone = match ($record['status']->value) {
                                                                'present' => 'bg-mint text-mint-ink border-green/30',
                                                                'leave' => 'bg-clay text-clay-ink border-clay-ink/30',
                                                                default => 'bg-alert-soft text-alert-ink border-alert/30',
                                                            };
                                                        @endphp
                                                        <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold {{ $statusTone }}">
                                                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                                            {{ $record['status']->label() }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="p-8 text-center text-sm text-ink-soft">No attendance recorded yet.</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @elseif ($identifier !== null)
                <div class="flex flex-col items-center gap-3 rounded-xl border border-haze bg-alert-soft/40 p-10 text-center">
                    <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-alert-soft text-alert-ink">
                        <x-portal-icon name="exclamation-triangle" class="h-8 w-8" />
                    </span>
                    <h3 class="text-xl font-bold text-ink">No records found</h3>
                    <p class="max-w-md text-sm text-ink-soft">
                        Check the CNIC or GR number and try again — the register only shows
                        currently enrolled students.
                    </p>
                </div>
            @else
                <div class="flex flex-col items-center justify-center rounded-xl bg-card p-12 text-center shadow-md">
                    <span class="mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-tint text-navy shadow-inner">
                        <x-portal-icon name="calendar" class="h-10 w-10" />
                    </span>
                    <h3 class="mb-2 font-display text-2xl font-semibold text-ink">Attendance appears here</h3>
                    <p class="mx-auto max-w-md text-sm leading-relaxed text-ink-soft">
                        Enter a parent or guardian CNIC, or the student GR number, to open the
                        daily roll call ledger for the last 30 recorded school days.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
