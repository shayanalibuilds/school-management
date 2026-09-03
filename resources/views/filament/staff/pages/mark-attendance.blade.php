<div class="py-8 space-y-6">
    <div>
        <flux:heading size="lg">Mark Attendance</flux:heading>
        <flux:subheading>Daily attendance must be submitted before 8:20 AM.</flux:subheading>
    </div>

    <flux:card class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <flux:label>Class</flux:label>
                <flux:select wire:model.live="classId" placeholder="Select a class">
                    @foreach ($this->classes as $class)
                        <flux:select.option value="{{ $class->getKey() }}">{{ $class->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="classId" />
            </div>
            <div>
                <flux:label>Date</flux:label>
                <flux:input type="date" wire:model.live="date" />
                <flux:error name="date" />
            </div>
        </div>
    </flux:card>

    @if ($this->students->isNotEmpty())
        <flux:card>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column heading="Student" />
                    <flux:table.column heading="Status" />
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->students as $student)
                        <flux:table.row>
                            <flux:table.cell>{{ $student->name }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex gap-2">
                                    @foreach (\App\Enums\AttendanceStatus::cases() as $status)
                                        <flux:button
                                            size="xs"
                                            :variant="$status->value === (\App\Enums\AttendanceStatus::tryFrom($statuses[$student->getKey()] ?? ''))?->value ? 'primary' : 'ghost'"
                                            wire:click="setStatus({{ $student->getKey() }}, '{{ $status->value }}')"
                                        >
                                            {{ $status->label() }}
                                        </flux:button>
                                    @endforeach
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            <div class="mt-4">
                <flux:button variant="primary" wire:click="save" icon="check">Save attendance</flux:button>
            </div>
        </flux:card>
    @endif
</div>
