<div style="display: grid; gap: 1.5rem;">
    <x-filament::section
        heading="My assignments"
        description="The classes and subjects you are currently teaching."
    >
        @if ($assignments->isEmpty())
            <p style="margin: 0; font-size: 0.875rem; color: #6b7280;">
                No assignments yet. You can request one under “My Requests”.
            </p>
        @else
            <div style="overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 0.75rem; background-color: #ffffff;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                    <thead style="background-color: #f9fafb;">
                        <tr style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.025em; color: #6b7280;">
                            <th style="padding: 0.625rem 1rem; text-align: start;">Class</th>
                            <th style="padding: 0.625rem 1rem; text-align: start;">Subject</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($assignments as $assignment)
                            <tr style="border-top: 1px solid #e5e7eb; color: #111827;">
                                <td style="padding: 0.625rem 1rem;">{{ $assignment->studentClass->name }}</td>
                                <td style="padding: 0.625rem 1rem;">{{ $assignment->subject->name }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</div>
