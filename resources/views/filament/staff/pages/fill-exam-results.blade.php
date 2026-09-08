<x-filament-panels::page>
@php
    $sheetState = $this->sheetState;
    $locked = $sheetState !== null && $sheetState['locked'];
@endphp
<div style="display: grid; gap: 1.5rem;">
    <x-filament::section
        description="Record exam marks for your assigned classes and subjects."
    >
        <div style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr));">
            <x-filament-forms::field-wrapper label="Class" id="classId" statePath="classId">
                <x-filament::input.wrapper>
                    <x-filament::input.select id="classId" wire:model.live="classId">
                        <option value="">Select a class</option>
                        @foreach ($this->classes as $class)
                            <option value="{{ $class->getKey() }}">{{ $class->name }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </x-filament-forms::field-wrapper>

            <x-filament-forms::field-wrapper label="Subject" id="subjectId" statePath="subjectId">
                <x-filament::input.wrapper>
                    <x-filament::input.select id="subjectId" wire:model.live="subjectId">
                        <option value="">Select a subject</option>
                        @foreach ($this->subjects as $subject)
                            <option value="{{ $subject->getKey() }}">{{ $subject->name }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </x-filament-forms::field-wrapper>

            <x-filament-forms::field-wrapper label="Year" id="year" statePath="year">
                <x-filament::input.wrapper>
                    <x-filament::input.select id="year" wire:model.live="year">
                        @foreach ($this->years as $yearValue => $yearLabel)
                            <option value="{{ $yearValue }}">{{ $yearLabel }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </x-filament-forms::field-wrapper>
        </div>

        @if ($this->classId !== null && $this->subjects->isEmpty())
            <p style="margin: 0.25rem 0 0; font-size: 0.8rem;" class="ledger-muted">
                No subjects are assigned to you in this class yet.
            </p>
        @endif

        @if ($sheetState !== null)
            @if ($locked)
                <x-filament::callout
                    color="danger"
                    icon="heroicon-m-lock-closed"
                    class="mt-4"
                >
                    <x-slot name="heading">
                        Results locked — no edits allowed
                    </x-slot>
                    These results were published on {{ $sheetState['ends_at']->subDays(30)->format('j M Y') }} and the
                    30-day correction window closed on {{ $sheetState['ends_at']->format('j M Y') }}.
                </x-filament::callout>
            @else
                <x-filament::callout
                    color="info"
                    icon="heroicon-m-clock"
                    class="mt-4"
                >
                    <x-slot name="heading">
                        Published — corrections open until {{ $sheetState['ends_at']->format('j M Y') }}
                    </x-slot>
                    Students see these results. Mistakes reported by students can be corrected until the window closes.
                </x-filament::callout>
            @endif
        @endif
    </x-filament::section>

    @if ($this->students->isNotEmpty())
        <x-filament::section heading="Marks">
            <p style="margin: 0 0 0.75rem; font-size: 0.8rem;" class="ledger-muted">
                {{ $this->savedResults->count() }} of {{ $this->students->count() }} students recorded — you can save anytime and finish the rest later.
            </p>
            <div class="ledger-card">
                <table class="ledger-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>GR #</th>
                            <th>Recorded</th>
                            <th>{{ $this->bounds !== null ? 'Marks (out of '.\App\Models\MarkingScheme::formatBound($this->bounds['max']).')' : 'Marks' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->students as $student)
                            @php
                                $saved = $this->savedResults->get((string) $student->getKey());
                            @endphp
                            <tr>
                                <td>{{ $student->name }}</td>
                                <td class="ledger-muted">{{ $student->gr_no }}</td>
                                <td>
                                    @if ($saved !== null)
                                        <span style="display: inline-flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                                            <x-filament::badge color="success" icon="heroicon-m-check">Saved</x-filament::badge>
                                            @if ($this->reportMode === 'grades')
                                                <x-filament::badge color="info">
                                                    {{ \App\Support\Grades::fromMarks($saved['marks'], $saved['total']) }}
                                                </x-filament::badge>
                                            @endif
                                        </span>
                                    @else
                                        <span class="ledger-muted" style="font-size: 0.75rem;">Not yet</span>
                                    @endif
                                </td>
                                <td style="max-width: 12rem;">
                                    <x-filament::input.wrapper>
                                        <x-filament::input
                                            type="number"
                                            :min="\App\Models\MarkingScheme::formatBound($this->bounds['min'] ?? 0)"
                                            :max="\App\Models\MarkingScheme::formatBound($this->bounds['max'] ?? 100)"
                                            step="0.5"
                                            wire:model="marks.{{ $student->getKey() }}"
                                            :readonly="$locked"
                                        />
                                    </x-filament::input.wrapper>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-slot name="footer">
                @if (! $locked)
                    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                        <x-filament::button wire:click="save" icon="heroicon-m-check">
                            Fill exam results
                        </x-filament::button>
                    </div>
                @else
                    <x-filament::badge color="danger" icon="heroicon-m-lock-closed">
                        Locked on {{ $sheetState['ends_at']->format('j M Y') }}
                    </x-filament::badge>
                @endif
            </x-slot>
        </x-filament::section>
    @endif
</div>
</x-filament-panels::page>
