<div class="py-8 space-y-6">
    <div>
        <flux:heading size="lg">Enter Results</flux:heading>
        <flux:subheading>Record exam marks for your assigned classes and subjects.</flux:subheading>
    </div>

    <flux:card class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-3">
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
                <flux:label>Subject</flux:label>
                <flux:select wire:model.live="subjectId" placeholder="Select a subject">
                    @foreach ($this->subjects as $subject)
                        <flux:select.option value="{{ $subject->getKey() }}">{{ $subject->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="subjectId" />
            </div>
            <div>
                <flux:label>Year</flux:label>
                <flux:select wire:model.live="year">
                    @foreach ($this->years as $yearValue => $yearLabel)
                        <flux:select.option value="{{ $yearValue }}">{{ $yearLabel }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="year" />
            </div>
        </div>
    </flux:card>

    @if ($this->students->isNotEmpty())
        <flux:card>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column heading="Student" />
                    <flux:table.column heading="Marks (out of 100)" />
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->students as $student)
                        <flux:table.row>
                            <flux:table.cell>{{ $student->name }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:input
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.5"
                                    wire:model="marks.{{ $student->getKey() }}"
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            <div class="mt-4">
                <flux:button variant="primary" wire:click="save" icon="check">Save results</flux:button>
            </div>
        </flux:card>
    @endif
</div>
