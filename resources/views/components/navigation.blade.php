<nav class="sticky top-0 z-40 border-b border-haze bg-card/90 shadow-sm backdrop-blur-md">
    <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <a href="/" class="flex min-w-0 items-center gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-navy text-white shadow-sm">
                <x-portal-icon name="academic-cap" class="h-5 w-5" />
            </span>
            <span class="min-w-0">
                <span class="block truncate text-[17px] font-semibold leading-tight text-ink">
                    {{ config('app.name') }}
                </span>
                <span class="block text-[11px] uppercase tracking-wider text-ink-soft">Academic Registry</span>
            </span>
        </a>

        <div class="hidden items-center gap-1 text-sm md:flex">
            @foreach (['home' => 'Home', 'results' => 'Results', 'attendance' => 'Attendance', 'fees' => 'Fees'] as $route => $label)
                <a
                    href="/{{ $route === 'home' ? '' : $route }}"
                    @class([
                        'rounded-lg px-3.5 py-2 transition-colors' => true,
                        'bg-tint font-semibold text-ink' => request()->routeIs($route),
                        'text-ink-soft hover:bg-mist hover:text-ink' => ! request()->routeIs($route),
                    ])
                    @if (request()->routeIs($route)) aria-current="page" @endif
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="flex items-center gap-2.5">
            <span class="hidden items-center gap-1.5 rounded-full bg-mist px-3 py-1 text-xs font-semibold text-ink-soft sm:inline-flex">
                <span class="h-2 w-2 rounded-full bg-green"></span>
                Public Portal
            </span>
            <a
                href="/staff"
                class="rounded-lg border border-line px-3.5 py-2 text-sm font-medium text-ink transition-colors hover:bg-tint"
            >
                Staff sign in
            </a>
            <a
                href="/dashboard"
                class="rounded-lg bg-navy px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-navy-hover"
            >
                Admin sign in
            </a>
        </div>
    </div>

    <!-- Compact route switcher for small screens: same four destinations. -->
    <div class="border-t border-haze bg-card/95 px-4 py-2 md:hidden">
        <div class="flex items-center gap-1 overflow-x-auto text-sm">
            @foreach (['home' => 'Home', 'results' => 'Results', 'attendance' => 'Attendance', 'fees' => 'Fees'] as $route => $label)
                <a
                    href="/{{ $route === 'home' ? '' : $route }}"
                    @class([
                        'whitespace-nowrap rounded-lg px-3 py-1.5' => true,
                        'bg-tint font-semibold text-ink' => request()->routeIs($route),
                        'text-ink-soft' => ! request()->routeIs($route),
                    ])
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>
</nav>
