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
                    'status' => $attendance->status,
                ]),
        ]);
    }

    public function search(): void
    {
        // State is bound to the URL; re-rendering performs the lookup.
    }
};
?>

<div class="py-8 space-y-6">
    <div>
        <flux:heading size="lg">Check Attendance</flux:heading>
        <flux:subheading>Enter the parent or guardian CNIC, or the student roll number (SR #).</flux:subheading>
    </div>

    <flux:card>
        <form wire:submit="search" class="grid gap-4 sm:grid-cols-3">
            <flux:input wire:model="identifier" label="CNIC or roll number" placeholder="35202-1234567-1 or 42" class="sm:col-span-2" />
            <div class="flex items-end">
                <flux:button variant="primary" type="submit" icon="magnifying-glass">Search</flux:button>
            </div>
        </form>
    </flux:card>

    @if ($this->students->isNotEmpty())
        @foreach ($this->students as $entry)
            <flux:card class="space-y-3">
                <flux:heading size="md">{{ $entry['student']->name }} — {{ $entry['student']->studentClass->name }} — SR #{{ $entry['student']->sr_no }}</flux:heading>

                @if ($entry['records']->isNotEmpty())
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column heading="Date" />
                            <flux:table.column heading="Status" />
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($entry['records'] as $record)
                                <flux:table.row>
                                    <flux:table.cell>{{ $record['date'] }}</flux:table.cell>
                                    <flux:table.cell><flux:badge>{{ $record['status']->label() }}</flux:badge></flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @else
                    <flux:text>No attendance recorded yet.</flux:text>
                @endif
            </flux:card>
        @endforeach
    @elseif ($identifier !== null)
        <flux:callout variant="warning">
            <flux:callout.heading>No records found</flux:callout.heading>
            <flux:callout.text>Check the CNIC or roll number and try again.</flux:callout.text>
        </flux:callout>
    @endif
</div>
