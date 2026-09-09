<?php

declare(strict_types=1);

use App\Filament\Widgets\AttendanceChart;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\StudentClass;
use Filament\Facades\Filament;
use Livewire\Livewire;

/**
 * The school's green brand palette must stay consistent across every
 * rendering surface: both Filament panels (which emit the palette CSS
 * variables the custom panel markup resolves from), the public layout's
 * override block (which re-points Filament's named primary at the same
 * scale), the shared panel stylesheet's fallback hexes, the portal
 * design tokens, and the dashboard charts.
 */
const BRAND_GREEN_400 = '#4ff8d2';
const BRAND_GREEN_500 = '#23e7ba';
const BRAND_GREEN_600 = '#0f7861';

const BRAND_GREEN_PALETTE = [
    50 => '#f1fdfb',
    100 => '#e0faf4',
    200 => '#c2f4e9',
    300 => '#96eeda',
    400 => '#4ff8d2',
    500 => '#23e7ba',
    600 => '#0f7861',
    700 => '#13725d',
    800 => '#135d4c',
    900 => '#124e41',
    950 => '#0a2f26',
];

it('registers the green primary scale on the admin panel', function (): void {
    expect(Filament::getPanel('admin')->getColors()['primary'])->toBe(BRAND_GREEN_PALETTE);
});

it('registers the green primary scale on the staff panel', function (): void {
    expect(Filament::getPanel('staff')->getColors()['primary'])->toBe(BRAND_GREEN_PALETTE);
});

it('renders the attendance chart bars in the brand green', function (): void {
    $class = StudentClass::factory()->create(['name' => 'Chart Palette Class']);
    $student = Student::factory()->create(['student_class_id' => $class->getKey()]);
    Attendance::factory()->create(['student_id' => $student->getKey()]);

    Livewire::test(AttendanceChart::class)
        ->assertSuccessful()
        ->assertSeeHtml(BRAND_GREEN_500)
        ->assertDontSeeHtml('#2563eb');
});

it('keeps both panel providers free of the retired blue scale', function (): void {
    foreach (['AdminPanelProvider', 'StaffPanelProvider'] as $provider) {
        $source = (string) file_get_contents(app_path("Providers/Filament/{$provider}.php"));

        expect($source)
            ->toContain(BRAND_GREEN_400)
            ->toContain(BRAND_GREEN_600)
            ->not->toContain('#2563eb', "{$provider} must not carry the retired blue");
    }
});

it('mirrors the green scale in the public layout override', function (): void {
    $layout = (string) file_get_contents(resource_path('views/layouts/app.blade.php'));

    expect($layout)
        ->toContain('--primary-400: '.BRAND_GREEN_400)
        ->toContain('--primary-500: '.BRAND_GREEN_500)
        ->toContain('--primary-600: '.BRAND_GREEN_600)
        ->not->toContain('#2563eb');
});

it('resolves panel stylesheet fallbacks from the green scale', function (): void {
    $partial = (string) file_get_contents(resource_path('views/filament/panel-styles.blade.php'));

    expect($partial)
        ->toContain('var(--primary-500, '.BRAND_GREEN_500.')')
        ->toContain('var(--primary-600, '.BRAND_GREEN_600.')')
        ->not->toContain('#3b82f6')
        ->not->toContain('#2563eb');
});

it('carries green accents and the slate shell in the portal tokens', function (): void {
    $css = (string) file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain('--color-green: '.BRAND_GREEN_600)
        ->toContain('--color-glow: '.BRAND_GREEN_400)
        ->toContain('--color-shell: #0d1318')
        ->not->toContain('#006c48')
        ->not->toContain('#121b2f');
});

it('renders dashboard charts in the brand palette', function (): void {
    foreach (['AttendanceChart', 'StudentPerformanceChart', 'SchoolProgressChart'] as $widget) {
        $source = (string) file_get_contents(app_path("Filament/Widgets/{$widget}.php"));

        expect($source)->toContain(BRAND_GREEN_500);
    }

    // Estimated spending keeps the palette's warm accent, not a clashing red.
    expect((string) file_get_contents(app_path('Filament/Widgets/SchoolProgressChart.php')))
        ->toContain('#e0953c')
        ->not->toContain('#ef4444');
});
