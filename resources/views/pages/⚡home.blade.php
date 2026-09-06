<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component {
    //
};
?>

<div class="space-y-10 py-4">
    <section class="space-y-4 text-center">
        <h1 class="text-4xl font-semibold tracking-tight text-zinc-950">{{ config('app.name') }}</h1>
        <p class="mx-auto max-w-2xl text-lg text-zinc-600">
            A simple, honest window into your school. Check results, follow attendance,
            and stay on top of fee payments — no account needed.
        </p>
        <div class="flex justify-center">
            <x-filament::button tag="a" href="/results" icon="heroicon-m-academic-cap">
                Check results
            </x-filament::button>
        </div>
    </section>

    <section class="grid gap-6 sm:grid-cols-3">
        <x-filament::section
            heading="Results"
            description="Look up exam results by year — as grades or class positions."
        >
            <x-slot name="footer">
                <x-filament::button tag="a" href="/results" color="gray" icon="heroicon-m-arrow-right">
                    Open results
                </x-filament::button>
            </x-slot>
        </x-filament::section>

        <x-filament::section
            heading="Attendance"
            description="Follow your child's daily attendance record, day by day."
        >
            <x-slot name="footer">
                <x-filament::button tag="a" href="/attendance" color="gray" icon="heroicon-m-arrow-right">
                    Open attendance
                </x-filament::button>
            </x-slot>
        </x-filament::section>

        <x-filament::section
            heading="Fees"
            description="See dues for every year and pay online via EasyPaisa or JazzCash."
        >
            <x-slot name="footer">
                <x-filament::button tag="a" href="/fees" color="gray" icon="heroicon-m-arrow-right">
                    Open fees
                </x-filament::button>
            </x-slot>
        </x-filament::section>
    </section>

    <section class="flex justify-center">
        <x-filament::callout
            color="gray"
            icon="heroicon-m-user-group"
            heading="Staff member?"
        >
            <x-slot name="footer">
                <a href="/staff" class="font-medium text-blue-600 hover:underline">Staff login</a>
                &nbsp;·&nbsp;
                <a href="/dashboard" class="font-medium text-blue-600 hover:underline">Admin login</a>
            </x-slot>
        </x-filament::callout>
    </section>
</div>
