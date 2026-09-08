<?php

declare(strict_types=1);

use App\Models\Admin;
use App\Models\Staff;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * The hand-built page markup (ledger tables, settings cards, attendance
 * buttons, hints) declares no colours of its own: the shared
 * filament::panel-styles partial owns every colour and pairs a light
 * value with a dark-mode counterpart, so the custom markup follows the
 * panel's theme switch instead of staying light while everything around
 * it goes dark.
 */
it('pairs every themed surface with a dark mode counterpart', function (): void {
    $css = (string) view('filament.panel-styles');

    expect($css)->toContain('.fi-sidebar-sub-group-items')
        ->toContain('.ledger-card')
        ->toContain('.ledger-table')
        ->toContain('.att-btn')
        ->toContain('.dark .ledger-card')
        ->toContain('.dark .ledger-table thead')
        ->toContain('.dark .ledger-table tbody tr')
        ->toContain('.dark .att-btn');
});

it('resolves every themed colour from Filaments own palette variables', function (): void {
    $css = (string) view('filament.panel-styles');

    // Filament v5 renders its grays from Tailwind v4 oklch tokens; the
    // custom surfaces must pull the exact same variables instead of a
    // parallel hex palette, or dark mode shows a blue-tinted mismatch.
    expect($css)->toContain('var(--gray-200')
        ->toContain('var(--gray-300')
        ->toContain('var(--gray-400')
        ->toContain('var(--gray-500')
        ->toContain('var(--gray-600')
        ->toContain('var(--gray-700')
        ->toContain('var(--gray-900')
        ->toContain('var(--gray-950')
        ->toContain('var(--gray-100,')
        ->toContain('var(--success-600')
        ->toContain('var(--success-400')
        ->toContain('var(--danger-600')
        ->toContain('var(--warning-500')
        ->toContain('var(--primary-600');
});

it('keeps the legacy hex palette only as variable fallbacks', function (): void {
    $css = (string) view('filament.panel-styles');

    // The pre-token hex palette must never drive a rule directly again;
    // it may only survive inside var(...) fallbacks.
    $withoutTokens = (string) preg_replace('/var\(--[^)]*\)/', '', $css);

    foreach ([
        '#111827', '#030712', '#374151', '#e5e7eb', '#f9fafb',
        '#6b7280', '#9ca3af', '#d1d5db', '#4b5563', '#16a34a',
        '#4ade80', '#dc2626', '#ef4444', '#eab308', '#ca8a04', '#facc15',
    ] as $stale) {
        expect($withoutTokens)->not->toContain($stale);
    }
});

it('injects the shared panel styles into the admin shell', function (): void {
    $admin = Admin::factory()->create();
    actingAs($admin, 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    get('/dashboard')
        ->assertOk()
        ->assertSee('ledger-card', escape: false)
        ->assertSee('fi-sidebar-sub-group-items', escape: false)
        ->assertSee('.dark .ledger-card', escape: false);
});

it('injects the shared panel styles into the staff shell', function (): void {
    $staff = Staff::factory()->create();
    actingAs($staff, 'staff');
    Filament\Facades\Filament::setCurrentPanel('staff');

    get('/staff')
        ->assertOk()
        ->assertSee('ledger-card', escape: false)
        ->assertSee('fi-sidebar-sub-group-items', escape: false)
        ->assertSee('.dark .ledger-card', escape: false);
});
