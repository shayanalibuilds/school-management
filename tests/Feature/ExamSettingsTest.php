<?php

declare(strict_types=1);

use App\Filament\Pages\ExamSettings;
use App\Models\Admin;
use App\Models\GradingScale;
use App\Models\MarkingScheme;
use App\Models\StudentClass;
use App\Models\Subject;
use App\Support\AppSettings;
use App\Support\Grades;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('renders the exam settings page to admins', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    get('/dashboard/exam-settings')->assertOk();
});

it('lets admins switch the exam report style between grades and positions', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    expect(AppSettings::examReportMode())->toBe('grades');

    Livewire::test(ExamSettings::class)
        ->set('reportMode', 'positions')
        ->call('saveReportMode')
        ->assertNotified();

    expect(AppSettings::examReportMode())->toBe('positions');
});

it('prefills the marking scheme editor with subjects of the selected class', function (): void {
    $class = StudentClass::factory()->create();
    $maths = Subject::factory()->create(['name' => 'Maths']);
    $art = Subject::factory()->create(['name' => 'Art']);
    $other = Subject::factory()->create(['name' => 'Unrelated']);

    $class->subjects()->attach([$maths->getKey(), $art->getKey()]);

    actingAs(Admin::factory()->create(), 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    $page = Livewire::test(ExamSettings::class)
        ->set('schemeClassId', $class->getKey());

    $component = $page->instance();
    assert($component instanceof ExamSettings);

    expect($component->getSchemeSubjectsProperty()->pluck('name')->all())->toBe(['Art', 'Maths'])
        ->and($component->schemes[$maths->getKey()])->toBe(['min' => '0', 'max' => '100'])
        ->and($component->schemes)->not->toHaveKey($other->getKey());
});

it('lets admins save mark limits per subject of a class', function (): void {
    $class = StudentClass::factory()->create();
    $maths = Subject::factory()->create(['name' => 'Maths']);
    $class->subjects()->attach($maths->getKey());

    actingAs(Admin::factory()->create(), 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire::test(ExamSettings::class)
        ->set('schemeClassId', $class->getKey())
        ->set("schemes.{$maths->getKey()}.min", '5')
        ->set("schemes.{$maths->getKey()}.max", '75')
        ->call('saveSchemes')
        ->assertNotified();

    $scheme = MarkingScheme::query()->sole();

    expect($scheme->student_class_id)->toBe($class->getKey())
        ->and($scheme->subject_id)->toBe($maths->getKey())
        ->and($scheme->min_marks)->toBe(5.0)
        ->and($scheme->max_marks)->toBe(75.0);
});

it('rejects a marking scheme whose minimum exceeds its maximum', function (): void {
    $class = StudentClass::factory()->create();
    $maths = Subject::factory()->create(['name' => 'Maths']);
    $class->subjects()->attach($maths->getKey());

    actingAs(Admin::factory()->create(), 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire::test(ExamSettings::class)
        ->set('schemeClassId', $class->getKey())
        ->set("schemes.{$maths->getKey()}.min", '90')
        ->set("schemes.{$maths->getKey()}.max", '10')
        ->call('saveSchemes')
        ->assertNotified();

    expect(MarkingScheme::query()->count())->toBe(0);
});

it('lets admins create, update and remove grading scale rows in one save', function (): void {
    $stale = GradingScale::factory()->create(['name' => 'Old', 'min_percentage' => 30]);
    $editable = GradingScale::factory()->create(['name' => 'A', 'min_percentage' => 80]);

    actingAs(Admin::factory()->create(), 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire::test(ExamSettings::class)
        ->set('scale', [
            ['id' => $editable->getKey(), 'name' => 'A++', 'min_percentage' => '95'],
            ['id' => null, 'name' => 'B', 'min_percentage' => '60'],
        ])
        ->call('saveScale')
        ->assertNotified();

    expect(GradingScale::query()->pluck('name', 'min_percentage')->all())->toBe([
        95.0 => 'A++',
        60.0 => 'B',
    ])->and(GradingScale::query()->whereKey($stale->getKey())->doesntExist())->toBeTrue()
        ->and(Grades::boundaries())->toBe(['A++' => 95.0, 'B' => 60.0]);
});

it('rejects grading scale rows with duplicate names', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire::test(ExamSettings::class)
        ->set('scale', [
            ['id' => null, 'name' => 'A', 'min_percentage' => '80'],
            ['id' => null, 'name' => 'a', 'min_percentage' => '70'],
        ])
        ->call('saveScale')
        ->assertNotified();

    expect(GradingScale::query()->count())->toBe(0);
});

it('rejects grading scale rows with a threshold outside 0-100', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire::test(ExamSettings::class)
        ->set('scale', [
            ['id' => null, 'name' => 'A', 'min_percentage' => '180'],
        ])
        ->call('saveScale')
        ->assertNotified();

    expect(GradingScale::query()->count())->toBe(0);
});

it('clears the grading scale when every row is removed', function (): void {
    GradingScale::factory()->create(['name' => 'A', 'min_percentage' => 80]);

    actingAs(Admin::factory()->create(), 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire::test(ExamSettings::class)
        ->set('scale', [])
        ->call('saveScale')
        ->assertNotified();

    expect(GradingScale::query()->count())->toBe(0)
        ->and(Grades::boundaries())->toBe([
            'A+' => 90.0,
            'A' => 80.0,
            'B' => 70.0,
            'C' => 60.0,
            'D' => 50.0,
        ]);
});
