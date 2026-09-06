<div style="display: grid; gap: 1.5rem;">
    <x-filament::section
        heading="Fill attendance"
        description="Daily attendance must be submitted before 8:20 AM."
    >
        <div style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr));">
            <x-filament-forms::field-wrapper label="Class" id="classId" statePath="classId">
                <x-filament::input.select id="classId" wire:model.live="classId">
                    <option value="">Select a class</option>
                    @foreach ($this->classes as $class)
                        <option value="{{ $class->getKey() }}">{{ $class->name }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament-forms::field-wrapper>

            <x-filament-forms::field-wrapper label="Date" id="date" statePath="date">
                <x-filament::input.wrapper>
                    <x-filament::input id="date" type="date" wire:model.live="date" />
                </x-filament::input.wrapper>
            </x-filament-forms::field-wrapper>
        </div>
    </x-filament::section>

    @if ($this->students->isNotEmpty())
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
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                        @foreach (\App\Enums\AttendanceStatus::cases() as $status)
                                            <x-filament::button
                                                size="xs"
                                                :color="$status->value === (\App\Enums\AttendanceStatus::tryFrom($statuses[$student->getKey()] ?? ''))?->value ? 'primary' : 'gray'"
                                                :outlined="$status->value !== (\App\Enums\AttendanceStatus::tryFrom($statuses[$student->getKey()] ?? ''))?->value"
                                                wire:click="setStatus('{{ $student->getKey() }}', '{{ $status->value }}')"
                                            >
                                                {{ $status->label() }}
                                            </x-filament::button>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-slot name="footer">
                <x-filament::button wire:click="save" icon="heroicon-m-check">
                    Fill attendance
                </x-filament::button>
            </x-slot>
        </x-filament::section>
    @endif
</div>
