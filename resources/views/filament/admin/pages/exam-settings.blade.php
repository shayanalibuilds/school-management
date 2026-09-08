<x-filament-panels::page>
<div style="display: grid; gap: 1.5rem;">
    <x-filament::section
        description="Decide how exam performance is reported this year: letter grades from the grading scale below, or class positions from total marks. The public gazette follows this choice."
    >
        <form wire:submit="saveReportMode" style="display: grid; gap: 1rem;">
            <x-filament-forms::field-wrapper
                label="Report style"
                id="reportMode"
                statePath="reportMode"
            >
                <x-filament::input.wrapper>
                    <x-filament::input.select id="reportMode" wire:model="reportMode">
                        <option value="grades">Grades — A+, A, B, C, D from the grading scale</option>
                        <option value="positions">Positions — 1st, 2nd, 3rd by total marks</option>
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </x-filament-forms::field-wrapper>

            <div>
                <x-filament::button type="submit" icon="heroicon-m-check">
                    Save report style
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    <x-filament::section
        description="Set the minimum and maximum marks teachers may enter for every subject of a class. Subjects without a scheme keep the default 0 - 100 scale."
    >
        <div style="display: grid; gap: 1rem; max-width: 24rem;">
            <x-filament-forms::field-wrapper
                label="Class"
                id="schemeClassId"
                statePath="schemeClassId"
            >
                <x-filament::input.wrapper>
                    <x-filament::input.select id="schemeClassId" wire:model.live="schemeClassId">
                        <option value="">Select a class</option>
                        @foreach ($this->classes as $class)
                            <option value="{{ $class->getKey() }}">{{ $class->name }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </x-filament-forms::field-wrapper>
        </div>

        @if ($this->schemeClass === null)
            <p style="margin: 0.25rem 0 0; font-size: 0.8rem;" class="ledger-muted">
                Select a class to edit its marking scheme.
            </p>
        @elseif ($this->schemeSubjects->isEmpty())
            <p style="margin: 0.25rem 0 0; font-size: 0.8rem;" class="ledger-muted">
                No subjects are attached to this class yet.
            </p>
        @else
            <form wire:submit="saveSchemes" style="display: grid; gap: 1rem;">
                <div class="ledger-card">
                    <table class="ledger-table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Minimum marks</th>
                                <th>Maximum marks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->schemeSubjects as $subject)
                                <tr>
                                    <td>{{ $subject->name }}</td>
                                    <td style="max-width: 10rem;">
                                        <x-filament::input.wrapper>
                                            <x-filament::input
                                                type="number"
                                                min="0"
                                                step="0.5"
                                                wire:model="schemes.{{ $subject->getKey() }}.min"
                                            />
                                        </x-filament::input.wrapper>
                                    </td>
                                    <td style="max-width: 10rem;">
                                        <x-filament::input.wrapper>
                                            <x-filament::input
                                                type="number"
                                                min="0"
                                                step="0.5"
                                                wire:model="schemes.{{ $subject->getKey() }}.max"
                                            />
                                        </x-filament::input.wrapper>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div>
                    <x-filament::button type="submit" icon="heroicon-m-check">
                        Save marking scheme
                    </x-filament::button>
                </div>
            </form>
        @endif
    </x-filament::section>

    <x-filament::section
        description="Grades are awarded when the percentage (marks out of the subject maximum) reaches a threshold. Leave the scale empty to use the built-in boundaries: A+ 90, A 80, B 70, C 60, D 50."
    >
        <form wire:submit="saveScale" style="display: grid; gap: 1rem;">
            <div class="ledger-card">
                <table class="ledger-table">
                    <thead>
                        <tr>
                            <th>Grade</th>
                            <th>Minimum percentage</th>
                            <th style="width: 3rem;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($scale as $index => $row)
                            <tr>
                                <td style="max-width: 10rem;">
                                    <x-filament::input.wrapper>
                                        <x-filament::input
                                            type="text"
                                            wire:model="scale.{{ $index }}.name"
                                            placeholder="A+"
                                        />
                                    </x-filament::input.wrapper>
                                </td>
                                <td style="max-width: 12rem;">
                                    <x-filament::input.wrapper>
                                        <x-filament::input
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.5"
                                            wire:model="scale.{{ $index }}.min_percentage"
                                            placeholder="90"
                                        />
                                    </x-filament::input.wrapper>
                                </td>
                                <td>
                                    <x-filament::icon-button
                                        icon="heroicon-m-trash"
                                        color="danger"
                                        wire:click="removeScaleRow({{ $index }})"
                                        wire:key="remove-scale-{{ $index }}"
                                    >
                                        Remove grade
                                    </x-filament::icon-button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="ledger-muted">
                                    The scale is empty — the built-in boundaries apply.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <x-filament::button type="button" color="gray" wire:click="addScaleRow" icon="heroicon-m-plus">
                    Add grade
                </x-filament::button>
                <x-filament::button type="submit" icon="heroicon-m-check">
                    Save grading scale
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</div>
</x-filament-panels::page>
