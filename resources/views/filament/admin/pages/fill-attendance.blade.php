<x-filament-panels::page>
<div style="display: grid; gap: 1.5rem;">
    <x-filament::section
        description="Pick a class, then record every student for today. Attendance can only be filled for the current day."
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

            <x-filament-forms::field-wrapper label="Date">
                <x-filament::input.wrapper>
                    <x-filament::input :value="\App\Filament\Pages\FillAttendance::attendanceDate()" type="date" readonly />
                </x-filament::input.wrapper>
            </x-filament-forms::field-wrapper>
        </div>
    </x-filament::section>

    @if ($this->students->isNotEmpty())
        {{-- Status picking happens entirely in the browser: clicking
             Present/Absent/Leave only updates local state instantly. The
             single database write happens when "Fill attendance" submits
             the whole board at once. The button colours live in the
             shared filament::panel-styles partial so they follow the
             panel theme. --}}
        <div x-data="{ statuses: @js($this->statuses) }" wire:key="attendance-board-{{ $classId }}">
            <x-filament::section heading="Students">
                <div class="ledger-card">
                    <table class="ledger-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>GR #</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->students as $student)
                                <tr>
                                    <td>{{ $student->name }}</td>
                                    <td class="ledger-muted">{{ $student->gr_no }}</td>
                                    <td>
                                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;" role="group" aria-label="Attendance status for {{ $student->name }}">
                                            @foreach (\App\Enums\AttendanceStatus::cases() as $status)
                                                <button
                                                    type="button"
                                                    class="att-btn"
                                                    data-status="{{ $status->value }}"
                                                    :aria-pressed="(statuses['{{ $student->getKey() }}'] ?? '') === '{{ $status->value }}'"
                                                    @click="statuses['{{ $student->getKey() }}'] = '{{ $status->value }}'"
                                                >
                                                    {{ $status->label() }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-slot name="footer">
                    <x-filament::button
                        x-on:click="$wire.statuses = statuses; $wire.save()"
                        icon="heroicon-m-check"
                    >
                        Fill attendance
                    </x-filament::button>
                </x-slot>
            </x-filament::section>
        </div>
    @endif
</div>
</x-filament-panels::page>
