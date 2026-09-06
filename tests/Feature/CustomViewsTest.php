<?php

declare(strict_types=1);

use App\Models\Admin;
use App\Models\Staff;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Custom blade views must be built from Filament components — never Flux.
 *
 * Flux styles ship in a separate CSS build that only the Livewire starter
 * layout loads, so Flux markup rendered inside Filament panels (staff pages,
 * payment settings) or without that build came out completely unstyled.
 * Filament components are styled by the same CSS the panels already ship.
 */
const CUSTOM_VIEWS = [
    'layouts/app.blade.php',
    'components/navigation.blade.php',
    'pages/⚡home.blade.php',
    'pages/⚡results.blade.php',
    'pages/⚡attendance.blade.php',
    'pages/⚡fees.blade.php',
    'filament/staff/pages/fill-attendance.blade.php',
    'filament/staff/pages/fill-exam-results.blade.php',
    'filament/admin/pages/fill-attendance.blade.php',
    'filament/admin/pages/fill-exam-results.blade.php',
    'filament/admin/pages/import-export.blade.php',
    'filament/staff/pages/my-assignments.blade.php',
    'filament/admin/pages/payment-settings.blade.php',
];

test('custom views are built from filament components, not flux', function (): void {
    foreach (CUSTOM_VIEWS as $view) {
        $path = resource_path('views/'.$view);

        expect(file_exists($path))->toBeTrue("expected {$view} to exist")
            ->and(file_get_contents($path))
            ->not->toContain('<flux:', "{$view} must not use Flux components");
    }
});

test('public layout loads filament assets without flux hooks', function (): void {
    $layout = (string) file_get_contents(resource_path('views/layouts/app.blade.php'));

    expect($layout)->toContain('@filamentStyles')
        ->toContain('@filamentScripts')
        ->toContain('--primary-600:')
        ->not->toContain('@fluxAppearance')
        ->not->toContain('@fluxScripts');

    // The blue palette override must come after @filamentStyles so it wins
    // over the amber default that Filament's asset pipeline registers.
    expect(mb_strpos($layout, '--primary-600:'))
        ->toBeGreaterThan((int) (mb_strpos($layout, '@filamentStyles')));

    $css = (string) file_get_contents(resource_path('css/app.css'));

    expect($css)->not->toContain('livewire/flux');
});

test('public landing renders filament styled markup', function (): void {
    get('/')
        ->assertOk()
        ->assertSee('fi-btn', false)
        ->assertSee('css/filament/filament/app.css', false);
});

test('staff fill attendance page renders filament styled markup', function (): void {
    actingAs(Staff::factory()->create(), 'staff');

    get('/staff/fill-attendance')
        ->assertOk()
        ->assertSee('fi-section', false)
        ->assertSee('fi-select-input', false)
        ->assertSee('fi-input', false)
        ->assertDontSee('<flux:', false);
});

test('admin payment settings page renders filament styled markup', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    get('/dashboard/payment-settings')
        ->assertOk()
        ->assertSee('fi-section', false)
        ->assertDontSee('<flux:', false);
});
