<footer class="bg-navy text-white">
    <div class="mx-auto grid w-full max-w-7xl gap-10 px-4 pb-10 pt-12 sm:px-6 md:grid-cols-2 lg:grid-cols-4 lg:px-8">
        <div class="flex flex-col gap-3">
            <div class="flex items-center gap-2">
                <x-portal-icon name="shield-check" class="h-6 w-6 text-glow" />
                <span class="text-lg font-semibold text-white">{{ config('app.name') }}</span>
            </div>
            <p class="text-sm leading-relaxed text-steel">
                The official public window into the school: examination gazettes, daily attendance
                registers, and fee ledgers — published by the school office for parents and guardians.
            </p>
            <div class="flex flex-wrap items-center gap-2 pt-1">
                <span class="rounded bg-white/10 px-2 py-1 text-[11px] font-semibold text-glow">Official Records</span>
                <span class="rounded bg-white/10 px-2 py-1 text-[11px] font-semibold text-steel">GR Registry</span>
            </div>
        </div>

        <div class="flex flex-col gap-3">
            <span class="text-sm font-semibold uppercase tracking-wider text-white">Quick Links</span>
            <ul class="flex flex-col gap-2 text-sm text-steel">
                @foreach (['results' => 'Examination Results & Gazette', 'attendance' => 'Attendance Records', 'fees' => 'Fee Vouchers & Receipts'] as $route => $label)
                    <li>
                        <a href="/{{ $route }}" class="flex items-center gap-1.5 transition-colors hover:text-white">
                            <x-portal-icon name="arrow-right" class="h-3.5 w-3.5" />
                            {{ $label }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="flex flex-col gap-3">
            <span class="text-sm font-semibold uppercase tracking-wider text-white">Registrar's Desk</span>
            <div class="flex flex-col gap-1.5 text-sm text-steel">
                <p>
                    <strong class="font-semibold text-white">Lookups</strong>
                    accept a parent or guardian CNIC, or the student GR number printed on the ID card.
                </p>
                <p class="pt-1">
                    <strong class="font-semibold text-white">Corrections</strong>
                    to any public record are handled by the school administration office.
                </p>
            </div>
        </div>

        <div class="flex flex-col gap-3">
            <span class="text-sm font-semibold uppercase tracking-wider text-white">Institutional Seal</span>
            <div class="flex flex-col gap-2 rounded-lg bg-white/5 p-4">
                <div class="flex items-center gap-2">
                    <x-portal-icon name="building-library" class="h-7 w-7 text-glow" />
                    <div>
                        <span class="block text-sm font-semibold leading-tight text-white">Office of the Registrar</span>
                        <span class="text-xs text-steel">Records & Verification</span>
                    </div>
                </div>
                <p class="text-xs leading-relaxed text-steel">
                    Every gazette, grade card, and receipt on this portal is system-generated and kept
                    verifiable against the school's internal register.
                </p>
            </div>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="mx-auto flex w-full max-w-7xl flex-col items-center justify-between gap-2 px-4 py-4 text-xs text-steel sm:flex-row sm:px-6 lg:px-8">
            <span>&copy; {{ now()->year }} {{ config('app.name') }} · Public records portal</span>
            <span class="tabular">Published results only — unpublished records never appear here</span>
        </div>
    </div>
</footer>
