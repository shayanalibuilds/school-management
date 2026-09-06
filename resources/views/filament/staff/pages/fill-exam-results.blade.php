@php
    $sheetState = $this->sheetState;
    $locked = $sheetState !== null && $sheetState['locked'];
@endphp
<div style="display: grid; gap: 1.5rem;">
    <x-filament::section
        heading="Fill exam results"
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
            <div style="overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 0.75rem; background-color: #ffffff;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                    <thead style="background-color: #f9fafb;">
                        <tr style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.025em; color: #6b7280;">
                            <th style="padding: 0.625rem 1rem; text-align: start;">Student</th>
                            <th style="padding: 0.625rem 1rem; text-align: start;">GR #</th>
                            <th style="padding: 0.625rem 1rem; text-align: start;">Marks (out of 100)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->students as $student)
                            <tr style="border-top: 1px solid #e5e7eb; color: #111827;">
                                <td style="padding: 0.625rem 1rem;">{{ $student->name }}</td>
                                <td style="padding: 0.625rem 1rem; color: #6b7280;">{{ $student->gr_no }}</td>
                                <td style="padding: 0.625rem 1rem; max-width: 12rem;">
                                    <x-filament::input.wrapper>
                                        <x-filament::input
                                            type="number"
                                            min="0"
                                            max="100"
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
                        <x-filament::button
                            wire:click="publish"
                            wire:confirm="Publish these results? Students will see them, and corrections stay open for 30 days."
                            color="success"
                            outlined
                            icon="heroicon-m-globe-alt"
                        >
                            Publish results
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
