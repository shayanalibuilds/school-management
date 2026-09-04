<nav class="sticky top-0 z-30 border-b border-zinc-200 bg-white/90 backdrop-blur">
    <div class="mx-auto flex w-full max-w-5xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3 sm:px-6">
        <a href="/" class="text-lg font-semibold tracking-tight text-zinc-950">
            {{ config('app.name') }}
        </a>

        <div class="flex items-center gap-4 text-sm font-medium">
            @foreach (['home' => 'Home', 'results' => 'Results', 'attendance' => 'Attendance', 'fees' => 'Fees'] as $route => $label)
                <a
                    href="/{{ $route === 'home' ? '' : $route }}"
                    @class([
                        'text-blue-600' => request()->routeIs($route),
                        'text-zinc-600 hover:text-blue-600' => ! request()->routeIs($route),
                    ])
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="ms-auto flex items-center gap-2">
            <x-filament::button tag="a" href="/staff" color="gray" outlined size="sm">
                Staff login
            </x-filament::button>
            <x-filament::button tag="a" href="/dashboard" color="primary" size="sm">
                Admin login
            </x-filament::button>
        </div>
    </div>
</nav>
