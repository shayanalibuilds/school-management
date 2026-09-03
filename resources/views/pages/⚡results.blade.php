<?php

use App\Support\Grades;
use App\Support\Positions;
use App\Support\StudentLookup;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component {
    #[Url]
    public ?string $cnic = null;

    #[Url]
    public ?string $phone = null;

    #[Url]
    public ?string $year = null;

    #[Url]
    public string $type = 'grades';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getResultRowsProperty(): array
    {
        if ($this->cnic === null || $this->phone === null || $this->year === null) {
            return [];
        }

        $students = StudentLookup::resolve($this->cnic, $this->phone);

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

<div class="py-8 space-y-6">
    <div>
        <flux:heading size="lg">Check Results</flux:heading>
        <flux:subheading>Enter the parent or guardian CNIC and registered phone number.</flux:subheading>
    </div>

    <flux:card>
        <form wire:submit="search" class="grid gap-4 sm:grid-cols-4">
            <flux:input wire:model="cnic" label="CNIC" placeholder="35202-1234567-1" />
            <flux:input wire:model="phone" label="Phone" placeholder="03001234567" />
            <div>
                <flux:label>Year</flux:label>
                <flux:select wire:model="year">
                    <option value="">Select year</option>
                    @foreach ($this->years as $availableYear)
                        <option value="{{ $availableYear }}">{{ $availableYear }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div>
                <flux:label>Show as</flux:label>
                <flux:select wire:model="type">
                    <option value="grades">Grades</option>
                    <option value="positions">Positions</option>
                </flux:select>
            </div>
            <div class="sm:col-span-4">
                <flux:button variant="primary" type="submit" icon="magnifying-glass">Search</flux:button>
            </div>
        </form>
    </flux:card>

    @if ($this->resultRows !== [])
        @foreach ($this->resultRows as $row)
            <flux:card class="space-y-3">
                <flux:heading size="md">{{ $row['student']->name }} — {{ $row['class'] }} — {{ $this->year }}</flux:heading>

                @if ($row['label'] !== null)
                    <flux:badge size="lg" variant="outline">{{ $row['label'] }}</flux:badge>
                @elseif (! empty($row['results']))
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column heading="Subject" />
                            <flux:table.column heading="Marks" />
                            <flux:table.column heading="Grade" />
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($row['results'] as $result)
                                <flux:table.row>
                                    <flux:table.cell>{{ $result['subject'] }}</flux:table.cell>
                                    <flux:table.cell>{{ $result['marks'] }} / {{ $result['total'] }}</flux:table.cell>
                                    <flux:table.cell><flux:badge>{{ $result['grade'] }}</flux:badge></flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @else
                    <flux:text>No results recorded for this year.</flux:text>
                @endif
            </flux:card>
        @endforeach
    @elseif ($cnic !== null && $phone !== null && $year !== null)
        <flux:callout variant="warning">
            <flux:callout.heading>No records found</flux:callout.heading>
            <flux:callout.text>Check the CNIC and phone number and try again.</flux:callout.text>
        </flux:callout>
    @endif
</div>
