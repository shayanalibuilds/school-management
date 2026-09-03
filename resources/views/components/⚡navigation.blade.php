<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div>
    <flux:header class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:brand href="/" name="{{ config('app.name') }}" class="max-lg:hidden dark:hidden" />
        <flux:brand href="/" name="{{ config('app.name') }}" class="hidden max-lg:hidden! dark:flex" />

        <flux:navbar class="max-lg:hidden">
            <flux:navbar.item icon="home" href="/" wire:navigate>Home</flux:navbar.item>
            <flux:navbar.item icon="academic-cap" href="/results" wire:navigate>Results</flux:navbar.item>
            <flux:navbar.item icon="clipboard-document-check" href="/attendance" wire:navigate>Attendance</flux:navbar.item>
            <flux:navbar.item icon="banknotes" href="/fees" wire:navigate>Fees</flux:navbar.item>
        </flux:navbar>

        <flux:spacer />

        <flux:button variant="ghost" href="/staff" wire:navigate icon="lock-closed">Staff login</flux:button>
        <flux:button variant="primary" href="/dashboard" wire:navigate icon="shield-check">Admin login</flux:button>
    </flux:header>

    <flux:sidebar sticky collapsible="mobile" class="border-r border-zinc-200 bg-zinc-50 lg:hidden dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <flux:sidebar.brand href="/" name="{{ config('app.name') }}" />
            <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.item icon="home" href="/" wire:navigate>Home</flux:sidebar.item>
            <flux:sidebar.item icon="academic-cap" href="/results" wire:navigate>Results</flux:sidebar.item>
            <flux:sidebar.item icon="clipboard-document-check" href="/attendance" wire:navigate>Attendance</flux:sidebar.item>
            <flux:sidebar.item icon="banknotes" href="/fees" wire:navigate>Fees</flux:sidebar.item>
        </flux:sidebar.nav>
    </flux:sidebar>

    <flux:main container>
        {{ $slot }}
    </flux:main>
</div>
