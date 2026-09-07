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
             the whole board at once. --}}
        <style>
            .att-btn {
                display: inline-flex; align-items: center; justify-content: center;
                border-radius: 0.5rem; border: 1px solid #d1d5db; padding: 0.3rem 0.85rem;
                font-size: 0.8125rem; font-weight: 500; line-height: 1.25rem;
                cursor: pointer; background-color: #ffffff; color: #4b5563;
                transition: background-color 150ms ease, border-color 150ms ease, color 150ms ease;
            }
            .att-btn:hover { border-color: var(--primary-500, #3b82f6); color: var(--primary-600, #2563eb); }
            .att-btn:focus-visible { outline: 2px solid var(--primary-500, #3b82f6); outline-offset: 2px; }
            .att-btn[aria-pressed="true"] {
                background-color: var(--primary-600, #2563eb); border-color: var(--primary-600, #2563eb);
                color: #ffffff;
            }
            .att-btn[aria-pressed="true"]:hover { background-color: var(--primary-500, #3b82f6); }
        </style>

        <div x-data="{ statuses: @js($this->statuses) }" wire:key="attendance-board-{{ $classId }}">
            <x-filament::section heading="Students">
                <div style="overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 0.75rem; background-color: #ffffff;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                        <thead style="background-color: #f9fafb;">
                            <tr style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.025em; color: #6b7280;">
                                <th style="padding: 0.625rem 1rem; text-align: start;">Student</th>
                                <th style="padding: 0.625rem 1rem; text-align: start;">GR #</th>
                                <th style="padding: 0.625rem 1rem; text-align: start;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->students as $student)
                                <tr style="border-top: 1px solid #e5e7eb; color: #111827;">
                                    <td style="padding: 0.625rem 1rem;">{{ $student->name }}</td>
                                    <td style="padding: 0.625rem 1rem; color: #6b7280;">{{ $student->gr_no }}</td>
                                    <td style="padding: 0.625rem 1rem;">
                                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;" role="group" aria-label="Attendance status for {{ $student->name }}">
                                            @foreach (\App\Enums\AttendanceStatus::cases() as $status)
                                                <button
                                                    type="button"
                                                    class="att-btn"
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
