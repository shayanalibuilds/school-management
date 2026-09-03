<?php

use App\Enums\AttendanceStatus;
use App\Support\StudentLookup;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component {
    #[Url]
    public ?string $cnic = null;

    #[Url]
    public ?string $phone = null;

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function getStudentsProperty(): \Illuminate\Support\Collection
    {
        if ($this->cnic === null || $this->phone === null) {
            return collect();
        }

        $students = StudentLookup::resolve($this->cnic, $this->phone);

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
        <flux:subheading>Enter the parent or guardian CNIC and registered phone number.</flux:subheading>
    </div>

    <flux:card>
        <form wire:submit="search" class="grid gap-4 sm:grid-cols-3">
            <flux:input wire:model="cnic" label="CNIC" placeholder="35202-1234567-1" />
            <flux:input wire:model="phone" label="Phone" placeholder="03001234567" />
            <div class="flex items-end">
                <flux:button variant="primary" type="submit" icon="magnifying-glass">Search</flux:button>
            </div>
        </form>
    </flux:card>

    @foreach ($this->students as $entry)
        <flux:card class="space-y-3">
            <flux:heading size="md">{{ $entry['student']->name }} — {{ $entry['student']->studentClass?->name }}</flux:heading>

            @if ($entry['records']->isEmpty())
                <flux:text>No attendance recorded yet.</flux:text>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column heading="Date" />
                        <flux:table.column heading="Status" />
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($entry['records'] as $record)
                            <flux:table.row>
                                <flux:table.cell>{{ $record['date'] }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge :variant="$record['status'] === AttendanceStatus::Present ? 'outline' : ($record['status'] === AttendanceStatus::Absent ? 'filled' : 'soft')">
                                        {{ $record['status']->label() }}
                                    </flux:badge>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </flux:card>
    @endforeach

    @if ($cnic !== null && $phone !== null && $this->students->isEmpty())
        <flux:callout variant="warning">
            <flux:callout.heading>No records found</flux:callout.heading>
            <flux:callout.text>Check the CNIC and phone number and try again.</flux:callout.text>
        </flux:callout>
    @endif
</div>
