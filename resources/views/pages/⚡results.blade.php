<?php

use App\Support\Grades;
use App\Support\Positions;
use App\Support\StudentLookup;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component {
    #[Url]
    public ?string $identifier = null;

    #[Url]
    public ?string $year = null;

    #[Url]
    public string $type = 'grades';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getResultRowsProperty(): array
    {
        if ($this->identifier === null || $this->year === null) {
            return [];
        }

        $students = StudentLookup::resolve($this->identifier);

        if ($students->isEmpty()) {
            return [];
        }

        $rows = [];

        foreach ($students as $student) {
            if ($this->type === 'positions') {
                $positions = Positions::forClass($student->studentClass, (int) $this->year);

                $position = $positions[$student->getKey()] ?? null;

                $rows[] = [
                    'student' => $student,
                    'class' => $student->studentClass->name,
                    'label' => $position !== null ? Positions::label($position).' position' : '—',
                    'position' => $position,
                    'candidates' => $positions->count(),
                    'total_marks' => null,
                    'total_max' => null,
                    'overall_grade' => null,
                    'results' => [],
                ];

                continue;
            }

            $results = $student->examResults()
                ->where('year', (int) $this->year)
                ->published()
                ->with('subject')
                ->get();

            $totalMarks = (float) $results->sum('marks');
            $totalMax = (float) $results->sum('total_marks');

            $position = null;
            $candidates = 0;

            if ($results->isNotEmpty()) {
                $positions = Positions::forClass($student->studentClass, (int) $this->year);
                $position = $positions[$student->getKey()] ?? null;
                $candidates = $positions->count();
            }

            $rows[] = [
                'student' => $student,
                'class' => $student->studentClass->name,
                'label' => null,
                'position' => $position,
                'candidates' => $candidates,
                'total_marks' => $totalMarks,
                'total_max' => $totalMax,
                'overall_grade' => $totalMax > 0 ? Grades::fromMarks($totalMarks, $totalMax) : null,
                'results' => $results->map(fn ($result): array => [
                    'subject' => $result->subject->name,
                    'marks' => $result->marks,
                    'total' => $result->total_marks,
                    'grade' => Grades::fromMarks($result->marks, $result->total_marks),
                ])->all(),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, int>
     */
    public function getYearsProperty(): array
    {
        return range((int) today()->year - 9, (int) today()->year);
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
                    Public Academic Registry
                </span>
            </div>
            <h1 class="font-display text-4xl font-medium tracking-tight text-ink">Exam results</h1>
            <p class="mt-1.5 text-base text-ink-soft">
                Enter a CNIC or GR number to see marks, grades, and class position.
            </p>
        </div>
        <div class="flex items-center gap-2.5 rounded-xl border border-haze bg-card px-4 py-2.5 shadow-sm">
            <x-portal-icon name="fingerprint" class="h-5 w-5 text-green" />
            <div class="leading-tight">
                <span class="block text-[11px] font-semibold uppercase tracking-wider text-ink-soft">Accepted credentials</span>
                <span class="text-sm font-semibold text-ink">Parent CNIC · Student GR</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12">
        {{-- Lookup column --}}
        <div class="flex flex-col gap-6 lg:col-span-4">
            <div class="relative overflow-hidden rounded-xl bg-card shadow-md">
                <div class="h-1.5 w-full bg-gradient-to-r from-navy via-green to-navy"></div>
                <div class="p-6">
                    <div class="mb-5 flex items-center justify-between border-b border-haze pb-4">
                        <div>
                            <span class="block text-xs font-bold uppercase tracking-wider text-ink-soft">Candidate Query</span>
                            <span class="text-lg font-bold text-ink">General Register Dossier</span>
                        </div>
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-mist text-navy">
                            <x-portal-icon name="identification" class="h-5 w-5" />
                        </span>
                    </div>

                    <form wire:submit="search" class="flex flex-col gap-4">
                        <div>
                            <label for="identifier" class="mb-1.5 block text-sm font-semibold text-ink">
                                CNIC or GR number <span class="text-alert" aria-hidden="true">*</span>
                            </label>
                            <input
                                id="identifier"
                                type="text"
                                wire:model="identifier"
                                required
                                autocomplete="off"
                                placeholder="35202-1234567-1 or GR 42"
                                class="w-full rounded-lg border border-line bg-mist px-4 py-3 text-sm font-semibold tabular text-ink shadow-sm transition-all placeholder:font-normal placeholder:text-line focus:bg-card focus:outline-none focus:ring-2 focus:ring-navy"
                            />
                        </div>
                        <div>
                            <label for="year" class="mb-1.5 block text-sm font-semibold text-ink">Examination year</label>
                            <div class="relative">
                                <select
                                    id="year"
                                    wire:model="year"
                                    class="w-full cursor-pointer appearance-none rounded-lg border border-line bg-mist px-4 py-3 pr-10 text-sm text-ink shadow-sm transition-all focus:bg-card focus:outline-none focus:ring-2 focus:ring-navy"
                                >
                                    <option value="">Select year</option>
                                    @foreach ($this->years as $availableYear)
                                        <option value="{{ $availableYear }}">{{ $availableYear }}</option>
                                    @endforeach
                                </select>
                                <x-portal-icon name="chevron-down" class="pointer-events-none absolute right-3 top-3.5 h-4 w-4 text-ink-soft" />
                            </div>
                        </div>
                        <div>
                            <label for="type" class="mb-1.5 block text-sm font-semibold text-ink">Show as</label>
                            <div class="relative">
                                <select
                                    id="type"
                                    wire:model="type"
                                    class="w-full cursor-pointer appearance-none rounded-lg border border-line bg-mist px-4 py-3 pr-10 text-sm text-ink shadow-sm transition-all focus:bg-card focus:outline-none focus:ring-2 focus:ring-navy"
                                >
                                    <option value="grades">Grades</option>
                                    <option value="positions">Positions</option>
                                </select>
                                <x-portal-icon name="chevron-down" class="pointer-events-none absolute right-3 top-3.5 h-4 w-4 text-ink-soft" />
                            </div>
                        </div>
                        <button
                            type="submit"
                            class="group flex min-h-[44px] w-full items-center justify-center gap-2 rounded-lg bg-navy px-6 py-3 text-sm font-semibold text-white shadow-sm transition-all hover:bg-navy-hover"
                        >
                            Look up results
                            <x-portal-icon name="arrow-right" class="h-4 w-4 text-glow transition-transform group-hover:translate-x-0.5" />
                        </button>
                    </form>

                    <div class="mt-4 flex items-start gap-2 text-ink-soft">
                        <x-portal-icon name="shield-check" class="mt-0.5 h-4 w-4 shrink-0 text-green" />
                        <p class="text-[13px] leading-snug">
                            Only published results appear here. Marks and grades are issued exactly as recorded by the school office.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-start gap-3 rounded-xl border border-haze bg-card p-5 shadow-sm">
                <x-portal-icon name="shield-check" class="mt-0.5 h-5 w-5 shrink-0 text-green" />
                <div class="text-[13px] leading-relaxed text-ink-soft">
                    <span class="mb-0.5 block text-sm font-semibold text-ink">Verified Registry</span>
                    Every gazette entry is published by the school office after the examination record
                    is checked. Unpublished and locked sheets never appear on this portal.
                </div>
            </div>
        </div>

        {{-- Results column --}}
        <div class="flex flex-col gap-6 lg:col-span-8">
            @if ($this->resultRows !== [])
                @foreach ($this->resultRows as $row)
                    @php
                        $student = $row['student'];
                        $initials = collect(explode(' ', trim($student->name)))
                            ->filter()
                            ->take(2)
                            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
                            ->implode('');
                    @endphp

                    <div class="flex flex-col gap-6">
                        {{-- Summary card --}}
                        <div class="overflow-hidden rounded-xl bg-card shadow-md">
                            <div class="h-2 w-full bg-green"></div>
                            <div class="rounded-xl bg-mist/60 p-5">
                                <div class="flex flex-col justify-between gap-5 md:flex-row md:items-start">
                                    <div class="flex items-start gap-4">
                                        <span
                                            aria-hidden="true"
                                            class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-navy font-display text-2xl font-semibold text-white shadow-md"
                                        >{{ $initials }}</span>
                                        <div>
                                            <div class="mb-1.5 flex flex-wrap items-center gap-2">
                                                <span class="inline-flex items-center gap-1.5 rounded-md bg-mint px-2.5 py-0.5 text-xs font-bold text-mint-ink">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-green"></span>
                                                    Record verified
                                                </span>
                                                <span class="rounded bg-tint px-2 py-0.5 font-mono text-xs font-bold tabular text-ink">
                                                    GR: {{ \App\Support\GrNumber::bare($student->gr_no) }}
                                                </span>
                                            </div>
                                            <h2 class="text-xl font-bold text-ink">{{ $student->name }}</h2>
                                            <p class="mt-0.5 text-sm text-ink-soft">
                                                {{ $row['class'] }} · Examination year {{ $year }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="shrink-0 leading-tight">
                                        <span class="block text-[11px] font-semibold uppercase tracking-wider text-ink-soft">Examination series</span>
                                        <span class="text-sm font-bold tabular text-ink">Annual Term · {{ $year }}</span>
                                        @if ($row['position'] !== null)
                                            <span class="mt-1 flex items-center gap-1 text-[13px] font-semibold text-green">
                                                <x-portal-icon name="trophy" class="h-4 w-4" />
                                                {{ App\Support\Positions::label($row['position']) }} in class
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Stat blocks --}}
                            <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-3">
                                @if ($this->type === 'grades' && $row['total_max'] > 0)
                                    <div class="flex flex-col justify-between rounded-xl bg-mist p-4 shadow-sm">
                                        <div class="mb-2 flex items-center justify-between text-ink-soft">
                                            <span class="text-xs font-semibold uppercase tracking-wider">Total marks</span>
                                            <x-portal-icon name="clipboard-list" class="h-5 w-5 text-navy" />
                                        </div>
                                        <div>
                                            <div class="flex items-baseline gap-2">
                                                <span class="text-2xl font-bold tabular text-ink">{{ number_format($row['total_marks'], 0) }}</span>
                                                <span class="text-sm tabular text-ink-soft">/ {{ number_format($row['total_max'], 0) }}</span>
                                            </div>
                                            <div class="mt-2 flex items-center gap-2">
                                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-haze-deep">
                                                    <div
                                                        class="h-1.5 rounded-full bg-navy"
                                                        style="width: {{ min(100, round(($row['total_marks'] / $row['total_max']) * 100, 2)) }}%"
                                                    ></div>
                                                </div>
                                                <span class="shrink-0 text-xs font-bold tabular text-ink">
                                                    {{ round(($row['total_marks'] / $row['total_max']) * 100, 2) }}%
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-col justify-between rounded-xl bg-mint/30 p-4 shadow-sm">
                                        <div class="mb-2 flex items-center justify-between text-mint-ink">
                                            <span class="text-xs font-semibold uppercase tracking-wider">Overall grade</span>
                                            <x-portal-icon name="trophy" class="h-5 w-5 text-green" />
                                        </div>
                                        <div class="text-2xl font-bold text-green">{{ $row['overall_grade'] }}</div>
                                        <div class="mt-2 flex items-center gap-1 text-xs font-semibold text-green">
                                            <x-portal-icon name="academic-cap" class="h-4 w-4" />
                                            Across {{ count($row['results']) }} subject{{ count($row['results']) === 1 ? '' : 's' }}
                                        </div>
                                    </div>
                                @else
                                    <div class="flex flex-col justify-between rounded-xl bg-mist p-4 shadow-sm md:col-span-2">
                                        <div class="mb-2 flex items-center justify-between text-ink-soft">
                                            <span class="text-xs font-semibold uppercase tracking-wider">Positions view</span>
                                            <x-portal-icon name="document-text" class="h-5 w-5 text-navy" />
                                        </div>
                                        <p class="text-sm text-ink-soft">
                                            Subject marks are hidden in this view — switch
                                            <span class="font-semibold text-ink">Show as: Grades</span> to see the full breakdown.
                                        </p>
                                    </div>
                                @endif
                                <div class="flex flex-col justify-between rounded-xl bg-mist p-4 shadow-sm">
                                    <div class="mb-2 flex items-center justify-between text-ink-soft">
                                        <span class="text-xs font-semibold uppercase tracking-wider">Class position</span>
                                        <x-portal-icon name="trophy" class="h-5 w-5 text-navy" />
                                    </div>
                                    <div class="text-2xl font-bold text-ink">
                                        @if ($row['position'] !== null)
                                            {{ App\Support\Positions::label($row['position']) }}
                                        @else
                                            —
                                        @endif
                                    </div>
                                    <div class="mt-2 text-xs text-ink-soft">
                                        @if ($row['position'] !== null)
                                            <span class="font-semibold text-ink">Rank {{ $row['position'] }}/{{ $row['candidates'] }}</span>
                                            in {{ $row['class'] }}
                                        @else
                                            Not ranked for {{ $year }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Subject table (grades mode) --}}
                        @if ($row['results'] !== [])
                            <div class="overflow-hidden rounded-xl bg-card shadow-md">
                                <div class="flex items-center justify-between bg-mist/60 px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <x-portal-icon name="clipboard-list" class="h-5 w-5 text-ink" />
                                        <h3 class="text-base font-bold text-ink">Subject-wise performance</h3>
                                    </div>
                                    <span class="rounded-full bg-mist px-3 py-1 text-xs font-semibold text-ink-soft">
                                        {{ count($row['results']) }} subject{{ count($row['results']) === 1 ? '' : 's' }}
                                    </span>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full border-collapse text-left">
                                        <thead>
                                            <tr class="bg-navy text-xs font-semibold uppercase tracking-wider text-white">
                                                <th scope="col" class="px-6 py-3.5">Subject</th>
                                                <th scope="col" class="px-4 py-3.5 text-right">Marks</th>
                                                <th scope="col" class="px-6 py-3.5 text-center">Grade</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-haze text-sm">
                                            @foreach ($row['results'] as $result)
                                                <tr class="transition-colors hover:bg-mist/70">
                                                    <td class="px-6 py-4 font-semibold text-ink">{{ $result['subject'] }}</td>
                                                    <td class="px-4 py-4 text-right font-semibold tabular text-ink">
                                                        {{ $result['marks'] }}
                                                        <span class="text-xs font-normal text-ink-soft">/ {{ $result['total'] }}</span>
                                                    </td>
                                                    <td class="px-6 py-4 text-center">
                                                        @php
                                                            $gradeTone = match (true) {
                                                                in_array($result['grade'], ['A+', 'A']) => 'bg-mint text-mint-ink',
                                                                in_array($result['grade'], ['B', 'C']) => 'bg-tint text-navy',
                                                                $result['grade'] === 'D' => 'bg-clay text-clay-ink',
                                                                default => 'bg-alert-soft text-alert-ink',
                                                            };
                                                        @endphp
                                                        <span class="inline-block rounded px-2.5 py-1 text-xs font-bold {{ $gradeTone }}">
                                                            {{ $result['grade'] }}
                                                        </span>
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
            @elseif ($identifier !== null && $year !== null)
                <div class="flex flex-col items-center gap-3 rounded-xl border border-haze bg-alert-soft/40 p-10 text-center">
                    <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-alert-soft text-alert-ink">
                        <x-portal-icon name="exclamation-triangle" class="h-8 w-8" />
                    </span>
                    <h3 class="text-xl font-bold text-ink">No published results found</h3>
                    <p class="max-w-md text-sm text-ink-soft">
                        Check the CNIC or GR number and the examination year, then search again —
                        only results the school has published are visible.
                    </p>
                </div>
            @else
                <div class="flex flex-col items-center justify-center rounded-xl bg-card p-12 text-center shadow-md">
                    <span class="mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-tint text-navy shadow-inner">
                        <x-portal-icon name="document-text" class="h-10 w-10" />
                    </span>
                    <h3 class="mb-2 font-display text-2xl font-semibold text-ink">Results appear here</h3>
                    <p class="mx-auto max-w-md text-sm leading-relaxed text-ink-soft">
                        Enter a parent or guardian CNIC, or the student GR number, then pick an
                        examination year to view the published gazette entry.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
