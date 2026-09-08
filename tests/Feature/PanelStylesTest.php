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
