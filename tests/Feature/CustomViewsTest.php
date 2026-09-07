<?php

declare(strict_types=1);

use App\Models\Admin;
use App\Models\Staff;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Custom blade views must never use Flux.
 *
 * Flux styles ship in a separate CSS build that only the Livewire starter
 * layout loads, so Flux markup rendered inside Filament panels (staff pages,
 * payment settings) or without that build came out completely unstyled.
 *
 * Filament panel views are built from Filament components, styled by the same
 * CSS the panels already ship. Public portal views use the institutional
 * registry design system: Tailwind tokens defined in resources/css/app.css
 * (Newsreader display type, navy primary containers, green verification
 * accents) rendered by the app's own Vite build.
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

test('public landing renders the registry design system markup', function (): void {
    get('/')
        ->assertOk()
        // Design tokens from the app's own Vite build are applied...
        ->assertSee('bg-navy', false)
        ->assertSee('font-display', false)
        // ...Filament's stylesheet is still loaded for panel parity...
        ->assertSee('css/filament/filament/app.css', false)
        // ...and Flux never appears anywhere.
        ->assertDontSee('<flux:', false);
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

/**
 * Every custom panel page must render the standard Filament page chrome:
 * the fi-header block is what keeps breathing room between the sticky top
 * navigation and the first section. A view that skips the
 * x-filament-panels::page wrapper loses the header and its section lands
 * flush against the topbar — this pins the structure that prevents that.
 */
test('every custom panel page renders the standard header chrome', function (string $url, string $guard): void {
    $user = $guard === 'admin' ? Admin::factory()->create() : Staff::factory()->create();

    actingAs($user, $guard)
        ->get($url)
        ->assertOk()
        ->assertSee('fi-header', false)
        ->assertSee('fi-page-content', false);
})->with([
    'admin import/export' => ['/dashboard/import-export', 'admin'],
    'admin fill attendance' => ['/dashboard/fill-attendance', 'admin'],
    'admin fill exam results' => ['/dashboard/fill-exam-results', 'admin'],
    'admin payment settings' => ['/dashboard/payment-settings', 'admin'],
    'admin app settings' => ['/dashboard/app-settings-page', 'admin'],
    'staff fill attendance' => ['/staff/fill-attendance', 'staff'],
    'staff fill exam results' => ['/staff/fill-exam-results', 'staff'],
    'staff my assignments' => ['/staff/my-assignments', 'staff'],
]);
