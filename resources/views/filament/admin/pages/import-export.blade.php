<div style="display: grid; gap: 1.5rem;">
    <x-filament::section
        heading="Import staff from CSV"
        description="Upload a CSV file with a header row. Staff are matched by CNIC. Students, attendance, exam results, fees, payments, payrolls, expenses, parents and guardians import directly from their own pages via the Import CSV button."
    >
        <div style="max-width: 28rem;">
            <form method="POST" action="{{ route('imports.staff') }}" enctype="multipart/form-data" style="border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem; background: #ffffff;">
                @csrf
                <p style="font-weight: 600; color: #111827; margin: 0 0 0.25rem;">Staff</p>
                <p style="font-size: 0.75rem; color: #6b7280; margin: 0 0 0.75rem;">Columns: name, cnic, email, phone, joining_date, status</p>
                <x-filament::input.wrapper class="mb-3">
                    <x-filament::input
                        type="file"
                        name="csv"
                        accept=".csv,text/csv"
                        required
                    />
                </x-filament::input.wrapper>
                <x-filament::button size="xs" icon="heroicon-m-arrow-up-tray">
                    Import staff
                </x-filament::button>
            </form>
        </div>
    </x-filament::section>

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

    @if (session('import_status'))
        <x-filament::callout
            color="success"
            icon="heroicon-m-check-circle"
        >
            <x-slot name="heading">
                {{ session('import_status') }}
            </x-slot>
        </x-filament::callout>
    @endif
</div>
