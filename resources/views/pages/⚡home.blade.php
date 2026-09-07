<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component {
    //
}
?>

<div class="space-y-12 py-2">
    <section class="mx-auto max-w-3xl space-y-5 text-center">
        <span class="inline-flex items-center gap-2 rounded-full bg-tint px-3 py-1 text-xs font-semibold uppercase tracking-wider text-ink-soft">
            <span class="h-2 w-2 rounded-full bg-green"></span>
            Public Academic Registry
        </span>
        <h1 class="font-display text-4xl font-medium tracking-tight text-ink md:text-5xl">
            {{ config('app.name') }}
        </h1>
        <p class="mx-auto max-w-2xl text-lg leading-relaxed text-ink-soft">
            A simple, honest window into your school. Check results, follow attendance,
            and stay on top of fee payments — no account needed.
        </p>
        <div class="flex flex-wrap items-center justify-center gap-3 pt-1">
            <a
                href="/results"
                class="inline-flex min-h-[44px] items-center gap-2 rounded-lg bg-navy px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-navy-hover"
            >
                <x-portal-icon name="academic-cap" class="h-4.5 w-4.5" />
                Check results
            </a>
            <a
                href="/fees"
                class="inline-flex min-h-[44px] items-center gap-2 rounded-lg bg-tint px-6 py-3 text-sm font-semibold text-ink transition-colors hover:bg-haze"
            >
                <x-portal-icon name="banknotes" class="h-4.5 w-4.5" />
                Look up fees
            </a>
        </div>
    </section>

    <section class="grid gap-6 md:grid-cols-3">
        @foreach ([
            'results' => ['Results', 'Look up exam results by year — as grades or class positions.', 'academic-cap'],
            'attendance' => ['Attendance', "Follow your child's daily attendance record, day by day.", 'calendar'],
            'fees' => ['Fees', 'See dues for every year and pay online via EasyPaisa or JazzCash.', 'receipt'],
        ] as $route => [$heading, $description, $icon])
            <div class="flex flex-col rounded-xl border border-haze bg-card shadow-sm">
                <div class="flex flex-1 flex-col gap-3 p-6">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-tint text-navy">
                        <x-portal-icon name="{{ $icon === 'receipt' ? 'document-text' : $icon }}" class="h-5 w-5" />
                    </span>
                    <h2 class="text-lg font-bold text-ink">{{ $heading }}</h2>
                    <p class="text-sm leading-relaxed text-ink-soft">{{ $description }}</p>
                </div>
                <a
                    href="/{{ $route }}"
                    class="flex items-center gap-1.5 border-t border-haze px-6 py-4 text-sm font-semibold text-green transition-colors hover:bg-mist"
                >
                    Open {{ strtolower($heading) }}
                    <x-portal-icon name="arrow-right" class="h-4 w-4" />
                </a>
            </div>
        @endforeach
    </section>

    <section class="mx-auto flex max-w-2xl items-center justify-center">
        <div class="flex w-full flex-wrap items-center justify-center gap-x-4 gap-y-2 rounded-xl border border-haze bg-card px-6 py-4 text-sm shadow-sm">
            <span class="flex items-center gap-2 font-semibold text-ink">
                <x-portal-icon name="identification" class="h-5 w-5 text-steel" />
                Staff member?
            </span>
            <a href="/staff" class="font-semibold text-green hover:underline">Staff sign in</a>
            <span class="text-line">•</span>
            <a href="/dashboard" class="font-semibold text-green hover:underline">Admin sign in</a>
        </div>
    </section>
</div>
