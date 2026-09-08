<x-filament-panels::page>
<div style="display: grid; gap: 1.5rem;">
    <x-filament::section
        description="The classes and subjects you are currently teaching."
    >
        @if ($assignments->isEmpty())
            <p style="margin: 0; font-size: 0.875rem;" class="ledger-muted">
                No assignments yet. You can request one under “My Requests”.
            </p>
        @else
            <div class="ledger-card">
                <table class="ledger-table">
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Subject</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($assignments as $assignment)
                            <tr>
                                <td>{{ $assignment->studentClass->name }}</td>
                                <td>{{ $assignment->subject->name }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</div>
</x-filament-panels::page>
