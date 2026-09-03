<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component {
    //
};
?>

<div class="py-12 space-y-12">
    <section class="text-center space-y-4">
        <flux:heading size="xl">{{ config('app.name') }}</flux:heading>
        <flux:subheading class="max-w-2xl mx-auto">
            A simple, honest window into your school. Check results, follow attendance,
            and stay on top of fee payments — no account needed.
        </flux:subheading>
        <flux:button variant="primary" href="/results" wire:navigate icon="academic-cap">
            Check results
        </flux:button>
    </section>

    <section class="grid gap-6 sm:grid-cols-3">
        <flux:card>
            <flux:heading size="md">Results</flux:heading>
            <flux:text class="mt-2">
                Look up exam results by year — as grades or class positions.
            </flux:text>
            <flux:button variant="ghost" href="/results" wire:navigate icon="arrow-right" class="mt-4">
                Open results
            </flux:button>
        </flux:card>

        <flux:card>
            <flux:heading size="md">Attendance</flux:heading>
            <flux:text class="mt-2">
                Follow your child's daily attendance record, day by day.
            </flux:text>
            <flux:button variant="ghost" href="/attendance" wire:navigate icon="arrow-right" class="mt-4">
                Open attendance
            </flux:button>
        </flux:card>

        <flux:card>
            <flux:heading size="md">Fees</flux:heading>
            <flux:text class="mt-2">
                See dues for every year and pay online via EasyPaisa or JazzCash.
            </flux:text>
            <flux:button variant="ghost" href="/fees" wire:navigate icon="arrow-right" class="mt-4">
                Open fees
            </flux:button>
        </flux:card>
    </section>

    <section class="text-center">
        <flux:callout variant="secondary" inline>
            <flux:callout.heading>Staff member?</flux:callout.heading>
            <flux:callout.text>
                <flux:link href="/staff" wire:navigate>Staff login</flux:link>
                &nbsp;·&nbsp;
                <flux:link href="/dashboard" wire:navigate>Admin login</flux:link>
            </flux:callout.text>
        </flux:callout>
    </section>
</div>
