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

<div class="space-y-6 py-4">
    <div class="space-y-1">
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-950">Check Attendance</h1>
        <p class="text-sm text-zinc-600">Enter the parent or guardian CNIC, or the student roll number (SR #).</p>
    </div>

    <x-filament::section>
        <form wire:submit="search" class="grid gap-4 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <label for="identifier" class="mb-1 block text-sm font-medium text-zinc-700">CNIC or roll number</label>
                <x-filament::input.wrapper>
                    <x-filament::input
                        id="identifier"
                        type="text"
                        wire:model="identifier"
                        placeholder="35202-1234567-1 or 42"
                    />
                </x-filament::input.wrapper>
            </div>
            <div class="flex items-end">
                <x-filament::button type="submit" icon="heroicon-m-magnifying-glass">Search</x-filament::button>
            </div>
        </form>
    </x-filament::section>

    @if ($this->students->isNotEmpty())
        @foreach ($this->students as $entry)
            <x-filament::section
                :heading="$entry['student']->name.' — '.$entry['student']->studentClass->name.' — SR #'.$entry['student']->sr_no"
            >
                @if ($entry['records']->isNotEmpty())
                    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-50">
                                <tr class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                    <th class="px-4 py-2 text-start">Date</th>
                                    <th class="px-4 py-2 text-start">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200">
                                @foreach ($entry['records'] as $record)
                                    <tr>
                                        <td class="px-4 py-2 text-zinc-950">{{ $record['date'] }}</td>
                                        <td class="px-4 py-2">
                                            <x-filament::badge
                                                :color="$record['status']->value === 'present' ? 'success' : ($record['status']->value === 'late' ? 'warning' : 'danger')"
                                            >
                                                {{ $record['status']->label() }}
                                            </x-filament::badge>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-zinc-600">No attendance recorded yet.</p>
                @endif
            </x-filament::section>
        @endforeach
    @elseif ($identifier !== null)
        <x-filament::callout
            color="warning"
            icon="heroicon-m-exclamation-triangle"
            heading="No records found"
        >
            Check the CNIC or roll number and try again.
        </x-filament::callout>
    @endif
</div>
