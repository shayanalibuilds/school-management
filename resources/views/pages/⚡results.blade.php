<?php

use App\Support\Grades;
use App\Support\Positions;
use App\Support\StudentLookup;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component {
    #[Url]
    public ?string $identifier = null;

    #[Url]
    public ?string $year = null;

    #[Url]
    public string $type = 'grades';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getResultRowsProperty(): array
    {
        if ($this->identifier === null || $this->year === null) {
            return [];
        }

        $students = StudentLookup::resolve($this->identifier);

        if ($students->isEmpty()) {
            return [];
        }

        $rows = [];

        foreach ($students as $student) {
            if ($this->type === 'positions') {
                $positions = Positions::forClass($student->studentClass, (int) $this->year);

                $position = $positions[$student->getKey()] ?? null;

                $rows[] = [
                    'student' => $student,
                    'class' => $student->studentClass->name,
                    'label' => $position !== null ? Positions::label($position).' position' : '—',
                    'results' => [],
                ];

                continue;
            }

            $results = $student->examResults()->where('year', (int) $this->year)->with('subject')->get();

            $rows[] = [
                'student' => $student,
                'class' => $student->studentClass->name,
                'label' => null,
                'results' => $results->map(fn ($result): array => [
                    'subject' => $result->subject->name,
                    'marks' => $result->marks,
                    'total' => $result->total_marks,
                    'grade' => Grades::fromMarks($result->marks, $result->total_marks),
                ])->all(),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, int>
     */
    public function getYearsProperty(): array
    {
        return range((int) today()->year - 9, (int) today()->year);
    }

    public function search(): void
    {
        // State is bound to the URL; re-rendering performs the lookup.
    }
};
?>

<div class="space-y-6 py-4">
    <div class="space-y-1">
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-950">Check Results</h1>
        <p class="text-sm text-zinc-600">Enter the parent or guardian CNIC, or the student GR number (GR #).</p>
    </div>

    <x-filament::section>
        <form wire:submit="search" class="grid gap-4 sm:grid-cols-4">
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
            <div>
                <label for="year" class="mb-1 block text-sm font-medium text-zinc-700">Year</label>
                <x-filament::input.select id="year" wire:model="year">
                    <option value="">Select year</option>
                    @foreach ($this->years as $availableYear)
                        <option value="{{ $availableYear }}">{{ $availableYear }}</option>
                    @endforeach
                </x-filament::input.select>
            </div>
            <div class="flex items-end justify-between gap-2">
                <div class="grow">
                    <label for="type" class="mb-1 block text-sm font-medium text-zinc-700">Show as</label>
                    <x-filament::input.select id="type" wire:model="type">
                        <option value="grades">Grades</option>
                        <option value="positions">Positions</option>
                    </x-filament::input.select>
                </div>
                <x-filament::button type="submit" icon="heroicon-m-magnifying-glass" class="!mb-0.5">
                    Search
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    @if ($this->resultRows !== [])
        @foreach ($this->resultRows as $row)
            <x-filament::section
                :heading="$row['student']->name.' — '.$row['class'].' — '.$this->year"
            >
                @if ($row['label'] !== null)
                    <x-filament::badge size="lg" color="primary">{{ $row['label'] }}</x-filament::badge>
                @elseif ($row['results'] !== [])
                    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-50">
                                <tr class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                    <th class="px-4 py-2 text-start">Subject</th>
                                    <th class="px-4 py-2 text-start">Marks</th>
                                    <th class="px-4 py-2 text-start">Grade</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200">
                                @foreach ($row['results'] as $result)
                                    <tr>
                                        <td class="px-4 py-2 text-zinc-950">{{ $result['subject'] }}</td>
                                        <td class="px-4 py-2 text-zinc-950">{{ $result['marks'] }} / {{ $result['total'] }}</td>
                                        <td class="px-4 py-2"><x-filament::badge color="primary">{{ $result['grade'] }}</x-filament::badge></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-zinc-600">No results recorded for this year.</p>
                @endif
            </x-filament::section>
        @endforeach
    @elseif ($identifier !== null && $year !== null)
        <x-filament::callout
            color="warning"
            icon="heroicon-m-exclamation-triangle"
            heading="No records found"
        >
            Check the CNIC or roll number and try again.
        </x-filament::callout>
    @endif
</div>
