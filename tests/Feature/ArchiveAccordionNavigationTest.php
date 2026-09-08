<?php

declare(strict_types=1);

use App\Filament\Resources\Students\Pages\ListStudents;
use App\Filament\Resources\Students\StudentResource;
use App\Models\Admin;
use App\Models\Student;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('turns the students sidebar entry into an accordion with active and inactive children', function (): void {
    $admin = Admin::factory()->create();

    actingAs($admin, 'admin');
    Filament::setCurrentPanel('admin');

    $items = StudentResource::getNavigationItems();

    expect($items)->toHaveCount(1)
        ->and($items[0]->getLabel())->toBe('Students')
        ->and($items[0]->getGroup())->toBe('Academics');

    $children = collect($items[0]->getChildItems());

    expect($children)->toHaveCount(2);

    $active = $children->get(0);
    $inactive = $children->get(1);

    assert($active instanceof NavigationItem);
    assert($inactive instanceof NavigationItem);

    expect($active->getLabel())->toBe('Active students')
        ->and($inactive->getLabel())->toBe('Inactive students')
        ->and($inactive->getUrl())->toEndWith('?tab=inactive');
});

it('deep links the sidebar inactive entry into the archived tab of the list page', function (): void {
    $admin = Admin::factory()->create();

    actingAs($admin, 'admin');
    Filament::setCurrentPanel('admin');

    $archived = Student::factory()->create(['name' => 'Past Kid']);
    $archived->archive();

    Student::factory()->create(['name' => 'Present Kid']);

    get('/dashboard/students')
        ->assertOk()
        ->assertSee('Present Kid')
        ->assertDontSee('Past Kid')
        ->assertDontSee('tablist', false);

    get('/dashboard/students?tab=inactive')
        ->assertOk()
        ->assertSee('Past Kid')
        ->assertDontSee('Present Kid')
        ->assertDontSee('tablist', false);
});

it('renders no active/inactive tab bar on any archivable list page', function (): void {
    $admin = Admin::factory()->create();

    actingAs($admin, 'admin');
    Filament::setCurrentPanel('admin');

    foreach ([
        '/dashboard/students',
        '/dashboard/student-classes',
        '/dashboard/subjects',
        '/dashboard/parents',
        '/dashboard/guardians',
        '/dashboard/fee-structures',
    ] as $url) {
        get($url)->assertOk()->assertDontSee('tablist', false);
    }
});

it('keeps the active view as the default when the query parameter is unknown', function (): void {
    $admin = Admin::factory()->create();

    actingAs($admin, 'admin');
    Filament::setCurrentPanel('admin');

    Livewire::test(ListStudents::class)->assertSet('tab', 'active');
});
