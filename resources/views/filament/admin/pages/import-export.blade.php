<x-filament-panels::page>
    <div style="display: grid; gap: 1.5rem;">
        <x-filament::section
            heading="Import staff"
            description="Use the Import staff button in the header to upload a CSV with a header row. Staff are matched by CNIC. Students, attendance, exam results, fees, payments, payrolls, expenses, parents and guardians import directly from their own pages via the Import CSV button."
        />
        <x-filament::section
            heading="Staff CSV columns"
            description="name, cnic, email, phone, joining_date, status (active, on_leave or resigned)."
        />

        <x-filament::section
            heading="Export to CSV"
            description="Download the current data as a CSV file."
        >
            <div style="display: grid; gap: 0.5rem; grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));">
                @foreach ([
                    'students' => 'Students',
                    'staff' => 'Staff',
                    'attendance' => 'Attendance',
                    'exam-results' => 'Exam results',
                    'fees' => 'Fees',
                    'payments' => 'Payments',
                    'payrolls' => 'Payrolls',
                    'expenses' => 'Expenses',
                    'parents' => 'Parents',
                    'guardians' => 'Guardians',
                ] as $type => $label)
                    <x-filament::button
                        tag="a"
                        :href="route('exports', ['type' => $type])"
                        icon="heroicon-m-arrow-down-tray"
                        color="gray"
                        target="_blank"
                    >
                        {{ $label }}
                    </x-filament::button>
                @endforeach
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
