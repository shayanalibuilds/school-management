<nav class="sticky top-0 z-40 border-b border-haze bg-card/90 shadow-sm backdrop-blur-md">
    <!-- flex-wrap below md lets the sign-in buttons drop to their own
         right-aligned row instead of squishing on small phones. -->
    <div class="mx-auto flex min-h-16 w-full max-w-7xl flex-wrap items-center justify-between gap-x-4 gap-y-2 px-4 py-2 sm:px-6 md:h-16 md:flex-nowrap md:py-0 lg:px-8">
        <a href="/" class="flex min-w-0 items-center gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-shell text-white shadow-sm">
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

        <!-- ml-auto pushes the wrapped button row to the right edge. -->
        <div class="ml-auto flex shrink-0 items-center gap-2 sm:gap-2.5">
            <span class="hidden items-center gap-1.5 rounded-full bg-mist px-3 py-1 text-xs font-semibold text-ink-soft sm:inline-flex">
                <span class="h-2 w-2 rounded-full bg-green"></span>
                Public Portal
            </span>
            <a
                href="/staff"
                class="whitespace-nowrap rounded-lg border border-line px-3 py-2 text-sm font-medium text-ink transition-colors hover:bg-tint sm:px-3.5"
            >
                Staff sign in
            </a>
            <a
                href="/dashboard"
                class="whitespace-nowrap rounded-lg bg-shell px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-shell-hover sm:px-4"
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
