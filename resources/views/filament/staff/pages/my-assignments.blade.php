<div class="py-8 space-y-6">
    <div>
        <flux:heading size="lg">My Assignments</flux:heading>
        <flux:subheading>The classes and subjects you are currently teaching.</flux:subheading>
    </div>

    @if ($assignments->isEmpty())
        <flux:card>
            <flux:text>No assignments yet. You can request one under “My Requests”.</flux:text>
        </flux:card>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column heading="Class" />
                <flux:table.column heading="Subject" />
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($assignments as $assignment)
                    <flux:table.row>
                        <flux:table.cell>{{ $assignment->studentClass->name }}</flux:table.cell>
                        <flux:table.cell>{{ $assignment->subject->name }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
